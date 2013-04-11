<?php
class Config
{
	private static $_config = array();
		
	public static function set( $key, $value)
	{		
		if( !isset(self::$_config))
		{
			$class_name = __CLASS__;
			self::$_config = new $class_name;
			self::$_config[$key] = $value;
		}

		if( $key == 'base_url')
		{
			if( $_SERVER['SERVER_NAME'] == 'localhost')
			{
				if( !empty($_SERVER['REQUEST_URI']))
				{
					$relative = explode('/', $_SERVER['REQUEST_URI']);
									
					self::$_config[$key] = $value . '/' . $relative[1];
					return;
				}				
			}
		}
		
		self::$_config[$key] = $value;
	}
	
	public static function get( $key)
	{
		if( !isset(self::$_config))
		{
			$class_name = __CLASS__;
			self::$_config = new $class_name;
			trigger_error('Config::get before Config::set called', E_USER_WARNING);
			return null;
		}
		else if( !isset( self::$_config[$key]))
		{
			trigger_error('invalid key('.$key.') in the Config', E_USER_WARNING);
			return null;			
		}
		else
		{
			return self::$_config[$key];
		}
		
	}
	
	public function __clone()
	{
		trigger_error('cloning configuration instance is not allowed', E_USER_ERROR);
	}
	public function __wakeup()
	{
		trigger_error('deserializing is not allowed.', E_USER_ERROR);
	}
}