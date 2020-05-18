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
	var routingResultMarkers = [];

	// Preload start and finish marker image
	var startMarkerElement = document.createElement('img');
	startMarkerElement.setAttribute('src', '../../../images/start.png');
	startMarkerElement.setAttribute('alt', 'start marker');
	var finishMarkerElement = document.createElement('img');
	finishMarkerElement.setAttribute('src', '../../../images/finish.png');
	finishMarkerElement.setAttribute('alt', 'finish marker');
	var walkMarkerElement = document.createElement('img');
	walkMarkerElement.setAttribute('src', '../../../images/means/walk/baloon/walk.png');
	walkMarkerElement.setAttribute('alt', 'walk marker');

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
				markers[sfValue].remove();
				markers[sfValue] = null;
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
			markers['start'] = new mapboxgl.Marker({
				element: startMarkerElement,
				anchor: 'bottom-right'
			});
			markers['start'].setLngLat([event.lngLat['lng'], event.lngLat['lat']]);
			markers['start'].addTo(map);
			$('#startInput').val(latLngToString(event.lngLat));
		} else if ($('#finishInput').val() === '') {
			markers['finish'] = new mapboxgl.Marker({
				element: finishMarkerElement,
				anchor: 'bottom-left'
			});
			markers['finish'].setLngLat([event.lngLat['lng'], event.lngLat['lat']]);
			markers['finish'].addTo(map);
			$('#finishInput').val(latLngToString(event.lngLat));
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
	 	// Remove layers in backward manner, because layer is dependant on source
	 	// but source was created first
	 	for (let i = map_component_ids.length; i >= 0; i--) {
	 		if(map.getLayer(map_component_ids[i])) {
	 			map.removeLayer(map_component_ids[i]);
	 		}
	 		if(map.getSource(map_component_ids[i])) {
	 			map.removeSource(map_component_ids[i]);
	 		}
	 	}
	 	// Remove markers
	 	for (let i = 0; i < routingResultMarkers.length; i++) {
	 		routingResultMarkers[i].remove();
	 	}
		routingResultMarkers = [];
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
		if (markers['start'] != null) {
			markers['start'].remove();
			markers['start'] = null;
		}
		if (markers['finish'] != null) {
			markers['finish'].remove();
			markers['finish'] = null;
		}
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
		return lonLat['lat'].toFixed(5) + ',' + lonLat['lng'].toFixed(5);
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
	 * @param cssClass the bootstrap css class
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
				resultHTML2 += '<tr><td class="p-1"><img src="../images/means/' + step[0] + '/' + step[1] + '.png" alt="' + step[1] + '"/></td><td class="p-1">' + step[3];
				resultHTML2 += '</td></tr>';
			});
			resultHTML2 += "<tr><td class=\"p-1 center\" colspan=\"2\">";
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
				let marker = new mapboxgl.Marker({
					element: startMarkerElement,
					anchor: 'bottom-right'
				});
				marker.setLngLat(stringToLonLat(step[2][0]));
				marker.addTo(map);
				routingResultMarkers.push(marker);
			} else {
				var lonlat = stringToLonLat(step[2][0]);
				if (step[0] != "walk") {
					let angkotMarkerElement = document.createElement('img');
					angkotMarkerElement.setAttribute('src', '../../../images/means/' + step[0] + '/baloon/' + step[1] + '.png');
					angkotMarkerElement.setAttribute('alt', 'angkot marker');
					let marker = new mapboxgl.Marker({
						element: angkotMarkerElement,
						anchor: 'bottom-left'
					});
					marker.setLngLat(lonlat);
					marker.addTo(map);
					routingResultMarkers.push(marker);
				} else {
					let marker = new mapboxgl.Marker({
						element: walkMarkerElement,
						anchor: 'bottom-right'
					});
					marker.setLngLat(lonlat);
					marker.addTo(map);
					routingResultMarkers.push(marker);
				}
			}

			if (stepIndex === result.steps.length - 1) {
				let marker = new mapboxgl.Marker({
					element: finishMarkerElement,
					anchor: 'bottom-left'
				});
				marker.setLngLat(stringToLonLat(step[2][step[2].length - 1]));
				marker.addTo(map);
				routingResultMarkers.push(marker);
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
		map.flyTo({ center: point, zoom: regions[region].zoom, bearing: 0, pitch: 0 });
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
