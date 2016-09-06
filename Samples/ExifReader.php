<?php
echo "<h1>uav.JPG</h1>";
$exif = exif_read_data('uav.JPG', 'IFD0');
echo $exif===false ? "<h2>No header data found.</h2>" : "<h2>Image contains headers</h2>";

$exif = exif_read_data('uav.JPG', 0, true);

$lon = getGpsHelper($exif["GPS"]["GPSLongitude"], $exif["GPS"]['GPSLongitudeRef']);
$lat = getGpsHelper($exif["GPS"]["GPSLatitude"], $exif["GPS"]['GPSLatitudeRef']);

echo "<h1>File Coordinates</h1>";
echo "<p> Lat: " . $lat . "<p>";
echo "<p> Long: " . $lon . "<p>";

/** These helper methods are documented from here 
http://stackoverflow.com/questions/2526304/php-extract-gps-exif-data
**/

function getGpsHelper($exifCoord, $hemi) {

    $degrees = count($exifCoord) > 0 ? gps2NumHelper($exifCoord[0]) : 0;
    $minutes = count($exifCoord) > 1 ? gps2NumHelper($exifCoord[1]) : 0;
    $seconds = count($exifCoord) > 2 ? gps2NumHelper($exifCoord[2]) : 0;

    $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;

    return $flip * ($degrees + $minutes / 60 + $seconds / 3600);

}

function gps2NumHelper($coordPart) {

    $parts = explode('/', $coordPart);

    if (count($parts) <= 0)
        return 0;

    if (count($parts) == 1)
        return $parts[0];

    return floatval($parts[0]) / floatval($parts[1]);
}