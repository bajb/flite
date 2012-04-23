<?php
$this->SetConfig('display_errors',false);
$this->SetConfig('error_reporting',E_ERROR);

$this->SetConfig('debug_report_email','help@phpflite.com');

//Performance
$this->SetConfig('gzip_enabled',false);
$this->SetConfig('zlib_enabled',false);
$this->SetConfig('zlib_level',5);

//Formatting Options
$this->SetConfig('long_date','Y-m-d H:i:s');
$this->SetConfig('short_date','Y-m-d');

$this->SetConfig('debug_curl',false);
$this->SetConfig('show_echo',false);

$this->SetConfig('dev_hostname','dev.example.local'); // Use hostname to detect if running on development platform

$this->SetConfig('cassie_servers',array());
$this->SetConfig('cassandra_cluster',false);
//$this->SetConfig('cassandra_cluster',array('Keyspace' => array('flite_name' => 'flite_cassandra_variable', 'keyspace' => 'Keyspace', 'nodes' => array('localhost')))); // Multiple Cassandra Connection Support
$this->SetConfig('memcache_servers',array());
$this->SetConfig('membase_servers',array());

//Cookies Configs
$this->SetConfig('cookie_salt', 'J�94@#jhf');
//$this->SetConfig('cookie_path','/foo/');
//$this->SetConfig('cookie_domain','.'.$_FLITE->domain.'.'.$_FLITE->tld);

$this->SetConfig('whitelist_ips', array('81.144.208.5','81.144.208.6','81.144.208.7','81.144.208.8','81.144.208.9','81.144.208.10')); // Whitelist IPs for FC::is_whitelist_ip()

$this->SetConfig('static_sub_domain', 'static');

//Database Configuration
$this->SetConfig('databases', array(
    array('flite_name' => 'db', 'hostname' => 'localhost','username' => 'default_username', 'password' => 'default_password', 'database' => 'databasename', 'classname_prefix' => '')
));

$this->SetConfig('message_exchanges',array(
    array('flite_name' => 'mq', 'hosts' => array('localhost'), 'username' => 'guest', 'password' => 'guest')
));
