<?php
class UserGen
{
	private static $_user_gen;
	
	private function __construct()
	{
		
	}
	
	public static function get_instance()
	{
		if( ! isset( self::$_user_gen))
		{
			self::$_user_gen = new UserGen();
		}
		return self::$_user_gen;
	}
	
	public function __clone()
	{
		trigger_error('Cloning UserGen instance is not allowed', E_USER_ERROR);
	}
	
	public function __wakeup()
	{
		trigger_error('Unserializing is not allowed.', E_USER_ERROR);
	}
	
	public function generate()
	{
		// generate a dummy non-sense name to be setted as user name
		$user['name'] = $this->get_random_string( rand( 3,10));
		
		// generate a level value between [1,1000]
		$user['lvl'] = rand( 1, 1000);
		
		// generate a total experience value between 1000 * lvl + random value between [0,999]
		$user['total_exp'] = $user['lvl'] * 1000 + rand( 0, 999);
		
		// generate week experince value between [0, total experience]
		$user['week_exp'] = rand( 0, $user['total_exp']);
						
		// generate a boolean value which decides yesterday is in week or not
		$yesterday_in_week = rand(0,1) == 0 ? true : false;
				
		if( $yesterday_in_week)
		{
			// if yesterday is in the week, then yesterday_exp must be between [0, weekly experience]
			$user['yesterday_exp'] = rand( 0, $user['week_exp']);
			
			// today experince must be between [0, weekly experience - yesterday experience]
			$user['today_exp'] = rand( 0, $user['week_exp'] - $user['yesterday_exp']);
		}
		else
		{
			// if yesterday is not in the week, then yesterday could be between [0, total experience - weekly experience]			
			$user['yesterday_exp'] = rand( 0, $user['total_exp'] - $user['week_exp']);
			
			// today experience could be between [0, weekly experince] if yesterday is not inside the week
			$user['today_exp'] = rand( 0, $user['week_exp']);
		}
		
		return $user;
	}
	
	// random string generator function with string length given
	private function get_random_string( $length = 6) {
	
		$valid_characters = "abcdefghijklmnopqrstuxyvwzABCDEFGHIJKLMNOPQRSTUXYVWZ";
		$valid_char_number = strlen( $valid_characters);
	
		$result = "";
	
		for ( $i = 0; $i < $length; $i++)
		{
			$index = mt_rand( 0, $valid_char_number - 1);
			$result .= $valid_characters[ $index];
		}
	
		return $result;
	}
}