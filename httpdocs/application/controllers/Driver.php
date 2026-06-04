<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Driver extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->model('Driver_model');
		$this->load->library('session');
		$this->load->helper('url');
	}

	public function index() {
		$driver = $this->_requireLogin();
		if (!$driver) return;

		$data = array(
			'driver' => $driver,
			'trackLabel' => $this->Driver_model->getTrackName($driver->trackTypeId, $driver->trackId),
			'trips' => $this->Driver_model->getActiveTripsByTrack($driver->trackTypeId, $driver->trackId),
			'summary' => $this->Driver_model->getTripSummaryByTrack($driver->trackTypeId, $driver->trackId),
		);
		$this->load->view('driver/dashboard', $data);
	}

	public function login() {
		if ($this->session->userdata('driverId')) {
			redirect('/driver');
			return;
		}
		$this->load->view('driver/login');
	}

	public function auth() {
		$phone = $this->Driver_model->normalizePhone($this->input->post('phone'));
		$password = $this->input->post('password');

		$driver = $this->Driver_model->authenticate($phone, $password);
		if (!$driver) {
			$this->session->set_flashdata('error', 'Nomor HP atau password salah.');
			redirect('/driver/login');
			return;
		}
		if ($driver->status === 'pending') {
			$this->session->set_flashdata('error', 'Akun Anda masih menunggu verifikasi admin. Silakan coba lagi nanti.');
			redirect('/driver/login');
			return;
		}
		if ($driver->status === 'rejected') {
			$this->session->set_flashdata('error', 'Pendaftaran akun Anda ditolak. Hubungi admin untuk informasi lebih lanjut.');
			redirect('/driver/login');
			return;
		}
		$this->session->set_userdata('driverId', $driver->driverId);
		redirect('/driver');
	}

	public function register() {
		if ($this->session->userdata('driverId')) {
			redirect('/driver');
			return;
		}
		$tracks = $this->Driver_model->getTracks();
		$this->load->view('driver/register', array('tracks' => $tracks));
	}

	public function do_register() {
		$phone = $this->Driver_model->normalizePhone($this->input->post('phone'));
		$password = $this->input->post('password');
		$name = $this->input->post('name');
		$track = $this->input->post('track');
		$region = $this->input->post('region');

		if (empty($phone) || empty($password) || empty($name) || empty($track)) {
			$this->session->set_flashdata('error', 'Semua field harus diisi.');
			redirect('/driver/register');
			return;
		}

		if (!$this->Driver_model->isValidPhone($phone)) {
			$this->session->set_flashdata('error', 'Nomor HP tidak valid. Gunakan format 08xxxxxxxxxx.');
			redirect('/driver/register');
			return;
		}

		if (strlen($password) < 6) {
			$this->session->set_flashdata('error', 'Password minimal 6 karakter.');
			redirect('/driver/register');
			return;
		}

		if ($this->Driver_model->phoneExists($phone)) {
			$this->session->set_flashdata('error', 'Nomor HP sudah terdaftar.');
			redirect('/driver/register');
			return;
		}

		if (strpos($track, '|') === false) {
			$this->session->set_flashdata('error', 'Trayek tidak valid.');
			redirect('/driver/register');
			return;
		}
		list($trackTypeId, $trackId) = explode('|', $track);

		$this->Driver_model->register(array(
			'name' => $name,
			'phone' => $phone,
			'password' => $password,
			'trackTypeId' => $trackTypeId,
			'trackId' => $trackId,
			'region' => $region ?: 'bdo',
		));

		$this->session->set_flashdata('success', 'Registrasi berhasil! Akun Anda akan aktif setelah diverifikasi admin.');
		redirect('/driver/login');
	}

	public function logout() {
		$this->session->unset_userdata('driverId');
		redirect('/driver/login');
	}

	public function update_location() {
		header('Content-Type: application/json');
		$driver = $this->_requireLogin(true);
		if (!$driver) {
			echo json_encode(array('status' => 'error', 'message' => 'Not logged in'));
			return;
		}

		$lat = floatval($this->input->post('lat'));
		$lng = floatval($this->input->post('lng'));

		if ($lat == 0 || $lng == 0) {
			echo json_encode(array('status' => 'error', 'message' => 'Invalid coordinates'));
			return;
		}

		// Update driver position
		$this->Driver_model->updateDriverLocation($driver->driverId, $lat, $lng);

		// Check proximity and auto-update trip statuses, then return the live list.
		$this->Driver_model->checkProximityAndUpdate($driver, $lat, $lng);
		echo json_encode($this->_tripsResponse($driver));
	}

	// Manually update a trip's status (driver-confirmed boarding / drop-off / cancel).
	// Fallback for when GPS proximity isn't accurate enough to auto-update.
	public function update_trip_status() {
		header('Content-Type: application/json');
		$driver = $this->_requireLogin(true);
		if (!$driver) {
			echo json_encode(array('status' => 'error', 'message' => 'Not logged in'));
			return;
		}

		$tripId = intval($this->input->post('tripId'));
		$newStatus = $this->input->post('newStatus');

		if (!$tripId || !in_array($newStatus, array('onboard', 'completed', 'cancelled'), true)) {
			echo json_encode(array('status' => 'error', 'message' => 'Invalid request'));
			return;
		}

		$ok = $this->Driver_model->updateTripStatus($tripId, $newStatus, $driver->trackTypeId, $driver->trackId);
		if (!$ok) {
			echo json_encode(array('status' => 'error', 'message' => 'Trip not found'));
			return;
		}

		echo json_encode($this->_tripsResponse($driver));
	}

	// Builds the standard JSON payload (live trip list + summary) for this driver's track.
	private function _tripsResponse($driver) {
		$trips = $this->Driver_model->getActiveTripsByTrack($driver->trackTypeId, $driver->trackId);
		$tripData = array();
		foreach ($trips as $trip) {
			$tripData[] = array(
				'tripId' => $trip->tripId,
				'passengerName' => $trip->passengerName,
				'passengerCount' => $trip->passengerCount,
				'pickupName' => $trip->pickupName,
				'dropoffName' => $trip->dropoffName,
				'distanceKm' => $trip->distanceKm,
				'price' => $this->Driver_model->formatPrice($trip->price),
				'status' => $trip->status,
				'createdAt' => date('H:i', strtotime($trip->createdAt)),
			);
		}
		$summary = $this->Driver_model->getTripSummaryByTrack($driver->trackTypeId, $driver->trackId);
		return array(
			'status' => 'ok',
			'trips' => $tripData,
			'summary' => array(
				'totalPassengers' => intval($summary->totalPassengers),
				'tripCount' => intval($summary->tripCount),
				'totalRevenue' => $this->Driver_model->formatPrice(intval($summary->totalRevenue)),
			),
		);
	}

	// $jsonResponse: when true, an unauthenticated/unapproved request returns false
	// (so the caller can emit JSON) instead of redirecting to the login page.
	private function _requireLogin($jsonResponse = false) {
		$driverId = $this->session->userdata('driverId');
		$driver = $driverId ? $this->Driver_model->getDriverById($driverId) : null;

		// Reject if not logged in, or if approval was revoked mid-session.
		if (!$driver || $driver->status !== 'approved') {
			if ($driver && $driver->status !== 'approved') {
				$this->session->unset_userdata('driverId');
			}
			if ($jsonResponse) {
				return false;
			}
			redirect('/driver/login');
			return false;
		}
		return $driver;
	}
}
