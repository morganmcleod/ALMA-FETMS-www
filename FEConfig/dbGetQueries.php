<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_dbConnect);
require_once('HelperFunctions.php');

class dbGetQueries {
    private $dbConnection;

    public function __construct() {
        $this->dbConnection = site_getDbConnection();
    }

    function getFrontEndConfig($feConfig, $facility) {
        //called from MupdateFE.php
        $getFrontConfig = mysqli_query($this->dbConnection, "SELECT * FROM Front_Ends WHERE keyFrontEnds=(SELECT fkFront_Ends FROM FE_Config
        WHERE keyFEConfig='$feConfig' AND keyFacility='$facility')")
            or die("Could not get data" . mysqli_error($this->dbConnection));
        return $getFrontConfig;
    }
    function getTwoDistinctNoCriteria($what1, $what2, $where, $orderby) {
        $this->tablename = $where;
        $getvals = mysqli_query($this->dbConnection, "SELECT $what1,max($what2) AS maxkey FROM $this->tablename
        GROUP BY $what1")
            or die("Could not get data from!" . mysqli_error($this->dbConnection));
        return $getvals;
    }
    function getFrontEndsSummary($facility = '%') {
        //called from GetFEData.php
        $fe_summary = mysqli_query($this->dbConnection, "SELECT max(FE_Config.keyFEConfig) AS maxkey, Front_Ends.SN, Front_Ends.keyFacility,
                                 Front_Ends.Docs
                                 FROM FE_Config
                                 JOIN Front_Ends ON (FE_Config.fkFront_Ends = Front_Ends.keyFrontEnds
                                 AND FE_Config.keyFacility=Front_Ends.keyFacility)
                                 WHERE Front_Ends.keyFacility LIKE '$facility'
                                 GROUP BY Front_Ends.SN")
            or die("Could not get data" . mysqli_error($this->dbConnection));
        return $fe_summary;
    }
    function getStatLocAndNotesWithJoin($keyval, $facility = '%') {
        //called from GetFEData.php
        $statlist = mysqli_query($this->dbConnection, "Select FE_Config.keyFEConfig as config,Front_Ends.SN,FE_Config.TS,
                                StatTab.Notes,StatTab.Status,StatTab.Description,StatTab.Updated_By,
                                Front_Ends.keyFacility AS keyFacility, Front_Ends.Docs AS Docs

                                FROM FE_Config
                                LEFT JOIN (Select FE_StatusLocationAndNotes.Notes,
                                FE_StatusLocationAndNotes.fkFEConfig,FE_StatusLocationAndNotes.keyFacility,
                                StatusTypes.Status,FE_StatusLocationAndNotes.Updated_By,Locations.Description
                                FROM FE_StatusLocationAndNotes
                                LEFT JOIN StatusTypes
                                ON FE_StatusLocationAndNotes.fkStatusType=StatusTypes.keyStatusType
                                LEFT JOIN Locations
                                ON FE_StatusLocationAndNotes.fkLocationNames=Locations.keyId
                                ORDER BY FE_StatusLocationAndNotes.TS DESC)
                                AS StatTab
                                ON (FE_Config.keyFEConfig=StatTab.fkFEConfig AND
                                FE_Config.keyFacility=StatTab.keyFacility)
                                JOIN Front_Ends ON (FE_Config.fkFront_Ends = Front_Ends.keyFrontEnds
                                AND FE_Config.keyFacility=Front_Ends.keyFacility)
                                WHERE
                                FE_Config.keyFEConfig='$keyval' AND FE_Config.keyFacility LIKE '$facility'
                                ORDER BY FE_Config.TS DESC LIMIT 1")
            or die("Could not get data" . mysqli_error($this->dbConnection));
        return $statlist;
    }
    function getFESN($keyFE, $facility) {
        //called from UpdateFE.php,ShowFEConfig.php
        $fesn = mysqli_query($this->dbConnection, "SELECT SN FROM Front_Ends WHERE keyFrontEnds=(SELECT fkFront_Ends FROM FE_Config
                           WHERE keyFEConfig='$keyFE' AND FE_Config.keyFacility='$facility') AND
                           Front_Ends.keyFacility='$facility'")

            or die("Could not get FE SN" . mysqli_error($this->dbConnection));

        return $fesn;
    }
    function getFEkeys($fe_sn, $facility) {
        //called from ShowFEConfig.php
        $fe_keys = mysqli_query($this->dbConnection, "SELECT keyFEConfig FROM FE_Config WHERE fkFront_Ends= ANY(SELECT keyFrontEnds FROM
                              Front_Ends WHERE SN='$fe_sn' AND keyFacility='$facility') AND keyFacility='$facility'
                              ORDER BY keyFEConfig DESC")
            or die("Could not get front end keys" . mysqli_error($this->dbConnection));
        return $fe_keys;
    }
    function getStatusLocationAndNotesData($keyval, $facility = '%') {
        //called from ShowFEConfig.php
        $this->key = $keyval;
        $statuslocationAndnotesData = mysqli_query($this->dbConnection, "SELECT FE_StatusLocationAndNotes.keyId,
                                    FE_StatusLocationAndNotes.TS,
                                    FE_StatusLocationAndNotes.Updated_By,
                                    FE_StatusLocationAndNotes.lnk_Data,FE_StatusLocationAndNotes.Notes,
                                    Locations.Description,StatusTypes.Status FROM FE_StatusLocationAndNotes
                                    LEFT JOIN Locations
                                    ON FE_StatusLocationAndNotes.fkLocationNames=Locations.keyId
                                    LEFT JOIN StatusTypes
                                    ON FE_StatusLocationAndNotes.fkStatusType=StatusTypes.keyStatusType
                                    WHERE FE_StatusLocationAndNotes.fkFEConfig='$this->key' AND
                                    FE_StatusLocationAndNotes.keyFacility LIKE '$facility'
                                    ORDER BY FE_StatusLocationAndNotes.TS DESC")
            or die("Could not get StatusLocationAndNotes" . mysqli_error($this->dbConnection));
        return $statuslocationAndnotesData;
    }
    function getFrontEndComponents($feConfig, $band, $facility, $ComponentType = '%') {
        //called from getFrontEndData.php
        $feComponents = mysqli_query($this->dbConnection, "SELECT FE_Components.keyId,FE_Components.SN,FE_Components.fkFE_ComponentType,
                                FE_Components.ESN1,FE_Components.ESN2,FE_Components.Description,
                                FE_Components.Band,FE_Components.TS,ComponentTypes.Description AS Notes
                                FROM FE_Components
                    LEFT JOIN ComponentTypes ON FE_Components.fkFE_ComponentType=ComponentTypes.keyId
                    WHERE FE_Components.keyId= ANY(SELECT fkFE_Components
                    FROM FE_ConfigLink WHERE fkFE_Config='$feConfig' AND fkFE_ComponentFacility='$facility')
                    AND Band='$band' AND FE_Components.keyFacility='$facility'
                    AND FE_Components.fkFE_ComponentType LIKE '$ComponentType'
                    ORDER BY ComponentTypes.Description ASC")
            or die("Could not get data" . mysqli_error($this->dbConnection));
        return $feComponents;
    }
    function getFECompsNoBand($feConfig, $facility, $ComponentType = '%') {
        //called from getFrontEndData.php
        $feComponents = mysqli_query($this->dbConnection, "SELECT FE_Components.keyId,FE_Components.SN,FE_Components.fkFE_ComponentType,
                    FE_Components.ESN1,FE_Components.ESN2,FE_Components.Description,
                    FE_Components.Band,
                    FE_Components.TS,ComponentTypes.Description AS Notes FROM FE_Components
                    LEFT JOIN ComponentTypes ON FE_Components.fkFE_ComponentType=ComponentTypes.keyId
                    WHERE FE_Components.keyId= ANY(SELECT fkFE_Components
                    FROM FE_ConfigLink WHERE fkFE_Config='$feConfig' AND FE_ConfigLink.fkFE_ComponentFacility='$facility')
                    AND (Band is Null OR Band='0') AND FE_Components.keyFacility='$facility'
                    AND FE_Components.fkFE_ComponentType LIKE '$ComponentType'
                    ORDER BY ComponentTypes.Description ASC")
            or die("Could not get data" . mysqli_error($this->dbConnection));
        return $feComponents;
    }
    function getAllCompsofType($type, $band) {
        //called from AddFrontEnd.php
        $comp = mysqli_query($this->dbConnection, "SELECT Max(keyId) AS maxkey, SN FROM FE_Components WHERE fkFE_ComponentType='$type' AND Band='$band'
        GROUP BY SN ORDER BY SN ASC")
            or die("Could not get components" . mysqli_error($this->dbConnection));
        return $comp;
    }
    function getCompsWithoutband($type) {
        //called from AddFrontEnd.php
        $comp = mysqli_query($this->dbConnection, "SELECT MAX(keyId) AS maxkey, SN FROM FE_Components WHERE fkFE_ComponentType='$type'
        AND Band is Null
        GROUP BY SN ORDER BY SN ASC")
            or die("Could not get components" . mysqli_error($this->dbConnection));
        return $comp;
    }
    function getComponentTypes() {
        //called from FEHome.php
        $comptypes = mysqli_query($this->dbConnection, "SELECT keyId, Description FROM ComponentTypes ORDER BY Description ASC");
        return $comptypes;
    }
    function getTestDataHeader($fesn, $feConfig, $band, $facility, $ComponentType = "%") {
        //called from getFrontEndData.php
        $testheader = mysqli_query($this->dbConnection, "SELECT TestData_header.keyId as TestKey,TestData_header.fkFE_Config, FE_Components.SN,
                                ComponentTypes.Description AS ComponentDescription,DataStatus.Description,
                                TestData_Types.TestData_TableName,
                                TestData_header.Notes, TestData_header.TS
                                FROM TestData_header
                            LEFT JOIN FE_Components ON (TestData_header.fkFE_Components = FE_Components.keyId
                            AND TestData_header.keyFacility=FE_Components.keyFacility
                            )
                            LEFT JOIN DataStatus ON TestData_header.fkDataStatus = DataStatus.keyId
                            LEFT JOIN ComponentTypes ON FE_Components.fkFE_ComponentType = ComponentTypes.keyId
                            LEFT JOIN TestData_Types ON TestData_header.fkTestData_Type = TestData_Types.keyId
                            WHERE TestData_header.Band='$band' AND
                            fkFE_Config=ANY(SELECT keyFEConfig FROM FE_Config WHERE
                            fkFront_Ends=ANY(SELECT keyFrontEnds FROM Front_Ends
                            WHERE SN='$fesn' AND Front_Ends.keyFacility='$facility')
                            AND FE_Config.keyFacility='$facility') AND TestData_header.keyFacility='$facility'

                            ORDER BY TestData_header.fkFE_Config DESC")
            or die("Could not get Data" . mysqli_error($this->dbConnection));



        return $testheader;
    }
    function getCompsTestDataHeader($fkComp) {
        $testheader = mysqli_query($this->dbConnection, "SELECT TestData_header.keyId as TestKey,TestData_header.fkFE_Components, FE_Components.SN,
                        ComponentTypes.Description AS ComponentDescription,DataStatus.Description,
                        TestData_Types.TestData_TableName,
                        TestData_header.Notes, TestData_header.TS
                        FROM TestData_header
                    LEFT JOIN FE_Components ON TestData_header.fkFE_Components = FE_Components.keyId
                    LEFT JOIN DataStatus ON TestData_header.fkDataStatus = DataStatus.keyId
                    LEFT JOIN ComponentTypes ON FE_Components.fkFE_ComponentType = ComponentTypes.keyId
                    LEFT JOIN TestData_Types ON TestData_header.fkTestData_Type = TestData_Types.keyId
                    WHERE fkFE_Components='$fkComp' AND (fkFE_Config='0' OR fkFE_Config is NULL) ORDER BY TestData_header.fkFE_Config DESC")
            or die("Could not get Data" . mysqli_error($this->dbConnection));
        return $testheader;
    }
    function getStatusLocation($tablename) {
        //called from MupdateFE.php,AddFrontEnd.php
        $list = mysqli_query($this->dbConnection, "SELECT * FROM $tablename");
        return $list;
    }
    function getNumBands($keyFE) {
        //called from ShowFEConfig.php
        $getnumrows = mysqli_query($this->dbConnection, "SELECT Band FROM FE_Components WHERE
                                keyId=ANY(Select fkFE_Components FROM FE_ConfigLink WHERE fkFE_Config='$keyFE')
                                AND fkFE_ComponentType='20' GROUP BY Band")
            or die("Could not get data" . mysqli_error($this->dbConnection));

        $numbands = mysqli_num_rows($getnumrows);

        return $numbands;
    }
    function getMaxKey($sn, $facility) {
        //called from AddFrontEnd.php
        $maxkey_query = mysqli_query($this->dbConnection, "SELECT max(keyFrontEnds) AS maxFEkey FROM Front_Ends
        WHERE SN='$sn' AND keyFacility='$facility'")
            or die("Could not get Max FE key" . mysqli_error($this->dbConnection));

        $maxkey = ADAPT_mysqli_result($maxkey_query, 0, "maxFEkey");

        return $maxkey;
    }
    function getMaxKeyFE_Config($maxFEkey) {
        //called from AddFrontEnd.php
        $FE_Configkey_query = mysqli_query($this->dbConnection, "SELECT MAX(keyFEConfig) as KeyFE FROM FE_Config WHERE fkFront_Ends='$maxFEkey'")
            or die("Could not get FE_Config key" . mysqli_error($this->dbConnection));

        $Fe_configkey = ADAPT_mysqli_result($FE_Configkey_query, 0, "KeyFE");

        return $Fe_configkey;
    }
    function getPreviousComponents($keyFE, $componentType, $band) {
        //called from MupdateFE.php
        if ($band != 0) {
            $allComponents = mysqli_query($this->dbConnection, "SELECT SN, keyId FROM FE_Components WHERE
                            keyId=ANY(SELECT fkFE_Components FROM FE_ConfigLink WHERE fkFE_Config='$keyFE') AND
                            fkFE_ComponentType='$componentType' AND Band='$band' ORDER BY keyId DESC LIMIT 1");
        } else {
            $allComponents = mysqli_query($this->dbConnection, "SELECT SN, keyId FROM FE_Components WHERE
                            keyId=ANY(SELECT fkFE_Components FROM FE_ConfigLink WHERE fkFE_Config='$keyFE') AND
                            fkFE_ComponentType='$componentType' AND (Band=0 OR Band is NULL) ORDER BY keyId DESC LIMIT 1");
        }

        if (mysqli_num_rows($allComponents) > 0) {
            $component_sn = ADAPT_mysqli_result($allComponents, 0, "SN");
            $component_key = ADAPT_mysqli_result($allComponents, 0, "keyId");
        }
        $component_option = array("cSN" => $component_sn, "cKey" => $component_key);

        //$component_option="<option value=\"$component_key\">$component_sn</option>";
        return $component_option;
    }
    function getStatusAndLocation($fkFEConfig) {
        $statandLoc = mysqli_query($this->dbConnection, "SELECT fkLocationNames,fkStatusType FROM FE_StatusLocationAndNotes
                                WHERE fkFEConfig='$fkFEConfig' ORDER BY TS DESC LIMIT 1")
            or die("Could not get data" . mysqli_error($this->dbConnection));

        return $statandLoc;
    }
    function getSelectedCompConfig($key) {
        //called from getComponentData.php
        $getcomps = mysqli_query($this->dbConnection, "SELECT FE_Components.keyId,FE_Components.fkFE_ComponentType,FE_Components.SN,
                               FE_Components.ESN1,FE_Components.ESN2,FE_Components.Description,FE_Components.Link1,
                               FE_Components.Description,
                               FE_Components.Band,FE_Components.TS ,
                               ComponentTypes.Description AS Descr
                               FROM FE_Components
                               LEFT JOIN ComponentTypes ON FE_Components.fkFE_ComponentType=ComponentTypes.keyId
                               WHERE FE_Components.keyId='$key'")
            or die("Could not get data" . mysqli_error($this->dbConnection));

        return $getcomps;
    }
    function getAllCompConfig($band, $comp_type, $selected_key) {
        //called from getComponentData.php
        $sn_query = mysqli_query($this->dbConnection, "SELECT SN FROM FE_Components WHERE keyId='$selected_key'");

        $num = mysqli_num_rows($sn_query);
        if ($num > 0) {
            $sn = ADAPT_mysqli_result($sn_query, 0, "SN");
        }
        if ($band != 0) {
            $getcomps = mysqli_query($this->dbConnection, "SELECT FE_Components.keyId,FE_Components.fkFE_ComponentType,FE_Components.SN,
                               FE_Components.ESN1,FE_Components.ESN2,FE_Components.Description,
                               FE_Components.Band,FE_Components.TS,
                               ComponentTypes.Description AS Descr
                               FROM FE_Components
                               LEFT JOIN ComponentTypes ON FE_Components.fkFE_ComponentType=ComponentTypes.keyId
                               WHERE FE_Components.SN='$sn' AND FE_Components.Band='$band' AND
                               FE_Components.fkFE_ComponentType='$comp_type' AND
                               FE_Components.keyId != '$selected_key' ORDER BY FE_Components.keyId DESC")
                or die("Could not get data" . mysqli_error($this->dbConnection));
        } else {
            $getcomps = mysqli_query($this->dbConnection, "SELECT FE_Components.keyId,FE_Components.fkFE_ComponentType,FE_Components.SN,
                               FE_Components.ESN1,FE_Components.ESN2,FE_Components.Description,
                               FE_Components.Band,FE_Components.TS,
                               ComponentTypes.Description AS Descr
                               FROM FE_Components
                               LEFT JOIN ComponentTypes ON FE_Components.fkFE_ComponentType=ComponentTypes.keyId
                               WHERE FE_Components.SN='$sn' AND FE_Components.Band is Null AND
                               FE_Components.fkFE_ComponentType='$comp_type' AND
                               FE_Components.keyId != '$selected_key' ORDER BY FE_Components.keyId DESC")
                or die("Could not get data" . mysqli_error($this->dbConnection));
        }
        return $getcomps;
    }
    function getOperatingParams($tablename, $selected_key, $band, $comp_type) {
        //called from getComponentData.php
        $getOpParams = mysqli_query($this->dbConnection, "SELECT * FROM $tablename WHERE fkComponent=ANY(SELECT keyId FROM FE_Components
                                 WHERE fkFE_ComponentType='$comp_type' AND SN=(SELECT SN FROM FE_Components WHERE
                                 keyId='$selected_key') AND Band='$band')
                                 ORDER BY fkComponent DESC");
        return $getOpParams;
    }
    function getTempSensors($compkey) {
        //called from getComponentData.php
        $getTempSensors = mysqli_query($this->dbConnection, "SELECT * FROM CCA_TempSensors
                            WHERE fkHeader=ANY(SELECT keyId FROM TestData_header
                            WHERE fkFE_Components='$compkey' AND fkTestData_Type='2')");
        return $getTempSensors;
    }
    function getYIGvalues($selected_key, $band) {
        //called from getComponentData.php
        $getYig = mysqli_query($this->dbConnection, "SELECT fkFE_Component, FloYIG,FhiYIG FROM WCAs WHERE
                             fkFE_Component=ANY(SELECT keyId FROM FE_Components
                             WHERE fkFE_ComponentType='11' AND SN=(SELECT SN FROM FE_Components WHERE
                            keyId='$selected_key') AND Band='$band')
                             ORDER BY fkFE_Component DESC")
            or die("Could not get yig values" . mysqli_error($this->dbConnection));
        return $getYig;
    }
    function getCompsSummary($ctype, $facility = '%') {
        //called from GetFEData.php
        $getcomps = mysqli_query($this->dbConnection, "SELECT max(keyId) AS MaxKey, SN, Band, keyFacility FROM FE_Components
                               WHERE fkFE_ComponentType='$ctype' AND keyFacility LIKE '$facility'
                                GROUP BY (SN + 0), Band")
            or die("Could not get Data" . mysqli_error($this->dbConnection));
        return $getcomps;
    }
    function getStatLocAndNotesComps($keyval, $facility = '%') {
        //called from GetFEData.php
        $statlist = mysqli_query($this->dbConnection, "Select FE_Components.keyId as config,FE_Components.SN,FE_Components.Band,FE_Components.TS,
                                StatTab.Notes,StatTab.Status,StatTab.Description,StatTab.Updated_By,
                                FE_Components.keyFacility
                                FROM FE_Components
                                LEFT JOIN (Select FE_StatusLocationAndNotes.Notes,
                                FE_StatusLocationAndNotes.fkFEComponents,FE_StatusLocationAndNotes.keyFacility,
                                StatusTypes.Status,FE_StatusLocationAndNotes.Updated_By,Locations.Description
                                FROM FE_StatusLocationAndNotes
                                LEFT JOIN StatusTypes
                                ON FE_StatusLocationAndNotes.fkStatusType=StatusTypes.keyStatusType
                                LEFT JOIN Locations
                                ON FE_StatusLocationAndNotes.fkLocationNames=Locations.keyId
                                ORDER BY FE_StatusLocationAndNotes.TS DESC)
                                AS StatTab
                                ON (FE_Components.keyId=StatTab.fkFEComponents
                                    AND FE_Components.keyFacility=StatTab.keyFacility)
                                WHERE
                                FE_Components.keyId LIKE '$keyval' AND FE_Components.keyFacility LIKE '$facility'
                                ORDER BY FE_Components.TS DESC LIMIT 1")
            or die("Could not get data" . mysqli_error($this->dbConnection));

        return $statlist;
    }
    function getComponentsForHistory($comp_key, $band, $comptype, $facility) {
        //called from ShowComponents.php

        $sn_query = mysqli_query($this->dbConnection, "SELECT SN FROM FE_Components WHERE keyId='$comp_key'
                                AND keyFacility='$facility'");
        $num = mysqli_num_rows($sn_query);
        if ($num > 0) {
            $sn = ADAPT_mysqli_result($sn_query, 0, "SN");
        }

        if ($band != 0) {
            $getCompkeys = mysqli_query($this->dbConnection, "SELECT keyId FROM FE_Components WHERE SN='$sn' AND Band='$band' AND
            fkFE_ComponentType='$comptype' AND keyFacility='$facility'")
                or die("Could not get component keys" . mysqli_error($this->dbConnection));
        } else {
            $getCompkeys = mysqli_query($this->dbConnection, "SELECT keyId FROM FE_Components WHERE SN='$sn'
            AND (Band='0' OR Band is Null) AND keyFacility='$facility'
            AND fkFE_ComponentType='$comptype'")
                or die("Could not get component keys" . mysqli_error($this->dbConnection));
        }
        return $getCompkeys;
    }
    function getStatusLocationAndNotesComps($keyVal, $facility = '%') {
        //called from ShowComponents.php

        $statuslocationAndnotesData = mysqli_query($this->dbConnection, "SELECT FE_StatusLocationAndNotes.keyId,
                                    FE_StatusLocationAndNotes.TS,
                                    FE_StatusLocationAndNotes.Updated_By,
                                    FE_StatusLocationAndNotes.lnk_Data,FE_StatusLocationAndNotes.Notes,
                                    Locations.Description,StatusTypes.Status,
                                    FE_StatusLocationAndNotes.keyFacility AS keyFacility
                                    FROM FE_StatusLocationAndNotes
                                    LEFT JOIN Locations
                                    ON FE_StatusLocationAndNotes.fkLocationNames=Locations.keyId
                                    LEFT JOIN StatusTypes
                                    ON FE_StatusLocationAndNotes.fkStatusType=StatusTypes.keyStatusType
                                    WHERE FE_StatusLocationAndNotes.fkFEComponents='$keyVal'
                                    AND FE_StatusLocationAndNotes.keyFacility LIKE'$facility'
                                    ORDER BY FE_StatusLocationAndNotes.TS DESC")
            or die("Could not get StatusLocationAndNotes" . mysqli_error($this->dbConnection));



        return $statuslocationAndnotesData;
    }

    function getCompSN($key, $facility) {
        //called from MupdateComponents.php
        $compband = mysqli_query($this->dbConnection, "SELECT Band, fkFE_ComponentType FROM FE_Components WHERE keyId='$key' AND
        keyFacility='$facility'");
        $band = ADAPT_mysqli_result($compband, 0, "Band");
        $comp_type = ADAPT_mysqli_result($compband, 0, "fkFE_ComponentType");

        if ($band != Null || $band != 0) {
            //called from MupdateComponents.php
            $compsn = mysqli_query($this->dbConnection, "SELECT MAX(keyId) as MaxKey,SN FROM FE_Components WHERE
            SN=(SELECT SN FROM FE_Components
            WHERE keyID='$key' AND Band='$band' AND keyFacility='$facility') AND fkFE_ComponentType='$comp_type'
            AND Band='$band' AND keyFacility='$facility' GROUP BY SN");
        } else {
            //called from MupdateComponents.php
            $compsn = mysqli_query($this->dbConnection, "SELECT MAX(keyId) as MaxKey ,SN FROM FE_Components WHERE SN=(SELECT SN FROM FE_Components
            WHERE keyID='$key' AND (Band is NULL OR Band ='0') AND keyFacility='$facility')
            AND fkFE_ComponentType='$comp_type' AND keyFacility='$facility'
            AND Band='$band' GROUP BY SN");
        }
        //or die("Could not get max key" . mysqli_error($this->dbConnection));

        return $compsn;
    }
    function getPrevComponents($keyComp, $facility) {
        //called from MupdateComponents.php
        $getComps = mysqli_query($this->dbConnection, "SELECT * FROM FE_Components WHERE keyId='$keyComp' AND keyFacility='$facility'")
            or die("Could not get Components data" . mysqli_error($this->dbConnection));

        return $getComps;
    }
    function getcomponentName($comptype) {
        //called from MupdateComponents.php
        $getCompName = mysqli_query($this->dbConnection, "SELECT Description FROM ComponentTypes WHERE keyId='$comptype'")
            or die("Could not get comp name" . mysqli_error($this->dbConnection));

        $compname = ADAPT_mysqli_result($getCompName, 0, "Description");
        return $compname;
    }
    function getStatusAndLocationComp($compkey, $facility) {
        //called from MupdateComponents.php
        $statandLoc = mysqli_query($this->dbConnection, "SELECT fkLocationNames,fkStatusType FROM FE_StatusLocationAndNotes
                                WHERE fkFEComponents='$compkey' AND keyFacility='$facility'
                                ORDER BY TS DESC LIMIT 1")
            or die("Could not get data" . mysqli_error($this->dbConnection));

        return $statandLoc;
    }
    function getWhichFE($comp_id) {
        //called from getComponentsData.php
        $getConfig_query = mysqli_query($this->dbConnection, "SELECT MAX(fkFE_Config) AS MaxFEConfig
                        FROM FE_ConfigLink WHERE fkFE_Components='$comp_id'");

        if (mysqli_num_rows($getConfig_query) > 0) {
            $fe_config = ADAPT_mysqli_result($getConfig_query, 0, "MaxFEConfig");

            $fe_sn_query = mysqli_query($this->dbConnection, "SELECT SN FROM Front_Ends WHERE
            keyFrontEnds=(SELECT fkFront_Ends FROM FE_Config WHERE keyFEConfig='$fe_config')")
                or die("Could not get FE SN" . mysqli_error($this->dbConnection));

            if (mysqli_num_rows($fe_sn_query) > 0) {
                $fesn = ADAPT_mysqli_result($fe_sn_query, 0, "SN");
            }

            $getMaxFEConfig_query = mysqli_query($this->dbConnection, "SELECT MAX(keyFEConfig) as MaxKeyConfig
                            FROM FE_Config WHERE fkFront_Ends=(SELECT MAX(keyFrontEnds)
                            FROM Front_Ends WHERE SN='$fesn')")
                or die("Could not get data" . mysqli_error($this->dbConnection));
            $getMaxFEConfig = ADAPT_mysqli_result($getMaxFEConfig_query, 0, "MaxKeyConfig");

            $arr = array("FESN" => $fesn, "FEConfig" => $getMaxFEConfig);


            if ($fe_config == $getMaxFEConfig) {
                return $arr;
            } else {
                return;
            }
        }
    }
}
