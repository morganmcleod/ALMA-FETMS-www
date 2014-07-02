<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_dbConnect);
require_once($site_classes . '/class.spec_functions.php');

/**
 * 
 * @author Aaron Beaudoin
 * 
 * Example code:
 * $plt = new plotter(); //initializes plotter class
 * $plt->setParams($data, 'NoiseTempLibrary', 6) //$data can be obtained through loadData() or calc classes.
 * //Initializes data structure, saves files to NoiseTempLibrary, and retrieves specs for band6
 * 
 * $plt->print_data //Print data to table in browser.
 * 
 * $plt->plotSize(900, 600); //Plot size with width 900, height 600
 * $plt->plotOutput('Band6 Pol0 Sb1 RF'); //output file name
 * $plt->plotTitle('Receiver Noise Temperature Tssb corrected, FE SN61, CCA6-61 WCA6-09'); //plot title
 * $plt->plotLabels(array('x' => 'LO (GHz)', 'y' => 'Tssb Corrected (K)', 'y2' => 'Difference from Spec (%)')); //Axes labels
 * $plt->plotYAxis(array('ymin' => 0, 'ymax' => 149.6, 'y2min' => 0, 'y2max' => 120)); //Y-axes ranges
 * $plt->checkIFLim('CenterIF'); //Removes data that doesn't meet IF specifications
 * $plt->createTempFile('RF_usb', 'Tssb_corr01', 'RF_usb', 0); //Creates files for lines
 * $plt->createTempFile('RF_usb', 'Trx01', 'RF_usb', 1);
 * $plt->createTempFile('RF_usb', 'diff01', 'RF_usb', 2);
 * $att = array();													//Line attributes to be passed to plotData()
 * $att[] = "lines lt 1 lw 3 title 'FEIC Meas Pol0 USB'";
 * $att[] = "lines lt 3 title 'Cart Group Meas Pol0 USB'";
 * $att[] = "points lt -1 axes x1y2 title 'Diff relative to Spec'";
 * $plt->plotData($att, 3); //Creates plot code using temp_data files
 * $plt->setPlotter($plt->genPlotCode()); //creates plotting script and writes to file.
 * system("$GNUPLOT $plt->plotter"); //creates plots and save to file name given by plotOutput
 *  
 * 
 */
class plotter{
	var $data;
	var $dir;
	var $specs;
	var $plotAtt;
	var $plotter;
	
	/**
	 * IF Spectrum plotter
	 */
	public function __construct() {}

	/**
	 * Sets data retrieved and calculated in noisetempcalc.php
	 *
	 * @param 2d array $data- Data to be passed.
	 * Data[x] = row x containing all data for one LO Freq and IF.
	 * Data[x][y] = value of data type y for row x.
	 * @param string $dir- Directory for test data to be placed
	 * NoiseTempLibrary, IFSpectrumLibrary, ...
	 * @param int $band
	 */
	public function setParams($data, $dir, $band) {
		$this->data = $data;
		$this->dir = $dir;
		$new_specs = new Specifications();
		$specs = $new_specs->getSpecs('FEIC_NoiseTemperature', $band);
		$this->specs = $specs;
	}


