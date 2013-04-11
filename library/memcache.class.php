<?php
class Memcacher
{
	private static $_memcache;
	private $connection;
	
	private function __construct()
	{
		$this->connection = new Memcache;	
		$this->connection->addServer( Config::get('memcache_server_ip'), Config::get('memcache_server_port'));
	}
	
	public static function get_instance()
	{
		if( ! Config::get('memcache'))
		{
			trigger_error('memcache module is not enabled, to use memcache, please enable memcache usage by setting Config::set(\'memcache\', true);', E_USER_WARNING);
			return null;
		}
		
		if( !isset( self::$_memcache))
		{
			
			self::$_memcache = new Memcacher();
		}		
					
		return self::$_memcache;
	
	}
	
	public function __clone()
	{
		trigger_error('cloning memcache instance is not allowed', E_USER_ERROR);	
	}
	
	public function __wakeup()
	{
		trigger_error('deserializing memcache instance is not allowed.', E_USER_ERROR);
	}
	
	public function save( $key, $object)
	{
		$this->connection->set( $key, $object, false, Config::get('memcache_lifetime'));
	}
	
	public function load( $key)
	{
		return $this->connection->get( $key);
	}
	
	public function invalidate( $key)
	{
		if( $this->connection->get( $key))
		{
			$this->connection->delete( $key);	
		}
	}
	
	public function remove_all()
	{
		$this->connection->flush();
	}
	
	public function generate_key( $query )
	{
		return $query;
	}
	
	
}