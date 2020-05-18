<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html class="no-js" lang="en">
<head>
	<?php $this->load->view('dev/template_head') ?>
	<title>Add API Key | KIRI Developers</title>
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
	<script src="/ext/jquery/jquery.min.js"></script>
	<script src="/ext/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
