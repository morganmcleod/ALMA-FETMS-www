<?php
// Reads/modifies a ScanDetails and its corresponding BeamEfficiencies record from the FEIC database.
//
// Utility methods to upload nearfield and farfield listing text files into the database.
//
// TODO: Includes a number of possibly obsolete helper functions:
// GetNominalAngles()
// GeneratePlot_NF()
// GeneratePlot_FF()
// MakePlot_NF()
// MakePlot_FF()
// MakePlot_FF2()
// -- these appear to be handled by the beameff_64 C program now and may not be needed anymore.
//

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');


class ScanDetails extends GenericTable {
    var $nominal_az;
    var $nominal_el;
    var $BeamEfficencies;
    var $fc; //facility key

    public function Initialize_ScanDetails($keyId,$in_fc){
        $this->dbconnection = site_getDbConnection();
        $this->fc = $in_fc;
        parent::Initialize("ScanDetails",$keyId,"keyId",$this->fc,'fkFacility');

        //Get keyId for Beam Efficiencies record for this scan
        $qbe = "SELECT keyBeamEfficiencies FROM BeamEfficiencies WHERE fkScanDetails = $keyId;";
        $rbe = @mysql_query($qbe,$this->dbconnection);
        $keyBE = @mysql_result($rbe,0);
        $this->BeamEfficencies = new GenericTable();
        $this->BeamEfficencies->Initialize("BeamEfficiencies",$keyBE,"keyBeamEfficiencies",$this->fc,'fkFacility');
    }

    public function GetNominalAngles(){
        $qBand = "SELECT band FROM ScanSetDetails
        WHERE keyId = ".$this->GetValue(fkScanSetDetails).";";
        $rband = @mysql_query($qBand,$this->dbconnection);
        $band = @mysql_result($rband,0);

        switch ($band) {
        case "1":
            $this->nominal_az = -1.7553;
            $this->nominal_el = -1.7553;
            break;
        case "2":
            $this->nominal_az = -1.7553;
            $this->nominal_el = 1.7553;
            break;
        case "3":
            $this->nominal_az = 0.323;
            $this->nominal_el = 1.811;
            break;
        case "4":
            $this->nominal_az = 0;
            $this->nominal_el = 0;
            break;
        case "5":
            $this->nominal_az = 1.6867;
            $this->nominal_el = 1.6867;
            break;
        case "6":
            $this->nominal_az = 1.6867;
            $this->nominal_el = -1.6867;
            break;
        case "7":
            $this->nominal_az = 0.974;
            $this->nominal_el = 0;
            break;
        case "8":
            $this->nominal_az = 0;
            $this->nominal_el = 0.974;
            break;
        case "9":
            $this->nominal_az = 0;
            $this->nominal_el = -0.974;
            break;
        case "10":
            $this->nominal_az = 0;
            $this->nominal_el = 0;
            break;
        }
    }

    public function UploadListing_Nearfield($filename_nf){
        $q_delete = "DELETE FROM BeamListings_nearfield WHERE fkScanDetails = $this->keyId;";
        $r_delete = @mysql_query($q_delete,$this->dbconnection);
        $nf_filecontents = file($filename_nf);
        $tempcount = 0;
        $startcounting=false;
        for($i=0; $i<sizeof($nf_filecontents); $i++) {
            $line_data = trim($nf_filecontents[$i]);
            $tempArray   = explode(" ", $line_data);
            $NFArray = RemoveSpaces($tempArray);

            if($startcounting){
                $qInsert = "INSERT INTO BeamListings_nearfield(fkScanDetails,x,y,amp,phase)
                VALUES($this->keyId,'$NFArray[0]', '$NFArray[1]','$NFArray[2]', '$NFArray[3]')";
                $Insert=@mysql_query($qInsert,$this->dbconnection);
            }
            if (strstr($line_data,"line:") != ""){
                $startcounting = true;
            }
        }
        unlink($filename_nf);
        fclose($nf_filecontents);
    }

