<?php
namespace RoutesInfo\TrafficInterface;


/**
 * AirTrafficEmulation is a class for simulate the traffic of aircraft traffic.
 *
 * The class allows to simulate the traffic of aircraft traffic on
 * predetermined trajectories. Flight information is specified using JSON.
 *
 */
interface TrafficEmulationInterface
{
    /** Calculate the total distance in km of the route. */
    public function distance($number);


    /** Calculate the estimated time of arrival for the flight. */
    public function timeArrival($number);


    /** Calculate the distance in km for each section of the route. */
    public function partDistance($number, $n);


    /** Calculates arrival time at the waypoint. */
    public function partTimeArrival($number, $n);


    /**
     * Gives a list of current aircraft that are already in flight,
     * but have not yet reached the final point.
     */
    public function inAir(\DateTime $date = null);
}
