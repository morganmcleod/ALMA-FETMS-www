<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.gnuplot_wrapper.php');
/**
 * class IFSpectrum_plot
 *
 * Extends GnuplotWrapper to add plotting functionality for IF spectrum, power variation,
 *   total, and in-band power.
 *
 */

class IFSpectrum_plot extends GnuplotWrapper {
    private $loValues;   // array of LO values to plot
    private $maxOffset;  // maximum accumulated offset for stacked specrum traces.
    private $minMaxData; // Min and max power plus offset seen per LO:
                         // array(
                         //     'LO_GHz'   => float,
                         //     'pMin_dBm' => float,
                         //     'pMax_dBm' => float,
                         //     'last_dBm' => float    // rightmost point in trace, for Y2 axis labels.
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
        $this->loValues = array();
        $this->resetMinMaxData();
    }

    public function resetMinMaxData() {
        $this->minMaxData = array();
        $this->maxOffset = 0;
    }

    /**
     * Apply offsets in dB to the power levels in dBm of subsequent LO traces.
     *  This is a utility for plotting, to spread out the traces for display on a single Y scale.
     *  Also accumulates min and max power levels seen per LO.
     *
     * @param float $offset Amount to offset each LO trace from the previous.
     * @return none.  Modifies the internal data arrays.
     */
    public function prepareSpectrumTraces($offset) {
        $appendResult = function(&$output, $LO, $mindBm, $maxdBm, $lastdBm) {
            $output[] = array(
                    'LO_GHz' => $LO,
                    'pMin_dBm' => $mindBm,
                    'pMax_dBm' => $maxdBm,
                    'last_dBm' => $lastdBm
            );
        };

        // reset the min/max and trace offset accumulators:
        $this->resetMinMaxData();

        if (empty($this->data))
            return;

        // to accumulate min/max powers seen per LO:
        $mindBm = self::HUGE_POWER;
        $maxdBm = self::TINY_POWER;
        $lastdBm = self::TINY_POWER;
        $lastLO = self::BAD_LO;

        // Loop for all rows:
        $size = count($this->data);
        for ($index = 0; $index < $size; $index++) {
            $row = $this->data[$index];
            $LO = $row['LO_GHz'];
            $IF = $row['Freq_GHz'];
            $PW = $row['Power_dBm'];
            // if new LO seen:
            if ($LO != $lastLO) {
                // and the previous one is not our start token:
                if ($lastLO != self::BAD_LO) {
                    // increase the total offset amount:
                    $this->maxOffset += $offset;
                    // output a row to minMaxData:
                    $appendResult($this->minMaxData, $lastLO, $mindBm, $maxdBm, $lastdBm);
                    // and reset the min/max accumulators:
                    $mindBm = self::HUGE_POWER;
                    $maxdBm = self::TINY_POWER;
                }
                // make make it the new current LO:
                $lastLO = $LO;
            }
            // Apply the offset to the current power level:
            $PW += $this->maxOffset;
            // Save the last power level in the trace for positioning the right-hand tic mark:
            $lastdBm = $PW;
            // Accumulate min and max power seen for this LO:
            if ($IF >= self::LOW_IF_CUTOFF) {
                if ($PW < $mindBm)
                    $mindBm = $PW;
                if ($PW > $maxdBm)
                    $maxdBm = $PW;
            }
            // Apply the offset to the current row:
            $this->data[$index]['Power_dBm'] = $PW;
        }
        // output the final minMaxData row:
        $appendResult($this->minMaxData, $lastLO, $mindBm, $maxdBm, $lastdBm);
    }

    /**
     * Generate a single spurious plot from the current data set.
     *
     * @param bool $expanded TRUE if this is an expanded spectrum plot
     * @param string $imagename is the output filename
     * @param string $plotTitle for the top of the plot
     * @param string $TDHdataLabels for the bottom of the plot
     */
    public function generateSpuriousPlot($expanded, $imagename, $plotTitle, $TDHdataLabels) {
        // find unique LO frequencies in the data set:
        $this->findLOs();
        // create temporary files with spurious noise data to be used by GNUPLOT:
        $this->createTempFiles('Power_dBm');

        if ($expanded) {
            $this->plotSize(900, count($this->loValues) * 300, false); // pixels
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
        $index = 0;
        // Sets 2nd y axis tick values using data created in prepareSpectrumTraces()
        // Sets line attributes.
        foreach ($this->loValues as $lo) {
            if ($expanded) {
                $ytics[$lo][0] = $this->minMaxData[$index]['pMin_dBm'];
                $ytics[$lo][1] = $this->minMaxData[$index]['pMax_dBm'];
            }

            $y2tics[$lo] = $this->minMaxData[$index]['last_dBm'];
            $index++;
            $att[] = "lines lt $index title '" . $lo . " GHz'";
        }
        if (!$expanded) {
            $ytics = FALSE;
        }
        $this->plotYTics(array('ytics' => $ytics, 'y2tics' => $y2tics));
        $this->plotLabels(array('x' => 'IF (GHz)', 'y' => 'Power (dB)')); // Set x and y axis labels
        $this->plotArrows(); // Creates vertical lines over IF range from specs ini file.
        $this->plotYAxis(array('ymin' => $this->minMaxData[0]['pMin_dBm'],
                               'ymax' => $this->minMaxData[count($this->minMaxData) - 1]['pMax_dBm']));
        $this->plotAddLabel($TDHdataLabels, array(array(0.01, 0.04), array(0.01, 0.01)));
        $this->plotData($att, count($att));
        $this->doPlot();
    }

    /**
     * Generate a single power variation plot from the current data set.
     *
     * @param bool $win31MHz TRUE if the window size is 31 MHz
     * @param string $imagename is the output filename
     * @param string $plotTitle for the top of the plot
     * @param string $TDHdataLabels for the bottom of the plot
     */
    public function generatePowerVarPlot($win31MHz, $imagename, $plotTitle, $TDHdataLabels) {
        // find unique LO frequencies in the data set:
        $this->findLOs();
        // create temporary files with spurious noise data to be used by GNUPLOT:
        $this->createTempFiles('pVar_dB');

        // use default plot size:
        $this->plotSize();

        if ($win31MHz)
            $this->specs['spec_value'] = 1.35;

        // setup the plot:
        $this->plotOutput($imagename);
        $this->plotTitle($plotTitle);
        $this->plotGrid();
        $this->createSpecsFile('Freq_GHz', array('spec_value'), array("lines lt -1 lw 5 title 'Spec'"), FALSE);
        $this->plotLabels(array('x' => 'Center of Window (GHz)', 'y' => 'Power Variation in Window (dB)'));
        $this->plotBMargin(7);
        $this->plotKey('outside');
        $ymax = ($this->band == 6) ? 9 : $this->specs['spec_value'] + 1;
        $this->plotYAxis(array('ymin' => 0, 'ymax' => $ymax));
        $att = array();
        $ltIndex = 1;
        foreach ($this->loValues as $lo) {
            $att[] = "lines lt $ltIndex title ' $lo GHz'";
            $ltIndex++;
        }
        if ($this->band == 6) {
            // Special band 6 power variation plot:
            $this->plotAddLabel($TDHdataLabels, array(array(0.01, 0.04), array(0.01, 0.01)));
            $this->band6powervar($if, $FEid, $dataSetGroup, $att, count($att));
        } else {
            $temp = "Max Power Variation: "; // . round($IF->maxvar, 2) . " dB";
            $TDHdataLabels[] = $temp;
            $this->plotAddLabel($TDHdataLabels, array(array(0.01, 0.07), array(0.01, 0.04), array(0.01, 0.01)));
            array_pop($TDHdataLabels);
            $this->plotData($att, count($att));
        }
        $this->doPlot();
    }

    /**
     * Plots band 6, 2 GHz case for power variation.
     *
     * @param int $IFChannel
     * @param int $FEid
     * @param int $DataSetGroup
     * @param array $lineAtt- Strings indicating parameters for each line to be plotted.
     * @param int $count- number of lines to be plotted, number of files created by createTempFile()
     */
    public function band6powervar($IFChannel, $FEid, $DataSetGroup, $lineAtt, $count) {
        require(site_get_config_main());

        $this->plotYAxis(array('ymin' => 0, 'ymax' => 9));
        $this->plotXAxis(array('xmin' => 5, 'xmax' => 9.2));
        $this->plotAttribs['fspec1'] = "f1(x) = ((x > 5.2) && (x < 5.8)) ? 8 : 1/0\n";
        $this->plotAttribs['fspec2'] = "f2(x) = ((x > 7) && (x < 9)) ? 7 : 1/0\n";

//         $dbpull = new IF_db();
//         $temp = $dbpull->q6($this->band, $IFChannel, $FEid, $DataSetGroup);
        $b6points = $temp[0];
        $maxvar = $temp[1];

        $this->plotAttribs["label3"] = "set label 'Max Power Variation: " . round($maxvar, 2) . " dB' at screen 0.01, 0.07\n";

        $bmax = 5.52;
        $bmin = 5.45;
        for ($i=0; $i<count($b6points); $i++) {
            $temp = "fb6_$i(x)=((x>$bmin) && (x<$bmax)) ? $b6points[$i] : 1/0\r\n";
            $this->plotAttribs["b6$i"] = $temp;
        }

        $temp = "plot fb6_0(x) with linespoints notitle pt 5 lt 1, '" . $main_write_directory . "$this->dir/temp_data0.txt'";
        $temp .= " using 1:2 with $lineAtt[0],";
        for ($k=1; $k<$count; $k++) {
            $temp .= " fb6_$k(x) with linespoints notitle pt 5 lt " . (string)($k + 1) . ", ";
            $temp .= "'" .$main_write_directory . "$this->dir/temp_data$k.txt'";
            $temp .= " using 1:2 with $lineAtt[$k],";
        }
        $temp .= " f1(x) title 'Spec' with lines lt -1 lw 5,";
        $temp .= " f2(x) notitle with lines lt -1 lw 5";

        if(isset($this->plotAttribs['specAtt'])) {
            $add = $this->plotAttribs['specAtt'];
            $temp .= $add;
            unset($this->plotAttribs['specAtt']);
        }
        $this->plotAttribs['plot'] = $temp . "\n";
    }


    /**
     * Finds unique LO values in the data array.
     *
     * Requires LO values to be in 'LO_GHz' column
     */
    public function findLOs() {
        $this->loValues = array();
        foreach($this->data as $row) {
            if(!in_array($row['LO_GHz'], $this->loValues)) {
                $this->loValues[] = $row['LO_GHz'];
            }
        }
    }

    /**
     * plots vertical lines at IF limits from specs class.
     */
    public function plotArrows() {
        $lo = $this->specs['ifspec_low'];
        $hi = $this->specs['ifspec_high'];
        $y1 = $this->minMaxData[0]['pMin_dBm'];
        $y2 = $this->minMaxData[count($this->minMaxData) - 1]['pMax_dBm'];
        $this->plotAttribs['arrow_lo'] = "set arrow 1 from $lo, " . $y1 . " to $lo, " . $y2 . " nohead lt -1 lw 2\n";
        $this->plotAttribs['arrow_hi'] = "set arrow 2 from $hi, " . $y1 . " to $hi, " . $y2 . " nohead lt -1 lw 2\n";
    }

    /**
     * Creates temp data files to be used to plot spurious noise for a given band and IF channel.
     */
    private function createTempFiles($yvar) {
        $LO = $this->loValues;
        $oldData = $this->data;
        for ($i=0; $i<count($LO); $i++) {
            $tempData = array();
            foreach ($oldData as $row) {
                if ($row['LO_GHz'] == $LO[$i]) {
                    $tempData[] = $row;
                }
            }
            $this->data = $tempData;
            $this->createTempFile('Freq_GHz', $yvar, $i);
        }
        $this->data = $oldData;
    }
}

?>
