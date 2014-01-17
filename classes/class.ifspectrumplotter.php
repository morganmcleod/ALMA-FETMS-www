<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.pwrspectools.php');
require_once($site_classes . '/class.logger.php');
require_once($site_FEConfig . '/HelperFunctions.php');
require_once($site_dbConnect);

class IFSpectrumPlotter extends TestData_header{
    var $ifsubheaders;            //Array of records from IFSpectrum_SubHeader
    var $urls;                    //Array of records from TEST_IFSpectrum_urls
    var $plt;                     //class.dataplotter.php
    var $plotplotswversion;
    var $NoiseFloor;              //Record from TEST_IFSpectrum_NoiseFloor_Header (class.generictable.php)
    var $GNUPLOT_path;
    var $progressfile;            //ini file to store progress information during plot procedure.
    var $progressfile_fullpath;   //full path of ini file to store progress information during plot procedure.
    var $CCASN;                   //SN of the CCA
    var $aborted;                 //If 1, plotting procedure is aborted.

    var $DataSetBand;             //Band of the datasets
    var $DataSetGroup;            //TestData_header.DataSetGroup value for this set of traces
    var $DataSetFEConfig;         //FEConfig for this data set
    var $FEid;                    //Front_Ends.keyId value
    var $dbConnection;
    var $dbConnection2;
    var $FrontEnd;                //FrontEnd object
    var $FacilityCode;

    var $TDHkeys;                 //Array of TestData_header.keyId values for this data set group
    var $TDHkeyString;            //String containing TestData_header keys ("304,308,309,etc", used to append to plots.
    var $logger;                   //Debugging logger.
    var $TS;                      //Timestamp string

    public function __construct(){
        $this->plotswversion = "1.0.23";
        // 1.0.23 MTM: fixes so we can run with E_NOTICE enabled
        // 1.0.22 MTM: fix "set...screen" commands to gnuplot
        // 1.0.21 MTM: fix font color for Total and In-band power table.
        //        Fix using/displaying wrong noise floor profile for total and inband.
        require(site_get_config_main());
        $this->logger = new Logger('IFSpectrumPlotter.php.txt', 'w');
        $this->GNUPLOT_path = $GNUPLOT;
        $this->writedirectory = $main_write_directory;
        $this->url_directory = $main_url_directory;
        $swver = $this->plotswversion;
        $this->aborted = 0;
    }

