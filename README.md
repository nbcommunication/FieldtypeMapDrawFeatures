# Draw Map Features Fieldtype
Stores a GeoJSON FeatureCollection and its bounds drawn on a MapLibre map.

## Overview
The main purpose of this module is to facilitate the capture, storage and retrieval of the following GeoJSON feature types: `Point`, `LineString`, `Polygon`, `MultiPoint`,  `MultiLineString`, and `MultiPolygon`.

It does this using the [MapLibre GL JS](https://maplibre.org/maplibre-gl-js-docs/api/) library to render the map, and the [mapbox-gl-draw](https://github.com/mapbox/mapbox-gl-draw) plugin to draw the features.

*Please note that MapLibre places longitude before latitude, and this format has been followed for consistency.*

## Installation
1. Download the [zip file](https://github.com/nbcommunication/FieldtypeMapDrawFeatures/archive/master.zip) at Github or clone the repo into your `site/modules` directory.
2. If you downloaded the zip file, extract it in your `sites/modules` directory.
3. In your admin, go to Modules > Refresh, then Modules > New, then click on the Install button for this module.

**ProcessWire >= 3.0.210 and PHP >= 8,1 are required to use this module.**

## Configuration
To configure this module, go to Modules > Configure > FieldtypeMapDrawFeatures.

### Style URL
The MapLibre JSON style for the map. This can either be a full URL or relative to the site root.

**This is required for the map to render. The style defines the visual appearance of the map. For more information please review the [Style Spec](https://maplibre.org/maplibre-style-spec/), the [Quickstart guide](https://maplibre.org/maplibre-gl-js-docs/api/#quickstart) and the [Examples](https://maplibre.org/maplibre-gl-js-docs/example/).**

## Map configuration
When creating a new MapDrawFeatures field, the map can be configured on the *Input* tab:

* `Default Longitude` - The longitude used for the map center if the page field has no value.
* `Default Latitude` - The latitude used for the map center if the page field has no value.
* `Minimum Zoom` - The minimum zoom used for the map.
* `Maximum Zoom` - The maximum zoom used for the map.
* `Default Zoom` - The zoom used for the map if the page field has no value.
* `Map Height` - The height of the map in pixels (256px-1024px).
* `Controls` - The draw controls to be used. [More information](https://github.com/mapbox/mapbox-gl-draw/blob/main/docs/API.md)
* `Default Colour` - The colour to be used for map features.

## Usage

### Page Editor
When using a MapDrawFeatures field, use the controls that you have defined to draw GeoJSON features on the map. These features will be stored alongside the bounds (SW, NE) of the features and the zoom value.

### Finding pages
```php
// Find pages with features
$hasFeatures = $pages->find('yourfieldname!=""');
```

```php
// Find pages with zoom greater than 2
$hasFeatures = $pages->find('yourfieldname.zoom>2');
```

### API

#### **getFeatures(**_string|array_ **$type**, _string|array_ **$lnglat)**
Get features, optionally by type. Returns an `array` of features.

The `type` can be any one of the following values:
- Point
- LineString
- Polygon
- MultiPoint
- MultiLineString
- MultiPolygon

You can also specify multiple types by passing an array or a pipe-delimited (|) string.

For more information on filtering by LngLat, please see Filtering by LngLat

```php

$lng = 0.0;
$lat = 0.0;
$lnglat = [$lng, $lat];

// Get all features
$MapDrawFeatures->getFeatures();

// Get all LineString features
$MapDrawFeatures->getFeatures('LineString');

// Get all Point and MultiPoint features
$MapDrawFeatures->getFeatures('Point|MultiPoint');
// Alternatively
$MapDrawFeatures->getFeatures(['Point', 'MultiPoint']);

// Get all features filtered by LngLat
$MapDrawFeatures->getFeatures($lnglat);

// Get all Polygon and MultiPolygon features filtered by LngLat
$MapDrawFeatures->getFeatures('Polygon|MultiPolygon', $lnglat);
// Alternatively
$MapDrawFeatures->getFeatures(['Polygon', 'MultiPolygon'], $lnglat);

```




### Filtering by LngLat

The API methods provided have an experimental feature that allows you to filter by a LatLng value. This can either by a comma-delimited string, an indexed array or an array with 'lng' and 'lat' keys.

- **Points**: A feature will be returned if it matches the LngLat value to a 4 decimal point precision.
- **LineStrings** A feature will be returned if the LngLat value is a point on the line, again to a 4 decimal point precision. (todo)
- **Polygons** A feature will be returned if the LngLat value is a point within the area. This will only work for simple polygons.
