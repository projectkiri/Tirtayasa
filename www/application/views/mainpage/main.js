<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
var regions = <?=json_encode($this->config->item('regions'))?>;
var map;
var trackStrokeStyles = [
	new ol.style.Style({
		stroke: new ol.style.Stroke({
			color : '#339933',
			width : 5			
		})
	}),
	new ol.style.Style({
		stroke: new ol.style.Stroke({
			color : '#8BB33B',
			width : 5			
		})
	}),
	new ol.style.Style({
		stroke: new ol.style.Stroke({
			color : '#267373',
			width : 5			
		})
	})
];

var walkStrokeStyle = new ol.style.Style({
	stroke: new ol.style.Stroke({
		color : '#CC3333',
		width : 5
	})
});
		
$(document).ready(function() {
	var protocol = new CicaheumLedengProtocol("02428203D4526448", function(message) {
		clearSecondaryAlerts();
		showAlert('<?=$this->lang->line('Connection problem')?>', 'alert');
	});
	
	var mapLayer = new ol.layer.Tile(
	{
		source : new ol.source.BingMaps(
			{
				key : 'AuV7xXD6_UMiQ5BLoZr0xkpjLpzWqMT55772Q8XtLIQeuDebHPKiNXSlZXxEr1GA',
				imagerySet : 'Road'
			})
	});
	var resultVectorSource = new ol.source.Vector();
	var inputVectorSource = new ol.source.Vector();
	
	var map = new ol.Map(
	{
		layers : [ mapLayer, new ol.layer.Vector({source: inputVectorSource}), new ol.layer.Vector({source: resultVectorSource}) ],
		target : 'map'
	});
	
	// Start geolocation tracking routine
	var geolocation = new ol.Geolocation({
		  projection: map.getView().getProjection()
		});
	var positionFeature = new ol.Feature();
	positionFeature.setStyle(new ol.style.Style({
		image : new ol.style.Circle({
			radius : 6,
			fill : new ol.style.Fill({
				color : '#3399CC'
			}),
			stroke : new ol.style.Stroke({
				color : '#fff',
				width : 2
			})
		})
	}));
	geolocation.on('change:position', function() {
		var coordinates = geolocation.getPosition();
		positionFeature
				.setGeometry(coordinates ? new ol.geom.Point(
						coordinates) : null);
	});
	var featuresOverlay = new ol.FeatureOverlay({
		  map: map,
		  features: [positionFeature]
	});
	
	geolocation.once('change', function(evt) {
		var closestKey = null, closestDistance = null;
		$.each(regions, function(key, value) {
			var distance = computeDistance(ol.proj.transform(geolocation.getPosition(), 'EPSG:3857', 'EPSG:4326'), stringToLonLat(value.center));
			if (closestDistance == null || distance < value.radius) {
				closestDistance = distance;
				closestKey = key;
			}
		});
		$('#regionselect').val(closestKey);
		updateRegion(closestKey, false);
	});
	geolocation.setTracking(true);
	// End geolocation tracking routine

	var markers = {start: null, finish: null};
	updateRegion(region, false);
	
	var focused = false;
	$.each(['start', 'finish'], function(sfIndex, sfValue) {
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
		
		placeInput.change(function() {
			coordinates[sfValue] = null;
			if (markers[sfValue] != null) {
				inputVectorSource.removeFeature(markers[sfValue]);
				markers[sfValue] = null;
			}
		});
		placeSelect.change(function() {
			clearAlerts();
			showAlert('<img src="images/loading.gif" alt="... "/> ' + '<?=$this->lang->line('Please wait')?>...', 'secondary');
			coordinates[sfValue] = $(this).val();
			checkCoordinatesThenRoute(coordinates);
		});
				
	});
	
	// Event handlers
	var localeSelect = $('#localeselect');
	localeSelect.change(function() {
		// IE fix: when window.location.origin is not available 
		if (!window.location.origin) {
			window.location.origin = window.location.protocol + "//" + window.location.hostname + (window.location.port ? ':' + window.location.port: '');
		}
		window.location.replace(window.location.origin + "?locale=" + localeSelect.val());
	});
	var regionSelect = $('#regionselect');
	regionSelect.change(function() {
		updateRegion(regionSelect.val(), true);
		coordinates['start'] = null;
		coordinates['finish'] = null;
	});
	$('#findbutton').click(findRouteClicked);
	$('input').keyup(function(e) {
	    if ( e.keyCode === 13 ) {
	    	findRouteClicked();
	    }
	});
	$('#resetbutton').click(resetScreen);
	$('#swapbutton').click(swapInput);
	
	// Map click event
	map.on('click', function(event) {
    	if ($('#startInput').val() === '') {
			markers['start'] = new ol.Feature({
				geometry: new ol.geom.Point(event.coordinate)
			})
			markers['start'].setStyle(new ol.style.Style({
				image: new ol.style.Icon({
					src: 'images/start.png',
					anchor: [1.0, 1.0]
				})
			}));
			inputVectorSource.addFeature(markers['start']);
    		$('#startInput').val(latLngToString(ol.proj.transform(event.coordinate, 'EPSG:3857', 'EPSG:4326')));
    	} else if ($('#finishInput').val() === '') {
			markers['finish'] = new ol.Feature({
				geometry: new ol.geom.Point(event.coordinate)
			})
			markers['finish'].setStyle(new ol.style.Style({
				image: new ol.style.Icon({
					src: 'images/finish.png',
					anchor: [0.0, 1.0]
				})
			}));
			inputVectorSource.addFeature(markers['finish']);
    		$('#finishInput').val(latLngToString(ol.proj.transform(event.coordinate, 'EPSG:3857', 'EPSG:4326')));
    	}
	});
	
	// Lastly, execute search if both start and finish are ready
	if ($('#startInput').val() != '' && $('#finishInput').val() != '' ) {
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
					function(results) {
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
		resultVectorSource.clear();
		updateRegion(region, false);
	}
	
	function clearRoutingResultsOnTable() {
		$('.tabs').remove();
		$('.tabs-content').remove();
	}
	
	function clearAlerts() {
		$('.alert-box').remove();
	}
	
	function clearSecondaryAlerts() {
		$('.alert-box.secondary').fadeOut();
	}
	
	function clearStartFinishMarker() {
		if (markers['start'] != null) {
			markers['start'] = null;
		}
		if (markers['finish'] != null) {
			markers['finish'] = null;
		}
		inputVectorSource.clear();
	}
	
	/**
	 * A function that will be called when find route button is clicked
	 * (or triggered by another means)
	 */
	function findRouteClicked() {
		// Validate
		var cancel = false;
		$.each(['start', 'finish'], function(sfIndex, sfValue) {
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
		$.each(['start', 'finish'], function(sfIndex, sfValue) {
			var placeInput = $('#' + sfValue + 'Input');
			var placeSelect = $('#' + sfValue + 'Select');
			if (isLatLng(placeInput.val())) {
				coordinates[sfValue] = placeInput.val();
				completedLatLon++;
			} else {
				if (coordinates[sfValue] == null) {
					// Coordinates not yet ready, we do a search place
					protocol.searchPlace(
							placeInput.val(),
							region,
							function(result) {
								placeSelect.empty();
								placeSelect.addClass('hidden');
								if (result.status != 'error') {
									if (result.searchresult.length > 0) {
										$.each(result.searchresult, function(index, value) {
											var placeSelect = $('#' + sfValue + 'Select');
											placeSelect
										         .append($('<option></option>')
										         .attr('value',value['location'])
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
					checkCoordinatesThenRoute(coordinates);
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
		$.each(['start', 'finish'], function(sfIndex, sfValue) {
			var placeInput = $('#' + sfValue + 'Input');
			placeInput.val('');	
			placeInput.prop('disabled', false);
			$('#' + sfValue + 'Select').addClass('hidden');
		});
	}
	
	/**
	 * Sets a cookie in browser, adapted from http://www.w3schools.com/js/js_cookies.asp
	 */
	function setCookie(cname,cvalue)	{
		var d = new Date();
		d.setTime(d.getTime()+(365*24*60*60*1000));
		var expires = "expires="+d.toGMTString();
		document.cookie = cname + "=" + cvalue + "; " + expires;
	} 
	
	/**
	 * Show alert message
	 * @param message the message
	 * @param cssClass the foundation css class ('success', 'alert', 'secondary')
	 */
	function showAlert(message, cssClass) {
		var alert = $('<div data-alert class="alert-box ' + cssClass + ' round">' + message + '<a href="#" class="close">&times;</a></div>');
		$('#routingresults').prepend(alert);
		$(document).foundation();    
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
		var temp1 = $('<dl class="tabs" data-tab=""></dl>');
		var temp2 = $('<div class="tabs-content"></div>');
		$('#routingresults').append(sectionContainer);
		$.each(results.routingresults, function(resultIndex, result) {
			var resultHTML1 = resultIndex === 0 ? '<dd class="active">' : '<dd class="">';
			resultHTML1 += '<a href="#panel1-' + (resultIndex + 1) + '">' + (result.traveltime === null ? '<?=$this->lang->line('Oops')?>' : result.traveltime) + '</a></dd>';
			var resultHTML2 = '<div id="panel1-' + (resultIndex + 1)+'"';
			resultHTML2 += resultIndex === 0 ? ' class="content active"><table>' : ' class="content"><table>';
			$.each(result.steps, function (stepIndex, step) {
				resultHTML2 += '<tr><td><img src="../images/means/' + step[0]+ '/' + step[1] + '.png" alt="' + step[1] + '"/></td><td>' + step[3];
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

		$.each(results.routingresults, function(resultIndex, result) {
			$('a[href="#panel1-' + (resultIndex + 1) + '"]').click(function() {
				showSingleRoutingResultOnMap(result);
 			});
		});
	    $(document).foundation();    
		showSingleRoutingResultOnMap(results.routingresults[0]);
	}
	
	/**
	 * Shows a single routing result on map
	 * @param result the JSON array for one result
	 */
	function showSingleRoutingResultOnMap(result) {
		clearRoutingResultsOnMap();

		var trackCounter = 0;
		$.each(result.steps, function (stepIndex, step) {
			if (step[0] === 'none') {
				// Don't draw line
			} else {
				var lineFeature = new ol.Feature({
					geometry: new ol.geom.LineString(stringArrayToPointArray(step[2])),
				});
				lineFeature.setStyle(step[0] == 'walk' ? walkStrokeStyle : trackStrokeStyles[trackCounter++ % trackStrokeStyles.length]);
				resultVectorSource.addFeature(lineFeature);
			}
			
			if (stepIndex === 0) {
				var pointFeature = new ol.Feature({
					geometry: new ol.geom.Point(ol.proj.transform(stringToLonLat(step[2][0]), 'EPSG:4326', 'EPSG:3857'))
				})
				pointFeature.setStyle(new ol.style.Style({
					image: new ol.style.Icon({
						src: 'images/start.png',
						anchor: [1.0, 1.0]
					})
				}));
				resultVectorSource.addFeature(pointFeature);
			} else {
				var lonlat = stringToLonLat(step[2][0]);
				if(step[0] != "walk"){
					var pointFeature = new ol.Feature({
						geometry: new ol.geom.Point(ol.proj.transform(lonlat, 'EPSG:4326', 'EPSG:3857'))
					})
					pointFeature.setStyle(new ol.style.Style({
						image: new ol.style.Icon({
							src: '../images/means/' + step[0] + '/baloon/' + step[1] + '.png',
							anchor: [0.0, 1.0]
						})
					}));
					resultVectorSource.addFeature(pointFeature);
				} else {
					var pointFeature = new ol.Feature({
						geometry: new ol.geom.Point(ol.proj.transform(lonlat, 'EPSG:4326', 'EPSG:3857'))
					})
					pointFeature.setStyle(new ol.style.Style({
						image: new ol.style.Icon({
							src: 'images/means/walk/baloon/walk.png',
							anchor: [1.0, 1.0]
						})
					}));
					resultVectorSource.addFeature(pointFeature);
				}
			}
			
			if (stepIndex === result.steps.length - 1) {
				var lonlat = stringToLonLat(step[2][step[2].length - 1]);
				var pointFeature = new ol.Feature({
					geometry: new ol.geom.Point(ol.proj.transform(lonlat, 'EPSG:4326', 'EPSG:3857'))
				})
				pointFeature.setStyle(new ol.style.Style({
					image: new ol.style.Icon({
						src: 'images/finish.png',
						anchor: [0.0, 1.0]
					})
				}));
				resultVectorSource.addFeature(pointFeature);
			}
		});
		map.getView().fitExtent(resultVectorSource.getExtent(), map.getSize());
	}
	
	/**
	 * Converts "lat,lon" array into coordinate object array.
	 * @return the converted Point array object
	 */
	function stringArrayToPointArray(textArray) {
		var lonlatArray = new Array();
		$.each(textArray, function(index, value) {
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
		map.getView().setCenter(ol.proj.transform(point, 'EPSG:4326', 'EPSG:3857'));
		map.getView().setZoom(regions[region].zoom);
	}
	
	/**
	 * Computes distance between two position (from http://www.movable-type.co.uk/scripts/latlong.html)
	 */
	function computeDistance(p1, p2) {
		var R = 6371; // km
		var p1Lat = p1[1] * Math.PI / 180;
		var p2Lat = p2[1] * Math.PI / 180;
		var dLat = (p2[1]-p1[1]) * Math.PI / 180;
		var dLon = (p2[0]-p1[0]) * Math.PI / 180;

		var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
				Math.sin(dLon/2) * Math.sin(dLon/2) *
		        Math.cos(p1Lat) * Math.cos(p2Lat);
		var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
		var d = R * c;
		return d;
	}
});

