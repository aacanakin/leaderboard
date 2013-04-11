<?php

/** check if the environment is development or not **/

function set_error_reporting ( )
{	
	ini_set("log_errors" , "1");
	ini_set ( 'error_log', ROOT . DS . 'tmp'. DS . 'logs' . DS . 'error_log');
	
	error_reporting( E_ALL);
	
	if( Config::get('environment') === 'development')
	{			
		ini_set( 'display_errors', 'On');
		
	}
	else if( Config::get('environment') === 'live') 
	{			
		ini_set ( 'display_errors', 'Off');		
	}
	else
	{
		trigger_error('invalid environment variable. you can only choose development or live', E_USER_ERROR);
	}
}

function clean_data ( $value)
{		
	$value = is_array($value) ? array_map( 'clean_data', $value) : stripslashes($value); strip_tags($value);
	return $value;
}

function remove_magic_quotes() 
{
	
	if( get_magic_quotes_gpc())
	{
		$_GET = clean_data( $_GET);
		$_POST = clean_data( $_POST);
		$_COOKIE = clean_data( $_COOKIE);
	}

}

function unregister_globals()
{

	if( ini_get('register_globals') ){
	
		$array = array( '_SESSION', '_POST', '_GET', '_COOKIE', '_REQUEST', '_SERVER', 'ENV', '_FILES');
		foreach ($array as $value)
		{
			foreach ($GLOBALS[$value] as $k => $v)
			{
				if( $v === $GLOBALS[$k])
				{
					unset( $GLOBALS[$k]);
				}
			}
		}
	}
}

function error_handler( $err_no, $err_str, $err_file, $err_line)
{	
	if( Config::get('environment') === 'live')
	{
		return;
	}
	
	if( !(error_reporting() & $err_no))
	{
		return;
	}
	
	// make css styles
	echo "
	<style type=\"text/css\">
	body{
    font-family:Arial, Helvetica, sans-serif; 
    font-size:13px;
	}
	.info, .success, .warning, .error, .validation {
    border: 1px solid;
    margin: 10px 0px;
    padding:15px 10px 15px 50px;
    background-repeat: no-repeat;
    background-position: 10px center;
	}
	.info {
    color: #00529B;
    background-color: #BDE5F8;

	}	
	.success {
    color: #4F8A10;
    background-color: #DFF2BF;

	}
	.warning {
    color: #9F6000;
    background-color: #FEEFB3;

	}
	.error {
    color: #D8000C;
    background-color: #FFBABA;

	}
	</style>
	";
	
	$log = 'Error [errno:'.$err_no.'] - '.$err_str.' on line : '.$err_line.', in file : '.$err_file;
	error_log( $log);
	
	$backtrace = '';
	
	if( Config::get('debugging') > 0)
	{
		$backtrace = generate_backtrace();
	}
	
	switch ( $err_no)
	{
		case E_USER_ERROR : 
			echo '<div class="error"><pre>Error [errno:'.$err_no.'] - '.$err_str.' on line : '.$err_line.', in file : '.$err_file . $backtrace . '</pre></div>'; 
			exit(1);
			break;
		case E_USER_WARNING :
			echo '<div class="warning"><pre>Warning [warno:'.$err_no.'] - '.$err_str.' on line : '.$err_line.', in file : '.$err_file . $backtrace .'</pre></div>'; 
			break;
		case E_USER_NOTICE :
			echo '<div class="info"><pre>Notice [notno:'.$err_no.'] - '.$err_str.' on line : '.$err_line.', in file : '.$err_file . $backtrace .'</pre></div>';
			break;
		default: 
			echo '<div class="error"><pre>Error [errno:'.$err_no.'] - '.$err_str.' on line : '.$err_line.', in file : '.$err_file . $backtrace .'</pre></div>';
			break;
	}
	
	return true;
}

