<?php
class BotController extends Controller
{
	function before_load()
	{
		$this->load_model('bot');
		$this->load_model('score');
		
		$this->load_helper('usergen');
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
		
	function build_user_base( $count = 100)
	{
		if( $this->http_auth())
		{
			// each call of this function generates 100 user
			for( $i = 0; $i < $count; $i++)
			{							
				$user = UserGen::get_instance()->generate();
				
				$add_user = $this->bot->add_user( $user['name'], $user['lvl'], $user['total_exp'], $user['week_exp'], $user['yesterday_exp'], $user['today_exp']);
				if( !$add_user['user']['error'] || !$add_user['score']['error'])
				{
					echo '<br>User : ' . $user['name'] . ' , total_exp : ' . $user['total_exp'] . ' , week_exp : ' . $user['week_exp'] . ' , today_exp : ' .$user['today_exp'] . ' , yesterday_exp : ' . $user['yesterday_exp'] . ' created successfully<br>';
				}			
				else
				{
					echo '<br>Error occured in adding user : ' . $user['name'].'<br>';
				}																
			}
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
		
		$return = array();
	
		$move_yesterday = $this->score->move_yesterday();
		if( !$move_yesterday['error'] && $move_yesterday['affected_rows'] > 0)
		{
			$reset_yesterday = $this->score->reset_today();
			if( !$reset_yesterday['error'] && $reset_yesterday['affected_rows'] > 0)
			{				
				$return['error'] = false;
				$return['msg'] = 'Day changed successfully';
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
	
	function change_week( $ajax = true)
	{
		if( $this->http_auth())
		{
			$return = array();
			$reset_week = $this->score->reset_week();
			
			if( !$reset_week['error'] && $reset_week['affected_rows'] > 0)
			{
				$return['error'] = false;
				$return['msg'] = 'Week changed successfully';
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
			//$this->look( $a_user);
			
			$update_user = $this->bot->update_user( $user['id'], $user['today_exp']);
			if( !$update_user['error'] && $update_user['affected_rows'] > 0)
			{
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
			
	function index()
	{
		echo 'BAD REQUEST';
	}
}