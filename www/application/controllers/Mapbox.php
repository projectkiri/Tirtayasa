<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mapbox extends CI_Controller {
	public function pascal() {
		$this->load->view('mapbox/pascal');
	}
	public function indra() {
		$this->load->view('mapbox/indra');
	}
	public function jode() {
		$this->load->view('mapbox/jode');
	}
	public function william() {
		$this->load->view('mapbox/william');
	}
	public function yoga() {
		$this->load->view('mapbox/yoga');
	}
	public function kelvin() {
		$this->load->view('mapbox/kelvin');
	}
}
