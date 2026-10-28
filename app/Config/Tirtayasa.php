<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Tirtayasa extends BaseConfig {

    public $regions = array(
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

    public $languages = array(
        'en' => [
            'name' => 'English'
        ],
        'id' => [
            'name' => 'Bahasa Indonesia'
        ]
    );

    public $routing_alternatives = array(
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

    public $searchplace_maxresult = 10;
    public $speed_walk = 5; // km/h
    public $url_geocode = 'https://maps.googleapis.com/maps/api/geocode/json';
    public $url_searchplace = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json';
}
