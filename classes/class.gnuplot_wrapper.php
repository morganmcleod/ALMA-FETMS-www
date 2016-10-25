<?php
/**
 * ALMA - Atacama Large Millimeter Array
 * (c) Associated Universities Inc., 2014
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307  USA
 *
 */

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.spec_functions.php');

class GnuplotWrapper {
    protected $data;            // 2D array of data for the plot
    protected $outputDir;       // Ouptut directory for the plot and its work files
    protected $outputFileName;  // Filename of the output plot
    protected $band;            // What ALMA cartridge band this plot is for
    protected $specs;           // Array of spec values as returned from class Specifications
    protected $labelLocations;  // Array of label locations at bottom of the plot
    protected $plotAttribs;     // Named attributes for the plot
    protected $commandFile;     // The command filename to pass to Gnuplot
    protected $gnuplot;         // Gnuplot command on this system.
    protected $swVersion;       // Software verision of this class

    /**
     * Constructor
     */
    public function __construct() {
        $this->swVersion = '1.2';
        /*
         * 1.2: Added labelLocations logic
         * Version 1.1 Modifications and refactoring Morgan McLeod
         * Version 1.0 (07/30/2014) author Aaron Beaudoin
         */
        require(site_get_config_main());
        $this->resetPlotter();
        $this->band = 0;
        $this->specs = array();
        $this->labelLocations = array(array(0.01, 0.01), array(0.01, 0.04), array(0.01, 0.07), array(0.01, 0.07));
        $a = func_get_args();
        $i = func_num_args();
        if ($i >= 1)
            $this->data = $a[0];
        if ($i >= 2)
            $this->outputDir = $a[1];
        if ($i >= 3)
            $this->band = $a[2];
        $this->gnuplot = $GNUPLOT;
    }

    /**
     * Resets all plot attributes to allow for new plot.
     */
    public function resetPlotter() {
        unset($this->data);
        $this->data = array();
        $this->outputDir = NULL;
        $this->outputFileName = NULL;
        unset($this->plotAttribs);
        $this->plotAttribs = array();
        $this->commandFile = '';
    }

    /**
     * Set the output directory and band number for plotting.
     *
     * @param string $outputDir- Directory for plot and support files to be placed.
     * @param int $band
     */
    public function setParams($outputDir = '', $band = 0) {
        $this->outputDir = $outputDir;
        $this->band = $band;
    }

    /**
     * Set the data to be plotted.
     *
     * @param 2d array $data- Data to be passed.
     * Data[x] = row x containing all data for one LO Freq and IF.
     * Data[x][y] = value of data type y for row x.
     */
    public function setData($data = NULL) {
        if ($data)
            $this->data = $data;
        else
            $this->data = array();
    }

    /**
     * Load the specs for the plot.
     *
     * @param array $specs: array loaded load from class Specifications.
     */
    public function setSpecs($specs) {
        $this->specs = $specs;
    }

    /**
     * Prints data in HTML table to browser.
     * MUST initialize HTML table prior to call.
     * ex: echo "<table border = '1'>";
     * 	   print_data();
     *     echo "</table>;
     * Note: If data value doesn't exist, prints NULL
     */
    public function print_data() {
        $keys = array_keys($this->data[0]);
        echo "<tr>";
        for ($i=0; $i<count($keys); $i++) {
            echo "<td>" . $keys[$i] . "</td>";
        }
        echo "</tr>";
        foreach ($this->data as $d) {
            echo "<tr>";
            for ($i=0; $i<count($keys); $i++) {
                if (isset($d[$keys[$i]])) {
                    echo "<td>" . $d[$keys[$i]] . "</td>";
                } else {
                    echo "<td>NULL</td>";
                }
            }
            echo "</tr>";
        }
    }

    /**
     * Loads saved data from previous work
     *
     * @param string $fileName
     * @return 2d array- data structure used in the rest of the library
     */
    public function loadData($fileName) {
        $file_path = $this->outputDir . "/$fileName";
        $file = file($file_path);
        $keys = explode("\t", $file[0]);
        $data = array();
        for ($i = 1; $i<count($file); $i++) {
            $values = explode("\t", $file[$i]);
            $d = array();
            for ($j=0; $j<count($keys); $j++) {
                if($values[$j] != "\n") {
                    $d[$keys[$j]] = $values[$j];
                }
            }
            $data[] = $d;
        }
        return $data;
    }

