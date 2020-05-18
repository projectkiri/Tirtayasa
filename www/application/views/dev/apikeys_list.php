<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html class="no-js" lang="en">
<head>
	<title>API Keys | KIRI Developers</title>
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
				<table class="table table-striped">
					<thead class="thead-dark">
						<tr>
							<th>API Key</th>
							<th>Domain Filter</th>
							<th>Description</th>
							<th>Actions</th>
						</thead>
						<tbody>
							<?php foreach ($rows as $row): ?>
								<tr>
									<td><code><?= $row->verifier ?></code></td>
									<td><?= $row->domainFilter ?></td>
									<td><?= $row->description ?></td>
									<td><a class="btn btn-sm btn-secondary" href="/dev/apikeys/edit?verifier=<?= $row->verifier ?>">Edit</a>
										<a class="btn btn-sm btn-secondary" href="/dev/apikeys/delete?verifier=<?= $row->verifier ?>">Delete</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<a href="/dev/apikeys/add" class="btn btn-primary">Add</a>
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
