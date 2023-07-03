

const InputfieldMapDrawFeatures = {

	init: function() {

		const options = ProcessWire.config.InputfieldMapDrawFeatures;
		const $map = $(this);
		const name = options.name;

		const $inputFeatures = $map.siblings('input[name="' + name + '"]');
		const $inputSouth = $map.siblings('input[name="_' + name + '_south"]');
		const $inputWest = $map.siblings('input[name="_' + name + '_west"]');
		const $inputNorth = $map.siblings('input[name="_' + name + '_north"]');
		const $inputEast = $map.siblings('input[name="_' + name + '_east"]');
		const $inputZoom = $map.siblings('input[name="_' + name + '_zoom"]');

		// Create the map
		const mapOptions = options.map;

		mapOptions.container = this;

		if (!('zoom' in mapOptions)) {
			mapOptions.zoom = parseFloat($inputZoom.val());
		}

		const map = new maplibregl.Map(mapOptions);

		map.dragRotate.disable(); // Disable map rotation using right click + drag.
		map.touchZoomRotate.disableRotation(); // Disable map rotation using touch rotation gesture.

		// Add navigation control (excluding compass button) to the map.
		map.addControl(new maplibregl.NavigationControl({
			showCompass: false
		}));

		// Draw features
		let draw;
		try {

			const drawOptions = options.draw;
			drawOptions.displayControlsDefault = false;

			draw = new MapboxDraw(drawOptions);
			map.addControl(draw);

			const features = $inputFeatures.val();
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

			const south = parseFloat($inputSouth.val());
			const west = parseFloat($inputWest.val());
			const north = parseFloat($inputNorth.val());
			const east = parseFloat($inputEast.val());
			if (south && west && north && east) {
				map.fitBounds([[west, south], [east, north]], {
					maxZoom: mapOptions.maxZoom - 2,
					padding: 40,
				});
			}

			const updateArea = () => {

				const features = draw.getAll().features;
				$inputFeatures.val(JSON.stringify(features));

				const bounds = new maplibregl.LngLatBounds();
				let coordinates;
				features.forEach(feature => {
					coordinates = feature.geometry.coordinates;
					if (Array.isArray(coordinates)) {
						if (Array.isArray(coordinates[0])) {
							coordinates.forEach(coordinate => {
								if (Array.isArray(coordinate[0])) {
									// Polygon
									coordinate.forEach(coord => bounds.extend(coord));
								} else {
									// Line
									bounds.extend(coordinate);
								}
							})
						} else {
							// Point
							bounds.extend(coordinates);
						}
					}
				});

				$inputSouth.val(bounds.getSouth());
				$inputWest.val(bounds.getWest());
				$inputNorth.val(bounds.getNorth());
				$inputEast.val(bounds.getEast());
				const zoom = map.getZoom();
				if (mapOptions.zoom !== zoom) {
					$inputZoom.val(zoom);
				}
			};

			map.on('draw.create', updateArea);
			map.on('draw.delete', updateArea);
			map.on('draw.update', updateArea);
			map.on('draw.combine', updateArea);
			map.on('draw.uncombine', updateArea);
			map.on('zoomend', e => $inputZoom.val(e.target.getZoom()));

		} catch (e) {
			console.error(e);
		}

		const updateMap = () => setTimeout(() => map.resize(), 128);

		$map.closest('.Inputfield').on('opened', updateMap);
		$(document).on('wiretabclick', updateMap);
	}
};

$(document).ready(function() {
	$('.InputfieldMapDrawFeaturesMap').each(InputfieldMapDrawFeatures.init);
});
