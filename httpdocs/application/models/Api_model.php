<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_model extends CI_Model {
	/**
	 * Retrieves input from GET or POST.
	 */
	public function getInput($key, $mandatory = true) {
		$value = $this->input->get($key);
		if (is_null($value)) {
			$value = $this->input->post($key);
		}
		if (is_null($value) && $mandatory) {
			throw new Exception("400 Parameter $key is required but not provided");
		}
		return $value;
	}

	public function checkApiKey($apikey) {
		$this->load->model('Cache_model');
		$row = $this->Cache_model->get('apikey', $apikey);
		if (is_null($row)) {
			$this->load->database();		
			$query = $this->db->query('SELECT verifier, ipFilter FROM apikeys WHERE verifier = ?', array($apikey));
			if ($query->num_rows() == 0) {
				throw new Exception("401 API key is not recognized: $apikey");
			}
			$row = $query->row();
			$this->Cache_model->put('apikey', $apikey, $row);
		}
		if (!is_null($row->ipFilter) && $this->input->server('REMOTE_ADDR') == $row->ipFilter) {
			throw new Exception("401 IP address is not accepted for this API key.");
		}
	}

	public function outputJson($jsonArray, $statusCode = '200') {
		$this->output->set_status_header($statusCode);
		$this->output->set_content_type('application/json');
		$this->output->set_header('Cache-control: no-cache');
		$this->output->set_header('Pragma: no-cache');
		$this->output->set_output(json_encode($jsonArray));
	}

	/**
	 * Replace a location point into a human readable form with most effort.
	 * For example:
	 * <ul>
	 * <li>'start' => 'your starting point'.
	 * <li>'xxx.xxx,yyy.yyy' => check in cache, or reverse geocode from google if miss
	 * </ul>
	 * @param string $location the original location constant
	 */
	public function humanizePoint($location) {
		if ($location == 'start') {
			return $this->lang->line('your starting point');
		} else if ($location == 'finish') {
			return $this->lang->line('your destination');
		} else {
			$cached_geocode = $this->Cache_model->get('geocoding', $location);
			if (!is_null($cached_geocode)) {
				return $cached_geocode;
			} else {
				$full_url = $this->config->item('url-geocode') . '?key=' . $this->config->item('google-server-key') . '&latlng=' . urlencode($location) . '&sensor=false';
				$result = file_get_contents($full_url);
				if ($result == FALSE) {
					throw new Exception("There's an error while reading the geocoding response from $full_url.");
				}
				$json_response = json_decode($result, true);
				if ($json_response == NULL) {
					throw new Exception("Unable to retrieve JSON response from Google geocoding service.");
				}
				if ($json_response['status'] == 'OK') {
					$bestguess = $location;
					for ($i = 0; $i < count($json_response['results']); $i++) {
						foreach ($json_response['results'][0]['address_components'] as $component) {
							if (in_array('transit_station', $component['types']) || in_array('route', $component['types'])) {
								$this->Cache_model->put('geocoding', $location, $component['long_name']);
								return $component['long_name'];
							}
							$bestguess = $component['long_name'];
						}
					}
					$this->Logging_model->logError("Warning: can't find street name, use best guess $bestguess for $location.");
					$this->Cache_model->put('geocoding', $location, $bestguess);
					return $bestguess;
				} else if ($json_response['status'] == 'ZERO_RESULTS') {
					// If not found, return the coordinate.
					$this->Logging_model->logError("Warning: can't find coordinate for $location.");
					return $location;
				} else {
					throw new Exception("Problem while geocoding from Google reverse geocoding: " . $result);
				}
			}
		}
	}

	public function getTrackDetails($means, $meansDetail) {
		$this->load->database();		
		$query = $this->db->query('SELECT tracks.trackname AS trackName, tracktypes.name AS trackTypeName, tracktypes.url as ticketURL, tracks.extraParameters AS extraParameters, tracks.internalInfo AS internalInfo, tracktypes.speed AS speed FROM tracks JOIN tracktypes ON tracktypes.trackTypeId=? AND tracks.trackTypeId=? AND tracks.trackid=?', array($means, $means, $meansDetail));
		if ($query->num_rows() == 0) {
			throw new Exception("Can't retrieve the track name from database: $means/$meansDetail");
		}
		$row = $query->row();
		return $row;
	}

	/**
	 * Format numeric travel time to a human readable one.
	 * @param float $time the travel time in hour.
	 */
	public function formatTravelTime($time) {
		if (is_null($time)) {
			return null;
		} elseif ($time > 1) {
			return round($time) . ' ' . $this->lang->line('hours');
		} else {
			return 5 * ceil($time * 60 / 5) . ' ' . $this->lang->line('minutes');
		}
	}

	/**
	 * Format a number into distance
	 * @param float $distance The distance
	 */
	function formatDistance($distance) {
		if (!is_numeric($distance)) {
			throw new Exception("Distance is not a floating number: $distance");
		}

		if ($distance < 1) {
			// Less than 1 km, show in meter
			return floor($distance * 1000) . ' ' . $this->lang->line('meter');
		} else {
			// More than 1 km, show in km
			$fdist = floor($distance);
			return $fdist . $this->lang->line('decimal-separator') . floor(($distance - $fdist) * 10) . ' ' . $this->lang->line('kilometer');
		}
	}

}