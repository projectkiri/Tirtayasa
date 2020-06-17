<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Logging_model extends CI_Model {
	/**
	 * Log statistic entry to database
	 * @param string $apikey the API key or host name
	 * @param string $type the event type, one of $type_xxx in constants.
	 * @param string $additionaInfo additional information about the event
	 */
	public function logStatistic($apikey, $type, $additionalInfo) {
		// FIXME switch to SQLite for space savings!
		$this->load->database();		
		$result = $this->db->query('INSERT INTO statistics(verifier, type, additionalInfo) VALUES(?,?,?)', array($apikey, $type, $additionalInfo));
		if ($result == FALSE) {
			$this->logError("Failed to store $apiKey/$type/$additionalInfo into statistic");
		}
	}

	public function logError($message) {
		log_message('error', $message);
	}

}