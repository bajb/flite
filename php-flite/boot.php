<?php
define('PHP_FLITE_START_TIME', microtime(true));
define('FLITE_DIR',dirname(__FILE__));

require_once (FLITE_DIR . '/thirdparty/phpcassa-1.0.a.2/lib/autoload.php');
use phpcassa\Connection\ConnectionPool;

class Flite
{
    public static $flite = null;
    public static $app = null;
    public static $cache = null;


    /**
     * @static
     * @param string $rel_path
     * @return FliteBase
     */
    public static function Base ($rel_path = '/')
    {
        if (self::$flite === null) self::$flite = new FliteBase($rel_path);
        return self::$flite;
    }


    /**
     * @static
     * @param null $site_view
     * @param null $branding
     * @param string $html_doctype
     * @return FliteApplication
     */
    public static function App ($site_view = null, $branding = null, $html_doctype = 'html5')
    {
        if (self::$app === null) self::$app = new FliteApplication($site_view, $branding, $html_doctype);
        return self::$app;
    }


    /**
     * @static
     * @return FliteCache
     */
    public static function Cache ()
    {
        if (self::$cache === null) self::$cache = new FliteCache();
        return self::$cache;
    }
}

class FliteDataObject implements IteratorAggregate
{
    private $_data = array();
    private $position = 0;

    public function __construct($data)
    {
        $this->Populate($data);
        $this->position = 0;
    }

    public function Populate($data)
    {
        if(is_object($data) || is_array($data))
        {
            foreach ($data as $k => $v) $this->Set($k,$v);
        }
    }

    public function __set($key,$value)
    {
        return $this->Set($key,$value);
    }

    public function __get($key)
    {
        return $this->Get($key);
    }

    public function __isset($key)
    {
        return isset($this->_data[$key]);
    }

    public function Exists($key)
    {
        return isset($this->_data[$key]);
    }

    public function Set($key,$value)
    {
        return $this->_data[$key] = $value;
    }

    public function Get($key,$default=null)
    {
        return isset($this->_data[$key]) ? $this->_data[$key] : $default;
    }

    public function GetArray($key,$default=array())
    {
        if(isset($this->_data[$key]))
        {
            return is_array($this->_data[$key]) ? $this->_data[$key] :
                (is_object($this->_data[$key]) ? FC::object_to_array($this->_data[$key]) : array());
        }
        else return $default;
    }

    public function GetInt($key,$default=0)
    {
        if(isset($this->_data[$key])) return intval($this->_data[$key]);
        else return $default;
    }

    public function GetFloat($key,$default=0)
    {
        if(isset($this->_data[$key])) return floatval($this->_data[$key]);
        else return $default;
    }

    public function GetBoolean($key,$default=false)
    {
        if(isset($this->_data[$key]))
        {
            return in_array($this->_data[$key], array('true','1',1,true), true);
        }
        return $default;
    }

    public function getIterator()
    {
        $o = new ArrayObject($this->_data);
        return $o->getIterator();
    }
}

class FliteConfig
{
    protected $config;

    public function GetConfig ($key, $default = false)
    {
        if (isset($this->config->$key)) return $this->config->$key;

        if (get_class($this) == 'FliteApplication')
        {
            $_FLITE = Flite::Base();
            return $_FLITE->GetConfig($key, $default);
        }
        return $default;
    }

    public function GetConfigAKey ($key, $array_key, $default = false, $return_object = true)
    {
        if (isset($this->config->{$key}[$array_key])) return $return_object ? FC::array_to_object(
                $this->config->{$key}[$array_key]) : $this->config->{$key}[$array_key];

        if (get_class($this) == 'FliteApplication')
        {
            $_FLITE = Flite::Base();
            return $_FLITE->GetConfigAKey($key, $array_key, $default, $return_object);
        }
        return $default;
    }

    public function SetConfig ($key, $value)
    {
        if (! isset($this->config) || is_null($this->config)) $this->config = new stdClass();
        return $this->config->$key = $value;
    }
}

class FliteBase extends FliteConfig
{
    private $metrics;
    private $exceptions = array();
    public $start_session = true;
    public $tweak_server_value = true;
    private $initiated = false;

    public function __construct ($rel_path = '/')
    {
        if (! $this->initiated)
        {
            if (! isset($this->metrics) || is_null($this->metrics)) $this->metrics = new stdClass();
            $this->metrics->page_execution_start = microtime(true);
            $this->SetConfig('relative_path', $rel_path);
            $this->BootFlite();
            $this->initiated = true;
        }
    }

