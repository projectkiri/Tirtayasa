<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Mainpage::index');
$routes->get('/mainpage/js/(:segment)', 'Mainpage::js/$1');
$routes->get('/api', 'Api::index');
$routes->get('/dev', 'Dev::index');
$routes->post('/dev/auth', 'Dev::auth');
$routes->get('/dev/logout', 'Dev::logout');
$routes->get('/dev/profile', 'Dev::profile');
$routes->post('/dev/profile', 'Dev::profile');
$routes->get('/dev/register', 'Dev::register');
$routes->post('/dev/register', 'Dev::register');
$routes->get('/dev/apikeys', 'Dev::apikeys');
$routes->get('/dev/apikeys/(:segment)', 'Dev::apikeys/$1');
$routes->post('/dev/apikeys/(:segment)', 'Dev::apikeys/$1');
$routes->get('/scheduled/daily/(:segment)', 'Scheduled::daily/$1');
$routes->get('/scheduled/monthly/(:segment)', 'Scheduled::monthly/$1');
$routes->get('/migrate', 'Migrate::index');
