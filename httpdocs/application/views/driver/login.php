<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>Login Driver | KIRI</title>
	<link rel="stylesheet" href="/ext/bootstrap/css/bootstrap.min.css" />
	<link rel="icon" href="/images/favicon.ico" type="image/x-icon">
	<style>
		body { background: #f0f2f5; }
		.login-card { max-width: 400px; margin: 80px auto; }
	</style>
</head>
<body>
	<div class="container">
		<div class="login-card">
			<div class="text-center mb-4">
				<img src="/images/kiri200.png" alt="KIRI" height="80" />
				<h4 class="mt-2">KIRI Driver</h4>
			</div>
			<?php if ($this->session->flashdata('error')): ?>
				<div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
			<?php endif; ?>
			<?php if ($this->session->flashdata('success')): ?>
				<div class="alert alert-success"><?= $this->session->flashdata('success') ?></div>
			<?php endif; ?>
			<div class="card">
				<div class="card-body">
					<form method="POST" action="/driver/auth">
						<div class="form-group mb-3">
							<label for="phone">Nomor HP</label>
							<input type="text" class="form-control" id="phone" name="phone" placeholder="08xxxxxxxxxx" required />
						</div>
						<div class="form-group mb-3">
							<label for="password">Password</label>
							<input type="password" class="form-control" id="password" name="password" required />
						</div>
						<button type="submit" class="btn btn-primary btn-block w-100">Masuk</button>
					</form>
				</div>
			</div>
			<p class="text-center mt-3">
				Belum punya akun? <a href="/driver/register">Daftar di sini</a>
			</p>
		</div>
	</div>
</body>
</html>
