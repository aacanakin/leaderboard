<?php
// should include smarty directory here
require_once SMARTY_PATH;

class View {

	protected $_template;
	protected $_controller;
	protected $_action;
	
	function __construct( $controller, $action){
		
		$this->_controller = $controller;
		$this->_action = $action;
		
		$this->_template = new Smarty();
		
		if(! is_writable(TMP_PATH . DS . 'cache' . DS . 'views'))
		{
			trigger_error('your tmp/ directory is not writable', E_USER_ERROR);
		}
		
		$this->_template->cache_dir = TMP_PATH . DS . 'cache' . DS . 'views';
        $this->_template->template_dir = '';
        $this->_template->compile_dir = TMP_PATH . DS . 'cache' . DS . 'views' . DS . 'compile';
        $this->_template->compile_check = true;
        $this->_template->caching = false;
        $this->_template->cache_lifetime = 1200;
	}
	
	function assign( $key, $value)
	{
		$this->_template->assign( $key, $value);
	}
	
	function render( $path, $return)
	{	
		$file_path = VIEW_PATH . DS . $this->_controller . DS . $this->_action . '.tpl';
		
		if( !is_null( $path))
		{
			$file_path = VIEW_PATH . $path . '.tpl';
		}		
		if( !file_exists( $file_path))
		{
			trigger_error( 'file : ' . $file_path . ' doesn\'t exist', E_USER_ERROR);
		}
		
		if(!$return)
		{
			$this->_template->display( $file_path); // base dir and template dir should be catted
		}
		else
		{
			return $this->_template->fetch( $file_path);
		}
	}
	
	function debug()
	{
		$this->_template->debugging = true;
	}
	
	function set_caching( $caching = false, $cache_lifetime = 1200)
	{
		$this->_template->caching = $caching;
		$this->_template->cache_lifetime = $cache_lifetime;
	}
	
}