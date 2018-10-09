<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// Note: this class is made because CI's mailer is problematic for outlook
class Email_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->config->load('credentials');
		$this->load->library('email', $this->config->item('email-config'));
    }

    public function from($email, $name) {
        $this->email->from($email, $name);
    }

    public function to($email) {
        $this->email->to($email);
    }

    public function subject($subject) {
        $this->email->subject($subject);
    }

    public function message($message) {
        $this->email->message($message);
    }

    public function set_alt_message($message) {
        $this->email->set_alt_message($message);
    }

    public function send() {
        if (!$this->email->send(false)) {
            throw new Exception("Email sending error: " . $this->email->print_debugger(['header']));
        }
	}
	
}
