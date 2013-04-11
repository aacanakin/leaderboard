<?php

// GENERAL ENVIRONMENT VARIABLES
/*
 * the application key-secret for encrypt/decrypt operations with a key
 */
Config::set('app_secret', '----my secret key----');


/* 
 * the environment variable can be the following ;
 * - development
 * - live
 * it sets error_reporting according to the environment variable 
 */
Config::set('environment', 'development');


/*
 * timezone settings of server
 */
Config::set('timezone', date_default_timezone_set('Etc/Greenwich'));

/*
 * application encoding
 */
Config::set('encoding', 'UTF8');

/*
 * the debugging level is an integer value. the choice is mutual exclusive in default error handling
 * to use it, Config->error_handler variable must be set to 'ariba'
 * 
 *  0 - no debugging at all, backtrace won't be printed
 *  1 - an intelligent backtrace level, developers can't see internal framework traces 
 *  2 - developers can see all function, object calls. it uses Exception::getTraceAsString()
 *  
 *  NOTE : for live environments, please use 0 as debugging for performance issues.
 *  NOTE : fatal errors always use Exception::getTraceAsString() function in debugging.
 */
Config::set('debugging', 1);


/*
 * the error handler values could be the following ;
 * - default
 * - ariba
 * ariba will provide better error showing with better descriptions, but of course, you can use default as choice
 */
Config::set('error_handler', 'ariba');

/*
 * the db type field could be the following values ;
 * - mongodb
 * - mysql
 * if you want to use no-sql, choose mongodb, then mongodb driver will be loaded automatically
 */
Config::set('db_type', 'mysql');

/*
 * mysql driver version could the following;
 * 1 - loads the old version of ariba mysql driver
 * 2 - loads the newest version of ariba mysql driver
 */
Config::set('mysql_driver_version', 2);

/*
 * mysql new driver debugging mode
 * true : automatically returns an array with key debug
 * false: doesn't return anything
 */

Config::set('mysql_debugging', true);

/*
 * memcache extension loader 
 * if    true  => mysql driver will have cache feature
 * else  false => mysql driver won't use memcache
 * NOTE : mongodb driver doesn't have memcache feature.
 *  	  if you chose db_type as mongodb, this variable will be ignored and memcache will be automatically disabled
 */
Config::set('memcache', false);


/* prefixes ; memcache key prefixes should be defined here, sample memcache_key_prefixes could be the information objects which is pulled from database functions.
 * 			  these prefixes should catted with function parameters or column names of the query in order to create unique keys
 * 			  this configuration is for handling custom memcache keys. for instance, profile, settings, friends, preferences, account, etc.
 * uniques  ; unique fields should be stored here. this will make memcache keys not override at all. uniques can be chosen from db table primary_keys like _id, id, etc. 
 * 	 
 * 
 * unique key set of memcache handling should be initialized here if any pre-defined key is possible, it should be defined here 
 */
Config::set('memcache_keys', array( 'prefixes' => array(),
									'uniques'  => array()	
								  ));

								  
/*
 * security of the memcache keys. in order to guarantee uniqueness of keys, it may be needed to encrypt them
 * possible values are ; none, secure
 */								  
Config::set('memcache_key_security', 'none');


/*
 * memcache expire time 
 */
Config::set('memcache_lifetime', 60*60*24); // 1 day default expire lifetime


/*
 * application dependant constants
 */

/* base url and base href. DO NOT CHANGE THESE */
Config::set('base_url', $_SERVER['SERVER_NAME']);
Config::set('base_href', 'http://' . Config::get('base_url'));

Config::set('host','localhost'); // shouldn't be active in live environment

// facebook api key & secret for facebook connect helper

Config::set('fb_app_id', '---- your facebook application key ----');
Config::set('fb_app_secret', '---- your facebook application secret ----');

