<?php
define( "REDIS_HOST", '127.0.0.1');
define( "REDIS_PORT", 6379);

class Leaderboard
{
	private static $_leaderboard;
	
	private $_redis;
	
	private function __construct()
	{
		$this->_redis = new Redis();
		$this->_redis->connect( REDIS_HOST, REDIS_PORT);
	}
	
	public static function get_instance()
	{
		if( ! isset( self::$_leaderboard))
		{
			self::$_leaderboard = new Leaderboard();
		}
		
		return self::$_leaderboard;
	}
	
	public function __clone()
	{
		trigger_error('Cloning Leaderboard helper instance is not allowed', E_USER_ERROR);
	}
	
	public function __wakeup()
	{
		trigger_error('Unserializing is not allowed.', E_USER_ERROR);
	}
	
	// function to save, load, update & delete score	
	public function update_member( $user_id, $user_name, $lvl, $total_exp, $week_exp, $today_exp, $yesterday_exp)
	{
		$key = $user_id . '_' . $user_name;
		// add level value to leaderboard:lvl table
		
		// add users when user base is firstly created
		if( $this->_redis->zScore( 'leaderboard:total', $key) !== NULL)
		{
			$this->_redis->zAdd( 'leaderboard:lvl', $key);
			$this->_redis->zAdd( 'leaderboard:total', $total_exp, $key);
			$this->_redis->zAdd( 'leaderboard:week', $week_exp, $key);
			$this->_redis->zAdd( 'leaderboard:yesterday', $yesterday_exp, $key);
			$this->_redis->zAdd( 'leaderboard:today', $today_exp, $key);
		}
		else
		{
			// delta computations are all O(1), so not costly at all =)
			
			// compute delta lvl
			$old_lvl = $this->_redis->zScore( 'leaderboard:lvl', $key);
			$delta_lvl = $lvl - $old_lvl;
			
			// compute delta total_exp
			$old_total_exp = $this->_redis->zScore( 'leaderboard:total', $key);
			$delta_total_exp = $total_exp - $old_total_exp;
			
			// compute delta week_exp
			$old_week_exp = $this->_redis->zScore( 'leaderboard:week', $key);
			$delta_week_exp = $week_exp - $old_week_exp;
			
			// compute delta yesterday_exp
			$old_yesterday_exp = $this->_redis->zScore( 'leaderboard:yesterday', $key);
			$delta_yesterday_exp = $yesterday_exp - $old_yesterday_exp;
			
			// compute delta today_exp
			$old_today_exp = $this->_redis->zScore( 'leaderboad:today', $key);
			$delta_today_exp = $today_exp - $old_total_exp;
			
			// update all tables, complexity : O( 5 * log(N))								
			$this->_redis->zIncrBy( 'leaderboard:lvl', $delta_lvl, $key);
			$this->_redis->zIncrBy( 'leaderboard:total', $delta_total_exp, $key);
			$this->_redis->zIncrBy( 'leaderboard:week', $delta_week_exp, $key);
			$this->_redis->zIncrBy( 'leaderboard:yesterday', $delta_yesterday_exp, $key);
			$this->_redis->zIncrBy( 'leaderboard:today', $delta_today_exp, $key);				
		}		
	} 		

	public function get_rankings( $type = 'total', $debug = false)
	{
		
	}
	
	
}