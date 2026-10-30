<?php

namespace App\Models;

use CodeIgniter\Model;

class ApiModel extends Model {
    /**
     * Retrieves input from GET or POST.
     */
    public function getInput($key, $mandatory = true) {
        $value = request()->getGet($key);
        if (is_null($value)) {
            $value = request()->getPost($key);
        }
        if (is_null($value) && $mandatory) {
            throw new \Exception("400 Parameter $key is required but not provided");
        }
        return $value;
    }

    public function checkApiKey($apikey) {
        $row = cache()->get('apikey' . '.' . $apikey);
        if (is_null($row)) {
            $query = $this->db->query('SELECT verifier, ipFilter FROM apikeys WHERE verifier = ?', array($apikey));
            if ($query->getNumRows() == 0) {
                throw new \Exception("401 API key is not recognized: $apikey");
            }
            $row = $query->getRow();
            cache()->save('apikey' . '.' . $apikey, $row, 60 * 60 * 24 * 30 * 3);
        }
        if (!is_null($row->ipFilter) && request()->getServer('REMOTE_ADDR') == $row->ipFilter) {
            throw new \Exception("401 IP address is not accepted for this API key.");
        }
    }

    public function outputJson($jsonArray, $statusCode = 200) {
        return response()->setStatusCode($statusCode)
            ->setContentType('application/json')
            ->setHeader('Cache-control', 'no-cache')
            ->setHeader('Pragma', 'no-cache')
            ->setBody(json_encode($jsonArray));
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
            return lang('Tirtayasa.your starting point');
        } else if ($location == 'finish') {
            return lang('Tirtayasa.your destination');
        } else {
            $cached_geocode = cache()->get('geocoding' . '.' . $location);
            if (!is_null($cached_geocode)) {
                return $cached_geocode;
            } else {
                $full_url = config('Tirtayasa')->url_geocode . '?key=' . config('Credentials')->google_server_key . '&latlng=' . urlencode($location) . '&sensor=false';
                $result = file_get_contents($full_url);
                if ($result == FALSE) {
                    throw new \Exception("There's an error while reading the geocoding response from $full_url.");
                }
                $json_response = json_decode($result, true);
                if ($json_response == NULL) {
                    throw new \Exception("Unable to retrieve JSON response from Google geocoding service.");
                }
                if ($json_response['status'] == 'OK') {
                    $bestguess = $location;
                    for ($i = 0; $i < count($json_response['results']); $i++) {
                        foreach ($json_response['results'][0]['address_components'] as $component) {
                            if (in_array('transit_station', $component['types']) || in_array('route', $component['types'])) {
                                cache()->save('geocoding' . '.' . $location, $component['long_name'], 60 * 60 * 24 * 30 * 3);
                                return $component['long_name'];
                            }
                            $bestguess = $component['long_name'];
                        }
                    }
                    log_message('error', "Warning: can't find street name, use best guess $bestguess for $location.");
                    cache()->save('geocoding' . '.' . $location, $bestguess, 60 * 60 * 24 * 30 * 3);
                    return $bestguess;
                } else if ($json_response['status'] == 'ZERO_RESULTS') {
                    // If not found, return the coordinate.
                    log_message('error', "Warning: can't find coordinate for $location.");
                    return $location;
                } else {
                    throw new \Exception("Problem while geocoding from Google reverse geocoding: " . $result);
                }
            }
        }
    }

    public function getTrackDetails($means, $meansDetail) {
        $query = $this->db->query('SELECT tracks.trackname AS trackName, tracktypes.name AS trackTypeName, tracktypes.url as ticketURL, tracks.extraParameters AS extraParameters, tracks.internalInfo AS internalInfo, tracktypes.speed AS speed FROM tracks JOIN tracktypes ON tracktypes.trackTypeId=? AND tracks.trackTypeId=? AND tracks.trackid=?', array($means, $means, $meansDetail));
        if ($query->getNumRows() == 0) {
            throw new \Exception("Can't retrieve the track name from database: $means/$meansDetail");
        }
        $row = $query->getRow();
        return $row;
    }

    /**
     * Format numeric travel time to a human readable one.
     * @param float|null $time the travel time in hour.
     */
    public function formatTravelTime($time) {
        if (is_null($time)) {
            return null;
        } elseif ($time > 1) {
            return round($time) . ' ' . lang('Tirtayasa.hours');
        } else {
            return 5 * ceil($time * 60 / 5) . ' ' . lang('Tirtayasa.minutes');
        }
    }

    /**
     * Format a number into distance
     * @param float $distance The distance
     */
    function formatDistance($distance) {
        if ($distance < 1) {
            // Less than 1 km, show in meter
            return floor($distance * 1000) . ' ' . lang('Tirtayasa.meter');
        } else {
            // More than 1 km, show in km
            $fdist = floor($distance);
            return $fdist . lang('Tirtayasa.decimal-separator') . floor(($distance - $fdist) * 10) . ' ' . lang('Tirtayasa.kilometer');
        }
    }

}
