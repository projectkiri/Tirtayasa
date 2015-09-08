<?php
require_once 'constants.php';
require_once 'ext/PHPMailer/PHPMailerAutoload.php';

/** Determines whether we should return error message or log it */
$global_hush_hush = false;

/** Stores the mysql connection for the whole script. */
$global_mysqli_link = null;

/**
 * Initialize mysql connection with the default configuration as specified in
 * constants.php. This method will also exit and print error message in case of problem.
 */
function init_mysql() {
	global $global_mysqli_link, $config_mysql_host, $config_mysql_username, $config_mysql_password, $config_mysql_database;
	$global_mysqli_link = mysqli_connect($config_mysql_host, $config_mysql_username, $config_mysql_password, $config_mysql_database) or
		die_nice("MySQL Connect error: " . mysqli_connect_error(), $header_printed);
}

/**
 * Close mysql connection, with additional error reporting.
 */
function deinit_mysql() {
	global $global_mysqli_link;
	mysqli_close($global_mysqli_link) or
		die_nice("Failure in closing mysql connection. Your transaction may have been processed.");
}

/**
 * Increment the track version by 1. Normally called after successful insert/delete/update
 */
function update_trackversion() {
	global $global_mysqli_link;
	mysqli_query($global_mysqli_link, "UPDATE properties SET propertyvalue=propertyvalue+1 WHERE propertyname='trackversion'") or
	die_nice("Error updating track version: " . mysqli_error($global_mysqli_link));
}

/**
 * Log statistic entry to database
 * @param unknown $verifier the API key or host name
 * @param unknown $type the event type, one of $type_xxx in constants.
 * @param unknown $additional_info additional information about the event
 */
function log_statistic($verifier, $type, $additional_info) {
	global $global_mysqli_link;
	mysqli_query($global_mysqli_link, "INSERT INTO statistics (verifier, type, additionalInfo) VALUES ('$verifier','$type','$additional_info')") or
		die_nice("Error updating statistic: " . mysqli_error($global_mysqli_link));
}

/**
 * Return JSON error message and quit nicely
 * @param string $message The error message to return
 * @param boolean $mysqlclose set true to close existing mysql connection
 */
function die_nice($message, $mysqlclose=false) {
	global $locale, $global_mysqli_link;
	global $proto_status, $proto_status_error, $proto_message, $global_hush_hush;
	if ($global_hush_hush) {
		log_error($message);
		// In case we have a localization
		if ($locale == "id") {
			$message = 'Mohon ampun, ada masalah internal. Programmer amatir! Tapi jangan khawatir, ia akan ditegur.';
		} else {
			$message = 'Sorry, there\'s internal error. Bad coder, but he\'ll be notified!';
		}
	}
	$json = array(
		$proto_status => $proto_status_error,
		$proto_message => $message);
	print(json_encode($json));
	if ($mysqlclose) {
		mysqli_close($global_mysqli_link);
	}
	exit(0);
}

/**
 * Return JSON status of OK, optionally with a message. Exits
 * the execution to ensure there's no additional outputs. 
 * @param string $message Optional message to be passed
 */
function well_done($message = null) {
	global $proto_status, $proto_status_ok, $proto_message;
	$json = array(
		$proto_status => $proto_status_ok,
	);
	if ($message != null) {
		$json[$proto_message] = $message;
	}
	print(json_encode($json));
	exit(0);
}

/**
 * Perform initializations on the PHP script
 */
function start_working() {
	header('Content-Type: application/json');
	header('Cache-control: no-cache');
	header('Pragma: no-cache');
}

/**
 * Get a parameter from post method, or return an error if not available
 * @param string $param the parameter name from post or get
 * @param boolean $mandatory when true, script will return error if parameter is not found 
 */
function retrieve_from_post($param, $mandatory = true) {
	$value = is_null($_POST[$param]) ? $_GET[$param] : $_POST[$param];
	if ($mandatory && $value == null) {
		die_nice("Value of $param is expected but not found");
	}
	// TODO try urldecode it, but see the impact for mjnserve 
	return $value;
}

/**
 * Log an error to the predefined file
 * @param int $messsage The error message
 * @param $errorlog_location the error file location if needed to change.
 */
