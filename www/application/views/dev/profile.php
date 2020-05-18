<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html class="no-js" lang="en">
<head>
	<?php $this->load->view('dev/template_head') ?>
	<title>Profile | KIRI Developers</title>
</head>
<body>
	<?php $this->load->view('dev/template_topbar'); ?>
	<div class="container">
		<div class="row">
			<div class="col-lg-3">&nbsp;</div>
			<div class="col-lg-6">
				&nbsp;
				<?php $this->load->view('dev/template_flashmessage'); ?>
				<form action="/dev/profile" method="POST">
					<input type="hidden" name="post" value="true"/>
					<div class="form-group">
						<label for="email">E-mail:</label>
						<input class="form-control" type="email" name="email" id="email" disabled value="<?= htmlspecialchars($email) ?>"/>
					</div>
					<div class="form-group">
						<label for="password">Password:</label>
						<input class="form-control" type="password" name="password" id="password"/>
					</div>
					<div class="form-group">
						<label for="confirmpassword">Password again:</label>
						<input class="form-control" type="password" name="confirmpassword" id="confirmpassword"/>
					</div>
					<div class="form-group">
						<label for="fullname">Full Name:</label>
						<input class="form-control" type="text" name="fullname" id="fullname" required value="<?= htmlspecialchars($fullname) ?>"/>
					</div>
					<div class="form-group">
						<label for="company">Company (optional):</label>
						<input class="form-control" type="text" name="company" id="company" value="<?= htmlspecialchars($company) ?>"/>
					</div>
					<div class="form-group">
						<button type="submit" class="btn btn-primary btn-block">Update</button>
					</div>
				</form>
			</div>
			<div class="col-lg-3">&nbsp;</div>
		</div>
	</div>
	<script src="/ext/jquery/jquery.min.js"></script>
	<script src="/ext/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
