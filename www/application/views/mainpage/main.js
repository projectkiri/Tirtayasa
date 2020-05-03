<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
var regions = <?= json_encode($this -> config -> item('regions')) ?>;
mapboxgl.accessToken = 'pk.eyJ1Ijoia2VsdmluYWRyaWFuIiwiYSI6ImNrOGx1NWlkdDA1YmczbW44MGM3dzY2czAifQ.06uwtSbY-t2pKcFYLAoXqA';
var map;
// colorList: 0=walk, 1=angkot1, 2=angkot2, 3=angkot3
const colorList = ['#CC3333', '#339933', '#8BB33B', '#267373'];
var ids = [];

// var trackStrokeStyles = [
// 	new ol.style.Style({
// 		stroke: new ol.style.Stroke({
// 			color : '#339933', hijau tua #339933
// 			width : 5			
// 		})
// 	}),
// 	new ol.style.Style({
// 		stroke: new ol.style.Stroke({
// 			color : '#8BB33B', hijau muda #8BB33B
// 			width : 5			
// 		})
// 	}),
// 	new ol.style.Style({
// 		stroke: new ol.style.Stroke({
// 			color : '#267373', biru muda #267373
// 			width : 5			
// 		})
// 	})
// ];

// var walkStrokeStyle = new ol.style.Style({
// 	stroke: new ol.style.Stroke({
// 		color : '#CC3333', merah #CC3333
// 		width : 5
// 	})
// });

