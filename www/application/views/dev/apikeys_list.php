<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html class="no-js" lang="en">
<head>
	<?php $this->load->view('dev/template_head') ?>
	<title>API Keys | KIRI Developers</title>
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
		<script src="/ext/jquery/jquery.min.js"></script>
		<script src="/ext/bootstrap/js/bootstrap.min.js"></script>
	</body>
	</html>
