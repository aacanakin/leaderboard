<?php
/*
 * Mysql connector class that provides easy and secure query retrieval
 * This driver version is the newest one. I haven't deprecated the old db driver yet
 */

class Mysql2 // mysql data driver for easy queries. uses mysqli functions in connection
{
	private static $_mysql2;
	
	private $connection;
	private $cache;
	/*
	 * dbh is the basic db handler array
	 */
	private function __construct()
	{
		$this->connection = mysqli_connect( Config::get('db_host'), Config::get('db_user'), Config::get('db_pass'), Config::get('db_schema'));
		
		// set application encoding
		mysqli_query( $this->connection, "set names " . Config::get('encoding')); // @todo: deprecate this statement if not needed		
		
		mysqli_set_charset( $this->connection, Config::get('encoding')); // @todo: test this
								
		if( mysqli_connect_errno())
		{
			trigger_error( 'problem in connection to database : ' . mysqli_connect_error(), E_USER_WARNING);
		}
	}

	public static function get_instance()
	{
		if( ! isset( self::$_mysql2))
		{
			self::$_mysql2 = new Mysql2();
		}			
		return self::$_mysql2;
	}
	
	public function __clone()
	{
		trigger_error('cloning db instance is not allowed', E_USER_ERROR);	
	}
	
	public function __wakeup()
	{
		trigger_error('unserializing db instance is not allowed.', E_USER_ERROR);
	}
	
	/*
	 * database functions for mysql
	 */			 
		
	
	/*  
	 *  query( $query, $options = array()) : function with no sql injection prevention. so use it with caution
	 *  $query : sql statement	
	 *  $options : a non-assoc array that define return output. can hold the following values;
	 *  	fetch_assoc   : returns automatically indexed keys in selective queries and stored procedures in $return['value']	 
	 *  	row 		  : returns first raw as an associative array in $return['value']
	 *  	insert_id 	  : returns last_insert_id in $return['insert_id']
	 *  	affected_rows : returns affected_rows in $return['affected_rows']
	 *  	debug		  : returns the generated sql statement, query execution time, mysql error, etc.
	 *  	info 		  : returns mysqli_info in $return['info']
	 *  	no_of_rows	  : returns number of rows in $return['no_of_rows']
	 *  	
	 *  ## fetch_assoc & row are mutual exclusive. don't use them together. it may cause unstable results.
	 *  ## don't use fetch_assoc & row in non-selective queries. it may cause troubles and unstable results.	 
	 */
	function query( $query, $options = array()) 
	{	
		$return = array();
		
		$debug = ( in_array( 'debug', $options) || Config::get('mysql_debugging')) ? true : false;
		
		$query = trim( $query);
		
		if( $debug)
		{
			$msc = microtime( true);
			$result = mysqli_query( $this->connection, $query);
			$msc = microtime( true) - $msc;
			$return['debug']['exec_time'] = $msc * 1000 . ' ms';	
		}
		else 
		{
			$result = mysqli_query( $this->connection, $query);
		}		
								
		if( $result)
		{		
			$return['error'] = false;
			
			if( in_array('no_of_rows', $options))
			{
				$return['no_of_rows'] = $this->no_of_rows( $result);
			}
			
			if( in_array('fetch_assoc', $options))
			{				
				$i = 0;
				while( $row = mysqli_fetch_assoc( $result))
				{
					$return['value'][$i] = $row;
					$i++; 
				}			
				
				// prevent command out of sync errors
				while( mysqli_more_results( $this->connection))
				{
					mysqli_next_result( $this->connection);
					if( $l_result = mysqli_store_result( $this->connection))
					{
						mysqli_free_result( $l_result);
					}
				}
				
				mysqli_free_result( $result);
				$return['error'] = false;				
			}
			else if( in_array('row', $options))
			{
				$row = mysqli_fetch_assoc( $result);
					
				while( mysqli_more_results( $this->connection))
				{
					mysqli_next_result( $this->connection);
					if( $l_result = mysqli_store_result( $this->connection))
					{
						mysqli_free_result( $l_result);
					}
				}
																
				mysqli_free_result( $result); // @todo: test this statement

				$return['value'] = $row;
				$return['error'] = false;																
			}						
			
		}
		else
		{
			$return['error'] = true;
			
			if( Config::get('environment') == 'live')
			{
				$dump = $query;
			}
			else
			{
				$dump = '<pre>' . print_r( $query, true) . '</pre>';
			}
			
			if( $debug)
			{				
				$return['debug']['error'] = mysqli_error( $this->connection);
			}
														
			trigger_error( 'error occured in custom query : ' . mysqli_error( $this->connection) . ' query : ' . $dump, E_USER_ERROR);				
		}
		
		if( $debug)
		{
			$return['debug']['query'] = $query;
		}
		
		if( in_array('insert_id', $options))
		{
			$return['insert_id'] = $this->last_insert_id();
		}
		
		if( in_array('affected_rows', $options))
		{
			$return['affected_rows'] = $this->affected_rows();
		}
						
		if( in_array('info', $options))
		{
			$return['info'] = $this->info();
		}
		
		return $return;
	}
	
