<?php

defined('BASEPATH') OR exit('No direct script access allowed');

$config['domain'] = 'http://localhost';
$config['google-clientid'] = '306988856488-pfc4b6e9nogigaifjldnmh2hlac7sgmh.apps.googleusercontent.com';
$config['google-clientsecret'] = 'IhJhqNU-FuyZNMbfsLnmULj1';
$config['google-redirecturi'] = $config['domain'] . '/auth/oauth2callback';

$config['email-config'] = Array(
    'protocol' => 'smtp',
    'smtp_host' => 'smtp.googlemail.com',
    'smtp_port' => 587,
    'smtp_user' => 'rootbluetape@gmail.com', //masukan email google 
    'smtp_pass' => 'Rootbluetape123', //password email  // di config bikin 2 AUTH 1 YANG SALAH
    'mailtype' => 'html',
    'charset' => 'iso-8859-1',
    'smtp_crypto'=> 'tls'
);