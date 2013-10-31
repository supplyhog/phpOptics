<?php
/**
 * Handles distances on Earth in Miles
 * @package phpOptics
 */
class OpticsMapMilePoint extends OpticsPoint{

	/**
	 * Haversine (Earth, Miles)
	 * Point should be array(Lat, Long)
	 * @link en.wikipedia.org/wiki/Haversine_formula
	 * @param OpticsSpherePoint $q
	 */
	public function distanceTo(OpticsPoint $q){
		//Check for 0 distance
		$first = $this->getPointDimension();
		$second = $q->getPointDimension();
		if($first === $second){
			return 0;
		}

		$first[0] = 0.01745 * $first[0];
		$second[0] = 0.01745 * $second[0];

		$a = sin(($second[0] - $first[0]) / 2.0);
		$b = sin(0.01745 * ($second[1] - $first[1]) / 2.0);
		return 7926.3352 * asin(sqrt($a*$a + (cos($first[0]) * cos($second[0]) * $b * $b)));

	}

}