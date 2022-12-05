<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_dbConnect);

$er = error_reporting();
error_reporting($er ^ E_NOTICE);

class dbInsertQueries {
    private $dbconnection;

    public function __construct() {
        $this->dbConnection = site_getDbConnection();
    }

    function InsertIntoFrontEnds($FEarray) {
        //called from AddFrontEnd.php
        $insert_frontends = mysqli_query($this->dbConnection, "INSERT INTO Front_Ends(SN,ESN,Docs,keyFacility,Description)
        VALUES('$FEarray[sn]','$FEarray[esn]','$FEarray[link]','$FEarray[facility]','$FEarray[description]')")
            or die("Could not insert into FrontEnds" . mysqli_error($this->dbConnection));

        return $insert_frontends;
    }
    function InsertIntoConfigLink($maxkey, $comp) {
        //called from AddFrontEnd.php
        $insert_configLink = mysqli_query($this->dbConnection, "INSERT INTO FE_ConfigLink(fkFE_Components,fkFE_Config) VALUES('$comp',
        '$maxkey')")
            or die("Could not insert into ConfigLink" . mysqli_error($this->dbConnection));
        return $insert_configLink;
    }
    function insertIntoStatLocAndNotes($notes, $updatedby, $status, $location, $link_data, $maxkey, $facility) {
        //called from AddFrontEnd.php, function InsertNewConfig() in thie file, CupdateFE.php
        $insert_notesetc = mysqli_query($this->dbConnection, "INSERT INTO FE_StatusLocationAndNotes(fkFEConfig,
        fkLocationNames,fkStatusType,Notes,lnk_Data,Updated_by,keyFacility)
        VALUES('$maxkey','$location','$status','$notes','$link_data','$updatedby','$facility')")
            or die("Could not get data" . mysqli_error($this->dbConnection));

        return $insert_notesetc;
    }
    function insertIntoFEConfig($maxkey, $fesn, $facility) {
        //called from AddFrontEnd.php
        $notes = "Initial configuration FE " . $fesn;


        $insert_feconfig = mysqli_query($this->dbConnection, "INSERT INTO FE_Config(fkFront_Ends,Description,keyFacility)
        VALUES('$maxkey','$notes','$facility')")
            or die("could not insert into FeConfig" . mysqli_error($this->dbConnection));

        return $insert_feconfig;
    }
    function InsertNewConfig($FrontEndArray, $componentArray, $keyFE) {
        //called from CupdateFE.php
        $getFEkey = mysqli_query($this->dbConnection, "SELECT fkFront_Ends FROM FE_Config WHERE keyFEConfig='$keyFE'")
            or die("Could not get feconfig" . mysqli_error($this->dbConnection));
        $fe_key = ADAPT_mysqli_result($getFEkey, 0, "fkFront_Ends");

        $getFESN = mysqli_query($this->dbConnection, "SELECT SN FROM Front_Ends WHERE keyFrontEnds='$fe_key'")
            or die("Could not get fesn" . mysqli_error($this->dbConnection));
        $fe_sn = ADAPT_mysqli_result($getFESN, 0, "SN");

        $insertNewConfig = mysqli_query($this->dbConnection, "INSERT INTO FE_Config(fkFront_Ends,Description)
                                      VALUES('$fe_key','Cold PAS Config for FE SN $fe_sn')")
            or die("Could not insert new config" . mysqli_error($this->dbConnection));

        $NewFEConfig_query = mysqli_query($this->dbConnection, "SELECT MAX(keyFEConfig) as NewConf FROM FE_Config WHERE fkFront_Ends='$fe_key'")
            or die("Could not get new config" . mysqli_error($this->dbConnection));
        $newconf = ADAPT_mysqli_result($NewFEConfig_query, 0, "NewConf");

        foreach ($componentArray as $value) {
            if ($value != "") {
                mysqli_query($this->dbConnection, "INSERT INTO FE_ConfigLink(fkFE_Components,fkFE_Config)
                          VALUES('$value','$newconf')")
                    or die("Could not insert data" . mysqli_error($this->dbConnection));
            }
        }

        $update_fetable = mysqli_query($this->dbConnection, "Update Front_Ends SET ESN='$FrontEndArray[cansn]' WHERE keyFrontEnds='$fe_key'");

        return $newconf;
    }
    function insertIntoComponents($component_array) {
        $insertIntoComponents = mysqli_query($this->dbConnection, "INSERT INTO FE_Components(fkFE_ComponentType,SN,ESN1,ESN2,
                                        Component_Description,Docs,Band)
                             VALUES('$component_array[type]','$component_array[sn]','$component_array[ESN1]',
                            '$component_array[ESN2]','$component_array[compdescr]','$component_array[docs]',
                           '$component_array[band]')")
            or die("Could not insert into FE_Components" . mysqli_error($this->dbConnection));

        $Componentkey_query = mysqli_query($this->dbConnection, "SELECT keyId FROM FE_Components WHERE SN='$component_array[sn]' AND
                                        fkFE_ComponentType='$component_array[type]' AND Band='$component_array[band]' ORDER BY
                                        keyId DESC LIMIT 1")
            or die("Could not get keyId" . mysqli_error($this->dbConnection));

        $Component_key = ADAPT_mysqli_result($Componentkey_query, 0, "keyId");

        $insertintoStatLoc = mysqli_query($this->dbConnection, "INSERT INTO FE_StatusLocationAndNotes(fkFEComponents,fkLocationNames,
                                        fkStatusType,Notes,lnk_Data,Updated_By)
                                        VALUES('$Component_key','$component_array[location]','$component_array[status]' ,
                                           '$component_array[notes]','$component_array[link]','$component_array[updatedby]')")
            or die("Could not insert into StatusLocationAndNotes" . mysqli_error($this->dbConnection));

        return $insertintoStatLoc;
    }
}

error_reporting($er);
