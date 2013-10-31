<?php
/**
 * OpticsCluster
 * @package phpOptics
 */
class OpticsCluster {
	/**
	 *
	 * @var Optics
	 */
	private $_optics;

	/**
	 *
	 * @var OpticsPoint[]
	 */
	public $points;

	/**
	 * Point Count Holder
	 * @var int
	 */
	public $nPoints;

	/**
	 *
	 * @param Optics $optics
	 * @param OpticsPoint[] $points
	 */
	public function __construct(Optics $optics, array $points){
		$this->_optics = $optics;
		$this->points = $points;
		$this->nPoints = count($points);
	}

	public function resetPointsProcessed(){
		foreach($this->points as $p){
			$p->processed = FALSE;
		}
	}


	/**
	 * Extract DBSCAN Results from the Optics Data
	 *
	 * @param Optics $optics
	 * @param float $clusterEpsilon
	 */
	public static function extractDBSCANClusters(Optics $optics, $clusterEpsilon){
		$currentClusterPoints = array();
		foreach($optics->getPointsOrdered() as $p){
			if($p->reachabilityDistance === NULL || $p->reachabilityDistance > $clusterEpsilon){
				if($p->getCoreDistance() !== NULL && $p->getCoreDistance() <= $clusterEpsilon){
					if(count($currentClusterPoints) > $optics->minPoints)
						$optics->addNewCluster(new OpticsCluster($optics, $currentClusterPoints));
					$currentClusterPoints = array($p);
				}
				//else Noise
			}
			else{
				$currentClusterPoints[] = $p;
			}
		}
		if(count($currentClusterPoints) > $optics->minPoints){
			$optics->addNewCluster(new OpticsCluster($optics, $currentClusterPoints));
		}
	}

	/**
	 * A simple Inflection based algorithm that takes variablility in data into account
	 * Data with high variablilty pulls out clusters with more variablity inside
	 * Data with lower variablility has a better chance of creating clusters
	 * @param Optics $optics
	 * @param float $clusterEpsilon
	 * @param float $minDelta
	 */
	public static function extractInflectionClusters(Optics $optics, $clusterEpsilon, $minDelta = 0.5){
		$avgRd = array();
		foreach($optics->getPointsOrdered() as $p){
			if($p->reachabilityDistance !== NULL)
				$avgRd[] = $p->reachabilityDistance;
		}
		$avgRd = array_sum($avgRd)/count($avgRd);
		$minDelta = $minDelta + $optics->getStandardDevOfCoreDistance()/10;
		$orderedPoints = array_reverse($optics->getPointsOrdered());
		$previousPoint = array_pop($orderedPoints);
		$p = array_pop($orderedPoints);
		$currentClusterPoints = array($previousPoint);
		$currentAvgRd = array();
		while($p){
			$next = array_pop($orderedPoints);
			$add = $newCluster = FALSE;
			if($p->reachabilityDistance === 0){
				$add = TRUE;
			}
			else if($p->reachabilityDistance === NULL){
				$newCluster = TRUE;
			}
			else if($p->reachabilityDistance > $clusterEpsilon){
				$newCluster = TRUE;
			}
			else if($previousPoint->reachabilityDistance + $minDelta > $p->reachabilityDistance){
				$add = TRUE;
			}
			else if($next && $next->reachabilityDistance + $minDelta > $p->reachabilityDistance){
				$add = TRUE;
			}
			else if(!empty($currentAvgRd) && (array_sum($currentAvgRd)/count($currentAvgRd)) < $avgRd - $minDelta){
				//Handle the cases in areas of low RD differently
				if($previousPoint->reachabilityDistance + $minDelta * 2 > $p->reachabilityDistance){
					$add = TRUE;
				}
				else if($next && $next->reachabilityDistance + $minDelta * 2 > $p->reachabilityDistance){
					$add = TRUE;
				}
				else{
					$newCluster = TRUE;
				}
			}
			else{
				$newCluster = TRUE;
			}

			if($add){
				$currentClusterPoints[] = $p;
				$currentAvgRd[] = $p->reachabilityDistance;
			}

			if($newCluster){
				if(count($currentClusterPoints) > $optics->minPoints)
					$optics->addNewCluster(new OpticsCluster($optics, $currentClusterPoints));
				$currentClusterPoints = array($p);
				$currentAvgRd = array();
			}

			$previousPoint = $p;
			$p = $next;
		}
		if(count($currentClusterPoints) > $optics->minPoints){
			$optics->addNewCluster(new OpticsCluster($optics, $currentClusterPoints));
		}
	}
}
