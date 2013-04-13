<?php
class UserModel extends Model
{
	// basic selective queries to get a single user and get all users
	
	function get_users()
	{
		$params = array( 'user_id', 'name');
		$conditions = array();
		$options = array( 'fetch_assoc');
		
		return $this->db->select( 'user', $params, $conditions, $options);
	}
	
	function get_user( $user_id)
	{
		$query = "select u.user_id, u.name, s.level, s.total_exp, s.week_exp, s.yesterday_exp, s.today_exp
				  from user u inner join score s
				  on u.user_id = s.user_id
				  where u.user_id = {$user_id}";
		
		$options = array( 'row');
		
		return $this->db->query( $query, $options);
	}
}