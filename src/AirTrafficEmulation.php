<?php
namespace RoutesInfo\Distance;

use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;

use Predis\Autoloader;
use Predis\Client;


/**
 * AirTrafficEmulation is a class for simulate the traffic of aircraft traffic.
 *
 * The class allows to simulate the traffic of aircraft traffic on
 * predetermined trajectories. Flight information is specified using JSON.
 *
 * Example usage:
 * $json = file_get_contents('./routes_info.json');
 * $air_traffic_emul = new AirTrafficEmulation($json);
 *
 * $insert = <<<'JSON'
 * {
 *     "name": "Boeing 737-46Q(SF)",
 *     "registration": "RA-89051",
 *     "start": "2016-01-07 9:00",
 *     "tr": [
 *         [33.55, 33],
 *         [37, 24],
 *         [43, 45]
 *     ],
 *     "speed": "555"
 * }
 * JSON;
 *
 * echo $air_traffic_emul->addFlight("FV777", $insert);
 * echo $air_traffic_emul->distance("FV777") . PHP_EOL;
 * echo $air_traffic_emul->timeArrival("FV777") . PHP_EOL;
 * echo $air_traffic_emul->partTimeArrival("FV777", 1) . PHP_EOL;
 * echo $air_traffic_emul->partTimeArrival("FV777", 0) . PHP_EOL;
 * print_r($air_traffic_emul->inAir($date)) . PHP_EOL;
 *
 * Static method call.
 * $point1 = [33, 33]; // [lat, long]
 * $point2 = [37, 24]; // [lat, long]
 * echo AirTrafficEmulation::distanceCalculation($point1, $point2) . PHP_EOL;
 *
 * @package  RoutesInfo
 * @access   public
 */
class AirTrafficEmulation
{
    /** @const EARTH_RADIUS Earth radius for distance calculation */
    const EARTH_RADIUS = 6372.795;
    /** @const DATE_TIME_FORMAT datatime format for print */
    const DATE_TIME_FORMAT = 'Y-m-j H:i';

    /**
     * Calculation of the distance from the coordinates of two points.
     *
     * @static
     * @access public
     *
     * @param array $point1
     * @param array $point2
     *
     * @return float
     */
    public static function distanceCalculation($point1, $point2)
    {
        $lat1 = $point1[0] * M_PI / 180; // To radians
        $long1 = $point1[1] * M_PI / 180;

        $lat2 = $point2[0] * M_PI / 180;
        $long2 = $point2[1] * M_PI / 180;

        $distance = acos(sin($lat1) * sin($lat2) +
                         cos($lat1) * cos($lat2) *
                         cos($long1 - $long2)) * self::EARTH_RADIUS;
        return round($distance, 2);
    }


    /** @var Predis\Client used to connect to redis */
    private $client;
    /** @var JsonSchema\Validator used to validate user-added data */
    private $validator;
    /**
     * @var array It is used to store the schema necessary for the validation
     * of data added by the user
     *
     */
    private $jsonSchema;


    /**
     * Construct the class, initializes redis storage and JSON validator.
     *
     * @access public
     *
     * @param string|array|null  $routes_json
     * @param string             $host
     * @param integer            $port
     *
     * @uses AirTrafficEmulation::redisConnect() to initialize redis storage
     * @uses JsonSchema\Validator to validate user-added data
     * @uses AirTrafficEmulation::$client to set data in storage
     * @uses AirTrafficEmulation::jsonParseAndAdd() to parse and add fligth
     *
     * @throws \BadMethodCallException if $routes_json could not be decoded
     * @throws Predis\Connection\ConnectionException
     * if the database is not connected
     *
     * @return void
     */
    function __construct($routes_json=null, $host='127.0.0.1', $port=6379)
    {
        $this->redisConnect($host, $port);

        $this->validator = new Validator;

        $this->jsonSchema = [
            "type" => "array",
            "additionalProperties" => false,
            "properties" => [
                "name" => [
                    "type" => "string",
                    "required" => true
                ],
                "registration" => [
                    "type" => "string",
                    "required" => true
                ],
                "start" => [
                    "type" => "string",
                    "required" => true
                ],
                "tr" => [
                    "type" => "array",
                    "required" => true,
                    "points" => [
                        "type" => "array",
                        "point" => [
                            "type" => "array"
                        ]
                    ]
                ],
                "speed" => [
                    "type" => "string",
                    "required" => true
                ]
            ]
        ];

        $this->jsonParseAndAdd($routes_json);
    }

