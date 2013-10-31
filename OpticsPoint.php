<?php
/**
 * OpticsPoint
 * @package phpOptics
 */
class OpticsPoint{

	/**
	 * Array of the point data.
	 * i.e. array(x, y)
	 * @float[]
	 */
	private $_point = array();

	/**
	 * @Optics
	 */
	private $_optics;

	/**
	 * Core Distance
	 * @var float
	 */
	private $_coreDistance = NULL;

	/**
	 * Reachability Distance
	 * @var float
	 */
	public $reachabilityDistance = NULL;

	/**
	 * The order index
	 * @int
	 */
	public $order;

	/**
	 * A unique index based on the initial point list
	 * @int
	 */
	public $index;

	/**
	 * All the neighbors
	 * @OpticsPointNeighbor[]
	 */
	private $_epsilonNeighbors = array();

	/**
	 * Current neighbor count
	 * @int
	 */
	public $nEpsilonNeighbors = 0;

	/**
	 * Has this point been processed?
	 * @var bool
	 */
	public $processed = FALSE;

	/**
	 * Is this point in the Seed list?
	 * @var bool
	 */
	public $seeded = FALSE;

	/**
	 * Create a new Point
	 * @param Optics $cluster
	 * @param array $point Point data such as array(x, y)
	 */
	public function __construct(Optics &$optics, array $point){
		$this->_point = $point;
		$this->_optics = $optics;
		$this->_coreDistance = $optics->epsilonMaxRadius + 1;
	}

	/**
	 * Get the distance from this point to point $q
	 * @param OpticsPoint $q
	 * @return float
	 */
	public function distanceTo(OpticsPoint $q){
		$sumMe = array();
		for($i = $this->_optics->getPointDimensions() - 1; $i >= 0; $i--){
			$sumMe[] = pow($this->getPointDimension($i) - $q->getPointDimension($i), 2);
		}
		return sqrt(array_sum($sumMe));
	}

	/**
	 * Get all or with param a dimension of the point
	 * @param int $i
	 * @return float
	 * @throws Exception
	 */
	public function getPointDimension($i = NULL){
		if($i === NULL){
			return $this->_point;
		}
		if(!isset($this->_point[$i])){
			throw new Exception('Tried to access a non-existent dimension of a point.');
		}
		return $this->_point[$i];
	}

	/**
	 * Find all the neighbor points and sort them by distance
	 */
	public function epsilonNeighborhood(){
		foreach($this->_optics->getUnprocessedPoints() as $q){
			if($this->index === $q->index){
				continue; //You are not your own neighbor
			}
			if(($dist = $this->distanceTo($q)) <= $this->_optics->epsilonMaxRadius){
				$this->addNeighbor($q, $dist);
			}
		}
		//Sort neighbors by distance
		usort($this->_epsilonNeighbors, array('OpticsPoint', 'uSortPoint'));
	}

	/**
	 * Sort the OpticsPointNeighbors based on distance
	 * @param OpticsPointNeighbor $a
	 * @param OpticsPointNeighbor $b
	 * @return int
	 */
	public static function uSortPoint($a, $b){
		if($a->distance === $b->distance){
			return 0;
		}
		return ($a->distance < $b->distance) ? -1 : 1;
	}

	/**
	 * Add a neighbor
	 * @param OpticsPoint $point
	 * @param float $distance
	 */
	public function addNeighbor(OpticsPoint $point, $distance){
		$this->_epsilonNeighbors[] = new OpticsPointNeighbor($point, $distance);
		$this->nEpsilonNeighbors++;
	}

	/**
	 * Get the closest unprocessed
	 * @return OpticsPoint || null
	 */
	public function getClosestUnprocessedNeighbor(){
		foreach($this->_epsilonNeighbors as $n){
			if(!$n->point->processed){
				return $n-point;
			}
		}
		return NULL;
	}

	/**
	 *
	 * @return OpticsPointNeighbor[]
	 */
	public function getNeighbors(){
		return $this->_epsilonNeighbors;
	}

	/**
	 *
	 * @return float
	 */
	public function getCoreDistance(){
		return $this->_coreDistance;
	}

	/**
	 * Set the Core Distance for the point to be the minPoints neighbor
	 */
	public function setCoreDistance(){
		if($this->nEpsilonNeighbors < $this->_optics->minPoints){
			//Not enough neighbors
			$this->_coreDistance = NULL;
			foreach($this->_epsilonNeighbors as $n){
				$n->distance = NULL;
			}
		}
		else{
			$this->_coreDistance = $this->_epsilonNeighbors[$this->_optics->minPoints - 2]->distance;
		}
	}

	/**
	 * Set the Reachability Distance for each point
	 */
	public function setReachabilityDistance(){
		foreach($this->_epsilonNeighbors as $n){
			$rd = max(array($this->_coreDistance, $n->distance));
			if($n->point->processed){
				$n->point->reachabilityDistance = min(array($rd, $n->point->reachabilityDistance));
			}
			else if($n->point->reachabilityDistance === NULL || $rd < $n->point->reachabilityDistance){
				$n->point->reachabilityDistance = $rd;
			}
		}
	}

}
