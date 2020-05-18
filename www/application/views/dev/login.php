<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html class="no-js" lang="en">
<head>
	<title>Login | KIRI Developers</title>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="author" content="Project Kiri (KIRI)" />
	<meta name="google-site-verification" content="9AtqvB-LWohGnboiTyhtZUXAEcOql9B-8lDjo_wcUew" />
	<link rel="stylesheet" href="/ext/bootstrap/css/bootstrap.min.css" />
	<link rel="icon" href="/images/favicon.ico" type="image/x-icon">
	<script src="/ext/bootstrap/js/vendor/modernizr.js"></script>
</head>
<body>
	<div class="container">
		<div class="row">
			<div class="col-lg-3">&nbsp;</div>
			<div class="col-lg-6">
				&nbsp;
				<?php $this->load->view('dev/template_flashmessage'); ?>
				<form action="/dev/auth" method="POST">
					<div class="form-group">
						<label for="email">E-mail:</label>
						<input class="form-control" type="email" name="email" id="email" required/>
					</div>
					<div class="form-group">
						<label for="password">Password:</label>
						<input class="form-control" type="password" name="password" id="password" required/>
					</div>
					<div class="form-group">
						<button type="submit" class="btn btn-primary btn-block"><?= $this->lang->line('Login') ?></button>
						<a href="/dev/register"><small><b>Register</b> to access developer options</small></a>
					</div>
				</form>
			</div>
			<div class="col-lg-3">&nbsp;</div>
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