	/*  
	* 	select( $table, $params = array(), $conditions = array(), $options = array()) : generic select function that generates basic select query
	*   $table 		  : database table without schema name
	*   $params 	  : a non-assoc array of column names of database table, '*' gets all columns
	*   $conditions	  : an assoc array of $k->$v pairs. $k is column, $v is value of column name
	*   $options 	  : a non-assoc array that define return output. can hold the following values;
	*   	distinct	  : adds distinct statement to select
	*  		fetch_assoc   : returns automatically indexed keys in selective queries and stored procedures in $return['value']
	*  		row 		  : returns first raw as an associative array in $return['value']		
	*  		debug		  : returns the generated sql statement, query execution time, mysql error, etc.
	*  		info 		  : returns mysqli_info in $return['info']
	*  		no_of_rows	  : returns number of rows in $return['no_of_rows']
	*  		start		  : returns starting index in limit
	*  		amount		  : returns number of rows wanted
	*  ## fetch_assoc & row are mutual exclusive. don't use them together. it may cause unstable results.
	*/
	function select( $table, $params = array(), $conditions = array(), $options = array())
	{
		$debug = ( in_array( 'debug', $options) || Config::get('mysql_debugging')) ? true : false;
		
		$conditions = $this->clean_input( $conditions); // prevent sql injection
		
		$params_query = is_array( $params) ? implode(',', $params) : '*';
		$query_select = 'select ' . $params_query . ' from ' . Config::get('db_schema') . '.' . $table.' ';
		$where = empty( $conditions) ? '' : ('where ' . urldecode( http_build_query( $conditions, '', ' AND ')) . ' ');				
		$limit = ( isset( $options['start']) && isset( $options['amount'])) ? ('limit ' . $options['start'] . ',' . $options['amount'] ) : '';				
		
		$query = $query_select . $where . $limit;
		
		if( $debug)
		{
			$msc = microtime( true);
			$result = mysqli_query( $this->connection, $query);
			$msc = microtime( true) - $msc;
			$return['debug']['exec_time'] = $msc * 1000 . ' ms';
		}
		else
		{
			$result = mysqli_query( $this->connection, $query);
		}
										
		if( $result)
		{
			if( in_array('no_of_rows', $options))
			{
				$return['no_of_rows'] = $this->no_of_rows( $result);
			}
			
			if( in_array('fetch_assoc', $options))
			{
				$i = 0;
				while( $row = mysqli_fetch_assoc( $result))
				{
					$return['value'][$i] = $row;
					$i++;
				}
										
				// prevent command out of sync errors
				while( mysqli_more_results( $this->connection))
				{
					mysqli_next_result( $this->connection);
					if( $l_result = mysqli_store_result( $this->connection))
					{
						mysqli_free_result( $l_result);
					}
				}
			
				mysqli_free_result( $result);
				$return['error'] = false;
			}
			else if( in_array('row', $options))
			{
				$row = mysqli_fetch_assoc( $result);
					
				while( mysqli_more_results( $this->connection))
				{
					mysqli_next_result( $this->connection);
					if( $l_result = mysqli_store_result( $this->connection))
					{
						mysqli_free_result( $l_result);
					}
				}
			
				mysqli_free_result( $result); // @todo: test this statement
			
				$return['value'] = $row;
				$return['error'] = false;
			}
		}
		else
		{
			$return['error'] = true;
				
			if( Config::get('environment') == 'live')
			{
				$dump = $query;
			}
			else
			{
				$dump = '<pre>' . print_r( $query, true) . '</pre>';
			}
				
			if( $debug)
			{
				$return['debug']['error'] = mysqli_error( $this->connection);
			}
		
			trigger_error( 'error occured in select query : ' . mysqli_error( $this->connection) . ' query : ' . $dump, E_USER_ERROR);
		}
		
		if( $debug)
		{
			$return['debug']['query'] = $query;
		}
														
		if( in_array('info', $options))
		{
			$return['info'] = $this->info();
		}
		
		return $return;
	}

