<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// Note: this class is made because CI's mailer is problematic for outlook
class PHPMailer_model extends CI_Model {

    private $mail;

    public function __construct() {
        parent::__construct();
        include_once 'application/libraries/ext/PHPMailer/PHPMailerAutoload.php';
        $this->config->load('credentials');
        $mailconfig = $this->config->item('email');

        $this->mail = new PHPMailer();
        $this->mail->isSMTP();
        $this->mail->Host = $mailconfig['smtp_host'];
        $this->mail->Port = $mailconfig['smtp_port'];
        $this->mail->SMTPAuth = true;
        $this->mail->SMTPSecure = $mailconfig['smtp_crypto'];
        $this->mail->Username = $mailconfig['smtp_user'];
        $this->mail->Password = $mailconfig['smtp_pass'];
        $this->mail->isHTML(true);
    }

    public function from($email, $name) {
        $this->mail->setFrom($email, $name);
    }

    public function to($email, $name) {
        $this->mail->addAddress($email, $name);
    }

    public function subject($subject) {
        $this->mail->Subject = $subject;
    }

    public function message($message) {
        $this->mail->Body = $message;
    }

    public function set_alt_message($message) {
        $this->mail->AltBody = $message;
    }

    public function send() {
        return $this->mail->send();
    }

    public function print_debugger() {
        return $this->mail->ErrorInfo;
    }

}
