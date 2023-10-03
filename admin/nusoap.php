<?php

    $ver = (float)phpversion();

    if ($ver > 7.0) {

        // php7.1 and above
        require_once('nusoap-php7.php');

    } elseif ($ver === 7.0) {

        // php7.0
        require_once('nusoap-php7.php');

    } else {

        // php5.6 or lower
        require_once('nusoap-php5.php');

    }

?>