    public function UploadListing_Farfield($filename_ff){
        $q_delete = "DELETE FROM BeamListings_farfield WHERE fkScanDetails = $this->keyId;";
        $r_delete = @mysql_query($q_delete,$this->dbconnection);
        $ff_filecontents = file($filename_ff);
        $tempcount = 0;
        $startcounting=false;
        for($i=0; $i<sizeof($ff_filecontents); $i++) {
            $line_data = trim($ff_filecontents[$i]);
            $tempArray   = explode(" ", $line_data);
            $FFArray = RemoveSpaces($tempArray);

            if($startcounting){
                $qInsert = "INSERT INTO BeamListings_farfield(fkScanDetails,x,y,amp,phase)
                VALUES($this->keyId,'$FFArray[0]', '$FFArray[1]','$FFArray[2]', '$FFArray[3]')";
                $Insert=@mysql_query($qInsert,$this->dbconnection);
            }
            if (strstr($line_data,"line:") != ""){
                $startcounting = true;
            }
        }
        unlink($filename_ff);
        fclose($ff_filecontents);
    }

    public function GeneratePlot_NF(){
        if ($this->GetValue('copol') == "0"){
            //Get max amplitude difference between copol and crosspol
            //Take IF atten into account

            //get max amp of this crosspol scan
            $qmax1 = "SELECT MAX(amp)+". $this->GetValue('ifatten') ."
            FROM BeamListings_nearfield WHERE fkScanDetails = $this->keyId;";
            $rmax1 = @mysql_query($qmax1,$this->dbconnection);
            $max1 = @mysql_result($rmax1,0);

            //Get keyId of corresponding crosspol scan in this set
            $q2 = "SELECT keyId, ifatten FROM ScanDetails
            WHERE fkScanSetDetails = ". $this->GetValue('fkScanSetDetails') ."
            AND pol = ". $this->GetValue('pol') ." AND copol = 1;";
            $r2 = @mysql_query($q2,$this->dbconnection);
            $row2 = @mysql_fetch_array($r2);
            $id2 = $row2['keyId'];
            $ifatten2 = $row2['ifatten'];

            //get max amp of corresponding crosspol scan
            $qmax2 = "SELECT MAX(amp)+ $ifatten2
            FROM BeamListings_nearfield WHERE fkScanDetails = $id2;";
            $rmax2 = @mysql_query($qmax2,$this->dbconnection);
            $rowmax2 = @mysql_fetch_array($rmax2);
            $max2 = $rowmax2['amp'];

            //get difference between the two values
            $ampdiff = abs($max1 - $max2);

            $this->MakePlot_NF("Amplitude",$ampdiff);
            $this->MakePlot_NF("Phase");
        }
        if ($this->GetValue('copol') == "1"){
            $this->MakePlot_NF("Amplitude");
            $this->MakePlot_NF("Phase");
        }
    }


    public function GeneratePlot_FF(){
        $this->GetNominalAngles();

        if ($this->GetValue('copol') == "0"){
            //Get max amplitude difference between copol and crosspol
            //Take IF atten into account

            //get max amp of this crosspol scan
            $qmax1 = "SELECT MAX(amp)+". $this->GetValue('ifatten') ."
            FROM BeamListings_farfield WHERE fkScanDetails = $this->keyId;";
            $rmax1 = @mysql_query($qmax1,$this->dbconnection);
            $max1 = @mysql_result($rmax1,0);

            //Get keyId of corresponding crosspol scan in this set
            $q2 = "SELECT keyId, ifatten FROM ScanDetails
            WHERE fkScanSetDetails = ". $this->GetValue('fkScanSetDetails') ."
            AND pol = ". $this->GetValue('pol') ." AND copol = 1;";
            $r2 = @mysql_query($q2,$this->dbconnection);
            $row2 = @mysql_fetch_array($r2);
            $id2 = $row2['keyId'];
            $ifatten2 = $row2['ifatten'];

            //get max amp of corresponding crosspol scan
            $qmax2 = "SELECT MAX(amp)+ $ifatten2
            FROM BeamListings_farfield WHERE fkScanDetails = $id2;";
            $rmax2 = @mysql_query($qmax2,$this->dbconnection);
            $rowmax2 = @mysql_fetch_array($rmax2);
            $max2 = $rowmax2['amp'];

            //get difference between the two values
            $ampdiff = abs($max1 - $max2);
            $this->MakePlot_FF2("Amplitude",$ampdiff);
            $this->MakePlot_FF("Phase");
        }
        if ($this->GetValue('copol') == "1"){
            $this->MakePlot_FF2("Amplitude");
             $this->MakePlot_FF("Phase");
        }
    }

