<?php namespace ProcessWire;

/**
 * MapDrawFeatures Class
 *
 * @copyright 2023 NB Communication Ltd
 * @license Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 *
 * @property float $south
 * @property float $west
 * @property float $north
 * @property float $east
 * @property string $features
 * @property float $zoom
 *
 * @property string $featureCollection
 * @property array $points
 * @property array $point
 * @property array $lineStrings
 * @property array $lineString
 * @property array $polygons
 * @property array $polygon
 *
 * @method array getFeatures(string|array $type = '', array|string $lnglat = [])
 * @method array|null getFeature(string $type = '', int $index = 0)
 * @method array getPoints()
 * @method array|null getPoint(int $index = 0)
 * @method array getLineStrings()
 * @method array|null getLineString(int $index = 0)
 * @method array getPolygons()
 * @method array|null getPolygon(int $index = 0)
 * @method bool inPolygon(array $lnglat, array $polygon)
 * @method bool isPoint(array $lnglat, array $point)
 * @method bool onLineString(array $lnglat, array $lineString)
 *
 */
class MapDrawFeatures extends WireData {

	const ZERO = 0.0;
	const ZOOM = 12.0;

	public function __construct() {
		$this->set('south', self::ZERO);
		$this->set('west', self::ZERO);
		$this->set('north', self::ZERO);
		$this->set('east', self::ZERO);
		$this->set('features', '');
		$this->set('zoom', self::ZOOM);
	}

	/**
	 * Get
	 *
	 * @param string $key
	 * @return mixed
	 *
	 */
	public function get($key) {

		$value = null;

		switch($key) {
			case 'bounds':
				$value = [[$this->west, $this->south], [$this->east, $this->north]];
				break;
			case 'center':
				$value = [($this->west + $this->east) / 2, ($this->south + $this->north) / 2];
				break;
			case 'featureCollection':
				$value = json_encode([
					'type' => 'FeatureCollection',
					'features' => $this->featuresArray($this->features),
				]);
				break;
			case 'points':
				$value = $this->getPoints();
				break;
			case 'point':
				$value = $this->getPoint();
				break;
			case 'lineStrings':
				$value = $this->getLineStrings();
				break;
			case 'lineString':
				$value = $this->getLineString();
				break;
			case 'polygons':
				$value = $this->getPolygons();
				break;
			case 'polygon':
				$value = $this->getPolygon();
				break;
			default:
				$value = parent::get($key);
				break;
		}

		return $value;
	}

	/**
	 * Set
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 *
	 */
	public function set($key, $value) {
		switch($key) {
			case 'bounds':
				$south = 0;
				$west = 0;
				$north = 0;
				$east = 0;
				if(!is_array($value)) {
					$value = json_decode($value, 1);
				}
				if(is_array($value)) {
					$start = $value[0] ?? null;
					if(is_array($start)) {
						list($west, $south) = $value[0];
						list($east, $north) = $value[1];
					} else if(is_float($start)) {
						list($west, $south, $east, $north) = $value;
					}
				}
				if($south && $west && $north && $east) {
					$this->set('south', $south);
					$this->set('west', $west);
					$this->set('north', $north);
					$this->set('east', $east);
				}
				break;
			case 'features':
				$value = json_encode($this->featuresArray($value));
				break;
			case 'south':
			case 'west':
			case 'north':
			case 'east':
			case 'zoom':
				$value = (float) $value;
				break;
		}
		return parent::set($key, $value);
	}

	/**
	 * Get features, optionally by type
	 *
	 * @param string|array $type Point|LineString|Polygon|MultiPoint|MultiLineString|MultiPolygon
	 * @param array|string $lnglat EXPERIMENTAL
	 * @return array
	 *
	 */
	public function getFeatures($type = '', $lnglat = []) {

		if(is_array($type) && !is_string($type[0] ?? '')) {
			$lnglat = $type;
			$type = '';
		}

		$featuresArray = $this->featuresArray($this->features);

		$types = [];
		foreach(is_array($type) ? $type : explode('|', $type) as $t) {
			if(in_array($t, [
				'Point',
				'LineString',
				'Polygon',
				'MultiPoint',
				'MultiLineString',
				'MultiPolygon',
			])) {
				$types[] = strtolower($t);
			}
		}
		if(!count($types) && empty($lnglat)) {
			return $featuresArray;
		}

		$features = [];
		foreach($featuresArray as $feature) {

			$geometry = $feature['geometry'] ?? [];
			if(empty($geometry)) continue;

			// Is it the requested type?
			if(!count($types) || in_array(strtolower($geometry['type'] ?? ''), $types)) {

				// Attempt to match a given LngLat
				if(!empty($lnglat)) {

					if(is_string($lnglat)) {
						if(strpos($lnglat, 'lng') !== false) {
							$lnglat = json_decode($lnglat, 1);
							$lnglat = [$lnglat['lng'], $lnglat['lat']];
						} else {
							$lnglat = explode(',', str_replace(' ', '', $lnglat));
						}
					}

					if(is_array($lnglat)) {

						list($lng, $lat) = $lnglat;
						if($lng && $lat) {

							$lnglat = [$lng, $lat];
							$coordinates = $geometry['coordinates'];
							$matches = false;

							foreach($types as $t) {
								switch($t) {
									case 'point':
									case 'multipoint':
										if($this->isPoint($lnglat, $coordinates)) {
											$matches = true;
										}
										break;
									case 'linestring':
									case 'multilinestring':
										if($this->onLineString($lnglat, $coordinates)) {
											$matches = true;
										}
										break;
									case 'polygon':
									case 'multipolygon':
										if($this->inPolygon($lnglat, $coordinates)) {
											$matches = true;
										}
										break;
								}
							}

							if($matches) {
								$features[] = $feature;
							}
						}
					}
				} else {
					$features[] = $feature;
				}
			}
		}

		return $features;
	}

