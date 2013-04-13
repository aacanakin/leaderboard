<?php
class AjaxController extends Controller
{
	function before_load()
	{
		$this->load_model('score');
		
		$this->load_helper('leaderboard');
		
		Leaderboard::get_instance();
	}
		
	// returns the leaderboard according to leaderboard type, player count and debug mode
	// - type possible values :
	// 		total, week, yesterday, today
	// - amount could be between [1, 100]
	// - debug could be true or false
	// 		- true returns both mysql & redis arrays with key 'redis' & 'mysql' and their execution times
	//		- false just returns 'leaderboard' array using redis
	
	// returns json output
	function leaderboard( $type = 'total', $amount = 100, $debug = false)
	{		
		$return = array();
				
		// get rankings from Leaderboard using redis
		$rankings = Leaderboard::get_instance()->get_rankings( $type, $amount, true);
		
		// if debug mode is on then a comparison will be shows with redis & mysql
		if( $debug)
		{
			$return['redis']['leaderboard'] = $rankings['leaderboard'];
			if( !is_null( $rankings['leaderboard']))
			{
				$return['redis']['debug']['exec_time'] = $rankings['debug']['exec_time'];
			}			
		}
		else
		{
			$return['leaderboard'] = $rankings['leaderboard'];
		}
		
		// if debug mode is on, then mysql queries will be run to compare with redis outputs
		if( $debug)
		{
			switch( $type)
			{
				case 'total':
					
					// reach mysql score model with $this->model_name
					$get_total = $this->score->get_total_ranking( $amount);
					if( !$get_total['error'] && !empty( $get_total['value']))
					{
						$return['mysql']['leaderboard'] = $get_total['value'];
						$return['mysql']['debug']['exec_time'] = $get_total['debug']['exec_time'];
						$return['error'] = false;
					}
					else
					{
						$return['error'] = true;
						$return['msg'] = 'Could not retrieve ranking by total experince';
					}
			
					break;
						
				case 'week':
			
					$get_weekly = $this->score->get_weekly_ranking( $amount);
					if( !$get_weekly['error'] && !empty( $get_weekly['value']))
					{
						$return['mysql']['leaderboard'] = $get_weekly['value'];
						$return['mysql']['debug']['exec_time'] = $get_weekly['debug']['exec_time'];
						$return['error'] = false;
					}
					else
					{
						$return['error'] = true;
						$return['msg'] = 'Could not retrieve ranking by weekly experince';
					}
			
					break;
			
				case 'yesterday':
			
					$get_yesterday = $this->score->get_yesterday_ranking( $amount);
					if( !$get_yesterday['error'] && !empty( $get_yesterday['value']))
					{
						$return['mysql']['leaderboard'] = $get_yesterday['value'];
						$return['mysql']['debug']['exec_time'] = $get_yesterday['debug']['exec_time'];
						$return['error'] = false;
					}
					else
					{
						$return['error'] = true;
						$return['msg'] = 'Could not retrieve ranking by yesterday experince';
					}
			
					break;
			
				case 'today':
			
					$get_today = $this->score->get_today_ranking( $amount);
					if( !$get_today['error'] && !empty( $get_today['value']))
					{
						$return['mysql']['leaderboard'] = $get_today['value'];
						$return['mysql']['debug']['exec_time'] = $get_today['debug']['exec_time'];
						$return['error'] = false;
					}
					else
					{
						$return['error'] = true;
						$return['msg'] = 'Could not retrieve ranking by today experince';
					}
			
					break;
			
				default:
			
					$return['error'] = true;
					$return['msg'] = 'Invalid ajax request';
					break;
			}
		}						
		
		
		echo json_encode( $return);
		exit;
	}
}