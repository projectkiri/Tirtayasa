<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->model('Api_model');
		$this->load->model('Cache_model');
		$this->load->config('tirtayasa');
		$this->load->config('credentials');
	}

	public function index()
	{
		try {
			$version = $this->Api_model->getInput('version', false);
			$version = is_null($version) ? 1 : $version;
			$apikey = $this->Api_model->getInput('apikey');
			$this->Api_model->checkApiKey($apikey);
			$mode = $this->Api_model->getInput('mode');

			switch ($mode) {
				case 'findroute':
					$this->_findroute($version, $apikey);
					break;
				case 'searchplace':
					$this->_searchplace($version, $apikey);
					break;
				case 'reporterror':
					throw new Exception('501 This mode is no longer supported.');
				case 'nearbytransports':
					$this->_nearbytransports($version, $apikey);
					break;
				default:
					throw new Exception('400 Mode not understood: ' . $mode);
			}
		} catch (Exception $e) {
			$message = $e->getMessage();
			$this->Logging_model->logError($message);
			if (preg_match('/^([1-5][0-9][0-9]) (.+)$/', $message, $matches) === 1) {
				$httpcode = $matches[1];
				$message = $matches[2];
			} else {
				$httpcode = '500';
			}
			$this->Api_model->outputJson(array(
				'status' => 'error',
				'message' => $message
			), $version >= 4 ? $httpcode : '200');
		}
	}

	public function _findroute($version, $apikey) {
		$start = $this->Api_model->getInput('start');
		$finish = $this->Api_model->getInput('finish');
		$locale = $this->Api_model->getInput('locale');

		$language = $this->config->item('languages')[$locale];
		if (is_null($language))	{
			throw new Exception("400 Locale not found: $locale");
		}
		$this->lang->load('tirtayasa', $language['file']);

		$presentation = $this->Api_model->getInput('presentation', false);
		if (is_null($presentation)) {
			$presentation = 'desktop';
		}

		// Retrieve from menjangan server.
		$results = array();
		if ($version >= 2) {
			$alternatives = $this->config->item('routing-alternatives');
			$count = $presentation === 'mobile' ? 1 : sizeof($alternatives);
			for ($i = 0; $i < $count; $i++) {
				$url = $this->config->item('url-menjangan') . "/?start=$start&finish=$finish";
				$url .= '&' . 'mw' . '=' . $alternatives[$i]['mw'];
				$url .= '&' . 'wm' . '=' . $alternatives[$i]['wm'];
				$url .= '&' . 'pt' . '=' . $alternatives[$i]['pt'];
				$result = file_get_contents($url);
				if ($result === FALSE) {
					throw new Exception("There's an error while reading the menjangan response.");
				}
				$results[$result] = true;
			}		
		} else {
			$result = file_get_contents($this->config->item('url-menjangan') . "/?start=$start&finish=$finish");
			if ($result === FALSE) {
				throw new Exception("There's an error while reading the menjangan response.");
			}
			$results[$result] = true;
		}

		foreach ($results as $result=>$dummy) {
			$travel_time = 0;
			$route_output = array();
			$steps = explode("\n", $result);
			foreach ($steps as $step) {
				$step = trim($step);
				if ($step === '') {
					// Could be the last line, ignore if empty.
					continue;
				}
				// Path is not found
				if ($step === 'none') {
					if (sizeof($results) === 1) {
						// There is not other alternative
						$route_output[] = array("none", "none", array($start, $finish), $this->lang->line('Route not found'));
						$travel_time = null;
						break;
					} else {
						// There is alternative, hence we just skip this step.
						continue 2;
					}
				}
				list($means, $means_detail, $route, $distance, $nearbyplaceids) = explode("/", $step);
				if (!isset($means) || !isset($route) || !isset($distance) || !isset($nearbyplaceids)) {
					throw new Exception("Incomplete response in this line: $step");
				}
				$points = explode(" ", $route);
				$from = $points[0];
				$to = $points[sizeof($points) - 1];
				// Replace keywords with real location, then construct the detailed path
				for ($i = 0, $size = sizeof($points); $i < $size; $i++) {
					if ($points[$i] === 'start') {
						$points[$i] = $start;
					}
					if ($points[$i] === 'finish') {
						$points[$i] = $finish;
					}
				}
		
				// Construct the human readable form of the walk
				$humanized_from = $this->Api_model->humanizePoint($from);
				$humanized_to = $this->Api_model->humanizePoint($to);
				// Convert whole path to human readable form
				if ($means === 'walk') {
					// Remove uneccessary information if not needed.
					if ($humanized_from === $humanized_to) {
						// When we're in mobile, skip this step (not really necessary)
						if ($presentation === 'mobile') {
							$humanreadable = null;
						} else {
							$humanreadable = $this->lang->line('Walk slightly at');
							$humanreadable = str_replace('%street', $humanized_from, $humanreadable);
							$humanreadable = str_replace('%distance', $this->Api_model->formatDistance($distance), $humanreadable);
						}
					} else {
						if ($presentation === 'mobile') {
							$humanized_from .= ' %fromicon';
							$humanized_to .= ' %toicon';
						}
						$humanreadable = $this->lang->line('Walk from to');
						$humanreadable = str_replace('%from', $humanized_from, $humanreadable);
						$humanreadable = str_replace('%to', $humanized_to, $humanreadable);
						$humanreadable = str_replace('%distance', $this->Api_model->formatDistance($distance), $humanreadable);
					}
					$travel_time += $distance / $this->config->item('speed-walk');
					$booking_url = null;
				} else {
					$trackDetail = $this->Api_model->getTrackDetails($means, $means_detail);

					// Construct the human readable form of the walk
					if ($presentation === 'mobile') {
						$humanized_from .= ' %fromicon';
						$humanized_to .= ' %toicon';
					}
					$humanreadable = $this->lang->line('Take public transport');
					$humanreadable = str_replace('%from', $humanized_from, $humanreadable);
					$humanreadable = str_replace('%to', $humanized_to, $humanreadable);
					$humanreadable = str_replace('%distance', $this->Api_model->formatDistance($distance), $humanreadable);
					$humanreadable = str_replace('%trackname', $trackDetail->trackName, $humanreadable);
					$humanreadable = str_replace('%tracktype', $trackDetail->trackTypeName, $humanreadable);
					
					$travel_time += $distance / intval($trackDetail->speed);
					if (!is_null($trackDetail->ticketURL) && !is_null($trackDetail->extraParameters)) {
						$booking_url = $trackDetail->ticketURL . $trackDetail->extraParameters;
					} else {
						$booking_url = null;
					}

					// compatibility patch for older 3rd party apps
					if ($means === 'bdo_angkot' && $version < 3) {
						$means = 'angkot';
					}
				}
				if (!is_null($humanreadable)) {
					$route_output[] = array($means, $means_detail, $points, $humanreadable, $booking_url);
				}
			}
			$routing_result['steps'] = $route_output;
			$routing_result['traveltime'] = $this->Api_model->formatTravelTime($travel_time);
			$routing_results[] = $routing_result;
		}
		
		//input log statistic
		$this->Logging_model->logStatistic($apikey, 'FINDROUTE', "$start/$finish/" . sizeof($results));
		
		if (!is_null($version) && $version >= 2) {
			$json_output = array(
					'status' => 'ok',
					'routingresults' => $routing_results
			);
		} else {
			$json_output = array(
					'status' => 'ok',
					'routingresult' => $routing_results[0]['steps'],
					'traveltime' => $routing_results[0]['traveltime']
			);
		}
		$this->Api_model->outputJson($json_output);
	}

	public function _searchplace($version, $apikey) {
		$querystring = $this->Api_model->getInput('querystring');
		$region = $this->Api_model->getInput('region', $version >= 2);
		$region = is_null($region) ? 'bdo' : $region;
		
		// Check if there is region modifier from the query string
		$regions = $this->config->item('regions');
		foreach ($regions as $key => $value) {
			if (preg_match('/' . $value['searchplace_regex'] . '/i', $querystring, $matches, PREG_OFFSET_CAPTURE)) {
				$region = $key;
				$querystring = substr($querystring, 0, $matches[0][1]);
				break;
			}
		}
		
		$querystring = urlencode($querystring);
		$cached_searchplace = $this->Cache_model->get('searchplace', "$region/$querystring");
		if (!is_null($cached_searchplace)) {
			$json_output = json_decode($cached_searchplace, true);
			$this->Logging_model->logStatistic("$apikey", "SEARCHPLACE",  "$querystring/cache");
		} else {
			$city_lat = $regions[$region]['lat'];
			$city_lon = $regions[$region]['lon'];
			$city_radius = $regions[$region]['radius'];
			$full_url = $this->config->item('url-searchplace') . '?key=' . $this->config->item('google-server-key') . "&input=$querystring&inputtype=textquery&locationbias=circle:$city_radius@$city_lat,$city_lon&fields=name,geometry";
			$result = file_get_contents($full_url);
			if ($result === FALSE) {
				throw new Exception("There's an error while reading the places response ($full_url).");
			}
		
			$json_result = json_decode($result, true);
			if ($json_result['status'] === 'OK' || $json_result['status'] === 'ZERO_RESULTS') {
				$search_result = array();
				if ($json_result['status'] === 'ZERO_RESULTS') {
					$this->Logging_model->logError("Place search not found: \"$querystring\"");
					$size = 0;
				} else {
					$size = min(sizeof($json_result['candidates']), $this->config->item('searchplace-maxresult'));
				}
				for ($i = 0; $i < $size; $i++) {
					$current_venue = $json_result['candidates'][$i];
					$search_result[$i]['placename'] = $current_venue['name'];
					$search_result[$i]['location'] = sprintf(
							'%.5lf,%.5lf',
							$current_venue['geometry']['location']['lat'],
							$current_venue['geometry']['location']['lng']
					);
				}
				$json_output = array(
					'status' => 'ok',
					'searchresult' => $search_result,
					'attributions' => isset($json_result['html_attributions'])?$json_result['html_attributions']:[]
				);
		
				//input log statistic
				$this->Logging_model->logStatistic("$apikey", "SEARCHPLACE",  "$querystring/$size");
				// Store to cache
				if ($size > 0) {
					$this->Cache_model->put('searchplace', "$region/$querystring", json_encode($json_output));
				}
			} else {
				throw new Exception('Place Search returned error: ' . $json_result['status'] . " (for this request: $full_url)");
			}
		}
		$this->Api_model->outputJson($json_output);
	}

	public function _nearbytransports($version, $apikey) {
		$start = $this->Api_model->getInput('start');
		if ($version >= 2) {
			$lines = explode("\n", file_get_contents($this->config->item('url-menjangan') . "/?start=$start"));
			$nearby_result = array();
			foreach ($lines as $line) {
				if (strlen(trim($line)) === 0) {
					continue;
				}
				list($trackTypeId, $trackId, $distance) = explode("/", $line);
				$trackDetail = $this->Api_model->getTrackDetails($trackTypeId, $trackId);
				$nearby_result[] = array(
					$trackTypeId,
					$trackId,
					$trackDetail->trackName,
					$distance
				);
			}
			usort($nearby_result, "_nearbytransports_result_compare");
			$this->Logging_model->logStatistic($apikey, "NEARBYTRANSPORTS", "$start/" . sizeof($nearby_result));		
			$json_output = array(
					'status' => 'ok',
					'nearbytransports' => $nearby_result
			);
			$this->Api_model->outputJson($json_output);
		} else {
			throw new Exception("400 Nearby transit is not supported in version 1. Use higher version");
		}
	}
}

/**
 * A sorting comparison function to be used in nearby transports
 * @param array $a an array, where index 3 is the distance
 * @param array $b an array, where index 3 is the distance
 * @return number as in usort() spec
 */
 function _nearbytransports_result_compare($a, $b) {
	if ($a[3] > $b[3]) {
		return +1;
	} else if ($a[3] < $b[3]) {
		return -1;
	} else {
		return 0;
	}
}
