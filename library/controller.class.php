<?php
class Controller
{
	protected $view;
	protected $_models = array();
	protected $_controller;
	protected $_action;
	
	function __construct( $controller , $action)
	{
		$this->before_load(); // call callback function		
		$this->_action = $action;
		$this->_controller = $controller;		
		$this->view = new View( $this->_controller, $action);
		
	}
	
	function __get( $key)
	{
		return $this->_models[$key];
	}
	
	function before_load() // callback for before the page is rendered. it should be used for loading models/helpers,checking session, 
						   // saving state, getting info about user agent, etc.
	{					   					
		trigger_error( 'default before_load() function fired. you should override it on your custom controller, be aware!', E_USER_WARNING);	
	}
	
	function redirect( $page)
	{
		header("location:". Config::get('base_href') . $page . "");
		exit();
	}
	
    function index() {
    
    	trigger_error('default controller index() function fired. please implement index() function on your custom controller', E_USER_WARNING);
    }
    
    function render( $path = null, $fetch = false)
    {
    	$this->view->render( $path, $fetch);
    }
    
    function load_model( $model_name)
    {    	
    	$model_class = $model_name.'Model';
    	$this->_models[$model_name] = new $model_class();
    }
    
    function get_model( $model_name)
    {
    	return $this->_models[$model_name];
    }
    
    function load_helper( $helper_name) // includes helper class or scripts, works either object-oriented or functional mode.
    {
    	if( file_exists( HELPER_PATH . DS . $helper_name . '.php'))
    	{
    		require_once ( HELPER_PATH . DS . $helper_name . '.php' );
    	}
    	else
    	{
    		trigger_error('helper : ' . $helper_name . ' not found in file : ' . HELPER_PATH . DS . $helper_name . '.php', E_USER_ERROR);
    	}
    }
    
    function run_script( $script_name) // only includes the file. it is used only for scripts, not objects in scripts.
    {
    	if( file_exists( SCRIPT_PATH . DS . $script_name . '.php'))
    	{
    		require_once ( SCRIPT_PATH . DS . $script_name . '.php');
    	}
    	else
    	{
    		trigger_error( 'script : ' . $script_name . ' not found in file : ' . SCRIPT_PATH . DS . $script_name . '.php', E_USER_ERROR );
    	}
    }    
    
    function import( $path) // import function from external folder
    {
    	if( file_exists( EXTERNAL_PATH . DS . $path . '.php'))
    	{
    		require_once ( EXTERNAL_PATH . DS . $path . '.php');
    	}
    	else
    	{
    		trigger_error( 'external library : ' .$path. ' not found in file : ' . EXTERNAL_PATH . DS . $path . '.php', E_USER_ERROR);
    	}
    }
    
    function dump( $array = array(), $return = false)
    {
    	if( $return)
    	{
    		return var_export( $array);
    	}
    	else
    	{
    		echo '<pre>';
    		var_dump( $array);
    		echo '</pre>';
    	}    	
    }
    
    function look( $array = array(), $return = false)
    {
    	if( $return)
    	{
    		return print_r( $array, true);
    	}
    	else
    	{
    		echo '<pre>';
    		print_r( $array);
    		echo '</pre>';
    	}
    }

}