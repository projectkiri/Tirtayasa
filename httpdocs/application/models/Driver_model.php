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
		// New drivers start unverified; an admin approves them via /dev/drivers.
		$data['status'] = 'pending';
		return $this->db->insert('drivers', $data);
	}

	public function getDriverById($driverId) {
		$query = $this->db->get_where('drivers', array('driverId' => $driverId));
		return $query->row();
	}

	public function phoneExists($phone) {
		return $this->db->get_where('drivers', array('phone' => $phone))->num_rows() > 0;
	}

	// Normalize an Indonesian phone number to a canonical 62xxxxxxxxxx form so the
	// same number can't be registered twice in different notations (08.., +62.., 62..).
	public function normalizePhone($phone) {
		$digits = preg_replace('/[^0-9]/', '', $phone);
		if (substr($digits, 0, 2) === '62') {
			// already in country-code form
		} else if (substr($digits, 0, 1) === '0') {
			$digits = '62' . substr($digits, 1);
		} else if ($digits !== '') {
			$digits = '62' . $digits;
		}
		return $digits;
	}

	// Basic sanity check: 62 + (8..) national number, total 10-15 digits.
	public function isValidPhone($normalizedPhone) {
		return preg_match('/^62[0-9]{8,13}$/', $normalizedPhone) === 1;
	}

	// --- Driver Location ---

	public function updateDriverLocation($driverId, $lat, $lng) {
		$this->db->where('driverId', $driverId);
		return $this->db->update('drivers', array('currentLat' => $lat, 'currentLng' => $lng));
	}

	// Check proximity and auto-update trip statuses. Returns updated trips.
	// This is a convenience fallback only; the driver can always set status manually
	// (see updateTripStatus) in case GPS accuracy isn't good enough to trigger it.
	public function checkProximityAndUpdate($driver, $lat, $lng) {
		$this->load->config('tirtayasa');
		$pickupRadius = $this->config->item('driver_pickup_radius_km');
		$dropoffRadius = $this->config->item('driver_dropoff_radius_km');

		// Get active + onboard trips for this track
		$this->db->where('trackTypeId', $driver->trackTypeId);
		$this->db->where('trackId', $driver->trackId);
		$this->db->where_in('status', array('active', 'onboard'));
		$trips = $this->db->get('trips')->result();

		foreach ($trips as $trip) {
			if ($trip->status === 'active') {
				$distToPickup = $this->calculateDistance($lat, $lng, $trip->pickupLat, $trip->pickupLng);
				if ($distToPickup <= $pickupRadius) {
					$this->db->where('tripId', $trip->tripId);
					$this->db->update('trips', array('status' => 'onboard'));
					$trip->status = 'onboard';
				}
			} else if ($trip->status === 'onboard') {
				$distToDropoff = $this->calculateDistance($lat, $lng, $trip->dropoffLat, $trip->dropoffLng);
				if ($distToDropoff <= $dropoffRadius) {
					$this->db->where('tripId', $trip->tripId);
					$this->db->update('trips', array('status' => 'completed'));
					$trip->status = 'completed';
				}
			}
		}

		return $trips;
	}

	// Manually move a trip to a new status. Scoped to the driver's track so a driver
	// can only touch trips on their own trayek. Returns true on success.
	public function updateTripStatus($tripId, $newStatus, $trackTypeId, $trackId) {
		$allowed = array('onboard', 'completed', 'cancelled');
		if (!in_array($newStatus, $allowed, true)) {
			return false;
		}
		$this->db->where('tripId', $tripId);
		$this->db->where('trackTypeId', $trackTypeId);
		$this->db->where('trackId', $trackId);
		$this->db->update('trips', array('status' => $newStatus));
		return $this->db->affected_rows() > 0;
	}

	// --- Admin verification ---

	public function getDriversByStatus($status = null) {
		$this->db->select('drivers.driverId, drivers.name, drivers.phone, drivers.region, drivers.status, drivers.createdAt, drivers.verifiedAt, drivers.trackTypeId, drivers.trackId, tracks.trackName, tracktypes.name as trackTypeName');
		$this->db->join('tracks', 'tracks.trackTypeId = drivers.trackTypeId AND tracks.trackId = drivers.trackId', 'left');
		$this->db->join('tracktypes', 'tracktypes.trackTypeId = drivers.trackTypeId', 'left');
		if ($status !== null) {
			$this->db->where('drivers.status', $status);
		}
		$this->db->order_by("FIELD(drivers.status,'pending','approved','rejected')", '', false);
		$this->db->order_by('drivers.createdAt', 'DESC');
		return $this->db->get('drivers')->result();
	}

	public function setDriverStatus($driverId, $status) {
		if (!in_array($status, array('pending', 'approved', 'rejected'), true)) {
			return false;
		}
		$this->db->where('driverId', $driverId);
		$this->db->update('drivers', array(
			'status' => $status,
			'verifiedAt' => $status === 'approved' ? date('Y-m-d H:i:s') : null,
		));
		return $this->db->affected_rows() >= 0;
	}

	// --- Trips (by track, not by driver) ---

	public function getActiveTripsByTrack($trackTypeId, $trackId) {
		$this->db->where('trackTypeId', $trackTypeId);
		$this->db->where('trackId', $trackId);
		$this->db->where_in('status', array('active', 'onboard'));
		$this->db->order_by('createdAt', 'DESC');
		return $this->db->get('trips')->result();
	}

	// Returns a combined summary for a track:
	//   - tripCount: live trips currently in progress (active + onboard) -> "Trip Aktif"
	//   - totalPassengers / totalRevenue: accumulated over trips COMPLETED today, so a
	//     driver's earnings keep adding up as trips finish instead of resetting to 0.
	//     Scoped to today (DATE(createdAt) = CURDATE()) so the totals don't grow unbounded.
	public function getTripSummaryByTrack($trackTypeId, $trackId) {
		// Live trips (active + onboard) for the "Trip Aktif" count.
		$this->db->select('COUNT(*) as tripCount');
		$this->db->where('trackTypeId', $trackTypeId);
		$this->db->where('trackId', $trackId);
		$this->db->where_in('status', array('active', 'onboard'));
		$live = $this->db->get('trips')->row();

		// Trips completed today for the accumulated passenger/revenue totals.
		$this->db->select('COUNT(*) as completedCount, COALESCE(SUM(passengerCount),0) as totalPassengers, COALESCE(SUM(price),0) as totalRevenue');
		$this->db->where('trackTypeId', $trackTypeId);
		$this->db->where('trackId', $trackId);
		$this->db->where('status', 'completed');
		$this->db->where('DATE(createdAt) = CURDATE()', null, false);
		$completed = $this->db->get('trips')->row();

		return (object) array(
			'tripCount' => intval($live->tripCount),
			'completedCount' => intval($completed->completedCount),
			'totalPassengers' => intval($completed->totalPassengers),
			'totalRevenue' => intval($completed->totalRevenue),
		);
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