	/*
	* 	insert( $table, $values = array(), $options = array()) : generic insert function
	*   $table 		  : database table without schema name
	*   $values 	  : a assoc array of $k->$v pairs. $k's are column names, $v's are values	
	*   $options 	  : a non-assoc array that define return output. can hold the following values;
	*   	ignore	  	  : adds ignore statement to insert
	* 	 	affected_rows : returns affected_rows in $return['affected_rows']
	*  		debug		  : returns the generated sql statement, query execution time, mysql error, etc.
	*  		info 		  : returns mysqli_info in $return['info']	
	*  		insert_id     : returns mysql last insert id in $return['insert_id']
	*/
	function insert( $table, $values = array(), $options = array() ) // insert row to the table with specific values
	{
				
		$debug = ( in_array( 'debug', $options) || Config::get('mysql_debugging')) ? true : false;
		
		$values = $this->clean_input( $values);
		
		$query_insert  = 'insert ' . ( in_array('ignore', $options) ? 'ignore ' : ' ') . Config::get('db_schema') . '.' . $table . ' ';
		$query_columns = '(' . implode(',', array_keys( $values)) . ') ';
		$query_values  = 'values (' . implode(',', array_values( $values)) . ')';
				
		$query = $query_insert . $query_columns . $query_values;
		
		if( $debug)
		{
			$msc = microtime( true);
			$result = mysqli_query( $this->connection, $query);
			$msc = microtime( true) - $msc;
			$return['debug']['exec_time'] = $msc * 1000 . ' ms';
		}
		else
		{
			$result = mysqli_query( $this->connection, $query);
		}
		
		if( $result)
		{
			$return['error'] = false;
		}
		else
		{
			$return['error'] = true;
			
			if( Config::get('environment') == 'live')
			{
				$dump = $query;
			}
			else
			{
				$dump = '<pre>' . print_r( $query, true) . '</pre>';
			}
			
			if( $debug)
			{				
				$return['debug']['error'] = mysqli_error( $this->connection);
			}
														
			trigger_error( 'error occured in insert query : ' . mysqli_error( $this->connection) . ' query : ' . $dump, E_USER_ERROR);	
		}
		
		if( $debug)
		{
			$return['debug']['query'] = $query;
		}
		
		if( in_array('insert_id', $options))
		{
			$return['insert_id'] = $this->last_insert_id();
		}
		
		if( in_array('affected_rows', $options))
		{
			$return['affected_rows'] = $this->affected_rows();
		}
						
		if( in_array('info', $options))
		{
			$return['info'] = $this->info();
		}
		
		return $return;
	}
		
