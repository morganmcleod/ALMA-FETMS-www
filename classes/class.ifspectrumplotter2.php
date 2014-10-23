<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.gnuplot_wrapper.php');
require_once($site_IF . '/IF_db.php');

class IFSpectrumPlotter2 extends GnuplotWrapper {
    private $loValues;   // array of LO values to plot
    private $spurVal;    // holds min and max power levels seen per LO

    /**
     * Constructor
     */
    public function __construct() {
        GnuplotWrapper::__construct();
        $loValues = array();
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
        if ($expanded) {
            $this->getSpuriousExpanded();
            $this->plotSize(900, 1500); // pixels
        } else {
            $this->getSpuriousNoise();
            $this->plotSize(900, 600); // pixels
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
        $ltIndex = 1;
        // Sets 2nd y axis tick values using data created in getSpuriousNoise()
        // Sets line attributes.
        foreach ($this->loValues as $lo) {
            if (!$expanded) {
                $y2tics[$lo] = $this->spurVal[$lo];
            } else {
                $ytics[$lo] = $this->spurVal[$lo];
                $y2tics[$lo] = $this->spurVal[$lo][0];
            }
            $att[] = "lines lt $ltIndex title '" . $lo . " GHz'";
            $ltIndex++;
        }
        if (!$expanded) {
            $ytics = FALSE;
        }
        $this->plotYTics(array('ytics' => $ytics, 'y2tics' => $y2tics));
        $this->plotLabels(array('x' => 'IF (GHz)', 'y' => 'Power (dB)')); // Set x and y axis labels
        $this->plotArrows(); // Creates vertical lines over IF range from specs ini file.
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
        // Create temporary files for power variation over 2 GHz window plots:
        $this->getPowerVar();
        $this->plotSize(900, 600); // pixels

        if ($win31MHz)
            $this->specs['spec_value'] = 1.35;

        // setup the plot:
        $this->plotOutput($imagename);
        $this->plotTitle($plotTitle);
        $this->plotGrid();
        $this->createSpecsFile('Freq_Hz', array('spec_value'), array("lines lt -1 lw 5 title 'Spec'"), FALSE);
        $this->plotLabels(array('x' => 'Center of Window (GHz)', 'y' => 'Power Variation in Window (dB)'));
        $this->plotBMargin(7);
        $this->plotKey('outside');
        $ymax = ($this->band == 6) ? 9 : $this->specs['spec_value'] + 1;
        $this->plotYAxis(array('ymin' => 0, 'ymax' => $ymax));
        $att = array();
        $ltIndex = 1;
        foreach ($this->loValues as $lo) {
            $att[] = "lines lt $ltIndex title '$lo GHz'";
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
        $this->plotAtt['fspec1'] = "f1(x) = ((x > 5.2) && (x < 5.8)) ? 8 : 1/0\n";
        $this->plotAtt['fspec2'] = "f2(x) = ((x > 7) && (x < 9)) ? 7 : 1/0\n";

        $dbpull = new IF_db();
        $temp = $dbpull->q6($this->band, $IFChannel, $FEid, $DataSetGroup);
        $b6points = $temp[0];
        $maxvar = $temp[1];

        $this->plotAtt["label3"] = "set label 'Max Power Variation: " . round($maxvar, 2) . " dB' at screen 0.01, 0.07\n";

        $bmax = 5.52;
        $bmin = 5.45;
        for ($i=0; $i<count($b6points); $i++) {
            $temp = "fb6_$i(x)=((x>$bmin) && (x<$bmax)) ? $b6points[$i] : 1/0\r\n";
            $this->plotAtt["b6$i"] = $temp;
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

        if(isset($this->plotAtt['specAtt'])) {
            $add = $this->plotAtt['specAtt'];
            $temp .= $add;
            unset($this->plotAtt['specAtt']);
        }
        $this->plotAtt['plot'] = $temp . "\n";
    }


    /**
     * Finds unique LO values in the data array.
     *
     * Requires LO values to be in 'FreqLO' column
     */
    public function findLOs() {
        $this->loValues = array();
        foreach($this->data as $row) {
            if(!in_array($row['FreqLO'], $this->loValues)) {
                $this->loValues[] = $row['FreqLO'];
            }
        }
    }

    /**
    * Creates temp data files to be used to plot spurious noise for a given band and IF channel.
    * @param array $LO- LO frequencies used in data.
    */
    public function getSpuriousNoise() {
        $LO = $this->loValues;
        $min = 1000;
        $max = -1000;
        $oldData = $this->data;
        for ($i=0; $i<count($LO); $i++) {
            $tempData = array();
            foreach ($oldData as $row) {
                if ($row['FreqLO'] == $LO[$i]) {
                    $tempData[] = $row;
                    if($row['Power_dBm'] < $min) {
                        $min = $row['Power_dBm'];
                    }
                    if($row['Power_dBm'] > $max) {
                        $max = $row['Power_dBm'];
                    }
                }
            }
            $this->data = $tempData;
            $this->createTempFile('Freq_Hz', 'Power_dBm', $i);
            $this->spurVal[$LO[$i]] = $tempData[count($tempData) - 1]['Power_dBm'];
        }
        $this->spurVal['ymin'] = $min;
        $this->spurVal['ymax'] = $max;
        $this->data = $oldData;
    }

    /**
     * Same as getSpuriousNoise, but adds an offset for expanded plots and changes ytics
     *
     * @param array $LO- LO frequencies used in data.
     */
    public function getSpuriousExpanded() {
        $LO = $this->loValues;
        $wmin = 1000;
        $wmax = -1000;
        $oldData = $this->data;
        $offset = 0;
        for ($i=0; $i<count($LO); $i++) {
            $tempData = array();
            $min = 1000;
            $max = -1000;
            foreach ($oldData as $row) {
                if ($row['FreqLO'] == $LO[$i]) {
                    $temp = $row;
                    $temp['Power_dBm'] += $offset;
                    $tempData[] = $temp;
                    if($temp['Power_dBm'] < $wmin) {
                        $wmin = $temp['Power_dBm'];
                    }
                    if($temp['Power_dBm'] > $wmax) {
                        $wmax = $temp['Power_dBm'];
                    }
                    if($temp['Power_dBm'] < $min) {
                        $min = $temp['Power_dBm'];
                    }
                    if($temp['Power_dBm'] > $max) {
                        $max = $temp['Power_dBm'];
                    }
                }
            }
            $this->data = $tempData;
            $this->createTempFile('Freq_Hz', 'Power_dBm', $i);
            $this->spurVal[$LO[$i]] = array(round($min, 2), round($max, 2));
            $offset += 32;
        }
        $this->spurVal['ymin'] = $wmin;
        $this->spurVal['ymax'] = $wmax;
        $this->data = $oldData;
    }

    /**
     * plots vertical lines at IF limits from specs class.
     *
     * MUST BE CALLED AFTER setParams() AND getSpuriousNoise/Expanded()
     */
    public function plotArrows() {
        $lo = $this->specs['ifspec_low'];
        $hi = $this->specs['ifspec_high'];
        $this->plotAtt['arrow_lo'] = "set arrow 1 from $lo, " . $this->spurVal['ymin'] . " to $lo, " . $this->spurVal['ymax'] . " nohead lt -1 lw 2\n";
        $this->plotAtt['arrow_hi'] = "set arrow 2 from $hi, " . $this->spurVal['ymin'] . " to $hi, " . $this->spurVal['ymax'] . " nohead lt -1 lw 2\n";
    }

    /**
     * Creates temporary files to be used to plot power variation data.
     */
    public function getPowerVar() {
        $LO = $this->loValues;
        $oldData = $this->data;
        for ($i=0; $i<count($LO); $i++) {
            $tempData = array();
            foreach($oldData as $row) {
                if ($row['FreqLO'] == $LO[$i]) {
                    $tempData[] = $row;
                }
            }
            $this->data = $tempData;
            $this->createTempFile('Freq_Hz', 'Power_dBm', $i);
        }
        $this->data = $oldData;
    }

    /**
     * Displays power variation table to browser.
     * Assumes band has already been initialized.
     *
     * @param int $DataSetGroup
     * @param int $FEid
     */
    public function powerVarTables($DataSetGroup, $FEid, $feconfig, $TS, $TDHdataLabels) {
        $IF = new IFCalc();
        $IF->setParams($this->band, 0, $FEid, $DataSetGroup);
        $powVar = $IF->getPowVarData();

        $oldData = $this->data;

        $this->data = $powVar;
        $this->findLOs();
        $tempdata = array();
        foreach ($this->loValues as $L) {
            $temp = array();
            foreach ($this->data as $row) {
                if ($row['FreqLO'] == $L) {
                    $temp[] = $row;
                }
            }
            $add = array('<b>LO (GHz)' => $L);
            foreach($temp as $t) {
                $add["<b>IF" . $t['IF']] = $t['value'];
            }
            $tempdata[] = $add;
        }
        $this->data = $tempdata;
        echo "<div style='width:400px' border='1'>";
        echo "<table id = 'table7' border = '1'>";
        echo "<tr><th colspan = '5'>Band $this->band Power Variation Full Band</th></tr>";
        $this->print_data();
        foreach ($TDHdataLabels as $label)
            echo "<tr class = 'alt3'><th colspan = '5'>$label</th></tr>";
        echo "</table></div>";
        $this->data = $oldData;
    }

    /**
    * Displays the total and in-band power table for IF Output Spectrum.
    * Assumes band has already been initialized.
    *
    * @param int $DataSetGroup
    * @param int $FEid
    * @param int $if
    */
    public function powerTotTables($DataSetGroup, $FEid, $if, $feConfig, $TS, $TDHdataLabels) {
        $IF = new IFCalc();
        $IF->setParams($this->band, 0, $FEid, $DataSetGroup);

        echo "<div style = 'width:600px'>";
        echo "<table id = 'table7' border = '1'>";
        echo "<tr><th colspan = '5'>Band $this->band Total and In-Band Power</th></tr>";
        echo "<tr><th colspan = '5'><b>IF Channel $if</b></th></tr>";
        echo "<tr><td colspan = '2' align = 'center'><i>0 dB Gain</i></td><td colspan = '3' align = 'center'><i>15 dB Gain</i></td></tr>";

        $IF->IFChannel = $if;
        $oldData = $this->data;
        $this->data = $IF->getTotPowData();
        $this->findLOs();
        $newData = array();
        foreach ($this->data as $row) {
            $temp = array();
            $temp["LO (GHz)"] = "<b>" . $row['FreqLO'] . "</b>";
            $temp['In-Band (dBm)'] = $row['pwr0'];
            $temp['In-Band (dBm) '] = $row['pwr15'];
            $temp['Total (dBm)'] = $row['pwrT'];
            $temp['Total - In-Band'] = $row['pwrdiff'];
            $newData[] = $temp;
        }
        $this->data = $newData;
        $this->print_data();
        foreach ($TDHdataLabels as $label)
            echo "<tr class = 'alt3'><th colspan = '5'>$label</th></tr>";
        echo "</table></div>";
        $this->data = $oldData;
    }
}

?>
