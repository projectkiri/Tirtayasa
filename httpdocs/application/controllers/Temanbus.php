<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Temanbus extends CI_Controller {

	private array $stops;

	public function __construct() {
		parent::__construct();
		$this->stops = [];
		foreach (scandir('./images/temanbus/360/') as $file) {
			$id = substr($file, 0, -4);
			if (str_ends_with($file, '.jpg')) {
				$stop = [
					'id' => $id,
					'name' => str_replace('-', ' ', $id),
					'author' => null,
					'area' => null
				];
				if (file_exists("./images/temanbus/360/$id.json")) {
					$meta = json_decode(file_get_contents("./images/temanbus/360/$id.json"));
					$stop['author'] = $meta->author;
					$stop['area'] = $meta->area;
				} else {
					throw new Exception("Incomplete data: " . $id);
				}
				$this->stops[$id] = $stop;
			}
		}
		// TODO cache
	}

	public function index() {
		$this->load->view('temanbus/main', ['stops' => $this->stops]);
	}

	public function threesixty($stopname = NULL) {
		if ($stopname === NULL || !array_key_exists($stopname, $this->stops)) {
			show_404();
			exit();
		}
		$this->load->view('temanbus/360', ['stop' => $this->stops[$stopname]]);
	}
}
