<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Driver_model extends CI_Model {

	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	// --- Driver Auth ---

	public function authenticate($phone, $password) {
		$query = $this->db->get_where('drivers', array('phone' => $phone));
		$driver = $query->row();
		if ($driver && password_verify($password, $driver->password)) {
			return $driver;
		}
		return false;
	}

	public function register($data) {
		$data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
		return $this->db->insert('drivers', $data);
	}

	public function getDriverById($driverId) {
		$query = $this->db->get_where('drivers', array('driverId' => $driverId));
		return $query->row();
	}

	public function phoneExists($phone) {
		return $this->db->get_where('drivers', array('phone' => $phone))->num_rows() > 0;
	}

	// --- Driver Location ---

	public function updateDriverLocation($driverId, $lat, $lng) {
		$this->db->where('driverId', $driverId);
		return $this->db->update('drivers', array('currentLat' => $lat, 'currentLng' => $lng));
	}

	// Check proximity and auto-update trip statuses. Returns updated trips.
	public function checkProximityAndUpdate($driver, $lat, $lng) {
		$proximityKm = 0.2; // 200 meters

		// Get active + onboard trips for this track
		$this->db->where('trackTypeId', $driver->trackTypeId);
		$this->db->where('trackId', $driver->trackId);
		$this->db->where_in('status', array('active', 'onboard'));
		$trips = $this->db->get('trips')->result();

		foreach ($trips as $trip) {
			if ($trip->status === 'active') {
				$distToPickup = $this->calculateDistance($lat, $lng, $trip->pickupLat, $trip->pickupLng);
				if ($distToPickup <= $proximityKm) {
					$this->db->where('tripId', $trip->tripId);
					$this->db->update('trips', array('status' => 'onboard'));
					$trip->status = 'onboard';
				}
			} else if ($trip->status === 'onboard') {
				$distToDropoff = $this->calculateDistance($lat, $lng, $trip->dropoffLat, $trip->dropoffLng);
				if ($distToDropoff <= $proximityKm) {
					$this->db->where('tripId', $trip->tripId);
					$this->db->update('trips', array('status' => 'completed'));
					$trip->status = 'completed';
				}
			}
		}

		return $trips;
	}

	// --- Trips (by track, not by driver) ---

	public function getActiveTripsByTrack($trackTypeId, $trackId) {
		$this->db->where('trackTypeId', $trackTypeId);
		$this->db->where('trackId', $trackId);
		$this->db->where_in('status', array('active', 'onboard'));
		$this->db->order_by('createdAt', 'DESC');
		return $this->db->get('trips')->result();
	}

	public function getTripSummaryByTrack($trackTypeId, $trackId) {
		$this->db->select('COUNT(*) as tripCount, COALESCE(SUM(passengerCount),0) as totalPassengers, COALESCE(SUM(price),0) as totalRevenue');
		$this->db->where('trackTypeId', $trackTypeId);
		$this->db->where('trackId', $trackId);
		$this->db->where_in('status', array('active', 'onboard'));
		return $this->db->get('trips')->row();
	}

	public function addTrip($data) {
		$data['distanceKm'] = $this->calculateDistance(
			$data['pickupLat'], $data['pickupLng'],
			$data['dropoffLat'], $data['dropoffLng']
		);
		$data['price'] = $this->calculatePrice($data['distanceKm'], $data['passengerCount']);
		$this->db->insert('trips', $data);
		return $this->db->insert_id();
	}

	// --- Track List ---

	public function getTracks() {
		$this->db->select('tracks.trackId, tracks.trackTypeId, tracks.trackName, tracktypes.name as trackTypeName');
		$this->db->join('tracktypes', 'tracktypes.trackTypeId = tracks.trackTypeId');
		$this->db->order_by('tracktypes.name', 'ASC');
		$this->db->order_by('tracks.trackName', 'ASC');
		return $this->db->get('tracks')->result();
	}

	public function getTrackName($trackTypeId, $trackId) {
		$this->db->select('tracks.trackName, tracktypes.name as trackTypeName');
		$this->db->join('tracktypes', 'tracktypes.trackTypeId = tracks.trackTypeId');
		$this->db->where('tracks.trackTypeId', $trackTypeId);
		$this->db->where('tracks.trackId', $trackId);
		$row = $this->db->get('tracks')->row();
		return $row ? $row->trackTypeName . ' - ' . $row->trackName : '';
	}

	// --- Price Calculation ---

	public function calculateDistance($lat1, $lng1, $lat2, $lng2) {
		$earthRadius = 6371;
		$dLat = deg2rad($lat2 - $lat1);
		$dLng = deg2rad($lng2 - $lng1);
		$a = sin($dLat / 2) * sin($dLat / 2) +
			cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
			sin($dLng / 2) * sin($dLng / 2);
		$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
		return round($earthRadius * $c, 2);
	}

	public function calculatePrice($distanceKm, $passengerCount = 1) {
		$this->load->config('tirtayasa');
		$baseFare = $this->config->item('driver_base_fare');
		$perKmRate = $this->config->item('driver_per_km_rate');
		$pricePerPerson = $baseFare + ceil($distanceKm) * $perKmRate;
		return $pricePerPerson * $passengerCount;
	}

	public function formatPrice($price) {
		return 'Rp ' . number_format($price, 0, ',', '.');
	}
}
