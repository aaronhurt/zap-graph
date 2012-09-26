#!/usr/bin/php
<?php

// we just generate pngs from the cli ...
if (php_sapi_name() !== "cli") die("Command line only!");

// pull in pchart
include("pChart/class/pData.class.php");
include("pChart/class/pDraw.class.php");
include("pChart/class/pImage.class.php");

// get filename ... die if none
if (!isset($argv[1]) || !is_readable($argv[1])) die("Usage: " . $argv[0] . " [csv file]\n");

// open file and get contents
$csv = file($argv[1]);

// print it
//echo "csv == " . print_r($csv, true) . "\n";

// get header
$header = explode(',', array_shift($csv));

// print it
//echo "header == " . print_r($header, true) . "\n";

// loop through rest of data ... add to results array
$results = array(); foreach ($csv as $x => $line) {
    // build associative array of values with keys from header
    foreach (explode(',', $line) as $y => $value) {
        // trim value
        $value = trim($value);
        // set blanks to litteral NULL
        if (empty($value)) $value = "NULL";
        // check and trim value
        if (isset($header[$y])) {
            $head = trim($header[$y]);
            // add it if not empty
            if (!empty($head)) $results[$x][$head] = $value;
        }
    }
}

// print it out
//echo "results == " . print_r($results, true) . "\n";

// create pchart object
$data = new pData();

// build our labels - cutoff at 98 percent
for ($x = 0; $x < 99; $x ++) {
    // add the label
    $labels[] = $x.'.0%';
}

// print it out
//echo "labels == " . print_r($labels, true) . "\n";

// build our data points
foreach ($results as $res) {
    // get speed points for labels set above
    $points = array(); foreach ($labels as $label) {
        $points[] = $res[$label];
    }
    // print it out
    //echo "points == " . print_r($points, true) . "\n";
    // add points with tag for this results
    $data->addPoints($points, $res['Tag']);
    // increase line weight
    $data->setSerieWeight($res['Tag'], 1.5);
}

// set vertical axis
$data->setAxisName(0, "Throughput in Mbps");
//$data->setAxisUnit(0, "mbps");

// add labels
$data->addPoints($labels, "labels");

// set abcissa series - horizontal axis
$data->setXAxisName("Percentile");
$data->setSerieDescription("labels", "Percentile");
$data->setAbscissa("labels");

// create the chart
$chart = new pImage(800,430, $data);

// toggle antialiasing
$chart->Antialias = false;

// add border
$chart->drawRectangle(0,0,799,429,array("R"=>0,"G"=>0,"B"=>0));

// draw title
$chart->setFontProperties(array("FontName"=>"pChart/fonts/Forgotte.ttf","FontSize"=>12));
$chart->drawText(150,35,"Zapwireless",array("FontSize"=>20,"Align"=>TEXT_ALIGN_BOTTOMMIDDLE));

// set default font
$chart->setFontProperties(array("FontName"=>"pChart/fonts/Forgotte.ttf","FontSize"=>10));

// set chart area
$chart->setGraphArea(60,40,780,390);

// draw scale
$scale = array(
    "XMargin"=>10,"YMargin"=>10,"GridR"=>200,"GridG"=>200,"GridB"=>200,"DrawSubTicks"=>true,
    "CycleBackground"=>true,"Mode"=>SCALE_MODE_START0,"LabelSkip"=>4,"LabelRotation"=>30,
);
$chart->drawScale($scale);

// toggle antialiasing
$chart->Antialias = true;

// draw chart
$chart->drawLineChart();
//$chart->drawPlotChart(array("DisplayValues"=>false,"PlotBorder"=>true,"BorderSize"=>-1,"Surrounding"=>-1,"BorderAlpha"=>80));

// set default font
$chart->setFontProperties(array("FontName"=>"pChart/fonts/pf_arma_five.ttf","FontSize"=>8));

// draw legend
$chart->drawLegend(250,20,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));

// set filename
$outs = preg_replace('/\.csv$/i','',$argv[1]) . "-graph.png";

// render graphic
$chart->render($outs);

// exit clean
echo "Success: graph generated -> $outs\n";
exit(0);
 