$(document).ready(function () {
	var protocol = new CicaheumLedengProtocol("02428203D4526448", function (message) {
		clearSecondaryAlerts();
		showAlert('<?=$this->lang->line('Connection problem')?>', 'alert');
	});

	// var mapLayer = new ol.layer.Tile(
	// {
	// 	source : new ol.source.BingMaps(
	// 		{
	// 			key : 'AuV7xXD6_UMiQ5BLoZr0xkpjLpzWqMT55772Q8XtLIQeuDebHPKiNXSlZXxEr1GA',
	// 			imagerySet : 'Road'
	// 		})
	// });
	// var resultVectorSource = new ol.source.Vector();
	// var inputVectorSource = new ol.source.Vector();

	var map = new mapboxgl.Map({
		container: 'map', // container id
		style: 'mapbox://styles/mapbox/outdoors-v11', // stylesheet location
		center: [-122.486055, 37.830948], // starting position [lng, lat]
		zoom: 12 // starting zoom
	});
	map.addControl(new mapboxgl.NavigationControl());
	var resultVectorSource = {
		'type': 'FeatureCollection',
		'features': [
			{}
		]};

	// auto detect geolocation
	// setTimeout(function() {
	// 	$(".mapboxgl-ctrl-geolocate").click();
	// }, 5000);

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
	updateRegion(region, false);

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
				// inputVectorSource.removeFeature(markers[sfValue]);
				map.removeLayer(sfValue);
				map.removeSource(sfValue)
			}
		});
		placeSelect.change(function () {
			clearAlerts();
			showAlert('<img src="images/loading.gif" alt="... "/> ' + '<?=$this->lang->line('Please wait')?>...', 'secondary');
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
						showAlert('<?=$this->lang->line('Connection problem')?>', 'alert');
					}
				});
		}
	}

	function clearRoutingResultsOnMap() {
		updateRegion(region, false);
		for (var i = 0; i < ids.length; i++) {
			if(map.getLayer(ids[i])) map.removeLayer(ids[i]);
			if(map.getSource(ids[i])) map.removeSource(ids[i]);
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
		// inputVectorSource.clear();
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
			showAlert('<?=$this->lang->line('Fill both')?>', 'alert');
			return;
		}

		clearAlerts();
		clearRoutingResultsOnTable();
		showAlert('<img src="images/loading.gif" alt="... "/> ' + '<?=$this->lang->line('Please wait')?>...', 'secondary');

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
										showAlert(placeInput.val() + ' <?=$this->lang->line('not found')?>', 'alert');
									}
								} else {
									clearSecondaryAlerts();
									clearRoutingResultsOnMap();
									showAlert('<?=$this->lang->line('Connection problem')?>', 'alert');
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
		var kiriURL = encodeURIComponent('http://kiri.travel?start=' + encodeURIComponent($('#startInput').val()) + '&finish=' + encodeURIComponent($('#finishInput').val()) + '&region=' + region);
		var kiriMessage = encodeURIComponent('<?=$this->lang->line("I take public transport")?>'.replace('%finish%', $('#finishInput').val()).replace('%start%', $('#startInput').val()));
		var sectionContainer = $('<div></div>');
		var temp1 = $('<ul id="myTab" class="nav nav-tabs" role="tablist"></ul>');
		var temp2 = $('<div class="tab-content"></div>');
		$('#routingresults').append(sectionContainer);
		$.each(results.routingresults, function (resultIndex, result) {
			var resultHTML1 = resultIndex === 0 ? '<li class="nav-link active">' : '<li class="nav-link">';
			resultHTML1 += '<a data-toggle="tab" href="#panel1-' + (resultIndex + 1) + '" role="tab">' + (result.traveltime === null ? '<?=$this->lang->line('Oops')?>' : result.traveltime) + '</a></li>';
			var resultHTML2 = '<div id="panel1-' + (resultIndex + 1) + '"';
			resultHTML2 += resultIndex === 0 ? ' class="tab-pane container active" role="tabpanel"><table>' : ' class="tab-pane container fade" role="tabpanel"><table>';
			$.each(result.steps, function (stepIndex, step) {
				resultHTML2 += '<tr><td><img src="../images/means/' + step[0] + '/' + step[1] + '.png" alt="' + step[1] + '"/></td><td>' + step[3];
				if (step[4] != null) {
					resultHTML2 += ' <a class="ticket" href="' + step[4] + '" target="_blank"><?=$this->lang->line('BUY TICKET')?></a></td></tr>';
				}
				if (step[5] != null) {
					resultHTML2 += ' <a href="' + step[5] + '" target="_blank"><img src="images/edit.png" class="fontsize" alt="edit"/></a></td></tr>';
				}
				resultHTML2 += '</td></tr>';
			});
			resultHTML2 += "<tr><td class=\"center\" colspan=\"2\">";
			resultHTML2 += '<a href="https://youtu.be/jDFePujA8Kk" target="_blank" style="font-size: small;">' + '<?=$this->lang->line("Route broken? Help fix it!")?>' + "</a><br/><br/>\n";
			resultHTML2 += "<a target=\"_blank\" href=\"https://www.facebook.com/sharer/sharer.php?u=" + kiriURL + "\"><img alt=\"Share to Facebook\" src=\"images/fb-large.png\"/></a> &nbsp; &nbsp; ";
			resultHTML2 += "<a target=\"_blank\" href=\"https://twitter.com/intent/tweet?via=kiriupdate&text=" + kiriMessage + "+" + kiriURL + "\"><img alt=\"Tweet\" src=\"images/twitter-large.png\"/></a>";
			resultHTML2 += "</td></tr>\n";
			resultHTML2 += '</table></div>';
			temp1.append(resultHTML1);
			temp2.append(resultHTML2);
		});
		sectionContainer.append(temp1);
		sectionContainer.append(temp2);

		$.each(results.routingresults, function (resultIndex, result) {
			$('a[href="#panel1-' + (resultIndex + 1) + '"]').click(function () {
				showSingleRoutingResultOnMap(result);
			});
		});
		showSingleRoutingResultOnMap(results.routingresults[0]);
	}
/**
 * Drawing a path between two coord1 and coord2
 **/
