<?php
//called from AddCompsForm.js
//dnagaraj 2011-03-23

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_config_main);
require_once($site_dbConnect);

//Since new components are being created, the facility code
//is not being passed in. It is defined in config_main.php

$command=$_POST['cmd'];
$icount=0;

if($command=="getCombo")
{
    //get Component_Type combo box values.
    $combo_data=mysqli_query($link, "SELECT Description FROM ComponentTypes ORDER BY Description ASC")
    or die("Could not get combo box data" .mysql_error());

    while($combolist=mysqli_fetch_object($combo_data))
    {
        $combo[]=$combolist;
    }
    echo json_encode($combo);
}

else if($command =="saveData")
{
    //get submitted data
    $data=$_POST['data'];
    if(get_magic_quotes_gpc())
    {
        $data=stripslashes($data);
    }
    $obj = json_decode($data);

    $error=0;

    foreach($obj AS $array)
    {
        //reset($comps);
        $comps=array();
        foreach($array as $id=>$value)
        {
            $comps[$id]=$value;
        }

        //check if new record
        if($comps['newRecord']==1 || $comps['newRecord'] == true)
        {

            $desc = $comps['Description'];

            //get Component id for given component description.
            $compType=mysqli_query($link, "SELECT keyId FROM ComponentTypes WHERE Description='$desc'")
            or die("Could not get component type id" .mysql_error());

            if(mysqli_num_rows($compType) > 0)
            {
                $type_id=ADAPT_mysqli_result($compType,0,'keyId');

                $sn = $comps['SN'];
                $band = $comps['Band'];

                //check if record already exists in the database
                if($band != "" || $band != 0)
                {
                    $checkduplicates=mysqli_query($link, "SELECT keyId FROM FE_Components
                    WHERE fkFE_ComponentType='$type_id' AND keyFacility='$fc'
                    AND SN='$sn' AND Band='$band'");
                }
                else
                {
                    $checkduplicates=mysqli_query($link, "SELECT keyId FROM FE_Components WHERE
                            fkFE_ComponentType='$type_id' AND keyFacility='$fc'
                            AND SN='$sn' AND (Band is NULL OR Band='0')");
                }
                if(mysqli_num_rows($checkduplicates) > 0)
                {
                    $error=1;
                    $duplicate_comps_type[]=$comps['Description'];
                    $duplicate_comps_sn[]=$comps['SN'];
                }
                //if record does not exist in database insert it.
                else
                {
                    $esn1 = isset($comps['ESN1']) ? $comps['ESN1'] : '';
                    $esn2 = isset($comps['ESN2']) ? $comps['ESN2'] : '';
                    $pstat = isset($comps['Production_Status']) ? $comps['Production_Status'] : '';

                    $insertQuery=mysqli_query($link, "INSERT INTO FE_Components(fkFE_ComponentType,
                    SN,ESN1,ESN2,Band,Production_Status,keyFacility)
                    VALUES('$type_id','$sn','$esn1','$esn2','$band','$pstat','$fc')")
                    or die("Could not insert data" .mysql_error());

                    $getKey_query=mysqli_query($link, "SELECT Max(keyId) AS maxkey FROM
                    FE_Components");
                    $inserted_key=ADAPT_mysqli_result($getKey_query,0,'maxkey');
                    setcookie("compcookie['$icount']",$inserted_key,time()+3600,'/');
                    $icount=$icount+1;
                }
            }
        }
    }//end for each object

    if($error==1) //if record is already in the database
    {
        $message="These records already exist in the database and therefore were not added: <br>";

        for($i=0; $i < count($duplicate_comps_type); $i++)
        {
            $message .= "Type: $duplicate_comps_type[$i]  SN: $duplicate_comps_sn[$i] <br>";

        }
        $pass_message="{message:'$message',success:true}";
        echo $pass_message;
    }
    else
    {
        $pass_message="{message:0,success:true}";
        echo $pass_message;

    }
}


?>