<?php
$this->SetConfig('display_errors',true);

//Database Settings
$this->SetConfig('database_host','127.0.0.1');
$this->SetConfig('database_slave',array('127.0.0.1'));
$this->SetConfig('database_name','dbname');
$this->SetConfig('database_user','dbuser');
$this->SetConfig('database_pass','dbpass');

$this->SetConfig('cassie_servers',array('localhost'));
$this->SetConfig('membase_servers',array('localhost'));
$this->SetConfig('memcache_servers',array('localhost'));