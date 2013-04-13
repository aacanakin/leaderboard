<?php
define( "REDIS_HOST", 'localhost');
define( "REDIS_PORT", 6379);

// leaderboard helper using redis connection
// @todo: redis should be inside model functions, it is not so convenient to use redis inside helper

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
	
	// alias of build_user_base in redis part	

	// every table items keyed with {user_id}_{user_name}
	
	// function removes redis tables, re-creates with all scores set to 0
	// time complexity : O( log(N) + N) + O( log(N))
	public function init( $users = array())
	{
		// initialize table
		foreach( $users as $a_user)
		{
			$key = $a_user['user_id'] . '_' . $a_user['name'];

			$this->_redis->zRemRangeByRank('leaderboard_total', 0, 100);
			$this->_redis->zRemRangeByRank( 'leaderboard_level', 0, 100);
			$this->_redis->zRemRangeByRank( 'leaderboard_total', 0, 100);
			$this->_redis->zRemRangeByRank( 'leaderboard_week', 0, 100);
			$this->_redis->zRemRangeByRank( 'leaderboard_yesterday', 0,100);
			$this->_redis->zRemRangeByRank( 'leaderboard_today', 0,100);
		}
		
		foreach( $users as $a_user)
		{			
			$key = $a_user['user_id'] . '_' . $a_user['name'];
										
			$this->_redis->zAdd( 'leaderboard_level', 0, $key);
			$this->_redis->zAdd( 'leaderboard_total', 0, $key);
			$this->_redis->zAdd( 'leaderboard_week', 0, $key);
			$this->_redis->zAdd( 'leaderboard_yesterday', 0, $key);
			$this->_redis->zAdd( 'leaderboard_today', 0, $key);
			
		}				
				
	}
		
	// updates level value of a given user with id & name
	// time complexity : O( log(N))
	public function update_level( $user_id, $user_name, $level)
	{
		$key = $user_id . '_' . $user_name;

		// inserts or updates
		$this->_redis->zAdd( 'leaderboard_level', $level, $key);					
	}
	
	// updates total_exp value of a given user with id & name
	// time complexity : O( log(N))
	public function update_total_exp( $user_id, $user_name, $total_exp)
	{
		$key = $user_id . '_' . $user_name;
		
		$this->_redis->zAdd( 'leaderboard_total', $total_exp, $key);		
	}
	
	// updates week_exp value of a given user with id & name
	// time complexity : O( log(N))
	public function update_week_exp( $user_id, $user_name, $week_exp)
	{
		$key = $user_id . '_' . $user_name;
				
		$this->_redis->zAdd( 'leaderboard_week', $week_exp, $key);		
	}		
	
	// updates yesterday_exp value of a given user with id & name
	// time complexity : O( log(N))
	public function update_yesterday_exp( $user_id, $user_name, $yesterday_exp)
	{
		$key = $user_id . '_' . $user_name;
						
		$this->_redis->zAdd( 'leaderboard_yesterday', $yesterday_exp, $key);
	}
	
	// updates today_exp value of a given user with id & name
	// time complexity : O( log(N))
	public function update_today_exp( $user_id, $user_name, $today_exp)
	{
		$key = $user_id . '_' . $user_name;
				
		$this->_redis->zAdd( 'leaderboard_today', $today_exp, $key);
	}

	// sets every item inside leaderboard_week table to zero given an array of users with id & name
	// time complexity : O( log(N))
	public function reset_week( $users = array())
	{
		foreach( $users as $a_user)
		{
			$key = $a_user['user_id'] . '_' . $a_user['name'];
			
			$this->_redis->zAdd( 'leaderboard_week', 0, $key);																
		}				
	}
	
	// moves every item inside leaderboard_yesterday to leaderboard_today
	// sets every item inside leaderboard_today to zero given an array of users with id & name
	// time complexity : O( 2 * log(N)) + 2 * O( 1) = O (log( N)) 
	public function reset_day( $users = array())
	{
		foreach( $users as $a_user)
		{
			$key = $a_user['user_id'] . '_' . $a_user['name'];
			$today_exp = $this->_redis->zScore( 'leaderboard_today', $key);						
			$yesterday_exp = $this->_redis->zScore( 'leaderboard_yesterday', $key);
			
			$this->_redis->zAdd('leaderboard_yesterday', $today_exp, $key);
			$this->_redis->zAdd('leaderboard_today', 0, $key);														
		}				
	}
		
	// returns the leaderboard array given type, player_count and debug mode
	// type can be total, week, yesterday and today
	// player_count can be [1,100]
	// debug can be true, false
	// time complexity : O( log(N) + M) + O( M) = O( M)
	
	public function get_rankings( $type = 'total', $player_count = 10, $debug = false)
	{
		$return = array();
		
		switch( $type)
		{
			case 'total':
				
				$msc = microtime( true);
													
				$total = $this->_redis->zRevRange('leaderboard_total', 0, $player_count - 1);				
				
				$msc = microtime( true) - $msc;
				
				if( $debug)
				{
					$return['debug']['exec_time'] = $msc * 1000 . ' ms';
				}
				
				$i = 0;
				foreach( $total as $user)
				{
					$raw_user = explode( '_', $user);
					$return['leaderboard'][$i]['user_id'] = $raw_user[0];
					$return['leaderboard'][$i]['name'] = $raw_user[1];
					$return['leaderboard'][$i]['total_exp'] = $this->_redis->zScore( 'leaderboard_total', $user);
					$return['leaderboard'][$i]['level'] = $this->_redis->zScore( 'leaderboard_level', $user);
					$i++;
				}
				
				break;
			
			case 'week':
				
				$msc = microtime( true);
				
				$week = $this->_redis->zRevRange('leaderboard_week', 0, $player_count - 1);
				
				$msc = microtime( true) - $msc;
				
				if( $debug)
				{
					$return['debug']['exec_time'] = $msc * 1000 . ' ms';
				}
				
				$i = 0;
				foreach( $week as $user)
				{
					$raw_user = explode( '_', $user);
					$return['leaderboard'][$i]['user_id'] = $raw_user[0];
					$return['leaderboard'][$i]['name'] = $raw_user[1];
					$return['leaderboard'][$i]['week_exp'] = $this->_redis->zScore( 'leaderboard_week', $user);
					$return['leaderboard'][$i]['level'] = $this->_redis->zScore( 'leaderboard_level', $user);
					$i++;
				}
											
				break;
			
			case 'yesterday':
			
				$msc = microtime( true);
				
				$yesterday = $this->_redis->zRevRange('leaderboard_yesterday', 0, $player_count - 1);
				
				$msc = microtime( true) - $msc;
				
				if( $debug)
				{
					$return['debug']['exec_time'] = $msc * 1000 . ' ms';
				}
				
				$i = 0;
				foreach( $yesterday as $user)
				{
					$raw_user = explode( '_', $user);
					$return['leaderboard'][$i]['user_id'] = $raw_user[0];
					$return['leaderboard'][$i]['name'] = $raw_user[1];
					$return['leaderboard'][$i]['yesterday_exp'] = $this->_redis->zScore( 'leaderboard_yesterday', $user);
					$return['leaderboard'][$i]['level'] = $this->_redis->zScore( 'leaderboard_level', $user);
					$i++;
				}
													
				break;
			
			case 'today':

				$msc = microtime( true);
				
				$today = $this->_redis->zRevRange('leaderboard_today', 0, $player_count - 1);
				
				$msc = microtime( true) - $msc;
				
				if( $debug)
				{
					$return['debug']['exec_time'] = $msc * 1000 . ' ms';
				}
				
				$i = 0;
				foreach( $today as $user)
				{
					$raw_user = explode( '_', $user);
					$return['leaderboard'][$i]['user_id'] = $raw_user[0];
					$return['leaderboard'][$i]['name'] = $raw_user[1];
					$return['leaderboard'][$i]['today_exp'] = $this->_redis->zScore( 'leaderboard_today', $user);
					$return['leaderboard'][$i]['level'] = $this->_redis->zScore( 'leaderboard_level', $user);
					$i++;
				}								
				
				break;
				
			default:
				$return['leaderboard'] = null;
				break;
		}
		
		return $return;
	}
	
	
}