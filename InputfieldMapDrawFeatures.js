

var InputfieldMapDrawFeatures = {

	init: function() {

		var options = ProcessWire.config.InputfieldMapDrawFeatures;
		var $map = $(this);
		var name = options.name;

		var $inputFeatures = $map.siblings('input[name="' + name + '"]');
		var $inputSouth = $map.siblings('input[name="_' + name + '_south"]');
		var $inputWest = $map.siblings('input[name="_' + name + '_west"]');
		var $inputNorth = $map.siblings('input[name="_' + name + '_north"]');
		var $inputEast = $map.siblings('input[name="_' + name + '_east"]');
		var $inputZoom = $map.siblings('input[name="_' + name + '_zoom"]');

		// Create the map
		var mapOptions = options.map;

		mapOptions.container = this;

		if (!('zoom' in mapOptions)) {
			mapOptions.zoom = parseFloat($inputZoom.val());
		}

		var map = new maplibregl.Map(mapOptions);

		map.dragRotate.disable(); // Disable map rotation using right click + drag.
		map.touchZoomRotate.disableRotation(); // Disable map rotation using touch rotation gesture.

		// Add navigation control (excluding compass button) to the map.
		map.addControl(new maplibregl.NavigationControl({
			showCompass: false
		}));

		// Draw features
		var draw;
		try {

			var drawOptions = options.draw;
			drawOptions.displayControlsDefault = false;

			draw = new MapboxDraw(drawOptions);
			map.addControl(draw);

			var features = $inputFeatures.val();
			if (features) {
				try {
					draw.add({
						type: 'FeatureCollection',
						features: JSON.parse(features),
					});
				} catch (e) {
					console.error(e);
				}
			}

			var south = parseFloat($inputSouth.val());
			var west = parseFloat($inputWest.val());
			var north = parseFloat($inputNorth.val());
			var east = parseFloat($inputEast.val());
			if (south && west && north && east) {
				map.fitBounds([[west, south], [east, north]]);
			}

			var updateArea = function() {

				$inputFeatures.val(JSON.stringify(draw.getAll().features));

				var bounds = map.getBounds();
				$inputSouth.val(bounds.getSouth());
				$inputWest.val(bounds.getWest());
				$inputNorth.val(bounds.getNorth());
				$inputEast.val(bounds.getEast());
				$inputZoom.val(map.getZoom());
			};

			map.on('draw.create', updateArea);
			map.on('draw.delete', updateArea);
			map.on('draw.update', updateArea);
			map.on('draw.combine', updateArea);
			map.on('draw.uncombine', updateArea);
			map.on('zoomend', function(e) {
				$inputZoom.val(e.target.getZoom());
			});

		} catch (e) {
			console.error(e);
		}

		var updateMap = function() {
			setTimeout(function() {
				map.resize();
			}, 128);
		};

		$map.closest('.Inputfield').on('opened', updateMap);
		$(document).on('wiretabclick', updateMap);
	}
};

$(document).ready(function() {
	$('.InputfieldMapDrawFeaturesMap').each(InputfieldMapDrawFeatures.init);
});