function drawPath(color,coord1,coord2, stepIndex, i, coordinates){
	coordinates.push([coord1[0], coord1[1]], [coord2[0], coord2[1]]);
	map.addSource('route' + stepIndex + i, {
		'type': 'geojson',
		'data': {
				'type': 'Feature',
				'properties': {
					'color': colorList[color]
				},
				'geometry': {
					'type': 'LineString',
					'coordinates': [
						[coord1[0],coord1[1]],
						[coord2[0],coord2[1]]
					]
				}
			}
		});
		map.addLayer({
			'id': 'route' + stepIndex + i,
			'type': 'line',
			'source': 'route' + stepIndex + i,
			'layout': {
				'line-join': 'round',
				'line-cap': 'round'
			},
			'paint': {
				'line-color': ['get', 'color'],
				'line-width': 5
			}
		}
	);
}

/**
 * Shows a single routing result on map
 * @param result the JSON array for one result
 */
function showSingleRoutingResultOnMap(result) {
	clearRoutingResultsOnMap();
	var trackCounter = 0;
	var startVector = "";
	var curColor = 0;
	var curAngkot = 0;
	var coordinates = [];
	$.each(result.steps, function (stepIndex, step) {
		if (step[0] === 'none') {
			// Don't draw line
		} else {
			for ($i = 0; $i < step[2].length - 1; $i += 1) {
				if (step[0]==""){
					if(step[0]=='walk'){
						curColor = 0;
					} else {
						curColor=1;
					}
				}
				// 0=walk, 1=angkot1, 2=angkot2, 3=angkot3
				// #CC3333, #339933, #8BB33B, #267373
				else if (step[0]!="walk"){
					if(startVector!=step[0]){
						if (curColor==0){
							curColor = curAngkot;
						}
						if (curColor<3){
							curColor ++;
							curAngkot = curColor;
						} else if (curColor==3) {
							curColor = 1;
							curAngkot = curColor;
						}
					}
				} else {
					curColor = 0
				}
				startVector = step[0];
				var coord1 = stringToLonLat(step[2][$i]);
				var coord2 = stringToLonLat(step[2][$i + 1]);
				ids.push('route' + stepIndex + $i);
				drawPath(curColor,coord1,coord2,stepIndex,$i, coordinates);
			}
		}

		if (stepIndex === 0) {
			var coord = stringToLonLat(step[2][0]);
			if (map.hasImage('startPoint')) map.removeImage('startPoint');
			if (map.getLayer('start')) map.removeLayer('start');
			if (map.getSource('start')) map.removeSource('start');
			ids.push('start');
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
				ids.push(step[0] + 'baloon' + step[1]);
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
				if (map.hasImage('walk' + stepIndex)) map.removeImage('walk' + stepIndex);
				if (map.getLayer('walk' + stepIndex)) map.removeLayer('walk' + stepIndex);
				if (map.getSource('walk' + stepIndex)) map.removeSource('walk' + stepIndex);
				ids.push('walk' + stepIndex);
				map.loadImage('../../../images/means/walk/baloon/walk.png',
					function(error, image) {
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
					}
				);
			}
		}

		if (stepIndex === result.steps.length - 1) {
			var lonlat = stringToLonLat(step[2][step[2].length - 1]);
			if (map.hasImage('finishPoint')) map.removeImage('finishPoint');
			if (map.getLayer('finish')) map.removeLayer('finish');
			if (map.getSource('finish')) map.removeSource('finish');
			ids.push('finish');
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
	
	var bounds = coordinates.reduce(function(bounds, coord) {
		return bounds.extend(coord);
	}, new mapboxgl.LngLatBounds(coordinates[0], coordinates[0]));
	map.fitBounds(bounds, {
		padding: 20
	});
}

/**
 * Converts "lat,lon" array into coordinate object array.
 * @return the converted Point array object
 * sudah tidak diperlukan lagi
 */
function stringArrayToPointArray(textArray) {
	var lonlatArray = new Array();
	$.each(textArray, function (index, value) {
		lonlatArray[index] = ol.proj.transform(stringToLonLat(value), 'EPSG:4326', 'EPSG:3857');
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
	setCookie('region', region);
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

