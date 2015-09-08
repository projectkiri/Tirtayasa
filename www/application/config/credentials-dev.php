<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['google-server-key'] = 'get-from-google-developer-console';
$config['scheduled-secret'] = 'match-with-webjobs';

// E-mail
$config['email']['protocol'] = 'smtp';
$config['email']['smtp_host'] = 'your-email-server';
$config['email']['smtp_port'] = 587;
$config['email']['smtp_crypto'] = 'tls';
$config['email']['smtp_user'] = 'your-email-address@kiri.travel';
$config['email']['smtp_pass'] = 'your-password';