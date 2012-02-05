<?php
$this->SetConfig('display_errors',false);

//Database Settings
$this->SetConfig('databases', array(
    array('flite_name' => 'db', 'hostname' => 'localhost','username' => '', 'password' => '', 'database' => ''),
    array('flite_name' => 'dbslave', 'hostname' => 'localhost','username' => '', 'password' => '', 'database' => '')
));

$this->SetConfig('cassie_servers',array('cassandra'));
$this->SetConfig('membase_servers',array('membase'));
$this->SetConfig('memcache_servers',array('memcache'));