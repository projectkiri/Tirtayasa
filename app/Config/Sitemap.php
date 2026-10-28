<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Sitemap extends BaseConfig
{
	public $sitemap = [
		'' => [
			'meta_title' => 'Example of specific meta title for homepage',
			'priority' => '1.0', // example only, optional. Remove if not used.
			'lastmod' => '2024-01-01', // example only, optional. Remove if not used.
		],
		'*' => [
			'meta_description' => 'Example of default meta description for all pages',
			'meta_image' => 'favicon.ico',
			'changefreq' => 'weekly' // example only, optional. Remove if not used.
		],
	];
}
