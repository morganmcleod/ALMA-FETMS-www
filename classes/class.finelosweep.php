<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.logger.php');
require_once($site_classes . '/class.spec_functions.php');

class FineLOSweep extends TestData_header {
    private $FLOSweepSubHeader; // array for subheader objects from TEST_FineLOSweep_SubHeader (class.generictable.php)

    public function Initialize_FineLOSweep($in_keyId, $in_fc){
        parent::Initialize_TestData_header($in_keyId, $in_fc);

        $q = "SELECT keyId, keyFacility FROM TEST_FineLOSweep_SubHeader
              WHERE fkHeader = $in_keyId AND keyFacility = $in_fc
              order by keyId ASC;" ;
        $r = @mysql_query($q, $this->dbconnection);

        // create tables for FineLOSweep SubHeaders for both polarization states
        $cnt = 0;
        while ($row = @mysql_fetch_array($r)){
            $keyID = $row[0];
            $facility = $row[1];
            $this->FLOSweepSubHeader[$cnt] = new GenericTable();
            $this->FLOSweepSubHeader[$cnt]->Initialize('TEST_FineLOSweep_SubHeader',$keyID,'keyId',$facility,'keyFacility');
            $cnt++;
        }
    }

    public function DrawPlot(){
    // set Plot Software Version
        $Plot_SWVer = "1.2.0";
    /*
     *  1.2.0:  Added Export()
     * 	1.1.0:  Now pulls specifications from new class that pulls from files instead of database.
     *  1.0.8:  MTM scale Y-axis maximum to the SIS current data rather than fixed 100 uA.
     *  1.0.7:  MTM fixed "set...screen" commands to gnuplot
     *  1.0.6:  MTM updated plotting to show measured TS rather than TS from the TestDataHeader
     */
        $this->SetValue('Plot_SWVer',$Plot_SWVer);
        $this->Update();

        //main_write_driectory is defined in config_main.php
        require(site_get_config_main());

        $plotdir = $main_write_directory . "flosweep/";
        //Create plot directory if it doesn't exist.
        if (!file_exists($plotdir)){
            mkdir($plotdir);
        }

    // start a logger file for debugging
        $l = new Logger("FineLOSweepLog.txt");
    //Get CCA Serial Number
        $q ="SELECT FE_Components.SN FROM FE_Components, FE_ConfigLink, FE_Config
             WHERE FE_ConfigLink.fkFE_Config = " .$this->GetValue('fkFE_Config'). "
             AND FE_Components.fkFE_ComponentType = 20
             AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
             AND FE_Components.Band = " . $this->GetValue('Band') . "
             AND FE_Components.keyFacility =" . $this->GetValue('keyFacility') ."
             AND FE_ConfigLink.fkFE_ConfigFacility = FE_Config.keyFacility
             GROUP BY Band ASC;";
            $r = @mysql_query($q,$this->dbconnection);
        $l->WriteLogFile("CCA SN Query: $q");
        $CCA_SN = @mysql_result($r,0,0);
        $l->WriteLogFile("CCA SN: $CCA_SN");

    //Get WCA Serial Number
        $q ="SELECT FE_Components.SN FROM FE_Components, FE_ConfigLink, FE_Config
             WHERE FE_ConfigLink.fkFE_Config = " .$this->GetValue('fkFE_Config'). "
             AND FE_Components.fkFE_ComponentType = 11
             AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
             AND FE_Components.Band = " . $this->GetValue('Band') . "
             AND FE_Components.keyFacility =" . $this->GetValue('keyFacility') ."
             AND FE_ConfigLink.fkFE_ConfigFacility = FE_Config.keyFacility
             GROUP BY Band ASC;";
            $r = @mysql_query($q,$this->dbconnection);
        $l->WriteLogFile("WCA SN Query: $q");
        $WCA_SN = @mysql_result($r,0,0);
        $l->WriteLogFile("WCA SN: $WCA_SN");

    // start loop to plot graphs for all tests
      $plot_cnt=count($this->FLOSweepSubHeader);// find out how many plots
      for ($cnt = 0; $cnt < $plot_cnt; $cnt++){

        $imagename = "FineLOSweep $cnt " . date('Y_m_d_H_i_s') . ".png";
        $imagepath = $plotdir . $imagename;
        $l->WriteLogFile("image path: $imagepath");

        //***************************************************
        //Create data file from database
        //***************************************************
        $datafile_name =  "FineLOSweep $cnt.txt";
        $datafile = $plotdir . $datafile_name;
        $l->WriteLogFile("datafile: $datafile");
        $fh = fopen($datafile,'w');

        $datafile_name =  "FineLOSweep_spec1 $cnt.txt";
        $spec1_datafile = $plotdir . $datafile_name;
        $l->WriteLogFile("spec_datafile: $spec1_datafile");
        $fspec1 = fopen($spec1_datafile,'w');

        $q = "SELECT FreqLO, SIS1Current, SIS2Current, LOPADrainSetting, LOPADrainVMonitor
         FROM TEST_FineLOSweep
         WHERE fkFacility = " .$this->GetValue('keyFacility') . "
         AND fkSubHeader = ".$this->FLOSweepSubHeader[$cnt]->GetValue('keyId') . "";
        $r = @mysql_query($q,$this->dbconnection);
        $l->WriteLogFile("FineLOSweep get Data query: $q");

        $max_freq = 0;
        $min_freq = 10000;
        $max_sis = 0;

        unset($LO_freq);
        unset($PA_set);
        while ($row = @mysql_fetch_array($r)) {
            $LO_freq[] = $row[0]; // save frequencies for processing
            $PA_set[] = $row[3];    // save PA_sets for processing
            if ($row[0] > $max_freq) {
                $max_freq = $row[0];
            }
            if ($row[0] < $min_freq) {
                $min_freq = $row[0];
            }
            if ($row[1] > $max_sis) {
                $max_sis = $row[1];
            }
            if ($row[2] > $max_sis) {
                $max_sis = $row[2];
            }
            //Write the data to a file for gnuplot
            $writestring = "$row[0]\t$row[1]\t$row[2]\t\t$row[3]\t\t$row[4]\r\n";
            fwrite($fh,$writestring);
        }
        fclose($fh);

        //Round Y-axis maximum up to the nearest 20 uA:
        $max_sis = ceil($max_sis / 20) * 20;

        //***************************************************
        // flag data that doesn't meet spec
        //***************************************************

        //get specs
        $new_spec = new Specifications();
        //$specs = get_specs ( 59, $this->GetValue('Band') );
        $specs = $new_spec->getSpecs('FLOSweep', $this->GetValue('Band'));

        // find outliers
       	$new_spec->stdevChks($PA_set, $LO_freq, $specs['FLOSpts_win'], $specs['FLOSstdev'], $fspec1);

        fclose($fspec1);

        //***************************************************
        //Create gnuplot command file
        //***************************************************
        $commandfile = $plotdir . "plotcommands.txt";
        $fh = fopen($commandfile,'w');
        $l->WriteLogFile("command file: $commandfile");
        $plot_title = "Fine LO Sweep, FE SN" .$this->FrontEnd->GetValue('SN').
        ", Band " . $this->GetValue('Band').
        " CCA SN$CCA_SN WCA SN$WCA_SN, Pol" . $this->FLOSweepSubHeader[$cnt]->GetValue('Pol')
        . ", Elevation ". $this->FLOSweepSubHeader[$cnt]->GetValue('TiltAngle_Deg')."";
        fwrite($fh, "set terminal png size 900,600 crop\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set xrange [$min_freq:$max_freq]\r\n");
        fwrite($fh, "set xlabel 'LO Frequency (GHz)'\r\n");
        fwrite($fh, "set ylabel 'SIS current (uA)'\r\n");
        fwrite($fh, "set yrange [0:$max_sis]\r\n");
        fwrite($fh, "set y2label 'LO PA drain monitor/control'\r\n");
        fwrite($fh, "set y2tics\r\n");
        fwrite($fh, "set y2range [0:5]\r\n");
        fwrite($fh, "set key outside\r\n");
        fwrite($fh, "set bmargin 6\r\n");
        fwrite($fh, "set label 'TestData_header.keyId: $this->keyId, Plot SWVer: $Plot_SWVer, Meas SWVer: ".$this->GetValue('Meas_SWVer')."' at screen 0.01, 0.01\r\n");
        fwrite($fh, "set label '".$this->FLOSweepSubHeader[$cnt]->GetValue('TS').", FE Configuration ".$this->GetValue('fkFE_Config')."' at screen 0.01, 0.04\r\n");
        fwrite($fh, "set pointsize 2\r\n");
        fwrite($fh, "plot  '$datafile' using 1:2 with lines lt 1 title 'IJ1 uA',");
        fwrite($fh, "'$datafile' using 1:3 with lines lt 2 title 'IJ2 uA',");
        fwrite($fh, "'$datafile' using 1:4 with lines lt 3 axes x1y2 title 'PA drain control (set)',");
        fwrite($fh, "'$datafile' using 1:5 with lines lt 4 axes x1y2 title 'PA drain V',");
        fwrite($fh, "'$spec1_datafile' using 1:2 with points lt -1 pt 3 axes x1y2 title '"."$specs[8]pt window, $specs[9] stdev"."'\r\n");
        fclose($fh);

        //Call gnuplot
        system("$GNUPLOT $commandfile");

        //***************************************************
        //Update plot url in TestData_header
        //***************************************************
        $image_url = $main_url_directory . "flosweep/$imagename";


        $this->FLOSweepSubHeader[$cnt]->SetValue('ploturl1',$image_url);
        $this->FLOSweepSubHeader[$cnt]->Update();
        }
    }

