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
	<style>
		html, body, #panorama {
			margin: 0;
		}
	</style>
</head>
<body>
	<img alt="Peta Transportasi Bandung Raya" src="<?= base_url("/images/temanbus/Peta-Transportasi-Bandung-Raya.png") ?>" usemap="#mapmap">
	<map name="mapmap">
<?php foreach ($stops as $id => $stop): ?>
	<area shape="<?= $stop['area']->shape ?>" coords="<?= $stop['area']->coords ?>" alt="<?= $stop['name'] ?>" href="<?= site_url('/temanbus/threesixty/' . $id) ?>">
<?php endforeach; ?>
	</map>
</body>
</html>
