<?php
class BotModel extends Model
{		
	function init()
	{
		$query_score = "delete from score where 1";
		$return['score'] = $this->db->query( $query_score);
		
		$query_user = "delete from user where 1";
		$return['user'] = $this->db->query( $query_user);
		
		$query_alter =  "ALTER TABLE user AUTO_INCREMENT=1";
		$return['alter_user'] = $this->db->query($query_alter);
		
		return $return;
	}
	
	// adds a new user with name
	// all other fields are set to zero
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
	
	// updates a user given user id, level, total_exp, week_exp, today_exp
	function update_user( $user_id, $level, $total_exp, $week_exp, $today_exp)
	{																						
		$params = array( 'level' => $level, 'week_exp' => $week_exp, 'today_exp' => $today_exp, 'total_exp' => $total_exp);
		$conditions = array( 'user_id' => $user_id);
		$options = array( 'affected_rows', 'debug');
		
		return $this->db->update( 'score', $params, $conditions, $options);
	}
	
}