    /**
     * Saves data in txt file for later use.
     * Note: If data value doesn't exist, prints NULL
     * @param string $fileName
     */
    public function save_data($fileName) {
        require(site_get_config_main());
        $file_path = "$this->outputDir/$fileName";

        $data = $this->data;
        $keys = array_keys($data[0]);

        $file = fopen($file_path, 'w');
        for($i=0; $i<count($keys); $i++) {
            fwrite($file, $keys[$i] . "\t");
        }
        fwrite($file, "\n");
        foreach($data as $d) {
            for ($i=0; $i<count($keys); $i++) {
                if(isset($d[$keys[$i]])) {
                    fwrite($file, $d[$keys[$i]] . "\t");
                } else {
                    fwrite($file, "NULL\t");
                }
            }
            fwrite($file, "\n");
        }
        fclose($file);
    }

    /**
     * Recursivly finds the order of data.
     * @param string $xvar- data column name desired to find order
     * @param string $index- index of row to check, initially 0.
     * @return string- either 'desc' if values are in descending order, or 'asc' if in ascending order.
     */
    public function findOrder($xvar, $index = 0) {
        if ($this->data[$index][$xvar] > $this->data[$index + 1][$xvar]) {
            return 'desc';
        } elseif ($this->data[$index][$xvar] < $this->data[$index + 1][$xvar]) {
            return 'asc';
        } else {
            return $this->findOrder($xvar, $index + 1);
        }
    }

    /**
     * Removes data rows that don't fit within predescribed IF limits.
     * @param string $IFLabel- column name for IF value in data structure
     */
    public function checkIFLim($IFLabel) {
        $new_data = array();
        foreach ($this->data as $row) {
            if ($this->specs['loIFLim'] <= $row[$IFLabel] && $row[$IFLabel] <= $this->specs['hiIFLim']) {
                $new_data[] = $row;
            }
        }
        $this->data = $new_data;
    }

    /**
     * Averages y values together based on same x values.
     * @param array $x- independent variable.
     * @param array $y- dependent variable, values which will be averaged.
     * @return 2d array- First array are same x values, second array are averaged y values.
     */
    public function averageData($x, $y) {
        $new_x = array();
        $averages = array();
        $temp_x = array($x[0]);
        $temp_y = array($y[0]);
        for ($j=1; $j<count($x); $j++) {
            if ($x[$j] == $x[$j-1]) {
                $temp_x[] = $x[$j];
                $temp_y[] = $y[$j];
            } else {
                $averages[] = array_sum($temp_y) / count($temp_y);
                $new_x[] = array_sum($temp_x) / count($temp_x);
                $temp_x = array($x[$j]);
                $temp_y = array($y[$j]);
            }
        }
        $averages[] = array_sum($temp_y) / count($temp_y);
        $new_x[] = array_sum($temp_x) / count($temp_x);
        return array($new_x, $averages);
    }

    /**
     * Writes data to temp_data#.txt files to be used in plotting script generation.
     * @param array $xvar- independent variable
     * @param array $yvar- dependent variable
     * @param string $sortBy- key for independent variable
     * @param int $count- index for naming output text files
     * @param boolean $average- True if average values desired. Defaults to FALSE.
     */
    public function createTempFile($xvar, $yvar, $count, $average = FALSE) {
        require(site_get_config_main());
        $x = array();
        $y = array();

        $order = $this->findOrder($xvar);

        foreach ($this->data as $row) {
            if (isset($row[$yvar])) {
                $x[] = $row[$xvar];
                $y[] = $row[$yvar];
            }
        }

        if($average) {
            $new_data = $this->averageData($x, $y);
            $x = $new_data[0];
            $y = $new_data[1];
        }

        $temp_data_file = "$this->outputDir/temp_data$count.txt";
        $f = fopen ($temp_data_file, 'w');
        fwrite($f, $x[0] . "\t" . $y[0] . "\n");
        for ($j=1; $j<count($x); $j++) {
            if ($order == 'asc' && ($x[$j] < $x[$j-1])) {
                fwrite($f, "\n");
            }
            if ($order == 'desc' && ($x[$j] > $x[$j-1])) {
                fwrite($f, "\n");
            }
            if ($y[$j] > -250) {
                fwrite($f, $x[$j] . "\t" . $y[$j] . "\n");
            }
        }
        fclose($f);
    }

