<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.testdata_header.php');

if (!isset($GNUPLOT_VER)) {
    global $GNUPLOT_VER;
    $GNUPLOT_VER = 4.9;
}

class cca_image_rejection extends TestData_header {

    public function __construct($keyHeader, $fc) {
        parent::__construct($keyHeader, $fc);
    }

    public function drawPlot() {
        // set Plot Software Version
        $Plot_SWVer = "1.0.2";

        /*
         * version 1.0.2:  MTM fixed "set...screen" commands to gnuplot
         */

        $this->Plot_SWVer = $Plot_SWVer;
        $this->Update();

        // start a logger file for debugging
        $l = new Logger("CCA_Image_Rejection.txt");
        //Get CCA Serial Number
        $q = "SELECT SN FROM FE_Components
            WHERE keyID={$this->fkFE_Components}";
        $r = mysqli_query($this->dbConnection, $q);
        $l->WriteLogFile("CCA SN Query: $q");
        $CCA_SN = ADAPT_mysqli_result($r, 0, 0);
        $l->WriteLogFile("CCA SN: $CCA_SN");

        // start plotting code

        //main_write_driectory is defined in config_main.php
        require(site_get_config_main());

        $plotdir = $main_write_directory . "CCA_Image_Rejection/";
        //Create plot directory if it doesn't exist.
        if (!file_exists($plotdir)) {
            mkdir($plotdir);
        }

        // create data files to populate and plot
        $datafile_cart_0_1_name =  "Image_Reject_Cart_pol0_SB1.txt";
        $datafile_cart_0_1 = $plotdir . $datafile_cart_0_1_name;
        $l->WriteLogFile("datafile_cart pol 0 SB1: $datafile_cart_0_1");
        $fc01 = fopen($datafile_cart_0_1, 'w');

        $datafile_cart_0_2_name =  "Image_Reject_Cart_pol0_SB2.txt";
        $datafile_cart_0_2 = $plotdir . $datafile_cart_0_2_name;
        $l->WriteLogFile("datafile_cart pol 0 SB2: $datafile_cart_0_2");
        $fc02 = fopen($datafile_cart_0_2, 'w');

        $datafile_cart_1_1_name =  "Image_Reject_Cart_pol1_SB1.txt";
        $datafile_cart_1_1 = $plotdir . $datafile_cart_1_1_name;
        $l->WriteLogFile("datafile_cart pol 1 SB1: $datafile_cart_1_1");
        $fc11 = fopen($datafile_cart_1_1, 'w');

        $datafile_cart_1_2_name =  "Image_Reject_Cart_pol1_SB2.txt";
        $datafile_cart_1_2 = $plotdir . $datafile_cart_1_2_name;
        $l->WriteLogFile("datafile_cart pol 1 SB2: $datafile_cart_1_2");
        $fc12 = fopen($datafile_cart_1_2, 'w');

        $spec_datafile_name =  "CCA_Image_Reject_spec.txt";
        $spec_datafile = $plotdir . $spec_datafile_name;
        $l->WriteLogFile("specifications datafile: $spec_datafile");
        $fspec = fopen($spec_datafile, 'w');

        // select Band specific IF frequency ranges
        switch ($this->Band) {
            case 1:
                $upper_freq_limit = 45;
                $lower_freq_limit = 31.3;
                $IR_90_spec = 10;
                $IR_spec = 7;
                break;
            case 2:
                $upper_freq_limit = 116;
                $lower_freq_limit = 67;
                $IR_90_spec = 10;
                $IR_spec = 7;
                break;
            case 3:
                $upper_freq_limit = 116;
                $lower_freq_limit = 84;
                $TSSB_80_spec = 39;
                $IR_90_spec = 10;
                $IR_spec = 7;
                break;
            case 4:
                $upper_freq_limit = 163;
                $lower_freq_limit = 125;
                $IR_90_spec = 10;
                $IR_spec = 7;
                break;
            case 5:
                $upper_freq_limit = 211;
                $lower_freq_limit = 163;
                $IR_90_spec = 10;
                $IR_spec = 7;
                break;
            case 6:
                $upper_freq_limit = 275;
                $lower_freq_limit = 211;
                $IR_90_spec = 10;
                $IR_spec = 7;
                break;
            case 7:
                $upper_freq_limit = 373;
                $lower_freq_limit = 275;
                $IR_90_spec = 10;
                $IR_spec = 7;
                break;
            case 8:
                $upper_freq_limit = 500;
                $lower_freq_limit = 385;
                $IR_90_spec = 10;
                $IR_spec = 7;
                break;
            case 9:
                $upper_freq_limit = 720;
                $lower_freq_limit = 602;
                $IR_90_spec = 3;
                $IR_spec = 3;
                break;
            case 10:
                $upper_freq_limit = 950;
                $lower_freq_limit = 787;
                $IR_90_spec = 10;
                $IR_spec = 7;
                break;
        }

        // write specifications datafile
        $string1 = "$lower_freq_limit\t$IR_90_spec\t$IR_spec\r\n";
        $string2 = "$upper_freq_limit\t$IR_90_spec\t$IR_spec\r\n";
        $writestring = $string1 . $string2;
        fwrite($fspec, $writestring);
        fclose($fspec);

        //***************************************************
        //Create data file from database
        //***************************************************
        $q = "SELECT FreqLO, CenterIF, POL, SB, SBR
              FROM CCA_TEST_SidebandRatio
              WHERE fkFacility = {$this->keyFacility}
              AND fkHeader = {$this->keyId}
              ORDER BY POL DESC, SB DESC, FreqLO ASC, CenterIF DESC";
        $r = mysqli_query($this->dbConnection, $q);
        $l->WriteLogFile("CCA Image Rejection Data Query: $q");

        $last_freq = '';
        while ($row = mysqli_fetch_array($r)) {
            // pol 1 SB2
            if ($row[2] == 1 && $row[3] == 2) {
                $IR_LSB_Pol1_Sb2 = $row[0] - $row[1];
                $IR_Pol1_Sb2 = abs($row[4]);
                $writestring = "$IR_LSB_Pol1_Sb2\t$IR_Pol1_Sb2\r\n";
                // insert new line to make plot data look better
                if ($last_freq != $row[0]) {
                    $last_freq = $row[0];
                    $writestring = "\r\n$writestring";
                }
                fwrite($fc12, $writestring);

                // pol 1 SB1 or band 9 pol1 SB0
            } else if ($row[2] == 1 && ($row[3] == 1 || $row[3] == 0)) {
                $IR_USB_Pol1_Sb1 = $row[0] + $row[1];
                $IR_Pol1_Sb1 = abs($row[4]);
                $writestring = "$IR_USB_Pol1_Sb1\t$IR_Pol1_Sb1\r\n";
                // insert new line to make plot data look better
                if ($last_freq != $row[0]) {
                    $last_freq = $row[0];
                    $writestring = "\r\n$writestring";
                }
                fwrite($fc11, $writestring);

                // pol 0 SB2
            } else if ($row[2] == 0 && $row[3] == 2) {
                $IR_LSB_Pol0_Sb2 = $row[0] - $row[1];
                $IR_Pol0_Sb2 = abs($row[4]);
                $writestring = "$IR_LSB_Pol0_Sb2\t$IR_Pol0_Sb2\r\n";
                // insert new line to make plot data look better
                if ($last_freq != $row[0]) {
                    $last_freq = $row[0];
                    $writestring = "\r\n$writestring";
                }
                fwrite($fc02, $writestring);

                // pol 0 SB1 or band 9 pol1 SB0
            } else if ($row[2] == 0 && ($row[3] == 1 || $row[3] == 0)) {
                $IR_USB_Pol0_Sb1 = $row[0] + $row[1];
                $IR_Pol0_Sb1 = abs($row[4]);
                $writestring = "$IR_USB_Pol0_Sb1\t$IR_Pol0_Sb1\r\n";
                // insert new line to make plot data look better
                if ($last_freq != $row[0]) {
                    $last_freq = $row[0];
                    $writestring = "\r\n$writestring";
                }
                fwrite($fc01, $writestring);
            }
        }
        fclose($fc01);
        fclose($fc02);
        fclose($fc11);
        fclose($fc12);

        //common plotting code
        $plot_label_1 = "set label 'TestData_header.keyId: {$this->keyId}, Plot SWVer: {$Plot_SWVer}, Meas SWVer: {$this->Meas_SWVer}' at screen 0.01, 0.01\r\n";
        $plot_label_2 = "set label '{$this->TS}, FE Component {$this->fkFE_Components}' at screen 0.01, 0.04\r\n";

        // start loop for both plots
        for ($cnt = 0; $cnt < 2; $cnt++) {
            $plot_title = "CCA{$this->Band}-{$CCA_SN} Image Rejection, Pol {$cnt}";


            ///        $lower_freq_limit-$upper_freq_limit GHz IF
            //         CCA"; //. $this->Band.
            //        "-$CCA_SN, Pol $cnt";
            $imagename = "CCA_Image_Rejection Pol$cnt " . date('Y_m_d_H_i_s') . ".png";
            $imagepath = $plotdir . $imagename;
            $l->WriteLogFile("image path: $imagepath");

            // Create GNU plot command file
            $commandfile = $plotdir . "plotcommands_$cnt.txt";
            $f = fopen($commandfile, 'w');
            $l->WriteLogFile("command file: $commandfile");
            fwrite($f, "set terminal png size 900,600 crop\r\n");
            if ($GNUPLOT_VER >= 5.0)
                fwrite($f, "set colorsequence classic\r\n");
            fwrite($f, "set output '$imagepath'\r\n");
            fwrite($f, "set title '$plot_title'\r\n");
            fwrite($f, "set xlabel 'Signal Frequency (GHz)'\r\n");
            fwrite($f, "set ylabel 'Image Rejection (dB)'\r\n");
            fwrite($f, "set key outside\r\n");
            fwrite($f, "set bmargin 6\r\n");
            fwrite($f, $plot_label_1);
            fwrite($f, $plot_label_2);
            // if statement to plot pol 0 and 1 graphs
            if ($this->Band == 9) {
                fwrite($f, "set yrange [0:5]\r\n");
                if ($cnt == 0) {
                    fwrite($f, "plot '$datafile_cart_0_1' using 1:2 with  linespoints lt 1 title 'Pol$cnt',");
                } else {
                    fwrite($f, "plot '$datafile_cart_1_1' using 1:2 with linespoints lt 1 title 'Pol$cnt',");
                }
                fwrite($f, "'$spec_datafile' using 1:3 with lines lt -1 lw 3 title ' Spec: All below $IR_spec dB\r\n',");
            } else {
                fwrite($f, "set yrange [0:60]\r\n");
                if ($cnt == 0) {
                    fwrite($f, "plot '$datafile_cart_0_1' using 1:2 with lines lt 1 title 'Pol$cnt USB',");
                    fwrite($f, "'$datafile_cart_0_2' using 1:2 with lines lt 2 title 'Pol$cnt LSB',");
                } else {
                    fwrite($f, "plot '$datafile_cart_1_1' using 1:2 with lines lt 1 title 'Pol$cnt USB',");
                    fwrite($f, "'$datafile_cart_1_2' using 1:2 with lines lt 2 title 'Pol$cnt LSB',");
                }
                fwrite($f, "'$spec_datafile' using 1:2 with lines lt 0 lw 1 title ' Spec: 90% above $IR_90_spec dB',");
                fwrite($f, "'$spec_datafile' using 1:3 with lines lt -1 lw 3 title ' Spec: None below $IR_spec dB\r\n',");
            }
            fclose($f);

            //Call gnuplot
            system("$GNUPLOT $commandfile");
            // save urls in an array
            $image_url[] = $main_url_directory . "CCA_Image_Rejection/$imagename";
        }
        //***************************************************
        //Update plot url in TestData_header
        //***************************************************
        $image_url_string = implode(",", $image_url);
        $this->PlotURL = $image_url_string;
        $this->Update();
    }

    public function displayPlots() {
        $saved_PlotURL = $this->PlotURL;
        $URL_array = explode(",", $saved_PlotURL);
        echo "<img src= '" . $URL_array[0] . "'><br><br><img src= '" . $URL_array[1] . "'><br><br>";
    }
}