    /**
     * Initialize redis storage.
     *
     * @access public
     *
     * @param string  $host
     * @param integer $port
     *
     * @uses Predis\Client to connect to redis
     *
     * @throws Predis\Connection\ConnectionException
     * if the database is not connected
     *
     * @return void
     */
    public function redisConnect($host, $port)
    {
        $this->client = new Client([
            'scheme' => 'tcp',
            'host'   => $host,
            'port'   => $port,
        ]);
    }

    /**
     * Parsing json string and add it to storage.
     *
     * @access public
     *
     * @param string|array|null $json
     *
     * @return void
     */
    public function jsonParseAndAdd($routes_json)
    {
        if (!is_null($routes_json)) {
            if (!is_array($routes_json)) {
                $routes = json_decode($routes_json, true);
            }
            else {
                $routes = $routes_json;
            }
            if (is_null($routes) || !isset($routes["routes"])) {
                throw new \BadMethodCallException(
                    "Invalid argument of constructor"
                );
            }

            foreach ($routes["routes"] as $key => $value) {
                $this->addFlight($key, $value);
            }
        }
    }

    /**
     * Calculate the total distance in km of the route.
     *
     * @access public
     *
     * @param string  $number flight number
     *
     * @uses AirTrafficEmulation::$client to get data from storage
     *
     * @return float
     */
    public function distance($number)
    {
        $data = json_decode($this->client->hget("routes", $number), true);

        if (isset($data)) {
            $tr = array_reverse($tr = $data["tr"]);
        }
        else {
            throw new \BadMethodCallException("Invalid argument of method");
        }
        $total_distance = 0;

        for ($i = 0; $i < count($tr)-1; $i++) {
            // Coordinates of the first point
            $lat1 = $tr[$i][0];
            $long1 = $tr[$i][1];

            // Coordinates of the second point
            $lat2 = $tr[$i+1][0];
            $long2 = $tr[$i+1][1];

            $point1 = [$lat1, $long1];
            $point2 = [$lat2, $long2];

            $distance = self::distanceCalculation($point1, $point2);

            $total_distance += $distance;
        }

        return $total_distance;
    }

    /**
     * Calculate the distance in km for each section of the route.
     *
     * @access public
     *
     * @param string  $number flight number
     * @param integer $n is number of the section between the points n-1 and n
     *
     * @uses AirTrafficEmulation::check() to check input arguments
     *
     * @return float
     */
    public function partDistance($number, $n)
    {
        $tr = $this->check($number, $n);
        // Coordinates of the first point
        $lat1 = $tr[$n-1][0];
        $long1 = $tr[$n-1][1];

        // Coordinates of the second point
        $lat2 = $tr[$n][0];
        $long2 = $tr[$n][1];

        $point1 = [$lat1, $long1];
        $point2 = [$lat2, $long2];

        // Distance calculation
        $distance = self::distanceCalculation($point1, $point2);

        return $distance;
    }

    /**
     * Receiving and prepare data for calculation.
     *
     * @access public
     *
     * @param string $number flight number
     *
     * @uses AirTrafficEmulation::$client to get data from storage
     *
     * @return array
     */
    private function dateTimePreparation($number)
    {
        // Receiving data for calculation
        $data = json_decode($this->client->hget("routes", $number), true);
        $date_time = $data["start"];
        $departure_time = \DateTime::createFromFormat(self::DATE_TIME_FORMAT,
                                                      $date_time);
        $flight_speed = (int)$data["speed"];

        $preparation_data = ['departure_time' => $departure_time,
                             'flight_speed'   => $flight_speed
        ];

        return $preparation_data;
    }

    /**
     * Calculate the estimated time of arrival for the flight.
     *
     * @access public
     *
     * @param string $number flight number
     *
     * @uses AirTrafficEmulation::dateTimePreparation() to prepare inpud args
     * @uses AirTrafficEmulation::distance() to arrival time calculation
     *
     * @return string
     */
    public function timeArrival($number)
    {
        // Receiving data for calculation
        $prep_data = $this->dateTimePreparation($number);

        // Arrival time calculation
        $flight_distance = $this->distance($number);

        $flight_time_hours = round($flight_distance /
                                   $prep_data['flight_speed']);

        $interval = new \DateInterval('PT'.$flight_time_hours.'H');
        $arrival_time = $prep_data['departure_time']->add($interval);

        return $arrival_time->format(self::DATE_TIME_FORMAT);
    }

