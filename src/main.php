<?php
require_once "vendor/autoload.php";

use RoutesInfo\Distance\AirTrafficEmulation;

/** @const EARTH_RADIUS Earth radius for distance calculation */
define('EARTH_RADIUS', 6372.795);
/** @const DATE_TIME_FORMAT datatime format for print */
define('DATE_TIME_FORMAT', 'Y-m-j H:i');


$json = '{"routes":{"FFFFFF":{"name":"Boeing 737-46Q(SF)","registration":"RA-89051","start":"2016-01-07 9:00","tr":[[33.55,33],[37,24],[43,45]],"speed":"375"},"IV4673":{"name":"Boeing 737-46Q(SF)","registration":"RA-42343","start":"2016-01-07 15:00","tr":[[60,53],[67,28],[75,53]],"speed":"382"}}}';

$insert = <<<'JSON'
{
    "name": "Boeing 737-46Q(SF)",
    "registration": "RA-89051",
    "start": "2016-01-07 9:00",
    "tr": [
        [33.55, 33],
        [37, 24],
        [43, 45]
    ],
    "speed": "555"
}
JSON;


// $json = json_decode($json, true);
// print_r($insert);
$r = new AirTrafficEmulation($json);
try {
    $r->addFlight("FV777", $insert);
    $r->addFlight("FV555", $insert);

    echo $r->partDistance("IV4673", 1) . PHP_EOL;
    echo $r->distance("IV4673") . PHP_EOL;
    echo $r->timeArrival("IV4673") . PHP_EOL;
    echo $r->partTimeArrival("IV4673", 1) . PHP_EOL;
    echo $r->partTimeArrival("FV555", 2) . PHP_EOL;
    $date = \DateTime::createFromFormat(DATE_TIME_FORMAT, '2016-01-07 11:00');
    // // echo 'date: ' . $date->format(DATE_TIME_FORMAT) . PHP_EOL;
    print_r($r->inAir($date)) . PHP_EOL;
    var_dump($r->inAir()) . PHP_EOL;
//     // $r->redisConnect();
//
} catch(\BadMethodCallException $e) {
    echo "Error" . '\n';
}
