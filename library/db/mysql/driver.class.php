<?php
/*
 * Mysql connector class that provides easy and secure query retrieval
 */

class Mysql // mysql data driver for easy queries. uses mysqli functions in connection
{
	private static $_mysql;
	
	private $connection;
	private $cache;
	/*
	 * dbh is the basic db handler array
	 */
	private function __construct()
	{
		$this->connection = mysqli_connect( Config::get('db_host'), Config::get('db_user'), Config::get('db_pass'), Config::get('db_schema'));
		
		// set application encoding
		mysqli_query($this->connection, "set names " . Config::get('encoding'));
		
		if( Config::get('memcache'))
		{
			$this->cache = Memcacher::get_instance();
		}
		
		if( ! $this->connection)
		{
			trigger_error( 'problem in connection to database : ' . mysqli_connect_error(), E_USER_WARNING);
		}
	}

	public static function get_instance()
	{
		if( ! isset( self::$_mysql))
		{
			self::$_mysql = new Mysql();
		}			
		return self::$_mysql;
	}
	
	public function __clone()
	{
		trigger_error('cloning db instance is not allowed', E_USER_ERROR);	
	}
	
	public function __wakeup()
	{
		trigger_error('unserializing is not allowed.', E_USER_ERROR);
	}
	
	/*
	 * database functions for mysql
	 */
	
	// calling this function 
	//						 example : $result = bindy_query( 'call sign_up( ?, ?, ?, ?)', array('Aras Can Akin', 'ari', 'password', 'other_field');				 
	
	function bindy_query( $query, $params = array()) // currently not available and won't be in long term use
	{
		trigger_error('bindy_query is not available, don\'t use it', E_USER_WARNING);
	}
	
