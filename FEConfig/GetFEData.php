<?php
// called from dbGrid.js

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.frontend.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_dbConnect);
require('dbGetQueries.php');

$ctype=$_GET['ctype'];

$getqueries=new dbGetQueries;

if($ctype==100)
{
    //Front Ends
    $outstring = "[";
    $rowcount = 0;
    $Notes = "";

    $qfe = "SELECT keyFrontEnds, keyFacility FROM Front_Ends ORDER BY SN ASC;";
    $rfe = @mysql_query($qfe,$db);

    while ($row = @mysql_fetch_array($rfe)){

        $fe = new FrontEnd();
        $fe->Initialize_FrontEnd($row[0],$row[1],-1);

        if ($rowcount == 0 ){
            $outstring .= "{'SN':'".$fe->GetValue('SN')."',";
        }
        if ($rowcount > 0 ){
            $outstring .= ",{'SN':'".$fe->GetValue('SN')."',";
        }
        $outstring .= "'config':'".$fe->feconfig->keyId."',";    ;

        if (isset($fe -> fesln)) {
            if ($fe -> fesln -> keyId > 0) {
                $outstring .= "'Location':'".$fe->fesln->location."',";
                $outstring .= "'Status':'".$fe->fesln->status."',";
                $outstring .= "'Updated_By':'".$fe->fesln->GetValue('Updated_By')."',";
                $Notes = @mysql_real_escape_string(stripslashes($fe->fesln->GetValue('Notes')));
            }
            if ($fe -> fesln -> keyId < 1){
                $outstring .= "'Location':'',";
                $outstring .= "'Status':'',";
                $outstring .= "'Updated_By':'',";
                $Notes = '';
            }
        }
        $outstring .= "'Docs':'" . FixHyperLink($fe->GetValue('Docs')). "',";
        $outstring .= "'TS':'".$fe->GetValue('TS')."',";
        $outstring .= "'keyFacility':'".$fe->GetValue('keyFacility')."',";
        $outstring .= "'Notes':'$Notes'}";

        unset($fe);
        $rowcount += 1;
    }
    $outstring .= "]";
    echo $outstring;

}
else
{
    //Components

    /*
    Band
    SN
    TS
    Updated By
    Status
    Location
    Notes
    Front End
     */
    //$l = new Logger('GetFEData.txt');
    //$l->WriteLogFile("test");

    $outstring = "[";
    $rowcount = 0;
    $Notes = "";

    $qfe = "SELECT keyId, keyFacility FROM FE_Components
            WHERE fkFE_ComponentType = $ctype
            ORDER BY Band ASC, (SN+0) ASC, keyId DESC;";
    $rfe = @mysql_query($qfe,$db);

    //$l->WriteLogFile($qfe);

    while ($row = @mysql_fetch_array($rfe)){

        $c = new FEComponent();
        $c->Initialize_FEComponent($row[0],$row[1]);

        $Duplicate = 0;
        if ($c->GetValue('Band') == $tempBand){
            if ($c->GetValue('SN') == $tempSN){
                $Duplicate = 1;
            }
        }


        if ($Duplicate == 0){
        if ($rowcount == 0 ){
            $outstring .= "{'SN':'".$c->GetValue('SN')."',";
        }
        if ($rowcount > 0 ){
            $outstring .= ",{'SN':'".$c->GetValue('SN')."',";
        }
        $outstring .= "'config':'".$c->keyId."',";    ;

        if ($c->sln->keyId > 0){
            $outstring .= "'Location':'".$c->sln->location."',";
            $outstring .= "'Status':'".$c->sln->status."',";
            $outstring .= "'Updated_By':'".$c->sln->GetValue('Updated_By')."',";
            $Notes = @mysql_real_escape_string($c->sln->GetValue('Notes'));
            //$l->WriteLogFile("SLN: " . $c->sln->location . "," . $c->sln->status . "," . $c->sln->GetValue('Notes'));
        }

        //

        if ($c->sln->keyId < 1){
            $outstring .= "'Location':'',";
            $outstring .= "'Status':'',";
            $outstring .= "'Updated_By':'',";
            $Notes = '';
        }
        $outstring .= "'Docs':'" . FixHyperLink($c->GetValue('Link1')). "',";
        $outstring .= "'TS':'".$c->GetValue('TS')."',";
        $outstring .= "'Band':'".$c->GetValue('Band')."',";
        $outstring .= "'keyFacility':'".$c->GetValue('keyFacility')."',";
        $outstring .= "'FESN':'".$c->FESN."',";
        $outstring .= "'Notes':'$Notes'}";

        $tempBand = $c->GetValue('Band');
        $tempSN   = $c->GetValue('SN');

        unset($c);
        $rowcount += 1;
        }
    }
    $outstring .= "]";

    //$l->WriteLogFile($outstring);

    echo $outstring;
}
?>
