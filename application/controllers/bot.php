<?php
class BotController extends Controller
{
	function before_load()
	{
		$this->load_model('bot');
		$this->load_model('score');
		$this->load_model('user');
		
		$this->load_helper('leaderboard');
		$this->load_helper('usergen');
		
		Leaderboard::get_instance();
	}
	
	private function http_auth()
	{
	
		if ( !isset($_SERVER['PHP_AUTH_USER']))
		{
	
			header('WWW-Authenticate: Basic realm="Private zone baby!"');
			header('HTTP/1.0 401 Unauthorized');
			exit;
		}
		else
		{
			if( $_SERVER['PHP_AUTH_USER'] != 'bot' || $_SERVER['PHP_AUTH_PW'] != 'test')
			{
				header('WWW-Authenticate: Basic realm="Private zone baby!"');
				header('HTTP/1.0 401 Unauthorized');
				exit;
			}
			else
			{
				return true;
			}
		}
	}
		
	function init()
	{
		if( $this->http_auth())
		{
			$delete = $this->bot->init();			
						
			// each call of this function generates 100 user
			$users = array();
			for( $i = 0; $i < 100; $i++)
			{							
				$user = UserGen::get_instance()->generate();
				
				$add_user = $this->bot->add_user( $user['name']);
				
				if( !$add_user['user']['error'] || !$add_user['score']['error'])
				{	
					$users[$i]['user_id'] = $add_user['user']['insert_id'];
					$users[$i]['name'] = $user['name'];					
				}			
				else
				{
					$return['error'] = true;
					$return['msg'] = 'User ' . $user['name'] . ' can not be created';
					return $return; 
				}																
			}
			
			if( !empty( $users))
			{
				Leaderboard::get_instance()->init( $users);
				$return['error'] = false;
				$return['msg'] = 'User base created successfully';
			}
			else
			{
				$return['error'] = true;
				$return['msg'] = 'Leaderboard redis user base can not be created';
			}
			
			echo json_encode( $return);
		}
		else
		{
			echo 'BAD REQUEST';
			exit;
		}
	}
	
	function change_day( $ajax = true)
	{
		// reset yesterday
		// move_points to yesterday
		if( $this->http_auth())
		{
			$return = array();
		
			$move_yesterday = $this->score->move_yesterday();
			
			if( !$move_yesterday['error'] )
			{
				$reset_yesterday = $this->score->reset_today();
				if( !$reset_yesterday['error'] )
				{		
					$get_users = $this->user->get_users();
					if( !empty( $get_users['value']))
					{
						Leaderboard::get_instance()->reset_day( $get_users['value']);
						
						$return['error'] = false;
						$return['msg'] = 'Day changed successfully';					
					}
					else
					{
						$return['error'] = true;
						$return['msg'] = 'Unable to get users';	
					}								
				}
				else
				{
					$return['error'] = true;
					$return['msg'] = 'Unable to reset yesterday';
				}			
			}
			else
			{
				$return['error'] = true;
				$return['msg'] = 'Unable to move yesterday';
			}
			
			
			if( $ajax)
			{
				echo json_encode( $return);
				exit;
			}
			else
			{
				$this->look( $return);
			}	
		}
		else
		{
			echo 'BAD REQUEST';
			exit;
		}	
 		
	}
	
	function change_week( $ajax = true)
	{
		if( $this->http_auth())
		{
			$return = array();
			$reset_week = $this->score->reset_week();
			
			if( !$reset_week['error'] && $reset_week['affected_rows'] > 0)
			{
				$get_users = $this->user->get_users();
				if( !empty( $get_users['value']))
				{
					Leaderboard::get_instance()->reset_week( $get_users['value']);
					
					$return['error'] = false;	
					$return['msg'] = 'Week changed successfully';					
																
				}
				else
				{
					$return['error'] = true;
					$return['msg'] = 'Unable to get all users';
				}				
			}
			else
			{
				$return['error'] = true;
				$return['msg'] = 'Unable to change week';
			}
			
			if( $ajax)
			{
				echo json_encode( $return);
				exit;
			}
			else
			{
				$this->look( $return);
			}
		}
		else
		{
			echo 'BAD REQUEST';
			exit;
		}
		
	}
	
	// $player_count : indicates how many random players to simulate	
	
	function simulate( $player_count = 100, $ajax = true)
	{
		if( $this->http_auth())
		{						
			$player_count = intval( $player_count);
	
			// generate 1 to 100 user ids
			$users = range( 1, 100);
	
			// shuffle and slice the array according to player count
			shuffle( $users);
			$users = array_slice( $users, 0, $player_count);			
			
			$user = array();
			$return = array();
			
			foreach( $users as $a_user)
			{			
				$user = UserGen::get_instance()->generate();
				unset( $user['name']);
				unset( $user['total_exp']);
				unset( $user['week_exp']);
				unset( $user['yesterday_exp']);
				unset( $user['lvl']);		
					
				$user['id'] = $a_user;
				
				$get_user = $this->user->get_user( $user['id']);
				//$this->look( $a_user);
				if( !empty( $get_user['value']))
				{
					$total_exp = $get_user['value']['total_exp'] + $user['today_exp'];
					$week_exp = $get_user['value']['week_exp'] + $user['today_exp'];
					$today_exp = $get_user['value']['today_exp'] + $user['today_exp'];
					$lvl = intval( $total_exp / 1000);
						
					$update_user = $this->bot->update_user( $user['id'], $lvl, $total_exp, $week_exp, $today_exp);
					if( !$update_user['error'])
					{	
						// update redis tables
						Leaderboard::get_instance()->update_level( $get_user['value']['user_id'], $get_user['value']['name'], $lvl);
						Leaderboard::get_instance()->update_total_exp( $get_user['value']['user_id'], $get_user['value']['name'], $total_exp);
						Leaderboard::get_instance()->update_week_exp( $get_user['value']['user_id'], $get_user['value']['name'], $week_exp);
						Leaderboard::get_instance()->update_today_exp( $get_user['value']['user_id'], $get_user['value']['name'], $today_exp);
																	
						$return['error'] = false;
						$return['msg'] = 'Successfully updated';
						$return['updated_user_ids'] = $users;
					}
					else
					{
						$return['error'] = true;
						$return['msg'] = 'Failed to update user : ' . $user['id'];
						break;
					}
				}
				else
				{
					$return['error'] = true;
					$return['msg'] = 'Unable to get user';
					return $return;
				}
				
										
			}
			
			if( $ajax)
			{
				echo json_encode( $return);
				exit;
			}
			else
			{
				$this->look( $return);
			}
		}
		else
		{
			echo 'BAD REQUEST';
			exit;
		}
				
	}
			
	function index()
	{
		echo 'BAD REQUEST';
	}
}