<?php
//require dirname(__FILE__).'/OpticsPoint.php';
/**
 * Handles color distance for points expressed in RGB
 * Uses the CIEDE2000 Color Distance Formula
 * Reference including the test data found:
 * @link http://www.ece.rochester.edu/~gsharma/ciede2000/
 * @package phpOptics
 */
class OpticsColorPoint extends OpticsPoint{

	/**
	 * L Color
	 * @var float
	 */
	public $l;
	/**
	 * a Color
	 * @var float
	 */
	public $a;
	/**
	 * b Color
	 * @var float
	 */
	public $b;

	/**
	 *
	 * @param Optics $optics
	 * @param array $point array(r, g, b)
	 * @return OpticsColorPoint
	 */
	public function __construct(Optics &$optics, array $point){
		$this->rgbToCieLab($point);
		return parent::__construct($optics,$point);
	}

	/**
	 * Color Distance CeiDe2000
	 * Based on:
	 * @link http://www.ece.rochester.edu/~gsharma/ciede2000/ciede2000noteCRNA.pdf
	 * @param OpticsColorPoint $q
	 */
	public function distanceTo(OpticsPoint $q){

		//Check for 0 distance
		if($this->getPointDimension() === $q->getPointDimension()){
			return 0;
		}

		$kl = $kc = $kh = 1.0;

		$barL = ($this->l + $q->l) / 2.0;

		//(Numbers corrispond to http://www.ece.rochester.edu/~gsharma/ciede2000/ciede2000noteCRNA.pdf eq)
		//2
		$helperB1Sq = pow($this->b, 2);
		$helperB2Sq = pow($q->b, 2);
		$c1 = sqrt(pow($this->a, 2) + $helperB1Sq);
		$c2 = sqrt(pow($q->a, 2) + $helperB2Sq);
		//3
		$barC = ($c1 + $c2) / 2.0;
		//4
		$helperPow7 = sqrt(pow($barC, 7) / (pow($barC, 7) + 6103515625));
		$g = 0.5*(1 - $helperPow7);
		//5
		$primeA1 = (1+$g)*$this->a;
		$primeA2 = (1+$g)*$q->a;
		//6
		$primeC1 = sqrt(pow($primeA1, 2) + $helperB1Sq);
		$primeC2 = sqrt(pow($primeA2, 2) + $helperB2Sq);

		//7
		if($this->b === 0 && $primeA1 === 0){
			$primeH1 = 0;
		}
		else{
			$primeH1 = (atan2($this->b, $primeA1) + 2 * M_PI) * (180 / M_PI);
		}
		if($q->b === 0 && $primeA2 === 0){
			$primeH2 = 0;
		}
		else{
			$primeH2 = (atan2($q->b, $primeA2) + 2 * M_PI) * (180 / M_PI);
		}

		//8
		$deltaLPrime = $q->l - $this->l;
		//9
		$deltaCPrime = $primeC2 - $primeC1;
		//10
		$helperH = $primeH2 - $primeH1;
		if($primeC1 * $primeC2 === 0){
			$deltahPrime = 0;
		}
		else if(abs($helperH) <= 180){
			$deltahPrime = $helperH;
		}
		else if($helperH > 180){
			$deltahPrime = $helperH - 360.0;
		}
		else if($helperH < - 180){
			$deltahPrime = $helperH + 360.0;
		}
		else{
			throw new Exception('Invalid delta h\'');
		}
		//11
		$deltaHPrime = 2 * sqrt($primeC1 * $primeC2) * sin(($deltahPrime / 2.0) * (M_PI/180));

		//12
		$barLPrime = ($this->l + $q->l) / 2.0;
		//13
		$barCPrime = ($primeC1 + $primeC2) / 2.0;
		//14
		$helperH = abs($primeH1 - $primeH2);
		if($primeC1 * $primeC2 === 0){
			$barHPrime = $primeH1 + $primeH2;
		}
		else if($helperH <= 180){
			$barHPrime = ($primeH1 + $primeH2) / 2.0;
		}
		else if($helperH > 180 && ($primeH1 + $primeH2) < 360){
			$barHPrime = ($primeH1 + $primeH2 + 360) / 2.0;
		}
		else if($helperH > 180 && ($primeH1 + $primeH2) >= 360){
			$barHPrime = ($primeH1 + $primeH2 - 360) / 2.0;
		}
		else{
			throw new Exception('Invalid bar h\'');
		}
		//15
		$t = 1 - .17 * cos(($barHPrime - 30) * (M_PI/180)) + .24 * cos((2 * $barHPrime) * (M_PI/180)) + .32 * cos((3 * $barHPrime + 6) * (M_PI/180)) - .2 * cos((4 * $barHPrime - 63) * (M_PI/180));
		//16
		$deltaTheta = 30 * exp(-1 * pow((($barHPrime-275)/25), 2));
		//17
		$rc = 2 * $helperPow7;
		//18
		$slHelper = pow($barLPrime - 50, 2);
		$sl = 1 + ((0.015*$slHelper) / sqrt(20+$slHelper));
		//19
		$sc = 1 + 0.046*$barCPrime;
		//20
		$sh = 1 + 0.015*$barCPrime*$t;
		//21
		$rt = -1 * sin((2 * $deltaTheta) * (M_PI/180)) * $rc;

		//22
		$deltaESquared= pow($deltaLPrime / ($kl * $sl), 2) +
						pow($deltaCPrime / ($kc * $sc), 2) +
						pow($deltaHPrime / ($kh * $sh), 2) +
						($rt * ($deltaCPrime / ($kc * $sc)) * ($deltaHPrime / ($kh * $sh)));

		$deltaE = sqrt($deltaESquared);
		return $deltaE;
	}

	/**
	 * Convert the RGB to CieLab and save in $this->l, ->a, ->b
	 *
	 * Code converted from https://github.com/THEjoezack/ColorMine
	 *
	 * @param array $point array(r, g, b)
	 */
	public function rgbToCieLab($point){
		//rgb to xyz
		foreach($point as &$p){
			$p = $p/255;
			$p = ($p > 0.04045 ? pow(($p + 0.055)/1.055, 2.4) : $p/12.92)*100.0;
		}

		$r = $point[0];
		$g = $point[1];
		$b = $point[2];

		// Observer. = 2°, Illuminant = D65
		$x = $r*0.4124 + $g*0.3576 + $b*0.1805;
		$y = $r*0.2126 + $g*0.7152 + $b*0.0722;
		$z = $r*0.0193 + $g*0.1192 + $b*0.9505;


		//Now from xyz to lab
		$whiteX = 95.047;
		$whiteY = 100.000;
		$whiteZ = 108.883;

		$x = $x / $whiteX;
		$y = $y / $whiteY;
		$z = $z / $whiteZ;

		$epsilon = 216/24389;
		$kappa = 24389/27;

		$x = $x > $epsilon ? pow($x, 1/3) : ($kappa * $x + 16) / 116;
		$y = $y > $epsilon ? pow($y, 1/3) : ($kappa * $y + 16) / 116;
		$z = $z > $epsilon ? pow($z, 1/3) : ($kappa * $z + 16) / 116;

		$this->l = max(0, 116*$y-16);
		$this->a = 500*($x - $y);
		$this->b = 200*($y - $z);
		return array($this->l, $this->a, $this->b);
	}

}