<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html class="no-js" lang="en">
<head>
	<?php $this->load->view('dev/template_head') ?>
	<title>Login | KIRI Developers</title>
</head>
<body>
	<div class="container">
		<div class="row">
			<div class="col-lg-3">&nbsp;</div>
			<div class="col-lg-6">
				&nbsp;
				<?php $this->load->view('dev/template_flashmessage'); ?>
				<form action="/dev/register" method="POST">
					<div class="form-group">
						<label for="email">E-mail:</label>
						<input class="form-control" type="email" name="email" id="email" required/>
					</div>
					<div class="form-group">
						<label for="fullname">Full Name:</label>
						<input class="form-control" type="text" name="fullname" id="fullname" required/>
					</div>
					<div class="form-group">
						<label for="company">Company (optional):</label>
						<input class="form-control" type="text" name="company" id="company"/>
					</div>
					<div class="form-group">
						<button type="submit" class="btn btn-primary btn-block">Register</button>
						<small>Password will be generated and sent to email</small>
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
