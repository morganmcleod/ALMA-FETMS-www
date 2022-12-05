<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.gnuplot_wrapper.php');
require_once($site_libraries . '/array_column/src/array_column.php');

/**
 * class IFSpectrum_plot
 *
 * Extends GnuplotWrapper to add plotting functionality for IF spectrum, power variation,
 *   total, and in-band power.
 *
 */

class IFSpectrum_plot extends GnuplotWrapper {
    private $pvarData_special; // special data array for band6 5-6 GHz

    private $RFBandEdgeMarks;   // location of RF band edge marks on the plot.
    //  array(
    //      [0] => array(
    //          'LO_GHz' => float,    // An LO frequency
    //          'Freq_GHz' => float,  // The IF at the band edge
    //          'Power_dBm' => float  // The position for the marker on the trace
    //      ),
    //      [1] => array...
    // )

    const BAD_LO = -999;          // GHz  Invalid value for LO
    const HUGE_POWER = 999;       // dBm  Invalid big value for power
    const TINY_POWER = -999;      // dBm  Invalid small value for power
    const LOW_IF_CUTOFF = 0.010;  // Ghz  Exlude power from below 10 MHz from total power calc and scaling

    /**
     * Constructor
     */
    public function __construct() {
        GnuplotWrapper::__construct();
        $pvarData_special = array();
        $this->RFBandEdgeMarks = array();
    }

    private function sendImage($imagepath) {
        global $site_storage;
        $temp = substr($imagepath, stripos($imagepath, "ifspectrum/"));
        $path = dirname($temp) . "/";
        $ch = curl_init($site_storage . 'upload.php');
        curl_setopt_array($ch, array(
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => array(
                'image' => new CURLFile($imagepath, 'image/png'),
                'path' => $path,
                'token' => getenv("STORAGE_TOKEN")
            )
        ));
        curl_exec($ch);
        unlink($imagepath);
    }

    public function setData_special($pvarData_special) {
        unset($this->pvarData_special);
        if ($pvarData_special)
            $this->pvarData_special = $pvarData_special;
        else
            $this->pvarData_special = array();
    }

    public function setRFBandEdgeMarks($RFBandEdgeMarks) {
        $this->RFBandEdgeMarks = $RFBandEdgeMarks;
    }

    /**
     * Generate a single spurious plot from the current data set.
     *
     * @param bool $expanded TRUE if this is an expanded spectrum plot
     * @param string $imagename is the output filename
     * @param string $plotTitle for the top of the plot
     * @param string $TDHdataLabels for the bottom of the plot
     */
    public function generateSpuriousPlot($expanded, $imagename, $plotTitle, $TDHdataLabels, $traceOffset, $minMaxData) {
        // create temporary files with spurious noise data to be used by GNUPLOT:
        $this->createTempFiles('LO_GHz', 'Freq_GHz', 'Power_dBm', $traceOffset);

        if ($expanded) {
            $this->plotSize(900, count($minMaxData) * 300, false); // pixels
        } else {
            $this->plotSize(900, 600, false); // pixels
        }

        // setup the plot:
        $this->plotOutput($imagename);
        $this->plotTitle($plotTitle);
        $this->plotGrid();
        $this->plotKey(FALSE);
        $this->plotBMargin(7);
        $ytics = array();
        $y2tics = array();
        $att = array();
        $loIndex = 0;
        // Sets 2nd y axis tick values
        // Sets line attributes.
        foreach ($minMaxData as $row) {
            $LO = $row['LO_GHz'];
            $offset = $loIndex * $traceOffset;
            if ($expanded) {
                $ytics[$LO][0] = $row['pMin_dBm'] + $offset;
                $ytics[$LO][1] = $row['pMax_dBm'] + $offset;
            }
            $y2tics[$LO] = $row['last_dBm'] + $offset;
            $loIndex++;
            $att[] = "lines lt $loIndex title '" . $LO . " GHz'";
        }
        $ylabel = FALSE;
        if (!$expanded) {
            $ytics = FALSE;
            $ylabel = 'Power (dB)  Traces are drawn with an artificial 10 dB spacing.';
        }
        $this->plotYTics(array('ytics' => $ytics, 'y2tics' => $y2tics));
        $this->plotLabels(array('x' => 'IF (GHz)', 'y' => $ylabel)); // Set x and y axis labels

        $ifLow = $this->specs['ifspec_low'];
        $ifHi  = $this->specs['ifspec_high'];
        $yMin = $minMaxData[0]['pMin_dBm'];
        $yMax = $minMaxData[$loIndex - 1]['pMax_dBm'] + (($loIndex - 1) * $traceOffset);

        $this->plotAttribs['arrow_lo'] = "set arrow 1 from $ifLow, " . $yMin . " to $ifLow, " . $yMax . " nohead lt -1 lw 2\n";
        $this->plotAttribs['arrow_hi'] = "set arrow 2 from $ifHi, " . $yMin . " to $ifHi, " . $yMax . " nohead lt -1 lw 2\n";

        $arrowIndex = 2;
        for ($loIndex = 0; $loIndex < count($minMaxData); $loIndex++) {
            $row = $minMaxData[$loIndex];
            $LO = $row['LO_GHz'];

            $markIndex = array_search($LO, array_column($this->RFBandEdgeMarks, 'LO_GHz'));
            if (!($markIndex === FALSE)) {

                $IF = $this->RFBandEdgeMarks[$markIndex]['Freq_GHz'];

                if ($IF > $ifLow && $IF < $ifHi) {

                    $x = $IF;
                    $y = $row['pMax_dBm'] - (($row['pMax_dBm'] - $row['pMin_dBm']) / 4) + ($loIndex * $traceOffset);

                    $x1 = $x + 0.4;
                    $y1 = $y - 2.0;
                    $y2 = $y + 2.0;

                    $arrowIndex++;
                    $lt = $loIndex + 1;
                    $arrowName = "arrow_$arrowIndex";
                    $this->plotAttribs[$arrowName] = "set arrow $arrowIndex from $x, " . $y . " to $x1, " . $y1 . " nohead lt $lt lw 3\n";
                    $arrowIndex++;
                    $arrowName = "arrow_$arrowIndex";
                    $this->plotAttribs[$arrowName] = "set arrow $arrowIndex from $x, " . $y . " to $x1, " . $y2 . " nohead lt $lt lw 3\n";
                }
            }
        }

        // Append an "RF band edge" line to the labels:
        if ($arrowIndex > 2)
            $TDHdataLabels[] = "< indicate RF band edges: " . $this->specs['rfMin'] . "-" . $this->specs['rfMax'] . " GHz";
        $this->plotYAxis(array('ymin' => $yMin, 'ymax' => $yMax));
        $this->plotAddLabel($TDHdataLabels);
        $this->plotData($att, count($att));
        $this->doPlot();
        $this->sendImage("{$this->outputDir}/{$imagename}");
        $this->deleteTempFiles();
    }

