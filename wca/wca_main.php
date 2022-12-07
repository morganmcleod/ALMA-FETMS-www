<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_dbConnect);
$dbConnection = site_getDbConnection();

include('header_js.php');
echo '<div style="margin-left:30px">';

echo 'To search for a WCA, enter the Band followed by serial number,<br>
      separated by a space.
      <br>For example, to see Band 9 SN 34, enter "9 34"';

if (isset($_REQUEST['band_sn'])){
    $band_sn = explode(" ",$_REQUEST['band_sn']);
    $Band = $band_sn[0];
    $SN = $band_sn[1];

    $q="SELECT keyFacility, MAX(keyId) FROM FE_Components
    WHERE band=$Band AND SN = $SN
    AND fkFE_ComponentType = 11;";
    $r = mysqli_query($dbConnection, $q);
    $row = mysqli_fetch_array($r);
    $fc = $row[0];
    $keyId = $row[1];

    if ($keyId == ""){
        echo "<br><br><font size = '+2' color = '#ff0000'>No WCA record found for band $Band, SN $SN.</font><br>";
    }
    if ($keyId != ""){
        echo '<meta http-equiv="Refresh" content="1;url=wca.php?fc='.$fc.'&keyId='. $keyId . '">';
    }
}

echo '</div>';

include('wca_search.php');

?>
<div class='footer' style="margin-top:20px;">
	<div style="margin-left:30px;">
<?php
    include "../FEConfig/footer.php";
?>
	</div>
</div>
</body>
</html>
