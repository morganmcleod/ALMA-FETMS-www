<?php
// called from dbGrid.js
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_dbConnect);

$band=$_GET['band'];

$q = "select keyFacility, keyId, LPAD(SN, 2, '0') AS SN, Band, TS
     FROM FE_Components
     WHERE Band LIKE '$band'
     AND Band <> 0
     AND fkFE_ComponentType = 11
     ORDER BY SN ASC;";

$r = mysqli_query($link, $q);


$outstring = "[";

$rowcount = 0;
$bgcolor = "";
while ($row = mysqli_fetch_array($r)){
    $bgcolor=($bgcolor == 'blue-row' ? 'alt-row' : 'blue-row');


//     $qfd = "SELECT Description, Notes FROM Locations
//             WHERE keyId = ".$row['keyFacility'].";";
//     $rfd = mysqli_query($link, $qfd);
//     $facdescr = ADAPT_mysqli_result($rfd,0,0) . " (";
//     $facdescr .= ADAPT_mysqli_result($rfd,0,1) . " )";

    if ($rowcount == 0 ){
        $outstring .= "{'SN':'".$row['SN']."',";
    }
    if ($rowcount > 0 ){
        $outstring .= ",{'SN':'".$row['SN']."',";
    }

    $outstring .= "'Band':'".$row['Band']."',";
    $outstring .= "'TS':'".$row['TS']."',";
    $outstring .= "'keyId':'".$row['keyId']."',";
//     $outstring .= "'Facility':'$facdescr',";
    $outstring .= "'keyFacility':'".$row['keyFacility']."',";
    $outstring .= "'bgcolor':'$bgcolor'}";

    $rowcount += 1;
}
$outstring .= "]";
echo $outstring;

?>