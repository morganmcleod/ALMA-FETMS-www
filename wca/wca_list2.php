<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_dbConnect);

include('header_js.php');

$Band = "%";
if (isset($_REQUEST['Band_selected'])){
    $Band = $_REQUEST['Band_selected'];
}

$qBand = "SELECT DISTINCT(Band) FROM FE_Components
WHERE fkFE_ComponentType = 11
AND Band <> 0
ORDER BY Band ASC;";

$rBand = @mysql_query($qBand,$db);

echo '
<div style="width:330px; margin:30px;">
<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
    echo "<select name='Band_selected' id='Band_selected' onChange='creategrid(this.value)'>";

    while($rowBand = @mysql_fetch_array($rBand)){
        if ($rowBand[0] == $Band){
            $option_band .= "<option value='$rowBand[0]' selected = 'selected'>Band $rowBand[0]</option>";
        }
        else{
            $option_band .= "<option value='$rowBand[0]'>Band $rowBand[0]</option>";
        }
    }
    $option_band .= "<option value='%'>All Bands</option>";
    echo $option_band;
    echo "</select>";
    echo '<div id="nocontenttoponly">
<div id="db-grid">
</div>
</div></form></div>';

?>
<div class='footer'>
    <div style="margin-left:30px;">
<?php
    include "../FEConfig/footer.php";
?>
	</div>
</div>
