<?php
require_once('../php-flite/boot.php');
FC::enable_error_reporting();
$_VIEW = new FCView('www');
$_VIEW->RunPage();