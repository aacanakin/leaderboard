<?php
class BotModel extends Model
{		
	function add_user( $name)
	{
		$options_user = array( 'insert_id', 'ignore');
		$values_user = array( 'name' => $name);
					
		$return['user'] = $this->db->insert( 'user', $values_user, $options_user);
		if( $return['user']['insert_id'] > 0)
		{
			$options_score = array( 'affected_rows', 'ignore');
			$values_score = array( 'user_id' => $return['user']['insert_id']);
						
			$return['score'] = $this->db->insert( 'score', $values_score, $options_score); 
		}
		
		return $return;
	}
	
	function update_user( $user_id, $today_exp)
	{
		$get_user = $this->get_user( $user_id);
		if( !empty( $get_user['value']))
		{
			$new_total_exp = $get_user['value']['total_exp'] + $today_exp;
			$new_week_exp = $get_user['value']['week_exp'] + $today_exp;
			$new_today_exp = $get_user['value']['today_exp'] + $today_exp;
			$new_lvl = intval( $new_total_exp / 1000);			
																		
			$params = array( 'level' => $new_lvl, 'week_exp' => $new_week_exp, 'today_exp' => $new_today_exp, 'total_exp' => $new_total_exp);
			$conditions = array( 'user_id' => $user_id);
			$options = array( 'affected_rows', 'debug');
			
			return $this->db->update( 'score', $params, $conditions, $options);
		}
		else
		{
			$return['error'] = true;
			$return['affected_rows'] = 0;
			return $return;
		}										
	}
	
	function get_user( $user_id)
	{
		$params = '*';
		$conditions = array( 'user_id' => $user_id);
		$options = array( 'row');
		return $this->db->select( 'score', $params, $conditions, $options);
	}
}