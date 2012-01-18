<?php
$this->SetConfig('display_errors',false);

//Database Settings
$this->SetConfig('database_host','db1');
$this->SetConfig('database_slave',array('db2','db3'));
$this->SetConfig('database_name','dbname');
$this->SetConfig('database_user','dbuser');
$this->SetConfig('database_pass','dbpass');

$this->SetConfig('cassie_servers',array('cassandra'));
$this->SetConfig('membase_servers',array('membase'));
$this->SetConfig('memcache_servers',array('memcache'));