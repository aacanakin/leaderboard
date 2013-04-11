<?php

/*
 * include custom routes if any available
*/
if( file_exists( CONFIG_PATH . DS . 'routes.php'))
{
	require_once( CONFIG_PATH . DS . 'routes.php');
}
else
{
	trigger_error( 'custom routes file not found', E_USER_WARNING);
}

/*
 *  call core functions
 */

set_error_reporting();
remove_magic_quotes();
unregister_globals();

// set error handler 
if( Config::get('error_handler') === 'ariba')
{
	set_error_handler('error_handler', E_ALL ^ E_STRICT);
	register_shutdown_function('fatal_error_handler');
}
else if( Config::get('error_handler') === 'default')
{
	// do nothing...
}
else 
{
	trigger_error('invalid error handler option in config.php, must be set either ariba or default : '. Config::get('error_handler').'' ,E_USER_ERROR);
	exit;
}

Router::get_instance()->hook();

// automagical load function
function __autoload( $class_name)
{	
	
	if( $class_name === 'Config')
	{
		require_once SYSTEM_PATH . DS . 'config.class.php';
		require_once CONFIG_PATH . DS . 'config.php';
		require_once CONFIG_PATH . DS . 'db.php';
	}
	else if( $class_name === 'Mysql')
	{
		require_once SYSTEM_PATH . DS . 'db' . DS . 'mysql' . DS . 'driver.class.php';	
	}
	else if( $class_name === 'Mysql2')
	{
		require_once SYSTEM_PATH . DS . 'db' . DS . 'mysql' . DS . 'driver.class.2.php';
	}
	else if( $class_name === 'Mongo')
	{
		require_once SYSTEM_PATH . DS . 'db' . DS . 'mongodb' . DS . 'driver.class.php';
	}
	else if( $class_name === 'Memcacher')
	{
		require_once SYSTEM_PATH . DS . 'memcache.class.php';
	}
	else if( substr(strtolower($class_name), 0, 16) === 'smarty_internal_' || strtolower($class_name) == 'smarty_security')
	{
		/** fix smarty class autoload **/
		include SMARTY_SYSPLUGINS_DIR . strtolower($class_name) . '.php';
	}
	else if ( file_exists( SYSTEM_PATH . DS . strtolower( $class_name) . '.class.php')) // autoload  
	{
		 require_once ( SYSTEM_PATH . DS . strtolower($class_name) . '.class.php');
	} 
	else if ( file_exists( CONTROLLER_PATH . DS . strtolower( substr( $class_name, 0, -10)) . '.php')) 
	{
		require_once( CONTROLLER_PATH . DS . strtolower( substr( $class_name, 0, -10)) . '.php');
	} 
	else if ( file_exists( MODEL_PATH . DS . strtolower( substr( $class_name, 0, -5)) . '.php')) 
	{
		require_once( MODEL_PATH . DS . strtolower( substr( $class_name, 0, -5)) . '.php');
	} 
	else {
			
		trigger_error('class : '.$class_name.' not found', E_USER_ERROR);					
	}	
}