<?php
	define('BASEPATH', 'bukitjarian');
	require_once('../application/config/database.php');
	
	// Global debugging configuration
	error_reporting(E_ERROR | E_COMPILE_ERROR | E_COMPILE_WARNING);

	/** hostname for mysql server */
	$config_mysql_host = $db['default']['hostname'];
	/** username for mysql account */
	$config_mysql_username = $db['default']['username'];   
	/** password for the mysql account */
	$config_mysql_password = $db['default']['password'];
	/** database name that stores the tables */
	$config_mysql_database = $db['default']['database'];
	
	/** API key for main website. */
	$apikey_kiri = '02428203D4526448';

	/** Cookie expiry time. */
	$cookie_expiry = 3600 * 24 * 365;
		
	/** MySQL interval for session expiry. */
	$session_expiry_interval_mysql = '6 HOUR';
	/** Unix time interval for session expiry (seconds). */
	$session_expiry_interval_unix = 6 * 3600;
	
	/** Number of decimal digits for lat/lon. */ 
	$latlon_precision = 5;
	
	/** maximum uploaded file size */
	$max_filesize = 100 * 1024;
	
	/** The file to log error report. */
	$errorlog_file = "log/error.log";
		
	/** Maximum length of user id input. */
	$maximum_userid_length = 128;
	/** Maximum length of password input. */
	$maximum_password_length = 32;	
	/** The number of times hash will be done, final value will be 2^n. */
	$passwordhash_cost_log2 = 8;
	/** Allow hash function be portable (work in older system but less secured. */
	$passwordhash_portable = FALSE;

	// CicaheumLedeng protocol constants
	$proto_apikey = 'apikey';
	$proto_apikeys_list = 'apikeyslist';
	$proto_company = 'company';
	$proto_description = 'description';
	$proto_domainfilter = 'domainfilter';
	$proto_fullname = 'fullname';
	$proto_geodata = 'geodata';
	$proto_internalinfo = 'internalinfo';
	$proto_message = 'message';
	$proto_mode = 'mode';
	$proto_mode_add_track = 'addtrack';
	$proto_mode_add_apikey = 'addapikey';
	$proto_mode_cleargeodata = 'cleargeodata';
	$proto_mode_delete_place = 'deleteplace';
	$proto_mode_delete_track = 'deletetrack';
	$proto_mode_getdetails_track = 'getdetailstrack';
	$proto_mode_getprofile = 'getprofile';
	$proto_mode_importkml = 'importkml';
	$proto_mode_list_apikeys = 'listapikeys';
	$proto_mode_list_tracks = 'listtracks';
	$proto_mode_login = 'login';
	$proto_mode_logout = 'logout';
	$proto_mode_register = 'register';
	$proto_mode_update_apikey = 'updateapikey';
	$proto_mode_update_profile = 'updateprofile';
	$proto_mode_update_track = 'updatetrack';
	$proto_new_trackid = 'newtrackid';
	$proto_password = 'password';
	$proto_pathloop = 'loop';
	$proto_penalty = 'penalty';
	$proto_privilege_apiUsage = 'apiusage';
	$proto_privilege_route = 'route';
	$proto_privileges = 'privileges';
	$proto_routefinish = 'finish';
	$proto_sessionid = 'sessionid';
	$proto_status = 'status';
	$proto_status_credentialfail = 'credentialfail';
	$proto_status_error = 'error';
	$proto_status_ok = 'ok';
	$proto_status_sessionexpired = 'sessionexpired';
	$proto_trackid = 'trackid';
	$proto_trackname = 'trackname';
	$proto_tracktype = 'tracktype';
	$proto_trackslist = 'trackslist';
	$proto_tracktypeslist = 'tracktypeslist';
	$proto_transfernodes = 'transfernodes';
	$proto_traveltime = 'traveltime';
	$proto_updateprofile = 'updateprofile';
	$proto_uploadedfile = 'uploadedfile';
	$proto_userid = 'userid';
	$proto_verifier = 'verifier';
?>
