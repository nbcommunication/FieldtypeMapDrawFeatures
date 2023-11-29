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

**ProcessWire >= 3.0.210 and PHP >= 8.1 are required to use this module.**

## Configuration
To configure this module, go to Modules > Configure > FieldtypeMapDrawFeatures.

### Style URL
The MapLibre JSON style for the map. This can either be a full URL or relative to the site root.

**This is required for the map to render. The style defines the visual appearance of the map including the tiles that will be used. For more information please review the [Style Spec](https://maplibre.org/maplibre-style-spec/), the [Quickstart guide](https://maplibre.org/maplibre-gl-js-docs/api/#quickstart) and the [Examples](https://maplibre.org/maplibre-gl-js-docs/example/).**

## Map configuration
When creating a new `MapDrawFeatures` field, the map can be configured on the *Input* tab:

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
When using a `MapDrawFeatures` field, use the controls that you have defined to draw GeoJSON features on the map. These `features` will be stored alongside the bounds (`south`, `west`, `north`, `east`) of the features and the `zoom` value.

### Finding pages
```php
// Find pages with features
$hasFeatures = $pages->find('mapDrawFeatures!=""');

// Find pages with zoom greater than 2
$hasFeatures = $pages->find('mapDrawFeatures.zoom>2');

// Find pages with features inside a longitude boundary
$hasFeatures = $pages->find('mapDrawFeatures.west>-1.123456,mapDrawFeatures.east<1.123456');

// Find pages with features inside a latitude boundary
$hasFeatures = $pages->find('mapDrawFeatures.south>59.123456,mapDrawFeatures.north<60.123456');

// Find pages with features where the bounds contain the given LngLat
$hasFeatures = $pages->find('mapDrawFeatures="-1.123456,59.123456"');

// Find pages where a Point matches the given LngLat
$hasFeatures = $pages->find('mapDrawFeatures.Point="-1.123456,59.123456"');

// Find pages where a LineString matches the given LngLat
// Incomplete - currently only matches actual defined points on the LineString, not any possible point on the line
$hasFeatures = $pages->find('mapDrawFeatures.LineString="-1.123456,59.123456"');

// Find pages where the given LngLat is in a Polygon
$hasFeatures = $pages->find('mapDrawFeatures.Polygon="-1.123456,59.123456"');
```

### API Methods

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

For more information on filtering by LngLat, please see [Filtering by LngLat](#filtering-by-lnglat)

```php
$lng = -1.123456;
$lat = 59.123456;
$lnglat = [$lng, $lat];

// Get all features
$page->mapDrawFeatures->getFeatures();

// Get all LineString features
$page->mapDrawFeatures->getFeatures('LineString');

// Get all Point and MultiPoint features
$page->mapDrawFeatures->getFeatures('Point|MultiPoint');
// Alternatively
$page->mapDrawFeatures->getFeatures(['Point', 'MultiPoint']);

// Get all features filtered by LngLat
$page->mapDrawFeatures->getFeatures($lnglat);

// Get all Polygon and MultiPolygon features filtered by LngLat
$page->mapDrawFeatures->getFeatures('Polygon|MultiPolygon', $lnglat);
// Alternatively
$page->mapDrawFeatures->getFeatures(['Polygon', 'MultiPolygon'], $lnglat);
```

#### **getPoints(_string|array_ **$lnglat)**
A shortcut for `getFeatures('Point|MultiPoint')`
```php
$points = $page->mapDrawFeatures->getPoints();
```

#### **getLineStrings(_string|array_ **$lnglat)**
A shortcut for `getFeatures('LineString|MultiLineString')`
```php
$lineStrings = $page->mapDrawFeatures->getLineStrings();
```

#### **getPolygons(_string|array_ **$lnglat)**
A shortcut for `getFeatures('Polygon|MultiPolygon')`
```php
$polygons = $page->mapDrawFeatures->getPolygons();
```

#### **hasBounds()**
Does the MapDrawFeatures item have bounds? A quick way of checking whether features have been added.
```php
$hasBounds = $page->mapDrawFeatures->hasBounds();
```

### API Properties
```php
// Returns float values
$page->mapDrawFeatures->south;
$page->mapDrawFeatures->west;
$page->mapDrawFeatures->north;
$page->mapDrawFeatures->east;
$page->mapDrawFeatures->zoom;

// bounds: Returns a LngLatLikeBounds
$page->mapDrawFeatures->bounds; // [[west, south],[east,north]]

// center: Returns a LngLatLike of the center value of the bounds
$page->mapDrawFeatures->center; // [lng,lat]

// featureCollection: Returns the features as a FeatureCollection JSON string
$page->mapDrawFeatures->featureCollection;

// features: Returns the features as a JSON string
$page->mapDrawFeatures->features;

// points: Alias of getPoints()
$page->mapDrawFeatures->points;

// lineStrings: Alias of getLineStrings()
$page->mapDrawFeatures->lineStrings;

// polygons: Alias of getPolygons()
$page->mapDrawFeatures->polygons;
```


### Setting values via the API
In the event that you would need to import data via the API, the example below demonstrates how to do it. If you do not already have the bounds of the features you'll have to calulate that yourself.
```php
$page->of(false);
$page->mapDrawFeatures->features = '[{"id":"5097c25da917d3532b37c6ce57cb0ffe","type":"Feature","properties":{},"geometry":{"coordinates":[[[-1.2406185250501949,60.28562764582293],[-1.291868396313589,60.273431759804595],[-1.377558181066803,60.25573964627907],[-1.3414782716976106,60.241701168376096],[-1.27833843029984,60.234984986226266],[-1.2143785909624398,60.240480146752645],[-1.2086386053815374,60.26387513911979],[-1.2406185250501949,60.28562764582293]]],"type":"Polygon"}},{"id":"7d756bf0d747cd0ecb33c494ebcb159a","type":"Feature","properties":{},"geometry":{"coordinates":[[[-1.548937750573316,60.27160198465933],[-1.445618010105818,60.27038207770991],[-1.4546379874475974,60.242515157508166],[-1.522287817516201,60.23661327802367],[-1.5452477598420842,60.246991736195525],[-1.548937750573316,60.27160198465933]]],"type":"Polygon"}}]';
$page->mapDrawFeatures->bounds = '[[-1.644468,60.203217],[-1.136069,60.300865]]';
// The above is a shortcut for
// $page->mapDrawFeatures->west = -1.644468;
// $page->mapDrawFeatures->south = 60.203217;
// $page->mapDrawFeatures->east = -1.136069;
// $page->mapDrawFeatures->north = 60.300865;
$page->mapDrawFeatures->zoom = 12;
$page->save('mapDrawFeatures');

```

<a id="filtering-by-lnglat"></a>
### Filtering by LngLat

The API methods provided have a partially unfinished (and not fully tested) feature that allows you to filter by a LngLat value. This can either by a comma-delimited string, an indexed array or an array with 'lng' and 'lat' keys.

- **Points**: A feature will be returned if it matches the LngLat value to a 6 decimal point precision.
- **LineStrings** A feature will be returned if the LngLat value is a defined point on the line, again to a 6 decimal point precision. The hope is to develop this further so that any possible point on the line will match.
- **Polygons** A feature will be returned if the LngLat value is a point within the area. This will only work for simple polygons.
