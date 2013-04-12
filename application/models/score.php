<?php
class ScoreModel extends Model
{
	function get_total_ranking( $amount)
	{
		$msc = microtime( true);
						
		$options = array( 'fetch_assoc');
		
		$query = "select u.user_id, u.name, s.level, s.total_exp
				  from leaderboard.user u inner join leaderboard.score s 
				  on u.user_id = s.user_id
				  order by s.total_exp desc
				  limit 0, {$amount};
				 ";
		
		$return = $this->db->query( $query, $options);
				
		$msc = microtime( true) - $msc;
		$return['debug']['exec_time'] = $msc * 1000 . ' ms';
		
		return $return;		
	}
	
	function get_weekly_ranking( $amount)
	{
		$msc = microtime( true);
		
		$options = array( 'fetch_assoc');
		
		$query = "select u.user_id, u.name, s.level, s.week_exp
				  from leaderboard.user u inner join leaderboard.score s
				  on u.user_id = s.user_id
				  order by s.week_exp desc, s.level desc
				  limit 0, {$amount};
				 ";
		
		$return = $this->db->query( $query, $options);
				
		$msc = microtime( true) - $msc;
		$return['debug']['exec_time'] = $msc * 1000 . ' ms';
		
		return $return;		
	}
	
	function get_yesterday_ranking( $amount)
	{
		$msc = microtime( true);
		
		$options = array( 'fetch_assoc');
		
		$query = "select u.user_id, u.name, s.level, s.yesterday_exp
				  from leaderboard.user u inner join leaderboard.score s
				  on u.user_id = s.user_id
				  order by s.yesterday_exp desc, s.level desc
				  limit 0, {$amount};
				 ";
		
		$return = $this->db->query( $query, $options);
				
		$msc = microtime( true) - $msc;
		$return['debug']['exec_time'] = $msc * 1000 . ' ms';
		
		return $return;	
	}
	
	function get_today_ranking( $amount)
	{
		$msc = microtime( true);
		
		$options = array( 'fetch_assoc');
		
		$query = "select u.user_id, u.name, s.level, s.today_exp
				  from leaderboard.user u inner join leaderboard.score s
				  on u.user_id = s.user_id
				  order by s.today_exp desc, s.level desc
				  limit 0, {$amount};
				 ";
		
		$return = $this->db->query( $query, $options);
		
		$msc = microtime( true) - $msc;
		$return['debug']['exec_time'] = $msc * 1000 . ' ms';
		
		return $return;
	}
	
	function reset_today()
	{
		$params = array( 'today_exp' => 0);
		$conditions = array();
		$options = array( 'affected_rows');
		
		return $this->db->update( 'score', $params, $conditions, $options);
	}
	
	function move_yesterday()
	{
		$query = "update score
				  set yesterday_exp = today_exp;		
				 ";
		
		$options = array( 'affected_rows');
		
		return $this->db->query( $query, $options);
	}
	
	function reset_week()
	{
		$params = array( 'week_exp' => 0);
		$conditions = array();
		$options = array( 'affected_rows');
		
		return $this->db->update( 'score', $params, $conditions, $options);
	}
}