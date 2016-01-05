<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Dev extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->database();
        // Setup locale
        if (is_null($this->input->get('locale'))) {
            $locale = $this->_getValidatedLocale($this->input->cookie('locale'));
        } else {
            $locale = $this->_getValidatedLocale($this->input->get('locale'));
            $this->input->set_cookie('locale', $locale, time() + 3600 * 24 * 365);
        }
        $this->lang->load('tirtayasa', $this->config->item('languages')[$locale]['file']);
    }

    public function index() {
        if ($this->session->has_userdata('email')) {
            echo 'Logged in';
        } else {
            $this->load->view('dev/login');
        }
    }
    
    public function auth() {
        $this->load->helper('url');
        try {
            $email = $this->input->post('email');
            $password = $this->input->post('password');
            $query = $this->db->get_where('users', array('email' => $email));
            $row = $query->row();
            if (isset($row)) {
                $hash = $row->password;
                if (password_verify($password, $hash)) {
                    $this->session->set_flashdata('message', 'Login success');
                    redirect('/dev');
                } else {
                    throw new Exception($this->lang->line('Login failed'));
                }
            } else {
                throw new Exception($this->lang->line('Login failed'));
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('message', $e->getMessage());
            redirect('/dev');
        }
    }

    // TODO unify with Mainpage->_getValidatedLocale()
    private function _getValidatedLocale($locale) {
        if (is_null($locale) || !isset($this->config->item('languages')[$locale])) {
            $locale = 'en';
        }
        return $locale;
    }

}
