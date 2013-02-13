<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_classes . '/class.frontend.php');
require_once($site_classes . '/class.cca.php');
require_once($site_classes . '/class.testdata_component.php');
require_once($site_classes . '/class.logger.php');
require_once($site_dbConnect);


class DataPlotter extends GenericTable{
    var $writedirectory;
    var $url_directory;
    var $GNUPLOT_path;
    var $TestDataHeader;
    var $dbconnection;
    var $Component;
    var $swversion;

    var $measdate;
    var $FESN;
    var $CCASN;
    var $FEcfg;
    var $fc;  //facility id
    var $logfile;

    var $cca; //CCA object

    function __construct() {
        require(site_get_config_main());
        $this->writedirectory = $main_write_directory;
        $this->GNUPLOT_path = $GNUPLOT;
        $this->swversion = "1.0.28";
        $this->logfile = $this->writedirectory . "log_" . date("Y_m_d__H_i_s") . ".txt";
        $this->logging = 0;
   }

    public function Initialize_DataPlotter($in_TestDataHeaderID,$in_dbconnection,$in_fc){
        $this->fc = $in_fc;
        $this->dbconnection = site_getDbConnection();
        $this->TestDataHeader = new TestData_header();
        $this->TestDataHeader->Initialize_TestData_header($in_TestDataHeaderID,$in_fc);
        $this->FEcfg = $this->TestDataHeader->GetValue('fkFE_Config');
        if ($this->TestDataHeader->FrontEnd->keyId != ''){
            $this->FESN = $this->TestDataHeader->FrontEnd->GetValue('SN');
        }

        $band = '%';
        if ($this->TestDataHeader->GetValue('Band') > 0){
            $band = $this->TestDataHeader->GetValue('Band');
        }

        //Get CCA
        $q = "SELECT FE_Components.keyId
        FROM FE_Components, FE_ConfigLink
        WHERE FE_ConfigLink.fkFE_Config = $this->FEcfg
        AND FE_Components.fkFE_ComponentType = 20
        AND FE_Components.Band LIKE '$band'
        AND FE_ConfigLink.fkFE_Components = FE_Components.keyId;";


        $r = @mysql_query($q,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);

        $this->CCASN = "N/A";
        if (@mysql_numrows($r) > 0){
            $this->cca = new CCA();
            $this->cca->Initialize_CCA(@mysql_result($r,0,0), $this->fc);
            $this->CCASN = $this->cca->GetValue('SN');
        }
        $this->measdate = $this->TestDataHeader->GetValue('TS');
        //Get meas date
        switch ($this->TestDataHeader->GetValue('fkTestData_Type')){
            case 7:
            //IF Spectrum
                $q = "SELECT TS FROM IFSpectrum_SubHeader
                WHERE fkHeader = " . $this->TestDataHeader->keyId . ";";
                $r = @mysql_query($q,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);
                $this->measdate = @mysql_result($r,0,0);
                break;
        }

        if ($this->FESN == ""){
            //Get Front End SN and
            $qfe = "SELECT Front_Ends.SN, FE_Config.keyFEConfig, IFSpectrum_SubHeader.TS
                    FROM TestData_header, Front_Ends,FE_Config,IFSpectrum_SubHeader
                    WHERE TestData_header.keyId = $in_TestDataHeaderID
                    AND FE_Config.keyFEConfig = TestData_header.fkFE_Config
                    AND Front_Ends.keyFrontEnds = FE_Config.fkFront_Ends
                    AND IFSpectrum_SubHeader.fkHeader = $in_TestDataHeaderID
                    AND Front_Ends.keyFacility = $this->fc
                    AND TestData_header.keyFacility = $this->fc
                    AND FE_Config.keyFacility = $this->fc
                    AND IFSpectrum_SubHeader.fkFacility = $this->fc
                    ;";
            $rfe = @mysql_query($qfe,$this->dbconnection);//  or die('Failed on query in dataplotter.php line ' . __LINE__);
            $this->measdate = @mysql_result($rfe,0,2);
        }

        if ($this->FESN == ""){
            //Get Front End SN and
            $qfe = "SELECT Front_Ends.SN, FE_Config.keyFEConfig, TestData_header.TS
                    FROM TestData_header, Front_Ends,FE_Config
                    WHERE TestData_header.keyId = $in_TestDataHeaderID
                    AND FE_Config.keyFEConfig = TestData_header.fkFE_Config
                    AND Front_Ends.keyFrontEnds = FE_Config.fkFront_Ends
                    AND Front_Ends.keyFacility = $this->fc
                    AND TestData_header.keyFacility = $this->fc
                    AND FE_Config.keyFacility = $this->fc;";
            $rfe = @mysql_query($qfe,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);
            $this->measdate = @mysql_result($rfe,0,2);
        }



    }