    /**
     * Creates temp file for spec data to be plotted.
     * @param string $xvar- Independent variable
     * @param array $specs- names of spec lines desired in plot.
     * @param array $lineAtt- Attributes for each spec line.
     */
    public function createSpecsFile($xvar, $specs, $lineAtt, $checkIF = TRUE) {
        require(site_get_config_main());

        $x = array();

        $order = $this->findOrder($xvar);

        if ($checkIF) {
            $this->checkIFLim('CenterIF');
        }
        foreach ($this->data as $row) {
            $x[] = $row[$xvar];
        }

        $spec_string = "";
        foreach ($specs as $s) {
            $spec_string .= "\t" . $this->specs[$s];
        }
        $spec_string .= "\n";

        $spec_files = "$this->outputDir/specData.txt";
        $fspec = fopen($spec_files, 'w');
        fwrite($fspec, $x[0] . $spec_string);
        for ($j=1; $j<count($x); $j++) {
            if($order == 'asc' && ($x[$j] < $x[$j-1])) {
                fwrite($fspec, "\n");
            } elseif ($order == 'desc' && ($x[j] > $x[$j-1])) {
                fwrite($fspec, "\n");
            } else {
                fwrite($fspec, $x[$j] . $spec_string);
            }
        }
        fclose($fspec);
        $temp = "";
        for ($j=0; $j<count($specs); $j++) {
            $temp .= ",'" . $this->outputDir . "/specData.txt' using 1:" . (string)($j + 2) . " with ";
            $temp .= $lineAtt[$j];
        }
        $this->plotAttribs['specAtt'] = $temp;
    }

    /**
     * Assign plot title to be used in plotting script
     * @param string $title
     */
    public function plotTitle($title) {
        $this->plotAttribs['title'] = "set title '" . $title . "'\n";
    }

    /**
     * Set plot size to be used in plotting script
     * @param int $xwin- Width of plot in pixels
     * @param int $ywin- Height of plot in pixels
     */
    public function plotSize($xwin = 640, $ywin = 480, $crop = FALSE) {
        $temp = "set terminal png";
        if ($xwin && $ywin)
            $temp .= " size $xwin,$ywin";
        if($crop)
            $temp .= " crop";
        $temp .= "\n";
        $this->plotAttribs['size'] = $temp;
    }

    /**
     * Shows grid lines in plot
     */
    public function plotGrid() {
        $this->plotAttribs['grid'] = "set grid\n";
    }

    /**
     * Places legend outside of plot
     *
     * @param string/ boolean $request- where should plot be placed
     * 'outside' for outside, FALSE if plot should not be shown
     */
    public function plotKey($request) {
        if($request == 'outside') {
            $this->plotAttribs['key'] = "set key outside\n";
        }
        if ($request == FALSE) {
            $this->plotAttribs['key'] = "set nokey\n";
        }
    }

    /**
     * Set bottom margin for plot
     *
     * @param int $value- size of bmargin
     */
    public function plotBMargin($value) {
        $this->plotAttribs['bmargin'] = "set bmargin $value\n";
    }

    /**
     * Add additional labels to bottom of plot.
     * Member labelLocations is used to place labels
     *
     * @param array $labels- Labels desired to be added to plot
     */
    public function plotAddLabel($labels) {
        $index = count($labels);
        if (!$index)
            return;     // nothing to do.

        // index into labelLocations:
        $labelIndex = 0;

        // apply the labels in reverse order, from bottom up plot upward
        while ($index > 0) {
            $index--;
            // can't proceed unless labelLocations has something here:
            if (isset($this->labelLocations[$labelIndex])) {
                $loc = $this->labelLocations[$labelIndex];
                $key = "label" . (string)($index + 1);
                $this->plotAttribs[$key] = "set label '" . $labels[$index] . "' at screen " . (string)$loc[0] . ", " . (string)$loc[1] . "\n";
                $labelIndex++;
            }
        }
    }

    /**
     * Set plot output file to be used in plotting script
     * @param string $saveas- name of output file
     * @param string $format- format of output file (defaults to png)
     */
    public function plotOutput($fileName, $format = 'png') {
        $this->outputFileName = "$fileName.$format";
        $this->plotAttribs['output'] = "set output '" . "$this->outputDir/$this->outputFileName'\n";
    }

    /**
     * Return the full filename to be used for output.  Suitable for embedding in a URL.
     * This prevents the calling code from specifying whether it is .png, .jpg, etc.
     */
    public function getOutputFileName() {
        return $this->outputFileName;
    }

    /**
     * Assign plot labels to be used in plotting script
     * @param array $labels- Axis labels in plot.
     * Keys should be x,y,x2,y2, only include desired keys.
     */
    public function plotLabels($labels) {
        $this->plotAttribs['xlabel'] = "set xlabel '" . $labels['x'] . "'\n";
        if ($labels['y']) {
            $this->plotAttribs['ylabel'] = "set ylabel '" . $labels['y'] . "'\n";
        }
        if (isset($labels['y2'])) {
            $this->plotAttribs['y2label'] = "set y2label '" . $labels['y2'] . "'\n";
        }
    }