    public function MakePlot_NF($amp_or_phase, $ampdiff=0){
        $GNUPLOT = '/usr/bin/gnuplot';
        $pol = $this->GetValue('pol');
        $copol_or_xpol = "Crosspol";
        if ($this->GetValue('copol') == "1"){
            $copol_or_xpol = "Copol";
        }


        //Create data file from database
        $data_file = "/home/www.cv.nrao.edu/active/php-internal/ntc/Tasks/DatabaseUI/www/meas/files/plotdata.txt";
        unlink($data_file);

        $q="SELECT MAX(amp) FROM BeamListings_nearfield WHERE fkScanDetails=$this->keyId;";
        $r=@mysql_query($q,$this->dbconnection);
        $MaxAmp=@mysql_result($r,0);

        $q="SELECT x,y,(amp + ABS($MaxAmp) - $ampdiff),phase FROM BeamListings_nearfield
            WHERE fkScanDetails = $this->keyId
            ORDER BY y ASC, x ASC;";

        $r=@mysql_query($q,$this->dbconnection);

        $numpts = sqrt(@mysql_num_rows($r));
        $tempct = 0;

        $fh = fopen($data_file, 'w');
            while($row=@mysql_fetch_array($r)){
                fwrite($fh, "$row[0]\t$row[1]\t$row[2]\t$row[3]\t\r\n");
                $tempct++;
                if ($tempct >= $numpts){
                    fwrite($fh, "\r\n");
                    $tempct=0;
                }

            }
        fwrite($fh, "\r\n");
        fwrite($fh, "\r\n");
        fclose($fh);

        $plot_command_file = "/home/www.cv.nrao.edu/active/php-internal/ntc/Tasks/DatabaseUI/www/meas/files/command.txt";
        unlink($plot_command_file);
        $imagedirectory = "/home/www.cv.nrao.edu/active/php-internal/ntc/Tasks/DatabaseUI/www/meas/files/";

        if ($amp_or_phase == "Amplitude"){
            $imagename = "nfamp_" . date("Ymd_G_i_s") . ".png";
            $this->SetValue('nf_amp_image',"http://www.cv.nrao.edu/php-internal/ntc/Tasks/DatabaseUI/www/meas/files/$imagename");
        }
        if ($amp_or_phase == "Phase"){
            $imagename = "nfphase_" . date("Ymd_G_i_s") . ".png";
            $this->SetValue('nf_phase_image',"http://www.cv.nrao.edu/php-internal/ntc/Tasks/DatabaseUI/www/meas/files/$imagename");
        }

        $imagepath = $imagedirectory . $imagename;
        $plot_title = "Nearfield $amp_or_phase, $copol_or_xpol Pol $pol";
        $fk_cable = parent::GetValue('keyId');
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 500, 500 crop\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set xlabel 'X(m)'\r\n");
        fwrite($fh, "set ylabel 'Y(m)'\r\n");
        fwrite($fh, "set palette model RGB defined (-50 'purple', -40 'blue', -30 'green', -20 'yellow', -10 'orange', 0 'red')\r\n");
        fwrite($fh, "set cblabel 'Nearfield Amplitude (dB)'\r\n");
        fwrite($fh, "set view 0,0\r\n");
        fwrite($fh, "set pm3d map\r\n");
        fwrite($fh, "set size square\r\n");

        if ($amp_or_phase == "Amplitude"){
            fwrite($fh, "set cbrange [-50:0]\r\n");
            fwrite($fh, "set xtics 0.015\r\n");
            fwrite($fh, "set ytics 0.015\r\n");
            fwrite($fh, "splot '$data_file' using 1:2:3 title ''\r\n");
        }
        if ($amp_or_phase == "Phase"){
            fwrite($fh, "set cbrange [-180:180]\r\n");
            fwrite($fh, "set xtics 0.015\r\n");
            fwrite($fh, "set ytics 0.015\r\n");
            fwrite($fh, "splot '$data_file' using 1:2:4 title ''\r\n");
        }

        fwrite($fh, "\r\n");
        fclose($fh);
        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);

