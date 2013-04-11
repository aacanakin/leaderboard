<?php
/*
 * Mongo Db connector class that provides easy query retrieval
*/

class Mongo // mysql data driver for easy queries. uses mysqli functions in connection
{
	private static $connection;

	private function __construct(){

	}

	public static function get_instance()
	{
		if( !isset(self::$connection))
		{
			$class_name = __CLASS__;
			self::$connection = new $class_name;
		}
		return self::$connection;
	}

	public function __clone()
	{
		trigger_error('cloning db instance is not allowed', E_USER_ERROR);
	}

	public function __wakeup()
	{
		trigger_error('unserializing is not allowed.', E_USER_ERROR);
	}

}