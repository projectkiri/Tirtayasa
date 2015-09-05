<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migrate extends CI_Controller {
        public function index() {
        		$this->load->config('migration');
        		if ($this->config->item('migration_enabled') === TRUE) {
	                $this->load->library('migration');
	                set_time_limit(300);
	                if ($this->migration->latest() === FALSE) {
	                	show_error($this->migration->error_string());
	                } else {
	                	echo 'Migrate success!';
	                }
	            } else {
	            	show_404();
	            }
        }
}