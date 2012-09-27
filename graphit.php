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
        if (empty($value) && $value != 0) $value = "NULL";
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
    //$data->setSerieWeight($res['Tag'], 1.5);
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

// create the image
$chart = new pImage(1000,670, $data);

// toggle antialiasing
$chart->Antialias = false;

// add border
$chart->drawRectangle(0,0,999,669,array("R"=>0,"G"=>0,"B"=>0));

// draw title
$chart->setFontProperties(array("FontName"=>"pChart/fonts/Inconsolata.ttf","FontSize"=>20));
$chart->drawText(20,35,"Zapwireless Test Results",array("Align"=>TEXT_ALIGN_BOTTOMLEFT));

// set default font
$chart->setFontProperties(array("FontName"=>"pChart/fonts/Inconsolata.ttf","FontSize"=>10));

// set chart area
$chart->setGraphArea(60,40,980,390);

// draw scale
$scale = array(
    "XMargin"=>10,"YMargin"=>10,"GridR"=>200,"GridG"=>200,"GridB"=>200,"DrawSubTicks"=>true,
    "CycleBackground"=>true,"LabelSkip"=>4,"LabelRotation"=>30,"Mode"=>SCALE_MODE_MANUAL,
    "ManualScale"=>array(0=>array("Min"=>0,"Max"=>300),1=>array("Min"=>0,"Max"=>100)),
);
$chart->drawScale($scale);

// toggle antialiasing
$chart->Antialias = true;

// draw chart
$chart->drawLineChart();
//$chart->drawPlotChart(array("DisplayValues"=>false,"PlotBorder"=>true,"BorderSize"=>-1,"Surrounding"=>-1,"BorderAlpha"=>80));

// draw legend box
$chart->drawLegend(880,65,array("BoxSize"=>4,"R"=>250,"G"=>250,"B"=>250,"Surrounding"=>20,"Family"=>LEGEND_FAMILY_CIRCLE,"Mode"=>LEGEND_VERTICAL));

// toggle shadows
$chart->setShadow(true,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));

// draw information area title
$chart->drawText(20,440,"Result Details",array("FontSize"=>16,"Align"=>TEXT_ALIGN_BOTTOMLEFT));

// some text properties
$text = array("DrawBox"=>true,"BoxRounded"=>true,"R"=>0,"G"=>0,"B"=>0,"Angle"=>0,"Align"=>TEXT_ALIGN_TOPLEFT);

// place holder
$tags = array();

// draw test information
$x = 20; foreach ($results as $res) {
    // populate tags
    $tags[] = $res['Tag'];
    // draw textbox for this tag
    $chart->drawText($x,455,
        "Tag: " . $res['Tag'] .
        "\nProtocol: " . $res['Protocol'] .
        "\nMulticast: " . $res['Multicast'] .
        "\nToS: " . $res['ToS'] .
        "\nSamples: " . $res['Samples'] .
        "\nSample Size: " . $res['Sample Size'] .
        "\nPayload Length: " . $res['Payload Length'] .
        "\nTransmit Delay: " . $res['Payload Transmit Delay'] .
        "\nReceived: " . $res['Payloads Received'] .
        "\nDropped: " . $res['Payloads Dropped'] .
        "\nRepeated: " . $res['Payloads Repeated'] .
        "\nOut of order: " . $res['Payloads Outoforder'],
        $text
    );
    // move x for next box
    $x += 160;
}

// write chart labels ...
$data->setAxisUnit(0, " Mbps");
$chart->writeLabel($tags,25,array("SerieBoxSize"=>4,"VerticalMargin"=>4,"HorizontalMargin"=>4));
$chart->writeLabel($tags,55,array("SerieBoxSize"=>4,"VerticalMargin"=>4,"HorizontalMargin"=>4));
$chart->writeLabel($tags,90,array("SerieBoxSize"=>4,"VerticalMargin"=>4,"HorizontalMargin"=>4));

// set filename
$outs = preg_replace('/\.csv$/i','',$argv[1]) . "-graph.png";

// render graphic
$chart->render($outs);

// exit clean
echo "Success: graph generated -> $outs\n";
exit(0);