	/**
	 *
	 *@param int $band- band being plotted, used to get specs
	 * @param array $data_types- Array of strings representing which columns are desired to be plotted.
	 * 						One independent variable, any number of dependent variables
	 * 						(Ex. array('RF_usb', 'Tssb_corr01', 'Trx', 'diff'))
	 * @param array $line_att- Strings to tell GNUPLOT how data should be presented (Ex. lines ... title '...')
	 * note: $data should have one more element than $line_att
	 * @param array $ylim- Lower and upper y limit for plot (If two axes desired, list y1 limits then y2 limits)
	 * @param string $title
	 * @param string $saveas- name of png file to save plot as. (Do NOT include .png)
	 * @param array $labels- Axes labels, list x label first, then all y labels desired.
	 * @param boolean $plotSpec- True if specs are desired in plot.
	 * @param boolean $average- True if average desired from y variable.
	 *
	 *//*
	public function generate_plots($band, $data_types, $line_att, $ylim, $title, $saveas, $labels, $plotSpec, $average) {
		$data = $this->data;
		$new_specs = new Specifications();
		$specs = $new_specs->getSpecs('FEIC_NoiseTemperature', $band);
		require(site_get_config_main());
		
		//Removes data that don't lay within IF limits.
		$iflim = in_array('RF_usb', $data_types) || in_array('RF_lsb', $data_types);
		if ($iflim) {
			$new_data = array();
			foreach ($data as $d) {
				if ($specs['loIFLim'] <= $d['CenterIF'] && $d['CenterIF'] <= $specs['hiIFLim']) {
					$new_data[] = $d;
				}
			}
			$data = $new_data;
		}
		
		//Writes data and specs to files 
		$i = 1;
		$count = 0;
		while($i<count($data_types)) {
			$x = array();
			$y = array();
			
			//Determines if data is increasing or decreasing to put spaces between RF values.
			//Assumes data is sorted by RF, then IF.
			$order = '';
			if ($data[0][$data_types[0]] > $data[1][$data_types[0]]) {
				$order = 'desc';
			} else {
				$order = 'asc';
			}
			foreach($data as $d) {
				if (isset($d[$data_types[$i]])) {
					$x[] = $d[$data_types[0]];
					$y[] = $d[$data_types[$i]];
				}
			}
			
			//Calculates average based on first datatype
			//Assumes data is sorted by first datatype.
			if ($average) {
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
				$x = $new_x;
				$y = $averages;				
			}

			$spec_files = $main_write_directory . "NoiseTempLibrary/specData.txt";
			$temp_data_file = $main_write_directory . "NoiseTempLibrary/temp_data$count.txt";
			$f_temp = fopen($temp_data_file, 'w');
			$fSpec = fopen($spec_files, 'w');
			for ($j=0; $j<count($x); $j++) {
				if (array_key_exists($j-1, $x)) {
					//Writes blank line if different RF value.
					if($order == 'asc' && ($x[$j] < $x[$j-1])) {
						fwrite($f_temp, "\n");
						fwrite($fSpec, "\n");
					} 
					if($order == 'desc'&& ($x[$j] > $x[$j-1])) {
						fwrite($f_temp, "\n");
						fwrite($fSpec, "\n");
					}
				}
				if($y[$j] > 0) {
					fwrite($f_temp, $x[$j] . "\t" . $y[$j] . "\n");
					fwrite($fSpec, $x[$j] . "\t" . $specs['NT20'] . "\t" . $specs['NT80'] . "\n");
				}
			}
			fclose($f_temp);
			fclose($fSpec);
			$i++;
			$count++;
		}	

		$plotter = $main_write_directory . 'NoiseTempLibrary/plot_script.txt';
			
		$plot_code = "";
		//Creates script file for GNUPLOT
		$plot_code .= "set terminal png size 900,600 crop\n";
		$plot_code .= "set output '" . $main_write_directory . "NoiseTempLibrary/$saveas.png'\n";
		$plot_code .= "set title '" . $title . "'\n";
		$plot_code .= "set xlabel '" . $labels[0] . "'\n";
		$plot_code .= "set ylabel '" . $labels[1] . "'\n";
		if(count($ylim)>2) {
			$plot_code .= "set y2label '" . $labels[2] . "'\n";
			$plot_code .= "set y2tics\n";
			$plot_code .= "set y2range [$ylim[2]:$ylim[3]]\n";
		}
		$plot_code .= "set key outside\n";
		$plot_code .= "set bmargin\n";
		$plot_code .= "set yrange [$ylim[0]:$ylim[1]]\n";
		$plot_code .= "plot ";
		for ($k=0; $k<$count; $k++) {
			$plot_code .= "'" . $main_write_directory . "NoiseTempLibrary/temp_data" . (string)$k. ".txt'";
			$plot_code .= " using 1:2 with $line_att[$k],";
		}
		$plot_code = substr($plot_code, 0, -1);
		if($plotSpec) {
			$plot_code .= ",'" . $main_write_directory . "NoiseTempLibrary/specData.txt'";
			$plot_code .= "using 1:2 with lines lt -1 lw 3 title '" . $specs['NT20'] . " K (100%)'";
			$plot_code .= ",'" . $main_write_directory . "NoiseTempLibrary/specData.txt'";
			$plot_code .= "using 1:3 with lines lt 0 lw 1 title '" . $specs['NT80'] . " K (80%)'";
		}
		$f = fopen($plotter, 'w');
		fwrite($f, $plot_code);
		fclose($f);
			
		system("$GNUPLOT $plotter");
	}
	
	public function genIFSpectrum($LO, $band, $ifchannel) {
		$data = $this->data;
		require(site_get_config_main());
		
		$ytics = array();
		
		$ymin = 999;
		$ymax = -999;
		
		$i = 0;
		while($i<count($LO)) {
			$x = array();
			$y = array();
			
			foreach($data as $d) {
				if ($d["FreqLO"] == (float)$LO[$i]) {
					$pow = $d["Power_dBm"];
					$x[] = $d["Freq_Hz"];
					$y[] = $pow;
					if($pow < $ymin) {
						$ymin = $pow;
					}
					if($pow > $ymax) {
						$ymax = $pow;
					}
				}
			}
			
			$temp_data_file = $main_write_directory . "IFSpectrumLibrary/temp_data$i.txt";
			$f = fopen($temp_data_file, 'w');
			for ($j=0; $j<count($x); $j++) {
				fwrite($f, $x[$j] . "\t" . $y[$j] . "\n");
			}
			fclose($f);
			$ytics["$LO[$i]"] = $y[count($y) - 1];
			$i++;
		}
		
		$plotter = $main_write_directory . "$this->dir/plot_script.txt";
		
		$plot_code = "";
		$plot_code .= "set terminal png size 900,600\n";
		$plot_code .= "set output '" . $main_write_directory . $this->dir . "/$band" . "spuriousIF$ifchannel.png'\n";"
		$plot_code .= 'set title 'Spurious Noise, FE-61, Band $band SN 60 IF$ifchannel\n";
		$plot_code .= "set xlabel 'IF (GHz)'\n";
		$plot_code .= "set ylabel 'Power (dB)'\n";
		$plot_code .= "unset ytics\n";
		$plot_code .= "set y2tics (";
		foreach ($LO as $L) {
			$plot_code .= "'" . $L . " GHz' " . $ytics["$L"] . " ,";
		}
		$plot_code = substr($plot_code, 0, -1);
		$plot_code .= ")\n";
		/*$plot_code .= "set ytics (";
		foreach($LO as $L) {
			$plot_code .= "'0 dB' " . $ytics["$L"] . ",";
		}
		$plot_code = substr($plot_code, 0, -1);
		$plot_code .= ")\n";//*//*
		$plot_code .= "set grid\n";	
		$plot_code .= "set arrow 1 from 4,$ymin to 4,$ymax nohead lt -1 lw 2\n";
		$plot_code .= "set arrow 2 from 8,$ymin to 8,$ymax nohead lt -1 lw 2\n";
		$plot_code .= "set bmargin 7\n";
		$plot_code .= "set key reverse outside\n";
		$plot_code .= "set nokey\n";
		$plot_code .= "plot ";
		for ($k=0; $k<$i; $k++) {
			$plot_code .= "'" . $main_write_directory . $this->dir . "/temp_data" . (string)$k . ".txt'";
			$plot_code .= " using 1:2 with lines lt " . (string)($k + 1) . " title '" . $LO[$k] . " GHz',";
		}
		$plot_code = substr($plot_code, 0, -1);
		$f = fopen($plotter, 'w');
		fwrite($f, $plot_code);
		fclose($f);
		
		system("$GNUPLOT $plotter");
	}//*/

