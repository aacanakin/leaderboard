<?php
class AjaxController extends Controller
{
	function before_load()
	{
		$this->load_model('score');
	}
	
	function leaderboard( $type = 'total', $amount = 10)
	{		
		$return = array();
		
		switch( $type)
		{
			case 'total':
				
				$get_total = $this->score->get_total_ranking( $amount);
				if( !$get_total['error'] && !empty( $get_total['value']))
				{
					$return['data']['leaderboard'] = $get_total['value'];
					$return['error'] = false;
				}
				else
				{
					$return['error'] = true;
					$return['msg'] = 'Could not retrieve ranking by total experince';					
				}		

				break;
			
			case 'weekly':
				
				$get_weekly = $this->score->get_weekly_ranking( $amount);
				if( !$get_weekly['error'] && !empty( $get_weekly['value']))
				{
					$return['data']['leaderboard'] = $get_weekly['value'];
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
					$return['data']['leaderboard'] = $get_yesterday['value'];
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
					$return['data']['leaderboard'] = $get_today['value'];
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
		
		echo json_encode( $return);
		exit;
	}
}