    /**
     * Calculates arrival time at the waypoint.
     *
     * @access public
     *
     * @param string  $number flight number
     * @param integer $n is number of the section between the points n-1 and n
     *
     * @uses AirTrafficEmulation::dateTimePreparation() to prepare inpud args
     * @uses AirTrafficEmulation::partDistance() to calculation part of
     * user input distance
     *
     * @return string
     */
    public function partTimeArrival($number, $n)
    {
        // Receiving data for calculation
        $prep_data = $this->dateTimePreparation($number);

        // Arrival time calculation
        $flight_distance_parts = 0;
        for ($i = $n; $i > 0; $i--) {
            $flight_distance_parts += $this->partDistance($number, $i);
        }

        $flight_time_hours = round($flight_distance_parts /
                                   $prep_data['flight_speed']);

        $interval = new \DateInterval('PT'.$flight_time_hours.'H');
        $arrival_time = $prep_data['departure_time']->add($interval);

        return $arrival_time->format(self::DATE_TIME_FORMAT);
    }

    /**
     * Gives a list of current aircraft that are already in flight,
     * but have not yet reached the final point.
     *
     * @access public
     *
     * @param \DateTime|null $date
     *
     * @uses AirTrafficEmulation::$client to get data from storage
     *
     * @return array
     */
    public function inAir(\DateTime $date = null)
    {
        if (is_null($date)) {
            $date = new \DateTime();
        }
        // echo '$date > ' . $date->format(self::DATE_TIME_FORMAT) . PHP_EOL;

        $flights = $this->client->hgetall("routes");

        $in_air = array();

        foreach (array_keys($flights) as $flight) {
            // echo $flight . PHP_EOL;
            $time = json_decode($this->client->hget("routes", $flight), true);
            $start_time = \DateTime::createFromFormat(self::DATE_TIME_FORMAT,
                                $time["start"]);
            $arrival_time = \DateTime::createFromFormat(self::DATE_TIME_FORMAT,
                                $this->timeArrival($flight));
            // echo '$start_time > ' . $start_time->format(self::DATE_TIME_FORMAT) . PHP_EOL;
            // echo '$arrival_time > ' . $arrival_time->format(self::DATE_TIME_FORMAT) . PHP_EOL;

            if (($arrival_time > $date) && ($date > $start_time)) {
                $in_air[$flight] = json_decode($flights[$flight], true);
            }
        }

        return $in_air;
    }

    /**
     * Adding new flights in RAM-storage.
     *
     * @access public
     *
     * @param string       $number
     * @param string|array $data
     *
     * @uses AirTrafficEmulation::jsonValidate() to validate input arguments
     * @uses AirTrafficEmulation::$client to set data in storage
     *
     * @return void
     */
    public function addFlight($number, $data)
    {
        $insert = $this->jsonValidate($data);
        // print_r($insert);
        $insert = json_encode($insert);
        $this->client->hset("routes", $number, $insert);
    }


    /**
     * Destruct the class, disconnection from redis storage.
     *
     * @access public
     *
     * @uses AirTrafficEmulation::$client to disconnect from storage
     *
     * @return void
     */
    function __destruct()
    {
        $this->client->disconnect();
    }


    /**
     * Construct the class, initializes redis storage and JSON validator.
     *
     * @access private
     *
     * @param string|array  $routes_json
     * @param string        $host
     * @param integer       $port
     *
     * @uses AirTrafficEmulation::$validator to validation data
     *
     * @throws \BadMethodCallException if $routes_json could not be decoded
     *
     * @return array
     */
    private function jsonValidate($data)
    {
        if (!is_array($data)) {
            $data = json_decode($data, true);
        }

        $this->validator->validate(
            $data,
            $this->jsonSchema,
            Constraint::CHECK_MODE_NORMAL
        );

        if ($this->validator->isValid()) {
            echo "JSON validates OK\n";
            return $data;
        }
        else {
            echo "JSON validation errors:\n";
            foreach ($this->validator->getErrors() as $error) {
                print_r($error);
            }
            throw new \BadMethodCallException(
                "Invalid argument of constructor"
            );
        }
    }

    /**
     * Checking method arguments.
     *
     * @access private
     *
     * @param string  $number flight number
     * @param integer $n is number of the section between the points n-1 and n
     *
     * @uses AirTrafficEmulation::$client to get data from storage
     *
     * @throws \BadMethodCallException if $routes_json could not be decoded
     *
     * @return array
     */
    private function check($number, $n)
    {
        $data = json_decode($this->client->hget("routes", $number), true);

        if ((($n >= 0) && ($n-1 >= 0)) &&
            (isset($data)) &&
            $n < count($data["tr"]))
        {
            return $data["tr"];
        }
        else {
            throw new \BadMethodCallException("Invalid argument of method");
        }
    }
}