	/*
	* 	update( $table, $params, $conditions = array(), $options = array()) : generic insert function
	*   $table 		  : database table without schema name
	*   $params 	  : an assoc array of $k->$v pairs. $k's are columns, $v's are values that should be updated
    *   $conditions	  : an assoc array of $k->$v pairs. $k is column, $v is value of column name
	*   $options 	  : a non-assoc array that define return output. can hold the following values;	
	* 	 	affected_rows : returns affected_rows in $return['affected_rows']
	*  		debug		  : returns the generated sql statement, query execution time, mysql error, etc.
	*  		info 		  : returns mysqli_info in $return['info']
	*/
	function update( $table, $params = array(), $conditions = array(), $options = array())  
	{
		$debug = ( in_array( 'debug', $options) || Config::get('mysql_debugging')) ? true : false;
		
		$params = $this->clean_input( $params);
		$conditions = $this->clean_input( $conditions);
		
		$query_update = 'update ' . Config::get('db_schema') . '.' . $table . ' ';
		$query_set 	  = 'set ' . urldecode( http_build_query( $params, '', ', ')) . ' ';
		$query_where = 'where 1';
		if( !empty( $conditions))
		{
			$query_where  = 'where ' . urldecode( http_build_query( $conditions, '', ' AND'));
		}			
		
		$query = $query_update . $query_set . $query_where;
		
		if( $debug)
		{
			$msc = microtime( true);
			$result = mysqli_query( $this->connection, $query);
			$msc = microtime( true) - $msc;
			$return['debug']['exec_time'] = $msc * 1000 . ' ms';
		}
		else
		{
			$result = mysqli_query( $this->connection, $query);
		}
		
		if( $result)
		{
			$return['error'] = false;
		}
		else
		{
			$return['error'] = true;
				
			if( Config::get('environment') == 'live')
			{
				$dump = $query;
			}
			else
			{
				$dump = '<pre>' . print_r( $query, true) . '</pre>';
			}
				
			if( $debug)
			{
				$return['debug']['error'] = mysqli_error( $this->connection);
			}
		
			trigger_error( 'error occured in update query : ' . mysqli_error( $this->connection) . ' query : ' . $dump, E_USER_ERROR);
		}
		
		if( $debug)
		{
			$return['debug']['query'] = $query;
		}
						
		if( in_array('affected_rows', $options))
		{
			$return['affected_rows'] = $this->affected_rows();
		}
		
		if( in_array('info', $options))
		{
			$return['info'] = $this->info();
		}
		
		return $return;
	}

