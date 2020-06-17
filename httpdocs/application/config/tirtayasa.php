<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['regions'] = array(
	'bdo' => array(
		'lat' => -6.91474,
		'lon' => 107.60981,
		'radius' => 17000,
		'zoom' => 12,
		'searchplace_regex' => ', *(bandung|bdg)$',
		'name' => 'Bandung'
	),
	'cgk' => array(
		'lat' => -6.21154,
		'lon' => 106.84517,
		'radius' => 15000,
		'zoom' => 11,
		'searchplace_regex' => ', *(jakarta|jkt)$',
		'name' => 'Jakarta'
	)
);

$config['languages'] = array(
	'en' => array(
		'file' => 'english',
		'name' => 'English'
	),
	'id' => array(
		'file' => 'indonesian',
		'name' => 'Bahasa Indonesia'
	)
);

$config['routing-alternatives'] = array(
	/* Normal */
	array(
		'mw' => 0.75,
		'wm' => 1,
		'pt' => 0.15
	),
	/* Prefer walking */
	array(
		'mw' => 1,
		'wm' => 0.75,
		'pt' => 0.15
	),
	/* Avoid transfers */
	array(
		'mw' => 0.75,
		'wm' => 1,
		'pt' => 0.45
	),
);

$config['searchplace-maxresult'] = 10;
$config['speed-walk'] = 5; // km/h
$config['url-geocode'] = 'https://maps.googleapis.com/maps/api/geocode/json';
$config['url-searchplace'] = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json';
