<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.logger.php');

class PwrSpecTool{
// ported from pwrSpecTools.py python module developed by M.McLeod

    var $dbconnection;
    var $cablePad;
    var $smoothingPts;
    var $writedirectory;
    var $NoiseFloorKey;
    var $fc;
    var $logger;  //Debugging logger.

    function __construct() {
       require(site_get_config_main());
       $this->cablePad = 6.0;
       $this->smoothingPts = 0;
       $this->writedirectory = $main_write_directory;
       $this->logger = new Logger("log_pwrspec_" . date("Y_m_d__H_i_s") . ".txt", 'a');
    }

    public function powerVarWindow2($fkSubHeader, $fMin, $fMax, $fWindow, $deleteprevious = 1){
        require(site_get_config_main());

        if ($deleteprevious == 1){
            //Delete previous records for this fWindow
            $qd = "DELETE FROM TEMP_TEST_IFSpectrum_PowerVar
                   WHERE fkSubHeader = $fkSubHeader
                   AND WindowSize_Hz = $fWindow
                   AND fkFacility = $this->fc;";
            $rd = @mysql_query($qd,$this->dbconnection) or die ('Query failed on class.pwrspectools line 35.');
            //$this->logger->WriteLogFile($qd);
        }

        /*
        # find index of the band edge frequencies
        iMin = index(data[0], lambda f: f >= fMin)
        iMax = index(data[0], lambda f: f >= fMax) - 1
        #print "iMin=", iMin, "iMax=", iMax
        # find index of the lowest and highest center frequencies for the window:
        fLo = fMin + (fWindow / 2)
        fHi = fMax - (fWindow / 2)
        iLo = index(data[0], lambda f: f >= fLo)
        iHi = index(data[0], lambda f: f >= fHi)
        */

        $fLo = $fMin + ($fWindow / 2);
        $fHi = $fMax - ($fWindow / 2);

        $qnew = "SELECT Freq_Hz,Power_dBm
                 FROM TEMP_IFSpectrum WHERE fkSubHeader = $fkSubHeader
                 AND fkFacility = $this->fc
                 order BY Freq_Hz ASC; ";
        
        $temp = $fkSubHeader;
        $temp1 = $this->fc;
        //$this->logger->WriteLogFile($qnew);
        $rnew = @mysql_query($qnew,$this->dbconnection) or die ('Query failed on class.pwrspectools line 59.') ;
        while($row = @mysql_fetch_array($rnew)) {
        	$temp = $row[0];
        	$temp1 = $row[1];
        }
        
        $indexval = 1;
        $maxval = 999999999999;
        $minval = $maxval;
        $loval = 999999999999;
        $hival = $loval;
        
        while ($row = @mysql_fetch_array($rnew)){

            $ifarray[$indexval] = $row[0];
            $pwrdbmarray[$indexval] = $row[1];
            $mintemp = abs($row[0] - $fMin);
            $maxtemp = abs($row[0] - $fMax);
            $lotemp = abs($row[0] - $fLo);
            $hitemp = abs($row[0] - $fHi);

            if ($mintemp < $minval){
                $minval = $mintemp;
                $iMin = $indexval;
            }
            if ($maxtemp < $maxval){
                $maxval = $maxtemp;
                $iMax = $indexval;
            }
            if ($lotemp < $loval){
                $loval = $lotemp;
                $iLo = $indexval-1;
            }
            if ($hitemp < $hival){
                $hival = $hitemp;
                $iHi = $indexval-1;
            }
            $indexval += 1;
        }
        $iMax -= 1;
        $iMin -= 1;
        $fMin = $ifarray[$iMin+1];
        $fMax = $ifarray[$iMax+1];

        /*
        # half the span for the window is the distance between band edge and lowest center:
        iSpan = iLo - iMin
        #print "fLo=", fLo, "fHi=", fHi, "iLo=", iLo, "iHi=", iHi, "iSpan=", iSpan
        */
        $iSpan = $iLo - $iMin;

        /*
        F = []
        V = []
        for iCenter in range(iLo, iHi + 1):
            beg = iCenter - iSpan
            end = iCenter + iSpan
            F.append(data[0][iCenter])
            V.append(max(data[1][beg:end]) - min(data[1][beg:end]))
        */

        for ($i=1; $i<count($pwrdbmarray); $i++){
            if (($i >= $iMin) && ($i <= $iMax)){
                $data_f[$i] = $ifarray[$i];
                $data_p[$i] = $pwrdbmarray[$i];
            }
        }

        $icnt = 0;
        for ($iCenter=$iLo; $iCenter<$iHi; $iCenter++){
            $beg = $iCenter - $iSpan;
            $end = $iCenter + $iSpan;

            $vmin = 1000;
            $vmax = -1000;

            for ($i=$beg; $i< $end; $i++){
                if ($data_p[$i] < $vmin){
                    $vmin = $data_p[$i];
                }
                if ($data_p[$i] > $vmax){
                    $vmax = $data_p[$i];
                }
            }

            $data_v = $vmax - $vmin;
            $freq = $data_f[$iCenter];

            $qinsert = "INSERT INTO TEMP_TEST_IFSpectrum_PowerVar
                        (fkSubHeader,WindowSize_Hz,Freq_Hz,Power_dBm,fkFacility)
                        VALUES
                        ('$fkSubHeader','$fWindow','$freq','$data_v','$this->fc');";
            $rinsert = @mysql_query($qinsert,$this->dbconnection)  or die ('Query failed on class.pwrspectools line 145.');
            //if ($icnt < 10){
            //    $this->logger->WriteLogFile($qinsert);
            //}
            $icnt+=1;
        }
    }

