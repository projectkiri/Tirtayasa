<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html class="no-js" lang="en">
<head>
	<meta charset="utf-8" />
	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-QXRGWXE3RZ"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());

		gtag('config', 'G-QXRGWXE3RZ');
	</script>
	<title>KIRI</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="description" content="<?= $this->lang->line('meta-description') ?>" />
	<meta name="author" content="Project Kiri (KIRI)" />
	<link rel="stylesheet" href="/ext/bootstrap/css/bootstrap.min.css" />
	<link href="https://api.mapbox.com/mapbox-gl-js/v1.10.0/mapbox-gl.css" rel="stylesheet" />
	<link rel="stylesheet" href="/stylesheets/styleIndex.css" />
	<link rel="icon" href="/images/favicon.ico" type="image/x-icon">
</head>
<body>
	<div class="container-fluid">
		<div class="row order-3">
			<div id="controlpanel" class="col-lg-3 col-md-6 order-md-9">
				<div class="col">
					<img class="mx-auto d-block" src="/images/kiri200.png" alt="KIRI logo"/>
				</div>

				<div class="row p-1 pb-3">
					<div class="col-5">
						<select id="regionselect" class="form-control">
							<?php foreach ($regions as $key => $value): ?>
								<option value="<?= $key ?>"<?= ($region == $key ? ' selected' : '') ?>><?= $value['name'] ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-7">
						<select id="localeselect" class="form-control">
							<?php foreach ($languages as $key => $value): ?>
								<option value="<?= $key ?>"<?= ($locale == $key ? ' selected' : '') ?>><?= $value['name'] ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				
				<div class="row p-1">
					<div class="col-2">
						<span for="startInput" class="align-middle"><?= $this->lang->line('From') ?>:</span>
					</div>
					<div class="col-10">
						<input type="text" id="startInput" class="form-control" value="" placeholder="<?= $this->lang->line('placeholder-from') ?>">
					</div>
				</div>
				<div class="row p-1">
					<div class="col-lg-12">
						<select id="startSelect" class="form-control hidden"></select>
					</div>
				</div>
				<div class="row p-1">
					<div class="col-2">
						<span for="finishInput" class="align-middle"><?= $this->lang->line('To') ?>:</span>
					</div>
					<div class="col-10">
						<input type="text" id="finishInput" class="form-control" value="" placeholder="<?= $this->lang->line('placeholder-to') ?>">
					</div>
				</div>
				<div class="row p-1">
					<div class="col-lg-12">
						<select id="finishSelect" class="form-control hidden"></select>
					</div>
				</div>
				<div class="row p-1 pb-3">
					<div class="btn-group fullwidth" role="group">
						<div class="col-sm-6">
							<a href="#" class="btn btn-primary btn-block" id="findbutton"><strong><?= $this->lang->line('Find') ?>!</strong></a>
						</div>
						<div class="col-sm-3">
							<a href="#" class="btn btn-light btn-block" id="swapbutton"><img src="images/swap.png" alt="swap"></a>
						</div>
						<div class="col-sm-3">
							<a href="#" class="btn btn-light btn-block" id="resetbutton"><img src="images/reset.png" alt="reset"></a>
						</div>
					</div>
				</div>
				<div class="row p-1">
					<div class="col-12" id="routingresults">
						<div id="results-section-container"></div>
					</div>
				</div>
				<div class="row">
					<div class="col-12">
						<footer>
							<a href="<?= $this->lang->line('url-legal') ?>"><?= $this->lang->line('Legal') ?></a> | 
							<a href="<?= $this->lang->line('url-feedback') ?>"><?= $this->lang->line('Feedback') ?></a> | 
							<a href="<?= $this->lang->line('url-about') ?>"><?= $this->lang->line('About KIRI') ?></a><br/><br/>
							<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
								<input type="hidden" name="cmd" value="_s-xclick">
								<input type="hidden" name="hosted_button_id" value="WKWS26A57WHJG">
								<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
								<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
							</form>
						</footer>
						&nbsp;
					</div>
				</div>
			</div>
			<div id="map" class="col-md-6 col-lg-9"></div>
		</div>
	</div>
	<script src="/ext/jquery/jquery.min.js"></script>
	<script src="/ext/bootstrap/js/bootstrap.min.js"></script>
	<script src="https://api.mapbox.com/mapbox-gl-js/v1.10.0/mapbox-gl.js"></script>
	<script>
		var region = '<?= $region ?>';
		var input_text = <?= json_encode($inputText) ?>;
		var coordinates = <?= json_encode($inputCoordinate) ?>;
	</script>
	<script src="/mainpage/js/protocol.js"></script>
	<script src="/mainpage/js/main.js?locale=<?= $locale ?>"></script>
	<?php if ($newhome_popup): ?>
		<div class="modal fade" id="popupModal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="modalodalLabel">KIRI has a new home</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<?= $this->lang->line('newhome-message') ?>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
					</div>
				</div>
			</div>
		</div>
		<script>
			$(document).ready(function() {
				$('#popupModal').modal();
			});
		</script>
	<?php endif; ?>
</body>
</html>
