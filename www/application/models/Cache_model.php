<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cache_model extends CI_Model {
	/**
	 * Checks cache if there is a value stored with a given key
	 * @param string $type cache type
	 * @param string $key the cache key
	 * @return the cache value, or null if cache miss
	 */
	function get($type, $key) {
		$query = $this->db->query('SELECT cacheValue FROM cache WHERE type=? AND cacheKey=?', array($type, $key));
		if ($query->num_rows() == 0) {
			return null;
		} else {
			$row = $query->row();
			return $row->cacheValue;
		}
	}

	/**
	 * Put into cache, log warning if duplicate.
	 * @param string $type the cache type
	 * @param string $key the cache key
	 * @param string $value cache value
	 */
	function put($type, $key, $value) {
		$result = $this->db->query('INSERT INTO cache(type, cacheKey, cacheValue) VALUES(?,?,?)', array($type, $key, $value)); 
		if ($result == FALSE) {
			$this->Logging_model->logError("Failed to store $type/$key=$value into cache");
		}
	}
}