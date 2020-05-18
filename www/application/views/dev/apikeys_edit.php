<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html class="no-js" lang="en">
<head>
	<?php $this->load->view('dev/template_head') ?>
	<title>Edit API Key | KIRI Developers</title>
</head>
<body>
	<?php $this->load->view('dev/template_topbar'); ?>
	&nbsp;
	<div class="container">
		<div class="row">
			<div class="col">
				<?php $this->load->view('dev/template_flashmessage'); ?>
				<form action="/dev/apikeys/edit" method="POST">
					<div class="form-group">
						<label for="verifier">API Key:</label>
						<input class="form-control" type="text" name="verifier" id="verifier" readonly value="<?= htmlspecialchars($verifier) ?>"/>
					</div>
					<div class="form-group">
						<label for="domainFilter">Domain filter:</label>
						<input class="form-control" type="text" name="domainFilter" id="domainFilter" required value="<?= htmlspecialchars($domainFilter) ?>" size="64"/>
					</div>
					<div class="form-group">
						<label for="description">Description:</label>
						<input class="form-control" type="text" name="description" id="description" value="<?= htmlspecialchars($description) ?>" size="256"/>
					</div>
					<div class="form-group">
						<input class="btn btn-primary" type="submit" value="Update"/>
						<a class="btn btn-secondary" href="/dev/apikeys/list">Cancel</a>
					</div>
				</form>
			</div>
		</div>
	</div>
	<script src="/ext/jquery/jquery.min.js"></script>
	<script src="/ext/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
