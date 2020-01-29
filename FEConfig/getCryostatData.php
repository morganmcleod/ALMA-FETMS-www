<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_dbConnect);
$dbconnection = site_getDbConnection();

$band=$_POST['band'];
$feConfig=$_POST['key'];
$facility=40;

$q = "SELECT DISTINCT(FE_Components.keyId)
    FROM FE_Components, FE_ConfigLink, FE_Config
    WHERE
    FE_Components.fkFE_ComponentType = 6
    AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
    AND FE_ConfigLink.fkFE_Config = $feConfig;";
$r = mysqli_query($dbconnection, $q);

$component = new FEComponent();
$component->Initialize_FEComponent(ADAPT_mysqli_result($r,0,0), $facility);

echo "component SN= " . $component->GetValue('SN') ."<br>";

?>

<div style='width:750px'>
<table id = 'table1'>
<tr class = 'alt'>
    <th colspan='5'>COMPONENTS</th>
</tr>
<tr>
    <th>DESCRIPTION</th>
    <th>SN</th>
    <th>ESN</th>
    <th>ESN2</th>
    <th>TS</th>
</tr>

<?php

while($comp=mysqli_fetch_array($getComponents)) {
    $trclass = ($trclass=="" ? 'class="alt"' : "");

?>

    <tr><td width='300px' class="bb" style="text-align: left";><?php echo $comp['Notes'];?></td>
    <td class="bb" width='50px'>
    <a href="ShowComponents.php?conf=<?php echo $comp['keyId'] ;?>&band=<?php echo $band ;?>&compType=<?php echo $comp['fkFE_ComponentType'];?>">
    <?php echo $comp['SN']; ?></a></td>
    <td class="bb"><?php echo $comp['ESN1']; ?></td>
    <td class="bb"><?php echo $comp['ESN2'];?></td>
    <td class="bb"><?php echo $comp['TS'];?></td>
    </tr>

<?php

}

?>

</table>

<?php

$component->DisplayTable_TestData();

?>

<br><br>
</div>

