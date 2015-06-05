<?php

// get PHP and libraries configuraion:
require_once(dirname(__FILE__) . '/../SiteConfig.php');

function gnuplotScript1() {
    // get the main data files write directory from config_main:
    require(site_get_config_main());

    // folder for plots:
    $plotDir = $main_write_directory . 'gnuplotTest/';

    // create plot directory if it doesn't exist.
    if (!file_exists($plotDir)) {
        mkdir($plotDir);
    }

    // Create gnuplot command file:
    $commandfile = $plotDir . "plotcommands.txt";
    $f = fopen($commandfile, 'w');

    //
    //  generate plot data files and script here.
    //

    fclose($f);

    //Call gnuplot
    system("$GNUPLOT $commandfile");
}

// Test code goes here:

echo "hello gnuplot!<br>";

gnuplotScript1();

echo "bye!<br>";

?>