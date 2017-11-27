RoutesInfo
=============
#### AirTrafficEmulation is a class for simulate the traffic of aircraft traffic. The class allows to simulate the traffic of aircraft traffic on predetermined trajectories. Flight information is specified using JSON.
-------------------
### Methods:
- **Public methods:**

  - **AirTrafficEmulation**(\$routes_json, \$host, \$port) **->** Constructor.
  - **redisConnect**(\$host, \$port) **->** Set Redis connection.
  - **jsonParseAndAdd**(\$routes_json) **->** Parsing json string and add it to storage.
  - **addFlight**(\$fligth, \$insert) **->** Adding new flights in RAM-storage.
  - **distance**(\$fligth) **->** Calculate the total distance in km of the route.
  - **timeArrival**(\$fligth) **->** Calculate the estimated time of arrival for the flight.
  - **partTimeArrival**(\$fligth, \$n) **->** Calculates arrival time at the waypoint.
  - **inAir**(\$date) **->** Gives a list of current aircraft that are already in flight, but have not yet reached the final point.
- **Static methods:**
	- **AirTrafficEmulation::distanceCalculation**(\$point1, \$point2) **->** ***Static*** method for calculation of the distance from the coordinates of two points.

-------------------
### Example class usage:
```php
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

```

### Example cli usage:
```sh
$ src/routes_info-cli.php --help
Usage: routes_info-cli [OPTIONS] [ARGS...]
  -c <host:port>            Redis server hostname:port connection
                            (default: 127.0.0.1:6379).
  -j <json>                 Input data from file (default: null).
  -d <flight>               Calculate the total distance in km of the route.
  -t <flight>               Calculate the estimated time of arrival for the flight.
  -p <flight:section>       Calculates arrival time at the waypoint.
  -i <date>                 Gives a list of current aircraft that are already in flight,
                            but have not yet reached the final point.
  -h                        Output this help and exit.
  --connection <host:port>  Redis server hostname:port connection
                            (default: 127.0.0.1:6379).
  --json                    Input data from file (default: null).
  --distance <flight>       Calculate the total distance in km of the route.
  --time-arrival <flight>   Calculate the estimated time of arrival for the flight.
  --part-time-arrival <flight:section> Calculates arrival time at the waypoint.
  --in-air <date:time>      Gives a list of current aircraft that are already in flight,
                            but have not yet reached the final point.
  --help                    Output this help and exit.

  When no command is given, routes_info-cli starts in interactive mode.
  Type "help" in interactive mode for information on available commands
  and settings.

```

### Example interactive-cli usage:
```sh
$ src/routes_info-cli.php
Start interactive session...
ri-cli>>> help

Commands available from the prompt:

  connection (or c) <host> <port>  to Redis server hostname:port connection
                                   (default: 127.0.0.1:6379).
  json (or j) <path to .json file> to input data from file (default: null).
  distance (or d) <flight>         to calculate the total distance in km of the route.
  time-arrival (or t) <flight>     to calculate the estimated time of arrival for the flight.
  part-time-arrival (or p) <flight> <section> to calculates arrival time at the waypoint.
  in-air (or i) [<date> <time>]    to gives a list of current aircraft that are already in flight,
                                   but have not yet reached the final point.
  help (or h)                      to output this help.

```
