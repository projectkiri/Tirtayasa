<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mainpage extends CI_Controller {
	public function index() {
		$this->load->config('tirtayasa');
		$this->lang->load('tirtayasa', 'english');
		$data = array(
			'regions' => $this->config->item('regions'),
			'languages' => $this->config->item('languages')
		);
		$this->load->view('mainpage/main', $data);
	}

	public function js($name) {
		switch ($name) {
			case 'main.js':
				$this->load->view('mainpage/main.js');
				break;
			case 'protocol.js':
				$this->load->view('mainpage/protocol.js');
				break;
			default:
				show_404();
		}
	}
}
