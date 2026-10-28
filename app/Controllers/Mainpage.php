<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

class Mainpage extends BaseController {
    public function index() {
        helper('cookie');
        // Setup locale
        if (is_null($this->request->getGet('locale'))) {
            $locale = $this->_getValidatedLocale(get_cookie('locale'));
        } else {
            $locale = $this->_getValidatedLocale($this->request->getGet('locale'));
            set_cookie('locale', $locale, time() + 3600 * 24 * 365);
        }
        $this->request->setLocale($locale);
        
        // Setup region
        if (is_null($this->request->getGet('region'))) {
            $region = $this->_getValidatedRegion(get_cookie('region'));
        } else {
            $region = $this->_getValidatedRegion($this->request->getGet('region'));
            set_cookie('region', $region, time() + 3600 * 24 * 365);
        }

        // Setup start/finish autofill
        $endpoint = array(
            'start' => $this->request->getGet('start'),
            'finish' => $this->request->getGet('finish')
        );
        foreach (array('start', 'finish') as $endpoint) {
            $startfinish = $this->request->getGet($endpoint);
            if (!is_null($startfinish)) {
                if (preg_match('/^(.+)\\/(-?[0-9.]+,-?[0-9.]+)$/', $startfinish, $matches)) {
                    $textual_endpoint [$endpoint] = $matches[1];
                    $coordinate_endpoint [$endpoint] = $matches[2];
                } else {
                    $textual_endpoint [$endpoint] = $startfinish;
                    $coordinate_endpoint [$endpoint] = null;
                }
            } else {
                $textual_endpoint [$endpoint] = null;
                $coordinate_endpoint [$endpoint] = null;
            }
        }

        $data = array(
            'regions' => config('Tirtayasa')->regions,
            'region' => $region,
            'languages' => config('Tirtayasa')->languages,
            'locale' => $locale,
            'inputText' => array('start' => $textual_endpoint['start'], 'finish' => $textual_endpoint['finish']),
            'inputCoordinate' => array('start' => $coordinate_endpoint['start'], 'finish' => $coordinate_endpoint['finish']),
            'newhome_popup' => $this->request->getGet('utm_campaign') === 'newhome'
        );
        return view('mainpage/main', $data);
    }

    public function js($name) {
        $locale = $this->_getValidatedLocale($this->request->getGet('locale'));
        $this->request->setLocale($locale);
        switch ($name) {
            case 'main.js':
                $this->response->setContentType('text/javascript');
                return view('mainpage/main.js', array('locale' => $locale));
            case 'protocol.js':
                $this->response->setContentType('text/javascript');
                return view('mainpage/protocol.js');
            default:
                throw PageNotFoundException::forPageNotFound();
        }
    }

    private function _getValidatedLocale($locale) {
        if (is_null($locale) || !isset(config('Tirtayasa')->languages[$locale])) {
            $locale = 'en';
        }
        return $locale;     
    }

    private function _getValidatedRegion($region) {
        if (is_null($region) || !isset(config('Tirtayasa')->regions[$region])) {
            $region = 'bdo';
        }
        return $region;     
    }

}
