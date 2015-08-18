<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mainpage extends CI_Controller {
	public function index() {
		$this->load->config('tirtayasa');
		$locale = $this->_getValidatedLocale($this->input->get('locale'));
		$this->lang->load('tirtayasa', $this->config->item('languages')[$locale]['file']);
		$data = array(
			'regions' => $this->config->item('regions'),
			'languages' => $this->config->item('languages'),
			'locale' => $locale
		);
		$this->load->view('mainpage/main', $data);
	}

	public function js($name) {
		$locale = $this->_getValidatedLocale($this->input->get('locale'));
		$this->lang->load('tirtayasa', $this->config->item('languages')[$locale]['file']);
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

	private function _getValidatedLocale($locale) {
		if (is_null($locale) || !isset($this->config->item('languages')[$locale])) {
			$locale = 'en';
		}
		return $locale;		
	}
}
