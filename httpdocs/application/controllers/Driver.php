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
		$phone = $this->input->post('phone');
		$password = $this->input->post('password');

		$driver = $this->Driver_model->authenticate($phone, $password);
		if ($driver) {
			$this->session->set_userdata('driverId', $driver->driverId);
			redirect('/driver');
		} else {
			$this->session->set_flashdata('error', 'Nomor HP atau password salah.');
			redirect('/driver/login');
		}
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
		$phone = $this->input->post('phone');
		$password = $this->input->post('password');
		$name = $this->input->post('name');
		$track = $this->input->post('track');
		$region = $this->input->post('region');

		if (empty($phone) || empty($password) || empty($name) || empty($track)) {
			$this->session->set_flashdata('error', 'Semua field harus diisi.');
			redirect('/driver/register');
			return;
		}

		if ($this->Driver_model->phoneExists($phone)) {
			$this->session->set_flashdata('error', 'Nomor HP sudah terdaftar.');
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

		$this->session->set_flashdata('success', 'Registrasi berhasil! Silakan login.');
		redirect('/driver/login');
	}

	public function logout() {
		$this->session->unset_userdata('driverId');
		redirect('/driver/login');
	}

	public function update_location() {
		$driver = $this->_requireLogin();
		if (!$driver) {
			header('Content-Type: application/json');
			echo json_encode(array('status' => 'error', 'message' => 'Not logged in'));
			return;
		}

		$lat = floatval($this->input->post('lat'));
		$lng = floatval($this->input->post('lng'));

		if ($lat == 0 || $lng == 0) {
			header('Content-Type: application/json');
			echo json_encode(array('status' => 'error', 'message' => 'Invalid coordinates'));
			return;
		}

		// Update driver position
		$this->Driver_model->updateDriverLocation($driver->driverId, $lat, $lng);

		// Check proximity and auto-update trip statuses
		$trips = $this->Driver_model->checkProximityAndUpdate($driver, $lat, $lng);

		// Return current trips for live dashboard update
		$tripData = array();
		foreach ($trips as $trip) {
			if ($trip->status === 'completed') continue;
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

		header('Content-Type: application/json');
		echo json_encode(array(
			'status' => 'ok',
			'trips' => $tripData,
			'summary' => array(
				'totalPassengers' => intval($summary->totalPassengers),
				'tripCount' => intval($summary->tripCount),
				'totalRevenue' => $this->Driver_model->formatPrice(intval($summary->totalRevenue)),
			),
		));
	}

	private function _requireLogin() {
		$driverId = $this->session->userdata('driverId');
		if (!$driverId) {
			redirect('/driver/login');
			return false;
		}
		return $this->Driver_model->getDriverById($driverId);
	}
}
