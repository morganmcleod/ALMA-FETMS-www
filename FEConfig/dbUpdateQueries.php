<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_dbConnect);

$er = error_reporting();
error_reporting($er ^ E_NOTICE);

class dbUpdateQueries {
    private $dbConnection;

    public function __construct() {
        $this->dbConnection = site_getDbConnection();
    }

    function UpdateFrontEnd($FrontEndArray, $keyFE) {
        $update_fetable = mysqli_query($this->dbConnection, "Update Front_Ends SET ESN='$FrontEndArray[cansn]' WHERE
                                   keyFrontEnds=(SELECT fkFront_Ends FROM FE_Config WHERE keyFEConfig='$keyFE')")
            or die("Could not update Front Ends table" . mysqli_error($this->dbConnection));
    }
    function UpdateComponents($ComponentArray) {
        $updateFEComponents = mysqli_query($this->dbConnection, "UPDATE FE_Components SET ESN1='$ComponentArray[esn1]',
        ESN2='$ComponentArray[esn2]',Description='$ComponentArray[descr]',
        Docs='$ComponentArray[link]'
        WHERE keyId='$ComponentArray[key]' AND keyFacility='$ComponentArray[facility]'")
            or die("Could not update FE_Components" . mysqli_error($this->dbConnection));

        $AddStatLoc = mysqli_query($this->dbConnection, "INSERT INTO FE_StatusLocationAndNotes(fkFEComponents,fkLocationNames,fkStatusType,
        Notes,Updated_By,keyFacility,lnk_Data)
        VALUES('$ComponentArray[key]','$ComponentArray[location]','$ComponentArray[status]','$ComponentArray[notes]',
        '$ComponentArray[updatedby]','$ComponentArray[facility]','$ComponentArray[docs]')")
            or die("Could not insert into StatusLocationAndNotes" . mysqli_error($this->dbConnection));
    }
}

error_reporting($er);
