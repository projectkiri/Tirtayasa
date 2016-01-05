<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Dev extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->database();
        $this->load->helper('url');
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
            redirect('/dev/apikeys/list');
        } else {
            $this->load->view('dev/login');
        }
    }

    public function auth() {
        try {
            $email = $this->input->post('email');
            $password = $this->input->post('password');
            $query = $this->db->get_where('users', array('email' => $email));
            $row = $query->row();
            if (isset($row)) {
                $hash = $row->password;
                if (password_verify($password, $hash)) {
                    $this->session->set_flashdata('message', 'Login success');
                    $this->session->set_userdata('email', $email);
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

    public function apikeys($action) {
        if ($this->session->has_userdata('email')) {
            $email = $this->session->userdata('email');
        } else {
            $this->session->set_flashdata('message', 'Please login');
            redirect('/dev');
            return;
        }
        try {
            switch ($action) {
                case 'add':
                    if (is_null($this->input->post('post'))) {
                        $this->load->view('dev/apikeys_add');
                    } else {
                        $verifier = $this->_generateRandom('01234456789ABCDEF', 16);
                        $this->db->insert('apikeys', array(
                            'verifier' => $verifier,
                            'email' => $email,
                            'description' => $this->input->post('description'),
                            'domainFilter' => $this->input->post('domainFilter')
                        ));
                        $this->session->set_flashdata('message', 'Added new API Key: ' . $verifier);
                        redirect('/dev/apikeys/list');
                    }
                    break;
                case 'delete':
                    $verifier = $this->input->get('verifier');
                    $this->db->where('email', $email);
                    $this->db->where('verifier', $verifier);
                    $this->db->delete('apikeys');
                    $this->session->set_flashdata('message', 'Deleted API Key: ' . $verifier);
                    redirect('/dev/apikeys/list');
                case 'edit':
                    if (is_null($this->input->post('verifier'))) {
                        $query = $this->db->get_where('apikeys', array(
                            'email' => $email,
                            'verifier' => $this->input->get('verifier')
                        ));
                        $row = $query->row();
                        $this->load->view('dev/apikeys_edit', $row);
                    } else {
                        $this->db->where('email', $email);
                        $this->db->where('verifier', $this->input->post('verifier'));
                        $this->db->update('apikeys', array(
                            'description' => $this->input->post('description'),
                            'domainFilter' => $this->input->post('domainFilter')
                        ));
                        $this->session->set_flashdata('message', 'Updated API Key: ' . $this->input->post('verifier'));
                        redirect('/dev/apikeys/list');
                    }
                    break;
                case 'list':
                    $query = $this->db->get_where('apikeys', array('email' => $email));
                    $rows = $query->result();
                    $this->load->view('dev/apikeys_list', array('rows' => $rows));
                    break;
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

//    private function _generatePassword() {
//        return _generateRandom("abcdefghiklmnopqrstuvwxyz0123456789", 8);
//    }

    /**
     * Generates a random string
     * @param string $chars available characters
     * @param int $length size of the string
     * @return string the generated string
     */
    private function _generateRandom($chars, $length) {
        $chars_size = strlen($chars);
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $chars[rand(0, $chars_size - 1)];
        }
        return $string;
    }

}