function fatal_error_handler()
{
	$error = error_get_last();
	
	if( $error !== NULL)
	{
		echo "
		<style type=\"text/css\">
		body{
	    font-family:Arial, Helvetica, sans-serif;
	    font-size:13px;
		}
		.info, .success, .warning, .error, .validation {
	    border: 1px solid;
	    margin: 10px 0px;
	    padding:15px 10px 15px 50px;
	    background-repeat: no-repeat;
	    background-position: 10px center;
		}
		.info {
	    color: #00529B;
	    background-color: #BDE5F8;
			
		}
		.success {
	    color: #4F8A10;
	    background-color: #DFF2BF;
			
		}
		.warning {
	    color: #9F6000;
	    background-color: #FEEFB3;
			
		}
		.error {
	    color: #D8000C;
	    background-color: #FFBABA;
			
		}
		</style>
		";
		
		// @todo : implement debug_backtrace for better results
		
		$backtrace = '';
		
		if( Config::get('debugging') > 0)
		{
			$backtrace = generate_backtrace( true); 		
		}
		
		$error_info = "<pre>Fatal error occured in file : " .$error['file'] . ' in line : '.$error['line'] . ' ;<br>' .$error['message'].'</pre>';
		echo '<div class="error">' . $error_info . $backtrace . '</div>';			
				
	}				
}

function generate_backtrace( $fatal = false) // generates debug bactrace in html format
{
	$return = '<pre>Trace<br>';

	if( $fatal)
	{
		$e = new Exception();
		$return .= $e->getTraceAsString();
	}
	else
	{
		if( Config::get('debugging') == 1)
		{
			$e = new Exception();
			$return .= $e->getTraceAsString();
		}	
// 		else if( Config::get('debugging') == 1) // smart debugging
// 		{
// 			$e = new Exception();
// 			$trace = $e->getTrace();
			
// 			$i = 0;
// 			$body = '';
// 			foreach( $trace as $k=>$v)
// 			{
// 				if( isset( $v['file'])) // then it's not an object
// 				{
// 					if( $v['function'] != 'error_handler')
// 					{
					
// 						if( ! contains( DS . 'webroot' . DS .'index.php', $v['file']) && ! contains( DS . 'library' . DS, $v['file']))
// 						{
// 							// then it's not a internal framework error
// 							$body .= '#' . $i . ' ';
// 							$body .= $v['file'].'('.$v['line'].'): ';
// 							if( isset( $v['class']))
// 							{
// 								$body .= $v['class'] . $v['type'];
// 							}
// 							$body .= $v['function'].'(';
// 							foreach( $v['args'] as $arg)
// 							{
// 								if( is_string( $arg))
// 								{
// 									$body .= '\'' . $arg . '\'' . ',';
// 								}	
// 								else
// 								{
// 									$body .= $arg . ',';
// 								}													
// 							}
// 							$body = rtrim( $body, ',');
// 							$body .= ')<br>';
// 							$i++;
// 						}	
// 					}														
// 				}
// 				else
// 				{
// 					$body .= '#' . $i . ' ';
// 					$body .= '[internal function] ';
// 					$body .= $v['class'] . $v['type'] . $v['function'] . '(';
// 					foreach( $v['args'] as $arg)
// 					{
// 						if( is_string( $arg))
// 						{
// 							$body .= '\'' . $arg . '\'' . ',';
// 						}
// 						else
// 						{
// 							$body .= $arg . ',';
// 						}						
// 					}
// 					$body = rtrim( $body, ',');
// 					$body .= ')<br>';
// 					$i++;
// 				}								
// 			}
// 			$return .= $body;
// 		}
		else 
		{
			return '';
		}
	}

	$return .= '<br></pre>';
	return $return;
}

function contains($substring, $string) 
{
	$pos = strpos($string, $substring);

	if($pos === false) 
	{
		// string needle NOT found in haystack
		return false;
	}
	else 
	{
		// string needle found in haystack
		return true;
	}
}
