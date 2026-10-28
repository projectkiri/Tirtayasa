<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Dev extends BaseController {

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        helper('cookie');
        // Setup locale
        if (is_null($this->request->getGet('locale'))) {
            $locale = $this->_getValidatedLocale(get_cookie('locale'));
        } else {
            $locale = $this->_getValidatedLocale($this->request->getGet('locale'));
            set_cookie('locale', $locale, time() + 3600 * 24 * 365);
        }
        $this->request->setLocale($locale);
    }

    public function index() {
        if (session()->has('email')) {
            session()->keepFlashdata('message');
            return redirect()->to('/dev/apikeys');
        } else {
            return view('dev/login');
        }
    }

    public function auth() {
        try {
            $email = $this->request->getPost('email');
            $password = $this->request->getPost('password');
            $query = $this->db->table('users')->where(array('email' => $email))->get();
            $row = $query->getRow();
            if (isset($row)) {
                $hash = $row->password;
                if (password_verify($password, $hash)) {
                    session()->setFlashdata('message', 'Login success');
                    session()->set('email', $email);
                    return redirect()->to('/dev');
                } else {
                    throw new \Exception(lang('Tirtayasa.Login failed'));
                }
            } else {
                throw new \Exception(lang('Tirtayasa.Login failed'));
            }
        } catch (\Exception $e) {
            session()->setFlashdata('message', $e->getMessage());
            return redirect()->to('/dev');
        }
    }

    public function logout() {
        session()->remove('email');
        session()->setFlashdata('message', 'You have logged out');
        return redirect()->to('/dev');
    }

    public function profile() {
        if (session()->has('email')) {
            $email = session()->get('email');
        } else {
            session()->setFlashdata('message', 'Please login');
            return redirect()->to('/dev');
        }
        try {
            if (is_null($this->request->getPost('post'))) {
                $query = $this->db->table('users')->where(array('email' => $email))->get();
                $row = $query->getRowArray();
                $row['fullname'] = $row['fullName']; // different caps in db
                return view('dev/profile', $row);
            } else {
                $builder = $this->db->table('users')
                    ->where('email', $email)
                    ->set('fullName', $this->request->getPost('fullname'))
                    ->set('company', $this->request->getPost('company'));
                if ($this->request->getPost('password') === '') {
                    session()->setFlashdata('message', 'Profile updated, except password (not set)');
                } elseif ($this->request->getPost('password') !== $this->request->getPost('confirmpassword')) {
                    session()->setFlashdata('message', 'Profile updated, except password (does not match confirmation)');
                } else {
                    $password_hash = password_hash($this->request->getPost('password'), PASSWORD_BCRYPT);
                    $builder->set('password', $password_hash);
                    session()->setFlashdata('message', 'Profile and password updated');
                }
                $builder->update();
                return redirect()->to('/dev');
            }
        } catch (\Exception $e) {
            session()->setFlashdata('message', $e->getMessage());
            return redirect()->to('/dev');
        }
    }

    public function register() {
        try {
            session()->remove('email');
            if (is_null($this->request->getPost('email'))) {
                return view('dev/register');
            } else {
                $password = $this->_generateRandom("abcdefghiklmnopqrstuvwxyz0123456789", 8);
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                // Send email
                $inputArray = array(
                    'fullname' => $this->request->getPost('fullname'),
                    'password' => $password
                );
                $email = service('email');
                $email->setTo($this->request->getPost('email'));
                $email->setSubject('KIRI API Registration');
                $email->setMessage(view('dev/email_registration.html.php', $inputArray));
                $email->setAltMessage(view('dev/email_registration.txt.php', $inputArray));
                if (!$email->send(false)) {
                    throw new \Exception("Email sending error: " . $email->printDebugger(['header']));
                }

                // Input to database
                $this->db->table('users')->insert(array(
                    'email' => $this->request->getPost('email'),
                    'password' => $password_hash,
                    'privilegeRoute' => 0,
                    'privilegeApiUsage' => 1,
                    'fullName' => $this->request->getPost('fullname'),
                    'company' => $this->request->getPost('company')
                ));
                session()->setFlashdata('message', 'Check your email for password!');
                return redirect()->to('/dev');
            }
        } catch (\Exception $e) {
            session()->setFlashdata('message', $e->getMessage());
            return redirect()->to('/dev');
        }
    }

    public function apikeys($action = 'list') {
        if (session()->has('email')) {
            $email = session()->get('email');
        } else {
            session()->setFlashdata('message', 'Please login');
            return redirect()->to('/dev');
        }
        try {
            switch ($action) {
                case 'add':
                if (is_null($this->request->getPost('post'))) {
                    return view('dev/apikeys_add');
                } else {
                    $verifier = $this->_generateRandom('01234456789ABCDEF', 16);
                    $this->db->table('apikeys')->insert(array(
                        'verifier' => $verifier,
                        'email' => $email,
                        'description' => $this->request->getPost('description'),
                        'domainFilter' => $this->request->getPost('domainFilter')
                    ));
                    session()->setFlashdata('message', 'Added new API Key: ' . $verifier);
                    return redirect()->to('/dev/apikeys');
                }
                case 'delete':
                $verifier = $this->request->getGet('verifier');
                $this->db->table('apikeys')
                    ->where('email', $email)
                    ->where('verifier', $verifier)
                    ->delete();
                session()->setFlashdata('message', 'Deleted API Key: ' . $verifier);
                return redirect()->to('/dev/apikeys');
                case 'edit':
                if (is_null($this->request->getPost('verifier'))) {
                    $query = $this->db->table('apikeys')
                        ->where(array(
                            'email' => $email,
                            'verifier' => $this->request->getGet('verifier')
                        ))->get();
                    $row = $query->getRowArray();
                    return view('dev/apikeys_edit', $row);
                } else {
                    $this->db->table('apikeys')
                        ->where('email', $email)
                        ->where('verifier', $this->request->getPost('verifier'))
                        ->update(array(
                            'description' => $this->request->getPost('description'),
                            'domainFilter' => $this->request->getPost('domainFilter')
                        ));
                    session()->setFlashdata('message', 'Updated API Key: ' . $this->request->getPost('verifier'));
                    return redirect()->to('/dev/apikeys');
                }
                case 'list':
                $query = $this->db->table('apikeys')->where(array('email' => $email))->get();
                $rows = $query->getResult();
                return view('dev/apikeys_list', array('rows' => $rows));
            }
        } catch (\Exception $e) {
            session()->setFlashdata('message', $e->getMessage());
            return redirect()->to('/dev');
        }
    }

    // TODO unify with Mainpage->_getValidatedLocale()
    private function _getValidatedLocale($locale) {
        if (is_null($locale) || !isset(config('Tirtayasa')->languages[$locale])) {
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
