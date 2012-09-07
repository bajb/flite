<?php
interface MessageQConsumer
{
    public function Name();
    public function Version();
    public function SetConfig($config);
    public function Process($envelope,$queue=null);
}

class StandardProcessor implements MessageQConsumer
{
    public function Name()
    {
        return "Standard Processor";
    }

    public function Version()
    {
        return "1.0.0.0";
    }

    public function SetConfig($config)
    {
        $this->config = $config;
    }

    public function Process($envelope,$queue = null)
    {
        $encoding = $envelope->getContentEncoding();
        $body = $envelope->getBody();
        $message = MessageQueue::ParseMessage($body,$encoding);
        $user_id = $envelope->getUserId();
        $timestamp = $envelope->getTimestamp();
        $message_id = $envelope->getMessageId();
        $delivery_tag = $envelope->getDeliveryTag();
        $content_type = $envelope->getContentType();
        $redelivery = $envelope->isRedelivery();

        $data_processed = true;

        if($data_processed)
        {
            $queue->ack($delivery_tag);
        }

        echo $body . "\n";
        flush();
    }
}

class MessageQueue
{
    private $host = '';
    private $hosts = array();
    private $port = '';
    private $login = '';
    private $password = '';

    private $connected = false;
    private $connection = null;
    private $channel = null;
    private $exchange = null;
    private $queue = null;
    private $connect_args = array();

    public static function MessageParser($message,$encoding)
    {
        return $encoding == 'MQ:json' ? json_decode($message) : $message;
    }

    public function __construct($connect=false,$host=null,$username=null,$password=null,$port=5672)
    {
        if(!is_null($host))
        {
            if(is_array($host)) $this->hosts = $host;
            else $this->hosts[] = $this->host = $host;
        }
        if(!is_null($port)) $this->port = $port;
        if(!is_null($username)) $this->username = $username;
        if(!is_null($password)) $this->password = $password;
        if($connect) $this->Connect();
    }

    public function __destruct()
    {
        $this->Disconnect();
    }

    public function Disconnect()
    {
        try
        {
            if($this->connected && $this->connection)
            {
                $this->connection->disconnect();
            }
        }
        catch (Exception $e){}
    }

    public function Connect($host=null,$username=null,$password=null,$port=5672,$connect_timeout=0.3)
    {
        if(!class_exists("AMQPConnection"))
        {
            error_log("AMQPConnection Not Available");
            return false;
        }

        if(!is_null($host))
        {
            if(is_array($host)) $this->hosts = $host;
            else $this->hosts[] = $this->host = $host;
        }
        if(!is_null($port)) $this->port = $this->port;
        if(!is_null($username)) $this->username = $this->username;
        if(!is_null($password)) $this->password = $this->password;

        if(empty($this->host) && !empty($this->hosts)) $this->host = current($this->hosts);

        $this->connection = new AMQPConnection(array('host' => $this->host,'port' => $this->port,'login' => $this->username,'password' => $this->password));

        //Connect to the first server as primary, and rotate all the slaves on failure
        shuffle($this->hosts);

        while(!$this->connected && !empty($this->hosts))
        {
            try
            {
                $errno = $errstr = '';
                //Cannot set a timeout value on AMQPConnection::Connect, so attempt to connect to socket where a timeout value can be used :D
                $available = @fsockopen($this->host,$this->port,$errno,$errstr,$connect_timeout);
                if($available)
                {
                    fclose($available);
                    $this->connected = $this->connection->connect();
                }
                else throw new Exception($errstr,$errno);
            }
            catch (Exception $e)
            {
                $this->connected = false;
            }

            if(!$this->connected)
            {
                $this->hosts = array_diff($this->hosts,array($this->host));
                if(!empty($this->hosts))
                {
                    $this->host = current($this->hosts);
                    $this->connection->setHost($this->host);
                }
            }
        }

        if($this->connected)
        {
            $this->channel = new AMQPChannel($this->connection);
            $this->exchange = new AMQPExchange($this->channel);
        }

        return $this->connected;
    }

    public function PublishMessage($exchange_name,$key,$message,$user_id=null,$message_id=null,$attributes=array())
    {
        if(!$this->connected) $this->Connect();
        if(!$this->connected) return false;

        if(!is_string($message))
        {
            $message = json_encode($message);
            $attributes['content_type'] = 'text/json';
            $attributes['content_encoding'] = 'MQ:json';
        }

        if(!isset($attributes['content_type'])) $attributes['content_type'] = 'text/plain';
        if(!isset($attributes['message_id'])) $attributes['message_id'] = $message_id;
        if(!isset($attributes['user_id'])) $attributes['user_id'] = $user_id;
        if(!isset($attributes['timestamp'])) $attributes['timestamp'] = time();

        foreach ($attributes as $k => $v) if(is_null($v)) unset($attributes[$k]);

        try
        {
            $this->exchange->setName($exchange_name);
            return $this->exchange->publish($message,$key,AMQP_NOPARAM,$attributes);
        }
        catch (Exception $e)
        { }

        return false;
    }

    public function ParseMessage($message,$encoding)
    {
        return $encoding == 'MQ:json' ? json_decode($message) : $message;
    }

    public function ConsumeQueue($queue_name,$class='StandardProcessor',$consumer_config=array(),$sleep_seconds=2,$max_run_seconds=3600)
    {
        if(!$this->connected) $this->Connect();
        if(!$this->connected) return false;

        $this->queue = new AMQPQueue($this->channel);
        $this->queue->setName($queue_name);

        $processor = new $class();

        if(!in_array("MessageQComsumer",class_implements($processor)))
        {
            throw new Exception("$class must implement MessageQComsumer");
        }

        $processor->SetConfig($consumer_config);
        $start = time();

        while(true)
        {
            $message = $this->queue->get(0);
            if($message === false)
            {
                sleep($sleep_seconds);
            }
            else
            {
                $processor->Process($message,$this->queue);
            }
            if(time() - $start > $max_run_seconds) break;
        }
    }
}