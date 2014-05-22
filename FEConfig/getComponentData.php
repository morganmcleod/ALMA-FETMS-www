<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.cca.php');
require_once($site_classes . '/class.cryostat.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_classes . '/class.wca.php');
require_once("dbGetQueries.php");

$fc=(isset($_REQUEST['fc'])) ? $_REQUEST['fc'] : '';
$band=(isset($_POST['band'])) ? $_POST['band'] : '';
$comp_type=(isset($_POST['band'])) ? $_POST['type'] : '';
$selected_key=(isset($_POST['band'])) ? $_POST['config'] : '';
$tabtype=(isset($_POST['band'])) ? $_POST['tabtype'] : '';

$fecomponent = new FEComponent();
$fecomponent->Initialize_FEComponent($selected_key,$fc, -1);

$getQuery=new dbGetQueries;
$getCompConfig_query=$getQuery->getSelectedCompConfig($selected_key);

if($tabtype == "testdata")
{
    $fecomponent->DisplayTable_TestData();
}
if($tabtype == "updateconfig")
{
    $fecomponent->Display_ALLUpdateConfigForm_CCA();
}
if($tabtype == 1)
{
    while($getFEComp=mysql_fetch_array($getCompConfig_query))
    {
        $comp_id=$getFEComp['keyId'];
        $ts=$getFEComp['TS'];
        $sn=$getFEComp['SN'];
        if ($sn == ''){
            $sn = "NA";
        }
        $band=$getFEComp['Band'];
        $descr=$getFEComp['Descr'];
        $esn1=$getFEComp['ESN1'];
        $esn2=$getFEComp['ESN2'];
        $docs=isset($getFEComp['Docs']) ? $getFEComp['Docs'] : "";

        if($docs== "")
        {
            $disabled= "disabled";
        }
        else
        {
            $disabled= "";
        }

        echo "<div>";

        $fecomponent->DisplayTable_ComponentInformation();
        $fecomponent->Display_Table_PreviousConfigurations();
    }
}

if($tabtype != 1)
{
    if($comp_type == 20)
    {
    //CCA
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
    }
    if($comp_type == 6)
    {
    //Cryostat
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
    }

    if($comp_type == 11)
    {
        $wca = new WCA();
        $wca->Initialize_WCA($selected_key,$fc);
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
}

?>