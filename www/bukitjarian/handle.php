<?php
require_once 'etc/utils.php';
require_once 'etc/constants.php';
require_once 'etc/PasswordHash.php';

start_working();

$mode = retrieve_from_post($proto_mode);

// Initializes MySQL and check for session
init_mysql();
if ($mode != $proto_mode_login && $mode != $proto_mode_logout && $mode != $proto_mode_register) {
	$sessionid = addslashes(retrieve_from_post($proto_sessionid));
	// Clear expired sessions
	mysqli_query($global_mysqli_link, "DELETE FROM sessions WHERE lastSeen < (NOW() - INTERVAL $session_expiry_interval_mysql)") or
		die_nice('Failed to clean expired sessions: ' . mysqli_error($global_mysqli_link), true);
	$result = mysqli_query($global_mysqli_link, "SELECT users.email, users.privilegeRoute, users.privilegeApiUsage FROM users LEFT JOIN sessions ON users.email = sessions.email WHERE sessions.sessionId = '$sessionid'") or
		die_nice('Failed to get user session information: ' . mysqli_error($global_mysqli_link), true);
	if (mysqli_num_rows($result) == 0) {
		deinit_mysql();
		// Construct json - session expired.
		$json = array(
			$proto_status => $proto_status_sessionexpired,
		);
		print(json_encode($json));
		exit(0);
	}
	$columns = mysqli_fetch_row($result);
	$active_userid = $columns[0]; 
	$privilege_route = $columns[1] != '0';
	$privilege_apiUsage = $columns[2] != '0';
}

