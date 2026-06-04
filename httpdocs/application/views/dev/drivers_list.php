<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html class="no-js" lang="en">
<head>
	<?php $this->load->view('dev/template_head') ?>
	<title>Drivers | KIRI Developers</title>
</head>
<body>
	<?php $this->load->view('dev/template_topbar'); ?>
	&nbsp;
	<div class="container">
		<div class="row">
			<div class="col">
				<?php $this->load->view('dev/template_flashmessage'); ?>
				<h4>Driver Verification</h4>
				<p class="text-muted">Approve drivers after verifying their phone number out of band (e.g. a call). Only approved drivers can log in.</p>
				<table class="table table-striped">
					<thead class="thead-dark">
						<tr>
							<th>Name</th>
							<th>Phone</th>
							<th>Region</th>
							<th>Trayek</th>
							<th>Registered</th>
							<th>Status</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($drivers as $d): ?>
							<tr>
								<td><?= htmlspecialchars($d->name) ?></td>
								<td><?= htmlspecialchars($d->phone) ?></td>
								<td><?= htmlspecialchars($d->region) ?></td>
								<td><?= htmlspecialchars(($d->trackTypeName ? $d->trackTypeName . ' - ' : '') . $d->trackName) ?></td>
								<td><small><?= htmlspecialchars($d->createdAt) ?></small></td>
								<td>
									<?php if ($d->status === 'approved'): ?>
										<span class="badge badge-success">approved</span>
									<?php elseif ($d->status === 'pending'): ?>
										<span class="badge badge-warning">pending</span>
									<?php else: ?>
										<span class="badge badge-danger">rejected</span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ($d->status !== 'approved'): ?>
										<a class="btn btn-sm btn-success" href="/dev/drivers/approve?driverId=<?= $d->driverId ?>">Approve</a>
									<?php endif; ?>
									<?php if ($d->status !== 'rejected'): ?>
										<a class="btn btn-sm btn-outline-danger" href="/dev/drivers/reject?driverId=<?= $d->driverId ?>">Reject</a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
						<?php if (empty($drivers)): ?>
							<tr><td colspan="7" class="text-center text-muted">No drivers registered yet.</td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<script src="/ext/jquery/jquery.min.js"></script>
	<script src="/ext/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