	/**
	 * Get a feature by type and index
	 *
	 * @param string $type Point|LineString|Polygon|MultiPoint|MultiLineString|MultiPolygon
	 * @param int $index
	 * @param string|array $lnglat
	 * @return array
	 *
	 */
	public function getFeature($type = '', int $index = 0, $lnglat = []) {
		return $this->getFeatures($type, $lnglat)[$index] ?? [];
	}

	/**
	 * Get Points
	 *
	 * @param string|array $lnglat
	 * @return array
	 *
	 */
	public function getPoints($lnglat = []) {
		return $this->getFeatures('Point', $lnglat);
	}

	/**
	 * Get a Point by index
	 *
	 * @param int $index
	 * @param string|array $lnglat
	 * @return array
	 *
	 */
	public function getPoint(int $index = 0, $lnglat = []) {
		return $this->getFeature('Point', $index, $lnglat);
	}

	/**
	 * Get LineStrings
	 *
	 * @param string|array $lnglat
	 * @return array
	 *
	 */
	public function getLineStrings($lnglat = []) {
		return $this->getFeatures('LineString', $lnglat);
	}

	/**
	 * Get a LineString by index
	 *
	 * @param int $index
	 * @param string|array $lnglat
	 * @return array
	 *
	 */
	public function getLineString(int $index = 0, $lnglat = []) {
		return $this->getFeature('LineString', $index, $lnglat);
	}

	/**
	 * Get Polygons
	 *
	 * @param string|array $lnglat
	 * @return array
	 *
	 */
	public function getPolygons($lnglat = []) {
		return $this->getFeatures('Polygon', $lnglat);
	}

	/**
	 * Get a Polygon by index
	 *
	 * @param int $index
	 * @param string|array $lnglat
	 * @return array
	 *
	 */
	public function getPolygon(int $index = 0, $lnglat = []) {
		return $this->getFeature('Polygon', $index, $lnglat);
	}

	/**
	 * Is the LngLat in the Polygon?
	 *
	 * @param array $lnglat
	 * @param array $polygon
	 * @return bool
	 *
	 */
	public function inPolygon(array $lnglat, array $polygon) {

		$in = false;

		if(isset($polygon[0][0]) && is_float($polygon[0][0])) {

			$x = $lnglat[1];
			$y = $lnglat[0];

			$len = count($polygon);
			for ($i = 0, $j = $len - 1; $i < $len; $j = $i++) {

				$xi = $polygon[$i][1];
				$yi = $polygon[$i][0];
				$xj = $polygon[$j][1];
				$yj = $polygon[$j][0];

				if ((($yi > $y) !== ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi)) {
					$in = !$in;
				}
			}

		} else if(
			isset($polygon[0][0][0]) && is_float($polygon[0][0][0]) || // Polygon
			isset($polygon[0][0][0][0]) && is_float($polygon[0][0][0][0]) // MultiPolygon
		) {

			foreach($polygon as $p) {
				if($this->inPolygon($lnglat, $p)) {
					$in = true;
				}
			}

		} else {

			// invalid?
			$this->log(json_encode($polygon, 1));
		}

		return $in;
	}

	/**
	 * Is the LngLat the Point?
	 *
	 * @param array $lnglat
	 * @param array $point
	 * @return bool
	 *
	 */
	public function isPoint(array $lnglat, array $point) {

		$_round = function($n) {
			return (string) round((float) $n, 6);
		};

		$is = false;

		if(is_array($point[0])) {

			// MultiPoint
			foreach($point as $p) {
				if($this->isPoint($lnglat, $p)) {
					$is = true;
				}
			}

		} else {

			$is = count($point) === 2 &&
				count($lnglat) === 2 &&
				$_round($point[0]) === $_round($lnglat[0]) &&
				$_round($point[1]) === $_round($lnglat[1]);
		}

		return $is;
	}

	/**
	 * Is the LngLat on the LineString?
	 *
	 * @param array $lnglat
	 * @param array $lineString
	 * @return bool
	 * @todo correct implementation that evaluates if the lnglat appears
	 * anywhere on the LineString, not just if it has the coordinate
	 *
	 */
	public function onLineString(array $lnglat, array $lineString) {

		$on = false;
		if(is_array($lineString[0][0])) {

			// MultiLineString
			foreach($lineString as $ls) {
				foreach($ls as $l) {
					if($this->isPoint($lnglat, $l)) {
						$on = true;
					}
				}
			}

		} else {

			foreach($lineString as $ls) {
				if($this->isPoint($lnglat, $ls)) {
					$on = true;
				}
			}
		}

		return $on;
	}

	/**
	 * Return the features correctly as an array
	 *
	 * Make empty arrays objects where required for correct JSON encoding
	 *
	 * @param string $features
	 * @return array
	 *
	 */
	private function featuresArray($features) {
		$features = json_decode($features, 1);
		if(is_array($features)) {
			$keys = ['properties', 'geometry'];
			foreach($features as $index => $feature) {
				foreach($keys as $key) {
					if(isset($feature[$key]) && is_array($feature[$key]) && empty($feature[$key])) {
						$feature[$key] = (object) $feature[$key];
					}
				}
				$features[$index] = $feature;
			}
		}
		return $features ?? [];
	}

	/**
	 * If accessed as a string output as JSON
	 *
	 */
	public function __toString() {
		return json_encode([
			'bounds' => $this->bounds,
			'features' => $this->featuresArray($this->features),
			'zoom' => $this->zoom,
		]);
	}
}