if ($mode == $proto_mode_login) {
	$userid = addslashes(retrieve_from_post($proto_userid));
	$plain_password = addslashes(retrieve_from_post($proto_password));
	if (strlen($userid) > $maximum_userid_length) {
		return_invalid_credentials("User ID length is more than allowed (". strlen($userid) . ')');
	}
	if (strlen($plain_password) > $maximum_password_length) {
		return_invalid_credentials('Password length is more than allowed ('. strlen($password) . ')');
	}

	// Retrieve the user information
	$result = mysqli_query($global_mysqli_link, "SELECT * FROM users WHERE email='$userid'") or
		die_nice('Failed to verify user id: ' . mysqli_error($global_mysqli_link), true);
	if (mysqli_num_rows($result) == 0) {
		deinit_mysql();
		return_invalid_credentials("User id not found: $userid");
	}
	$userdata = mysqli_fetch_assoc($result);
	
	// Check against the stored hash.
	$hasher = new PasswordHash($passwordhash_cost_log2, $passwordhash_portable);
	if (!$hasher->CheckPassword($plain_password, $userdata['password'])) {
		log_statistic("$apikey_kiri", 'LOGIN', $userid . '/FAIL');
		deinit_mysql();
		return_invalid_credentials("Password mismatch for $userid");
	}
	
	log_statistic("$apikey_kiri", 'LOGIN', $userid . '/SUCCESS');
	
	// Create session id
	$sessionid = generate_sessionid();
	mysqli_query($global_mysqli_link, "INSERT INTO sessions (sessionId, email) VALUES ('$sessionid', '$userid')") or
		die_nice('Failed to generate session: ' . mysqli_error($global_mysqli_link), true);

	// Construct privilege lists
	$privileges = '';
	if ($userdata['privilegeRoute'] != 0) {
		$privileges .= ",$proto_privilege_route";
	}
	if ($userdata['privilegeApiUsage'] != 0) {
		$privileges .= ",$proto_privilege_apiUsage";
	}
	if (strlen($privileges) > 0) {
		$privileges = substr($privileges, 1);
	}
	
	// Construct json.
	$json = array(
			$proto_status => $proto_status_ok,
			$proto_sessionid => $sessionid,
			$proto_privileges => $privileges
	);
	
	deinit_mysql();
	print(json_encode($json));
} elseif ($mode == $proto_mode_logout) {
	$sessionid = addslashes(retrieve_from_post($proto_sessionid));

	// Remove the session information
	$result = mysqli_query($global_mysqli_link, "DELETE FROM sessions WHERE sessionId='$sessionid'") or
		die_nice('Failed to logout sessionid $sessionid: ' . mysqli_error($global_mysqli_link), true);
	deinit_mysql();
	well_done();	
} elseif ($mode == $proto_mode_add_track) {
	check_privilege($privilege_route); 
	$trackid = addslashes(retrieve_from_post($proto_trackid));
	$trackname = addslashes(retrieve_from_post($proto_trackname));
	$tracktype = addslashes(retrieve_from_post($proto_tracktype));
	$penalty = addslashes(retrieve_from_post($proto_penalty));
	$internalinfo = addslashes(retrieve_from_post($proto_internalinfo, false)) or $internalinfo = '';
	
	// Check if the id is already existed
	$result = mysqli_query($global_mysqli_link, "SELECT trackId FROM tracks WHERE trackId='$trackid'") or
		die_nice('Failed to check trackid existence: ' . mysqli_error($global_mysqli_link), true);
	if (mysqli_num_rows($result) == 0) {
		mysqli_query($global_mysqli_link, "INSERT INTO tracks (trackId, trackTypeId, trackName, penalty, internalInfo) VALUES ('$trackid','$tracktype','$trackname','$penalty','$internalinfo')") or
			die_nice('Failed to add a new track: ' . mysqli_error($global_mysqli_link), true);
	} else {
		die_nice("The trackId '$trackid' already existed.", true);
	}
	deinit_mysql();
	well_done();
} elseif ($mode == $proto_mode_update_track) {
	check_privilege($privilege_route);
	$trackid = addslashes(retrieve_from_post($proto_trackid));
	$newtrackid = addslashes(retrieve_from_post($proto_new_trackid));
	$tracktype = addslashes(retrieve_from_post($proto_tracktype));
	$trackname = addslashes(retrieve_from_post($proto_trackname));
	$internalinfo = addslashes(retrieve_from_post($proto_internalinfo, false)) or $internalinfo = '';
	$pathloop = retrieve_from_post($proto_pathloop) == 'true' ? 1 : 0;
	$penalty = addslashes(retrieve_from_post($proto_penalty));
	$transfernodes = retrieve_from_post($proto_transfernodes, false);
	
	// When changed, check if the id is already existed
	if ($newtrackid != $trackid) {
		$result = mysqli_query($global_mysqli_link, "SELECT trackId FROM tracks WHERE trackId='$newtrackid'") or
			die_nice('Failed to check trackid existence: ' . mysqli_error($global_mysqli_link), true);
		if (mysqli_num_rows($result) != 0) {
			die_nice("The new trackId '$newtrackid' already existed.", true);
		}
	}
	mysqli_query($global_mysqli_link, "UPDATE tracks SET trackTypeId='$tracktype', trackId='$newtrackid', trackName='$trackname', internalInfo='$internalinfo', pathloop='$pathloop', penalty='$penalty' WHERE trackId='$trackid'") or
		die_nice('Failed to update the track: ' . mysqli_error($global_mysqli_link));
	if (!is_null($transfernodes)) {
		$transfernodes = addslashes($transfernodes);
		mysqli_query($global_mysqli_link, "UPDATE tracks SET transferNodes='$transfernodes'WHERE trackId='$trackid'") or
			die_nice('Failed to update the track: ' . mysqli_error($global_mysqli_link));
	}
	deinit_mysql();
	well_done();
} elseif ($mode == $proto_mode_list_tracks) {
	check_privilege($privilege_route);
	// Retrieve track list from database
	$result = mysqli_query($global_mysqli_link, 'SELECT trackTypeId, trackId, trackName FROM tracks ORDER BY trackTypeId, trackId') or
		die('Cannot retrieve the track names from database');
	$track_list = array();	
	while ($row = mysqli_fetch_row($result)) {
		$track_list[] = array($row[1], htmlspecialchars($row[0] . '/' . $row[2]));
	}
	// Retrieve track types list result from database
	$result = mysqli_query($global_mysqli_link, 'SELECT trackTypeId, name FROM tracktypes ORDER BY trackTypeId') or
		die_nice('Cannot retrieve the track types from database');
	$tracktype_list = array();
	while ($row = mysqli_fetch_row($result)) {
		$tracktype_list[] = array($row[0], htmlspecialchars($row[1]));
	}
	
	// Construct json.
	$json = array(
		$proto_status => $proto_status_ok,
		$proto_trackslist => $track_list,
		$proto_tracktypeslist => $tracktype_list
	);
	
	deinit_mysql();
	print(json_encode($json));
} elseif ($mode == $proto_mode_getdetails_track) {
	check_privilege($privilege_route);
	$trackid = addslashes(retrieve_from_post($proto_trackid));

	// Retrieve result from database and construct in XML format
	$result = mysqli_query($global_mysqli_link, "SELECT trackTypeId, trackName, internalInfo, AsText(geodata), pathloop, penalty, transferNodes FROM tracks WHERE trackId='$trackid'") or
		die_nice("Can't retrieve the track details from database: " . mysqli_error($global_mysqli_link), true);
	$i = 0;
	$row = mysqli_fetch_row($result);
	if ($row == FALSE) {
		die_nice("Can't find track information for '$trackid'", true);
	}
	$geodata = lineStringToLatLngArray($row[3]);
	// Construct json.
	$json = array(
		$proto_status => $proto_status_ok,
		$proto_trackid => $trackid,
		$proto_tracktype => $row[0],
		$proto_trackname => $row[1],
		$proto_internalinfo => $row[2],
		$proto_geodata => $geodata,
		$proto_pathloop => ($row[4] > 0 ? true : false),
		$proto_penalty => doubleval($row[5]),
		$proto_transfernodes => is_null($row[6]) ? array('0-' . (count($geodata) - 1)) : split(',', $row[6]), 
	);
	
	deinit_mysql();
	print(json_encode($json));
} elseif ($mode == $proto_mode_cleargeodata) {
	check_privilege($privilege_route);
	$trackid = addslashes(retrieve_from_post($proto_trackid));
	
	mysqli_query($global_mysqli_link, "UPDATE tracks SET geodata=NULL, transferNodes=NULL WHERE trackId='$trackid'") or
		die_nice('Failed to clear the geodata: ' . mysqli_error($global_mysqli_link), true);
	
	deinit_mysql();
	well_done();
} elseif ($mode == $proto_mode_importkml) {
	check_privilege($privilege_route);
	$trackid = addslashes(retrieve_from_post($proto_trackid));
	// Import KML file into a geodata in database
	if ($_FILES[$proto_uploadedfile]['error'] != UPLOAD_ERR_OK) {
		die_nice("Server script is unable to retrieve the file, with PHP's UPLOAD_ERR_xxx code: " . $_FILES[$proto_uploadedfile]['error'], true);
	}
	if ($_FILES[$proto_uploadedfile]['size'] > $max_filesize) {
		die_nice("Uploaded file size is greater than maximum size allowed ($max_filesize)", true);
	}
	$file = fopen($_FILES[$proto_uploadedfile]['tmp_name'], "r") or die_nice('Unable to open uploaded file', true);
	$haystack = '';
	while ($line = fgets($file)) {
		$haystack .= trim($line);
	}
	$num_matches = preg_match_all("/<LineString>.*<coordinates>(.*)<\/coordinates>.*<\/LineString>/i", $haystack, $matches, PREG_PATTERN_ORDER);
	if ($num_matches != 1) {
		die_nice("The KML file must contain exactly one <coordinate> tag inside one <LineString> tag. But I found $num_matches occurences", true);
	}
	fclose($file);
	
	// Start constructing output
	$output = 'LINESTRING(';
	$points = preg_split('/\s+/', $matches[1][0]);
	for ($i = 0, $size = sizeof($points); $i < $size; $i++) {
		list($x, $y, $z) = preg_split('/\s*,\s*/', $points[$i]);
		if ($i > 0) {
			$output .= ',';
		}
		$output .= "$x $y";
	}
	$output .= ')';
	mysqli_query($global_mysqli_link, "UPDATE tracks SET geodata=GeomFromText('$output'), transferNodes=NULL WHERE trackId='$trackid'") or
		die_nice("Error updating the goedata: " . mysqli_error($global_mysqli_link), true);
	deinit_mysql();
	well_done();
} elseif ($mode == $proto_mode_delete_track) {
	check_privilege($privilege_route);
	$trackid = addslashes(retrieve_from_post($proto_trackid));
	
	init_mysql();
	
	// Check if the id is already existed
	mysqli_query($global_mysqli_link, "DELETE FROM tracks WHERE trackId='$trackid'") or
		die_nice('Failed to delete track $trackid: ' . mysqli_error($global_mysqli_link), true);
	if (mysqli_affected_rows($global_mysqli_link) == 0) {
		die_nice("The track $trackid was not found in the database", true);
	}
	deinit_mysql();
	well_done();	
} elseif ($mode == $proto_mode_list_apikeys) {
	check_privilege($privilege_apiUsage);
	// Retrieve api key list from database
	$result = mysqli_query($global_mysqli_link, "SELECT verifier, domainFilter, description FROM apikeys WHERE email='$active_userid' ORDER BY verifier") or
		die_nice('Cannot retrieve the API keys list from database: ' . mysqli_error($global_mysqli_link));
	$apikey_list = array();	
	while ($row = mysqli_fetch_row($result)) {
		$apikey_list[] = array($row[0], $row[1], $row[2]);
	}
	
	// Construct json.
	$json = array(
		$proto_status => $proto_status_ok,
		$proto_apikeys_list => $apikey_list,
	);
	
	deinit_mysql();
	print(json_encode($json));
} elseif ($mode == $proto_mode_add_apikey) {
	check_privilege($privilege_apiUsage);
	$domainfilter = addslashes(retrieve_from_post($proto_domainfilter));
	$description = addslashes(retrieve_from_post($proto_description));
	$apikey = generate_apikey();
	
	// Retrieve api key list from database
	$result = mysqli_query($global_mysqli_link, "INSERT INTO apikeys(verifier, email, domainFilter, description) VALUES('$apikey', '$active_userid', '$domainfilter', '$description')") or
		die_nice('Cannot insert a new api key: ' . mysqli_error($global_mysqli_link));

	log_statistic("$apikey_kiri", 'ADDAPIKEY', $userid . $apikey);
	
	// Construct json.
	$json = array(
			$proto_status => $proto_status_ok,
			$proto_verifier => $apikey,
	);
	
	deinit_mysql();
	print(json_encode($json));
} elseif ($mode == $proto_mode_update_apikey) {
	check_privilege($privilege_apiUsage);
	$apikey = addslashes(retrieve_from_post($proto_verifier));
	$domainfilter = addslashes(retrieve_from_post($proto_domainfilter));
	$description = addslashes(retrieve_from_post($proto_description));
	// Ensure that this user has access to the apikey
	$result = mysqli_query($global_mysqli_link, "SELECT email FROM apikeys WHERE verifier='apikey'") or
		die_nice('Cannot check API key owner: ' . mysqli_error($global_mysqli_link));
	while ($row = mysqli_fetch_row($result)) {
		if ($row[0] != $active_userid) {
			die_nice("User $active_userid does not have privilege to update API Key $apikey");
		}
	}
	mysqli_query($global_mysqli_link, "UPDATE apikeys SET domainFilter='$domainfilter', description='$description' WHERE verifier='$apikey'") or
		die_nice('Failed to update API Key: ' . mysqli_error($global_mysqli_link));
	
	deinit_mysql();
	well_done();
} elseif ($mode == $proto_mode_register) {
	$email = addslashes(retrieve_from_post($proto_userid));
	$fullname = addslashes(retrieve_from_post($proto_fullname));
	$company = addslashes(retrieve_from_post($proto_company));

	// Check if the email has already been registered.
	$result = mysqli_query($global_mysqli_link, "SELECT email FROM users WHERE email='$email'") or
		die_nice('Cannot check user id existence: ' . mysqli_error($global_mysqli_link));
	if (mysqli_num_rows($result) > 0) {
		die_nice("Ooops! Email $email has already registered. Please check your mailbox or contact hello@kiri.travel");
	}

	// Generate and send password
	$password = generate_password();
	$hasher = new PasswordHash($passwordhash_cost_log2, $passwordhash_portable);
	$passwordHash = $hasher->HashPassword($password); 
	mysqli_query($global_mysqli_link, "INSERT INTO users(email, password, privilegeApiUsage, fullName, company) VALUES('$email', '$passwordHash', 1, '$fullname', '$company')") or
		die_nice('Cannot add new user $email: ' . mysqli_error($global_mysqli_link));	
	sendPassword($email, $password, $fullname);

	log_statistic("$apikey_kiri", 'REGISTER', "$email/$fullname/$company");
	
	deinit_mysql();
	well_done();
} elseif ($mode == $proto_mode_getprofile) {
		$email = $active_userid;
	
		$result = mysqli_query($global_mysqli_link, "SELECT fullName, company FROM users WHERE email='$email'") or
			die_nice('Cannot retrieve user details: ' . mysqli_error($global_mysqli_link));
		if ($row = mysqli_fetch_row($result)) {
			$fullname = $row[0];
			$company = $row[1];
		} else {
			die_nice("User $email not found in database.");
		} 
	
		deinit_mysql();
		// Construct json.
		$json = array(
				$proto_status => $proto_status_ok,
				$proto_fullname => $fullname,
				$proto_company => $company
		);
		
		print(json_encode($json));
} elseif ($mode == $proto_mode_update_profile) {
	$email = $active_userid;
	$password = addslashes(retrieve_from_post($proto_password, false));
	$fullname = addslashes(retrieve_from_post($proto_fullname));
	$company = addslashes(retrieve_from_post($proto_company));

	// Updates password if necessary
	if (!is_null($password) && $password != "") {		
		$hasher = new PasswordHash($passwordhash_cost_log2, $passwordhash_portable);
		$passwordHash = $hasher->HashPassword($password);
		mysqli_query($global_mysqli_link, "UPDATE users SET password='$passwordHash' WHERE email='$email'") or
			die_nice('Cannot update password for $email: ' . mysqli_error($global_mysqli_link));
	}
	mysqli_query($global_mysqli_link, "UPDATE users SET fullName='$fullname', company='$company' WHERE email='$email'") or
		die_nice('Cannot update profile for $email: ' . mysqli_error($global_mysqli_link));

	deinit_mysql();
	well_done();
} else {
	die_nice("Mode not understood: \"" . $mode . "\"", true);
}

