<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Temanbus extends CI_Controller {
	public function threesixty($stopname = NULL) {
		if ($stopname === NULL || !file_exists("./images/temanbus/360/$stopname.jpg")) {
			show_404();
			exit();
		}
		$this->load->view('temanbus/360', ['stopname' => $stopname]);
	}
}