    public function Plot_CCA_AmplitudeStability(){
        require(site_get_config_main());
        $this->writedirectory = $cca_write_directory;
        $this->url_directory = $cca_url_directory;
        $TestData_Id = $this->TestDataHeader->keyId;
        $tdhfc = $this->TestDataHeader->GetValue('keyFacility');

        //Initialize component object
        $this->Component = new FEComponent();
        $this->Component->Initialize_FEComponent($this->TestDataHeader->GetValue('fkFE_Components'),$tdhfc);

        //write data file from database
        $qFindLO = "SELECT DISTINCT(FreqLO) FROM CCA_TEST_AmplitudeStability
                    WHERE fkHeader = $TestData_Id
                    AND fkFacility = $tdhfc
                    ORDER BY FreqLO ASC;";
        $rFindLO = @mysql_query($qFindLO,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);
        $rowLO=@mysql_fetch_array($rFindLO);

        $datafile_count=0;
        $spec_value = 0.0000001;

        for ($j=0;$j<=1;$j++){
            for ($i=0;$i<=sizeof($rowLO);$i++){
                $CurrentLO = @mysql_result($rFindLO,$i);
                $DataSeriesName = "LO $CurrentLO GHz, Pol $j";

                $q = "SELECT Time,AllanVar FROM CCA_TEST_AmplitudeStability
                    WHERE FreqLO = $CurrentLO
                    AND Pol = $j
                    AND fkHeader = $TestData_Id
                    AND fkFacility = $tdhfc
                    ORDER BY Time ASC;";
                $r = @mysql_query($q,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);

                if (@mysql_num_rows($r) > 1){
                    $plottitle[$datafile_count] = "Pol $j, $CurrentLO GHz";
                    $data_file[$datafile_count] = $this->writedirectory . "cca_as_data_".$i."_".$j.".txt";
                    $fh = fopen($data_file[$datafile_count], 'w');
                    $row=@mysql_fetch_array($r);
                    $TimeVal = $row[0];

                    if ($TimeVal > 500){
                        fwrite($fh, "$row[0]\t0.00000009\r\n");
                    }
                        while($row=@mysql_fetch_array($r)){
                            $stringData = "$row[0]\t$row[1]\r\n";
                            fwrite($fh, $stringData);
                        }
                    fclose($fh);
                    $datafile_count++;
                }
            }//end for i
        }//end for j

        //Write command file for gnuplot
        $plot_command_file = $this->writedirectory . "cca_as_command.txt";
        $imagedirectory .= $this->writedirectory . $this->Component->GetValue('Band') . "_" . $this->Component->GetValue('SN') . "/";
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagename = "CCA_AmplitudeStability_SN" . $this->Component->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";
        $image_url = $this->url_directory . $this->Component->GetValue('Band') . "_" . $this->Component->GetValue('SN') . "/$imagename";
        $plot_title = "CCA Band" . $this->Component->GetValue('Band') . " SN" . $this->Component->GetValue('SN') . " Amplitude Stability ($TS)";

        //Update plot url
        $this->TestDataHeader->SetValue('PlotURL',$image_url);
        $this->TestDataHeader->Update();

        $imagepath = $imagedirectory . $imagename;
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 900,500\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set log xy\r\n");
        fwrite($fh, "set key outside\r\n");
        fwrite($fh, "set ylabel 'Allan Variance'\r\n");
        fwrite($fh, "set xlabel 'Allan Time, T (=Integration, Tau)'\r\n");
        $ymax = pow(10,-5);
        fwrite($fh, "set yrange [:$ymax]\r\n");
        fwrite($fh, "f1(x)=((x>500) && (x<100000)) ? 0.00000009 : 1/0\r\n");
        fwrite($fh, "f2(x)=((x>299999) && (x<350000)) ? 0.000001 : 1/0\r\n");
        $plot_string = "plot f1(x) title 'Spec' with lines lw 3";
        $plot_string .= ", f2(x) title 'Spec' with points pt 5 pointsize 1";
        $plot_string .= ", '$data_file[0]' using 1:2 title '$plottitle[0]' with lines";
        for ($i=1;$i<sizeof($data_file);$i++){
            $plot_string .= ", '$data_file[$i]' using 1:2 title '$plottitle[$i]' with lines";
        }
        $plot_string .= "\r\n";
        fwrite($fh, $plot_string);
        fclose($fh);
        //Make the plot

        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
    }
    public function Plot_CCA_PhaseDrift(){
        require(site_get_config_main());
        $this->writedirectory = $cca_write_directory;
        $this->url_directory = $cca_url_directory;
        $TestData_Id = $this->TestDataHeader->keyId;

        //Initialize component object
        $this->Component = new FEComponent();
        $this->Component->Initialize_FEComponent($this->TestDataHeader->GetValue('fkFE_Components'),$this->TestDataHeader->GetValue('keyFacility'));


        $qlo = "SELECT DISTINCT(FreqLO), Pol FROM CCA_TEST_PhaseDrift
              WHERE fkHeader = $TestData_Id
              ORDER BY FreqLO ASC;";
        $rlo = @mysql_query($qlo, $this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);

        $loindex=0;

        $qTS = "SELECT TS FROM CCA_TEST_PhaseDrift
                    WHERE fkHeader = $TestData_Id
                    AND TS <> ''
                    LIMIT 1;";
        $rTS = @mysql_query($qTS,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);
        $TS = @mysql_result($rTS,0);

        //write data file from database
        $qFindLO = "SELECT DISTINCT(FreqLO) FROM CCA_TEST_PhaseDrift
                    WHERE fkHeader = $TestData_Id
                    ORDER BY FreqLO ASC;";
        $rFindLO = @mysql_query($qFindLO,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);
        $rowLO=@mysql_fetch_array($rFindLO);

        $datafile_count=0;
        for ($j=0;$j<=1;$j++){
            for ($i=0;$i<=sizeof($rowLO);$i++){
                $CurrentLO = @mysql_result($rFindLO,$i);
                $DataSeriesName = "LO $CurrentLO GHz, Pol $j";

                $q = "SELECT FreqCarrier,AllanPhase FROM CCA_TEST_PhaseDrift
                    WHERE FreqLO = $CurrentLO
                    AND Pol = $j
                    AND fkHeader = $TestData_Id
                    ORDER BY FreqCarrier ASC;";
                $r = @mysql_query($q,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);

                if (@mysql_num_rows($r) > 1){
                    $plottitle[$datafile_count] = "Pol $j, $CurrentLO GHz";
                    $data_file[$datafile_count] = $this->writedirectory . "cca_phasenz_".$i."_".$j.".txt";
                    $fh = fopen($data_file[$datafile_count], 'w');
                    $row=@mysql_fetch_array($r);

                    while($row=@mysql_fetch_array($r)){
                        $stringData = "$row[0]\t$row[1]\r\n";
                        fwrite($fh, $stringData);
                    }
                    fclose($fh);
                    $datafile_count++;
                }
            }//end for i
        }//end for j

        $imagedirectory = $this->writedirectory . $this->Component->GetValue('Band') . "_" . $this->Component->GetValue('SN') . "/";
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagename = "CCA_PhaseNoise_SN" . $this->Component->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";
        $image_url = $this->url_directory . $this->Component->GetValue('Band') . "_" . $this->Component->GetValue('SN') . "/$imagename";
        //Update plot url
        $this->TestDataHeader->SetValue('PlotURL',$image_url);
        $this->TestDataHeader->Update();


        $plot_title = "WCA Band" . $this->Component->GetValue('Band') . " SN" . $this->Component->GetValue('SN') . " Phase Noise ($TS)";
        $imagepath = $imagedirectory . $imagename;

        //Write command file for gnuplot
        $plot_command_file = $this->writedirectory . "cca_pn_command.txt";
        //unlink($plot_command_file) or die("cca pn command file not found");
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 900,500\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set log x\r\n");
        //fwrite($fh, "set yrange [-140:-40]\r\n");
        //fwrite($fh, "set xrange [10:10000000]\r\n");

        fwrite($fh, "set xlabel 'f (Hz)'\r\n");
        fwrite($fh, "set ylabel 'L(f) [dBc/Hz]'\r\n");
        fwrite($fh, "set key outside\r\n");
        $plot_string = "plot '$data_file[0]' using 1:2 title '$plottitle[0]' with lines";
        for ($i=1;$i<sizeof($data_file);$i++){
            $plot_string .= ", '$data_file[$i]' using 1:2 title '$plottitle[$i]' with lines";
        }
        $plot_string .= "\r\n";
        fwrite($fh, $plot_string);
        fclose($fh);

        //Make the plot

        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);


    }
    public function Plot_CCA_InBandPower(){
        require(site_get_config_main());
        $this->writedirectory = $cca_write_directory;
        $this->url_directory = $cca_url_directory;
        $TestData_Id = $this->TestDataHeader->keyId;

        //Initialize component object
        $this->Component = new FEComponent();

        $this->Component->Initialize_FEComponent($this->TestDataHeader->GetValue('fkFE_Components'),$this->TestDataHeader->GetValue('keyFacility'));

        $datafile_count=0;

        //Pol 0 and 1
        for ($pol=0;$pol<=1;$pol++){
            //SB 0, 1 or 2
            for ($sb=0;$sb<=2;$sb++){
                $DataSeriesName = "Pol $pol SB $sb";

                $q = "SELECT FreqLO,Power FROM CCA_TEST_InBandPower
                    WHERE Pol = $pol
                    AND SB = $sb
                    AND fkHeader = $TestData_Id
                    ORDER BY FreqLO ASC;";
                //echo "q3 = $q<br>";
                $r = @mysql_query($q,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);

                if (@mysql_num_rows($r) > 1){
                    $plottitle[$datafile_count] = "Pol $pol, SB $sb";
                    //$data_file[$datafile_count] = "/export/home/teller/vhosts/safe.nrao.edu/active/php/ntc/cca_datafiles/cca_as_data_".$i."_".$j.".txt";
                    $data_file[$datafile_count] = $this->writedirectory . "cca_ibp_data_".$pol."_".$sb.".txt";

                    //unlink($data_file[$datafile_count]);
                    $fh = fopen($data_file[$datafile_count], 'w');
                    $row=@mysql_fetch_array($r);
                        while($row=@mysql_fetch_array($r)){
                            $stringData = "$row[0]\t$row[1]\r\n";
                            fwrite($fh, $stringData);
                        }
                    fclose($fh);
                    $datafile_count++;
                }
            }//end for i
        }//end for j

        //Write command file for gnuplot
        $plot_command_file = $this->writedirectory . "cca_ibp_command.txt";
        $imagedirectory .= $this->writedirectory . $this->Component->GetValue('Band') . "_" . $this->Component->GetValue('SN') . "/";
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagename = "CCA_InBandPower_SN" . $this->Component->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";
        $image_url = $this->url_directory . $this->Component->GetValue('Band') . "_" . $this->Component->GetValue('SN') . "/$imagename";
        $plot_title = "CCA Band" . $this->Component->GetValue('Band') . " SN" . $this->Component->GetValue('SN') . " In-Band Power ($TS)";

        //Update plot url
        $this->TestDataHeader->SetValue('PlotURL',$image_url);
        $this->TestDataHeader->Update();

        $imagepath = $imagedirectory . $imagename;
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 900,500\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set key outside\r\n");
        fwrite($fh, "set ylabel 'Power dBm'\r\n");
        fwrite($fh, "set xlabel 'Frequency LO (GHz)'\r\n");
        fwrite($fh, "set pointsize 2\r\n");

        $plot_string = "plot '$data_file[0]' using 1:2 title '$plottitle[0]' with points";
        for ($i=1;$i<sizeof($data_file);$i++){
            $plot_string .= ", '$data_file[$i]' using 1:2 title '$plottitle[$i]' with points";
        }
        $plot_string .= "\r\n";
        fwrite($fh, $plot_string);
        fclose($fh);
        //Make the plot
        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
    }
    public function Plot_CCA_TotalPower(){
        require(site_get_config_main());
        $this->writedirectory = $cca_write_directory;
        $this->url_directory = $cca_url_directory;
        $TestData_Id = $this->TestDataHeader->keyId;

        //Initialize component object
        $this->Component = new TestData_Component();
        $this->Component->Initialize_TestData_Component($this->TestDataHeader->GetValue('fkFE_Components'),$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);

        $datafile_count=0;

        //Pol 0 and 1
        for ($pol=0;$pol<=1;$pol++){
            //SB 0, 1 or 2
            for ($sb=0;$sb<=2;$sb++){
                $DataSeriesName = "Pol $pol SB $sb";

                $q = "SELECT FreqLO,Power FROM CCA_TEST_TotalPower
                    WHERE Pol = $pol
                    AND SB = $sb
                    AND fkHeader = $TestData_Id
                    ORDER BY FreqLO ASC;";
                //echo "q3 = $q<br>";
                $r = @mysql_query($q,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);

                if (@mysql_num_rows($r) > 1){
                    $plottitle[$datafile_count] = "Pol $pol, SB $sb";
                    //$data_file[$datafile_count] = "/export/home/teller/vhosts/safe.nrao.edu/active/php/ntc/cca_datafiles/cca_as_data_".$i."_".$j.".txt";
                    $data_file[$datafile_count] = $this->writedirectory . "cca_tp_data_".$pol."_".$sb.".txt";

                    //unlink($data_file[$datafile_count]);
                    $fh = fopen($data_file[$datafile_count], 'w');
                    $row=@mysql_fetch_array($r);
                        while($row=@mysql_fetch_array($r)){
                            $stringData = "$row[0]\t$row[1]\r\n";
                            fwrite($fh, $stringData);
                        }
                    fclose($fh);
                    $datafile_count++;
                }
            }//end for i
        }//end for j

        //Write command file for gnuplot
        $plot_command_file = $this->writedirectory . "cca_tp_command.txt";
        $imagedirectory .= $this->writedirectory . $this->Component->GetValue('Band') . "_" . $this->Component->GetValue('SN') . "/";
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagename = "CCA_TotalPower_SN" . $this->Component->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";
        $image_url = $this->url_directory . $this->Component->GetValue('Band') . "_" . $this->Component->GetValue('SN') . "/$imagename";
        $plot_title = "CCA Band" . $this->Component->GetValue('Band') . " SN" . $this->Component->GetValue('SN') . " Total Power ($TS)";

        //Update plot url
        $this->TestDataHeader->SetValue('PlotURL',$image_url);
        $this->TestDataHeader->Update();

        $imagepath = $imagedirectory . $imagename;
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 900,500\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set key outside\r\n");
        fwrite($fh, "set ylabel 'Power dBm'\r\n");
        fwrite($fh, "set xlabel 'Frequency LO (GHz)'\r\n");
        fwrite($fh, "set pointsize 2\r\n");

        $plot_string = "plot '$data_file[0]' using 1:2 title '$plottitle[0]' with points";
        for ($i=1;$i<sizeof($data_file);$i++){
            $plot_string .= ", '$data_file[$i]' using 1:2 title '$plottitle[$i]' with points";
        }
        $plot_string .= "\r\n";
        fwrite($fh, $plot_string);
        fclose($fh);
        //Make the plot

        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
    }
    public function Plot_CCA_GainCompression(){
        require(site_get_config_main());
        $this->writedirectory = $cca_write_directory;
        $this->url_directory = $cca_url_directory;
        $TestData_Id = $this->TestDataHeader->keyId;

        //Initialize component object
        $this->Component = new TestData_Component();
        $this->Component->Initialize_TestData_Component($this->TestDataHeader->GetValue('fkFE_Components'),$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);

        $datafile_count=0;

        //Pol 0 and 1
        for ($pol=0;$pol<=1;$pol++){
            //SB 0, 1 or 2
            for ($sb=0;$sb<=2;$sb++){
                $DataSeriesName = "Pol $pol SB $sb";

                $q = "SELECT FreqLO,Compression FROM CCA_TEST_GainCompression
                    WHERE Pol = $pol
                    AND SB = $sb
                    AND fkHeader = $TestData_Id
                    ORDER BY FreqLO ASC;";
                //echo "q3 = $q<br>";
                $r = @mysql_query($q,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);

                if (@mysql_num_rows($r) > 1){
                    $plottitle[$datafile_count] = "Pol $pol, SB $sb";
                    //$data_file[$datafile_count] = "/export/home/teller/vhosts/safe.nrao.edu/active/php/ntc/cca_datafiles/cca_as_data_".$i."_".$j.".txt";
                    $data_file[$datafile_count] = $this->writedirectory . "cca_gc_data_".$pol."_".$sb.".txt";

                    //unlink($data_file[$datafile_count]);
                    $fh = fopen($data_file[$datafile_count], 'w');
                    $row=@mysql_fetch_array($r);
                        while($row=@mysql_fetch_array($r)){
                            $stringData = "$row[0]\t$row[1]\r\n";
                            fwrite($fh, $stringData);
                        }
                    fclose($fh);
                    $datafile_count++;
                }
            }//end for i
        }//end for j

        //Write command file for gnuplot
        $plot_command_file = $this->writedirectory . "cca_gc_command.txt";
        $imagedirectory .= $this->writedirectory . $this->Component->GetValue('Band') . "_" . $this->Component->GetValue('SN') . "/";
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagename = "CCA_GainCompression_SN" . $this->Component->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";
        $image_url = $this->url_directory . $this->Component->GetValue('Band') . "_" . $this->Component->GetValue('SN') . "/$imagename";
        $plot_title = "CCA Band" . $this->Component->GetValue('Band') . " SN" . $this->Component->GetValue('SN') . " Gain Compression ($TS)";

        //Update plot url
        $this->TestDataHeader->SetValue('PlotURL',$image_url);
        $this->TestDataHeader->Update();

        $imagepath = $imagedirectory . $imagename;
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 900,500\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set key outside\r\n");
        fwrite($fh, "set ylabel 'Compression %'\r\n");
        fwrite($fh, "set xlabel 'Frequency LO (GHz)'\r\n");


        $plot_string = "plot '$data_file[0]' using 1:2 title '$plottitle[0]' with points";
        for ($i=1;$i<sizeof($data_file);$i++){
            $plot_string .= ", '$data_file[$i]' using 1:2 title '$plottitle[$i]' with points";
        }
        $plot_string .= "\r\n";
        fwrite($fh, $plot_string);
        fclose($fh);
        //Make the plot

        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
    }
    public function Plot_CCA_IFSpectrum(){
        require(site_get_config_main());
        $this->writedirectory = $cca_write_directory;
        $this->url_directory = $cca_url_directory;
        $TestData_Id = $this->TestDataHeader->keyId;

        //Initialize component object
        $this->Component = new TestData_Component();
        $this->Component->Initialize_TestData_Component($this->TestDataHeader->GetValue('fkFE_Components'),$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);

        $datafile_count=0;

        //Pol 0 and 1
        for ($pol=0;$pol<=1;$pol++){
            //SB 0, 1 or 2
            for ($sb=0;$sb<=2;$sb++){
                $DataSeriesName = "Pol $pol SB $sb";

                $q = "SELECT CenterIF,Power FROM CCA_TEST_IFSpectrum
                    WHERE Pol = $pol
                    AND SB = $sb
                    AND fkHeader = $TestData_Id
                    ORDER BY FreqLO ASC;";
                //echo "q3 = $q<br>";
                $r = @mysql_query($q,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);

                if (@mysql_num_rows($r) > 1){
                    $plottitle[$datafile_count] = "Pol $pol, SB $sb";
                    //$data_file[$datafile_count] = "/export/home/teller/vhosts/safe.nrao.edu/active/php/ntc/cca_datafiles/cca_as_data_".$i."_".$j.".txt";
                    $data_file[$datafile_count] = $this->writedirectory . "cca_ifs_data_".$pol."_".$sb.".txt";

                    //unlink($data_file[$datafile_count]);
                    $fh = fopen($data_file[$datafile_count], 'w');
                    $row=@mysql_fetch_array($r);
                        while($row=@mysql_fetch_array($r)){
                            $stringData = "$row[0]\t$row[1]\r\n";
                            fwrite($fh, $stringData);
                        }
                    fclose($fh);
                    $datafile_count++;
                }
            }//end for i
        }//end for j

        //Write command file for gnuplot
        $plot_command_file = $this->writedirectory . "cca_ifs_command.txt";
        $imagedirectory .= $this->writedirectory . $this->Component->GetValue('Band') . "_" . $this->Component->GetValue('SN') . "/";
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagename = "CCA_IFSpectrum_SN" . $this->Component->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";
        $image_url = $this->url_directory . $this->Component->GetValue('Band') . "_" . $this->Component->GetValue('SN') . "/$imagename";
        $plot_title = "CCA Band" . $this->Component->GetValue('Band') . " SN" . $this->Component->GetValue('SN') . " IF Spectrum ($TS)";

        //Update plot url
        $this->TestDataHeader->SetValue('PlotURL',$image_url);
        $this->TestDataHeader->Update();

        $imagepath = $imagedirectory . $imagename;
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 900,500\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set key outside\r\n");
        fwrite($fh, "set ylabel 'Power dBm'\r\n");
        fwrite($fh, "set xlabel 'IF Frequency (GHz)'\r\n");
        fwrite($fh, "set pointsize 2\r\n");

        $plot_string = "plot '$data_file[0]' using 1:2 title '$plottitle[0]' with points";
        for ($i=1;$i<sizeof($data_file);$i++){
            $plot_string .= ", '$data_file[$i]' using 1:2 title '$plottitle[$i]' with points";
        }
        $plot_string .= "\r\n";
        fwrite($fh, $plot_string);
        fclose($fh);
        //Make the plot

        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
    }
    public function Plot_CCA_PolAccuracy(){
        require(site_get_config_main());
        $this->writedirectory = $cca_write_directory;
        $this->url_directory = $cca_url_directory;
        $TestData_Id = $this->TestDataHeader->keyId;

        //Initialize component object
        $this->Component = new FEComponent();
        $this->Component->Initialize_FEComponent($this->TestDataHeader->GetValue('fkFE_Components'),$this->TestDataHeader->GetValue('keyFacility'));
        $datafile_count=0;
        //Pol 0 and 1
        for ($pol=0;$pol<=1;$pol++){
            //SB 0, 1 or 2
                $DataSeriesName = "Pol $pol";

                $q = "SELECT FreqLO,AngleError FROM CCA_TEST_PolAccuracy
                    WHERE Pol = $pol
                    AND fkHeader = $TestData_Id
                    ORDER BY FreqLO ASC;";
                //echo "q3 = $q<br>";
                $r = @mysql_query($q,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);

                if (@mysql_num_rows($r) > 1){
                    $plottitle[$datafile_count] = "Pol $pol";
                    //$data_file[$datafile_count] = "/export/home/teller/vhosts/safe.nrao.edu/active/php/ntc/cca_datafiles/cca_as_data_".$i."_".$j.".txt";
                    $data_file[$datafile_count] = $this->writedirectory . "cca_polacc_data_".$pol ."txt";

                    //unlink($data_file[$datafile_count]);
                    $fh = fopen($data_file[$datafile_count], 'w');
                    $row=@mysql_fetch_array($r);
                        while($row=@mysql_fetch_array($r)){
                            $stringData = "$row[0]\t$row[1]\r\n";
                            fwrite($fh, $stringData);
                        }
                    fclose($fh);
                    $datafile_count++;
                }
        }//end for pol

        //Write command file for gnuplot
        $plot_command_file = $this->writedirectory . "cca_polacc_command.txt";
        $imagedirectory .= $this->writedirectory . $this->Component->GetValue('Band') . "_" . $this->Component->GetValue('SN') . "/";
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagename = "CCA_PolAccuracy_SN" . $this->Component->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";
        $image_url = $this->url_directory . $this->Component->GetValue('Band') . "_" . $this->Component->GetValue('SN') . "/$imagename";
        $plot_title = "CCA Band" . $this->Component->GetValue('Band') . " SN" . $this->Component->GetValue('SN') . " Polarization Accuracy ($TS)";

        //Update plot url
        $this->TestDataHeader->SetValue('PlotURL',$image_url);
        $this->TestDataHeader->Update();

        $imagepath = $imagedirectory . $imagename;
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 900,500\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set key outside\r\n");
        fwrite($fh, "set ylabel 'Angle Error Degrees'\r\n");
        fwrite($fh, "set xlabel 'Frequency LO (GHz)'\r\n");
        fwrite($fh, "set pointsize 2\r\n");

        $plot_string = "plot '$data_file[0]' using 1:2 title '$plottitle[0]' with points";
        for ($i=1;$i<sizeof($data_file);$i++){
            $plot_string .= ", '$data_file[$i]' using 1:2 title '$plottitle[$i]' with points";
        }
        $plot_string .= "\r\n";
        fwrite($fh, $plot_string);
        fclose($fh);
        //Make the plot

        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
    }

    //New version, generate a plot for each LO frequency
    public function Plot_CCA_IVCurve(){
        require(site_get_config_main());
        $this->writedirectory = $cca_write_directory;
        $this->url_directory = $cca_url_directory;
        $TestData_Id = $this->TestDataHeader->keyId;

        //Initialize component object
        //$this->Component = new TestData_Component();
        //$this->Component->Initialize_TestData_Component($this->TestDataHeader->GetValue('fkFE_Components'),$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);
        $TS = $this->TestDataHeader->GetValue('TS');
        if ($this->TestDataHeader->GetValue('fkFE_Components') > 0){
            $c = new FEComponent();
            $c->Initialize_FEComponent($this->TestDataHeader->GetValue('fkFE_Components'),$this->TestDataHeader->GetValue('keyFacility'));
            $sn = $c->GetValue('SN');
            unset($c);
        }
        if ($this->TestDataHeader->GetValue('fkFE_Config') > 0){
            $fe = new FrontEnd();
            $fe->Initialize_FrontEnd_FromConfig($this->TestDataHeader->GetValue('fkFE_Config'),$this->TestDataHeader->GetValue('keyFacility'));
            $sn = $fe->GetValue('SN');
            unset($fe);
        }

        $band = $this->TestDataHeader->GetValue('Band');

        $imagedirectory = $this->writedirectory . $band . "_" . $sn . "/";
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }

        $FreqLOarr[0] = " = 0";
        $FreqLOarr[1] = " <> 0";

        $l = New Logger('ivcurve.txt');
        $l->WriteLogFile('test...');

        $qlo = "SELECT FreqLO FROM CCA_TEST_IVCurve
            WHERE fkFacility = ".$this->TestDataHeader->GetValue('keyFacility')."
            AND fkHeader = $TestData_Id
            GROUP BY FreqLO ASC";
        $l->WriteLogFile($qlo);
        $rlo = @mysql_query($qlo,$this->dbconnection);

        while($rowlo = @mysql_fetch_array($rlo)){
            $CurrentLO = $rowlo[0];
            for ($pol=0;$pol<=1;$pol++){
                for ($sb=1;$sb<=2;$sb++){

                    $DataSeriesName = "Pol $pol SB $sb IJ";

                    $q = "SELECT VJ,IJ FROM CCA_TEST_IVCurve
                    	WHERE FreqLO = $CurrentLO
                        AND Pol = $pol
                        AND SB = $sb
                        AND fkHeader = $TestData_Id
                        ORDER BY FreqLO ASC;";
                    $l->WriteLogFile($q);
                    $r = @mysql_query($q,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);

                    if (@mysql_num_rows($r) > 1){
                        $plottitle = "$DataSeriesName";
                        //$data_file[$datafile_count] = "/export/home/teller/vhosts/safe.nrao.edu/active/php/ntc/cca_datafiles/cca_as_data_".$i."_".$j.".txt";
                        $data_file = $imagedirectory . "cca_ivc_data_".$pol . "_" . $sb . "_" . $i . $this->udate("Ymd_G_i_s_u") . ".txt";

                        $l->WriteLogFile("data file= $data_file");
                        //unlink($data_file[$datafile_count]);
                        $fh = fopen($data_file, 'w');
                        $row=@mysql_fetch_array($r);
                        while($row=@mysql_fetch_array($r)){
                            $stringData = "$row[0]\t$row[1]\r\n";
                            fwrite($fh, $stringData);
                        }
                        fclose($fh);


                        //Write command file for gnuplot
                        $plot_command_file = $imagedirectory . "cca_ivc_command.txt";
                        $l->WriteLogFile("plot_command_file= $plot_command_file");

                        $imagename = "CCA_IVCurve_SN$sn_" . $this->udate("Ymd_G_i_s_u") . ".png";
                        if ($image_url != ''){
                            $image_url .= "," . $this->url_directory . $band . "_" . $sn . "/$imagename";
                        }
                        if ($image_url == ''){
                            $image_url = $this->url_directory . $band . "_" . $sn . "/$imagename";
                        }

                        $plot_title = "IV Curve, $CurrentLO GHz, CCA$band-$this->CCASN";
                        $plot_label_1 =" set label 'TestData_header.keyId: ".$this->TestDataHeader->keyId.", Plot SWVer: $this->swversion, Meas SWVer: ".$this->TestDataHeader->GetValue('Meas_SWVer')."' at screen 0, screen 0.01\r\n";
                        $plot_label_2 ="set label '".$this->TestDataHeader->GetValue('TS').", FE Configuration ".$this->TestDataHeader->GetValue('fkFE_Config')."' at screen 0, screen 0.04\r\n";

                        $imagepath = $imagedirectory . $imagename;
                        $fh = fopen($plot_command_file, 'w');
                        fwrite($fh, "set terminal png size 900,500\r\n");
                        fwrite($fh, "set output '$imagepath'\r\n");
                        fwrite($fh, "set title '$plot_title'\r\n");
                        fwrite($fh, "set grid\r\n");
                        fwrite($fh, "set key outside\r\n");
                        fwrite($fh, "set ylabel 'IJ uA'\r\n");
                        fwrite($fh, "set xlabel 'VJ mV'\r\n");
                        fwrite($fh, "set pointsize 0.4\r\n");
                        fwrite($fh, "set bmargin 6\r\n");
                        fwrite($fh, $plot_label_1);
                        fwrite($fh, $plot_label_2);

                        $plot_string = "plot '$data_file' using 1:2 title '$plottitle' with points\r\n";
                        fwrite($fh, $plot_string);
                        fclose($fh);
                        //Make the plot

                        $CommandString = "$GNUPLOT $plot_command_file";
                        system($CommandString);

                    }// end if (@mysql_num_rows($r) > 1)
                }//end for sb
            }//end for pol
        }

        //Update plot url
        $this->TestDataHeader->SetValue('PlotURL',$image_url);
        $this->TestDataHeader->Update();

        $l->WriteLogfile($image_url);
    }




    public  function udate($format, $utimestamp = null)
    {
        if (is_null($utimestamp))
            $utimestamp = microtime(true);

        $timestamp = floor($utimestamp);
        $milliseconds = round(($utimestamp - $timestamp) * 1000000);

        return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
    }

    public function Plot_Repeatability(){
        require(site_get_config_main());
        $this->writedirectory = $main_write_directory  . "FE_" . $this->TestDataHeader->Component->GetValue('SN') . "/";
        $this->writedirectory = $main_write_directory;
        if (!file_exists($this->writedirectory)){
            mkdir($this->writedirectory);
        }

        $this->url_directory = $main_url_directory  . "FE_" . $this->TestDataHeader->Component->GetValue('SN') . "/";
        $this->url_directory = $main_url_directory;
        if (!file_exists($this->url_directory)){
            mkdir($this->url_directory);
        }
        $TestData_Id = $this->TestDataHeader->keyId;

        //Initialize component object
        //$this->Component = new GenericTable();
        //$this->Component->Initialize('Front_Ends',$this->TestDataHeader->GetValue('fe_keyId'),'keyFrontEnds',$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);

        //$this->TestDataHeader->Component

        $datafile_count=0;

        $dataarr[0]="tilt";
        $dataarr[1]="amplitude";
        $dataarr[2]="phase";
        $dataarr[3]="pll_locked";



            //SB 0, 1 or 2
            for ($i=0;$i<=4;$i++){
                $DataSeriesName = "dataarr[$i]";

                $q = "SELECT TimeValue,$dataarr[$i] FROM TEST_Repeatability
                    WHERE fkHeader = $TestData_Id
                    ORDER BY TimeValue ASC;";

                echo $q . "<br>";
                //echo "q3 = $q<br>";
                $r = @mysql_query($q,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);

                if (@mysql_num_rows($r) > 1){
                    $plottitle[$datafile_count] = "$dataarr[$i]";
                    //$data_file[$datafile_count] = "/export/home/teller/vhosts/safe.nrao.edu/active/php/ntc/cca_datafiles/cca_as_data_".$i."_".$j.".txt";
                    $data_file[$datafile_count] = $this->writedirectory . "rep_data_" . $dataarr[$i] . ".txt";
                    echo "data file= $data_file[$datafile_count]<br>";
                    //unlink($data_file[$datafile_count]);
                    $fh = fopen($data_file[$datafile_count], 'w');
                    $row=@mysql_fetch_array($r);
                    $timeval = 0;
                        while($row=@mysql_fetch_array($r)){
                            $stringData = "$timeval\t$row[1]\r\n";
                            fwrite($fh, $stringData);
                            $timeval += 30;
                        }
                    fclose($fh);
                    $datafile_count++;
                }
            }//end for i


        //Write command file for gnuplot
        $plot_command_file = $this->writedirectory . "rep_command_" . date("Ymd_G_i_s") . ".txt";
        $imagedirectory .= $this->writedirectory . "FE_" . $this->TestDataHeader->Component->GetValue('SN') . "/";
        //$imagedirectory .= $this->writedirectory;

        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagename = "Repeatability" . date("Ymd_G_i_s") . ".png";
        $image_url = $this->url_directory . "FE_" . $this->TestDataHeader->Component->GetValue('SN') . "/$imagename";
        $plot_title = "Repeatability, FE SN" . $this->TestDataHeader->Component->GetValue('SN');

        //Update plot url
        $this->TestDataHeader->SetValue('PlotURL',$image_url);
        $this->TestDataHeader->Update();

        $imagepath = $imagedirectory . $imagename;
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 900,500\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set key outside\r\n");
        //fwrite($fh, "set ylabel 'Power dBm'\r\n");
        fwrite($fh, "set xlabel 'Time'\r\n");
        fwrite($fh, "set pointsize 2\r\n");

        fwrite($fh, "set yrange[58:62]\r\n");
        fwrite($fh, "set ylabel 'Tilt Angle (deg)'\r\n");
        fwrite($fh, "set y2range[-70:0]\r\n");
        fwrite($fh, "set y2tics\r\n");
        fwrite($fh, "set y2label 'Amplitude (dBm)'\r\n");
        //fwrite($fh, "set y3range[-181:181]\r\n");
        //fwrite($fh, "set y4range[-1:2]\r\n");


        $plot_string = "plot '$data_file[0]' using 1:2 title '$plottitle[0]' with lines";
        $plot_string .= ", '$data_file[1]' using 1:2 title '$plottitle[$i]' with lines";

        for ($i=1;$i<sizeof($data_file)-2;$i++){
            //$plot_string .= ", '$data_file[$i]' using 1:2 title '$plottitle[$i]' with lines";
        }
        $plot_string .= "\r\n";
        fwrite($fh, $plot_string);
        fclose($fh);
        //Make the plot

        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
    }

    public function Plot_WorkmanshipAmplitude(){
        require(site_get_config_main());
        $this->writedirectory = $main_write_directory  . "FE_" . $this->TestDataHeader->Component->GetValue('SN') . "/";
        $this->writedirectory = $main_write_directory;
        if (!file_exists($this->writedirectory)){
            mkdir($this->writedirectory);
        }

        $this->url_directory = $main_url_directory  . "FE_" . $this->TestDataHeader->Component->GetValue('SN') . "/";
        $this->url_directory = $main_url_directory;
        if (!file_exists($this->url_directory)){
            mkdir($this->url_directory);
        }
        $TestData_Id = $this->TestDataHeader->keyId;


        $datafile_count=0;

            if ($this->TestDataHeader->GetValue('Band') != 9){
                $q = "SELECT MIN(tilt), MAX(tilt),MIN(power_pol0_chA),MIN(power_pol0_chB),
                MIN(power_pol1_chA),MIN(power_pol1_chB),
                MAX(power_pol0_chA),MAX(power_pol0_chB),
                MAX(power_pol1_chA),MAX(power_pol1_chB)
                FROM TEST_Workmanship_Amplitude
                    WHERE fkHeader = $TestData_Id
                    AND fkFacility = $this->fc;";
                $r = @mysql_query($q,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);


                $tiltmin = @MYSQL_RESULT($r,0,0) - 10;
                $tiltmax = @MYSQL_RESULT($r,0,1) + 10;

                $ampmin = min(@MYSQL_RESULT($r,0,2),@MYSQL_RESULT($r,0,3),@MYSQL_RESULT($r,0,4),@MYSQL_RESULT($r,0,5)) - 0.1;
                $ampmax = max(@MYSQL_RESULT($r,0,6),@MYSQL_RESULT($r,0,7),@MYSQL_RESULT($r,0,8),@MYSQL_RESULT($r,0,9)) + 0.1;
            }

        if ($this->TestDataHeader->GetValue('Band') == 9){
                $q = "SELECT MIN(tilt), MAX(tilt),MIN(power_pol0_chA),
                MIN(power_pol1_chA),
                MAX(power_pol0_chA),
                MAX(power_pol1_chA)
                FROM TEST_Workmanship_Amplitude
                    WHERE fkHeader = $TestData_Id
                    AND fkFacility = $this->fc;";
                $r = @mysql_query($q,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);


                $tiltmin = @MYSQL_RESULT($r,0,0) - 10;
                $tiltmax = @MYSQL_RESULT($r,0,1) + 10;

                $ampmin = min(@MYSQL_RESULT($r,0,2),@MYSQL_RESULT($r,0,3)) - 0.1;
                $ampmax = max(@MYSQL_RESULT($r,0,4),@MYSQL_RESULT($r,0,5)) + 0.1;
            }



                $q = "SELECT tilt,power_pol0_chA,power_pol0_chB,
                power_pol1_chA,power_pol1_chB
                FROM TEST_Workmanship_Amplitude
                    WHERE fkHeader = $TestData_Id
                    AND fkFacility = $this->fc
                    ORDER BY TS ASC;";

                $r = @mysql_query($q,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);
                $plottitle = "Workmanship Amplitude";
                $data_file = $this->writedirectory . "FE_" . $this->TestDataHeader->Component->GetValue('SN') . "/wkm_amp_data.txt";
                if (@mysql_num_rows($r) > 1){

                    $fh = fopen($data_file, 'w');

                    $row=@mysql_fetch_array($r);
                    $timeval = 0;
                    $once = 0;
                        while($row=@mysql_fetch_array($r)){
                            if ($once != 1){
                                if ($this->TestDataHeader->GetValue('Band') != 9){
                                    $maxtemp = max($row[1],$row[2],$row[3],$row[4]);
                                }
                                if ($this->TestDataHeader->GetValue('Band') == 9){
                                    $maxtemp = max($row[1],$row[3]);
                                }
                                $o1 = $row[1] - $maxtemp;
                                $o2 = $row[2] - $maxtemp;
                                $o3 = $row[3] - $maxtemp;
                                $o4 = $row[4] - $maxtemp;
                                $once = 1;
                            }

                            $d1 = $row[1];
                            $d2 = $row[2];
                            $d3 = $row[3];
                            $d4 = $row[4];
                            $stringData = "$timeval\t$row[0]\t$d1\t$d2\t$d3\t$d4\r\n";
                            fwrite($fh, $stringData);
                            $timeval += (1/60);
                        }
                    fclose($fh);
                    $datafile_count++;
                }



        //Write command file for gnuplot
        $plot_command_file = $this->writedirectory . "wkm_amp_command_tdh$TestData_Id.txt.txt";
        $imagedirectory .= $this->writedirectory . "FE_" . $this->TestDataHeader->Component->GetValue('SN') . "/";
        //$imagedirectory .= $this->writedirectory;

        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagename = "WorkmanshipAmplitude_band" . $this->TestDataHeader->GetValue('Band') . "_" . date("Ymd_G_i_s") . ".png";
        $image_url = $this->url_directory . "FE_" . $this->TestDataHeader->Component->GetValue('SN') . "/$imagename";
        $plot_title = "Workmanship Amplitude, FE" . $this->TestDataHeader->Component->GetValue('SN') . " Band " . $this->TestDataHeader->GetValue('Band');




        //Update plot url
        $this->TestDataHeader->SetValue('PlotURL',$image_url);
        $this->TestDataHeader->Update();

        $imagepath = $imagedirectory . $imagename;


        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 900,500\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set key outside\r\n");
        //fwrite($fh, "set ylabel 'Power dBm'\r\n");
        fwrite($fh, "set xlabel 'Time (minutes)'\r\n");
        fwrite($fh, "set pointsize 2\r\n");

        fwrite($fh, "set y2range[$tiltmin:$tiltmax]\r\n");
        fwrite($fh, "set y2label 'Tilt Angle (deg)'\r\n");
        fwrite($fh, "set y2tics\r\n");
        fwrite($fh, "set yrange[$ampmin:$ampmax]\r\n");
        fwrite($fh, "set ytics\r\n");
        fwrite($fh, "set ylabel 'Amplitude (dBm)'\r\n");
        //fwrite($fh, "set y3range[-181:181]\r\n");
        //fwrite($fh, "set y4range[-1:2]\r\n");
        fwrite($fh, "set bmargin 6\r\n");
        fwrite($fh, "set label 'TestData_header.keyId: " . $this->TestDataHeader->keyId . ", Dataplotter Ver. $this->swversion' at screen 0, screen 0.01\r\n");
        fwrite($fh, "set label 'Tested $this->measdate, FE Configuration $this->FEcfg' at screen 0, screen 0.04\r\n");

        //fwrite($fh, "set linestyle 1 lt 1 lw 1\r\n");


        $plot_string = "plot '$data_file' using 1:2 title 'Tilt Angle' with points pt 1 ps 0.2 axis x1y2";
        $plot_string .= ", '$data_file'  using 1:3 title 'Pol 0, USB Power' with lines axis x1y1";
        if ($this->TestDataHeader->GetValue('Band') != 9){
            $plot_string .= ", '$data_file'  using 1:4 title 'Pol 0, LSB Power' with lines axis x1y1";
        }
        $plot_string .= ", '$data_file'  using 1:5 title 'Pol 1, USB Power' with lines axis x1y1";
        if ($this->TestDataHeader->GetValue('Band') != 9){
            $plot_string .= ", '$data_file'  using 1:6 title 'Pol 1, LSB Power' with lines axis x1y1";
        }

        $plot_string .= "\r\n";
        fwrite($fh, $plot_string);
        fclose($fh);
        //Make the plot

        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);

    }


