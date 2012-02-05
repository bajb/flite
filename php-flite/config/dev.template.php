<?php
$this->SetConfig('display_errors',true);

//Database Settings
$this->SetConfig('databases', array(
    array('flite_name' => 'db', 'hostname' => '127.0.0.1','username' => 'root', 'password' => '', 'database' => ''),
    array('flite_name' => 'dbslave', 'hostname' => '127.0.0.1','username' => 'root', 'password' => '', 'database' => '')
));

$this->SetConfig('cassie_servers',array('localhost'));
$this->SetConfig('membase_servers',array('localhost'));
$this->SetConfig('memcache_servers',array('localhost'));