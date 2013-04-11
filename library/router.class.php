<?php

class Router {
	
	private static $_router;
	
	private $_routes = array(); // custom routes array
	public $args = array();
	public $controller;
	public $action;	
	
	private function __construct()
	{
		
	}
	
	public static function get_instance()
	{
		if( ! isset( self::$_router))
		{
			self::$_router = new Router();
		}
		
		return self::$_router;
	}
	
	public function hook( )
	{
		$this->get_controller();
				
		// create a new controller instance
		$controller_name = $this->controller;		
		$controller = ucwords( $this->controller . 'Controller' ) ;
													
		// run the action					
		if( file_exists( CONTROLLER_PATH . DS . strtolower($controller_name) . '.php'))
		{
			$dispatch = new $controller( $controller_name, $this->action);
			
			if ( method_exists( $dispatch, $this->action))
			{					
				 $call = call_user_func_array( array( $dispatch,$this->action),$this->args);					 
			}
			else 
			{
							
				if( Config::get('environment') === 'live') // if the action doesn't exist route to default index controller
				{											
					header("location:". Config::get('base_href') . '/' . $controller_name);
					exit();									
				}
				
				trigger_error('controller function doesnt exist, controller : '.$controller_name. ' , action : ' . $this->action. '' , E_USER_ERROR );	
			}
		}
		else
		{		
			trigger_error( 'controller ' . $controller_name . ' does not exist' ,E_USER_ERROR);
			
			if( Config::get('environment') === 'live')
			{								
				header("location:". Config::get('base_href') . '/error');
				exit();
			}								
		}			
	}
			
	private function get_controller(){
				
		$route = ( empty( $_GET['url'])) ? '' : $_GET['url'];		 	
		
		if( empty( $route))
		{
			$route = 'index';
		}
		else 
		{
			$url_array = explode('/', $route);
			$this->controller = $url_array[0];
									
			if( isset($url_array[1]))
			{
				$this->action = $url_array[1];
			}
			if( isset($url_array[2])) // now the functions could have infinite number of arguments
			{ 
				$j = 0;
				for ($i = 2; $i < sizeof($url_array); $i++)
				{
					$this->args[$j] = $url_array[$i];
					$j++;					
				}				
			}
			
			if( !empty( $this->_routes))
			{
				foreach( $this->_routes as $c_route => $params)
				{
					// control root routing
 					if( $c_route == '/')
					{						
						
						if( ! file_exists( CONTROLLER_PATH . DS . strtolower( $this->controller) . '.php'))
						{							
							$this->controller = $params['controller'];
							$this->action = $params['action'];
							
							if( isset( $params[ 'args'] ))
							{
								$j = 0;
									
								for( $i = $params[ 'args']; $i < sizeof( $url_array); $i++)
								{
							
									$this->args[ $j] = $url_array[ $i];
									$j++;
								}
							}
							
							return;
						}
						
					}
					
					if( $c_route === $this->controller)
					{
						$this->controller = $params[ 'controller'];
						$this->action = $params[ 'action'];
						
						if( isset( $params[ 'args'] ))
						{
							$j = 0;										
							
							for( $i = $params[ 'args']; $i < sizeof( $url_array); $i++)
							{
								
								$this->args[ $j] = $url_array[ $i];
								$j++;
							}	
						}
						
						return;
					}
				}
			}
			
		}
		
		if( empty($this->controller))
		{
			$this->controller = 'index';
		}
		
		if( empty($this->action))
		{
			$this->action = 'index';
		}
			
	}
	
	// @todo : improve custom routing
	public function rewrite( $url, $rules)
	{
		$this->_routes[$url] = $rules; 		
	}
				
}