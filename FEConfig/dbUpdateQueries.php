<?php

$er = error_reporting();
error_reporting($er ^ E_NOTICE);

class dbUpdateQueries
{
    function UpdateFrontEnd($FrontEndArray,$keyFE)
    {
        $update_fetable=mysql_query("Update Front_Ends SET ESN='$FrontEndArray[cansn]' WHERE
                                   keyFrontEnds=(SELECT fkFront_Ends FROM FE_Config WHERE keyFEConfig='$keyFE')")
        or die("Could not update Front Ends table" .mysql_error());
    }
    function UpdateComponents($ComponentArray)
    {
        $updateFEComponents=mysql_query("UPDATE FE_Components SET ESN1='$ComponentArray[esn1]',
        ESN2='$ComponentArray[esn2]',Description='$ComponentArray[descr]',
        Docs='$ComponentArray[link]'
        WHERE keyId='$ComponentArray[key]' AND keyFacility='$ComponentArray[facility]'")
        or die("Could not update FE_Components" .mysql_error());

        $AddStatLoc=mysql_query("INSERT INTO FE_StatusLocationAndNotes(fkFEComponents,fkLocationNames,fkStatusType,
        Notes,Updated_By,keyFacility,lnk_Data)
        VALUES('$ComponentArray[key]','$ComponentArray[location]','$ComponentArray[status]','$ComponentArray[notes]',
        '$ComponentArray[updatedby]','$ComponentArray[facility]','$ComponentArray[docs]')")
        or die("Could not insert into StatusLocationAndNotes" .mysql_error());

    }
}

error_reporting($er);