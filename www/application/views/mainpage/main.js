<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
var regions = <?=json_encode($this->config->item('regions'))?>;
mapboxgl.accessToken = <?=json_encode($this->config->item('mapbox-token'))?>;

const trackColors = ['#339933', '#8BB33B', '#267373'];
const walkColor = '#CC3333';

var map_component_ids = [];

$(document).ready(function () {
	var protocol = new CicaheumLedengProtocol(<?=json_encode($this->config->item('cicaheumledeng-key'))?>, function (message) {
		clearSecondaryAlerts();
		showAlert('<?=$this->lang->line("Connection problem")?>', 'alert');
	});

	var map = new mapboxgl.Map({
		container: 'map', // container id
		style: 'mapbox://styles/mapbox/outdoors-v11', // stylesheet location
		center: [regions[region].lon, regions[region].lat], // starting position [lng, lat]
		zoom: regions[region].zoom // starting zoom
	});
	map.addControl(new mapboxgl.NavigationControl());
	var resultVectorSource = {
		'type': 'FeatureCollection',
		'features': [
		{}
		]};

	// Start geolocation tracking routine
	var geolocation = new mapboxgl.GeolocateControl({
		positionOptions: {
			enableHighAccuracy: true,
			timeout:1000
		},
		trackUserLocation: true

	});
	map.addControl(geolocation);
	// End geolocation tracking routine

	var markers = { start: null, finish: null };

	var focused = false;
	$.each(['start', 'finish'], function (sfIndex, sfValue) {
		var placeInput = $('#' + sfValue + 'Input');
		var placeSelect = $('#' + sfValue + 'Select');

		if (input_text[sfValue] != null) {
			placeInput.val(input_text[sfValue]);
			if (coordinates[sfValue] != null) {
				placeInput.prop('disabled', true);
				var lonlat = stringToLonLat(coordinates[sfValue]);
				mapCenter = lonlat;
			}
		} else if (focused === false) {
			placeInput.focus();
			focused = true;
		}
		$('#' + sfValue + 'Select').addClass('hidden');

		placeInput.change(function () {
			coordinates[sfValue] = null;
			if (markers[sfValue] != null) {
				map.removeLayer(sfValue);
				map.removeSource(sfValue)
			}
		});
		placeSelect.change(function () {
			clearAlerts();
			showAlert('<img src="images/loading.gif" alt="... "/> ' + '<?=$this->lang->line("Please wait")?>...', 'secondary');
			coordinates[sfValue] = $(this).val();
			checkCoordinatesThenRoute(coordinates);
		});

	});

	// Event handlers
	var localeSelect = $('#localeselect');
	localeSelect.change(function () {
		// IE fix: when window.location.origin is not available 
		if (!window.location.origin) {
			window.location.origin = window.location.protocol + "//" + window.location.hostname + (window.location.port ? ':' + window.location.port : '');
		}
		window.location.replace(window.location.origin + "?locale=" + localeSelect.val());
	});
	var regionSelect = $('#regionselect');
	regionSelect.change(function () {
		updateRegion(regionSelect.val(), true);
		coordinates['start'] = null;
		coordinates['finish'] = null;
	});
	$('#findbutton').click(findRouteClicked);
	$('input').keyup(function (e) {
		if (e.keyCode === 13) {
			findRouteClicked();
		}
	});
	$('#resetbutton').click(resetScreen);
	$('#swapbutton').click(swapInput);

	// Map click event
	map.on('click', function (event) {
		if ($('#startInput').val() === '') {
			map.loadImage('../../../images/start.png',
				function(error, image) {
					map.addImage('startPoint', image);
					map.addSource('start', {
						'type': 'geojson',
						'data': {
							'type': 'FeatureCollection',
							'features': [
							{
								'type': 'Feature',
								'geometry': {
									'type': 'Point',
									'coordinates': [event.lngLat['lng'], event.lngLat['lat']]
								}
							}
							]
						}
					});
					map.addLayer({
						'id': 'start',
						'type': 'symbol',
						'source': 'start',
						'layout': {
							'icon-image': 'startPoint',
							'icon-size': 1
						}
					});
					markers['start'] = map.getSource('start');
				}
				);
			$('#startInput').val(event.lngLat['lat'] + ',' + event.lngLat['lng']);
		} else if ($('#finishInput').val() === '') {
			map.loadImage('../../../images/finish.png',
				function(error, image) {
					map.addImage('finishPoint', image);
					map.addSource('finish', {
						'type': 'geojson',
						'data': {
							'type': 'FeatureCollection',
							'features': [
							{
								'type': 'Feature',
								'geometry': {
									'type': 'Point',
									'coordinates': [event.lngLat['lng'], event.lngLat['lat']]
								}
							}
							]
						}
					});
					map.addLayer({
						'id': 'finish',
						'type': 'symbol',
						'source': 'finish',
						'layout': {
							'icon-image': 'finishPoint',
							'icon-size': 1
						}
					});
					markers['finish'] = map.getSource('finish')
				}
				);
			$('#finishInput').val(event.lngLat['lat'] + ',' + event.lngLat['lng']);
		}
	});

	// Lastly, execute search if both start and finish are ready
	if ($('#startInput').val() != '' && $('#finishInput').val() != '') {
		findRouteClicked();
	}

	/**
	 * Check if coordinates are complete. If yes, then start routing.
	 * @param coordinates the coordinates to check.
	 */
	 function checkCoordinatesThenRoute(coordinates) {
	 	if (coordinates['start'] != null && coordinates['finish'] != null) {
	 		protocol.findRoute(
	 			coordinates['start'],
	 			coordinates['finish'],
	 			'<?=$locale?>',
	 			function (results) {
	 				if (results.status === 'ok') {
	 					showRoutingResults(results);
	 				} else {
	 					clearSecondaryAlerts();
	 					showAlert('<?=$this->lang->line("Connection problem")?>', 'alert');
	 				}
	 			});
	 	}
	 }

	 function clearRoutingResultsOnMap() {
	 	updateRegion(region, false);
	 	// Remove components in backward manner, because layer is dependant on source
	 	// but source was created first
	 	for (let i = map_component_ids.length; i >= 0; i--) {
	 		if(map.getLayer(map_component_ids[i])) {
	 			map.removeLayer(map_component_ids[i]);
	 		}
	 		if(map.getSource(map_component_ids[i])) {
	 			map.removeSource(map_component_ids[i]);
	 		}
	 	}
	 }

	 function clearRoutingResultsOnTable() {
	 	$('.nav').remove();
	 	$('.tab-content').remove();
	 }

	 function clearAlerts() {
	 	$('.alert').remove();
	 }

	 function clearSecondaryAlerts() {
	 	$('.alert.alert-secondary').fadeOut();
	 }

	 function clearStartFinishMarker() {
	 	if (map.getLayer('start')) map.removeLayer('start');
	 	if (map.getSource('start')) map.removeSource('start');
	 	if (map.hasImage('startPoint')) map.removeImage('startPoint');

	 	if (map.getLayer('finish')) map.removeLayer('finish');
	 	if (map.getSource('finish')) map.removeSource('finish');
	 	if (map.hasImage('finishPoint')) map.removeImage('finishPoint');
	 }

	/**
	 * A function that will be called when find route button is clicked
	 * (or triggered by another means)
	 */
	 function findRouteClicked() {
		// Validate
		var cancel = false;
		$.each(['start', 'finish'], function (sfIndex, sfValue) {
			if ($('#' + sfValue + 'Input').val() === '') {
				cancel = true;
				return;
			}
		});
		if (cancel) {
			showAlert('<?=$this->lang->line("Fill both")?>', 'alert');
			return;
		}

		clearAlerts();
		clearRoutingResultsOnTable();
		showAlert('<img src="images/loading.gif" alt="... "/> ' + '<?=$this->lang->line("Please wait")?>...', 'secondary');

		var completedLatLon = 0;
		$.each(['start', 'finish'], function (sfIndex, sfValue) {
			var placeInput = $('#' + sfValue + 'Input');
			var placeSelect = $('#' + sfValue + 'Select');

			if (isLatLng(placeInput.val())) {
				coordinates[sfValue] = placeInput.val();
				completedLatLon++;
			} else {
				if (coordinates[sfValue] == null) {
					// Coordinates not yet ready, we do a search place
					if (coordinates[sfValue] == null) {
						// Coordinates not yet ready, we do a search place
						protocol.searchPlace(
							placeInput.val(),
							region,
							function (result) {
								placeSelect.empty();
								placeSelect.addClass('hidden');
								if (result.status != 'error') {
									if (result.searchresult.length > 0) {
										$.each(result.searchresult, function (index, value) {
											var placeSelect = $('#' + sfValue + 'Select');
											placeSelect
											.append($('<option></option>')
												.attr('value', value['location'])
												.text(value['placename']));
											placeSelect.removeClass('hidden');
										});
										coordinates[sfValue] = result.searchresult[0]['location'];
										checkCoordinatesThenRoute(coordinates);
									} else {
										clearSecondaryAlerts();
										clearRoutingResultsOnMap();
										showAlert(placeInput.val() + ' <?=$this->lang->line("not found")?>', 'alert');
									}
								} else {
									clearSecondaryAlerts();
									clearRoutingResultsOnMap();
									showAlert('<?=$this->lang->line("Connection problem")?>', 'alert');
								}
							});
					} else {
						// Coordinates are already available, skip searching
						completedLatLon++;
					}
				}
			}
		});
		if (completedLatLon == 2) {
			checkCoordinatesThenRoute(coordinates);
		}
	}

	/**
	 * Convert lon/lat into text representation
	 * @return the lon/lat in string, 5 digits after '.'
	 */
	function latLngToString(lonLat) {
		return lonLat[1].toFixed(5) + ',' + lonLat[0].toFixed(5);
	}

	/**
	 * Checks if the text provided is in a lat/lng format.
	 * @return true if it is, false otherwise.
	 */
	function isLatLng(text) {
		return text.match('-?[0-9.]+,-?[0-9.]+');
	}

	function resetScreen() {
		clearRoutingResultsOnTable();
		clearRoutingResultsOnMap();
		clearAlerts();
		clearStartFinishMarker();
		$.each(['start', 'finish'], function (sfIndex, sfValue) {
			var placeInput = $('#' + sfValue + 'Input');
			placeInput.val('');
			placeInput.prop('disabled', false);
			$('#' + sfValue + 'Select').addClass('hidden');
		});
	}

	/**
	 * Sets a cookie in browser, adapted from http://www.w3schools.com/js/js_cookies.asp
	 */
	function setCookie(cname, cvalue) {
		var d = new Date();
		d.setTime(d.getTime() + (365 * 24 * 60 * 60 * 1000));
		var expires = "expires=" + d.toGMTString();
		document.cookie = cname + "=" + cvalue + "; " + expires;
	}

	/**
	 * Show alert message
	 * @param message the message
	 * @param cssClass the foundation css class ('success', 'alert', 'secondary')
	 */
	function showAlert(message, cssClass) {
		if (cssClass === 'alert') {
			cssClass = 'danger'
		}
		var alert = $('<div data-alert class="alert alert-' + cssClass + ' alert-dismissible rounded-left rounded-right" role="alert">' + message + '<a href="#" class="close" data-dismiss="alert">&times;</a></div>');
		$('#routingresults').prepend(alert);
	}

	/**
	 * Shows the routing result on panel an map
	 * @param results the routing result JSON response
	 */
	function showRoutingResults(results) {
		clearStartFinishMarker();
		clearRoutingResultsOnTable();
		clearSecondaryAlerts();
		var kiriURL = encodeURIComponent('<?= base_url() ?>?start=' + encodeURIComponent($('#startInput').val()) + '&finish=' + encodeURIComponent($('#finishInput').val()) + '&region=' + region);
		var kiriMessage = encodeURIComponent('<?=$this->lang->line("I take public transport")?>'.replace('%finish%', $('#finishInput').val()).replace('%start%', $('#startInput').val()));
		var sectionContainer = $('<div></div>');
		var temp1 = $('<ul class="nav nav-tabs" role="tablist"></ul>');
		var temp2 = $('<div class="tab-content"></div>');
		$('#routingresults').append(sectionContainer);
		$.each(results.routingresults, function(resultIndex, result) {
			var resultHTML1 = resultIndex === 0 ? '<li><a class="nav-link active active-tabs ' : '<li><a class="nav-link ';
			resultHTML1 += 'text-decoration-none" data-toggle="tab" href="#panel1-' + (resultIndex + 1) + '" role="tab">' + (result.traveltime === null ? '<?=$this->lang->line("Oops")?>' : result.traveltime) + '</a></li>';
			var resultHTML2 = '<div id="panel1-' + (resultIndex + 1) + '"';
			resultHTML2 += resultIndex === 0 ? ' class="tab-pane active" role="tabpanel"><table class="table-striped">' : ' class="x tab-pane" role="tabpanel"><table class="table-striped">';
			$.each(result.steps, function (stepIndex, step) {
				resultHTML2 += '<tr><td><img src="../images/means/' + step[0] + '/' + step[1] + '.png" alt="' + step[1] + '"/></td><td>' + step[3];
				resultHTML2 += '</td></tr>';
			});
			resultHTML2 += "<tr><td class=\"center\" colspan=\"2\">";
			resultHTML2 += "<a target=\"_blank\" href=\"https://www.facebook.com/sharer/sharer.php?u=" + kiriURL + "\"><img alt=\"Share to Facebook\" src=\"images/fb-large.png\"/></a> &nbsp; &nbsp; ";
			resultHTML2 += "<a target=\"_blank\" href=\"https://twitter.com/intent/tweet?via=kiriupdate&text=" + kiriMessage + "+" + kiriURL + "\"><img alt=\"Tweet\" src=\"images/twitter-large.png\"/></a>";
			resultHTML2 += "</td></tr>\n";
			resultHTML2 += '</table></div>';
			temp1.append(resultHTML1);
			temp2.append(resultHTML2);
		});
		sectionContainer.append(temp1);
		sectionContainer.append(temp2);

		$(".nav .nav-link").on("click", function(){
			$(".nav").find(".active").removeClass("active");
			$(".tab-pane").removeClass("active");
			$(this).addClass("active");
			$($(this).attr("href")).addClass("active");
		});

		$.each(results.routingresults, function(resultIndex, result) {
			$('a[href="#panel1-' + (resultIndex + 1) + '"]').click(function() {
				showSingleRoutingResultOnMap(result);
			});
		});
		showSingleRoutingResultOnMap(results.routingresults[0]);
	}

	/**
	 * Shows a single routing result on map
	 * @param result the JSON array for one result
	 **/
	function showSingleRoutingResultOnMap(result) {
		clearRoutingResultsOnMap();
		let trackCounter = 0;
		let bounds = null;
		$.each(result.steps, function (stepIndex, step) {
			if (step[0] === 'none') {
				// Don't draw line
			} else {
				let coordinates = stringArrayToPointArray(step[2]);
				map.addSource('source_' + stepIndex, {
					'type': 'geojson',
					'data': {
						'type': 'Feature',
						'properties': {
							'color': step[0] == 'walk' ? walkColor : trackColors[trackCounter++ % trackColors.length]
						},
						'geometry': {
							'type': 'LineString',
							'coordinates': coordinates
						}
					}
				});
				map_component_ids.push('source_' + stepIndex);
				map.addLayer({
					'id': 'layer_' + stepIndex,
					'type': 'line',
					'source': 'source_' + stepIndex,
					'layout': {
						'line-join': 'round',
						'line-cap': 'round'
					},
					'paint': {
						'line-color': ['get', 'color'],
						'line-width': 5
					}
				});
				map_component_ids.push('layer_' + stepIndex);
				for (let i = 0; i < coordinates.length; coordinates++) {
					if (bounds) {
						bounds.extend(coordinates[i]);
					} else {
						bounds = new mapboxgl.LngLatBounds(coordinates[i], coordinates[i]);
					}					
				}
			}

			if (stepIndex === 0) {
				var coord = stringToLonLat(step[2][0]);
				if (map.hasImage('startPoint')) map.removeImage('startPoint');
				if (map.getLayer('start')) map.removeLayer('start');
				if (map.getSource('start')) map.removeSource('start');
				map_component_ids.push('start');
				map.loadImage('../../../images/start.png',
					function(error, image) {
						map.addImage('startPoint', image);
						map.addSource('start', {
							'type': 'geojson',
							'data': {
								'type': 'FeatureCollection',
								'features': [
								{
									'type': 'Feature',
									'geometry': {
										'type': 'Point',
										'coordinates': [coord[0],coord[1]]
									}
								}
								]
							}
						});
						map.addLayer({
							'id': 'start',
							'type': 'symbol',
							'source': 'start',
							'layout': {
								'icon-image': 'startPoint',
								'icon-size': 1,
								'icon-anchor': 'bottom-right'
							}
						});
					}
					);
			} else {
				var lonlat = stringToLonLat(step[2][0]);
				if (step[0] != "walk") {
					if (map.hasImage(step[0] + 'baloon' + step[1])) map.removeImage(step[0] + 'baloon' + step[1]);
					if (map.getLayer(step[0] + 'baloon' + step[1])) map.removeLayer(step[0] + 'baloon' + step[1]);
					if (map.getSource(step[0] + 'baloon' + step[1])) map.removeSource(step[0] + 'baloon' + step[1]);
					map_component_ids.push(step[0] + 'baloon' + step[1]);
					map.loadImage('../../../images/means/' + step[0] + '/baloon/' + step[1] + '.png',
						function(error, image) {
							map.addImage(step[0] + 'baloon' + step[1], image);
							map.addSource(step[0] + 'baloon' + step[1], {
								'type': 'geojson',
								'data': {
									'type': 'FeatureCollection',
									'features': [
									{
										'type': 'Feature',
										'geometry': {
											'type': 'Point',
											'coordinates': [lonlat[0],lonlat[1]]

										}
									}
									]
								}
							});
							map.addLayer({
								'id': step[0] + 'baloon' + step[1],
								'type': 'symbol',
								'source': step[0] + 'baloon' + step[1],
								'layout': {
									'icon-image': step[0] + 'baloon' + step[1],
									'icon-size': 1,
									'icon-anchor': 'bottom-left'
								}
							});
						}
						);
				} else {
					if (map.hasImage('walk' + stepIndex)) {
						map.removeImage('walk' + stepIndex);
					}
					if (map.getLayer('walk' + stepIndex)) {
						map.removeLayer('walk' + stepIndex);
					}
					if (map.getSource('walk' + stepIndex)) {
						map.removeSource('walk' + stepIndex);
					}
					map_component_ids.push('walk' + stepIndex);
					map.loadImage('../../../images/means/walk/baloon/walk.png', function(error, image) {
						map.addImage('walk' + stepIndex, image);
						map.addSource('walk' + stepIndex, {
							'type': 'geojson',
							'data': {
								'type': 'FeatureCollection',
								'features': [
								{
									'type': 'Feature',
									'geometry': {
										'type': 'Point',
										'coordinates': [lonlat[0],lonlat[1]]

									}
								}
								]
							}
						});
						map.addLayer({
							'id': 'walk' + stepIndex,
							'type': 'symbol',
							'source': 'walk' + stepIndex,
							'layout': {
								'icon-image': 'walk' + stepIndex,
								'icon-size': 1,
								'icon-anchor': 'bottom-right'
							}
						});
					});
				}
			}

			if (stepIndex === result.steps.length - 1) {
				var lonlat = stringToLonLat(step[2][step[2].length - 1]);
				if (map.hasImage('finishPoint')) map.removeImage('finishPoint');
				if (map.getLayer('finish')) map.removeLayer('finish');
				if (map.getSource('finish')) map.removeSource('finish');
				map_component_ids.push('finish');
				map.loadImage('../../../images/finish.png', function(error, image) {
					map.addImage('finishPoint', image);
					map.addSource('finish', {
						'type': 'geojson',
						'data': {
							'type': 'FeatureCollection',
							'features': [
							{
								'type': 'Feature',
								'geometry': {
									'type': 'Point',
									'coordinates': [lonlat[0],lonlat[1]]
								}
							}
							]
						}
					});
					map.addLayer({
						'id': 'finish',
						'type': 'symbol',
						'source': 'finish',
						'layout': {
							'icon-image': 'finishPoint',
							'icon-size': 1,
							'icon-anchor': 'bottom-left'
						}
					});
				}
				);
			}
		});

		map.fitBounds(bounds, {
			padding: 20
		});
	}

	/**
	 * Converts "lat,lon" array into coordinate object array.
	 * @return the converted Point array object
	 */
	function stringArrayToPointArray(textArray) {
		var lonlatArray = new Array();
		$.each(textArray, function (index, value) {
			lonlatArray[index] = stringToLonLat(value);
		});
		return lonlatArray;
	}

	/**
	 * Converts "lat,lng" into lonlat array
	 * @return the converted lonlat array
	 */
	function stringToLonLat(text) {
		var latlon = text.split(/,\s*/);
		return [parseFloat(latlon[1]), parseFloat(latlon[0])];
	}

	/**
	 * Swap the inputs
	 */
	function swapInput() {
		var startInput = $('#startInput');
		var finishInput = $('#finishInput');
		var temp = startInput.val();
		startInput.val(finishInput.val());
		finishInput.val(temp);
		coordinates['start'] = null;
		coordinates['finish'] = null;

		if (startInput.val() != '' && finishInput.val() != '') {
			findRouteClicked();
		}
	}

	/**
	 * Updates the region information in this page.
	 */
	function updateRegion(newRegion, updateCookie) {
		region = newRegion;
		if (updateCookie) {
			setCookie('region', region);
		}
		var point = [regions[region].lon, regions[region].lat];
		map.flyTo({ center: point, zoom: regions[region].zoom });
	}

	/**
	 * Computes distance between two position (from http://www.movable-type.co.uk/scripts/latlong.html)
	 */
	function computeDistance(p1, p2) {
		var R = 6371; // km
		var p1Lat = p1[1] * Math.PI / 180;
		var p2Lat = p2[1] * Math.PI / 180;
		var dLat = (p2[1] - p1[1]) * Math.PI / 180;
		var dLon = (p2[0] - p1[0]) * Math.PI / 180;

		var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
		Math.sin(dLon / 2) * Math.sin(dLon / 2) *
		Math.cos(p1Lat) * Math.cos(p2Lat);
		var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
		var d = R * c;
		return d;
	}
});
