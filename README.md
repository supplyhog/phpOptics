phpOptics
=========

PHP Implementation of the OPTICS Clustering Algorithm

Features
---------
* Direct access to ordered values for custom extraction
* DBSCAN and inflection based cluster extraction included
* Euclidean, Great Circle, and CIEDE2000 Color Distance Included
* Easy to extention for different point distances
* Depending on chosen distance type, easily handles 1000 points (500 is suggested for Color)

References
---------
* [Wikipedia OPTICS](http://en.wikipedia.org/wiki/OPTICS_algorithm)
* OPTICS: Ordering Points To Identify the Clustering Structure By Mihael Ankerst, Markus M. Breunig, Hans-Peter Kriegel, J&g Sander ([pdf](http://www.cs.uiuc.edu/class/fa05/cs591han/papers/ankerst99.pdf))
* [CIEDE2000 Color Distance](http://www.ece.rochester.edu/~gsharma/ciede2000/)

Basic Usage
---------
`$optics = new Optics($epsilonMaxRadius, $minPoints, $pointDimensions = 2, $pointClass = 'OpticsPoint');`  
`$optics->addDataPoints(array(array(1,3,4), ...));`  
`$optics->run();`  
`$optics->getPointsOrdered();`  
`OpticsCluster::extractDBSCANClusters($optics, $clusterEpsilon);`
