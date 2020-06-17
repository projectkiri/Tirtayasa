<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cache_model extends CI_Model {
	public function __construct() {
		parent::__construct();
		$this->load->driver('cache', array('adapter' => 'file'));
	}

	/**
	 * Checks cache if there is a value stored with a given key
	 * @param string $type cache type
	 * @param string $key the cache key
	 * @return the cache value, or null if cache miss
	 */
	public function get($type, $key) {
		$item = $this->cache->get(urlencode($key) . '.' . urlencode($type));
		if ($item === FALSE) {
			return null;
		} else {
			return $item;
		}
	}

	/**
	 * Put into cache, log warning if duplicate.
	 * @param string $type the cache type
	 * @param string $key the cache key
	 * @param mixed $value cache value
	 */
	public function put($type, $key, $value) {
		if (!$this->cache->save(urlencode($key) . '.' . urlencode($type), $value, 60 * 60 * 24 * 30 * 3)) {
			$this->load->model('Logging_model');
			$this->Logging_model->logError("Failed to save cache $type.$key=$value");
		}
	}
}