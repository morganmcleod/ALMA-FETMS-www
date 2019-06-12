<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.fecomponent.php');
require_once('dbGetQueries.php');

$fc = $_REQUEST['fc'];

$compkey=$_GET['compkey'];

$fecomp = new FEComponent();
$fecomp->Initialize_FEComponent($compkey, $fc);
$comptype = $fecomp->GetValue('fkFE_ComponentType');

$feconfig = $fecomp->FEConfig;
$fesn = $fecomp->FESN;

$getqueries=new dbGetQueries;

$getMaxKeyAndSN_query=$getqueries->getCompSN($compkey,$fc);
$comp_sn=ADAPT_mysqli_result($getMaxKeyAndSN_query,0,"SN");
$comp_max_key=ADAPT_mysqli_result($getMaxKeyAndSN_query,0,"MaxKey");

//get component type name
$getCompName=$getqueries->getcomponentName($comptype);

$getAllData_query=$getqueries->getPrevComponents($compkey,$fc);
while($data=mysqli_fetch_array($getAllData_query))
{
    $esn1=$data['ESN1'];
    $esn2=$data['ESN2'];
    $band=$data['Band'];
    $comp_type=$data['fkFE_ComponentType'];
}

$statloc_query=$getqueries->getStatusAndLocationComp($compkey,$fc);
if(mysqli_num_rows($statloc_query) > 0)
{
    $selected_stat=ADAPT_mysqli_result($statloc_query,0,"fkStatusType");
    $selected_loc=ADAPT_mysqli_result($statloc_query,0,"fkLocationNames");
}
$status_query=$getqueries->getStatusLocation(StatusTypes);
while($stat_rs=mysqli_fetch_array($status_query))
{
    $name=$stat_rs['Status'];
    $keystat=$stat_rs['keyStatusType'];
    if($keystat==$selected_stat)
    {
        $selected_s="selected";
    }
    else
    {
        $selected_s="";
    }
    $status_block .= "<option value=\"$keystat\" $selected_s>$name</option>";
}

//get location dropdown values.
$location_query=$getqueries->getStatusLocation(Locations);
while($loc_rs=mysqli_fetch_array($location_query))
{
    $name=$loc_rs['Description'];
    $keyloc=$loc_rs['keyId'];
    if($keyloc==$selected_loc)
    {
        $selected_l="selected";
    }
    else
    {
        $selected_l="";
    }
    $location_block .= "<option value=\"$keyloc\" $selected_l>$name</option>";
}

?>