    public function Initialize_IFSpectrum($inFEid, $inDataSetGroup, $infc, $Band){
        require_once(site_get_classes() . '/class.frontend.php');
        // TODO: really need a FrontEnd object here?

        $this->DataSetBand = $Band;
        $this->DataSetGroup = $inDataSetGroup;
        $this->FEid = $inFEid;

        $this->dbConnection = site_getDbConnection();
        $this->FacilityCode = $infc;

    //     $this->logger->NewLine();
    //     $this->logger->WriteLogFile('Initialize_IFSpectrum: fc=' . $this->FacilityCode . ' FEid=' . $this->FEid
    //                                   . ' band=' . $this->DataSetBand . ' group=' . $this->DataSetGroup);

        $qTDH = "SELECT TestData_header.keyId, TestData_header.TS
                FROM TestData_header, FE_Config
                WHERE TestData_header.DataSetGroup = $this->DataSetGroup
                AND TestData_header.fkTestData_Type = 7
                AND TestData_header.Band = " . $this->DataSetBand . "
                AND TestData_header.fkFE_Config = FE_Config.keyFEConfig
                AND FE_Config.fkFront_Ends = $this->FEid
                ORDER BY TestData_header.keyId ASC";

        $rTDH = @mysql_query($qTDH,$this->dbConnection);
        $count = 0;
        while($rowTDH = @mysql_fetch_Array($rTDH)){
            $this->TDHkeys[$count] = $rowTDH['keyId'];
            $this->TS = $rowTDH['TS'];
    //         $this->logger->WriteLogFile('Initialize_IFSpectrum: TDHkey=' . $this->TDHkeys[$count] . ' TS=' . $this->TS);
            $count += 1;
        }

        //Initialize urls
        $qurl = "SELECT keyId, IFChannel FROM TEST_IFSpectrum_urls
                 WHERE((TEST_IFSpectrum_urls.fkHeader =  " . $this->TDHkeys[0] . ") ";

        //Display for all TDH keys
        for ($iTDH=1;$iTDH<count($this->TDHkeys);$iTDH++){
            //Iterate through each TDHkey value and generate power data for each one.
            $qurl .= " OR (TEST_IFSpectrum_urls.fkHeader =  " . $this->TDHkeys[$iTDH] . ") ";
        }

        $qurl .= ") ORDER BY IFChannel ASC;";

    //    $this->logger->WriteLogFile('Initialize_IFSpectrum: qurl=' . $qurl);

        $rurl = @mysql_query($qurl,$this->dbConnection);
        $numurl = @mysql_num_rows($rurl);

        if ($numurl > 0){
            $rurl = @mysql_query($qurl,$this->dbConnection);
            while ($rowurl = @mysql_fetch_array($rurl)){
                $ifchannel = $rowurl[1];
                $this->urls[$ifchannel] = new GenericTable();
                $this->urls[$ifchannel]->Initialize('TEST_IFSpectrum_urls',$rowurl[0],'keyId',40,'fkFacility');

    //             $this->logger->WriteLogFile('Initialize_IFSpectrum: ifchannel=' . $ifchannel);
            }

            //Get Noisefloor

            $qnf = "SELECT IFSpectrum_SubHeader.fkNoiseFloorHeader, IFSpectrum_SubHeader.Band
                    FROM TestData_header, IFSpectrum_SubHeader
                    WHERE TestData_header.keyId = " . $this->TDHkeys[0] .
                   " AND TestData_header.keyFacility = IFSpectrum_SubHeader.keyFacility
                    AND TestData_header.keyId = IFSpectrum_SubHeader.fkHeader LIMIT 1";

            $rnf = @mysql_query($qnf,$this->dbConnection);
            $this->NoiseFloor = new GenericTable();
            $this->NoiseFloor->Initialize('TEST_IFSpectrum_NoiseFloor_Header',@mysql_result($rnf,0,0),'keyId');
            $this->NoiseFloorHeader = $this->NoiseFloor->keyId;

    //         $this->logger->WriteLogFile('Initialize_IFSpectrum: NoiseFloorHeader=' . $this->NoiseFloorHeader);

        }//end if numurl > 0

        switch ($this->DataSetBand) {
            case "3":
                $this->fWindow_Low  = 4  * pow(10,9);
                $this->fWindow_High = 8  * pow(10,9);
                break;
            case "4":
                $this->fWindow_Low  = 4  * pow(10,9);
                $this->fWindow_High = 8  * pow(10,9);
                break;
            case "6":
                $this->fWindow_Low  = 6  * pow(10,9);
                $this->fWindow_High = 10  * pow(10,9);
                break;
            case "7":
                $this->fWindow_Low  = 4  * pow(10,9);
                $this->fWindow_High = 8  * pow(10,9);
                break;
            case "8":
                $this->fWindow_Low  = 4  * pow(10,9);
                $this->fWindow_High = 8  * pow(10,9);
                break;
            case "9":
                $this->fWindow_Low  = 4  * pow(10,9);
                $this->fWindow_High = 12  * pow(10,9);
                break;
            case "10":
                $this->fWindow_Low  = 4  * pow(10,9);
                $this->fWindow_High = 12  * pow(10,9);
                break;
        }

        $this->CCASN = 0;
        $this->FrontEnd = new FrontEnd();
        $this->FrontEnd->Initialize_FrontEnd($this->FEid, $this->FacilityCode);

        if ($this->FrontEnd->ccas[$this->DataSetBand]->keyId != ''){
            $this->CCASN = $this->FrontEnd->ccas[$this->DataSetBand]->GetValue('SN');
        }

    //     $this->logger->WriteLogFile('Initialize_IFSpectrum: CCASN=' . $this->CCASN);

        //Get string with all test dataheader keys
        $this->TDHkeyString = $this->TDHkeys[0];
        for ($iTDH=1;$iTDH<count($this->TDHkeys);$iTDH++){
            $this->TDHkeyString .= ", " . $this->TDHkeys[$iTDH];
        }
    }


    public function CreateNewProgressFile($fc, $DataSetGroup){
        //Create progress update ini file
        require(site_get_config_main());
        $testmessage = "IF Spectrum FE" . $this->FrontEnd->GetValue('SN') . " Band " . $this->DataSetBand;

        $url = '"' . $rootdir_url . 'FEConfig/ifspectrum/ifspectrumplots.php?fc=' . $fc
                   . '&fe=' . $this->FEid . '&b=' . $this->DataSetBand . '&g=' . $this->DataSetGroup . '"';
        $this->progressfile = CreateProgressFile($testmessage,'',$url);
        $this->progressfile_fullpath = $main_write_directory . $this->progressfile . ".txt";

    //     $this->logger->WriteLogFile('CreateNewProgressFile: url=' . $url . ' fullpath=' . $this->progressfile_fullpath);
    }


    // TODO:  MM this appears to be dead code for now:
    public function Display_NoiseFloorSelector(){
        /*
        $qnf = "SELECT Notes FROM TEST_IFSpectrum_NoiseFloor_Header
                WHERE keyId = $this->NoiseFloorHeader
                AND fkFacility = ".$this->GetValue('keyFacility').";";
        $rnf = @mysql_query($qnf,$this->dbConnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);
        $nflr = @mysql_result($rnf,0,0);
        if ($_REQUEST['showrawdata'] == 1){
            echo "Noise Floor Profile: $nflr<br>";
        }
        //Noise Floor selector
        $qnf = "SELECT keyId, Notes FROM TEST_IFSpectrum_NoiseFloor_Header
                WHERE fkFacility = ".$this->GetValue('keyFacility')."
                ORDER BY Notes ASC;";
        $rnf = @mysql_query($qnf,$this->dbConnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);

        echo '
            <p><div style="width:500px;height:80px; align = "left"></p>
            <!-- The data encoding type, enctype, MUST be specified as below -->
            <form enctype="multipart/form-data" action="' . $_SERVER['PHP_SELF'] . '" method="POST">';

        if ($_SERVER['SERVER_NAME'] == "webtest.cv.nrao.edu"){

            echo "Noise Floor Profile: <select name = 'nfheader' onChange = submit()>";
            echo "<option value = '0' selected = 'selected'>None</option>";
            while ($rownf = @mysql_fetch_array($rnf)){
                 if ($rownf[0] == $this->NoiseFloorHeader){
                     echo "<option value = $rownf[0] selected = 'selected'>$rownf[1]</option>";
                 }
                 else{
                     echo "<option value = $rownf[0]>$rownf[1]</option>";
                 }
             }
            echo "</select>";
            echo "<input type = 'hidden' name = 'keyheader' value = " . $this->keyId .">";
            echo "<input type = 'hidden' name = 'fc' value = " . $this->GetValue('keyFacility') .">";
            echo '</form>
                </div>';
        }
        */
    }

    public function DisplayTDHinfo(){
        //Display information for all TestData_header records
        echo "<br><br>
              <div style='height:900px;width:900px'>
              <table id = 'table1' border = '1'>";
        echo "<tr class = 'alt'><th colspan='3'>IF Spectrum data sets for TestData_header.DataSetGroup $this->DataSetGroup</th></tr>";
        echo "<tr><th>Key</th><th>Timestamp</th><th>Notes</th></tr>";

        for ($i=0;$i<count($this->TDHkeys);$i++){
            if ($i % 2 == 0){
                $trclass = "alt";
            }
            if ($i % 2 != 0){
               $trclass = "";
            }
            $t = new TestData_header();
            $t->Initialize_TestData_header($this->TDHkeys[$i], $this->FacilityCode, 0);
            echo "<tr class = $trclass>";
            echo "<td>" . $t->keyId . "</td>";
            echo "<td>" . $t->GetValue('TS') . "</td>";
            echo "<td style='text-align:left !important;'>" . $t->GetValue('Notes') . "</td>";
            echo "</tr>";

        }
        echo "</table></div>";
    }


    public function Display_TotalPowerTable($in_ifchannel){

        echo "<div style = 'width:600px'>";
        echo "    <table id = 'table7' border = '1'>";

        echo "        <tr class = 'alt'><th colspan = '5' >Band " .$this->DataSetBand . " Total and In-Band Power";
        if (isset($this->NoiseFloor))
            echo "  <font color = '#cccccc'>(Noise Floor Profile: ".$this->NoiseFloor->GetValue('Notes').")</font>";
        echo "</th></tr>";

        //$minch = $in_ifchannel;
        //$maxch = $in_ifchannel;

        //$this->logger->WriteLogFile('Display_TotalPowerTable in_ifchannel=' . $in_ifchannel . ' minch=' . $minch .' maxch=' . $maxch);

        //for ($ifchannel = $minch; $ifchannel <= $maxch; $ifchannel++ ){

        $ifchannel = $in_ifchannel;

        echo "<tr><th colspan = '5'><b>IF Channel $ifchannel</b></th></tr>";
        echo "<tr>
              <td colspan='2'><i>0 dB Gain</i></td>
              <td colspan = 3 align = 'center' style='border-left:solid 1px #000000;'><i>15 dB Gain</i></td>
              </tr>";
        echo "<tr>
                  <td style='border-bottom:solid 1px'>LO (GHz)</td>
                  <td style='border-bottom:solid 1px'>In-Band (dBm)</td>
                  <td style='border-left:solid 1px #000000;border-bottom:solid 1px;'>In-Band (dBm)</td>
                  <td style='border-bottom:solid 1px'>Total (dBm)</td>
                  <td style='border-bottom:solid 1px'>Total - In-Band</td>
              </tr>";

        $qlo = "SELECT DISTINCT(FreqLO) FROM IFSpectrum_SubHeader
                WHERE ((fkHeader = " . $this->TDHkeys[0] . ")";

        //Display for all TDH keys
        for ($iTDH=1; $iTDH < count($this->TDHkeys); $iTDH++) {
            //Iterate through each TDHkey value and generate power data for each one.
            $qlo .= " OR (fkHeader =  " . $this->TDHkeys[$iTDH] . ") ";
        }

        $qlo .=  ") AND IFChannel = $ifchannel AND IsIncluded = 1 ORDER BY FreqLO ASC;";

        $rlo = @mysql_query($qlo,$this->dbConnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);

        $rowcount = 0;
        while ($rowlo = @mysql_fetch_array($rlo)){
            $lo = $rowlo[0];
            if ($lo > 0){
                if ($rowcount % 2 == 0){
                    $trclass = "alt";
                } else {
                    $trclass = "";
                }

                $q0 = "select IFSpectrum_SubHeader.FreqLO, ROUND(TEST_IFSpectrum_TotalPower.InBandPower,1)
                        from IFSpectrum_SubHeader, TEST_IFSpectrum_TotalPower
                        where IFSpectrum_SubHeader.FreqLO =$lo
                        and IFSpectrum_SubHeader.IFGain = 0
                        and IFSpectrum_SubHeader.IFChannel = $ifchannel
                        and TEST_IFSpectrum_TotalPower.fkSubHeader = IFSpectrum_SubHeader.keyId
                        and IFSpectrum_SubHeader.IsIncluded = 1
                        and ((IFSpectrum_SubHeader.fkHeader =  " . $this->TDHkeys[0] . ") ";

                //Display for all TDH keys
                for ($iTDH=1;$iTDH<count($this->TDHkeys);$iTDH++){
                    //Iterate through each TDHkey value and generate power data for each one.
                    $q0 .= " OR (IFSpectrum_SubHeader.fkHeader =  " . $this->TDHkeys[$iTDH] . ") ";
                }
                $q0 .= ");";
                $r0 = @mysql_query($q0,$this->dbConnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);



                $q15 = "select ROUND(TEST_IFSpectrum_TotalPower.InBandPower,1),
                ROUND(TEST_IFSpectrum_TotalPower.TotalPower,1)
                from IFSpectrum_SubHeader, TEST_IFSpectrum_TotalPower
                where IFSpectrum_SubHeader.FreqLO =$lo
                and IFSpectrum_SubHeader.IFGain = 15
                and IFSpectrum_SubHeader.IFChannel = $ifchannel
                and IFSpectrum_SubHeader.IsPAI = 1
                and TEST_IFSpectrum_TotalPower.fkSubHeader = IFSpectrum_SubHeader.keyId
                and ((IFSpectrum_SubHeader.fkHeader =  " . $this->TDHkeys[0] . ") ";

                //Display for all TDH keys
                for ($iTDH=1;$iTDH<count($this->TDHkeys);$iTDH++){
                    //Iterate through each TDHkey value and generate power data for each one.
                    $q15 .= " OR (IFSpectrum_SubHeader.fkHeader =  " . $this->TDHkeys[$iTDH] . ") ";
                }
                $q15 .= ");";
                $r15 = @mysql_query($q15,$this->dbConnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);


                $pwr_0 = round(@mysql_result($r0,0,1),1);
                $pwr_15_inband = round(@mysql_result($r15,0,0),1);

                $pwr_0 = number_format($pwr_0, 1, '.', '');
                $pwr_15_inband = number_format($pwr_15_inband, 1, '.', '');

                $inband_diff = abs($pwr_0 - $pwr_15_inband);

                echo "<tr class = $trclass><td><b>$lo</b></td>";

                // Color the background light red if the 0 dB and 15 dB results are not 15 +/- 1 dB apart:
                $redHilite = FALSE;
                if ($inband_diff < 14 || $inband_diff > 16)
                    $redHilite = TRUE;

                // Color the foreground red if the 0 dB gain value is >= -22 dBm:
                $fontcolor = "#000000";
                if ($pwr_0 > -22)
                    $fontcolor = "#FF0000";

                // Output the 0 dB gain in-band power.
                // TODO:  This is totally screwy that we're getting the red highlighting from the "table7" span CSS:
                echo "<td>";
                if ($redHilite)
                    echo "<span>";
                echo "<font color='$fontcolor'>$pwr_0</font>";
                if ($redHilite)
                    echo "</span>";
                echo "</td>";

                // Color the foreground red if the 15 dB gain value is <= -22 dBm:
                $fontcolor = "#000000";
                if ($pwr_15_inband < -22)
                    $fontcolor = "#FF0000";

                // Output the 15 dB gain in-band power:
                // TODO:  This is totally screwy that we're getting the red highlighting from the "table7" span CSS:
                echo "<td style='border-left:solid 1px #000000;'>";
                if ($redHilite)
                    echo "<span>";
                echo "<font color='$fontcolor'>$pwr_15_inband</font>";
                if ($redHilite)
                    echo "</span>";
                "</td>";

                // Computed and output the 15 dB gain total power:
                $pwr_total = round(@mysql_result($r15,0,1),1);
                $pwr_total = number_format($pwr_total, 1, '.', '');
                echo "<td><b>$pwr_total</b></td>";

                // Compute the 15 dB difference between total and in-band power:
                $pwrdiff = round(@mysql_result($r15,0,1) - @mysql_result($r15,0,0),1);
                $pwrdiff = number_format($pwrdiff, 1, '.', '');

                // Color the foreground red if there is more than 3 dB difference between total and in-band power:
                $fontcolor = "#000000";
                if ($pwrdiff > 3)
                    $fontcolor = "#ff0000";

                // Output the total minus in-band difference:
                echo "<td><font color = $fontcolor><b>$pwrdiff</b></font></td>";
                echo "</tr>";

                $rowcount += 1;
            }//end if $lo > 0
        }//end while rowlo

        //Meas date and FE Config info
        echo "<tr class = 'alt3'><th colspan = '5'>Front End Configuration: <a href='../ShowFEConfig.php?key="
        . $this->FrontEnd->feconfig_latest
        . "&fc=". $this->FacilityCode
        . "'><font color = '#0000ff'>"
        . $this->FrontEnd->feconfig_latest
        . "</a></font></th></tr>";

        echo "<tr class = 'alt3'><th colspan = '5'>class.ifspectrum version: " . $this->plotswversion ."</th></tr>";
        echo "<tr class = 'alt4'><th colspan = '5' >Meas Date: ". $this->TS. "</th></tr>";
        echo "</table></div>";
    }

    public function DisplayPowerVarFullBandTable(){
        $band = $this->DataSetBand;
        echo "<div style='width:400px' border='1'>";
        echo "<table id = 'table7' border='1'>";
        echo "<tr class='alt'><th colspan = '5'>Band $band Power Variation Full Band</th></tr>";
        echo "<tr class='alt3'><td style='border-right:solid 1px #000000;'><b>LO (GHz)</td>";
        echo "<td><b>IF0</td>";
        echo "<td><b>IF1</td>";
        if ($band < 9){
            echo "<td><b>IF2</td>";
            echo "<td><b>IF3</td></tr>";
        } else {
            echo "</tr>";
        }

        $qlo  = "SELECT DISTINCT(FreqLO) FROM IFSpectrum_SubHeader
                 WHERE ((fkHeader = " . $this->TDHkeys[0] . ") ";

        for ($i=1;$i<count($this->TDHkeys);$i++){
            $qlo .= " OR (fkHeader = " . $this->TDHkeys[$i] . ") ";
        }
        $qlo .= ") AND IsIncluded = 1 ORDER BY FreqLO ASC;";

    //    $this->logger->WriteLogFile('DisplayPowerVarFullBandTable: qlo=' . $qlo);

        $rlo = @mysql_query($qlo,$this->dbConnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);
        $rowcount = 0;
        while ($rowlo = @mysql_fetch_array($rlo)){
            $lo = $rowlo[0];
            if ($lo > 80){
                if ($rowcount % 2 == 0){
                    $trclass = "alt";
                } else {
                    $trclass = "";
                }
                echo "<tr class=$trclass><td style='border-right:solid 1px #000000;'><b>$lo</b></td>";
                $maxch = 3;
                if ($band >= 9){
                    $maxch = 1;
                }
                for ($ifchannel = 0; $ifchannel <= $maxch; $ifchannel++ ){

                    $q15 = "select TEST_IFSpectrum_PowerVarFullBand.Power_dBm
                            from IFSpectrum_SubHeader, TEST_IFSpectrum_PowerVarFullBand
                            where IFSpectrum_SubHeader.FreqLO =$lo
                            and IFSpectrum_SubHeader.IFGain = 15
                            and IFSpectrum_SubHeader.IFChannel = $ifchannel
                            and IFSpectrum_SubHeader.IsPAI = 1
                            and TEST_IFSpectrum_PowerVarFullBand.fkSubHeader = IFSpectrum_SubHeader.keyId
                            and
                            ((IFSpectrum_SubHeader.fkHeader =  " . $this->TDHkeys[0] . ") ";

                            //Display for all TDH keys
                            for ($iTDH=1;$iTDH<count($this->TDHkeys);$iTDH++){
                                //Iterate through each TDHkey value and generate power data for each one.
                                $q15 .= " OR (IFSpectrum_SubHeader.fkHeader =  " . $this->TDHkeys[$iTDH] . ") ";
                            }
                            $q15 .= ");";

                    $r15 = @mysql_query($q15,$this->dbConnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);

                    $pwr = round(@mysql_result($r15,0,0),1);
                    $pwr = number_format($pwr, 1, '.', '');

                    $bgcolor = "";
                    $fontcolor = "#000000";

                    if ($band == 6 || $band == 7 || $band == 9) {
                        if ($pwr >= 11) {
                            $bgcolor = "#ffddff";
                            $fontcolor = "#ff0000";
                        }
                    } else {
                        if ($pwr >= 10) {
                            $bgcolor = "#ffddff";
                            $fontcolor = "#ff0000";
                        }
                    }
                    echo "<td><font color = $fontcolor><b>$pwr</b></font></td>";

                }// end for ifchannel
                echo "</tr>";
                $rowcount += 1;

            }// end if lo>80
        }//end while

        //Meas date and FE Config info
        echo "<tr class = 'alt3'><th colspan = '5'>Front End Configuration: <a href='../ShowFEConfig.php?key="
            . $this->FrontEnd->feconfig_latest
            . "&fc=". $this->FacilityCode
            . "'><font color = '#0000ff'>"
            . $this->FrontEnd->feconfig_latest
            . "</a></font></th></tr>";

        echo "<tr class = 'alt3'><th colspan = '5'>class.ifspectrum version: " . $this->plotswversion ."</th></tr>";
        echo "<tr class = 'alt4'><th colspan = '5' >Meas Date: ". $this->TS. "</th></tr>";
        echo "</table></div>";
    }


    public function GeneratePlots(){
        WriteINI($this->progressfile,'progress',1);
        WriteINI($this->progressfile,'message','Creating temporary tables...');
        $this->CreateTemporaryTables();

        WriteINI($this->progressfile,'progress',20);
        WriteINI($this->progressfile,'message','Plotting IF Spectrum data...');
        $this->Plot_IFSpectrum_Data();

        WriteINI($this->progressfile,'progress',60);
        WriteINI($this->progressfile,'message','Plotting Power Variation data...');
        $this->Plot_PowerVariation_Data();

        WriteINI($this->progressfile,'progress',90);
        WriteINI($this->progressfile,'message','Removing temporary tables...');

        $this->DropTemporaryTables();
        WriteINI($this->progressfile,'progress',100);
        WriteINI($this->progressfile,'message','Finished plotting IF Spectrum.');
    }

    public function CreateTemporaryTables(){
    	//$this->logger->WriteLogFile('entering CreateTemporaryTables()');

    	$this->dbConnection2 = site_getDbConnection();
        //$this->logger->WriteLogFile("got site_getDbConnection()");

        //IF Spectrum
        $q = "DROP TABLE IF EXISTS TEMP_IFSpectrum ;";
        $r = @mysql_query($q,$this->dbConnection2) ; //or die("Query failed on class.testdata_header line " . __LINE__);
        $q = "DROP TABLE IF EXISTS TEMP_TEST_IFSpectrum_PowerVar;";
        $r = @mysql_query($q,$this->dbConnection2) ; //or die("Query failed on class.testdata_header line " . __LINE__);

        $q = "CREATE TEMPORARY TABLE IF NOT EXISTS TEMP_TEST_IFSpectrum_PowerVar (
              fkSubHeader INT,
              fkFacility INT,
              WindowSize_Hz DOUBLE,
              Freq_Hz DOUBLE,
              Power_dBm DOUBLE,
              TS TIMESTAMP,
              INDEX(fkSubHeader)
            );";
        $r = @mysql_query($q,$this->dbConnection2) ; //or die("Query failed on class.testdata_header line " . __LINE__);

        WriteINI($this->progressfile,'progress',6);

        $q = "CREATE TEMPORARY TABLE IF NOT EXISTS TEMP_IFSpectrum (
              fkSubHeader INT,
              fkFacility INT,
              Freq_Hz DOUBLE,
              Power_dBm DOUBLE,
              INDEX (fkSubHeader)
            );";
        $r = @mysql_query($q,$this->dbConnection2) ; //or die("Query failed on class.testdata_header line " . __LINE__);

        WriteINI($this->progressfile,'progress',12);


        //Get keyId for each IFSpectrum_SubHeader record
        $qtemp = "SELECT IFSpectrum_SubHeader.keyId
            FROM
            IFSpectrum_SubHeader, TestData_header, FE_Config WHERE
            IFSpectrum_SubHeader.fkHeader =  TestData_header.keyId
            AND IFSpectrum_SubHeader.IsIncluded =  1
            AND TestData_header.DataSetGroup = $this->DataSetGroup
            AND TestData_header.Band = ".$this->DataSetBand."
            AND TestData_header.fkTestData_Type = 7
            AND TestData_header.fkFE_Config = FE_Config.keyFEConfig
            AND FE_Config.fkFront_Ends = " . $this->FrontEnd->keyId;

        $ifsubkeys = ''; //array of IFSpectrum SubHEader keyId values

        $rtemp = @mysql_query($qtemp,$this->dbConnection2);
        $tempcount = 0;
        while ($rowtemp = @mysql_fetch_array($rtemp)){
            $ifsubkeys[$tempcount] = $rowtemp['keyId'];
            $tempcount += 1;
        }

        $q = "INSERT INTO TEMP_IFSpectrum
            SELECT IFSpectrum.fkSubHeader,IFSpectrum.fkFacility,IFSpectrum.Freq_Hz,
            IFSpectrum.Power_dBm
            FROM
            IFSpectrum WHERE
            (IFSpectrum.fkSubHeader = $ifsubkeys[0]) ";

        for ($i=1;$i<count($ifsubkeys);$i++){
            $q .= " OR (IFSpectrum.fkSubHeader = $ifsubkeys[$i]) ";
        }
        $q .= ";";

        $r = @mysql_query($q,$this->dbConnection2); //or die("Query failed on class.testdata_header line " . __LINE__);

        WriteINI($this->progressfile,'progress',18);
    }

    public function DropTemporaryTables(){
        $q = "DROP TABLE TEMP_TEST_IFSpectrum_PowerVar;";
        $r = @mysql_query($q,$this->dbConnection2);
        $q = "DROP TABLE TEMP_IFSpectrum;";
        $r = @mysql_query($q,$this->dbConnection2);
    }

    public function Plot_IFSpectrum_Data(){
        $this->CheckForAbort();
        if ($this->aborted == 0){
            WriteINI($this->progressfile,'progress',25);
            WriteINI($this->progressfile,'message','Plotting Spurious IF0...');
            $this->Plot_IFSpectrum_Spurious2D(0,'spurious_url2d',10,650,-1);
        }
        $this->CheckForAbort();
        if ($this->aborted == 0){
            WriteINI($this->progressfile,'progress',30);
            WriteINI($this->progressfile,'message','Plotting Spurious Expanded IF0...');
            $this->Plot_IFSpectrum_Spurious2D(0,'spurious_url2d2',42,'',1);
        }
        $this->CheckForAbort();
        if ($this->aborted == 0){
            WriteINI($this->progressfile,'progress',35);
            WriteINI($this->progressfile,'message','Plotting Spurious IF1...');
            $this->Plot_IFSpectrum_Spurious2D(1,'spurious_url2d',10,650,-1);
        }
        $this->CheckForAbort();
        if ($this->aborted == 0){
            WriteINI($this->progressfile,'progress',40);
            WriteINI($this->progressfile,'message','Plotting Spurious Expanded IF1...');
            $this->Plot_IFSpectrum_Spurious2D(1,'spurious_url2d2',42,'',1);
        }

        if ($this->DataSetBand < 9){
            $this->CheckForAbort();
            if ($this->aborted == 0){
                WriteINI($this->progressfile,'progress',45);
                WriteINI($this->progressfile,'message','Plotting Spurious IF2...');
                $this->Plot_IFSpectrum_Spurious2D(2,'spurious_url2d',10,650,-1);
            }

            $this->CheckForAbort();
            if ($this->aborted == 0){
                WriteINI($this->progressfile,'progress',50);
                WriteINI($this->progressfile,'message','Plotting Spurious Expanded IF2...');
                $this->Plot_IFSpectrum_Spurious2D(2,'spurious_url2d2',42,'',1);
            }

            $this->CheckForAbort();
            if ($this->aborted == 0){
                WriteINI($this->progressfile,'progress',55);
                WriteINI($this->progressfile,'message','Plotting Spurious IF3...');
                $this->Plot_IFSpectrum_Spurious2D(3,'spurious_url2d',10,650,-1);
            }

            $this->CheckForAbort();
            if ($this->aborted == 0){
                WriteINI($this->progressfile,'progress',60);
                WriteINI($this->progressfile,'message','Plotting Spurious Expanded IF3...');
                $this->Plot_IFSpectrum_Spurious2D(3,'spurious_url2d2',42,'',1);
            }
        }
    }

    public function Plot_IFSpectrum_Spurious2D($IFChannel, $in_url = "spurious_url2d", $offsetamount = 10, $plotheight = 0, $show_ytics = -1){
        $IFGain = 15;
        $imagedirectory = $this->writedirectory . 'tdh/';
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagedirectory .= $this->TDHkeys[0] . "/";
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagedirectory .= 'IFSpectrum/';
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }

    //     $this->logger->WriteLogFile('Plot_IFSpectrum_Spurious2D: imagedirectory=$imagedirectory');

        $plot_title = "Spurious Noise, FE-".$this->FrontEnd->GetValue('SN').", Band $this->DataSetBand SN ";
        $plot_title .= $this->CCASN . " IF$IFChannel";

        $ifspec_low = 4;
        $ifspec_high = 8;

        switch($this->DataSetBand){
            case 6:
                $ifspec_low = 5;
                $ifspec_high = 10;
                break;
            case 9:
            case 10:
                $ifspec_low = 4;
                $ifspec_high = 12;
                break;
        }

        //Get Max and Min LO Values
        $qlo = "SELECT IFSpectrum_SubHeader.FreqLO
                FROM IFSpectrum_SubHeader, TestData_header, FE_Config
                WHERE IFSpectrum_SubHeader.fkHeader = TestData_header.keyId
                AND IFSpectrum_SubHeader.Band = $this->DataSetBand
                AND IFSpectrum_SubHeader.IFChannel = $IFChannel
                AND IFSpectrum_SubHeader.IFGain = 15
                AND IFSpectrum_SubHeader.IsIncluded = 1
                AND TestData_header.fkFE_Config = FE_Config.keyFEConfig
                AND FE_Config.fkFront_Ends = $this->FEid
                AND TestData_header.DataSetGroup = $this->DataSetGroup
                GROUP BY IFSpectrum_SubHeader.FreqLO ASC";

    //    $this->logger->WriteLogFile('qlo=' . $qlo);

        $rlo = @mysql_query($qlo,$this->dbConnection); // or die ('Query failed on class.dataplotter '. __LINE__ .'.');
        $ilo=0;
        while ($rowlo = @mysql_fetch_array($rlo)){
            $loarray[$ilo] = $rowlo[0];
            $ilo += 1;
        }

        $lomin = $loarray[0];
        $lomax = $loarray[sizeof($loarray)-1];

        //Get number of IF subheaders
        $qifsub = "SELECT IFSpectrum_SubHeader.keyId, IFSpectrum_SubHeader.FreqLO, TestData_header.keyId
                   FROM IFSpectrum_SubHeader, TestData_header,FE_Config
                   WHERE IFSpectrum_SubHeader.fkHeader = TestData_header.keyId
                   AND IFSpectrum_SubHeader.Band = $this->DataSetBand
                   AND IFSpectrum_SubHeader.IFChannel = $IFChannel
                   AND IFSpectrum_SubHeader.IFGain = 15
                   AND IFSpectrum_SubHeader.IsIncluded = 1
                   AND TestData_header.fkFE_Config = FE_Config.keyFEConfig
                   AND FE_Config.fkFront_Ends = $this->FEid
                   AND TestData_header.DataSetGroup = $this->DataSetGroup
                   ORDER BY IFSpectrum_SubHeader.FreqLO ASC;";

    //    $this->logger->WriteLogFile('qifsub=' . $qifsub);


        $rifsub = @mysql_query($qifsub,$this->dbConnection) ; // or die ('Query failed on class.dataplotter '. __LINE__ .'.');

        // to accumulate the min and max power seen in any trace:
        $minpower = 999;
        $maxpower = -999;

        // loop vars:
        $datafile_count = 0;
    //    $speccount = 0;
        $offset = 0;

        while ($rowifsub = @mysql_fetch_array($rifsub)){

            $FreqLO = $rowifsub[1];
            $TDHkey = $rowifsub[2];

            $data_file[$datafile_count] = $imagedirectory . "if_spurious_data2d_$FreqLO.txt";

            $fh = fopen($data_file[$datafile_count], 'w');

            $ifsub = new GenericTable();
            $ifsub->Initialize('IFSpectrum_SubHeader',$rowifsub[0],'keyId', $this->FacilityCode,'fkFacility');

            $qdata = "SELECT Freq_Hz/1000000000,(Power_dBm + $offset) FROM TEMP_IFSpectrum
                      WHERE fkSubHeader = $rowifsub[0]
                      AND Freq_Hz > 12000000
                      ORDER BY Freq_Hz ASC;";
            $rdata = @mysql_query($qdata,$this->dbConnection2) ; // or die ('Query failed on class.dataplotter '. __LINE__ .'.');

            // to accumulate max and min power in this trace:
            $mintrace = 999999;
            $maxtrace = -999999;

            //Write data to file:
            while ($rowdata = @mysql_fetch_array($rdata)){
                $stringData = "$rowdata[0]\t$FreqLO\t$rowdata[1]\r\n";
                fwrite($fh, $stringData);

                // check for min and max power overall:
                if ($rowdata[1] < $minpower){
                    $minpower = $rowdata[1];
                }
                if ($rowdata[1] > $maxpower){
                    $maxpower = $rowdata[1];
                }

    //            if (round($rowdata[0],0) == 4){
    //                $lowspecs[$speccount] = $rowdata[1];
    //                $speccount += 1;
    //            }

                $finalvals[$datafile_count] = $rowdata[1];

                //check for min and max power of this trace
                if ($rowdata[1] < $mintrace){
                    $mintrace = $rowdata[1];
                }
                if ($rowdata[1] > $maxtrace){
                    $maxtrace = $rowdata[1];
                }
            }
            // save the min/max powers per trace seen:
            $maxpowers[$datafile_count] = round($maxtrace,2);
            $minpowers[$datafile_count] = round($mintrace,2);
            $maxpowersshowval[$datafile_count] = round($maxtrace - $mintrace,2);

            // move the Y-axis offset for the next trace:
            $offset += $offsetamount;

            //Put empty line after each series for gnuplot
            fwrite($fh, "\r\n");
            unset($ifsub);
            fclose($fh);

            $datafile_count += 1;
            //Final value in data array, used for the set ytics command
            //in gnuplot below
        }

        $imagename = $in_url . "_Band$this->DataSetBand" . date('Y_m_d_H_i_s') . "dsg" . $this->DataSetGroup . ".png";
        $imagepath = $imagedirectory . $imagename;


        //Write command file
        $commandfile = $imagedirectory . "if_spurious2d$IFChannel" . "_commands_tdh" . $this->DataSetGroup . ".txt";
        $fh = fopen($commandfile, 'w');

        if ($plotheight == 0){
            $plotheight = count($loarray) * 300;
        }
        fwrite($fh, "set terminal png size 900,$plotheight\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set xlabel 'IF (GHz)'\r\n");
        fwrite($fh, "set ylabel 'Power (dB)'\r\n");
        fwrite($fh, "unset ytics\r\n");

        //Write "XX GHz" labels on righthand column

        if ($show_ytics == 1){
            fwrite($fh, "set y2tics ('$loarray[0] GHz' $minpowers[0] ");
            for ($jlo = 0; $jlo < (sizeof($loarray)); $jlo++){

                $lcnt = $jlo + 10;

                if ($jlo > 0){
                    //fwrite($fh, ",'$loarray[$jlo] GHz (p-p: $maxpowers[$jlo] dB)' $finalvals[$jlo] ");
                    fwrite($fh, ",'$loarray[$jlo] GHz' $minpowers[$jlo] ");
                }
            }
            fwrite($fh, ")\r\n");
        }

        if ($show_ytics != 1){
            fwrite($fh, "set y2tics ('$loarray[0] GHz' $finalvals[0] ");
            for ($jlo = 0; $jlo < (sizeof($loarray)); $jlo++){

                $lcnt = $jlo + 10;

                if ($jlo > 0){
                    //Label on right side for each LO trace
                    fwrite($fh, ",'$loarray[$jlo] GHz' $finalvals[$jlo] ");
                }
            }
            fwrite($fh, ")\r\n");
        }

        if ($show_ytics == 1){
            //Write '0 dB' labels in lefthand column
            fwrite($fh, "set ytics ('0 dB' $minpowers[0]");
            for ($jlo = 0; $jlo < (sizeof($loarray)); $jlo++){

                $lcnt = $jlo + 10;

                if ($jlo > 0){
                    fwrite($fh, ",'0 dB' $minpowers[$jlo] ");
                }
            }

            //Write max power labels in lefthand column
            fwrite($fh, ", '$maxpowersshowval[0] dB' $maxpowers[0]");
            for ($jlo = 0; $jlo < (sizeof($loarray)); $jlo++){

                $lcnt = $jlo + 10;

                if ($jlo > 0){
                    fwrite($fh, ",'$maxpowersshowval[$jlo] dB' $maxpowers[$jlo] ");
                }
            }
            fwrite($fh, ")\r\n");
        }

        fwrite($fh, "set grid\r\n");

        $labelcount = 17;
        for ($ilo=0; $ilo < (sizeof($loarray)); $ilo++){
            $labelcount += 1;
        }

        fwrite($fh, "set arrow 1 from $ifspec_low,$minpower to $ifspec_low,$maxpower nohead lt -1 lw 2 \r\n");
        fwrite($fh, "set arrow 2 from $ifspec_high,$minpower to $ifspec_high,$maxpower nohead lt -1 lw 2 \r\n");
        fwrite($fh, "set bmargin 7 \r\n");

        $label1_ht = 0.07;
        if ($plotheight > 0){
            $label1_ht = 30 / $plotheight;
        }
        $label2_ht = $label1_ht / 2;

        $setstring = "set label 'TestData_header.keyId: $this->TDHkeyString' at screen 0.01, $label1_ht\r\n";
        fwrite($fh, $setstring);
        fwrite($fh, "set label '".$this->TS .", FE Configuration ".$this->FrontEnd->feconfig_latest."; TestData_header.DataSetGroup: $this->DataSetGroup; IFSpectrum Ver. $this->plotswversion' at screen 0.01, $label2_ht\r\n");

        fwrite($fh, "set key reverse outside\r\n");
        fwrite($fh, "set nokey\r\n");

        for ($i = count($data_file)-1; $i >= 0; $i--){
            $lt = $i + 1;
            if ($i == count($data_file)-1){
                fwrite($fh, "plot '$data_file[$i]' using 1:3 with lines lt $lt title '$loarray[$i] GHz'");
            }
            if ($i != count($data_file)-1){
                fwrite($fh, ", '$data_file[$i]' using 1:3 with lines lt $lt title '$loarray[$i] GHz'");
            }
        }

        fwrite($fh, "\r\n\r\n");
        fclose($fh);

        $GNUPLOT = $this->GNUPLOT_path;
        $CommandString = "$GNUPLOT $commandfile";
        system($CommandString);

        $image_url = $this->url_directory . "tdh/" . $this->TDHkeys[0] . "/IFSpectrum/$imagename";

        $qurl = "SELECT keyId FROM TEST_IFSpectrum_urls
                WHERE fkHeader = " . $this->TDHkeys[0] . "
                AND Band = $this->DataSetBand
                AND IFChannel = $IFChannel
                AND IFGain = $IFGain;";

        $rurl = @mysql_query($qurl,$this->dbConnection) ; // or die ('Query failed on class.dataplotter '. __LINE__ .'.');
        $ifs_id = @mysql_result($rurl,0);
        //$this->Initialize_IFSpectrum($this->FEid, $this->DataSetGroup, $this->FacilityCode, $this->DataSetBand);

        if ($this->urls[$IFChannel]->keyId == ''){
            $this->urls[$IFChannel] = new GenericTable();
            $this->urls[$IFChannel]->NewRecord('TEST_IFSpectrum_urls','keyId',40,'fkFacility');
            $this->urls[$IFChannel]->SetValue('fkHeader',$this->TDHkeys[0]);
            $this->urls[$IFChannel]->SetValue('Band',$this->DataSetBand);
            $this->urls[$IFChannel]->SetValue('IFChannel',$IFChannel);
            $this->urls[$IFChannel]->SetValue('IFGain',$IFGain);

        }
        $this->urls[$IFChannel]->SetValue($in_url,$image_url);
        $this->urls[$IFChannel]->Update();

        WriteINI($this->progressfile,'image',$image_url);
    }

    public function Plot_PowerVariation_Data(){
        $this->CheckForAbort();
        if ($this->aborted != 1){
            WriteINI($this->progressfile,'progress',65);
            WriteINI($this->progressfile,'message','Generating Power Table data...');
            $this->Generate_Power_Data();
        }

        $windowsizes[0] = 31 * pow(10,6);
        $windowsizes[1] = 2 * pow(10,9);

        $this->CheckForAbort();

        $TDHkey = $this->TDHkeys[0];

        if ($this->aborted != 1){
            WriteINI($this->progressfile,'progress',70);
            WriteINI($this->progressfile,'message','Plotting Power Var IF0 31MHz...');
            $this->Plot_IFSpectrum_PowerVar($this->DataSetBand, 0, $TDHkey, $windowsizes[0]);

            WriteINI($this->progressfile,'progress',73);
            WriteINI($this->progressfile,'message','Plotting Power Var IF1 31 MHz...');
            $this->Plot_IFSpectrum_PowerVar($this->DataSetBand, 1, $TDHkey, $windowsizes[0]);

            WriteINI($this->progressfile,'progress',76);
            WriteINI($this->progressfile,'message','Plotting Power Var IF0 2GHz...');
            $this->Plot_IFSpectrum_PowerVar($this->DataSetBand, 0, $TDHkey, $windowsizes[1]);

            WriteINI($this->progressfile,'progress',79);
            WriteINI($this->progressfile,'message','Plotting Power Var IF1 2GHz...');
            $this->Plot_IFSpectrum_PowerVar($this->DataSetBand, 1, $TDHkey, $windowsizes[1]);

            if ($this->DataSetBand < 9){
                WriteINI($this->progressfile,'progress',82);
                WriteINI($this->progressfile,'message','Plotting Power Var IF2 31MHz...');
                $this->Plot_IFSpectrum_PowerVar($this->DataSetBand, 2, $TDHkey, $windowsizes[0]);
                WriteINI($this->progressfile,'progress',85);
                WriteINI($this->progressfile,'message','Plotting Power Var IF3 31MHz...');
                $this->Plot_IFSpectrum_PowerVar($this->DataSetBand, 3, $TDHkey, $windowsizes[0]);

                WriteINI($this->progressfile,'progress',88);
                WriteINI($this->progressfile,'message','Plotting Power Var IF2 2GHz...');
                $this->Plot_IFSpectrum_PowerVar($this->DataSetBand, 2, $TDHkey, $windowsizes[1]);

                WriteINI($this->progressfile,'progress',91);
                WriteINI($this->progressfile,'message','Plotting Power Var IF3 2GHz...');
                $this->Plot_IFSpectrum_PowerVar($this->DataSetBand, 3, $TDHkey, $windowsizes[1]);
            }
        }
    }

    public function Generate_Power_Data(){
        $windowsizes[0] = 31 * pow(10,6);
        $windowsizes[1] = 2 * pow(10,9);

        $ps = new PwrSpecTool();
        $ps->dbconnection = $this->dbConnection2;
        $ps->fc = 40;

        //Get all TestData_header keys for this dataset
        for ($iTDH=0;$iTDH<count($this->TDHkeys);$iTDH++){
            //Iterate through each TDHkey value and generate power data for each one.
            $keyTDH = $this->TDHkeys[$iTDH];

            $qifsub = "SELECT keyId FROM IFSpectrum_SubHeader
                       WHERE fkHeader = $keyTDH
                       AND IsIncluded = 1
                       ORDER BY IFChannel ASC, FreqLO ASC;";

            $rifsub = @mysql_query($qifsub,$this->dbConnection2); // or die ('Query failed on class.testdata_header line 1278.');
            while ($rowifsub = @mysql_fetch_array($rifsub)){
                $this->CheckForAbort();
                if ($this->aborted != 1){
                    $ifsub = new GenericTable();

                    $ifsub->Initialize('IFSpectrum_SubHeader',$rowifsub[0],'keyId',40,'keyFacility');
                    $LO = $ifsub->GetValue('FreqLO') . " GHz";
                    WriteINI($this->progressfile,'message','Plot Power Var IF' . $ifsub->GetValue('IFChannel') . "...");

                    if ($ifsub->GetValue('IFGain') == '15'){

                        //Generate 31MHz window data
                        //For band 6, use 5-10 GHz for the 31 MHz plot
                        WriteINI($this->progressfile,'message','Power Var Window IF' . $ifsub->GetValue('IFChannel') . " $LO...");
                        if ($this->DataSetBand == 6){
                            $ps->powerVarWindow2($ifsub->keyId, 5  * pow(10,9), 10  * pow(10,9),31 * pow(10,6));
                        }
                        if ($this->DataSetBand != 6){
                            $ps->powerVarWindow2($ifsub->keyId, $this->fWindow_Low, $this->fWindow_High,31 * pow(10,6));
                        }
                        //Generate 2GHz window data.
                        $ps->powerVarWindow2($ifsub->keyId, $this->fWindow_Low, $this->fWindow_High,2 * pow(10,9));

                        //Special case for band 6
                        if ($this->DataSetBand == 6){
                            $winlo = 4 * pow(10,9);
                            $winhi = 7 * pow(10,9);
                            $ps->powerVarWindow2($ifsub->keyId, $winlo, $winhi,2 * pow(10,9), -1);
                        }
                    }

                    WriteINI($this->progressfile,'message','Total and In-band power IF' . $ifsub->GetValue('IFChannel') . " $LO...");
                    $ps->powerTotalAndInBandPower($ifsub->keyId, $this->fWindow_Low, $this->fWindow_High);

                    if ($ifsub->Band != 6){
                        $ps->PowerVarFullBand($ifsub->keyId,$this->fWindow_Low,$this->fWindow_High);
                    }
                    if ($ifsub->Band == 6){

                        $ps->PowerVarFullBand($ifsub->keyId, 5  * pow(10,9), 10  * pow(10,9));
                    }
                    unset($ifsub);
                }//end if abort == 0
            }// end while row
        }//end for iTDH loop

        unset($ps);
    }

    public function Plot_IFSpectrum_PowerVar($Band, $IFChannel, $td_header, $windowSize){
        $b6case = 0;
        if (($this->DataSetBand == 6) && ($windowSize == 2 * pow(10,9))){
            $b6case = 1;
        }

        $windowSize_string = "2 GHz";
        $spec_value = 6;

        switch($this->DataSetBand){
            case "6" :
                $spec_value = 7;
            case "4" :
                $spec_value = 6;
            case "7" :
                $spec_value = 7;
            case "8" :
                $spec_value = 7;
            case "9" :
                $spec_value = 7;
            case "10" :
                $spec_value = 7;
        }

        if ($windowSize == 31 * pow(10,6)){
            $windowSize_string = "31 MHz";
            $spec_value = 1.35;
        }

        $ccaSN = $this->CCASN;
        $plot_title = "Power Variation $windowSize_string Window: FE-".$this->FrontEnd->GetValue('SN').", Band $this->DataSetBand SN $ccaSN, IF$IFChannel";


        //Write the data file

        //Get records from IFSpectrum_SubHeader
        $qifsub = "SELECT IFSpectrum_SubHeader.keyId, IFSpectrum_SubHeader.FreqLO, TestData_header.keyId
                    FROM
                    IFSpectrum_SubHeader, TestData_header,FE_Config
                    WHERE IFSpectrum_SubHeader.fkHeader = TestData_header.keyId
                    AND IFSpectrum_SubHeader.Band = $this->DataSetBand
                    AND IFSpectrum_SubHeader.IFChannel = $IFChannel
                    AND IFSpectrum_SubHeader.IFGain = 15
                    AND IFSpectrum_SubHeader.IsIncluded = 1
                    AND TestData_header.fkFE_Config = FE_Config.keyFEConfig
                    AND FE_Config.fkFront_Ends = $this->FEid
                    AND TestData_header.DataSetGroup = $this->DataSetGroup
                    ORDER BY IFSpectrum_SubHeader.FreqLO ASC;";


        //Get max power var
        $rifsub = @mysql_query($qifsub,$this->dbConnection) ; // or die ('Query failed on class.dataplotter '. __LINE__ .'.');
        $maxpowervar = -999;
        while ($rowifsub = @mysql_fetch_array($rifsub)){
            $qmax = "SELECT MAX(Power_dBm) FROM TEMP_TEST_IFSpectrum_PowerVar
                      WHERE fkSubHeader = $rowifsub[0]
                      AND WindowSize_Hz = $windowSize;";
            $rmax = @mysql_query($qmax, $this->dbConnection2)  or die('Failed on query in dataplotter.php line ' . __LINE__);
            $tempmaxpowervar = round(@mysql_result($rmax,0,0),2);

            if ($tempmaxpowervar > $maxpowervar){
                $maxpowervar = $tempmaxpowervar;
            }
        }

        $rifsub = @mysql_query($qifsub,$this->dbConnection2) ; // or die ('Query failed on class.dataplotter '. __LINE__ .'.');
        $filecount = 0;
        $b6count = 0;
        $b6points = Array();
        $maxpowervar6 = -999;
        $data_file = Array();
        $FreqLO = Array();
        while ($rowifsub = @mysql_fetch_array($rifsub)){
            $FreqLO[$filecount] = $rowifsub[1];

            $data_file[$filecount] = $this->writedirectory . "if_spurious_data_pwrvar31_$FreqLO[$filecount]_$windowSize.txt";
            $fh = fopen($data_file[$filecount], 'w');

            $ifsub = new GenericTable();
            $ifsub->Initialize('IFSpectrum_SubHeader',$rowifsub[0],'keyId',40,'keyFacility');

            //Special case for band 6
            if ($b6case == 1){
                $q6 = "SELECT MAX(Power_dBm), MIN(Power_dBm) FROM TEMP_IFSpectrum
                          WHERE fkSubHeader = $rowifsub[0]
                          AND Freq_Hz < 6000000000
                          AND Freq_Hz > 5000000000;";
                $r6 = @mysql_query($q6, $this->dbConnection2) ; // or die ('Query failed on class.dataplotter '. __LINE__ .'.');
                $b6val = @mysql_result($r6,0,0) - @mysql_result($r6,0,1);
                if ($b6val != 0){
                $b6points[$b6count] = $b6val;
                $b6count += 1;
                }
            }

            for ($im = 0; $im < count ($b6points); $im++){
                if ($b6points[$im] > $maxpowervar6){
                    $maxpowervar6 = $b6points[$im];
                }
            }

            $qdata = "SELECT Freq_Hz,Power_dBm FROM TEMP_TEST_IFSpectrum_PowerVar
                      WHERE fkSubHeader = $rowifsub[0]
                      AND WindowSize_Hz = $windowSize
                      ORDER BY Freq_Hz ASC;";
            $rdata = @mysql_query($qdata,$this->dbConnection2) ; // or die ('Query failed on class.dataplotter '. __LINE__ .'.');
            while ($rowdata = @mysql_fetch_array($rdata)){
                $pval = $rowdata[1];
                $fval = $rowdata[0] * pow (10,-9);
                if ($b6case == 1){
                    if ($fval < 6){
                        $pval = "-1";
                    }
                    if (($fval >= 7) && ($fval <= 9) ){
                        if ($pval > $maxpowervar6){
                            $maxpowervar6 = $pval;
                        }
                    }
                }
                if ($pval != -1){
                $stringData = "$fval\t$pval\r\n";
                fwrite($fh, $stringData);
                }
            }
            //Put empty line after each series for gnuplot
            fwrite($fh, "\r\n");

            unset($ifsub);
            fclose($fh);
            $filecount += 1;
        }//end while ($rowifsub = @mysql_fetch_array($rifsub))

        if ($b6case == 1){
            $maxpowervar = $maxpowervar6;
        }

        $maxpowervar = round($maxpowervar,1);

        //Create directories if necesary
        $imagedirectory = $this->writedirectory . 'tdh/';
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagedirectory .= "$td_header/";
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagedirectory .= 'IFSpectrum/';
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagename = "IFPowerVar$windowSize" . "_Band$Band" . "_IF$IFChannel" . date("Ymd_G_i_s") . ".png";
        $imagepath = $imagedirectory . $imagename;

        //Write command file
        $commandfile = $this->writedirectory . "if_powervar_commands_tdh$this->keyId.txt";


        $fh = fopen($commandfile, 'w');
        fwrite($fh, "set terminal png crop\r\n");

        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set xlabel 'Center of Window (GHz)'\r\n");
        fwrite($fh, "set ylabel 'Power Variation in Window (dB)'\r\n");
        fwrite($fh, "set bmargin 7\r\n");
        fwrite($fh, "set label 'Max Power Variation: $maxpowervar dB' at screen 0.01, 0.07\r\n");
        $setstring = "set label 'TestData_header.keyId: $this->TDHkeyString' at screen 0.01, 0.04\r\n";
        fwrite($fh, $setstring);
        fwrite($fh, "set label '".$this->TS .", FE Config ".$this->FrontEnd->feconfig_latest."; DataSetGroup: $this->DataSetGroup; IFSpectrum Ver. $this->plotswversion' at screen 0.01, 0.01\r\n");



        fwrite($fh, "set key right outside\r\n");
        if ($b6case == 1){
            fwrite($fh, "set xrange[5:9.2]\r\n");
            fwrite($fh, "set yrange[0:9]\r\n");
        }
        if ($b6case != 1){
            fwrite($fh, "set yrange[0:" . ($spec_value + 1) . "]\r\n");
        }
        fwrite($fh, "set grid xtics ytics\r\n");
            $fspec1 = "f1(x)=((x>5.2) && (x<5.8)) ? 8 : 1/0\r\n";
            $fspec2 = "f2(x)=((x>7) &&    (x<9)) ? 7 : 1/0\r\n";

            fwrite($fh, $fspec1);
            fwrite($fh, $fspec2);

            if ($b6case == 1){
                for ($b6count=0; $b6count < sizeof($b6points); $b6count++){
                    $bmax = 5.52;
                    $bmin = 5.45;
                    $pointval = "fb6_$b6count(x)=((x>$bmin) && (x<$bmax)) ? $b6points[$b6count] : 1/0\r\n";
                    //echo $pointval . "<br>";
                    fwrite($fh, $pointval);
                }
            }

        if ($b6case == 0){
            fwrite($fh, "plot '$data_file[0]' using 1:2 title '$FreqLO[0] GHz' with lines lt 2 ");
            for ($i = 1; $i < $filecount; $i++){
                $lt = $i + 3;
                fwrite($fh, ", '$data_file[$i]' using 1:2 title '$FreqLO[$i] GHz' with lines lt $lt ");
            }
            $lt += 1;
                fwrite($fh, ", $spec_value title 'Spec' with lines lt -1 lw 5\r\n");
                fwrite($fh, "\r\n");
        }

        if ($b6case == 1){
            fwrite($fh, "plot fb6_0(x) with linespoints notitle pt 5 lt 2, '$data_file[0]' using 1:2 title '$FreqLO[0] GHz' with lines lt 2 ");
            $b6count=0;
            for ($i = 1; $i < sizeof($b6points); $i++){
                $lt = $i + 3;
                fwrite($fh, ", fb6_$i(x) with linespoints  notitle pt 5 lt $lt, '$data_file[$i]' using 1:2 title '$FreqLO[$i] GHz' with lines lt $lt ");
            }
            $lt += 1;
            fwrite($fh, ", f1(x) title 'Spec' with lines lt -1 lw 5");
            fwrite($fh, ", f2(x) notitle with lines lt -1 lw 5");
            fwrite($fh, "\r\n");
        }

        fclose($fh);

        $GNUPLOT = $this->GNUPLOT_path;
        $CommandString = "$GNUPLOT $commandfile";
        system($CommandString);


        $image_url = $this->url_directory . "tdh/$td_header/IFSpectrum/$imagename";
        $urlname = "powervar_2GHz_url";
        if ($windowSize == 31 * pow(10,6)){
            $urlname = "powervar_31MHz_url";
        }

        if ($this->urls[$IFChannel]->keyId == ''){
            $this->urls[$IFChannel] = new GenericTable();
            $this->urls[$IFChannel]->NewRecord('TEST_IFSpectrum_urls','keyId',40,'fkFacility');
            $this->urls[$IFChannel]->SetValue('fkHeader',$td_header);
            $this->urls[$IFChannel]->SetValue('Band',$Band);
            $this->urls[$IFChannel]->SetValue('IFChannel',$IFChannel);
            $this->urls[$IFChannel]->SetValue('IFGain',$IFGain);

        }
        $this->urls[$IFChannel]->SetValue($urlname,$image_url);
        $this->urls[$IFChannel]->Update();
        WriteINI($this->progressfile,'image',$image_url);

    }

    public function CheckForAbort(){
        $ini_array = parse_ini_file($this->progressfile_fullpath);
        $this->aborted = $ini_array['abort'];
        if ($this->aborted == 1){
            WriteINI($this->progressfile,'message',"Aborted.");
        }
    }
} // end class
?>
