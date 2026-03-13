<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>Daftar Driver | KIRI</title>
	<link rel="stylesheet" href="/ext/bootstrap/css/bootstrap.min.css" />
	<link rel="icon" href="/images/favicon.ico" type="image/x-icon">
	<style>
		body { background: #f0f2f5; }
		.register-card { max-width: 500px; margin: 40px auto; }
	</style>
</head>
<body>
	<div class="container">
		<div class="register-card">
			<div class="text-center mb-4">
				<img src="/images/kiri200.png" alt="KIRI" height="80" />
				<h4 class="mt-2">Daftar Driver KIRI</h4>
			</div>
			<?php if ($this->session->flashdata('error')): ?>
				<div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
			<?php endif; ?>
			<div class="card">
				<div class="card-body">
					<form method="POST" action="/driver/do_register">
						<div class="form-group mb-3">
							<label for="name">Nama Lengkap</label>
							<input type="text" class="form-control" id="name" name="name" required />
						</div>
						<div class="form-group mb-3">
							<label for="phone">Nomor HP</label>
							<input type="text" class="form-control" id="phone" name="phone" placeholder="08xxxxxxxxxx" required />
						</div>
						<div class="form-group mb-3">
							<label for="password">Password</label>
							<input type="password" class="form-control" id="password" name="password" required />
						</div>
						<div class="form-group mb-3">
							<label for="region">Wilayah</label>
							<select class="form-control" id="region" name="region">
								<option value="bdo">Bandung</option>
								<option value="cgk">Jakarta</option>
							</select>
						</div>
						<div class="form-group mb-3">
							<label for="track">Trayek</label>
							<select class="form-control" id="track" name="track" required>
								<option value="">-- Pilih Trayek --</option>
								<?php foreach ($tracks as $t): ?>
									<option value="<?= htmlspecialchars($t->trackTypeId) ?>|<?= htmlspecialchars($t->trackId) ?>">
										<?= htmlspecialchars($t->trackTypeName) ?> - <?= htmlspecialchars($t->trackName) ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						<button type="submit" class="btn btn-primary btn-block w-100">Daftar</button>
					</form>
				</div>
			</div>
			<p class="text-center mt-3">
				Sudah punya akun? <a href="/driver/login">Login di sini</a>
			</p>
		</div>
	</div>
</body>
</html>
