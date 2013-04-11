<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 'On');

define ( 'DS', DIRECTORY_SEPARATOR);
define ( 'ROOT', dirname(dirname(__FILE__)));

define ( 'TMP_PATH' ,  		ROOT . DS . 'tmp' );
define ( 'APP_PATH',   		ROOT. DS . 'application');
define ( 'SYSTEM_PATH',   	ROOT . DS . 'library');
define ( 'EXTERNAL_PATH', 	ROOT . DS . 'external');
define ( 'CONFIG_PATH',   	ROOT . DS . 'config');
define ( 'HELPER_PATH',   	ROOT . DS . 'helpers');
define ( 'SCRIPT_PATH',   	ROOT . DS . 'scripts');
define ( 'TEST_PATH',     	ROOT . DS . 'test');
define ( 'MODULE_PATH',		ROOT . DS . 'modules');

define ( 'VIEW_PATH',  		APP_PATH . DS . 'views' );
define ( 'CONTROLLER_PATH', APP_PATH . DS . 'controllers');
define ( 'MODEL_PATH', 		APP_PATH . DS . 'models');

define ( 'SMARTY_PATH', EXTERNAL_PATH . DS . 'smarty' . DS . 'libs' . DS . 'Smarty.class.php' );


require_once SYSTEM_PATH . DS . 'bootstrap.php';
