<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>Dashboard Driver | KIRI</title>
	<link rel="stylesheet" href="/ext/bootstrap/css/bootstrap.min.css" />
	<link rel="icon" href="/images/favicon.ico" type="image/x-icon">
	<style>
		.summary-card { border-left: 4px solid; }
		.summary-passengers { border-color: #007bff; }
		.summary-trips { border-color: #28a745; }
		.summary-revenue { border-color: #ffc107; }
		.pickup-badge { background: #28a745; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.85em; }
		.dropoff-badge { background: #dc3545; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.85em; }
		.status-active { background: #ffc107; color: #000; padding: 2px 10px; border-radius: 4px; font-size: 0.85em; }
		.status-onboard { background: #007bff; color: #fff; padding: 2px 10px; border-radius: 4px; font-size: 0.85em; }
		.status-completed { background: #28a745; color: #fff; padding: 2px 10px; border-radius: 4px; font-size: 0.85em; }
		#gps-status { font-size: 0.85em; }
	</style>
</head>
<body>
	<nav class="navbar navbar-dark bg-primary">
		<div class="container-fluid">
			<span class="navbar-brand">
				<img src="/images/kiri200.png" alt="KIRI" height="30" class="mr-2" />
				KIRI Driver
			</span>
			<div>
				<span id="gps-status" class="badge badge-secondary mr-2">GPS: Menunggu...</span>
				<span class="text-white mr-3"><?= htmlspecialchars($driver->name) ?></span>
				<a href="/driver/logout" class="btn btn-sm btn-outline-light">Keluar</a>
			</div>
		</div>
	</nav>

	<div class="container mt-4">
		<?php if ($this->session->flashdata('success')): ?>
			<div class="alert alert-success"><?= $this->session->flashdata('success') ?></div>
		<?php endif; ?>
		<?php if ($this->session->flashdata('error')): ?>
			<div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
		<?php endif; ?>

		<div class="mb-3">
			<h5>Trayek: <?= htmlspecialchars($trackLabel) ?></h5>
			<small class="text-muted">Status trip diperbarui otomatis berdasarkan lokasi GPS Anda. Saat Anda mendekati titik naik penumpang, status berubah menjadi "Dalam Perjalanan". Saat mendekati titik turun, trip otomatis selesai.</small>
		</div>

		<!-- Summary Cards -->
		<div class="row mb-4">
			<div class="col-md-4 mb-2">
				<div class="card summary-card summary-passengers">
					<div class="card-body">
						<h6 class="text-muted">Total Penumpang</h6>
						<h3 id="sum-passengers"><?= intval($summary->totalPassengers) ?></h3>
					</div>
				</div>
			</div>
			<div class="col-md-4 mb-2">
				<div class="card summary-card summary-trips">
					<div class="card-body">
						<h6 class="text-muted">Trip Aktif</h6>
						<h3 id="sum-trips"><?= intval($summary->tripCount) ?></h3>
					</div>
				</div>
			</div>
			<div class="col-md-4 mb-2">
				<div class="card summary-card summary-revenue">
					<div class="card-body">
						<h6 class="text-muted">Total Pendapatan</h6>
						<h3 id="sum-revenue"><?= $this->Driver_model->formatPrice(intval($summary->totalRevenue)) ?></h3>
					</div>
				</div>
			</div>
		</div>

		<!-- Passenger List -->
		<div id="trip-list">
			<?php if (empty($trips)): ?>
				<div class="alert alert-info" id="empty-alert">Belum ada penumpang aktif untuk trayek Anda.</div>
			<?php else: ?>
				<div class="table-responsive">
					<table class="table table-striped">
						<thead class="thead-dark">
							<tr>
								<th>#</th>
								<th>Nama Penumpang</th>
								<th>Jumlah</th>
								<th>Naik</th>
								<th>Turun</th>
								<th>Jarak</th>
								<th>Harga</th>
								<th>Waktu</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody id="trip-tbody">
							<?php $no = 1; foreach ($trips as $trip): ?>
								<tr>
									<td><?= $no++ ?></td>
									<td><strong><?= htmlspecialchars($trip->passengerName) ?></strong></td>
									<td><?= $trip->passengerCount ?> org</td>
									<td><span class="pickup-badge">Naik</span> <?= htmlspecialchars($trip->pickupName) ?></td>
									<td><span class="dropoff-badge">Turun</span> <?= htmlspecialchars($trip->dropoffName) ?></td>
									<td><?= $trip->distanceKm ?> km</td>
									<td><strong><?= $this->Driver_model->formatPrice($trip->price) ?></strong></td>
									<td><small><?= date('H:i', strtotime($trip->createdAt)) ?></small></td>
									<td>
										<?php if ($trip->status === 'active'): ?>
											<span class="status-active">Menunggu</span>
										<?php elseif ($trip->status === 'onboard'): ?>
											<span class="status-onboard">Dalam Perjalanan</span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<script src="/ext/jquery/jquery.min.js"></script>
	<script src="/ext/bootstrap/js/bootstrap.min.js"></script>
	<script>
		var gpsStatus = $('#gps-status');
		var watchId = null;

		function statusBadge(status) {
			if (status === 'active') return '<span class="status-active">Menunggu</span>';
			if (status === 'onboard') return '<span class="status-onboard">Dalam Perjalanan</span>';
			if (status === 'completed') return '<span class="status-completed">Selesai</span>';
			return status;
		}

		function renderTrips(data) {
			// Update summary
			$('#sum-passengers').text(data.summary.totalPassengers);
			$('#sum-trips').text(data.summary.tripCount);
			$('#sum-revenue').text(data.summary.totalRevenue);

			var trips = data.trips;
			var container = $('#trip-list');

			if (trips.length === 0) {
				container.html('<div class="alert alert-info">Belum ada penumpang aktif untuk trayek Anda.</div>');
				return;
			}

			var html = '<div class="table-responsive"><table class="table table-striped">';
			html += '<thead class="thead-dark"><tr><th>#</th><th>Nama Penumpang</th><th>Jumlah</th><th>Naik</th><th>Turun</th><th>Jarak</th><th>Harga</th><th>Waktu</th><th>Status</th></tr></thead><tbody>';
			$.each(trips, function(i, t) {
				html += '<tr>';
				html += '<td>' + (i + 1) + '</td>';
				html += '<td><strong>' + $('<span>').text(t.passengerName).html() + '</strong></td>';
				html += '<td>' + t.passengerCount + ' org</td>';
				html += '<td><span class="pickup-badge">Naik</span> ' + $('<span>').text(t.pickupName).html() + '</td>';
				html += '<td><span class="dropoff-badge">Turun</span> ' + $('<span>').text(t.dropoffName).html() + '</td>';
				html += '<td>' + t.distanceKm + ' km</td>';
				html += '<td><strong>' + t.price + '</strong></td>';
				html += '<td><small>' + t.createdAt + '</small></td>';
				html += '<td>' + statusBadge(t.status) + '</td>';
				html += '</tr>';
			});
			html += '</tbody></table></div>';
			container.html(html);
		}

		function sendLocation(lat, lng) {
			$.post('/driver/update_location', { lat: lat, lng: lng }, function(data) {
				if (data.status === 'ok') {
					renderTrips(data);
				}
			}, 'json');
		}

		// Start GPS tracking
		if (navigator.geolocation) {
			gpsStatus.removeClass('badge-secondary').addClass('badge-warning').text('GPS: Mencari...');
			watchId = navigator.geolocation.watchPosition(
				function(pos) {
					gpsStatus.removeClass('badge-warning badge-secondary').addClass('badge-success')
						.text('GPS: Aktif (' + pos.coords.latitude.toFixed(4) + ', ' + pos.coords.longitude.toFixed(4) + ')');
					sendLocation(pos.coords.latitude, pos.coords.longitude);
				},
				function(err) {
					gpsStatus.removeClass('badge-success badge-warning').addClass('badge-danger')
						.text('GPS: Error - ' + err.message);
				},
				{ enableHighAccuracy: true, maximumAge: 5000 }
			);

			// Also poll every 10 seconds in case watchPosition doesn't fire frequently
			setInterval(function() {
				navigator.geolocation.getCurrentPosition(function(pos) {
					sendLocation(pos.coords.latitude, pos.coords.longitude);
				});
			}, 10000);
		} else {
			gpsStatus.removeClass('badge-secondary').addClass('badge-danger').text('GPS: Tidak didukung');
			// Fallback: auto-refresh page every 30 seconds
			setTimeout(function() { location.reload(); }, 30000);
		}
	</script>
</body>
</html>