    /**
     * Assign plot Y-axis limits to be used in plotting script
     * @param array $ylims- min and max values for y-axis
     * Keys should be ymin,ymax,y2min,y2max. Only include desired keys.
     */
    public function plotYAxis($ylims) {
        $this->plotAttribs['yrange'] = "set yrange [". $ylims['ymin']. ":" . $ylims['ymax'] . "]\n";

        if (isset($ylims['y2min'])) {
            $this->plotAttribs['y2range'] = "set y2range [". $ylims['y2min'] . ":" . $ylims['y2max'] . "]\n";
        }
    }

    /**
     * Assign plot X-axis limits to be used in plotting script
     * @param array $xlims- min and max values for x-axis
     * Keys should be xmin,xmax,x2min,x2max. Only include desired keys.
     */
    public function plotXAxis($xlims) {
        $this->plotAttribs['xrange'] = "set xrange [". $xlims['xmin']. ":" . $xlims['xmax'] . "]\n";

        if (isset($xlims['x2min'])) {
            $this->plotAttribs['x2range'] = "set x2range [". $xlims['x2min'] . ":" . $xlims['x2max'] . "]\n";
        }
    }

    /**
     * Set plot Y-axis tics to be used in plotting script
     * @param 2d array $ytics:
     * Keys are ytics, y2tics.
     * Each subarray should be set to an array of floats of desired ytic values,
     * or False indicating if tics should not be present
     * or empty indicating default tics should be present
     */
    public function plotYTics($ytics) {
        if($ytics['ytics'] == FALSE) {
            $this->plotAttribs['ytics'] = "unset ytics\n";

        } elseif(is_array($ytics['ytics'])) {
            $ytic = $ytics['ytics'];
            $temp = "set ytics (";
            $LO = array_keys($ytic);
            foreach($LO as $L) {
                $temp .= "'0 dB' " . $ytic[$L][0] . ",'" . (string)(round($ytic[$L][1] - $ytic[$L][0], 2)) . " dB' " . $ytic[$L][1] . ",";
            }
            $temp = substr($temp, 0, -1);
            $temp .= ")\n";
            $this->plotAttribs['ytics'] = $temp;
        }
        if(isset($ytics['y2tics'])) {
            $ytic = $ytics['y2tics'];
            if(is_array($ytic)) {
                $temp = "set y2tics (";
                $LO = array_keys($ytic);
                foreach($LO as $L) {
                    $temp .= "'" . $L . " GHz' " . $ytic[$L] . " ,";
                }
                $temp = substr($temp, 0, -1);
                $temp .= ")\n";
                $this->plotAttribs['y2tics'] = $temp;
            } else {
                $this->plotAttribs['y2tics'] = "set y2tics\n";
            }
        }
    }

    /**
     * Assign plot lines to be used in plotting script
     * @param array $lineAtt- Strings indicating parameters for each line to be plotted.
     * @param int $count- number of lines to be plotted, number of files created by createTempFile()
     */
    public function plotData($lineAtt, $count) {
        $temp = "plot ";
        for ($k=0; $k<$count; $k++) {
            $temp .= "'" . "$this->outputDir/temp_data$k.txt'";
            $temp .= " using 1:2 with $lineAtt[$k],";
        }
        $temp = substr($temp, 0, -1);    // remove final comma

        if(isset($this->plotAttribs['specAtt'])) {
            $add = $this->plotAttribs['specAtt'];
            $temp .= $add;
            unset($this->plotAttribs['specAtt']);
        }

        $this->plotAttribs['plot'] = $temp . "\n";
    }

    /**
     * Generates plotting script
     * @return string- entire script to be passed to GNUPLOT
     */
    public function genPlotCode() {
        $plot_code = "";
        $keys = array_keys($this->plotAttribs);
        foreach ($keys as $k) {
            $plot_code .= $this->plotAttribs[$k];
        }
        return $plot_code;
    }

    /**
     * Initializes plotting script, saves the file, and runs it through GNUPLOT
     * @param string $plot_code- entire script to be passed to GNUPLOT. Can be generated by genPlotCode()
     */
    public function setPlotter($plot_code) {
        $this->commandFile = "$this->outputDir/plot_script.txt";
        $f = fopen($this->commandFile, 'w');
        fwrite($f, $plot_code);
        fclose($f);
    }

    /**
     * Generate the plotting script and execute it.
     */
    public function doPlot() {
        $this->setPlotter($this->genPlotCode()); // Generates and saves plotting script
        system("$this->gnuplot $this->commandFile > $this->outputDir/std_output.txt");
    }

}

?>
