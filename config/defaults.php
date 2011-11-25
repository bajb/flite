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

$this->SetConfig('cassie_servers',array());
$this->SetConfig('memcache_servers',array());
$this->SetConfig('membase_servers',array());