	function query( $query, $first = false) // secure your query with cleaning the inputs by clean_input function in Mysql class 
	{						// INSECURE
		$result = mysqli_query( $this->connection, $query);
		$query = trim($query);
		$query_type = strtolower( strstr( $query , ' ', true) );		
		
		$return = array();
		if( $result)
		{
			if(  $query_type == 'select' || $query_type == 'call')
			{
				if( !$first)
				{					
					$i = 0;
					$return['value'] = array();
					while( $row = mysqli_fetch_assoc( $result))
					{
						$return['value'][$i] = $row;
						$i++;						
					}
					
					while( mysqli_more_results( $this->connection))
					{
						mysqli_next_result( $this->connection);
						if( $l_result = mysqli_store_result( $this->connection))
						{
							mysqli_free_result( $l_result);
						}
					}
					
					$return['error'] = false;
					mysqli_free_result( $result);
				}
				else
				{
										
					$row = mysqli_fetch_assoc( $result);
					$return['value'] = $row;

					while( mysqli_more_results( $this->connection))
					{
						mysqli_next_result( $this->connection);
						if( $l_result = mysqli_store_result( $this->connection))
						{
							mysqli_free_result( $l_result);
						}
					}										
					$return['error'] = false;
					mysqli_free_result( $result);
				}
			}
			else if( strtolower( $query_type) === 'insert')
			{
				if( $this->last_insert_id() != 0)
				{
					$return['error'] = false;					
				}
				else
				{					
					$return['error'] = true;
				}			
			}
			else
			{
				$return['error'] = false;
			}		
		}
		else
		{
			$return['error'] = true;						
			trigger_error( 'error occured in custom query : ' .mysqli_error( $this->connection).' <br> query : ' .var_export($query) .'<br>', E_USER_WARNING);		
		}
		
		return $return;
	}
	function select_first( $table, $params = '*', $conditions = array(), $cache = false) // params can be * or array
	{
		
		$conditions = $this->clean_input( $conditions);
		$query = 'select ' ;
		if( $params === '*')
		{
			$query .= $params;	
		}
		else if( is_array( $params))
		{
			foreach( $params as $param)
			{
				$query .= $param . ',';
			}
			$query = rtrim( $query, ',');
		}
		else
		{
			trigger_error('params is neither array, nor *', E_USER_WARNING);
		}

		$query .= ' from ' . $table ;
		
		if( !empty( $conditions))
		{
			$query .= ' where ';
		}
		foreach( $conditions as $key => $value)
		{
			$query .= $key . ' = ' . $value . ' and ';
		}
		$query = rtrim( $query, ' and ');
		
		if( $cache)
		{
			$data = $this->cache->load( $this->cache->generate_key( $query));
			if( $data)
			{
				return $data;
			}
		}
		
		$return = array();
		
		$result = mysqli_query( $this->connection, $query);
						
		if( $result)
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
			
			$return['value'] = $row;
			
			
			
			$return['error'] = false;
			
			mysqli_free_result( $result);
				
			if( $cache)
			{
				$this->cache->save($this->cache->generate_key($query), $return);
			}
		}
		else
		{
			trigger_error( 'error occured in select_first query : ' .mysqli_error( $this->connection).' <br> query : ' . $query . '<br>', E_USER_WARNING);
			$return['value'] = null;
			$return['error'] = false;
		}
		
		
		return $return;
		
	}
	
	function select_all( $table, $conditions = array(), $cache=false ) // select all the columns from the table with conditions
	{
		
		$query = 'select * from ' . $table .'';
		if( !empty( $conditions))
		{
			if( !is_array( $conditions))
			{
				trigger_error('$conditions is not an array in select_all query', E_USER_ERROR);
			}
			$query .= ' where ';
			$conditions = $this->clean_input( $conditions);
			foreach( $conditions as $key => $value)
			{
				$query .= $key . ' = ' . $value . ' and ';
			}			
			$query = rtrim( $query, ' and '); // remove the last , of query	
		}
		
		if( $cache)
		{
			$data = $this->cache->load( $this->cache->generate_key( $query));
			if( $data)
			{
				echo '<br>data came from cache<br>';
				return $data;
			}
		}
		
		$return = array();
		
		$result = mysqli_query( $this->connection, $query);
		
		
		if( $result)
		{
			$i = 0;
			while( $row = mysqli_fetch_assoc( $result))
			{
				$return['value'][$i] = $row;
				$i++;								
			}
			
			while( mysqli_more_results( $this->connection))
			{
				mysqli_next_result( $this->connection);
				if( $l_result = mysqli_store_result( $this->connection))
				{
					mysqli_free_result( $l_result);
				}
			}
			
			$return['error'] = false;		
			mysqli_free_result( $result);
			
			if( $cache)
			{
				$this->cache->save($this->cache->generate_key($query), $return);
			}					
		}	
		else
		{
			trigger_error( 'error occured in select_all query : ' .mysqli_error( $this->connection).' <br> query : ' . $query . '<br>', E_USER_WARNING);
			$return['value'] = null;
			$return['error'] = true;	
		}

		return $return;					
	}
	
	function insert( $table, $values = array() ) // insert row to the table with specific values
	{
		$values = $this->clean_input($values);
		$query = 'insert into '.$table.'('; // create generic insert
		foreach ( $values as $key => $value) // column names
		{
			$query .= $key . ',';
		}
		$query = rtrim( $query, ',');
		$query .= ') values (';
		foreach( $values as $key => $value)
		{
			$query .= $value . ',';
		} 
		$query = rtrim( $query, ',');
		$query .= ')';
		
		$return = array();
		$result = mysqli_query( $this->connection, $query);
		if( $result)
		{	
			$return['error'] = false;					
		}
		else
		{			
			trigger_error( 'error occured in insert query : ' .mysqli_error( $this->connection).' <br> query : ' . $query .'<br>', E_USER_WARNING);
			$return['error'] = true;			
		}
		
		return $return;
	}
	
	function insert_ignore( $table, $values = array())
	{
		$values = $this->clean_input( $values);
		$query = 'insert ignore into '.$table. '(';
		
		foreach ( $values as $key => $value) // column names
		{
			$query .= $key . ',';
		}
		$query = rtrim( $query, ',');
		$query .= ') values (';
		
		foreach( $values as $key => $value)
		{
			$query .= $value . ',';
		}
		$query = rtrim( $query, ',');
		$query .= ')';
		
		$return = array();
		$result = mysqli_query( $this->connection, $query);
				
		if( $result)
		{	
			$return['error'] = false;					
		}
		else
		{
			trigger_error( 'error occured in insert query : ' .mysqli_error( $this->connection).' <br> query : ' . $query . '<br>', E_USER_WARNING);
			$return['error'] = true;
		}
		
		return $return;
	}
	
	function update( $table, $params, $conditions = array())  
	{
		$params = $this->clean_input( $params);
		$conditions = $this->clean_input( $conditions);
		$query = 'update ' . $table ;
		if( !is_array($params))
		{
			trigger_error('$params is not array in update( $table, $params, $conditions)', E_USER_ERROR);
		}
		
		$query .= ' set ';
		foreach( $params as $key => $value)
		{
			$query .= $key . ' = ' . $value . ',';
		}			
		
		$query = rtrim( $query, ',');
	
		if( !empty( $conditions))
		{
			if( !is_array( $conditions) )
			{
				trigger_error('$conditions is not array in update( $table, $params, $conditions)', E_USER_ERROR);
			}
			$query .= ' where ';
			foreach( $conditions as $key => $value)
			{
				$query .= $key . ' = ' . $value . ' and ';
			}
			$query = rtrim($query, ' and ');
		}
		
		$return = array();
		$result = mysqli_query( $this->connection, $query);
		if( $result)
		{
			$return['error'] = false;
		}
		else
		{
			trigger_error( 'error occured in update query : ' .mysqli_error( $this->connection).' <br> query : ' . $query . '<br>', E_USER_WARNING);
			$return['error'] = true;
		}
				
		return $return;
	}
	
	function select ( $table, $params, $conditions = array(), $cache = false)
	{
		//$params = $this->clean_input( $params); // be aware of what parameters you plug into this query
		$conditions = $this->clean_input( $conditions);
		$query = 'select ' ;
		if( !is_array($params))
		{
			trigger_error('$params is not an array  in select( $table, $params, $conditions) : ', E_USER_ERROR);
		}
		foreach( $params as $param)
		{
			$query .= $param . ',';
		}
		$query = rtrim( $query, ',');
		$query .= ' from ' . $table ;
				
		if( !empty( $conditions))
		{
			$query .= ' where ';
		}
		foreach( $conditions as $key => $value)
		{
			$query .= $key . ' = ' . $value . ' and ';
		}
		$query = rtrim( $query, ' and ');
		
		if( $cache)
		{
			$data = $this->cache->load( $this->cache->generate_key( $query));
			if( $data)
			{
				echo '<br>data came from cache<br>';
				return $data;
			}
		}
		
		$return = array();
		
		$result = mysqli_query( $this->connection, $query);
		
		
		if( $result)
		{
			$i = 0;
			while( $row = mysqli_fetch_assoc( $result))
			{
				$return['value'][$i] = $row;
				$i++;												
			}
			
			while( mysqli_more_results( $this->connection))
			{
				mysqli_next_result( $this->connection);
				if( $l_result = mysqli_store_result( $this->connection))
				{
					mysqli_free_result( $l_result);
				}
			}
			
			$return['error'] = false;		
			mysqli_free_result( $result);
			
			if( $cache)
			{
				$this->cache->save($this->cache->generate_key($query), $return);
			}								
		}	
		else
		{
			trigger_error( 'error occured in select query : ' .mysqli_error( $this->connection).' <br> query : ' . $query .'<br>', E_USER_WARNING);
			$return['value'] = null;
			$return['error'] = true;	
		}

		
		return $return;	
	}
	
	function call( $procedure, $params = array(), $fetch_assoc = false) // use fetch_assoc as true in selective queries. do not use it in insert or update
	{																	// if you use it in insert | update queries,  call function will give warning
		$query = 'call '. $procedure . '(';
		if( !empty( $params))
		{
			$params = $this->clean_input( $params);
			foreach( $params as $param)
			{
				$query .= $param . ',';	
			}
			$query = rtrim( $query, ',');
		}
		$query .= ')';		
		
		$return = array();
		$result = mysqli_query( $this->connection, $query);
		if( $result)
		{
			if( $fetch_assoc)
			{
				$i = 0;
				$return ['value'] = array();
				while( $row = mysqli_fetch_assoc( $result))
				{
					$return['value'][$i] = $row;
					$i++;										
				}
				
				while( mysqli_more_results( $this->connection))
				{
					mysqli_next_result( $this->connection);
					if( $l_result = mysqli_store_result( $this->connection))
					{
						mysqli_free_result( $l_result);
					}
				}
							
				$return['error'] = false;	
				mysqli_free_result( $result);			
			}
			else
			{
				$return['error'] = false;			
			}
		}
		else
		{
			trigger_error( 'error occured in calling procedure : ' .mysqli_error( $this->connection).' <br> query : ' . $query .'<br>', E_USER_WARNING);
			$return['error'] = true;
		}
		
		return $return;
	}
	
	function delete( $table, $conditions = array() )
	{
		$conditions = $this->clean_input( $conditions);
		$query = 'delete from ' . $table;
		if( !is_array( $conditions))
		{
			trigger_error('$conditions is not array in delete( $table, $conditions)', E_USER_ERROR);
		}
		
		if( !empty( $conditions))
		{
			$query .= ' where ';
		}
		
		foreach( $conditions as $key => $value)
		{
			$query .= $key . ' = ' . $value . ' and ';
		}
		
		$query = rtrim( $query, ' and ');
		
		$return = array();
		
		$result = mysqli_query( $this->connection, $query);
		
		if( $result)
		{
			$return['error'] = false;			
		}
		else
		{
			trigger_error( 'error occured in select query : ' .mysqli_error( $this->connection).' <br> query : ' . $query . '<br>', E_USER_WARNING);
			$return['error'] = true;
		}
		
		return $return;
	}
	
	function last_insert_id () // returns the last insert id of connection | result variable
	{
		return mysqli_insert_id( $this->connection);
	}
	
	function no_of_rows() // get number of rows 
	{
		return mysqli_num_rows( $this->connection);
	}
	
	function no_of_fields()
	{
		return mysqli_num_fields( $this->connection);
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
	
	function close_connection()
	{
		mysqli_close( $this->connection);
	}
	
}