	/**
	 * Prints data in HTML table to browser
	 * Note: If data value doesn't exist, prints NULL
	 */
	public function print_data() {
		echo "<table border='1'>";
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
		echo "</table>";
	}

	/**
	 * Loads saved data from previous work
	 * @return 2d array- data structure used in the rest of the library
	 */
	public function loadData() {
		require(site_get_config_main());
		$file_path = $main_write_directory . "$this->dir/data.txt";
		$file = file($file_path);
		$keys = explode("\t", $file[0]);
		$data = array();
		for ($i = 1; $i<count($file); $i++) {
			$values = explode("\t", $file[$i]);
			$d = array();
			for ($j=0; $j<count($keys); $j++) {
				$d[$keys[$j]] = $values[$j];
			}
			$data[] = $d;
		}
		return $data;
	}
	
	/**
	 * Saves data in txt file for use in generate_plots()
	 * Note: If data value doesn't exist, prints NULL
	 */
	public function save_data() {
		require(site_get_config_main());
		$file_path = $main_write_directory . "$this->dir/data.txt";
			
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
	 * @param string $xvar- data type desired to find order
	 * @param string $index- index of data to check, initially 0.
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
	 * @param string $IFLabel- key for IF value in data structure
	 */
	public function checkIFLim($IFLabel) {
		$new_data = array();
		foreach ($this->data as $d) {
			if ($this->specs['loIFLim'] <= $d[$IFLabel] && $d[$IFLabel] <= $this->specs['hiIFLim']) {
				$new_data[] = $d;
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
	 * @param int $count- occurance in which function is called.
	 */
	public function createTempFile($xvar, $yvar, $sortBy, $count) {
		require(site_get_config_main());
		$x = array();
		$y = array();
	
		$order = $this->findOrder($sortBy);
	
		foreach ($this->data as $d) {
			if (isset($d[$yvar])) {
				$x[] = $d[$xvar];
				$y[] = $d[$yvar];
			}
		}
	
		$temp_data_file = $main_write_directory . "$this->dir/temp_data$count.txt";
		$f = fopen ($temp_data_file, 'w');
		fwrite($f, $x[0] . "\t" . $y[0] . "\n");
		for ($j=1; $j<count($x); $j++) {
			if ($order == 'asc' && ($x[$j] < $x[$j-1])) {
				fwrite($f, "\n");
			}
			if ($order == 'desc' && ($x[j] > $x[$j-1])) {
				fwrite($f, "\n");
			}
			if ($y[$j] > 0) {
				fwrite($f, $x[$j] . "\t" . $y[$j] . "\n");
			}
		}
		fclose($f);
	}
	
	/**
	 * Generates plot title to be used in plotting script
	 * @param string $title
	 */
	public function plotTitle($title) {
		$this->plotAtt['title'] = "set title '" . $title . "'\n";
	}
	
	/**
	 * Generates plot size to be used in plotting script
	 * @param int $xwin- Width of plot in pixels
	 * @param int $ywin- Height of plot in pixels
	 */
	public function plotSize ($xwin, $ywin) {
		$this->plotAtt['size'] = "set terminal png size $xwin,$ywin crop\n";
	}
	
	/**
	 * Generates plot output file to be used in plotting script
	 * @param string $saveas- name of output file
	 * @param string $format- format of output file (defaults to png)
	 */
	public function plotOutput($saveas, $format = 'png') {
		require(site_get_config_main());
		$this->plotAtt['output'] = "set output '" . $main_write_directory . "$this->dir/$saveas.format'\n";
	}
	
	/**
	 * Generates plot labels to be used in plotting script
	 * @param array $labels- Axis labels in plot.
	 * Keys should be x,y,x2,y2, only include desired keys.
	 */
	public function plotLabels($labels) {
		$this->plotAtt['xlabel'] = "set xlabel '" . $labels['x'] . "'\n";
		$this->plotAtt['ylabel'] = "set ylabel '" . $labels['y'] . "'\n";
		if (isset($labels['y2'])) {
			$this->plotAtt['y2label'] = "set y2label '" . $labels['y2'] . "'\n";
		}
	}
	
	/**
	 * Generates plot Y-axis limits to be used in plotting script
	 * @param array $ylims- min and max values for y-axis 
	 * Keys should be ymin,ymax,y2min,y2max. Only include desired keys.
	 */
	public function plotYAxis($ylims) {
		$this->plotAtt['yrange'] = "set yrange [". $ylims['ymin']. ":" . $ylims['ymax'] . "]\n";
		
		if (isset($ylims['y2min'])) {
			$this->plotAtt['y2range'] = "set y2range [". $ylims['y2min'] . ":" . $ylims['y2max'] . "]\n";
		}
	}
	
	/**
	 * Generates plot Y-axis tics to be used in plotting script
	 * @param 2d array $ytics:
	 * Keys are ytics, y2tics.
	 * Each subarray should be set to an array of floats of desired ytic values,
	 * or False indicating if tics should not be present
	 * or empty idicating default tics should be present
	 */
	public function plotYTics($ytics) {
		if($ytics['ytics'] == FALSE) {
			$this->plotAtt['ytics'] = "unset ytics\n";
		} elseif(is_array($ytics['ytics'])) {
			$ytic = $ytics['ytics'];
			$temp = "set ytics (";
			$LO = array_keys($ytic);
			foreach($LO as $L) {
				$temp .= "'0 dB' " . $ytic[$L] . ",";
			}
			$temp = substr($temp, 0, -1);
			$temp .= ")\n";
			$this->plotAtt['ytics'] = $temp;
		}
		if(isset($ytics['y2tics'])) {
			$ytic = $ytics['y2tics'];
			if(is_array($ytic)) {
				$temp = "set y2tics (";
				$LO = array_keys($ytic);
				foreach($LO as $L) {
					$temp .= "'" . $L . " GHz' " . $ytics["$L"] . " ,";
				}
				$temp = substr($temp, 0, -1);
				$temp .= ")\n";
				$this->plotAtt['y2tics'] = $temp;
			} else {
				$this->plotAtt['y2tics'] = "set y2tics\n";
			}
		}
	}
	
	/**
	 * Generates plot line to be used in plotting script
	 * @param array $lineAtt- Strings indicating parameters for each line to be plotted.
	 * @param int $count- number of lines to be plotted, number of files created by createTempFile()
	 */
	public function plotData($lineAtt, $count) {
		require(site_get_config_main());
		$temp = "plot ";
		for ($k=0; $k<$count; $k++) {
			$temp .= "'" .$main_write_directory . "$this->dir/temp_data$k.txt'";
			$temp .= " using 1:2 with $lineAtt[$k],";
		}
		$temp = substr($temp, 0, -1);
		$this->plotAtt['plot'] = $temp;
	}
	
	/**
	 * Generates plotting script
	 * @return string- entire script to be passed to GNUPLOT
	 */
	public function genPlotCode() {
		$plot_code = "";
		$keys = array_keys($this->plotAtt);
		foreach ($keys as $k) {
			$plot_code .= $this->plotAtt[$k];
		}
		return $plot_code;
	}
	
	/**
	 * Initializes plotting script, saves the file, and runs it through GNUPLOT
	 * @param string $plot_code- entire script to be passed to GNUPLOT. Can be generated by genPlotCode()
	 */
	public function setPlotter($plot_code) {
		require(site_get_config_main());
		$this->plotter = $main_write_directory . "$this->dir/plot_script.txt";
		$f = fopen($this->plotter, 'w');
		fwrite($f, $plot_code);
		fclose($f);
	}
}
?>