function log_error($message, $errorlog_location = null) {
	global $errorlog_file, $global_hush_hush;
	if (is_null($errorlog_location)) {
		$errorlog_location = $errorlog_file;
	}
	$file = fopen($errorlog_location, "a");
	if ($file == NULL) {
		// Don't let be in infinite loop
		$global_hush_hush = false;
		die_nice("Internal fatal error. I couldn't tell the coder. Could you mail to pascalalfadian@live.com? Please...?");
	}
 	$time = strftime('%d-%b-%Y %H:%M:%S GMT', time());
 	$server = '';
 	foreach ($_SERVER as $key => $value) {
 		$server .= str_replace("\n", "\\n", "$key=>$value;");
 	}
 	$post = '';
 	foreach ($_GET as $key => $value) {
 		$post .= str_replace("\n", "\\n", "$key=>$value;");
 	}
	fwrite($file, "time=$time;message=$message;\$POST=$post\n");	
	fclose($file);
}

/**
 * Generates a random session id.
 * @return string the session id generated
 */
function generate_sessionid() {
	return generate_random("abcdefghiklmnopqrstuvwxyz0123456789", 16);
}

function generate_apikey() {
	return generate_random("01234456789ABCDEF", 16);
}

function generate_password() {
	return generate_random("abcdefghiklmnopqrstuvwxyz0123456789", 8);
}

/**
 * Generates a random string
 * @param string $chars available characters
 * @param int $length size of the string
 * @return string the generated string
 */
function generate_random($chars, $length) {
	$chars_size = strlen($chars);
	$string = '';
	for ($i = 0; $i < $length; $i++) {
		$string .= $chars[rand(0, $chars_size)];
	}
	return $string;
}

/**
 * Converts SQL's LINESTRING() format into array of LatLng
 * @param string $lineString
 * @return array of "lat,lng"
 */
function lineStringToLatLngArray($lineString) {
	if (is_null($lineString)) {
		return null;
	}
	$lineString = preg_replace('/LINESTRING\(([^)]+)\)/', '$1', $lineString);
	$lnglatArray = split(',', $lineString);
	$returnValue = array();
	foreach ($lnglatArray as $lnglat) {
		list($lng,$lat) = split(' ', $lnglat);
		$returnValue[] = sprintf("%.$latlon_precision" . "f,%.$latlon_precision" . "f", $lat, $lng);
	}
	return $returnValue;
}

/**
 * Send password to recipient
 * @param unknown $email the recipient email
 * @param unknown $password password
 * @param unknown $fullname Full name of the recipient
 * @param unknown $debug_level 0 to 2 for more debug options
 */
function sendPassword($email, $password, $fullname, $debug_level = 0) {
	define('BASEPATH', 'bukitjarian');
	require_once('../application/config/credentials.php');

	date_default_timezone_set ( 'Asia/Jakarta' );
	$mail = new PHPMailer ();
	$mail->isSMTP ();
	$mail->Host = $config['email']['smtp_host'];
	$mail->Port = $config['email']['smtp_port'];
	$mail->SMTPAuth = true;
	$mail->SMTPSecure = ['email']['smtp_crypto'];
	$mail->Username = $config['email']['smtp_user'];
	$mail->Password = $config['email']['smtp_pass'];
	$mail->setFrom ( 'hello@kiri.travel', 'Project KIRI' );
	$mail->addAddress ( $email, $fullname );
	$mail->Subject = 'KIRI API Registration';
	$mail->msgHTML ( "<p>Hello $fullname,</p>" . "<p>Thank you for becoming KIRI Friends. Please find below your<br/>" . "initial password (8 characters of alphanumerics): <pre>$password</pre><br/>" . "Please login to our site and change your password immediately.</p>" . "<p>Sincerely yours,<br/>" . "Pascal & Budyanto</p>" );
	$mail->AltBody = "Hello $fullname,\n\n" . "Thank you for becoming KIRI Friends. Please find below your\n" . "initial password (8 characters of alphanumerics): $password\n" . "Please login to our site and change your password immediately.\n\n" . "Sincerely yours,\n" . "Pascal & Budyanto\n";

	// send the message, check for errors
	if (!$mail->send ()) {
		die_nice('Email error: ' . $mail->ErrorInfo);
	}
}

?>