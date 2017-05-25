<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.cca.php');
require_once($site_classes . '/class.cryostat.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_classes . '/class.wca.php');
require_once("dbGetQueries.php");

$fc=(isset($_REQUEST['fc'])) ? $_REQUEST['fc'] : '40';
$band=(isset($_POST['band'])) ? $_POST['band'] : '';
$comp_type=(isset($_POST['type'])) ? $_POST['type'] : '';
$selected_key=(isset($_POST['config'])) ? $_POST['config'] : '';
$tabtype=(isset($_POST['tabtype'])) ? $_POST['tabtype'] : '';

$fecomponent = new FEComponent();
$fecomponent->Initialize_FEComponent($selected_key, $fc, -1);

if ($tabtype == "testdata") {
    $fecomponent->DisplayTable_TestData();

} else if ($tabtype == "updateconfig") {
    $fecomponent->Display_ALLUpdateConfigForm_CCA();

} else if ($tabtype == 1) {
    // General configuration tab
    $getQuery=new dbGetQueries;
    $getCompConfig_query=$getQuery->getSelectedCompConfig($selected_key);
    while ($getFEComp=mysql_fetch_array($getCompConfig_query)) {
        $fecomponent->DisplayTable_ComponentInformation();
        $fecomponent->Display_Table_PreviousConfigurations();
    }

} else if ($comp_type == 20) {
    // CCA tab:
    $cca = new CCA();
    $cca->Initialize_CCA($selected_key,$fc);
    switch($tabtype){
        case 2:
            $cca->Display_MixerParams();
            break;

        case 3:
            $cca->Display_PreampParams();
            break;

        case 4:
            $cca->Display_TempSensors();
            break;
    }
    unset($cca);

} else if ($comp_type == 6) {
    // Cryostat tab:
    $cryo = new Cryostat();
    $cryo->Initialize_Cryostat($selected_key,$fc);
    switch($tabtype){
        case 2:
            $cryo->DisplayTempSenors();

            break;
        case 5:
            $cryo->Display_uploadform_Notempsensors();
            break;
    }
    unset($cryo);

} else if ($comp_type == 11) {
    // WCA tab:
    $wca = new WCA();
    $wca->Initialize_WCA($selected_key, $fc, WCA::INIT_ALL);
    switch($tabtype){
        case 2:
            $wca->DisplayMainDataNonEdit();
            break;

        case 3:
            $wca->Compute_MaxSafePowerLevels(TRUE);
            $wca->Display_MaxSafePowerLevels();
            break;

        case 4:
            $wca->Display_LOParams();
            break;
    }
    unset($wca);
}

?>