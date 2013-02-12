<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.dboperations.php');
require_once($site_classes . '/class.fecomponent.php');
require_once('dbUpdateQueries.php');
require_once('functions.php');
require_once('jsFunctions.php');

$fc = $_REQUEST['fc'];

$updatequery = new dbUpdateQueries;

if($_POST['submitornot'] == 1)
{
    $keyComp=$_POST['maxkey'];
    $notes=$_POST['notes'];
    $notes=addslashes($notes);
    $fc = $_REQUEST['fc'];

    $link=$_POST['docs'];
    if(substr($link,0,4) != "http")
    {
        //$link=modifyLink($link);
    }

    $component = new FEComponent();
    $component->Initialize_FEComponent($keyComp,$fc);
    $component->SetValue('Notes', addslashes($_REQUEST['notes']));
    $component->SetValue('keyFacility', $fc);
    $component->SetValue('Description', addslashes($_REQUEST['descr']));
    $component->SetValue('ESN1', addslashes($_REQUEST['esn1']));
    $component->SetValue('ESN2', addslashes($_REQUEST['esn2']));
    $component->SetValue('Docs', $link);
    $component->Update();

    $dbop = new DBOperations();


    $dbop->UpdateStatusLocationAndNotes_Component($fc, $_REQUEST['stat'], $_REQUEST['loc'],$_REQUEST['notes'],$component->keyId, $_REQUEST['updatedby'],$link);

    echo "<script type='text/javascript' language='JavaScript'>location.href='ShowComponents.php?conf=$keyComp&fc=$fc'</script>";

}
?>