public function Plot_WorkmanshipPhase(){
    require(site_get_config_main());
    $this->writedirectory = $main_write_directory  . "FE_" . $this->TestDataHeader->Component->GetValue('SN') . "/";
    $this->writedirectory = $main_write_directory;
    if (!file_exists($this->writedirectory)){
        mkdir($this->writedirectory);
    }

    $this->url_directory = $main_url_directory  . "FE_" . $this->TestDataHeader->Component->GetValue('SN') . "/";
    $this->url_directory = $main_url_directory;
    if (!file_exists($this->url_directory)){
        mkdir($this->url_directory);
    }
    $TestData_Id = $this->TestDataHeader->keyId;

    $datafile_count=0;
    $q = "SELECT MIN(tilt), MAX(tilt)
        FROM TEST_Workmanship_Phase
        WHERE fkHeader = $TestData_Id
        AND fkFacility = $this->fc;";
    $r = @mysql_query($q,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);
    //echo $q . "<br>";

    $tiltmin = @MYSQL_RESULT($r,0,0) - 10;
    $tiltmax = @MYSQL_RESULT($r,0,1) + 10;

    $qsh = "SELECT keyTEST_Workmanship_Phase_SubHeader
    FROM TEST_Workmanship_Phase_SubHeader
        WHERE fkHeader = $TestData_Id;";
    $rsh = @mysql_query($qsh,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);
    $subheader_id = @mysql_result($rsh,0,0);
    $wsub = new GenericTable();
    $wsub->Initialize('TEST_Workmanship_Phase_SubHeader',$subheader_id,'keyTEST_Workmanship_Phase_SubHeader');


    $l = new Logger('CLASSDATAPLOTTER.txt');


    // Get array of phase values and unwrap, to determine offset value for plotting.

    $q = "SELECT phase, tilt
    FROM TEST_Workmanship_Phase
        WHERE fkHeader = $TestData_Id
        AND fkFacility = $this->fc
        ORDER BY TS ASC;";

    $l->WriteLogFile($q);
    $r = @mysql_query($q,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);

    if (@mysql_num_rows($r) > 1) {
        $row=@mysql_fetch_array($r);
        $timeval = 0;
        $phasecount = 0;
        while($row=@mysql_fetch_array($r)){
            $phase = $row[0];
            if ($phase < 0)
                $phase += 360;

            $phases[$phasecount] = $phase;
            $tilts[$phasecount] = $row[1];
            $phasecount += 1;
        }

        $maxphase = max($phases);
        $minphase = min($phases);
        $phasemid = 0.5 * ($maxphase + $minphase);

        $l->WriteLogFile("Max=$maxphase min=$minphase mid=$phasemid");
    }
    $imagedirectory .= $this->writedirectory . "FE_" . $this->TestDataHeader->Component->GetValue('SN') . "/";
    if (!file_exists($imagedirectory)){
        mkdir($imagedirectory);
    }

    $offset = $phasemid;
    $plottitle = "Workmanship Phase";
    $data_file = $imagedirectory . "wkm_phase_data.txt";

    $fh = fopen($data_file, 'w');

    $row=@mysql_fetch_array($r);
    $timeval = 0;

    for ($i=0; $i < count($phases); $i++){
        $newphase = $phases[$i];
        $stringData = "$timeval\t$tilts[$i]\t$phases[$i]\r\n";
        fwrite($fh, $stringData);
        $timeval += (1/60);
    }
    fclose($fh);
    $datafile_count++;

    //Write command file for gnuplot
    $plot_command_file = $imagedirectory . "wkm_phase_command_tdh$TestData_Id.txt.txt";

    $imagename = "WorkmanshipPhase_band" . $this->TestDataHeader->GetValue('Band') . "_" . date("Ymd_G_i_s") . ".png";
    $image_url = $this->url_directory . "FE_" . $this->TestDataHeader->Component->GetValue('SN') . "/$imagename";

    $sb = "USB";
    if ($wsub->GetValue('sb') == 2){
        $sb = "LSB";
    }

    $plot_title = "Workmanship Phase, FE" . $this->TestDataHeader->Component->GetValue('SN') . " Band " . $this->TestDataHeader->GetValue('Band');
    $plot_title .= ", Pol " . $wsub->GetValue('pol') . " $sb (RF " . $wsub->GetValue('rf') . ", LO " . $wsub->GetValue('lo') . ")";

    //Update plot url
    $this->TestDataHeader->SetValue('PlotURL',$image_url);
    $this->TestDataHeader->Update();

    $imagepath = $imagedirectory . $imagename;
    $fh = fopen($plot_command_file, 'w');
    fwrite($fh, "set terminal png size 900,500\r\n");
    fwrite($fh, "set output '$imagepath'\r\n");
    fwrite($fh, "set title '$plot_title'\r\n");
    fwrite($fh, "set grid\r\n");
    fwrite($fh, "set key outside\r\n");
    //fwrite($fh, "set ylabel 'Power dBm'\r\n");
    fwrite($fh, "set xlabel 'Time (minutes)'\r\n");
    fwrite($fh, "set pointsize 2\r\n");

    fwrite($fh, "set y2range[$tiltmin:$tiltmax]\r\n");
    fwrite($fh, "set y2label 'Tilt Angle (deg)'\r\n");
    fwrite($fh, "set y2tics\r\n");
    fwrite($fh, "set yrange[".($minphase-10).":".($maxphase+10)."]\r\n");
    fwrite($fh, "set ytics\r\n");
    fwrite($fh, "set ylabel 'Phase (deg)'\r\n");
    //fwrite($fh, "set y3range[-181:181]\r\n");
    //fwrite($fh, "set y4range[-1:2]\r\n");
    fwrite($fh, "set bmargin 6\r\n");
    fwrite($fh, "set label 'TestData_header.keyId: " . $this->TestDataHeader->keyId . ", Dataplotter Ver. $this->swversion' at screen 0, screen 0.01\r\n");
    fwrite($fh, "set label 'Tested $this->measdate, FE Configuration $this->FEcfg' at screen 0, screen 0.04\r\n");

    //fwrite($fh, "set linestyle 1 lt 1 lw 1\r\n");


    $plot_string = "plot '$data_file' using 1:2 title 'Tilt Angle' with points pt 1 ps 0.2 axis x1y2";
    $plot_string .= ", '$data_file'  using 1:3 title 'Phase' with lines axis x1y1";

    //$plot_string = "plot '$data_file' using 1:2 title 'Tilt Angle' with points pt 1 ps 0.2 axis x1y2";
    //$plot_string .= ", '$data_file'  using 1:3 title 'Phase' with points pt 1 ps 0.2 axis x1y1";


    $plot_string .= "\r\n";
    fwrite($fh, $plot_string);
    fclose($fh);
    //Make the plot

    $CommandString = "$GNUPLOT $plot_command_file";
    system($CommandString);

}