    public function powerTotalAndInBandPower($fkSubHeader, $fMin, $fMax){
        //Get noisefloor key
        $qnf = "SELECT fkNoiseFloorHeader FROM IFSpectrum_SubHeader
                WHERE
                keyId = $fkSubHeader
                AND keyFacility = $this->fc LIMIT 1;";
        $rnf = @mysql_query($qnf,$this->dbconnection);
        $this->NoiseFloorKey = @mysql_result($rnf,0);

        $this->logger->WriteLogFile("powerTotalAndInBandPower NoiseFloorKey=" . $this->NoiseFloorKey);

        $q = "SELECT Power_dBm, Freq_Hz from  TEMP_IFSpectrum
          where fkSubHeader = $fkSubHeader
          AND fkFacility = $this->fc
          order by Freq_Hz ASC;";
        $r = @mysql_query($q,$this->dbconnection);

        $count = 0;
        while ($row = @mysql_fetch_array($r)){
            /*
             * Fill up two arrays with query results.
             * f_arr = array of frequency values
             * p_arr = array of power values
             */
            $f_arr[$count] = $row[1];
            $p_arr[$count] = $row[0];
            $count+=1;
        }

        $q = "SELECT Power_dBm, Freq_Hz  from  TEST_IFSpectrum_NoiseFloor
              where fkHeader = $this->NoiseFloorKey
              AND fkFacility = $this->fc
              order by Freq_Hz ASC;";
        $r = @mysql_query($q,$this->dbconnection)  or die ('Query failed on class.pwrspectools line 212.');

        $count = 0;
        while ($row = @mysql_fetch_array($r)){
            /*
             * Get an array with the noise floor values
             * pnf_arr = array of power values
             */
            $pnf_arr[$count] = $row[0];
            $count+=1;
        }

        $total = 0;
        $inBand = 0;

        $icnt=0;
        for ($i=0; $i<count($p_arr); $i++){
            /*
             * Step through the arrays of frequency and power values,
             * subtract noise floor.
             *
             * psnf = power level after subtracting noise floor
             * total = sum of all psnf values
             * inBand = sum of all psnf values withhin the band
             */
            $f = $f_arr[$i];
            $psnf = $this->SubtractNoiseFloor($p_arr[$i],$pnf_arr[$i]);
            $p = pow(10, ($psnf + $this->cablePad) / 10);
            if ($f >= 10000000){
                $total += $p;
            }
            if (($f >= $fMin) && ($f <= $fMax)){
                $inBand += $p;
            }
        }

        /*
         * Convert power levels back from log
         */
        $total = 10 * log($total,10);
        $inBand = 10 * log($inBand,10);

        //Update power table in database
        $qd = "SELECT keyId FROM TEST_IFSpectrum_TotalPower
        WHERE fkSubHeader = $fkSubHeader
        AND fkFacility = $this->fc;";
        $rd = @mysql_query($qd,$this->dbconnection)  or die ('Query failed on class.pwrspectools line 258.');
        $keytp = @mysql_result($rd,0);

        $tp = new GenericTable();
        $tp->Initialize("TEST_IFSpectrum_TotalPower", $keytp, "keyId", $this->fc,'fkFacility' );

        if ($tp->keyId == ""){
            $tp->NewRecord("TEST_IFSpectrum_TotalPower");
            $tp->SetValue('fkSubHeader',$fkSubHeader);
        }
        $tp->SetValue('TotalPower',$total);
        $tp->SetValue('InBandPower',$inBand);
        $tp->Update();
    }