/**
 * Return invalid credential error, close mysql connection, and exit.
 * @param string $logmessage the message to record in the log file.
 */
function return_invalid_credentials($logmessage) {
	global $proto_status, $proto_status_credentialfail, $errorlog_file, $global_mysqli_link;
	$ip_address = $_SERVER['REMOTE_ADDR'];
	log_error("Login failed (IP=$ip_address): $logmessage");
	$json = array(
		$proto_status => $proto_status_credentialfail);
	print(json_encode($json));
	mysqli_close($global_mysqli_link);
	exit(0);
}

/**
 * 
 * Simply checks the input parameter, when false do default action
 * to return "user does not have privilege"
 * @param boolean $privilege if false will return error
 */
function check_privilege($privilege) {
	if (!$privilege) {
		die_nice("User doesn't have enough privilege to perform the action.", true);
	}
}

/**
 * Scans a directory and remove files that have not been modified for max_age
 * @param string $path the path to the directory to clean 
 * @param int $max_age maximum age of the file in seconds
 * @return boolean true if okay, false if there's an error.
 */
function clean_temporary_files($path, $max_age) {
	$currenttime = time();
	if ($dirhandle = opendir($path)) {
		while (($file = readdir($dirhandle)) != FALSE) {
			$fullpath = "$path/$file";
			if (is_file($fullpath) && $currenttime - filemtime($fullpath) > $max_age) {
				if (!unlink($fullpath)) {
					return FALSE;
				}
			}
		}
		return TRUE;
	} else {
		return FALSE;
	}
}

?>