    private function Loader ($classname)
    {
        $b = FLITE_DIR . '/dblib/';
        $classname = strtolower($classname);

        if (file_exists($b . str_replace('_', '/', $classname) . '.php'))
        {
            include_once ($b . str_replace('_', '/', $classname) . '.php');
        }
        else if (file_exists($b . $classname . '.php'))
        {
            include_once ($b . $classname . '.php');
        }
        else if (file_exists(FLITE_DIR . '/lib/' . str_replace('_', '/', $classname) .
                 '.php'))
        {
            include_once (FLITE_DIR . '/lib/' . str_replace('_', '/', $classname) . '.php');
        }
        else if (file_exists(FLITE_DIR . '/lib/' . $classname . '.php'))
        {
            include_once (FLITE_DIR . '/lib/' . $classname . '.php');
        }
    }

    public function LoadFiles ($directory, $ext = '.php')
    {
        $handle = opendir($directory);
        if ($handle)
        {
            while (false !== ($file = readdir($handle)))
            {
                $ext_length = strlen($ext);
                if (substr($file, ($ext_length * - 1)) == $ext)
                {
                    include_once ($directory . $file);
                }
            }
            closedir($handle);
            return true;
        }
        else
            return false;
    }

    private function BootFlite ()
    {
        if ($this->tweak_server_value)
        {
            if (isset($_SERVER['HTTP_HOST']) && substr($_SERVER['HTTP_HOST'], - 5) == ':8080') $_SERVER['HTTP_HOST'] = substr(
                    $_SERVER['HTTP_HOST'], 0, - 5);
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
            if (isset($_SERVER['HTTP_X_REAL_FORWARDED_FOR'])) $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_REAL_FORWARDED_FOR'];
            if (! isset($_SERVER['HOSTNAME'])) $_SERVER['HOSTNAME'] = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
            if (! isset($_SERVER['HOSTNAME'])) $_SERVER['HOSTNAME'] = '';
            if (! isset($_SERVER['SERVER_ADMIN'])) $_SERVER['SERVER_ADMIN'] = '';
            if (! isset($_SERVER['SERVER_NAME']) && isset($_SERVER['HTTP_HOST'])) $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
        }

        if (stristr($_SERVER['PHP_SELF'], '.') !== false)
            list ($this->page, ) = explode('.', $_SERVER['PHP_SELF'], 2);
        else $this->page = $_SERVER['PHP_SELF'];
        $this->page = str_replace($this->GetConfig('relative_path', '/'), '/', $this->page);
        $this->local_page = $this->page;
        if (isset($_SERVER['REQUEST_URI']) && ! empty($_SERVER['REQUEST_URI']))
        {
            list ($this->local_page, ) = explode('?', $_SERVER['REQUEST_URI'], 2);
            $this->local_page = str_replace($this->GetConfig('relative_path', '/'), '/', $this->local_page);
        }

        if (! @include_once (dirname(__FILE__) . '/cache/config.php')) include_once (dirname(__FILE__) . '/config.php');

        error_reporting($this->GetConfig('error_reporting'));
        ini_set('display_errors', $this->GetConfig('display_errors'));

        if (! @include_once (FLITE_DIR . '/cache/core.lib.php')) $this->LoadFiles(
            FLITE_DIR . '/core.lib/');

        if ($this->GetConfig('zlib_enabled'))
        {
            ini_set('zlib.output_compression', 'On');
            ini_set('zlib.output_compression_level', $this->GetConfig('zlib_level'));
        }
        else
        {
            ini_set('zlib.output_compression', 'Off');
            ini_set('zlib.output_compression_level', 0);
        }

        if ($this->start_session && $this->GetConfig('is_web'))
        {
            session_start();
        }

        spl_autoload_register(array($this,'Loader'));

        if (is_array($this->GetConfig('databases')))
        {
            $dbs = $this->GetConfig('databases');
            foreach ($dbs as $db_conf)
            {
                $this->$db_conf['flite_name'] = new DBConnection($db_conf['hostname'], $db_conf['username'],
                        $db_conf['password'], $db_conf['database']);
            }
        }
        else
        {
            $this->db = new DBConnection($this->GetConfig('database_host'), $this->GetConfig('database_user'),
                    $this->GetConfig('database_pass'), $this->GetConfig('database_name'));
            $this->dbslave = new DBConnection($this->GetConfig('database_slave'), $this->GetConfig('database_user'),
                    $this->GetConfig('database_pass'), $this->GetConfig('database_name'));
        }

        $this->membase = new Membase();
        if ($this->GetConfig('membase_servers') != false && FC::count($this->GetConfig('membase_servers')) > 0)
        {
            foreach ($this->GetConfig('membase_servers') as $mserv)
            {
                if (is_array($mserv))
                {
                    $this->membase->addServer($mserv['host'], isset($mserv['port']) ? $mserv['port'] : 11211,
                            isset($mserv['persistent']) ? $mserv['persistent'] : true);
                }
                else
                    $this->membase->addServer($mserv);
            }
        }
        else
            $this->membase->addServer('localhost');

        $this->local_memcache = new Memcache();
        $this->local_memcache->addServer('localhost', 11211, true);

        if ($this->GetConfig('memcache_servers') != false && FC::count($this->GetConfig('memcache_servers')) > 0)
        {
            $this->memcache = new Memcache();
            foreach ($this->GetConfig('memcache_servers') as $mserv)
            {
                if (is_array($mserv))
                {
                    $this->memcache->addServer($mserv['host'], isset($mserv['port']) ? $mserv['port'] : 11211,
                            isset($mserv['persistent']) ? $mserv['persistent'] : true);
                }
                else
                    $this->memcache->addServer($mserv);
            }
        }
        else
            $this->memcache = new stdClass();

        $this->cassandra = new stdClass();
        $cassandra_clustername = $this->GetConfig('cassandra_cluster');
        if ($cassandra_clustername)
        {
            if (is_array($cassandra_clustername))
            {
                foreach ($cassandra_clustername as $keyspace)
                {
                    if (is_array($keyspace))
                    {
                        $this->{(isset($keyspace['flite_name']) ? $keyspace['flite_name'] : "cassandra_" . $keyspace)} = new ConnectionPool(
                                $keyspace['keyspace'],
                                (isset($keyspace['nodes']) ? $keyspace['nodes'] : $this->GetConfig('cassie_servers',
                                        null)));
                    }
                    else
                    {
                        $this->{(FC::count($cassandra_clustername) == 1 ? 'cassandra' : "cassandra_" . $keyspace)} = new ConnectionPool(
                                $keyspace, $this->GetConfig('cassie_servers', null));
                    }
                }
            }
            else
            {
                $this->cassandra = new ConnectionPool($cassandra_clustername, $this->GetConfig('cassie_servers', null));
            }
        }

        if (is_array($this->GetConfig('message_exchanges')))
        {
            $mqs = $this->GetConfig('message_exchanges');
            foreach ($mqs as $mq_conf)
            {
                $this->$mq_conf['flite_name'] = new MessageQueue(false, $mq_conf['hosts'], $mq_conf['username'],
                        $mq_conf['password'], isset($mq_conf['port']) ? $mq_conf['port'] : 5672);
            }
        }

        $this->LoadFiles(FLITE_DIR . '/included/');

        $this->Define();
    }

