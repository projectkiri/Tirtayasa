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
		$this->load->config('tirtayasa');
		$this->lang->load('tirtayasa', $this->config->item('languages')[$locale]['file']);
	}

	public function index() {
		if ($this->session->has_userdata('email')) {
			$this->session->keep_flashdata('message');
			redirect('/dev/apikeys');
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

	public function logout() {
		$this->session->unset_userdata('email');
		$this->session->set_flashdata('message', 'You have logged out');
		redirect('/dev');
	}

	public function profile() {
		if ($this->session->has_userdata('email')) {
			$email = $this->session->userdata('email');
		} else {
			$this->session->set_flashdata('message', 'Please login');
			redirect('/dev');
			return;
		}
		try {
			if (is_null($this->input->post('post'))) {
				$query = $this->db->get_where('users', array('email' => $email));
				$row = $query->row();
                $row->fullname = $row->fullName; // different caps in db
                $this->load->view('dev/profile', $row);
            } else {
            	$this->db->where('email', $email);
            	$this->db->set('fullName', $this->input->post('fullname'));
            	$this->db->set('company', $this->input->post('company'));
            	if ($this->input->post('password') === '') {
            		$this->session->set_flashdata('message', 'Profile updated, except password (not set)');
            	} elseif ($this->input->post('password') !== $this->input->post('confirmpassword')) {
            		$this->session->set_flashdata('message', 'Profile updated, except password (does not match confirmation)');
            	} else {
            		$password_hash = password_hash($this->input->post('password'), PASSWORD_BCRYPT);
            		$this->db->set('password', $password_hash);
            		$this->session->set_flashdata('message', 'Profile and password updated');
            	}
            	$this->db->update('users');
            	redirect('/dev');
            }
        } catch (Exception $e) {
        	$this->session->set_flashdata('message', $e->getMessage());
        	redirect('/dev');
        }
    }

    public function register() {
    	try {
    		$this->session->unset_userdata('email');
    		if (is_null($this->input->post('email'))) {
    			$this->load->view('dev/register');
    		} else {
    			$password = $this->_generateRandom("abcdefghiklmnopqrstuvwxyz0123456789", 8);
    			$password_hash = password_hash($password, PASSWORD_BCRYPT);
                // Send email
    			$inputArray = array(
    				'fullname' => $this->input->post('fullname'),
    				'password' => $password
    			);
    			$this->load->model('Email_model');
    			$this->load->config('dev');
    			$this->Email_model->from($this->config->item('sender_email'), $this->config->item('sender_name'));
    			$this->Email_model->to($this->input->post('email'));
    			$this->Email_model->subject('KIRI API Registration');
    			$this->Email_model->message($this->load->view('dev/email_registration.html.php', $inputArray, TRUE));
    			$this->Email_model->set_alt_message($this->load->view('dev/email_registration.txt.php', $inputArray, TRUE));
    			$this->Email_model->send();

                // Input to database
    			$this->db->insert('users', array(
    				'email' => $this->input->post('email'),
    				'password' => $password_hash,
    				'privilegeRoute' => 0,
    				'privilegeApiUsage' => 1,
    				'fullName' => $this->input->post('fullname'),
    				'company' => $this->input->post('company')
    			));
    			$this->session->set_flashdata('message', 'Check your email for password!');
    			redirect('/dev');
    		}
    	} catch (Exception $e) {
    		$this->session->set_flashdata('message', $e->getMessage());
    		redirect('/dev');
    	}
    }

    public function apikeys($action = 'list') {
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
    				redirect('/dev/apikeys');
    			}
    			break;
    			case 'delete':
    			$verifier = $this->input->get('verifier');
    			$this->db->where('email', $email);
    			$this->db->where('verifier', $verifier);
    			$this->db->delete('apikeys');
    			$this->session->set_flashdata('message', 'Deleted API Key: ' . $verifier);
    			redirect('/dev/apikeys');
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
    				redirect('/dev/apikeys');
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
