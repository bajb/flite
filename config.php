<?php
$this->SetConfig('site_root',substr(dirname(__FILE__),0,-6) . '/'); //With Trailing Slash
$this->SetConfig('is_dev',in_array($_SERVER['SERVER_ADDR'],array('','127.0.0.1')) ?  true : false);
$this->SetConfig('is_web',(isset($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['DOCUMENT_ROOT'])));

/* Include Defaults */
include_once($this->GetConfig('site_root') . 'flite/config/defaults.php');

if($this->GetConfig('is_web'))
{
    $serverparts = explode('.',$_SERVER['SERVER_NAME'],3);
    $this->sub_domain = (count($serverparts) === 3 ? current($serverparts) : '');
    $this->domain = next($serverparts);
    $this->tld = next($serverparts);
}
else
{
    $this->sub_domain = 'www';
    $this->domain = 'phpflite';
    $this->tld = $this->GetConfig('is_dev') ? 'dev' : 'com';
}

$this->SetConfig('site_domain',$this->domain. '.' . $this->tld);
$this->SetConfig('protocol',(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://');

if($this->GetConfig('is_dev')) include_once($this->GetConfig('site_root') . 'flite/config/dev.php');
else include_once($this->GetConfig('site_root') . 'flite/config/live.php');