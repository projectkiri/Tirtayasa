<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->helper('url');
?><!DOCTYPE html>
<html class="no-js" lang="en">
<head>
	<meta charset="utf-8" />
	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-QXRGWXE3RZ"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());

		gtag('config', 'G-QXRGWXE3RZ');
	</script>
	<title>KIRI Temanbus</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="description" content="<?= $this->lang->line('meta-description') ?>" />
	<meta name="author" content="Project Kiri (KIRI)" />
	<link rel="icon" href="/images/favicon.ico" type="image/x-icon">
	<link rel="stylesheet" href="<?= base_url('/ext/pannellum/pannellum.css') ?>"/>
	<script type="text/javascript" src="<?= base_url('/ext/pannellum/pannellum.js') ?>"></script>
	<style>
		html, body, #panorama {
			width: 100%;
			height: 100%;
			margin: 0;
		}
	</style>
</head>
<body>
	<div id="panorama"></div>
	<script>
		pannellum.viewer('panorama', {
			"type": "equirectangular",
			"panorama": "<?= base_url('/images/temanbus/360/' . $stop['id'] . '.jpg') ?>",
			"autoLoad": true,
			"title": "<?= $stop['name'] ?>",
			"author": "<?= $stop['author'] ?>",
		});
	</script>
</body>
</html>
