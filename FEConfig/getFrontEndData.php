<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.frontend.php');
require_once($site_classes . '/class.testdata_table.php');
require_once($site_FEConfig . '/HelperFunctions.php');
require_once($site_dbConnect);

$band = $_POST['band'];
$feConfig = $_POST['key'];
$facility = $_REQUEST['fc'];

$fe = new FrontEnd(NULL, $facility, FrontEnd::INIT_NONE, $feConfig);
$fc   = $fe->keyFacility;
$fesn = $fe->SN;

if ($band == 100) {
    //Front End tab panel
?>
    <table>
        <tr>
            <td>
                <div style="width:280px">
                    <table id="table5">
                        <tr>
                            <th>SN:</th>
                            <td bgcolor='#ff0000'><?php echo $fe->SN; ?></td>
                        </tr>
                        <tr>
                            <th>TS:
            </td>
            <td><?php echo $fe->TS; ?></td>
        </tr>
        <tr>
            <th>Configuraton#:</td>
            <td><?php echo $feConfig; ?></td>
        </tr>
        <tr>
            <th>CAN SN:</td>
            <td><?php echo $fe->GetValue('ESN'); ?></td>
        </tr>
        <?php
        if (strlen($fe->GetValue('Docs')) > 1) {
            echo "<tr><th>Docs:</td><td><a href='" . FixHyperlink($fe->GetValue('Docs')) . "'>Link </a></td></tr>";
        } else {
            echo "<tr><th>Docs:</td><td></td></tr>";
        }
        ?>
        <tr>
            <th>Description:</td>
            <td><?php echo $fe->GetValue('Description'); ?></td>
        </tr>
    </table>
    </div>
    </td>
    </tr>
    </table>

<?php

} else {
    // Components, or Band tab
    if ($band == "other")
        $band = 0;

    // Display components matching band:
    $fe->DisplayTable_ComponentList($band);

    // Display HC and PAS reference data matching band:
    $fe->DisplayTable_PAITestData_Summary($band);

    // For all bands, including band "0" show PAI data:
    $td = new TestDataTable($band);
    $td->setFrontEnd($fe->keyId);
    $td->DisplayAllMatching();
    unset($td);
}

?>

<br><br>

<?php
unset($fe);
?>