	/*
	*  call( $procedure, $params = array(),  $options = array()) : generic call function for stored procedures
	*  $procedure : procedure name
	*  $params    : an non-assoc array of values that are procedure parameters
	*  $options   : a non-assoc array that define return output. can hold the following values;
	*  		fetch_assoc   : returns automatically indexed keys in selective queries and stored procedures in $return['value']
	* 	 	row 		  : returns first raw as an associative array in $return['value']
	* 	 	insert_id 	  : returns last_insert_id in $return['insert_id']
	* 	 	affected_rows : returns affected_rows in $return['affected_rows']
	* 	 	debug		  : returns the generated sql statement, query execution time, mysql error, etc.
	* 	 	info 		  : returns mysqli_info in $return['info']
	* 	 	no_of_rows	  : returns number of rows in $return['no_of_rows']
	*
	*  ## fetch_assoc & row are mutual exclusive. don't use them together. it may cause unstable results.
	*  ## don't use fetch_assoc & row in non-selective queries. it may cause troubles and unstable results.
	*/
	function call( $procedure, $params = array(), $options = array()) 
	{																	
		$return = array();
		
		$debug = ( in_array( 'debug', $options) || Config::get('mysql_debugging')) ? true : false;
		
		$this->clean_input( $params);
		
		$query_call = 'call ' . $procedure . ' ';
		$query_params = '(' . implode( ',', $params) . ')';
		
		$query = $query_call . $query_params;
		
		if( $debug)
		{
			$msc = microtime( true);
			$result = mysqli_query( $this->connection, $query);
			$msc = microtime( true) - $msc;
			$return['debug']['exec_time'] = $msc * 1000;
		}
		else
		{
			$result = mysqli_query( $this->connection, $query);
		}
		
		if( $result)
		{
			if( in_array('fetch_assoc', $options))
			{
				$i = 0;
				while( $row = mysqli_fetch_assoc( $result))
				{
					$return['value'][$i] = $row;
					$i++;
				}
		
				// prevent command out of sync errors
				while( mysqli_more_results( $this->connection))
				{
					mysqli_next_result( $this->connection);
					if( $l_result = mysqli_store_result( $this->connection))
					{
						mysqli_free_result( $l_result);
					}
				}
		
				mysqli_free_result( $result);
				$return['error'] = false;
			}
			else if( in_array('row', $options))
			{
				$row = mysqli_fetch_assoc( $result);
					
				while( mysqli_more_results( $this->connection))
				{
					mysqli_next_result( $this->connection);
					if( $l_result = mysqli_store_result( $this->connection))
					{
						mysqli_free_result( $l_result);
					}
				}
		
				mysqli_free_result( $result); // @todo: test this statement
		
				$return['value'] = $row;
				$return['error'] = false;
			}
				
		}
		else
		{
			$return['error'] = true;
				
			if( Config::get('environment') == 'live')
			{
				$dump = $query;
			}
			else
			{
				$dump = '<pre>' . print_r( $query, true) . '</pre>';
			}
				
			if( $debug)
			{
				$return['debug']['error'] = mysqli_error( $this->connection);
			}
		
			trigger_error( 'error occured in call query : ' . mysqli_error( $this->connection) . ' query : ' . $dump, E_USER_ERROR);
		}
		
		if( $debug)
		{
			$return['debug']['query'] = $query;
		}
		
		if( in_array('insert_id', $options))
		{
			$return['insert_id'] = $this->last_insert_id();
		}
		
		if( in_array('affected_rows', $options))
		{
			$return['affected_rows'] = $this->affected_rows();
		}
		
		if( in_array('no_of_rows', $options))
		{
			$return['no_of_rows'] = $this->no_of_rows();
		}
		
		if( in_array('info', $options))
		{
			$return['info'] = $this->info();
		}
		
		return $return;
	}
	
	/*
	* 	delete( $table, $params, $conditions = array(), $options = array()) : generic insert function
	*   $table 		  : database table without schema name
	*   $params 	  : an assoc array of $k->$v pairs. $k's are columns, $v's are values that should be updated
	*   $conditions	  : an assoc array of $k->$v pairs. $k is column, $v is value of column name
	*   $options 	  : a non-assoc array that define return output. can hold the following values;
	* 	 	affected_rows : returns affected_rows in $return['affected_rows']
	*  		debug		  : returns the generated sql statement, query execution time, mysql error, etc.
	*  		info 		  : returns mysqli_info in $return['info']
	*/
	function delete( $table, $conditions = array(), $options = array())
	{
		$debug = ( in_array( 'debug', $options) || Config::get('mysql_debugging')) ? true : false;
				
		$conditions = $this->clean_input( $conditions);
		
		$query_delete = 'delete from ' . Config::get('db_schema') . '.' . $table . ' ';		
		$query_where  = 'where ' . urldecode( http_build_query( $conditions, '', ' AND'));
		
		$query = $query_delete . $query_where;
		
		if( $debug)
		{
			$msc = microtime( true);
			$result = mysqli_query( $this->connection, $query);
			$msc = microtime( true) - $msc;
			$return['debug']['exec_time'] = $msc * 1000;
		}
		else
		{
			$result = mysqli_query( $this->connection, $query);
		}
		
		if( $result)
		{
			$return['error'] = false;
		}
		else
		{
			$return['error'] = true;
		
			if( Config::get('environment') == 'live')
			{
				$dump = $query;
			}
			else
			{
				$dump = '<pre>' . print_r( $query, true) . '</pre>';
			}
		
			if( $debug)
			{
				$return['debug']['error'] = mysqli_error( $this->connection);
			}
		
			trigger_error( 'error occured in delete query : ' . mysqli_error( $this->connection) . ' query : ' . $dump, E_USER_WARNING);
		}
		
		if( $debug)
		{
			$return['debug']['query'] = $query;
		}
		
		if( in_array('affected_rows', $options))
		{
			$return['affected_rows'] = $this->affected_rows();
		}
		
		if( in_array('info', $options))
		{
			$return['info'] = $this->info();
		}
		
		return $return;
	}
	