    /**
     * Generate a single power variation plot from the current data set.
     *
     * @param bool $win31MHz TRUE if the window size is 31 MHz
     * @param string $imagename is the output filename
     * @param string $plotTitle for the top of the plot
     * @param string $TDHdataLabels for the bottom of the plot
     */
    public function generatePowerVarPlot($win31MHz, $imagename, $plotTitle, $spec, $badLOs, $TDHdataLabels, $loFreqs) {
        // create temporary files with spurious noise data to be used by GNUPLOT:
        $this->createTempFiles('LO_GHz', 'Freq_GHz', 'pVar_dB', 0);

        // use default plot size:
        $this->plotSize();

        $ymax = $spec + 1;
        if (!$win31MHz && $this->Band == 6)
            $ymax = 9;

        // Higher ymax for band 2 proto cartridge:
        if (!$win31MHz && $this->Band == 2)
            $ymax = 12;

        $xArray = array_column($this->data, 'Freq_GHz');
        $xMin = min($xArray);
        $xMax = max($xArray);

        // setup the plot:
        $this->plotOutput($imagename);
        $this->plotTitle($plotTitle);
        $this->plotGrid();
        $this->createSpecsFile('Freq_GHz', $spec);
        $this->plotLabels(array('x' => 'Center of Window (GHz)', 'y' => 'Power Variation in Window (dB)'));
        $this->plotBMargin(7);
        $this->plotKey('outside');

        $this->plotXAxis(array('xmin' => $xMin, 'xmax' => $xMax));
        $this->plotYAxis(array('ymin' => 0, 'ymax' => $ymax));
        $att = array();
        $ltIndex = 1;
        foreach ($loFreqs as $lo) {
            $mark = ' ';
            if (in_array($lo, $badLOs))
                $mark = '*';

            $att[] = "lines lt $ltIndex title '$mark$lo GHz'";
            $ltIndex++;
        }

        // TODO: Get band6 special power var plots working again
        //         if (!$win31MHz && $this->Band == 6) {
        //             $this->plotAttribs['fspec1'] = "f1(x) = ((x > 5.2) && (x < 5.8)) ? 8 : 1/0\n";

        //             $att[] = "f1(x) notitle with lines lt -1 lw 5";

        //              $ltIndex = 1;
        //              foreach ($this->pvarData_special as $row) {
        //                  $att[] = "5, " . $row['pVar_dB'] . "linespoints lt $ltIndex notitle";
        //                  $ltIndex++;
        //              }
        //         }
        $this->plotAddLabel($TDHdataLabels);
        $this->plotData($att, count($att));
        $this->doPlot();
        $this->sendImage("{$this->outputDir}/{$imagename}");
        $this->deleteTempFiles();
        $this->deleteSpecsFile();
    }
}
