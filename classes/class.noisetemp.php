<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.logger.php');
require_once($site_FEConfig. '/testdata/spec_functions.php');
require_once($site_FEConfig. '/testdata/pas_tables.php');

class NoiseTemperature extends TestData_header{
    var $NT_SubHeader; // array for subheader objects from Noise_Temp_SubHeader (class.generictable.php)

    public function Initialize_NoiseTemperature($in_keyId, $in_fc){
        parent::Initialize_TestData_header($in_keyId, $in_fc);

        $q = "SELECT keyId, keyFacility FROM Noise_Temp_SubHeader
              WHERE fkHeader = $in_keyId AND keyFacility = $in_fc
              order by keyId ASC;" ;
        $r = @mysql_query($q, $this->dbconnection);
            $keyID = @mysql_result($r,0,0);
            $facility = @mysql_result($r,0,1);
            $this->NT_SubHeader = new GenericTable();
            $this->NT_SubHeader->Initialize('Noise_Temp_SubHeader',$keyID,'keyId',$facility,'keyFacility');
    }

    public function DrawPlot(){

        // define function to save average NT results
        function save_avg_NT( $last_freq, $Pol0_Sb1_avg,$Pol0_Sb2_avg,$Pol1_Sb1_avg,$Pol1_Sb2_avg,$TSSB_80_spec,$TSSB_spec,$TSSB_80_spec_RF_limit,$favg){
            $avg01 = array_sum ( $Pol0_Sb1_avg ) / count ( $Pol0_Sb1_avg );
            $avg02 = array_sum ( $Pol0_Sb2_avg ) / count ( $Pol0_Sb2_avg );
            $avg11 = array_sum ( $Pol1_Sb1_avg ) / count ( $Pol1_Sb1_avg );
            $avg12 = array_sum ( $Pol1_Sb2_avg ) / count ( $Pol1_Sb2_avg );

            // this was added to accomidate a band 10 specification changes.
            if ($last_freq >= $TSSB_80_spec_RF_limit){
                $spec_line1 = NaN;
                $spec_line2 = $TSSB_spec;
            } else {
                $spec_line1 = $TSSB_80_spec;
                $spec_line2 = $TSSB_spec;
            }

            $writestring = "$last_freq\t$avg01\t$avg02\t$avg11\t$avg12\t$spec_line1\t$spec_line2\r\n";
            fwrite($favg,$writestring);
        }

        // set Plot Software Version
        $Plot_SWVer = "1.0.17";
        /*
         * 1.0.16  MTM: fix plot axis labels for Tssb and "corrected"
         * 1.0.17  MTM: fix "set label...screen" commands to gnuplot
         */


        $this->SetValue('Plot_SWVer',$Plot_SWVer);
        $this->Update();

        // start a logger file for debugging
        $l = new Logger("NT_Log.txt");
        //Get CCA Serial Number
        $q ="SELECT FE_Components.SN FROM FE_Components, FE_ConfigLink, FE_Config
             WHERE FE_ConfigLink.fkFE_Config = " .$this->GetValue('fkFE_Config'). "
             AND FE_Components.fkFE_ComponentType = 20
             AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
             AND FE_Components.Band = " . $this->GetValue('Band') . "
             AND FE_Components.keyFacility =" . $this->GetValue('keyFacility') ."
             AND FE_ConfigLink.fkFE_ConfigFacility = FE_Config.keyFacility
             ORDER BY Band ASC;";
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

        //Get CCA FE_Component keyid
        $q ="SELECT keyId FROM FE_Components
                WHERE SN = $CCA_SN AND fkFE_ComponentType = 20
                AND band = " . $this->GetValue('Band') . "
                AND keyFacility =" . $this->GetValue('keyFacility') ."
                GROUP BY keyId DESC";
            $r = @mysql_query($q,$this->dbconnection);
        $l->WriteLogFile("CCA FE_Component key query: $q");
        while ($row = @mysql_fetch_array($r)){
            $CCA_key[]=$row[0];
        }
        $l->WriteLogFile("CCA FE_Component key: $CCA_key[0]");

        //main_write_driectory is defined in config_main.php
        require(site_get_config_main());

        $plotdir = $main_write_directory . "noisetemp/";
        //Create plot directory if it doesn't exist.
        if (!file_exists($plotdir)){
            mkdir($plotdir);
        }

        //***************************************************
        //Create initialize data files
        //***************************************************
        $rf_datafile_name =  "NoiseTemp_RF.txt";
        $rf_datafile = $plotdir . $rf_datafile_name;
        $l->WriteLogFile("rf datafile: $rf_datafile");
        $frf = fopen($rf_datafile,'w');

        $if_datafile_name =  "NoiseTemp_IF.txt";
        $if_datafile = $plotdir . $if_datafile_name;
        $l->WriteLogFile("if_datafile: $if_datafile");
        $fif = fopen($if_datafile,'w');

        $avg_datafile_name =  "NoiseTemp_avg.txt";
        $avg_datafile = $plotdir . $avg_datafile_name;
        $l->WriteLogFile("average_datafile: $avg_datafile");
        $favg = fopen($avg_datafile,'w');

        $datafile_cart_0_1_name =  "NoiseTemp_Cart_pol0_SB1.txt";
        $datafile_cart_0_1 = $plotdir . $datafile_cart_0_1_name;
        $l->WriteLogFile("datafile_cart pol 0 SB1: $datafile_cart_0_1");
        $fc01 = fopen($datafile_cart_0_1,'w');

        $datafile_cart_0_2_name =  "NoiseTemp_Cart_pol0_SB2.txt";
        $datafile_cart_0_2 = $plotdir . $datafile_cart_0_2_name;
        $l->WriteLogFile("datafile_cart pol 0 SB2: $datafile_cart_0_2");
        $fc02 = fopen($datafile_cart_0_2,'w');

        $datafile_cart_1_1_name =  "NoiseTemp_Cart_pol1_SB1.txt";
        $datafile_cart_1_1 = $plotdir . $datafile_cart_1_1_name;
        $l->WriteLogFile("datafile_cart pol 1 SB1: $datafile_cart_1_1");
        $fc11 = fopen($datafile_cart_1_1,'w');

        $datafile_cart_1_2_name =  "NoiseTemp_Cart_pol1_SB2.txt";
        $datafile_cart_1_2 = $plotdir . $datafile_cart_1_2_name;
        $l->WriteLogFile("datafile_cart pol 1 SB2: $datafile_cart_1_2");
        $fc12 = fopen($datafile_cart_1_2,'w');

        $datafile_diff_0_1_name =  "NoiseTemp_Diff_pol0_SB1.txt";
        $datafile_diff_0_1 = $plotdir . $datafile_diff_0_1_name;
        $l->WriteLogFile("datafile_diff pol 0 SB1: $datafile_diff_0_1");
        $fdiff01 = fopen($datafile_diff_0_1,'w');

        $datafile_diff_0_2_name =  "NoiseTemp_Diff_pol0_SB2.txt";
        $datafile_diff_0_2 = $plotdir . $datafile_diff_0_2_name;
        $l->WriteLogFile("datafile_diff pol 0 SB2: $datafile_diff_0_2");
        $fdiff02 = fopen($datafile_diff_0_2,'w');

        $datafile_diff_1_1_name =  "NoiseTemp_Diff_pol1_SB1.txt";
        $datafile_diff_1_1 = $plotdir . $datafile_diff_1_1_name;
        $l->WriteLogFile("datafile_diff pol 1 SB1: $datafile_diff_1_1");
        $fdiff11 = fopen($datafile_diff_1_1,'w');

        $datafile_diff_1_2_name =  "NoiseTemp_Diff_pol1_SB2.txt";
        $datafile_diff_1_2 = $plotdir . $datafile_diff_1_2_name;
        $l->WriteLogFile("datafile_diff pol 1 SB2: $datafile_diff_1_2");
        $fdiff12 = fopen($datafile_diff_1_2,'w');

        $spec_datafile_name =  "NoiseTemp_spec.txt";
        $spec_datafile = $plotdir . $spec_datafile_name;
        $l->WriteLogFile("specifications datafile: $spec_datafile");
        $fspec = fopen($spec_datafile,'w');

        //***************************************************
        //Get Image Rejection Data
        //***************************************************
        $cnt = 0;  // initialize counter for do-while loop
        do {    // check all CCA configurations for IR data
            //get CCA Test Data key
            $q = "SELECT keyID FROM TestData_header WHERE fkTestData_Type = 38
                AND fkDataStatus = 7 AND fkFE_Components = $CCA_key[$cnt]
                AND keyFacility =" . $this->GetValue('keyFacility') ."";
            $r = @mysql_query($q,$this->dbconnection);
            $l->WriteLogFile("CCA Image Rejection Testdata_Header Query: $q");
            $CCA_TD_key = @mysql_result($r,0,0);
            $l->WriteLogFile("CCA TD key: $CCA_TD_key");

            //get CCA Image Rejection Test Data key
            $q = "SELECT FreqLO, CenterIF, Pol, SB, SBR
                FROM CCA_TEST_SidebandRatio WHERE fkHeader = $CCA_TD_key
                AND fkFacility =" . $this->GetValue('keyFacility') . "
                ORDER BY POL DESC, SB DESC, FreqLO ASC, CenterIF DESC";
            $cnt++;
        } while ($CCA_TD_key === FALSE && $cnt < count($CCA_key));

        if ($CCA_TD_key !== FALSE && $this->GetValue('Band')!=9 && $this->GetValue('Band')!=10) {
            $l->WriteLogFile("Cartridge Image Rejection Data Was Found");
            $IR_Data =1;    // flag to indicate Imagine Rejection Data is found
            $r = @mysql_query($q,$this->dbconnection);
            $l->WriteLogFile("CCA Image Rejection Data Query: $q");
            // initialize arrays so that array search doesn't crash for band 9
            $IR_LSB_Pol1_Sb2[0] = 0;
            $IR_LSB_Pol0_Sb2[0] = 0;
            $IR_Pol1_Sb2[0] = 0;
            $IR_Pol0_Sb2[0] = 0;

            while ($row = @mysql_fetch_array($r)){
                // pol 1 SB2
                if ($row[2] == 1 && $row[3] == 2){
                    $IR_LSB_Pol1_Sb2[] = $row[0] - $row[1];
                    $IR_Pol1_Sb2[] =$row[4];

                // pol 1 SB1 or band 9 pol1 SB0
                } else if ($row[2] == 1 && ($row[3] == 1 || $row[3] == 0)){
                    $IR_USB_Pol1_Sb1[] = $row[0] + $row[1];
                    $IR_Pol1_Sb1[] =$row[4];

                // pol 0 SB2
                } else if ($row[2] == 0 && $row[3] == 2){
                    $IR_LSB_Pol0_Sb2[] = $row[0] - $row[1];
                    $IR_Pol0_Sb2[] =$row[4];

                // pol 0 SB1 or band 9 pol1 SB0
                } else if ($row[2] == 0 && ($row[3] == 1 || $row[3] == 0)){
                    $IR_USB_Pol0_Sb1[] = $row[0] + $row[1];
                    $IR_Pol0_Sb1[] =$row[4];
                }
            }
        } else {
            $IR_Data = 0;  // flag to indicate Imagine Rejection Data is not found
            if ( $this->GetValue('Band') != 9 && $this->GetValue('Band') != 10){
                echo "<B>NO CARTRIDGE IMAGE REJECTION DATA FOUND<B><BR><BR>";
                $l->WriteLogFile("No Cartridge Image Rejection Data Found");
            }
        }

        //***************************************************
        //Get and process Noise Temp Data
        //***************************************************

        //get specs from DB
        $specs = get_specs( 58 , $this->GetValue('Band') );
        //assign specs to variables
        $coldload=$specs[1];
        $default_IR=$specs[2];
        $upper_freq_limit=$specs[3];
        $lower_freq_limit=$specs[4];
        $TSSB_80_spec=$specs[5];
        $TSSB_spec=$specs[6];
        $NT_spec2=$specs[7];
        $TSSB_80_spec_RF_limit = $specs[17];

        /*
        SWITCH ($this->GetValue('Band')){
               case 1:
                $coldload = 78.2;
                $default_IR = 10;
                $upper_freq_limit = 10;
                $lower_freq_limit = 4;
                $TSSB_80_spec = 17;
                $TSSB_spec = 26;
                break;
               case 2:
                $coldload = 78.2;
                $default_IR = 10;
                $upper_freq_limit = 10;
                $lower_freq_limit = 4;
                $TSSB_80_spec = 17;
                $TSSB_spec = 47;
                break;
               case 3:
                $coldload = 78.0;
                $default_IR = 10;
                $upper_freq_limit = 8;
                $lower_freq_limit = 4;
                $TSSB_80_spec = 39;
                $TSSB_spec = 60;
                $NT_spec2 = 43;
                break;
               case 4:
                $coldload = 80.1;
                $default_IR = 10;
                $upper_freq_limit = 8;
                $lower_freq_limit = 4;
                $TSSB_80_spec = 51;
                $TSSB_spec = 82;
                break;
               case 5:
                $coldload = 78.2;
                $default_IR = 10;
                $upper_freq_limit = 10;
                $lower_freq_limit = 4;
                $TSSB_80_spec = 65;
                $TSSB_spec = 105;
                break;
            case 6:
                $coldload = 79.1;
                $default_IR = 10;
                $upper_freq_limit = 10;
                $lower_freq_limit = 5;
                $TSSB_80_spec = 83;
                $TSSB_spec = 136;
                break;
               case 7:
                $coldload = 78.1;
                $default_IR = 10;
                $upper_freq_limit = 8;
                $lower_freq_limit = 4;
                $TSSB_80_spec = 147;
                $TSSB_spec = 219;
                break;
            case 8:
                $coldload = 78.2;
                $default_IR = 10;
                $upper_freq_limit = 8;
                $lower_freq_limit = 4;
                $TSSB_80_spec = 196;
                $TSSB_spec = 292;
                    break;
            case 9:
                $coldload = 78.2;
                $default_IR = 1000;
                $upper_freq_limit = 12;
                $lower_freq_limit = 4;
                $TSSB_80_spec = 175;
                $TSSB_spec = 261;
                    break;
            case 10:
                $coldload = 78.2;
                $default_IR = 10;
                $upper_freq_limit = 12;
                $lower_freq_limit = 4;
                $TSSB_80_spec = 230;
                $TSSB_spec = 344;
                break;
            }
        */

        if ($this->GetValue('DataSetGroup') == 0){
            $q = "SELECT FreqLO, CenterIF, TAmbient, Pol0Sb1YFactor, Pol0Sb2YFactor, Pol1Sb1YFactor, Pol1Sb2YFactor
            FROM Noise_Temp
            WHERE fkSub_Header=". $this->NT_SubHeader->GetValue('keyId')."
            AND keyFacility =" . $this->GetValue('keyFacility') . "
            AND Noise_Temp.IsIncluded = 1
            ORDER BY FreqLO ASC, CenterIF ASC";
        } else {
            // query to get front end key of the FEConfig of the TDH.
            $qfe = "SELECT fkFront_Ends FROM `FE_Config` WHERE `keyFEConfig` = ". $this->GetValue('fkFE_Config');

            //Get all Noise_Temp_SubHeader keyId values for records with the same DataSetGroup as this one
            $q = "SELECT Noise_Temp.FreqLO, Noise_Temp.CenterIF, Noise_Temp.TAmbient, Noise_Temp.Pol0Sb1YFactor, Noise_Temp.Pol0Sb2YFactor, Noise_Temp.Pol1Sb1YFactor, Noise_Temp.Pol1Sb2YFactor, TestData_header.keyId
                    FROM FE_Config
                    LEFT JOIN TestData_header ON TestData_header.fkFE_Config = FE_Config.keyFEConfig
                    LEFT JOIN Noise_Temp_SubHeader ON Noise_Temp_SubHeader.`fkHeader` = `TestData_header`.`keyId`
                    LEFT JOIN Noise_Temp ON Noise_Temp_SubHeader.`keyId` = Noise_Temp.fkSub_Header
                    WHERE TestData_header.Band = " . $this->GetValue('Band')."
                    AND TestData_header.fkTestData_Type= 58
                    AND TestData_header.DataSetGroup= " . $this->GetValue('DataSetGroup')."
                    AND Noise_Temp.IsIncluded = 1
                    AND FE_Config.fkFront_Ends = ($qfe)
                    ORDER BY Noise_Temp.FreqLO ASC, Noise_Temp.CenterIF ASC";
        }
        $r = @mysql_query($q,$this->dbconnection);
        $l->WriteLogFile("NoiseTemp get Data query: $q");

        // write specifications datafile
        $string1 = "$lower_freq_limit\t$TSSB_80_spec\t$TSSB_spec\r\n";
        $string2 = "$upper_freq_limit\t$TSSB_80_spec\t$TSSB_spec\r\n";
        $writestring = $string1 . $string2;
        fwrite($fspec,$writestring);

        $init_last_freq = 1;  // initialize last frequency read

        while ($row = @mysql_fetch_array($r)){
            if ($init_last_freq == 1){
                $last_freq = $row[0];  // initialize freq compare value
                $init_last_freq = 0;
            }

            $USB = $row[0] + $row[1];
            $LSB = $row[0] - $row[1];
            // Tr, uncorrected (K)
             $Pol0_Sb1_Tr_uncorr = ($row[2]-$coldload*$row[3])/($row[3]-1);
             $Pol0_Sb2_Tr_uncorr = ($row[2]-$coldload*$row[4])/($row[4]-1);
            $Pol1_Sb1_Tr_uncorr = ($row[2]-$coldload*$row[5])/($row[5]-1);
            $Pol1_Sb2_Tr_uncorr = ($row[2]-$coldload*$row[6])/($row[6]-1);

            // Select Image Rejection data
            if ($IR_Data == 1) { // make sure IR data is found
                // pol 1 SB2
                $index=array_search($LSB,$IR_LSB_Pol1_Sb2);
                if ($index !== FALSE){
                    $IR_1_2 = $IR_Pol1_Sb2[$index];

                } else { $IR_1_2 = $default_IR; }

                // pol 1 SB1
                $index=array_search($USB,$IR_USB_Pol1_Sb1);
                if ($index !== FALSE){
                    $IR_1_1 = $IR_Pol1_Sb1[$index];
                } else { $IR_1_1 = $default_IR; }

                // pol 0 SB2
                $index=array_search($LSB,$IR_LSB_Pol0_Sb2);
                if ($index !== FALSE){
                    $IR_0_2 = $IR_Pol0_Sb2[$index];
                } else { $IR_0_2 = $default_IR; }

                // pol 0 SB1
                $index=array_search($USB,$IR_USB_Pol0_Sb1);
                if ($index !== FALSE){
                    $IR_0_1 = $IR_Pol0_Sb1[$index];
                } else { $IR_0_1 = $default_IR; }

                // correct the data using image correction
                //Tssb, corrected (K)
                $Pol0_Sb1_Tssb_corr = $Pol0_Sb1_Tr_uncorr * (1+pow(10,-abs($IR_0_1)/10));
                $Pol0_Sb2_Tssb_corr = $Pol0_Sb2_Tr_uncorr * (1+pow(10,-abs($IR_0_2)/10));
                $Pol1_Sb1_Tssb_corr = $Pol1_Sb1_Tr_uncorr * (1+pow(10,-abs($IR_1_1)/10));
                $Pol1_Sb2_Tssb_corr = $Pol1_Sb2_Tr_uncorr * (1+pow(10,-abs($IR_1_2)/10));

            } else {
            // if no Cartridge image rejection data is found don't correct data
                $Pol0_Sb1_Tssb_corr = $Pol0_Sb1_Tr_uncorr;
                $Pol0_Sb2_Tssb_corr = $Pol0_Sb2_Tr_uncorr;
                $Pol1_Sb1_Tssb_corr = $Pol1_Sb1_Tr_uncorr;
                $Pol1_Sb2_Tssb_corr = $Pol1_Sb2_Tr_uncorr;
            }

            //insert empty line between LO scans to make plots look better
            //and save average data to file for plotting
            if ($last_freq !== $row[0]) {
                $writestring = "\r\n";
                 fwrite($frf,$writestring);
                 fwrite($fif,$writestring);

                 // calculate and save average NT dadta to file and db
                save_avg_NT( $last_freq, $Pol0_Sb1_avg ,$Pol0_Sb2_avg,$Pol1_Sb1_avg,$Pol1_Sb2_avg,$TSSB_80_spec,$TSSB_spec,$TSSB_80_spec_RF_limit,$favg);

                $last_freq = $row[0];  // save new last frequency

                // reset average array
                unset ($Pol0_Sb1_avg);
                unset ($Pol0_Sb2_avg);
                unset ($Pol1_Sb1_avg);
                unset ($Pol1_Sb2_avg);
            }

            //Write the data to a file for gnuplot
            $writestring = "$row[1]\t$Pol0_Sb1_Tssb_corr\t$Pol0_Sb2_Tssb_corr\t$Pol1_Sb1_Tssb_corr\t$Pol1_Sb2_Tssb_corr\r\n";
             fwrite($fif,$writestring);

             //save data for 5 Ghz window plots
             if ($row[1] >= $lower_freq_limit && $row[1] <= $upper_freq_limit){
                $writestring = "$USB\t$LSB\t$Pol0_Sb1_Tssb_corr\t$Pol0_Sb2_Tssb_corr\t$Pol1_Sb1_Tssb_corr\t$Pol1_Sb2_Tssb_corr\r\n";
                fwrite($frf,$writestring);
                // save data to compare with cart data
                $FEIC_USB[] = $USB;
                $FEIC_LSB[] = $LSB;
                $Pol0_Sb1[] = $Pol0_Sb1_Tssb_corr;
                $Pol0_Sb2[] = $Pol0_Sb2_Tssb_corr;
                $Pol1_Sb1[] = $Pol1_Sb1_Tssb_corr;
                $Pol1_Sb2[] = $Pol1_Sb2_Tssb_corr;
                // save data for average plot
                $Pol0_Sb1_avg[] = $Pol0_Sb1_Tssb_corr;
                $Pol0_Sb2_avg[] = $Pol0_Sb2_Tssb_corr;
                $Pol1_Sb1_avg[] = $Pol1_Sb1_Tssb_corr;
                $Pol1_Sb2_avg[] = $Pol1_Sb2_Tssb_corr;

             }
        }

        //save last average point
        save_avg_NT( $last_freq, $Pol0_Sb1_avg ,$Pol0_Sb2_avg,$Pol1_Sb1_avg,$Pol1_Sb2_avg,$TSSB_80_spec,$TSSB_spec,$TSSB_80_spec_RF_limit,$favg);

        // For band 3 read the average NT file and store the information in the db
        if ($this->GetValue('Band') == 3){
            fclose($favg);
            $favg = fopen($avg_datafile,'r');

            // read file and format data into a string to write out in a DB query
            while ($scan = fscanf($favg,"%f\t%f\t%f\t%f\t%f\t%f\r\n")) {
                list ($freq, $avg01, $avg02, $avg11, $avg12, $TSSB_80_spec) = $scan;
                $avg = ($avg01+$avg02+$avg11+$avg12)/4;
                $values ="(" . $this->GetValue('keyId'). ",$freq,$avg01,$avg02,$avg11,$avg12,$avg),".$values;
            }
            //delete last "," and replace it with ";"
            $values = substr_replace ($values,";",(strlen ($values))-1);

            //query to delete any existing data in the DB with the same TD Header keyID
            $q ="DELETE FROM `Noise_Temp_Band3_Results`
                WHERE  `fkHeader` = " . $this->GetValue('keyId') . "";
            $r = @mysql_query($q,$this->dbconnection);

            // query to insert new data into table
            $q ="INSERT INTO `Noise_Temp_Band3_Results`
                (`fkHeader`,`FreqLO`,`Pol0USB`,`Pol0LSB`,`Pol1USB`,`Pol1LSB`,`AvgNT`)
                VALUES $values";
            $r = @mysql_query($q,$this->dbconnection);

            $l->WriteLogFile("Band3 Replace Query: $q\r\n");

        }

        fclose($frf);
        fclose($fif);
        fclose($favg);
        fclose($fspec);

        //***************************************************
        //Get and process Cartridge Noise Temp Data
        //***************************************************
        $cnt = 0;  // initialize counter for do-while loop
        do {    // check all CCA configurations for NT data
            //Use CCA FE_Component keyid to get keyID for Testdata header record for CCA Noise temp data
            $q ="SELECT keyId FROM TestData_header
                WHERE fkFE_Components = $CCA_key[$cnt]
                AND fkTestData_Type = 42
                AND fkDataStatus=7
                AND keyFacility =" . $this->GetValue('keyFacility') ."
                GROUP BY keyId DESC";
            $r = @mysql_query($q,$this->dbconnection);
            $l->WriteLogFile("CCA Noise Temp Testdata record query: $q");
            $CCA_NT_key = @mysql_result($r,0,0);
            $l->WriteLogFile("CCA NoiseTemp Testdataheader key: $CCA_NT_key");
            $cnt++;
        } while ($CCA_NT_key === FALSE && $cnt < count($CCA_key));

        if ($CCA_NT_key !== FALSE){
                $CCA_Data = 1;  // flag to indicate CCA_NT Data is found
                // finally get the CCA Noise Temp data...I'm sure there's a better way
                $q ="SELECT FreqLO, CenterIF, Pol, SB, Treceiver FROM CCA_TEST_NoiseTemperature
                    WHERE fkheader = $CCA_NT_key ORDER BY POL DESC, SB DESC, FreqLO ASC, CenterIF DESC";
                $r = @mysql_query($q,$this->dbconnection);
            $l->WriteLogFile("CCA Noise Temp data query: $q");


            $cnt_band9_0 = 0;
            $cnt_band9_1 = 0;
            while ($row = @mysql_fetch_array($r)){

                // special band 3 NT_specs
                if ( $this->GetValue('Band') == 3 && $row[0] == 104){
                    $NT_spec = $NT_spec2;
                } else {
                    $NT_spec = $TSSB_80_spec;
                }

                $USB = $row[0] + $row[1];
                $LSB = $row[0] - $row[1];

                // Write plot data out to files.  Only plot specific window and only exclude Band 9
                if ($row[1] >= $lower_freq_limit && $row[1] <= $upper_freq_limit ){

                    // pol 0 SB1
                    if ( $row[2] == 0 && $row[3] == 1){
                        //insert empty line between LO scans to make plots look better
                        if ($last_freq != $row[0]) {
                            $last_freq = $row[0];
                            $writestring = "\r\n";
                             fwrite($fc01,$writestring);
                         }
                        // write CCA plot data to file
                         $writestring = "$USB\t$row[4]\t$row[2]\t$row[3]\r\n";
                         fwrite($fc01,$writestring);

                         // Save difference data to plot
                        $index=array_search($USB,$FEIC_USB);
                        if ($index !== FALSE){
                            $diff_save=100*abs($Pol0_Sb1[$index]-$row[4])/$NT_spec;
                            $writestring = "$USB\t$diff_save\r\n";
                             fwrite($fdiff01,$writestring);
                        }

                    // pol 0 SB2
                    } else if ( $row[2] == 0 && $row[3] == 2){
                        //insert empty line between LO scans to make plots look better
                        if ($last_freq != $row[0]) {
                            $last_freq = $row[0];
                            $writestring = "\r\n";
                             fwrite($fc02,$writestring);
                        }

                        // write CCA plot data to file
                         $writestring = "$LSB\t$row[4]\t$row[2]\t$row[3]\r\n";
                         fwrite($fc02,$writestring);

                         // Save difference data to plot
                        $index=array_search($LSB,$FEIC_LSB);
                        if ($index !== FALSE){
                            $diff_save=100*abs($Pol0_Sb2[$index]-$row[4])/$NT_spec;
                            $writestring = "$LSB\t$diff_save\r\n";
                             fwrite($fdiff02,$writestring);
                        }

                    // pol 1 SB1
                    } else if ( $row[2] == 1 && $row[3] == 1 ){
                        //insert empty line between LO scans to make plots look better
                        if ($last_freq != $row[0]) {
                            $last_freq = $row[0];
                            $writestring = "\r\n";
                             fwrite($fc11,$writestring);
                         }
                        // write CCA plot data to file
                        $writestring = "$USB\t$row[4]\t$row[2]\t$row[3]\r\n";
                         fwrite($fc11,$writestring);

                        // Save difference data to plot
                        $index=array_search($USB,$FEIC_USB);
                        if ($index !== FALSE){
                            $diff_save=100*abs($Pol1_Sb1[$index]-$row[4])/$NT_spec;
                            $writestring = "$USB\t$diff_save\r\n";
                             fwrite($fdiff11,$writestring);
                        }

                     // pol 1 SB2
                    } else if ( $row[2] == 1 && $row[3] == 2){
                        //insert empty line between LO scans to make plots look better
                        if ($last_freq !== $row[0]) {
                            $last_freq = $row[0];
                            $writestring = "\r\n";
                             fwrite($fc12,$writestring);
                         }
                        // write CCA plot data to file
                        $writestring = "$LSB\t$row[4]\t$row[2]\t$row[3]\r\n";
                         fwrite($fc12,$writestring);

                        // Save difference data to plot
                        $index=array_search($LSB,$FEIC_LSB);
                        if ($index !== FALSE){
                            $diff_save=100*abs($Pol1_Sb2[$index]-$row[4])/$NT_spec;
                            $writestring = "$LSB\t$diff_save\r\n";
                             fwrite($fdiff12,$writestring);
                        }

                    // band 9 pol0 SB0
                    }  else if ($row[2] == 0 &&  $row[3] == 0){
                        //insert empty line between LO scans to make plots look better
                        if ($last_freq != $row[0]) {
                            $last_freq = $row[0];
                            $writestring = "\r\n";
                             fwrite($fc01,$writestring);
                         }
                        // write CCA plot data to file
                         $writestring = "$USB\t$row[4]\t$row[2]\t$row[3]\r\n";
                         fwrite($fc01,$writestring);

                        // the CCA NT values are measured at a finer resolution than at the FEIC
                        // therefore the CCA NT data is averaged over a range that corresponds
                        // to a single FEIC scan.  To do this, two arrays are created here.
                        // one array is for the CCA_NT data and the other is an index array
                        // that correlates the CCA NT data to a single FEIC scan.


                        // loop through all FEIC data to correlate the CCA NT data
                        $FEIC_cnt = 0;
                        $USB_found = 0;
                        while ($USB_found != 1 && $FEIC_cnt <= count($FEIC_USB) ){
                            // set window (0.1 Ghz) to correlate data
                             if ( $USB <= $FEIC_USB[$FEIC_cnt] + 0.05 && $USB >= $FEIC_USB[$FEIC_cnt] - 0.05 ){
                                $Band9_USB_Pol0[$cnt_band9_0] = $FEIC_USB[$FEIC_cnt];
                                $Band9_NT_Pol0[$cnt_band9_0] = $row[4];
                                $cnt_band9_0++;
                                $USB_found = 1;
                            }
                            $FEIC_cnt++;
                        }

                    // band 9 pol1 SB0
                    } else if ( $row[2] == 1 && $row[3] == 0 ){
                        //insert empty line between LO scans to make plots look better
                        if ($last_freq !== $row[0]) {
                            $last_freq = $row[0];
                            $writestring = "\r\n";
                             fwrite($fc11,$writestring);
                         }
                        // write CCA plot data to file
                        $writestring = "$USB\t$row[4]\t$row[2]\t$row[3]\r\n";
                         fwrite($fc11,$writestring);

                        // loop through all FEIC data to correlate the CCA NT data
                        $FEIC_cnt = 0;
                        $USB_found = 0;
                        while ($USB_found != 1 && $FEIC_cnt < count($FEIC_USB) ){
                            // set window (0.1 Ghz) to correlate data
                             if ( $USB <= $FEIC_USB[$FEIC_cnt] + 0.05 && $USB >= $FEIC_USB[$FEIC_cnt] - 0.05 ){
                                $Band9_USB_Pol1[$cnt_band9_1] = $FEIC_USB[$FEIC_cnt];
                                $Band9_NT_Pol1[$cnt_band9_1] = $row[4];
                                $cnt_band9_1++;
                                $USB_found = 1;
                            }
                            $FEIC_cnt++;
                        }
                        if ($USB_found == 0){echo "POL 1 USB not found: $USB<br>";}
                    }
                }

            } // end while loop

            // Process Band 9 Cartridge data
            if ( $this->GetValue('Band') == 9 || $this->GetValue('Band') == 10 ){
                // calculate difference for Pol 0
                $FEIC_cnt = 0;    // initilize index for FEIC NT data
                foreach ($FEIC_USB as $USB_FEIC){
                    $indexes=array_keys($Band9_USB_Pol0,$USB_FEIC);
                    $index_cnt = count($indexes);    //  How many values need to be averaged
                    if ($index_cnt != 0){
                        $sum =0;        // initialize sum
                        foreach ($indexes as $index){    // average values
                            $sum = $Band9_NT_Pol0[$index] + $sum;
                        }
                        $avg = $sum / $index_cnt;        //  calculate average NT

                        // save difference data to file
                        $diff_save=100*abs($Pol0_Sb1[$FEIC_cnt]-$avg)/$NT_spec;
                        $writestring = "$USB_FEIC\t$diff_save\r\n";
                         fwrite($fdiff01,$writestring);
                    }
                $FEIC_cnt++; // increment index
                }

                // calculate difference for Pol 1
                $FEIC_cnt = 0;    // initilize index for FEIC NT data
                foreach ($FEIC_USB as $USB_FEIC){
                    $indexes=array_keys($Band9_USB_Pol1,$USB_FEIC);
                    $index_cnt = count($indexes);    //  How many values need to be averaged
                    if ($index_cnt == 0){
                    } else {
                        $sum =0;        // initialize sum
                        foreach ($indexes as $index){    // average values
                            $sum = $Band9_NT_Pol1[$index] + $sum;
                        }
                        $avg = $sum / $index_cnt;        //  calculate average NT

                        // save difference data to file
                        $diff_save=100*abs($Pol1_Sb1[$FEIC_cnt]-$avg)/$NT_spec;
                        $writestring = "$USB_FEIC\t$diff_save\r\n";
                         fwrite($fdiff11,$writestring);
                    }
                    $FEIC_cnt++; // increment index
                }
            }
        } else {
            $CCA_Data = 0;  // flag to indicate CCA_NT Data is not found
            echo "<B>NO CCA NOISE TEMPERATURE DATA FOUND<B><BR><BR>";
        }

        fclose($fc01);
        fclose($fc02);
        fclose($fc11);
        fclose($fc12);
        fclose($fdiff01);
        fclose($fdiff02);
        fclose($fdiff11);
        fclose($fdiff12);

        //***************************************************
        //Plotting code
        //***************************************************
        //common plotting code
        if ($this->GetValue('DataSetGroup') == 0){
            $plot_label_1 =" set label 'TestData_header.keyId: $this->keyId, Plot SWVer: $Plot_SWVer, Meas SWVer: ".$this->GetValue('Meas_SWVer')."' at screen 0.01, 0.01\r\n";
            $plot_label_2 ="set label '".$this->GetValue('TS').", FE Configuration ".$this->GetValue('fkFE_Config')."' at screen 0.01, 0.04\r\n";
        } else {

            $q = "SELECT `TestData_header`.keyID, `TestData_header`.TS,`TestData_header`.`fkFE_Config`,`TestData_header`.Meas_SWVer
                FROM FE_Config
                LEFT JOIN `TestData_header` ON TestData_header.fkFE_Config = FE_Config.keyFEConfig
                WHERE TestData_header.Band = " . $this->GetValue('Band')."
                AND TestData_header.fkTestData_Type= " . $this->GetValue('fkTestData_Type')."
                AND TestData_header.DataSetGroup= " . $this->GetValue('DataSetGroup')."
                AND FE_Config.fkFront_Ends = (SELECT fkFront_Ends FROM `FE_Config` WHERE `keyFEConfig` = ".$this->GetValue('fkFE_Config').")
                ORDER BY `TestData_header`.keyID DESC";

            $r = @mysql_query($q, $this->dbconnection);

            $cnt = 0; //initialize counter
            while ($row = @mysql_fetch_array($r)){
                if ($cnt == 0){ // initialize label variables
                    $keyId = $row[0];
                    $maxTS = $row[1];
                    $minTS = $row[1];
                    $max_FE_Config = $row[2];
                    $min_FE_Config = $row[2];
                    $meas_ver = $row[3];
                } else { // find the max and min TS and FE_config
                    $keyId = "$keyId,$row[0]";
                    if ($row[1] > $maxTS){
                        $maxTS = $row[1];
                    }
                    if ($row[1] < $minTS){
                        $minTS = $row[1];
                    }
                    if ($row[1] > $max_FE_Config){
                        $max_FE_Config = $row[2];
                    }
                    if ($row[1] < $min_FE_Config){
                        $min_FE_Config = $row[2];
                    }
                }
                $cnt++;
            }
            // format label string variables to display
            if ($cnt > 1){
                $TS = "($maxTS, $minTS)";
                $FE_Config = "($max_FE_Config, $min_FE_Config)";
            } else {
                $TS = "($maxTS)";
                $FE_Config = "($max_FE_Config)";
            }
            $plot_label_1 =" set label 'TestData_header.keyId: ($keyId), Plot SWVer: $Plot_SWVer, Meas SWVer: $meas_ver' at screen 0.01, 0.01\r\n";
            $plot_label_2 ="set label 'Dataset: ".$this->GetValue('DataSetGroup').", TS: $TS, FE Configuration: $FE_Config' at screen 0.01, 0.04\r\n";

        }

        // Tssb vs IF frequency plot
        $imagename = "Tssb_vs_IF_NoiseTemp " . date('Y_m_d_H_i_s') . ".png";
        $imagepath = $plotdir . $imagename;
        $l->WriteLogFile("image path: $imagepath");
        $plot_title = "Receiver Noise Temperature ";
        if ($IR_Data == 1)
            $plot_title .= "Tssb corrected";
        else
            $plot_title .= "T_rec uncorrected";
        $plot_title .= ", FE SN" . $this->FrontEnd->GetValue('SN') . ", CCA" . $this->GetValue('Band') . "-$CCA_SN WCA" . $this->GetValue('Band') . "-$WCA_SN";
        $y_lim = 1.1 * $TSSB_spec;  // upper limit to y axis

        // Create GNU plot command file for Tssb vs IF plot command
        $commandfile = $plotdir . "Tssb_vs_IF_plotcommands.txt";
        $f = fopen($commandfile,'w');
        $l->WriteLogFile("command file: $commandfile");
        fwrite($f, "set terminal png size 900,600 crop\r\n");
        fwrite($f, "set output '$imagepath'\r\n");
        fwrite($f, "set title '$plot_title'\r\n");
        fwrite($f, "set xlabel 'IF(GHz)'\r\n");
        if ($IR_Data == 1)
            fwrite($f, "set ylabel 'Tssb (K)'\r\n");
        else
            fwrite($f, "set ylabel 'T_Rec (K)'\r\n");
        fwrite($f, "set yrange [0:$y_lim]\r\n");
        fwrite($f, "set key outside\r\n");
        fwrite($f, "set bmargin 6\r\n");
    //    fwrite($f, "set linestyle 1 lt 1 lw 3\r\n");
        fwrite($f, $plot_label_1);
        fwrite($f, $plot_label_2);
        // band dependent plotting
        if ($this->GetValue('Band') == 9 || $this->GetValue('Band') == 10){
            fwrite($f, "plot  '$if_datafile' using 1:2 with lines lt 1 lw 1 title 'Pol0',");
            fwrite($f, "'$if_datafile' using 1:4 with lines lt 3 lw 1 title 'Pol1',");
            fwrite($f, "'$spec_datafile' using 1:3 with lines lt -1 lw 3 title ' $TSSB_spec K (20%)',");
            fwrite($f, "'$spec_datafile' using 1:2 with lines lt 0 lw 1 title ' $TSSB_80_spec K (80%)\r\n',");
        } else {
            fwrite($f, "plot  '$if_datafile' using 1:2 with lines lt 1 lw 1 title 'Pol0sb1',");
            fwrite($f, "'$if_datafile' using 1:3 with lines lt 2 lw 1 title 'Pol0sb2',");
            fwrite($f, "'$if_datafile' using 1:4 with lines lt 3 lw 1 title 'Pol1sb1',");
            fwrite($f, "'$if_datafile' using 1:5 with lines lt 4 lw 1 title 'Pol1sb2',");
            fwrite($f, "'$spec_datafile' using 1:3 with lines lt -1 lw 3 title ' $TSSB_spec K (20%)',");
            fwrite($f, "'$spec_datafile' using 1:2 with lines lt 0 lw 1 title '$TSSB_80_spec K (80%)'\r\n");
        }
        fclose($f);

        //Call gnuplot
        system("$GNUPLOT $commandfile");

        // store image location
        $image_url = $main_url_directory . "noisetemp/$imagename";
        $this->NT_SubHeader->SetValue('ploturl3',$image_url);

        // Average Tssb vs LO frequency plot
        $imagename = "Avg_Tssb_vs_LO_NoiseTemp " . date('Y_m_d_H_i_s') . ".png";
        $imagepath = $plotdir . $imagename;
        $l->WriteLogFile("image path: $imagepath");

        $plot_title = "Receiver Noise Temperature ";
        if ($IR_Data == 1)
            $plot_title .= "Tssb corrected";
        else
            $plot_title .= "T_Rec uncorrected";
        $plot_title .= ", FE SN" . $this->FrontEnd->GetValue('SN') . ", CCA" . $this->GetValue('Band') . "-$CCA_SN WCA" . $this->GetValue('Band') . "-$WCA_SN";

        // Create GNU plot command file for Tssb vs IF plot command
        $commandfile = $plotdir . "Avg_Tssb_vs_LO_plotcommands.txt";
        $f = fopen($commandfile,'w');
        $l->WriteLogFile("command file: $commandfile");
        fwrite($f, "set terminal png size 900,600 crop\r\n");
        fwrite($f, "set output '$imagepath'\r\n");
        fwrite($f, "set title '$plot_title'\r\n");
        fwrite($f, "set xlabel 'LO(GHz)'\r\n");
        if ($IR_Data == 1)
            fwrite($f, "set ylabel 'Average Tssb (K)'\r\n");
        else
            fwrite($f, "set ylabel 'Average T_Rec (K)'\r\n");
        fwrite($f, "set yrange [0:$y_lim]\r\n");
        fwrite($f, "set key outside\r\n");
        fwrite($f, "set bmargin 6\r\n");
        fwrite($f, $plot_label_1);
        fwrite($f, $plot_label_2);

        switch ( $this->GetValue('Band') ){
            case 9;
                fwrite($f, "plot  '$avg_datafile' using 1:2 with linespoints lt 1 lw 1 title 'Pol0',");
                fwrite($f, "'$avg_datafile' using 1:4 with linespoints lt 3 lw 1 title 'Pol1',");
                fwrite($f, "'$avg_datafile' using 1:6 with lines lt -1 lw 3 title ' $TSSB_80_spec K (80%)'\r\n");
                break;

            case 10;
                fwrite($f, "plot  '$avg_datafile' using 1:2 with linespoints lt 1 lw 1 title 'Pol0',");
                fwrite($f, "'$avg_datafile' using 1:4 with linespoints lt 3 lw 1 title 'Pol1',");
                fwrite($f, "'$avg_datafile' using 1:6 with lines lt 0 lw 3 title ' $TSSB_80_spec K (80%)',");
                fwrite($f, "'$avg_datafile' using 1:7 with lines lt -1 lw 3 title ' $TSSB_spec K (100%)'\r\n");
                break;

            default;
                fwrite($f, "plot  '$avg_datafile' using 1:2 with linespoints lt 1 lw 1 title 'Pol0sb1',");
                fwrite($f, "'$avg_datafile' using 1:3 with linespoints lt 2 lw 1 title 'Pol0sb2',");
                fwrite($f, "'$avg_datafile' using 1:4 with linespoints lt 3 lw 1 title 'Pol1sb1',");
                fwrite($f, "'$avg_datafile' using 1:5 with linespoints lt 4 lw 1 title 'Pol1sb2',");
                fwrite($f, "'$avg_datafile' using 1:6 with lines lt -1 lw 3 title ' $TSSB_80_spec K (80%)'\r\n");
                break;
        }

        fclose($f);

        //Call gnuplot
        system("$GNUPLOT $commandfile");

        // store image location
        $image_url = $main_url_directory . "noisetemp/$imagename";
        $this->NT_SubHeader->SetValue('ploturl4',$image_url);


        // start loop for TSSB vs RF freq plots
        for ($cnt = 0; $cnt < 4; $cnt++){
        $plot_title = "Receiver Noise Temperature, $lower_freq_limit-$upper_freq_limit GHz IF, FE SN" . $this->FrontEnd->GetValue('SN').
        ", CCA" . $this->GetValue('Band').
        "-$CCA_SN WCA" . $this->GetValue('Band'). "-$WCA_SN,";
        $imagename = "Tssb_vs_RF_Freq_NoiseTemp Plot$cnt " . date('Y_m_d_H_i_s') . ".png";
        $imagepath = $plotdir . $imagename;
        $l->WriteLogFile("image path: $imagepath");
        $image_url = $main_url_directory . "noisetemp/$imagename";

        // Create GNU plot command file
        $commandfile = $plotdir . "plotcommands_$cnt.txt";
        $f = fopen($commandfile,'w');
        $l->WriteLogFile("command file: $commandfile");
        fwrite($f, "set terminal png size 900,600 crop\r\n");
        fwrite($f, "set output '$imagepath'\r\n");
        fwrite($f, "set xlabel 'RF (GHz)'\r\n");
        if ( $IR_Data == 1 ){
            fwrite($f, "set ylabel 'Tssb corrected (K)'\r\n");
        } else {
            fwrite($f, "set ylabel 'T_Rec uncorrected (K)'\r\n");
            if ( $this->GetValue('Band') != 9 && $this->GetValue('Band') != 10){
                fwrite($f,  " ".'set label "****** UNCORRECTED DATA ****** UNCORRECTED DATA ****** UNCORRECTED DATA ******" at screen .08, .16'."\r\n");
                fwrite($f,  " ".'set label "****** UNCORRECTED DATA ****** UNCORRECTED DATA ****** UNCORRECTED DATA ******" at screen .08, .9'."\r\n");
            }
        }
        fwrite($f, "set y2label 'Difference from Spec(%)'\r\n");
        fwrite($f, "set y2tics\r\n");
        fwrite($f, "set y2range [0:120]\r\n");
        fwrite($f, "set key outside\r\n");
        fwrite($f, "set bmargin 6\r\n");
        fwrite($f, $plot_label_1);
        fwrite($f, $plot_label_2);
        fwrite($f, "set yrange [0:$y_lim]\r\n");

        switch ($cnt){
            case 0;
                if ($this->GetValue('Band') == 9 || $this->GetValue('Band') == 10){
                    $SB = "";
                } else {
                    $SB = "USB";
                }
                $plot_title = "$plot_title Pol 0 $SB";
                fwrite($f, "set title '$plot_title'\r\n");
                fwrite($f, "plot  '$rf_datafile' using 1:3 with lines lt 1 lw 3 title 'FEIC Meas Pol0 $SB'");
                // if statement to plot pol graphs
                if     ($CCA_Data == 1){
                    fwrite($f, ",");
                    fwrite($f, "'$datafile_cart_0_1' using 1:2 with lines lt 3 title 'Cart Group Meas Pol0 $SB',");
                    fwrite($f, "'$datafile_diff_0_1' using 1:2 with points lt -1 axes x1y2 title 'Diff relative to Spec'\r\n");
                } else {
                    fwrite($f, "\r\n");
                }
                fclose($f);

                //Call gnuplot
                system("$GNUPLOT $commandfile");
                $this->NT_SubHeader->SetValue('ploturl1',$image_url);
                break;

            case 1;
                if ($this->GetValue('Band') != 9 && $this->GetValue('Band') != 10){
                    $plot_title = "$plot_title Pol 0 LSB";
                    fwrite($f, "set title '$plot_title'\r\n");
                    fwrite($f, "plot  '$rf_datafile' using 2:4 with lines lt 1 lw 3 title 'FEIC Meas Pol0 LSB'");
                    // if statement to plot pol graphs
                    if     ($CCA_Data == 1){
                        fwrite($f, ",");
                        fwrite($f, "'$datafile_cart_0_2' using 1:2 with lines lt 3 title 'Cart Group Meas Pol0 LSB',");
                        fwrite($f, "'$datafile_diff_0_2' using 1:2 with points lt -1 axes x1y2 title 'Diff relative to Spec'\r\n");
                    } else {
                        fwrite($f, "\r\n");
                    }
                    fclose($f);

                    //Call gnuplot
                    system("$GNUPLOT $commandfile");
                    $this->NT_SubHeader->SetValue('ploturl2',$image_url);
                }
                break;

            case 2;
                if ($this->GetValue('Band') == 9 || $this->GetValue('Band') == 10){
                    $SB = "";
                } else {
                    $SB = "USB";
                }
                    $plot_title = "$plot_title Pol 1 $SB";
                    fwrite($f, "set title '$plot_title'\r\n");
                    fwrite($f, "plot  '$rf_datafile' using 1:5 with lines lt 1 lw 3 title 'FEIC Meas Pol1 $SB'");
                    // if statement to plot pol graphs
                    if     ($CCA_Data == 1){
                        fwrite($f, ",");
                        fwrite($f, "'$datafile_cart_1_1' using 1:2 with lines lt 3 title 'Cart Group Meas Pol1 $SB',");
                        fwrite($f, "'$datafile_diff_1_1' using 1:2 with points lt -1 axes x1y2 title 'Diff relative to Spec'\r\n");
                    } else {
                        fwrite($f, "\r\n");
                    }
                    fclose($f);

                    //Call gnuplot
                    system("$GNUPLOT $commandfile");
                if ($this->GetValue('Band') == 9 || $this->GetValue('Band') == 10){
                    $this->NT_SubHeader->SetValue('ploturl2',$image_url);
                } else {
                    $this->NT_SubHeader->SetValue('ploturl5',$image_url);
                }
                break;

            case 3;
                if ($this->GetValue('Band') != 9 && $this->GetValue('Band') != 10){
                    $plot_title = "$plot_title Pol 1 LSB";
                    fwrite($f, "set title '$plot_title'\r\n");
                    fwrite($f, "plot  '$rf_datafile' using 2:6 with lines lt 1 lw 3 title 'FEIC Meas Pol1 LSB'");
                    // if statement to plot pol graphs
                    if     ($CCA_Data == 1){
                        fwrite($f, ",");
                        fwrite($f, "'$datafile_cart_1_2' using 1:2 with lines lt 3 title 'Cart Group Meas Pol1 LSB',");
                        fwrite($f, "'$datafile_diff_1_2' using 1:2 with points lt -1 axes x1y2 title 'Diff relative to Spec'\r\n");
                    } else {
                        fwrite($f, "\r\n");
                    }
                    fclose($f);

                    //Call gnuplot
                    system("$GNUPLOT $commandfile");
                    $this->NT_SubHeader->SetValue('ploturl6',$image_url);

                }
                break;

            }
        }
        $this->NT_SubHeader->Update();  // save image locations to database
    }

    public function DisplayPlots(){
        echo "<img src= '" . $this->NT_SubHeader->GetValue('ploturl1') . "'><br><br>";
        echo "<img src= '" . $this->NT_SubHeader->GetValue('ploturl2') . "'><br><br>";
        echo "<img src= '" . $this->NT_SubHeader->GetValue('ploturl5') . "'><br><br>";
        echo "<img src= '" . $this->NT_SubHeader->GetValue('ploturl6') . "'><br><br>";
        echo "<img src= '" . $this->NT_SubHeader->GetValue('ploturl3') . "'><br><br>";
        if ($this->GetValue('Band') != 3){
            echo "<img src= '" . $this->NT_SubHeader->GetValue('ploturl4') . "'><br><br>";
        }else {
            Band3_NT_results($this->GetValue('keyId'));
            Band3_CCA_NT_results($this->GetValue('keyId'));
        }
    }
}
?>