    public function SubtractNoiseFloor($power,$power_nf){
       /*Original Python code by Morgan McLeod:
        *
        *if len(noiseFloorData[1]) < len(data):
        *    return  # can't subtract noise floor with different dimensions
        *try:
        *    for i in range(len(data[0])):
        *        p = 10.0 ** ((data[1][i]) / 10.0)
        *        floor = 10.0 ** ((noiseFloorData[1][i]) / 10.0)
        *        if (p <= floor):
        *            p = 1.0e-9  # if we can't subtract the floor from the power level, just use a tiny quantity of power.
        *        else:
        *            p -= floor;
        *        data[1][i] = 10 * log10(p)
        */

        $p     = pow(10,$power/10.0);
        $floor = pow(10,$power_nf/10.0);

        if ($p <= $floor){
            $p = pow(10,-9);
        }
        else{
            $p -= $floor;
        }
        $psnf = 10 * log($p, 10);
        return $psnf;
    }

    public function PowerVarFullBand($fkSubHeader, $fMin = 4000000000, $fMax = 8000000000){
        /*
         * def powerVarFullBand(data, fMin = 4.0e9, fMax = 8.0e9):
        if (smoothingPts > 1):
            data = movingAverage(data, smoothingPts)
        # find index of the band edge frequencies
        iMin = index(data[0], lambda f: f >= fMin)
        iMax = index(data[0], lambda f: f >= fMax) - 1
        # full band variation:
        beg = iMin
        end = iMax + 1
        fullBandVar = max(data[1][beg:end]) - min(data[1][beg:end])
        #print "iMin=", iMin, "iMax=", iMax, "fullBandVar=", fullBandVar
        return fullBandVar
        */
        $this->logger->WriteLogFile("PowerVarFullBand, fMin $fMin, fMax $fMax");

        //Delete previous records for this fWindow
        $qd = "DELETE FROM TEST_IFSpectrum_PowerVarFullBand
                WHERE fkSubHeader = $fkSubHeader
                AND fkFacility = $this->fc;";
        //$this->logger->WriteLogFile($qd);
        $rd = @mysql_query($qd,$this->dbconnection)  or die ('Query failed on class.pwrspectools line 329.');


        $qmin = "SELECT  MIN(Power_dBm), MAX(Power_dBm)
                  FROM TEMP_IFSpectrum WHERE fkSubHeader = $fkSubHeader
                  AND Freq_Hz >= $fMin
                  AND Freq_Hz <= $fMax
                  AND fkFacility = $this->fc; ";
        //$this->logger->WriteLogFile($qmin);
        //echo $qmin . "<br>";
        $rmin = @mysql_query($qmin,$this->dbconnection)  or die ('Query failed on class.pwrspectools line 339.');
        $vMin = @mysql_result($rmin,0,0);
        $vMax = @mysql_result($rmin,0,1);
        $v = abs(abs($vMin) - abs($vMax));

        //echo "$vMin,$vMax,$v";
        $qinsert = "INSERT INTO TEST_IFSpectrum_PowerVarFullBand
                    (fkSubHeader,Power_dBm,fkFacility)
                    VALUES
                    ('$fkSubHeader','$v','$this->fc');";
        //$this->logger->WriteLogFile($qinsert);
        $rinsert = @mysql_query($qinsert,$this->dbconnection)  or die ('Query failed on class.pwrspectools line 350.');
        $this->logger->WriteLogFile("Finished PowerVarFullBand.");
    //    $this->CloseLogfile();
        //echo $qinsert . "<br>";
    }
}// end class PwrSpecTool
?>
