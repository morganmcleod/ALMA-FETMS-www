<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_dbConnect);
require_once($site_classes . '/class.spec_functions.php');

class plotter{
	var $data;	
	
	public function __construct() {}
	
	/**
	 * Sets data retrieved and calculated in noisetempcalc.php
	 * 
	 * @param 2d array $data- Data to be passed. 
	 * Data[x] = row x containing all data for one LO Freq and IF.
	 * Data[x][y] = value of data type y for row x.
	 */
	public function setData($data) {
		$this->data = $data;
	}
	
	/**
	 *
	 *@param int $band- band being plotted, used to get specs
	 * @param array $data_types- Array of strings representing which columns are desired to be plotted.
	 * 						One independent variable, any number of dependent variables
	 * 						(Ex. array('RF_usb', 'Tssb_corr01', 'Trx', 'diff'))
	 * @param array $line_att- Strings to tell GNUPLOT how data should be presented (Ex. lines ... title '...')
	 * @param array $ylim- Lower and upper y limit for plot
	 * note: $data should have one more element than $line_att
	 * 
	 * TODO: Allow more customability options
	 */
	public function generate_plots($band, $data_types, $line_att, $ylim) {
		$data = $this->data;
		$new_specs = new Specifications();
		$specs = $new_specs->getSpecs('FEIC_NoiseTemperature', $band);
		require(site_get_config_main());
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
		$i = 1;
		$count = 0;
		while($i<count($data_types)) {
			$x = array();
			$y = array();
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
			//array_multisort($x, $y);
			
			$temp_data_file = $main_write_directory . "NoiseTempLibrary/temp_data$count.txt";
			$f_temp = fopen($temp_data_file, 'w');
			for ($j=0; $j<count($x); $j++) {
				if (array_key_exists($j-1, $x)) {
					if($order == 'asc' && ($x[$j] < $x[$j-1])) {
						fwrite($f_temp, "\n");
					} 
					if($order == 'desc'&& ($x[$j] > $x[$j-1])) {
						fwrite($f_temp, "\n");
					}
				}
				if($y[$j] > 0) {
					fwrite($f_temp, $x[$j] . "\t" . $y[$j] . "\n");
				}
			}
			fclose($f_temp);
			$i++;
			$count++;
		}
		
		$xlabel = substr($data_types[0], 0, strpos($data_types[0],'_'));

		$plotter = $main_write_directory . 'NoiseTempLibrary/plot_script.txt';
			
		$plot_code = "";
		//Think about adding parameters for plot attributes
		$plot_code .= "set terminal png size 900,600 crop\n";
		$plot_code .= "set output '" . $main_write_directory . "NoiseTempLibrary/explot.png'\n";
		$plot_code .= "set xlabel '" . $xlabel . "'\n";
		$plot_code .= "set ylabel 'Tssb corrected (K)'\n";
		$plot_code .= "set y2label 'Difference from Spec(%)'\n";
		$plot_code .= "set y2tics\n";
		$plot_code .= "set y2range [0:120]\n";
		$plot_code .= "set key outside\n";
		$plot_code .= "set bmargin\n";
		$plot_code .= "set yrange [$ylim[0]:$ylim[1]]\n";
		$plot_code .= "plot ";
		for ($k=0; $k<$count; $k++) {
			$plot_code .= "'" . $main_write_directory . "NoiseTempLibrary/temp_data" . (string)$k. ".txt'";
			$plot_code .= " using 1:2 with $line_att[$k],";
		}
		$plot_code = substr($plot_code, 0, -1);
		$f = fopen($plotter, 'w');
		fwrite($f, $plot_code);
		fclose($f);
			
		system("$GNUPLOT $plotter");
	}
	
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
	 * Saves data in txt file for use in generate_plots()
	 * Note: If data value doesn't exist, prints NULL
	 */
	public function save_data() {
		require(site_get_config_main());
		$file_path = $main_write_directory . 'NoiseTempLibrary/data.txt';
			
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
}
?>