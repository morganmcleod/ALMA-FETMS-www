<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');

function getPASfileName($ifchannel, $band, $lofreq) {
    if($ifchannel == '0') {
        $pol=0; $sb=1;
    }
    else if($ifchannel == '1') {
        $pol=1; $sb=1;
    }
    else if($ifchannel == '2') {
        $pol=0; $sb=2;
    }
    else if($ifchannel == '3') {
        $pol=1; $sb=2;
    }

    $filename="Band" . $band . "-IF" . $ifchannel . "-Pol" . $pol . "-Sb" . $sb . "-" . $lofreq . ".txt";
    return $filename;
}

function generatePASfile($ifspec_keyId,$ifchannel,$band,$lofreq,$facility)
{
    $getIfSpecData_query=mysql_query("SELECT Freq_Hz,Power_dBm FROM IFSpectrum WHERE
            fkSubHeader='$ifspec_keyId' AND fkFacility='$facility' ORDER BY Freq_Hz")
    or die("Could not get IfSpectrum data" .mysql_error());

    include(site_get_config_main());

    $filename="$main_write_directory/PASData/" . getPASfileName($ifchannel, $band, $lofreq);
    $delimiter="\t";

    if(mysql_num_rows($getIfSpecData_query) > 0) {
        $file=fopen($filename,'w') or die("Cant create file");

        while($IfSpecData=mysql_fetch_array($getIfSpecData_query))
        {
            $data = $IfSpecData['Freq_Hz'];
            $data .= $delimiter . $IfSpecData['Power_dBm'];
            $data .= "\r\n";
            fwrite($file,$data);
        }

        fclose($file);
    }
    return;
}
