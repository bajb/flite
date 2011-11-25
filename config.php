<?php
if(!isset($_SERVER['HOSTNAME'])) $_SERVER['HOSTNAME'] = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';

ini_set('session.gc_maxlifetime','1800');

//Site Settings
$this->SetConfig('site_root',substr(dirname(__FILE__),0,-6) . '/'); //With Trailing Slash

if(!isset($_SERVER['HOSTNAME'])) $_SERVER['HOSTNAME'] = '';
if(!isset($_SERVER['SERVER_ADMIN'])) $_SERVER['SERVER_ADMIN'] = '';

$this->SetConfig('is_dev',true); //in_array($_SERVER['SERVER_ADDR'],array('','127.0.0.1','192.168.0.84')) ?  true : false;
$this->SetConfig('is_web',false);

if(isset($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['DOCUMENT_ROOT'])) $this->SetConfig('is_web',true);

/* Include Cache Files*/
include_once($this->GetConfig('site_root') . 'flite/config/defaults.php');

$this->local_page = $this->page = substr($_SERVER['PHP_SELF'],1,-4);
if(isset($_SERVER['REQUEST_URI'])) $this->local_page = $_SERVER['REQUEST_URI'];

if($this->GetConfig('is_web'))
{
    if(!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
    $serverparts = explode('.',$_SERVER['SERVER_NAME'],3);
    $spcount = count($serverparts);
    $this->sub_domain = $spcount == 3 ? $serverparts[0] : '';
    $this->domain = $serverparts[$spcount - 2];
    $this->tld = $serverparts[$spcount - 1];
}
else
{
    $this->sub_domain = 'www';
    $this->domain = 'phpflite';
    $this->tld = $this->GetConfig('is_dev') ? 'dev' : 'com';
}

$this->SetConfig('site_domain',$this->domain. '.' . $this->tld);
$this->SetConfig('PROTOCOL',(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://');

if($this->GetConfig('is_dev'))
{
    include_once($this->GetConfig('site_root') . 'flite/config/dev.php');
}
else
{
    include_once($this->GetConfig('site_root') . 'flite/config/live.php');
}