	/*
	*  bindy_query( $query, $params = array(), $options = array()) : function with no sql injection prevention. so use it with caution
	*  $query : sql statement with ordered ?'s
	*  $params    : an non-assoc array of values that are ordered column values
	*  $options : a non-assoc array that define return output. can hold the following values;
	*  		fetch_assoc   : returns automatically indexed keys in selective queries and stored procedures in $return['value']
	*  		row 		  : returns first raw as an associative array in $return['value']
	*  		insert_id 	  : returns last_insert_id in $return['insert_id']
	*  		affected_rows : returns affected_rows in $return['affected_rows']
	*  		debug		  : returns the generated sql statement, query execution time, mysql error, etc.
	*  		info 		  : returns mysqli_info in $return['info']
	*  		no_of_rows	  : returns number of rows in $return['no_of_rows']
	*
	*  ## fetch_assoc & row are mutual exclusive. don't use them together. it may cause unstable results.
	*  ## don't use fetch_assoc & row in non-selective queries. it may cause troubles and unstable results.
	*/
	function bindy_query() // currently not available and won't be in long term use
	{
// 		$args = func_get_args();
// 		echo '<pre>';
// 		print_r( $args);
// 		echo '</pre>';
		
		trigger_error('bindy_query is not available, don\'t use it', E_USER_WARNING);
	}
	
	function last_insert_id () // returns the last insert id of connection | result variable
	{
		return mysqli_insert_id( $this->connection);
	}
			
	function no_of_fields( $result_set)
	{
		return mysqli_num_fields( $result_set);
	}
	
	function no_of_rows( $result_set)
	{
		return mysqli_num_rows( $result_set);
	}
	
	function affected_rows()
	{
		return mysqli_affected_rows( $this->connection);
	}
	
	function free_result()
	{
		return mysqli_free_result( $this->connection);	
	}
	
	function get_error()
	{
		return mysqli_error( $this->connection);
	}
	
	function status()
	{
		return mysqli_stat( $this->connection);
	}
	
	function char_set()
	{
		return mysqli_character_set_name( $this->connection);
	}
	
	function info()
	{
		return mysqli_info( $this->connection);
	}
	
	function clean_input( $input)
	{
		if( is_array($input))
		{
			foreach ($input as $key=>$value)
			{
				$value = trim($value);
				$value = mysqli_real_escape_string( $this->connection, $value);
				$key = trim($key);
				$key = mysqli_real_escape_string( $this->connection, $key);
				if( is_string($value))
				{
					$value = '"'.$value.'"';
				}
				
				$input[$key] = $value;
			}
			
			return $input;
		}
		else // $input is not array, it is a single 
		{
			$input = trim( $input);
			$input = mysqli_real_escape_string( $this->connection, $input);
			if( is_string($input))
			{
				$input = "'".$input."'";
			}
			return $input;	
		}
		
	}
	
	function close()
	{
		mysqli_close( $this->connection);
	}
	
}
