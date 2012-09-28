<?php
/* @var $this FliteApplication */

$this->SetConfig("site_title", 'PHP Flite');
$this->SetConfig('page_title', '');

$this->SetRoutes(
    array(
         '*' => array(
             'controller' => 'default', 'view' => 'default',
             'settings'   => array('controller' => 'default', 'view' => 'default')
         )
    )
);