public function Plot_PolAngles(){
        require(site_get_config_main());




    $td_header = $this->TestDataHeader->keyId;
        $this->writedirectory = $main_write_directory;
        $this->url_directory = $main_url_directory;
        $plot_title = "Pol Angles  FE-" . $this->FESN . ", Band " . $this->TestDataHeader->GetValue('Band');

        /**********************
         * Write the data file
         * ********************/
            $min0 = 999;
            $min1 = 999;



            $data_file = $this->writedirectory . "polangle_data" . $this->TestDataHeader->keyId .".txt";
            $fh = fopen($data_file, 'w');

            //$tdh = new GenericTable();
            ////$tdh->Initialize('TestData_header',$td_header,'keyId');


            $qdata = "SELECT angle,amp_pol0,phase_pol0,amp_pol1,phase_pol1
                        FROM TEST_PolAngles
                      WHERE fkHeader = $td_header
                      AND fkFacility = $this->fc
                      ORDER BY angle ASC;";



            $rdata = @mysql_query($qdata,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);
            while ($rowdata = @mysql_fetch_array($rdata)){
                $stringData = "$rowdata[0]\t$rowdata[1]\t$rowdata[2]\t$rowdata[3]\t$rowdata[4]\r\n";
                fwrite($fh, $stringData);

                if ($rowdata[1] < $min0){
                    $min0 = round($rowdata[1],2);
                    $min0angle = round($rowdata[0],2);
                }
                if ($rowdata[3] < $min1){
                    $min1 = round($rowdata[3],2);
                    $min1angle = round($rowdata[0],2);
                }
            }
            //Put empty line after each series for gnuplot
            fwrite($fh, "\r\n");

            unset($ifsub);
            fclose($fh);

        //Create directories if necesary
        $imagedirectory = $this->writedirectory . 'tdh/';
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagedirectory .= "$td_header/";
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagedirectory .= 'PolAngle/';
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagename = "polangleFE" . $this->FESN . "_Band" . $this->TestDataHeader->GetValue('Band') . "_tdh" . $this->TestDataHeader->keyId . ".png";
        $imagepath = $imagedirectory . $imagename;



        //Write command file
        $commandfile = $this->writedirectory . "polangle_commands_tdh$td_header.txt";
        $fh = fopen($commandfile, 'w');
        fwrite($fh, "set terminal png crop\r\n");

        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set xlabel 'Source Rotation Angle (deg)'\r\n");
        fwrite($fh, "set ylabel 'Amplitude (dB)'\r\n");
        fwrite($fh, "set bmargin 6\r\n");
        fwrite($fh, "set label 'TestData_header.keyId: $td_header, Dataplotter Ver. $this->swversion' at screen 0, screen 0.01\r\n");
        fwrite($fh, "set label 'Tested $this->measdate, FE Configuration $this->FEcfg' at screen 0, screen 0.04\r\n");
        fwrite($fh, "set key right outside\r\n");
        fwrite($fh, "set grid xtics ytics\r\n");
        fwrite($fh, "plot '$data_file' using 1:2 title 'Pol 0 Amplitude' with lines ");
        fwrite($fh, ", '$data_file' using 1:4 title 'Pol 1 Amplitude' with lines ");
        fwrite($fh, "\r\n");
        fclose($fh);


        $CommandString = "$GNUPLOT $commandfile";
        system($CommandString);

        $image_url = $this->url_directory . "tdh/$td_header/PolAngle/$imagename";


        $this->TestDataHeader->SetValue('PlotURL',$image_url);
        $this->TestDataHeader->Update();


}


