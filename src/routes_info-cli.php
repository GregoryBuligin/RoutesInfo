#!/usr/bin/env php
<?php
require_once "vendor/autoload.php";

use RoutesInfo\Distance\AirTrafficEmulation;
use Hoa\Console\Parser;

error_reporting(E_ERROR | E_WARNING | E_PARSE);

$help_msg = <<<'HELP'
Usage: AirTrafficEmulation-cli [OPTIONS] [args...]
  -c <host:port>            Redis server hostname:port connection
                            (default: 127.0.0.1:6379).
  -j <json>                 Input data from file (default: null).
  -d <flight>               Calculate the total distance in km of the route.
  -t <flight>               Calculate the estimated time of arrival for the flight.
  -p <flight:section>      Calculates arrival time at the waypoint.
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

HELP;

$print_help = false;
// definition of default values
$host = '127.0.0.1';
$port = 6379;
$json = null;
$flight = null;
$date = null;

if (!isset($argv[1])) {
    $air_traffic_emul = new AirTrafficEmulation($json, $host, $port);
    print "Start interactive session..." . PHP_EOL;
    $session = true;
    while ($session) {
        $line = readline('ri-cli>>> ');
        if ($line == 'q' or $line == 'quit' or $line == 'exit') {
            print "End interactive session..." . PHP_EOL;
            $session = false;
        }
        $line = explode(' ', $line);

        try {
            if ($line[0] == 'distance' ||  $line[0] == 'd') {
                $flight = $line[1];
                print $air_traffic_emul->distance(strtoupper($flight)) .
                    'km' . PHP_EOL;
            }
            else if  ($line[0] == 'time-arrival' || $line[0] == 't') {
                $flight = $line[1];
                print $air_traffic_emul->timeArrival(strtoupper($flight)) .
                                                                PHP_EOL;
            }
            else if  ($line[0] == 'part-time-arrival' || $line[0] == 'p') {
                print $air_traffic_emul->partTimeArrival(strtoupper($line[1]),
                                                         $line[2]).PHP_EOL;
            }
            else if  ($line[0] == 'in-air' || $line[0] == 'i') {
                if (isset($line[1])) {
                    $date_info = $line[1];
                    $time_info = $line[2];
                    $date = $date_info . ' ' . $time_info;
                    print $time_info;
                    $date = \DateTime::createFromFormat('Y-m-j H:i', $date);
                    if ($date) {
                        print json_encode($air_traffic_emul->inAir($date),
                                          JSON_PRETTY_PRINT) . PHP_EOL;
                    }
                    else {
                        throw new \BadMethodCallException("Invalid datetime");
                    }
                }
                else {
                    print json_encode($air_traffic_emul->inAir(),
                                      JSON_PRETTY_PRINT) . PHP_EOL;
                }
            }
            else if ($line[0] == 'help' || $line[0] == 'h') {
                print $help_msg . PHP_EOL;
            }
        } catch(\BadMethodCallException $e) {
            print "Unrecognized option or bad number of args: " .
                $e->getMessage() . PHP_EOL;
        }
    }
}
else {
    unset($argv[0]);
    $command = implode(' ', $argv);
    $parser = new Parser();
    $parser->parse($command);

    // Options definition
    $options = new Hoa\Console\GetOption(
        [
            ['connection',        Hoa\Console\GetOption::REQUIRED_ARGUMENT, 'c'],
            ['json',              Hoa\Console\GetOption::REQUIRED_ARGUMENT, 'j'],
            ['distance',          Hoa\Console\GetOption::REQUIRED_ARGUMENT, 'd'],
            ['time-arrival',      Hoa\Console\GetOption::REQUIRED_ARGUMENT, 't'],
            ['part-time-arrival', Hoa\Console\GetOption::REQUIRED_ARGUMENT, 'p'],
            ['in-air',            Hoa\Console\GetOption::OPTIONAL_ARGUMENT, 'i'],
            ['help',              Hoa\Console\GetOption::NO_ARGUMENT,       'h']
        ],
        $parser
    );


    $names = $parser->getInputs();

    try {
        // The following while with the switch will assign the values to the variables.
        while (false !== $shortName = $options->getOption($value)) {
            switch ($shortName) {
                case 'c':
                    $conn_info = explode(':', $value);
                    $host = $conn_info[0];
                    $port = $conn_info[1];
                    $air_traffic_emul = new AirTrafficEmulation($json, $host, $port);
                    break;
                case 'j':
                    $json = $value;
                    break;
                case 'd':
                    $air_traffic_emul = new AirTrafficEmulation($json, $host, $port);
                    $flight = $value;
                    print $air_traffic_emul->distance(strtoupper($flight)) . PHP_EOL;
                    break;
                case 't':
                    $air_traffic_emul = new AirTrafficEmulation($json, $host, $port);
                    $flight = $value;
                    print $air_traffic_emul->timeArrival(strtoupper($flight)).PHP_EOL;
                    break;
                case 'p':
                    $air_traffic_emul = new AirTrafficEmulation($json, $host, $port);
                    $flight_info = explode(':', $value);
                    print $air_traffic_emul->partTimeArrival(strtoupper($flight_info[0]),
                                                             $flight_info[1]).PHP_EOL;
                    break;
                case 'i':
                    $air_traffic_emul = new AirTrafficEmulation($json, $host, $port);
                    if ($value != 1) {
                        $date_info = $value;
                        $date_info = explode('/', $value);
                        $date = $date_info[0] . ' ' . $date_info[1];
                        $date = \DateTime::createFromFormat('Y-m-j H:i', $date);
                        if ($date) {
                            print json_encode($air_traffic_emul->inAir($date),
                                              JSON_PRETTY_PRINT) . PHP_EOL;
                        }
                        else {
                            throw new \BadMethodCallException("Invalid datetime");
                        }
                    }
                    else {
                        print json_encode($air_traffic_emul->inAir(),
                                          JSON_PRETTY_PRINT) . PHP_EOL;
                    }
                    break;
                case 'h':
                    $print_help = true;
                    break;
            }
        }
        if ($print_help) {
            print $help_msg . PHP_EOL;
        }
    } catch (\BadMethodCallException $e) {
        print "Unrecognized option or bad number of args" . PHP_EOL;
    }
}
