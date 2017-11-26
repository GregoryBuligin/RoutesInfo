<?php
require_once "vendor/autoload.php";

use RoutesInfo\Distance\AirTrafficEmulation;

/** @const DATE_TIME_FORMAT datatime format for print */
define('DATE_TIME_FORMAT', 'Y-m-j H:i');

// Or load from file
$json = <<<'JSON'
{
    "routes": {
        "FFFFFF": {
            "name": "Boeing 737-46Q(SF)",
            "registration": "RA-89051",
            "start": "2016-01-07 9:00",
            "tr": [
                [33.55, 33],
                [37, 24],
                [43, 45]
            ],
            "speed" :"375"
        },
        "IV4673": {
            "name": "Boeing 737-46Q(SF)",
            "registration": "RA-42343",
            "start": "2016-01-07 15:00",
            "tr": [
                [60, 53],
                [67, 28],
                [75, 53]
            ],
            "speed": "382"
        }
    }
}
JSON;

// Or load from file
$insert = <<<'JSON'
{
    "name": "Boeing 737-46Q(SF)",
    "registration": "RA-89051",
    "start": "2016-01-07 9:00",
    "tr": [
        [33, 33],
        [37, 24],
        [43, 45]
    ],
    "speed": "555"
}
JSON;

// To array
// $json = json_decode($json, true);

$air_traffic_emul = new AirTrafficEmulation($json);
try {
    // Public methods calls.
    // $air_traffic_emul->redisConnect();
    $air_traffic_emul->addFlight("FV777", $insert);
    $air_traffic_emul->addFlight("FV555", $insert);

    echo $air_traffic_emul->partDistance("FV777", 1) . PHP_EOL;
    echo $air_traffic_emul->distance("IV4673") . PHP_EOL;
    echo $air_traffic_emul->timeArrival("IV4673") . PHP_EOL;
    echo $air_traffic_emul->partTimeArrival("IV4673", 1) . PHP_EOL;
    echo $air_traffic_emul->partTimeArrival("FV555", 2) . PHP_EOL;
    $date = \DateTime::createFromFormat(DATE_TIME_FORMAT, '2016-01-07 11:00');
    // echo 'date: ' . $date->format(DATE_TIME_FORMAT) . PHP_EOL;
    print_r($air_traffic_emul->inAir($date)) . PHP_EOL;
    print_r($air_traffic_emul->inAir()) . PHP_EOL;

    // Static method call.
    $point1 = [33, 33]; // [lat, long]
    $point2 = [37, 24]; // [lat, long]
    echo AirTrafficEmulation::distanceCalculation($point1, $point2) . PHP_EOL;

} catch(\BadMethodCallException $e) {
    echo "Error" . PHP_EOL;
}