    public function DisplayPlots(){
        $plot_cnt = count($this->FLOSweepSubHeader);// find out how many plots
        for ($cnt = 0; $cnt < $plot_cnt; $cnt++){
            echo "<img src= '" . $this->FLOSweepSubHeader[$cnt]->GetValue('ploturl1') . "'><br><br>";
        }
    }

    public function Export($outputDir) {
        $destFile = $outputDir . "FineLO_B" . $this->GetValue('Band') . "_H" . $this->TestDataHeader . ".ini";
        $handle = fopen($destFile, "w");
        fwrite($handle, "[export]\n");
        fwrite($handle, "band=" . $this->GetValue('Band') . "\n");
        fwrite($handle, "FEid=" . $this->fe_keyId . "\n");
        fwrite($handle, "CCAid=" . $this->GetValue('fkFE_Components') . "\n");
        fwrite($handle, "TDHid=" . $this->TestDataHeader . "\n");
        $plot_cnt = count($this->FLOSweepSubHeader);// find out how many plots
        for ($i = 0; $i < $plot_cnt; $i++) {
            fwrite($handle, "plot" . $i+1 . "=" . $this->FLOSweepSubHeader[$i]->GetValue('ploturl1') . "\n");
        }
        fclose($handle);
        echo "Exported '$destFile'.<br>";
        return $destFile;
    }
}
?>