        //unlink($data_file);
        //unlink($plot_command_file);
        parent::Update();
    }

    public function MakePlot_FF($amp_or_phase,$ampdiff=0){
        $GNUPLOT = '/usr/bin/gnuplot';
        $pol = $this->GetValue('pol');
        $copol_or_xpol = "Crosspol";
        if ($this->GetValue('copol') == "1"){
            $copol_or_xpol = "Copol";
        }


        //Create data file from database
        $data_file = "/home/www.cv.nrao.edu/active/php-internal/ntc/Tasks/DatabaseUI/www/meas/files/plotdata.txt";
        unlink($data_file);

        $q="SELECT MAX(amp) FROM BeamListings_farfield WHERE fkScanDetails=$this->keyId;";
        $r=@mysql_query($q,$this->dbconnection);
        $MaxAmp=@mysql_result($r,0);

        $q="SELECT x,y,(amp + ABS($MaxAmp) - $ampdiff),phase FROM BeamListings_farfield
            WHERE fkScanDetails = $this->keyId
            ORDER BY y ASC, x ASC;";
        $r=@mysql_query($q,$this->dbconnection);

        $numpts = sqrt(@mysql_num_rows($r));
        $tempct = 0;

        $fh = fopen($data_file, 'w');
            while($row=@mysql_fetch_array($r)){
                fwrite($fh, "$row[0]\t$row[1]\t$row[2]\t$row[3]\t\r\n");
                $tempct++;
                if ($tempct >= $numpts){
                    fwrite($fh, "\r\n");
                    $tempct=0;
                }

            }
        fwrite($fh, "\r\n");
        fwrite($fh, "\r\n");
        fclose($fh);

        $plot_command_file = "/home/www.cv.nrao.edu/active/php-internal/ntc/Tasks/DatabaseUI/www/meas/files/command.txt";
        unlink($plot_command_file);
        $imagedirectory = "/home/www.cv.nrao.edu/active/php-internal/ntc/Tasks/DatabaseUI/www/meas/files/";


        if ($amp_or_phase == "Amplitude"){
            $imagename = "ffamp_" . date("Ymd_G_i_s") . ".png";
            $this->SetValue('ff_amp_image',"http://www.cv.nrao.edu/php-internal/ntc/Tasks/DatabaseUI/www/meas/files/$imagename");
        }
        if ($amp_or_phase == "Phase"){
            $imagename = "ffphase_" . date("Ymd_G_i_s") . ".png";
            $this->SetValue('ff_phase_image',"http://www.cv.nrao.edu/php-internal/ntc/Tasks/DatabaseUI/www/meas/files/$imagename");
        }

        $imagepath = $imagedirectory . $imagename;
        $plot_title = "Farfield $amp_or_phase, $copol_or_xpol Pol $pol";
        $fk_cable = parent::GetValue('keyId');
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 500, 500 crop\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set xlabel 'AZ (deg)'\r\n");
        fwrite($fh, "set ylabel 'EL (deg)'\r\n");
        fwrite($fh, "set palette model RGB defined (-50 'purple', -40 'blue', -30 'green', -20 'yellow', -10 'orange', 0 'red')\r\n");
        fwrite($fh, "set cblabel 'Farfield Amplitude (dB)'\r\n");
        fwrite($fh, "set view 0,0\r\n");
        fwrite($fh, "set pm3d map\r\n");
        fwrite($fh, "set size square\r\n");

        if ($amp_or_phase == "Amplitude"){
            fwrite($fh, "set cbrange [-50:0]\r\n");
            //fwrite($fh, "set xtics 0.015\r\n");
            //fwrite($fh, "set ytics 0.015\r\n");
            fwrite($fh, "splot '$data_file' using 1:2:3 title ''\r\n");
        }
        if ($amp_or_phase == "Phase"){
            fwrite($fh, "set cbrange [-180:180]\r\n");
            //fwrite($fh, "set xtics 0.015\r\n");
            //fwrite($fh, "set ytics 0.015\r\n");
            fwrite($fh, "splot '$data_file' using 1:2:4 title ''\r\n");
        }

        fwrite($fh, "\r\n");
        fclose($fh);

        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);

        //unlink($data_file);
        //unlink($plot_command_file);
        parent::Update();
    }


    public function MakePlot_FF2($amp_or_phase,$ampdiff=0){
        $GNUPLOT = '/usr/bin/gnuplot';
        $pol = $this->GetValue('pol');
        $copol_or_xpol = "Crosspol";
        if ($this->GetValue('copol') == "1"){
            $copol_or_xpol = "Copol";
        }

        //Create data file from database
        $data_file = "/home/www.cv.nrao.edu/active/php-internal/ntc/Tasks/DatabaseUI/www/meas/files/plotdata.txt";
        unlink($data_file);

        $q="SELECT MAX(amp) FROM BeamListings_farfield WHERE fkScanDetails=$this->keyId;";
        $r=@mysql_query($q,$this->dbconnection);
        $MaxAmp=@mysql_result($r,0);

        $q="SELECT x,y,(amp + ABS($MaxAmp) - $ampdiff),phase FROM BeamListings_farfield
            WHERE fkScanDetails = $this->keyId
            ORDER BY y ASC, x ASC;";
        $r=@mysql_query($q,$this->dbconnection);

        $numpts = sqrt(@mysql_num_rows($r));
        $tempct = 0;

        $fh = fopen($data_file, 'w');
            while($row=@mysql_fetch_array($r)){
                fwrite($fh, "$row[0]\t$row[1]\t$row[2]\t$row[3]\t\r\n");
                $tempct++;
                if ($tempct >= $numpts){
                    fwrite($fh, "\r\n");
                    $tempct=0;
                }

            }
        fwrite($fh, "\r\n");
        fwrite($fh, "\r\n");
        fclose($fh);

        $plot_command_file = "/home/www.cv.nrao.edu/active/php-internal/ntc/Tasks/DatabaseUI/www/meas/files/commandff.txt";
        unlink($plot_command_file);
        $imagedirectory = "/home/www.cv.nrao.edu/active/php-internal/ntc/Tasks/DatabaseUI/www/meas/files/";


        if ($amp_or_phase == "Amplitude"){
            $imagename = "ffamp_" . date("Ymd_G_i_s") . ".png";
            $this->SetValue('ff_amp_image',"http://www.cv.nrao.edu/php-internal/ntc/Tasks/DatabaseUI/www/meas/files/$imagename");
        }
        if ($amp_or_phase == "Phase"){
            $imagename = "ffphase_" . date("Ymd_G_i_s") . ".png";
            $this->SetValue('ff_phase_image',"http://www.cv.nrao.edu/php-internal/ntc/Tasks/DatabaseUI/www/meas/files/$imagename");
        }

        $imagepath = $imagedirectory . $imagename;
        $plot_title = "Farfield $amp_or_phase, $copol_or_xpol Pol $pol";
        $fk_cable = parent::GetValue('keyId');
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 500, 500 crop\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set xlabel 'AZ (deg)'\r\n");
        fwrite($fh, "set ylabel 'EL (deg)'\r\n");
        fwrite($fh, "set palette model RGB defined (-50 'purple', -40 'blue', -30 'green', -20 'yellow', -10 'orange', 0 'red')\r\n");
        fwrite($fh, "set cblabel 'Farfield Amplitude (dB)'\r\n");
        fwrite($fh, "set view 0,0\r\n");
        fwrite($fh, "set pm3d map\r\n");
        fwrite($fh, "set size square\r\n");

        fwrite($fh,"set parametric\n");
        fwrite($fh,"set angles degrees \n");
        fwrite($fh,"set urange [0:360]\n");



        if ($amp_or_phase == "Amplitude"){
            fwrite($fh, "set cbrange [-50:0]\r\n");
            //fwrite($fh, "set xtics 0.015\r\n");
            //fwrite($fh, "set ytics 0.015\r\n");

            fwrite($fh, "splot '$data_file' using 1:2:3 title '', $this->nominal_az + 3.58*cos(u),$this->nominal_el + 3.58*sin(u),1 notitle linetype 0\r\n");
        }
        if ($amp_or_phase == "Phase"){
            fwrite($fh, "set cbrange [-180:180]\r\n");
            //fwrite($fh, "set xtics 0.015\r\n");
            //fwrite($fh, "set ytics 0.015\r\n");
            fwrite($fh, "splot '$data_file' using 1:2:3 title ''\r\n");
        }

        fwrite($fh, "\r\n");
        fclose($fh);

        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);

        //unlink($data_file);
        //unlink($plot_command_file);
        parent::Update();
    }


}

function RemoveSpaces($in_array){
    // utility function used in UploadListing_Nearfield() and UploadListing_Farfield() above.
    $index=0;
    for ($i=0;$i<count($in_array);$i++){
        if ($in_array[$i] != ""){
            $NewArray[$index]=$in_array[$i];
            $index++;
        }
    }
    return $NewArray;
}

?>