    public function Define ()
    {
        define('DOMAIN', $this->domain);
        define('TLD', $this->tld);
        define('SUB_DOMAIN', $this->sub_domain);
        define('REL_PATH', $this->GetConfig('relative_path', '/'));
        define('PROTOCOL', $this->protocol);
    }

    public function DebugTime ($call, $force = false, $return_time = false)
    {
        if ($this->GetConfig('show_echo') || $force)
        {
            $this->metrics->now = microtime(true);
            $start = $this->metrics->now - $this->metrics->page_execution_start;
            $start = round($start * 1000, 3);
            $last = isset($this->metrics->page_execution_last) ? $this->metrics->now -
                     $this->metrics->page_execution_last : 0;
            $last = round($last * 1000, 3);
            $this->metrics->page_execution_last = microtime(true);

            if (! $return_time)
                echo "\n<br /><h2>$call</h2><br>Time Since Start: <strong>$start</strong>ms, Time Since Last Check: <strong>$last</strong>ms\n<br>";
            else return $start;
        }
    }

    public function SendErrorReport ($script, $message, $errorno = 0)
    {
        return mail($this->GetConfig('debug_report_email'),
                'Error Report' . ($errorno > 0 ? ' : Err No (' . $errorno . ')' : ''),
                "Script Name: $script\n\nError:\n\n" . $message);
    }

    public function Exception ($class, $method, $exception)
    {
        $e = new stdClass();
        $e->class = $class;
        $e->method = $method;
        $e->exception = $exception;
        $this->exceptions[] = $e;
        return true;
    }
}

$_FLITE = Flite::Base(isset($relative_path) ? $relative_path : '/');
