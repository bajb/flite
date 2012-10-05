<?php
// Site Root with trailing slash
$site_root = (substr(substr(dirname(__FILE__),0,-6),-5) == 'flite' ?
    substr(dirname(__FILE__),0,-16) : substr(dirname(__FILE__),0,-10)) . '/';

$this->SetConfig('site_root', $site_root);

if($site_root !== FLITE_PRODUCTION_ROOT)
{
    $is_local = $is_dev = false;

    if(isset($_SERVER['SERVER_ADDR']) && in_array($_SERVER['SERVER_ADDR'], array('', '127.0.0.1')))
    {
        if(isset($_SERVER['SERVER_NAME']) && stristr($_SERVER['SERVER_NAME'], '.local'))
        {
            $is_local = true;
        }
        if(isset($_SERVER['SERVER_NAME']) && stristr($_SERVER['SERVER_NAME'], '.dev'))
        {
            $is_dev = true;
        }
    }
    $this->SetConfig('is_local', $is_local);
    $this->SetConfig('is_dev', $is_dev);
}

$this->SetConfig('is_web',(isset($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['DOCUMENT_ROOT'])));
if(defined('FLITE_WIN') && FLITE_WIN) $this->SetConfig('is_local',true);

if($this->GetConfig('is_local'))
{
    // todo extract these to a configurable ini file
    $web_roots = array('internal', 'flite_html', 'inbound', 'flow');
    $this->SetConfig('site_root', rtrim(str_replace($web_roots, '', getcwd()), '\\/') .'/');
}

/* Include Defaults */
include_once(FLITE_DIR . '/config/defaults.php');

if(isset($_SERVER['HOSTNAME']) && $_SERVER['HOSTNAME'] == $this->GetConfig("dev_hostname"))
{
    $this->SetConfig('is_dev',true);
}

if($this->GetConfig('is_web'))
{
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

$this->protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ||
                    (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'],'HTTPS'))) ? 'https://' : 'http://';
$this->SetConfig('protocol',$this->protocol);
$this->SetConfig('full_domain',$this->protocol.$this->sub_domain.'.'.$this->domain.'.'.$this->tld.'/');

if($this->GetConfig('is_local'))
{
    define('FLITE_ENV','local');
    include_once(FLITE_DIR . '/config/local.php');
}
else if($this->GetConfig('is_dev'))
{
    define('FLITE_ENV','dev');
    include_once(FLITE_DIR . '/config/dev.php');
}
else
{
    define('FLITE_ENV','live');
    include_once(FLITE_DIR . '/config/live.php');
}

if(file_exists(FLITE_DIR . '/config/'. $this->domain .'.php'))
{
    include_once(FLITE_DIR . '/config/'. $this->domain .'.php');
}
