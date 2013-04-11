<?php

class Model
{
	protected $db;
	protected $cache;
					
	function before_load()
	{
		// print no warning, because it might not be necessary
	}
	
	function __construct()
	{
		$this->before_load();		
		
		if( Config::get('db_type') == 'mysql')
		{
			if( Config::get('mysql_driver_version') == 2)
			{
				$this->db = Mysql2::get_instance();
			}
			else
			{
				$this->db = Mysql::get_instance();
			}			
			
			if( Config::get('memcache') )
			{
				$this->cache = Memcacher::get_instance();
			}
			else
			{
				$this->cache = null;
			}
		}
		else if( Config::get('db_type') == 'mongodb')
		{
			$this->db = Mongo::get_instance();
		}
		else
		{
			trigger_error('invalid db type : ' . Config::get('db_type') .'' , E_USER_ERROR);
		}
		
	}
	
	
}