public function Plot_LOLockTest(){
        require(site_get_config_main());

    $td_header = $this->TestDataHeader->keyId;
        $this->writedirectory = $main_write_directory  . "FE_" . $this->FESN . "/";
        $this->writedirectory = $main_write_directory;
        if (!file_exists($this->writedirectory)){
            mkdir($this->writedirectory);
        }

        $this->url_directory = $main_url_directory  . "FE_" . $this->FESN . "/";
        $this->url_directory = $main_url_directory;
        if (!file_exists($this->url_directory)){
            mkdir($this->url_directory);
        }

        //Get CCA Serial Number
        $qcca ="SELECT FE_Components.SN FROM FE_Components, FE_ConfigLink, FE_Config
             WHERE FE_ConfigLink.fkFE_Config = $this->FEcfg
             AND FE_Components.fkFE_ComponentType = 20
             AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
             AND FE_Components.Band = " . $this->TestDataHeader->GetValue('Band') . "
             AND FE_Components.keyFacility = $this->fc
             AND FE_ConfigLink.fkFE_ConfigFacility = FE_Config.keyFacility
             ORDER BY Band ASC;";
            $r = @mysql_query($qcca,$this->dbconnection);

        $CCA_SN = @mysql_result($r,0,0);

        //Get WCA info
        $qwca = "SELECT FE_Components.SN FROM FE_Components, FE_ConfigLink, FE_Config
         WHERE FE_ConfigLink.fkFE_Config = $this->FEcfg
         AND FE_Components.fkFE_ComponentType = 11
         AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
         AND FE_Components.Band = " . $this->TestDataHeader->GetValue('Band') . "
         AND FE_Components.keyFacility = $this->fc
         AND FE_ConfigLink.fkFE_ConfigFacility = FE_Config.keyFacility
         GROUP BY Band ASC;";
        $rwca = @mysql_query($qwca,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);
        $WCA_SN = @mysql_result($rwca,0,0);

            $data_file = $this->writedirectory . "lolocktest_data" . $this->TestDataHeader->keyId . ".txt";
            $fh = fopen($data_file, 'w');


            $tdh = new GenericTable();
            $tdh->Initialize('TestData_header',$td_header,'keyId',$this->fc,'keyFacility');

        $t = new Logger('CLASSDATAPLOTTER.txt');


        //Get Subheader key
        $qsub = "SELECT MAX(keyId) FROM TEST_LOLockTest_SubHeader
                 WHERE fkHeader = ".$this->TestDataHeader->keyId."
                 AND TEST_LOLockTest_SubHeader.keyFacility = " . $this->TestDataHeader->getValue('keyFacility') . ";";
        $rsub = @mysql_query($qsub,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);


/*
        //Get all TestData_header.keyId values for records with the same DataSetGroup as this one
        $qtdh = "SELECT keyId FROM TestData_header WHERE
                Band = " . $tdh->GetValue('Band') . " AND fkTestData_Type= " . $tdh->GetValue('fkTestData_Type') .
                " AND DataSetGroup= " . $tdh->GetValue('DataSetGroup') . " AND fkFE_Config= ". $tdh->GetValue('fkFE_Config') . ";";
        */

        $t->WriteLogFile($qsub);

//        $rtdh = @mysql_query($qtdh,$this->dbconnection);

//        $t->WriteLogFile($qtdh);
/*        $tdharray = '';
        $tdhcount = 0;
        while ($rowtdh = @mysql_fetch_array($rtdh)){
            $tdharray[$tdhcount] = $rowtdh[keyId];
            $tdhcount += 1;
        }
*/

        $subheader_id = @mysql_result($rsub,0,0);

        //Array of LO frequencies where lock was lost
        $UnlockedLO = '';
        //Array of PLLRefTotalPower where lock was lost
        $UnlockedPwr = '';
        $UnlockedCount = 0;

        $lpr = new GenericTable();
        $lpr->Initialize('TEST_LOLockTest_SubHeader',$subheader_id,'keyId',$this->fc,'keyFacility');

        // data query depending on dataset
        if ($this->TestDataHeader->GetValue('DataSetGroup') == 0){
            $qdata= "SELECT TEST_LOLockTest.LOFreq,
                        TEST_LOLockTest.PhotomixerCurrent,
                        TEST_LOLockTest.PLLRefTotalPower
                        FROM TEST_LOLockTest, TEST_LOLockTest_SubHeader, TestData_header
                        WHERE TEST_LOLockTest.fkHeader = TEST_LOLockTest_SubHeader.keyId
                        AND TEST_LOLockTest_SubHeader.fkHeader = TestData_header.keyId
                        AND TestData_header.keyId = $td_header
                        AND TEST_LOLockTest.IsIncluded = 1
                        GROUP BY TEST_LOLockTest.LOFreq ASC;";
        } else {
            // query to get front end key of the FEConfig of the TDH.
            $qfe = "SELECT fkFront_Ends FROM `FE_Config` WHERE `keyFEConfig` = ". $this->TestDataHeader->GetValue('fkFE_Config');

            // query to get data
            $qdata = "SELECT TEST_LOLockTest.LOFreq,
                    TEST_LOLockTest.PhotomixerCurrent,
                    TEST_LOLockTest.PLLRefTotalPower
                    FROM FE_Config
                    LEFT JOIN TestData_header ON TestData_header.fkFE_Config = FE_Config.keyFEConfig
                    LEFT JOIN TEST_LOLockTest_SubHeader ON TEST_LOLockTest_SubHeader.`fkHeader` = `TestData_header`.`keyId`
                    LEFT JOIN TEST_LOLockTest ON TEST_LOLockTest_SubHeader.`keyId` = TEST_LOLockTest.fkHeader
                    WHERE TestData_header.Band = " . $this->TestDataHeader->GetValue('Band')."
                    AND TestData_header.fkTestData_Type= 57
                    AND TestData_header.DataSetGroup= " . $this->TestDataHeader->GetValue('DataSetGroup')."
                    AND TEST_LOLockTest.IsIncluded = 1
                    AND FE_Config.fkFront_Ends = ($qfe)
                    GROUP BY TEST_LOLockTest.LOFreq ASC;";
        }

            $t->WriteLogFile($qdata);

            $rdata = @mysql_query($qdata,$this->dbconnection)  or die('Failed on query in dataplotter.php line ' . __LINE__);
            $UnlocksFound = 0;
            $count = 0;
            while ($rowdata = @mysql_fetch_array($rdata)){
                if ($counted == 1){
                    $FreqStepSize = abs($previousLO - $rowdata[LOFreq]);
                }


                //If even one unlock is found, set $UnlocksFound = 1.
                //If any unlocked points are found, they will be plotted on the graph.
                if (($rowdata[LOLocked] == 0) || ($rowdata[LORTMLocked] == 0)){
                    $UnlocksFound = 1;
                }


                $stringData = $rowdata[LOFreq] . "\t" . $rowdata[PhotomixerCurrent] . "\t" . $rowdata[PLLRefTotalPower] . "\r\n";
                fwrite($fh, $stringData);

                //Record any points at which TEST_LOLockTest.LOLocked=0 or TEST_LOLockTest.LORTMLocked=0
                if (($rowdata[LOLocked] == '0') || ($rowdata[LORTMLocked] == '0')){
                    $UnlockedLO[$UnlockedCount]  = $rowdata[LOFreq];
                    $UnlockedPwr[$UnlockedCount] = $rowdata[PhotomixerCurrent];
                    $UnlockedCount += 1;
                }
                $previousLO = $rowdata[LOFreq];
                $counted += 1;


            }
            //Put empty line after each series for gnuplot
            fwrite($fh, "\r\n");



            unset($ifsub);
            fclose($fh);


        $plot_title = "LO Lock Test FE SN" . $this->FESN;
        $plot_title .= ", CCA". $this->TestDataHeader->GetValue('Band'). "-$CCA_SN ";
        $plot_title .= "WCA". $this->TestDataHeader->GetValue('Band'). "-$WCA_SN, ";
        $plot_title .= "LPR EDFA Modulation ". $lpr->GetValue('LPRModulation')."V ";
/*        if ($this->TestDataHeader->GetValue('DataSetGroup') != 0){
            $plot_title .= ", Data Set Group " . $tdh->GetValue('DataSetGroup');
        }    */
        $t->WriteLogFile($plot_title);

        //Create directories if necesary
        $imagedirectory .= $this->writedirectory . "FE_" . $this->FESN . "/";

        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagename = "LOLockTest_band" . $this->TestDataHeader->GetValue('Band') . "_" . date("Ymd_G_i_s") . ".png";
        $image_url = $this->url_directory . "FE_" . $this->TestDataHeader->Component->GetValue('SN') . "/$imagename";
        $plot_command_file = $this->writedirectory . "lolocktest_command_tdh$TestData_Id.txt";
        $image_url = $this->url_directory . "FE_" . $this->FESN . "/$imagename";

        //Update plot url. Set the same value for all TestData_header records in this group.
        $this->TestDataHeader->SetValue('PlotURL',$image_url);
        $this->TestDataHeader->Update();


//        for ($i=0;$i<count($tdharray);$i++){
//            $qURL = "UPDATE TestData_header SET PlotURL = '$image_url' WHERE keyId = $tdharray[$i];";
        $qURL = "UPDATE TestData_header SET PlotURL = '$image_url' WHERE keyId = $td_header;";
        $rURL = @mysql_query($qURL,$this->dbconnection);


        $t->WriteLogFile($qURL);
//        }



        //$t->WriteLogFile($qURL);



        $imagepath = $imagedirectory . $imagename;


        $t->WriteLogFile("Plot command file= " . $plot_command_file);

        // set up plot labels
        if ($this->TestDataHeader->GetValue('DataSetGroup') == 0){
            $plot_label_1 =" set label 'TestData_header.keyId: $td_header, Plot SWVer: $this->swversion, Meas SWVer: ".$this->TestDataHeader->GetValue('Meas_SWVer')."' at screen 0, screen 0.01\r\n";
            $plot_label_2 ="set label '".$this->TestDataHeader->GetValue('TS').", FE Configuration ".$this->TestDataHeader->GetValue('fkFE_Config')."' at screen 0, screen 0.04\r\n";
        } else {
            $q = "SELECT `TestData_header`.keyID, `TestData_header`.TS,`TestData_header`.`fkFE_Config`,`TestData_header`.Meas_SWVer
                FROM FE_Config
                LEFT JOIN `TestData_header` ON TestData_header.fkFE_Config = FE_Config.keyFEConfig
                WHERE TestData_header.Band = " . $this->TestDataHeader->GetValue('Band')."
                AND TestData_header.fkTestData_Type= " . $this->TestDataHeader->GetValue('fkTestData_Type')."
                AND TestData_header.DataSetGroup= " . $this->TestDataHeader->GetValue('DataSetGroup')."
                AND FE_Config.fkFront_Ends = (SELECT fkFront_Ends FROM `FE_Config` WHERE `keyFEConfig` = ".$this->TestDataHeader->GetValue('fkFE_Config').")
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

            $plot_label_1 =" set label 'TestData_header.keyId: ($keyId), Plot SWVer: $this->swversion, Meas SWVer: $meas_ver' at screen 0, screen 0.01\r\n";
            $plot_label_2 ="set label 'Dataset: ".$this->TestDataHeader->GetValue('DataSetGroup').", TS: $TS, FE Configuration: $FE_Config' at screen 0, screen 0.04\r\n";
        }

        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 900,500\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set key outside\r\n");
        fwrite($fh, "set xlabel 'LO Frequency (GHz)'\r\n");
        fwrite($fh, "set pointsize 2\r\n");
        fwrite($fh, "set y2label 'Photomixer Current (mA)'\r\n");
        fwrite($fh, "set y2tics\r\n");
        fwrite($fh, "set yrange[-5:0]\r\n");
        fwrite($fh, "set ytics\r\n");
        fwrite($fh, "set ylabel 'PLL IF Detected Power (Volts)'\r\n");
        fwrite($fh, "set bmargin 6\r\n");
        fwrite($fh, $plot_label_1);
        fwrite($fh, $plot_label_2);
//        fwrite($fh, "set label 'TestData_header.keyId: " . $this->TestDataHeader->keyId . ", Dataplotter Ver. $this->swversion' at screen 0, screen 0.01\r\n");
//        fwrite($fh, "set label 'Tested $this->measdate, FE Configuration $this->FEcfg' at screen 0, screen 0.04\r\n");

        if ($UnlocksFound == 1){
            //Plot points where lock failed
            //This loop adds a function for each point where LO was unlocked.
            for ($i=0; $i<count($UnlockedLO); $i++){
                $minpoint = $UnlockedLO[$i] - (0.5 * $FreqStepSize);
                $maxpoint = $UnlockedLO[$i] + (0.5 * $FreqStepSize);
                //$pointval = "p$i(x)=((x>$minpoint) && (x<$maxpoint)) ? $UnlockedPwr[$i] : 1/0\r\n";

                $pointval = "p$i(x)=((x>$minpoint) && (x<$maxpoint)) ? 0 : 1/0\r\n";

                fwrite($fh, $pointval);
            }
        }

        $plot_string = "plot '$data_file' using 1:2 title 'Photomixer Current' lt 8 with lines axis x1y2";
        $plot_string .= ", '$data_file'  using 1:3 title 'PLL IF Total Power' with lines axis x1y1";
        $plot_string .= ", -0.5  title 'spec' lt 1 with lines axis x1y1";

        if ($UnlocksFound == 1){
            //Plot points where lock failed
            //This loops plots each function where LO was unlocked.
            $plot_string .= ", p0(x) with linespoints  title 'LO Unlocked' pt 5 lt 12 pointsize 1 axis x1y1";
            for ($i=1; $i<count($UnlockedLO); $i++){
                //$plot_string .= ", p$i(x) with linespoints  notitle pt 5 lt 12 pointsize 1 axis x1y1";
                $plot_string .= ", p$i(x) with linespoints  notitle pt 5 lt 12 pointsize 1 axis x1y1";
            }
        }



        $plot_string .= ", -4.5  notitle lt 1 with lines axis x1y1";

        //Plot markers at points where lock was lost


        $plot_string .= "\r\n";
        fwrite($fh, $plot_string);
        fclose($fh);
        //Make the plot

        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
}

function progressBar($percentage) {
    print "<div id=\"progress-bar\" class=\"all-rounded\">\n";
    print "<div id=\"progress-bar-percentage\" class=\"all-rounded\" style=\"width: $percentage%\">";
        if ($percentage > 5) {print "$percentage%";} else {print "<div class=\"spacer\">&nbsp;</div>";}
    print "</div></div>";
}












}//end class