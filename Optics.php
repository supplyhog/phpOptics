<?php
/**
 * A PHP implimentation of the OPTICS Cluster Ordering Algorithm
 *
 * With help from
 * http://en.wikipedia.org/wiki/OPTICS_algorithm
 * OPTICS: Ordering Points To Identify the Clustering Structure
 * By Mihael Ankerst, Markus M. Breunig, Hans-Peter Kriegel, J&g Sander
 * @link http://www.cs.uiuc.edu/class/fa05/cs591han/papers/ankerst99.pdf
 *
 * @author Wil Wade <wil@supplyhog.com>
 * @license MIT http://opensource.org/licenses/MIT
 * @copyright (c) 2012, SupplyHog Inc.
 * @package phpOptics
 *
 */

//Require all the phpOptics Files
require_once dirname(__FILE__).'/OpticsCluster.php';
require_once dirname(__FILE__).'/OpticsPoint.php';
require_once dirname(__FILE__).'/OpticsPointNeighbor.php';
require_once dirname(__FILE__).'/OpticsColorPoint.php';
require_once dirname(__FILE__).'/OpticsMapMilePoint.php';

/**
 * Optics
 * @package phpOptics
 */
class Optics {

	/**
	 * Points ordered.
	 * @var OpticsPoint[]
	 */
	private $_pointsOrdered = array();

	/**
	 * All Points.
	 * @var OpticsPoint[]
	 */
	private $_points = array();

	/**
	 * All unprocessed Points
	 * @var OpticsPoint[]
	 */
	private $_unprocessedPoints = array();

	/**
	 * Points waiting in line to be processed
	 * @var OpticsPoint[]
	 */
	private $_seeds = array();

	/**
	 * Placeholder for the count of points
	 * @var int
	 */
	private $_nPoints = 0;

	/**
	 * The clusters
	 * @var OpticsCluster[]
	 */
	private $_clusters = array();

	/**
	 * Placeholder for the count of clusters
	 * @var int
	 */
	private $_nClusters = 0;

	/**
	 * Number of dimensions to each point. Default: 2
	 * @var int
	 */
	private $_pointDimensions = 2;

	/**
	 * Maximum distance away
	 * @var float
	 */
	public $epsilonMaxRadius;

	/**
	 * Minimumn number of points to make a neighborhood
	 * @var int
	 */
	public $minPoints;

	/**
	 *
	 * @var Class of the Point to use. Defaults to Euclidean Distance
	 */
	private $_pointClass = 'OpticsPoint';

	/**
	 *
	 * @param float $epsilonMaxRadius
	 * @param int $minPoints
	 * @param int $pointDimensions
	 */
	public function __construct($epsilonMaxRadius, $minPoints, $pointDimensions = 2, $pointClass = 'OpticsPoint'){
		$this->epsilonMaxRadius = $epsilonMaxRadius;
		$this->minPoints = $minPoints;
		$this->_pointDimensions = (int)$pointDimensions;
		$this->_pointClass = $pointClass;
	}

	/**
	 * Get point as an array
	 * @return array
	 */
	public function getPointDimensions(){
		return $this->_pointDimensions;
	}

	/**
	 *
	 * @param array $point array(x, y, ...)
	 * @throws Exception
	 */
	public function addDataPoint(array $point){
		if(count($point) !== $this->_pointDimensions){
			throw new Exception('Invalid data point');
		}
		$this->_points[] = new $this->_pointClass($this, $point);
	}

	/**
	 *
	 * @param array $bulkPoints array(array(x, y,...),...)
	 */
	public function addDataPoints(array $bulkPoints){
		foreach($bulkPoints as $point){
			$this->addDataPoint($point);
		}
	}

	/**
	 * Run on the current data and return the points ordered
	 *
	 * @return OpticsPoint[]
	 * @throws Exception
	 */
	public function run(){
		$this->_nPoints = count($this->_points);
		$this->_unprocessedPoints = $this->_points;
		foreach($this->_points as $i => $p){
			$p->index = $i;
		}

		$next = $this->_points[0];

		while($next !== NULL){
			$next = $this->expandClusterOrder($next);
			if($next === NULL){
				$next = $this->findNextUnprocessed();
			}
		}

		//Get that order information into each point model
		foreach($this->_pointsOrdered as $order => $p){
			$p->order = $order;
		}
		$this->resetProcessed();
		return $this->_pointsOrdered;
	}

	/**
	 * Order the point with respect to the other points
	 * @param OpticsPoint $p
	 */
	public function expandClusterOrder(OpticsPoint $p){
		$this->processPoint($p);
		$p->epsilonNeighborhood();
		$p->setCoreDistance();
		$p->setReachabilityDistance();
		$next = NULL;
		foreach($p->getNeighbors() as $n){
			if($next === NULL && !$n->point->processed){
				$next = $n->point;
			}
			//Do not reseed if already seeded
			else if(!$n->point->seeded){
				$n->point->seeded = TRUE;
				$this->_seeds[] = $n->point;
			}
		}

		return $next;

	}

	/**
	 * Add another to the Cluster List
	 * @param OpticsCluster $cluster
	 */
	public function addNewCluster(OpticsCluster $cluster){
		$this->_clusters[] = $cluster;
		$this->_nClusters++;
		if($this->_nClusters === 1001){
			throw new Exception('Over 1000 Clusters Found! I think you need to tweak your epsilon and min points.');
		}
	}

	/**
	 * Find the first point possible that has not been processed
	 * @return OpticsPoint[]
	 */
	public function findNextUnprocessed(){
		while($p = array_shift($this->_seeds)){
			if(!$p->processed){
				return $p;
			}
		}

		foreach($this->getUnprocessedPoints() as $p){
				return $p;
		}
		return NULL;
	}

	/**
	 * Handles marking as processed
	 * @param OpticsPoint $p
	 */
	public function processPoint(OpticsPoint $p){
		$p->processed = TRUE;
		$p->seeded = TRUE;
		$this->_pointsOrdered[] = $p;
		unset($this->_unprocessedPoints[$p->index]);
	}

	/**
	 * Get the array of all the OpticsPoints
	 * @return OpticsPoint[]
	 */
	public function getPoints(){
		return $this->_points;
	}

	/**
	 * The Unprocessed Points
	 * @return OpticsPoint[]
	 */
	public function getUnprocessedPoints(){
		return $this->_unprocessedPoints;
	}

	/**
	 * Get the ordered array of OpticsPoints
	 * @return OpticsPoint[]
	 */
	public function getPointsOrdered(){
		return $this->_pointsOrdered;
	}

	/**
	 * Set all the points to processed FALSE
	 */
	public function resetProcessed(){
		foreach($this->_points as $p){
			$p->processed = FALSE;
		}
	}

	/**
	 * Get all those clusters
	 * @return OpticsCluster[]
	 */
	public function getClusters(){
		return $this->_clusters;
	}

	/**
	 * Reset the clusters
	 */
	public function resetClusters(){
		$this->_clusters = array();
	}

	/**
	 * Get the StdDev of all the Core Distances
	 * @return float
	 */
	public function getStandardDevOfCoreDistance(){
		$cds = array();
		foreach($this->_points as $p){
			if($p->getCoreDistance() !== NULL){
				$cds[] = $p->getCoreDistance();
			}
		}
		$countCds = count($cds);
		$fMean = array_sum($cds) / $countCds;
		$fVariance = 0.0;
		foreach ($cds as $i)
		{
			$fVariance += pow($i - $fMean, 2);
		}
		$fVariance /= $countCds;
		return (float) sqrt($fVariance);
	}

}