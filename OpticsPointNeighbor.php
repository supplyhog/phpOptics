<?php
/**
 * OpticsPointNeighbor
 * @package phpOptics
 */
class OpticsPointNeighbor {
	/**
	 *
	 * @var float
	 */
	public $distance;

	/**
	 *
	 * @var OpticsPoint
	 */
	public $point;

	/**
	 * Set the Neighbor data
	 * 
	 * @param OpticsPoint $point
	 * @param float $distance
	 */
	public function __construct(OpticsPoint $point, $distance){
		$this->point = $point;
		$this->distance = $distance;
	}
}