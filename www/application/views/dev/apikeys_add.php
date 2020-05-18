<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html class="no-js" lang="en">
<head>
	<title>Add API Key | KIRI Developers</title>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="author" content="Project Kiri (KIRI)" />
	<meta name="google-site-verification" content="9AtqvB-LWohGnboiTyhtZUXAEcOql9B-8lDjo_wcUew" />
	<link rel="stylesheet" href="/ext/bootstrap/css/bootstrap.min.css" />
	<link rel="icon" href="/images/favicon.ico" type="image/x-icon">
	<script src="/ext/bootstrap/js/vendor/modernizr.js"></script>
</head>
<body>
	<?php $this->load->view('dev/template_topbar'); ?>
	&nbsp;
	<div class="container">
		<div class="row">
			<div class="col">
				<?php $this->load->view('dev/template_flashmessage'); ?>
				<form action="/dev/apikeys/add" method="POST">
					<input type="hidden" name="post" value="true"/>
					<div class="form-group">
						<label for="verifier">API Key:</label>
						<input class="form-control" type="text" name="verifier" id="verifier" disabled value="To be generated"/>
					</div>
					<div class="form-group">
						<label for="domainFilter">Domain filter:</label>
						<input class="form-control" type="text" name="domainFilter" id="domainFilter" required value="*" size="64"/>
					</div>
					<div class="form-group">
						<label for="description">Description:</label>
						<input class="form-control" type="text" name="description" id="description" size="256"/>
					</div>
					<div class="form-group">					
						<input class="btn btn-primary" type="submit" value="Add"/>
						<a class="btn btn-secondary" href="/dev/apikeys/list">Cancel</a>
					</div>
				</form>
			</div>
		</div>
	</div>
	<script src="/ext/bootstrap/js/vendor/jquery.js"></script>
	<script src="/ext/bootstrap/js/vendor/fastclick.js"></script>
	<script src="/ext/bootstrap/js/bootstrap.min.js"></script>
	<script>
		(function (i, s, o, g, r, a, m) {
			i['GoogleAnalyticsObject'] = r;
			i[r] = i[r] || function () {
				(i[r].q = i[r].q || []).push(arguments)
			}, i[r].l = 1 * new Date();
			a = s.createElement(o),
			m = s.getElementsByTagName(o)[0];
			a.async = 1;
			a.src = g;
			m.parentNode.insertBefore(a, m)
		})(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');

		ga('create', 'UA-36656575-2', 'kiri.travel');
		ga('require', 'displayfeatures');
		ga('send', 'pageview');
	</script>
</body>
</html>
