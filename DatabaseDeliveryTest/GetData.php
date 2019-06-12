<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_dbConnect);

class GetData
{
	function CreateTempTable($frontend_sn,$facility)
	{
	    $q = "DROP TEMPORARY TABLE IF EXISTS Config_Keys";
	    mysqli_query($link, $q)
	    or die("Could not drop Temp table" .mysql_error());

	    $q = "CREATE TEMPORARY TABLE Config_Keys (Config_key INT(10) NULL, Test_Type INT(10) NULL)";
	    mysqli_query($link, $q)
		or die("Could not create Temp table" .mysql_error());

		$q = "INSERT INTO Config_Keys (Config_key,Test_Type)
			SELECT fkFE_Config,fkTestData_Type
			FROM TestData_header WHERE
			TestData_header.fkFE_Config=ANY(SELECT keyFEConfig
			FROM FE_Config WHERE FE_Config.fkFront_Ends=ANY(SELECT keyFrontEnds
			FROM Front_Ends WHERE Front_Ends.SN='$frontend_sn' AND
			keyFacility='$facility') AND keyFacility='$facility') AND
			keyFacility='$facility' ORDER BY fkTestData_Type";
		mysqli_query($link, $q)
		or die("Could not insert into temp table" .mysql_error());
	}
	function getFrontEndData($frontend_sn,$keyFacility)
	{
		$frontend_data=mysqli_query($link, "SELECT * FROM Front_Ends WHERE SN='$frontend_sn' AND keyFacility='$keyFacility'
									ORDER BY keyFrontEnds DESC LIMIT 1")
		or die("Could not get front end data" .mysql_error());

		return $frontend_data;
	}
	function getComponents($componentType,$fe_maxkey,$keyFacility)
	{
		$getmax_config=mysqli_query($link, "SELECT max(keyFEConfig) AS MaxConfig FROM FE_Config
									WHERE fkFront_Ends='$fe_maxkey'
									AND keyFacility='$keyFacility'");
		$maxconfig=ADAPT_mysqli_result($getmax_config,0,"MaxConfig");

		$components=mysqli_query($link, "SELECT * FROM (SELECT * FROM FE_Components WHERE
		keyId=ANY(SELECT fkFE_Components FROM FE_ConfigLink WHERE fkFE_Config='$maxconfig' AND fkFE_ConfigFacility='$keyFacility')
		AND fkFE_ComponentType='$componentType' AND keyFacility='$keyFacility'
		ORDER BY keyId DESC) AS subq GROUP BY Band")
		or die("Could not get components" .mysql_error());

		return $components;
	}
	function getWCAYig($WCA_keyVal,$keyFacility)
	{
		$getSNandBand=mysqli_query($link, "SELECT SN, Band FROM FE_Components WHERE keyId='$WCA_keyVal'
		AND keyFacility='$keyFacility'");
		$sn=ADAPT_mysqli_result($getSNandBand,0,'SN');
		$band=ADAPT_mysqli_result($getSNandBand,0,'Band');

		$getAllKeys=mysqli_query($link, "SELECT MAX(fkFE_Component) as maxkey FROM WCAs WHERE
					fkFE_Component=ANY(SELECT keyId FROM FE_Components WHERE SN='$sn' AND Band='$band')
					 AND fkFacility='$keyFacility'");

		$wcakey=ADAPT_mysqli_result($getAllKeys,0,"maxkey");
		if($wcakey != 0 || $wcakey != Null)
		{
			$wca_yig=mysqli_query($link, "SELECT FloYIG,FhiYig FROM WCAs WHERE fkFE_Component='$wcakey'
			AND fkFacility='$keyFacility' ORDER BY TS DESC LIMIT 1")
			or die("Could not get yig values" .mysql_error());

			$yig_lo=ADAPT_mysqli_result($wca_yig,0,"FloYIG");
			$yig_hi=ADAPT_mysqli_result($wca_yig,0,"FhiYig");

			$yig_array=array("yiglo" =>$yig_lo,"yighi" =>$yig_hi);
		}
		return $yig_array;
	}
	function getLOParams($WCA_keyVal,$keyFacility)
	{
		$getSNandBand=mysqli_query($link, "SELECT SN, Band FROM FE_Components WHERE keyId='$WCA_keyVal'
								AND keyFacility='$keyFacility' ");
		$sn=ADAPT_mysqli_result($getSNandBand,0,'SN');
		$band=ADAPT_mysqli_result($getSNandBand,0,'Band');

		$getAllKeys=mysqli_query($link, "SELECT MAX(fkComponent) as maxkey FROM WCA_LOParams WHERE
					fkComponent=ANY(SELECT keyId FROM FE_Components WHERE SN='$sn' AND Band='$band')
					 AND fkFacility='$keyFacility'");

		$wcakey=ADAPT_mysqli_result($getAllKeys,0,"maxkey");

		if($wcakey != 0 || $wcakey != Null)
		{
			$loparams=mysqli_query($link, "SELECT * FROM WCA_LOParams WHERE fkComponent='$wcakey' AND fkFacility='$keyFacility'
			ORDER BY FreqLO ASC")
			or die("Could not get LOParams" .mysql_error());
		}
		return $loparams;
	}
	function getMixerParams($componentId,$keyFacility)
	{
		$getSNandBand=mysqli_query($link, "SELECT SN, Band FROM FE_Components WHERE keyId='$componentId'
						AND keyFacility='$keyFacility'");
		$sn=ADAPT_mysqli_result($getSNandBand,0,'SN');
		$band=ADAPT_mysqli_result($getSNandBand,0,'Band');

		$getAllKeys=mysqli_query($link, "SELECT MAX(fkComponent) as maxkey FROM CCA_MixerParams WHERE
								fkComponent=ANY(SELECT keyId FROM FE_Components WHERE SN='$sn' AND Band='$band'
								AND fkFE_ComponentType='20') AND fkFacility='$keyFacility'")
        or die("Could not fkComp from MixerParams" .mysql_error());

		$ccakey=ADAPT_mysqli_result($getAllKeys,0,"maxkey");

        if($ccakey != 0 || $ccakey != Null)
		{
				$mixerparams=mysqli_query($link, "SELECT * FROM CCA_MixerParams WHERE fkComponent='$ccakey'
								 AND fkFacility='$keyFacility' ORDER BY FreqLO ASC")
			    or die("Could not get mixer params" .mysql_error());
		}
		return $mixerparams;
	}
	function getPreampParams($componentId,$keyFacility)
	{
		$getSNandBand=mysqli_query($link, "SELECT SN, Band FROM FE_Components WHERE keyId='$componentId'
		             AND keyFacility='$keyFacility'");
		$sn=ADAPT_mysqli_result($getSNandBand,0,'SN');
		$band=ADAPT_mysqli_result($getSNandBand,0,'Band');

		$getAllKeys=mysqli_query($link, "SELECT MAX(fkComponent) as maxkey FROM CCA_PreampParams WHERE
								fkComponent=ANY(SELECT keyId FROM FE_Components WHERE SN='$sn' AND Band='$band'
								AND fkFE_ComponentType='20') AND fkFacility='$keyFacility'");

		$ccakey=ADAPT_mysqli_result($getAllKeys,0,"maxkey");
		if($ccakey != 0 || $ccakey != Null)
		{
			$preampparams=mysqli_query($link, "SELECT * FROM CCA_PreampParams WHERE fkComponent='$ccakey' AND fkFacility='$keyFacility'
							ORDER BY FreqLO ASC, Pol ASC, SB ASC")
			or die("Could not get Preamp Params" .mysql_error());
		}
		return $preampparams;
	}
	/*function getCartAssemblies($frontend_sn)
	{
		$cartAssemblies=mysqli_query($link, "SELECT * FROM CartAssemblies WHERE fkFrontEnd='$frontend_sn' ORDER BY KeyId ASC")
		or die("Could not get CartAssemblies" .mysql_error());

		return $cartAssemblies;
	}*/
	function getConfigData($testType,$dataStatus,$frontend_sn,$keyFacility,$withUseForPAI=FALSE)
	{
	    $q = "SELECT * FROM TestData_header
		WHERE TestData_header.fkTestData_Type='$testType'
		AND TestData_header.fkDataStatus='$dataStatus'
	    AND keyFacility='$keyFacility'";

	    if ($withUseForPAI) {
	        $q .= " AND TestData_header.UseForPAI != 0";
	    } else {
	        $q .= " AND DataSetGroup = 1";
	    }

	    $q .= " AND TestData_header.fkFE_Config=ANY(SELECT Config_Key FROM Config_Keys WHERE Test_Type='$testType')
		ORDER BY Band ASC, keyId ASC";

		$configData = mysqli_query($link, $q)
		or die("Could not get configuration data" .mysql_error());

		return $configData;
	}
	function getYFactorData($keyTestDataType,$fe_config,$band,$keyFacility)
	{
		$yfactor=mysqli_query($link, "SELECT * FROM Yfactor WHERE fkHeader=(SELECT keyId FROM TestData_header WHERE
		fkTestData_Type='$keyTestDataType' AND fkFE_Config='$fe_config' AND Band='$band' AND keyFacility='$keyFacility' AND DataSetGroup='1'
		ORDER BY TS DESC Limit 1) AND fkFacility='$keyFacility' ORDER BY IFchannel ASC")
		or die("Could not get Y Factors" .mysql_error());

		return $yfactor;
	}
	function getIFSpectrumData($fkHeader,$freqlo1,$freqlo2,$keyFacility)
	{
	    $q="SELECT * FROM IFSpectrum_SubHeader WHERE
	       fkHeader='$fkHeader' AND keyFacility='$keyFacility' AND
	       IFGain='15' AND (FreqLO='$freqlo1' OR FreqLO='$freqlo2') AND IsIncluded='1'";
	    $ifspectrum=mysqli_query($link, $q)
	    or die("Could not get IFSpectrum" .mysql_error());
	    return $ifspectrum;
	}
	function getIFSpectrumDataOLD($keyTestDataType,$fe_config,$band,$freqlo,$keyFacility)
	{
		$ifspectrum=mysqli_query($link, "SELECT * FROM IFSpectrum_SubHeader WHERE
		fkHeader=(SELECT keyId FROM TestData_header WHERE
		fkTestData_Type='$keyTestDataType' AND fkFE_Config='$fe_config' AND Band='$band' AND keyFacility='$keyFacility'
		AND TestData_header.UseForPAI != 0
		ORDER BY TS DESC Limit 1) AND keyFacility='$keyFacility' AND
		IFGain='15' AND FreqLO='$freqlo' AND IsIncluded='1' ORDER BY Band ASC, FreqLO ASC, IFGain ASC")
		or die("Could not get IFSpectrum" .mysql_error());

		return $ifspectrum;
	}
	function getIFTotalPower($keyTestDataType,$fe_config,$dataStatus,$band,$keyFacility)
	{
		$iftotalpower=mysqli_query($link, "SELECT * FROM IFTotalPower WHERE fkHeader=(SELECT keyId FROM TestData_header WHERE
		fkTestData_Type='$keyTestDataType' AND fkFE_Config='$fe_config' AND fkDataStatus='$dataStatus'
		AND Band='$band' AND keyFacility='$keyFacility' AND DataSetGroup='1'
		ORDER BY TS DESC Limit 1) AND fkFacility='$keyFacility' ORDER BY IFChannel ASC")
		or die("Could not get IFTotalPower" .mysql_error());

		return $iftotalpower;
	}
	function getCCALNABias($keyTestDataType,$fe_config,$dataStatus,$component_key,$keyFacility)
	{
		$ccalnabias=mysqli_query($link, "SELECT * FROM CCA_LNA_bias WHERE
		fkHeader=(SELECT keyId FROM TestData_header WHERE
		fkTestData_Type='$keyTestDataType' AND fkFE_Config='$fe_config' AND fkDataStatus='$dataStatus'
		AND fkFE_Components='$component_key' AND keyFacility='$keyFacility' AND DataSetGroup='1'
		ORDER BY TS DESC Limit 1) AND fkFacility='$keyFacility'
		ORDER BY FreqLO ASC, Pol ASC, SB ASC, Stage ASC")
		or die("Could not get LNA data" .mysql_error());

		return $ccalnabias;
	}
	function getCCASISBias($keyTestDataType,$fe_config,$dataStatus,$component_key,$keyFacility)
	{
		$ccaSISbias=mysqli_query($link, "SELECT * FROM CCA_SIS_bias
		WHERE fkHeader=(SELECT keyId FROM TestData_header WHERE
		fkTestData_Type='$keyTestDataType' AND fkFE_Config='$fe_config' AND fkDataStatus='$dataStatus'
		AND fkFE_Components='$component_key' AND keyFacility='$keyFacility' AND DataSetGroup='1'
		ORDER BY TS DESC Limit 1) AND fkFacility='$keyFacility' ORDER BY FreqLO ASC")
		or die("Could not get SIS data" .mysql_error());

		return $ccaSISbias;
	}
	function getCCATemps($keyTestDataType,$fe_config,$dataStatus,$component_key,$keyFacility)
	{
		$ccaTemp=mysqli_query($link, "SELECT * FROM CCA_TempSensors WHERE fkHeader=(SELECT keyId FROM TestData_header WHERE
		fkTestData_Type='$keyTestDataType' AND fkFE_Config='$fe_config' AND fkDataStatus='$dataStatus'
		AND fkFE_Components='$component_key' AND keyFacility='$keyFacility' AND DataSetGroup='1'
		ORDER BY TS DESC Limit 1) AND fkFacility='$keyFacility' ORDER BY fkHeader ASC")
		or die("Could not get Temp data" .mysql_error());

		return $ccaTemp;
	}
	function getCryostatTemps($keyTestDataType,$fe_config,$dataStatus,$component_key,$keyFacility)
	{
		$cryostatTemp=mysqli_query($link, "SELECT * FROM CryostatTemps WHERE
		fkHeader=(SELECT keyId FROM TestData_header WHERE
		fkTestData_Type='$keyTestDataType' AND fkFE_Config='$fe_config' AND fkDataStatus='$dataStatus'
		AND fkFE_Components='$component_key' AND keyFacility='$keyFacility' AND DataSetGroup='1'
		ORDER BY TS DESC Limit 1)
		AND fkFacility='$keyFacility'")
		or die("Could not get Temp data" .mysql_error());

		return $cryostatTemp;
	}
	function getFloogHealth($keyTestDataType,$fe_config,$dataStatus,$component_key,$keyFacility)
	{
		$flooghealth=mysqli_query($link, "SELECT * FROM FLOOGdist WHERE fkHeader=(SELECT keyId FROM TestData_header WHERE
		fkTestData_Type='$keyTestDataType' AND fkFE_Config='$fe_config' AND fkDataStatus='$dataStatus'
		AND fkFE_Components='$component_key' AND keyFacility='$keyFacility' AND DataSetGroup='1'
		ORDER BY TS DESC Limit 1) AND fkFacility='$keyFacility'")
		or die("Could not get floog data" .mysql_error());

		return $flooghealth;
	}
	function getIFSwitchTemps($keyTestDataType,$fe_config,$dataStatus,$component_key,$keyFacility)
	{
		$ifswitchTemp=mysqli_query($link, "SELECT * FROM IFSwitchTemps WHERE fkHeader=(SELECT keyId FROM TestData_header
		WHERE fkTestData_Type='$keyTestDataType' AND fkFE_Config='$fe_config' AND fkDataStatus='$dataStatus'
		AND fkFE_Components='$component_key' AND keyFacility='$keyFacility' AND DataSetGroup='1'
		ORDER BY TS DESC Limit 1) AND fkFacility='$keyFacility'")
		or die("Could not get IF Switch Temp data" .mysql_error());

		return $ifswitchTemp;
	}
	function getWCApaBias($keyTestDataType,$fe_config,$dataStatus,$component_key,$keyFacility)
	{
		$wcapabias=mysqli_query($link, "SELECT * FROM WCA_PA_bias WHERE fkHeader=(SELECT keyId FROM TestData_header WHERE
		fkTestData_Type='$keyTestDataType' AND fkFE_Config='$fe_config' AND fkDataStatus='$dataStatus'
		AND fkFE_Components='$component_key' AND keyFacility='$keyFacility' AND DataSetGroup='1'
		ORDER BY TS DESC Limit 1) AND fkFacility='$keyFacility' ORDER BY Band ASC")
		or die("Could not get WCA PA bias data" .mysql_error());

		return $wcapabias;
	}
	function getWCAamcBias($keyTestDataType,$fe_config,$dataStatus,$component_key,$keyFacility)
	{
		$wca_amc_bias=mysqli_query($link, "SELECT * FROM WCA_AMC_bias WHERE fkHeader=(SELECT keyId FROM TestData_header WHERE
		fkTestData_Type='$keyTestDataType' AND fkFE_Config='$fe_config' AND fkDataStatus='$dataStatus'
		AND fkFE_Components='$component_key' AND keyFacility='$keyFacility' AND DataSetGroup='1'
		ORDER BY TS DESC Limit 1) AND fkFacility='$keyFacility' ORDER BY Band ASC")
		or die("Could not get WCA PA bias data" .mysql_error());

		return $wca_amc_bias;
	}
	function getWCAmiscData($keyTestDataType,$fe_config,$dataStatus,$component_key,$keyFacility)
	{
		$wca_misc_bias=mysqli_query($link, "SELECT * FROM WCA_Misc_bias WHERE fkHeader=(SELECT keyId FROM TestData_header WHERE
		fkTestData_Type='$keyTestDataType' AND fkFE_Config='$fe_config' AND fkDataStatus='$dataStatus'
		AND fkFE_Components='$component_key' AND keyFacility='$keyFacility' AND DataSetGroup='1'
		ORDER BY TS DESC Limit 1) AND fkFacility='$keyFacility'")
		or die("Could not get WCA misc bias data" .mysql_error());

		return $wca_misc_bias;
	}
	function getCPDSdata($keyTestDataType,$fe_config,$dataStatus,$component_key,$keyFacility)
	{
		$cpds_data=mysqli_query($link, "SELECT * FROM CPDS_monitor WHERE fkHeader=(SELECT keyId FROM TestData_header WHERE
		fkTestData_Type='$keyTestDataType' AND fkFE_Config='$fe_config' AND fkDataStatus='$dataStatus'
		AND fkFE_Components='$component_key' AND keyFacility='$keyFacility' AND DataSetGroup='1'
		ORDER BY TS DESC Limit 1) AND fkFacility='$keyFacility'")
		or die("Could not get CPDS data" .mysql_error());

		return $cpds_data;
	}
	function getLPRWarmHealth($keyTestDataType,$fe_config,$dataStatus,$component_key,$keyFacility)
	{
		$lpr_health=mysqli_query($link, "SELECT * FROM LPR_WarmHealth WHERE fkHeader=(SELECT keyId FROM TestData_header WHERE
		fkTestData_Type='$keyTestDataType' AND fkFE_Config='$fe_config' AND fkDataStatus='$dataStatus'
		AND fkFE_Components='$component_key' AND keyFacility='$keyFacility' AND DataSetGroup='1'
		ORDER BY TS DESC Limit 1) AND fkFacility='$keyFacility'")
		or die("Could not get LPR data" .mysql_error());

		return $lpr_health;
	}
	function getPhotomixerHealth($keyTestDataType,$feconfig,$dataStatus,$keyFacility)
	{
		$photomixer_health=mysqli_query($link, "SELECT * FROM Photomixer_WarmHealth WHERE fkHeader=(SELECT keyId FROM TestData_header WHERE
		fkTestData_Type='$keyTestDataType' AND fkFE_Config='$feconfig' AND fkDataStatus='$dataStatus'
		AND keyFacility='$keyFacility' AND DataSetGroup='1'
		ORDER BY TS DESC Limit 1) AND fkFacility='$keyFacility'")
		or die("Could not get PM data" .mysql_error());

		return $photomixer_health;
	}
	function getRateofRise($keyTestDataType,$feConfig,$dataStatus,$keyFacility)
	{
		$cryostat_ror=mysqli_query($link, "SELECT * FROM CryostatROR WHERE fkHeader=(SELECT keyId FROM TestData_header WHERE
		fkTestData_Type='$keyTestDataType' AND fkFE_Config='$feConfig' AND fkDataStatus='$dataStatus'
		AND keyFacility='$keyFacility'
		ORDER BY TS DESC Limit 1) AND fkFacility='$keyFacility'")
		or die("Could not get ROR data" .mysql_error());

		return $cryostat_ror;
	}
	function getMaxKey($frontend_sn,$keyFacility)
	{
		$getmaxkey=mysqli_query($link, "SELECT MAX(keyFrontEnds) AS MaxKey FROM Front_Ends WHERE SN='$frontend_sn'
		 AND keyFacility='$keyFacility'")
		or die("Could not get data" .mysql_error());

		$maxkey=ADAPT_mysqli_result($getmaxkey,0,"MaxKey");
		return $maxkey;
	}
	function getWarmPreampParams($keyComponent,$fkFacility)
	{
		$PreampParams=mysqli_query($link, "SELECT * FROM CCA_PreampParams WHERE fkComponent='$keyComponent'
		AND fkFacility='$fkFacility'
		ORDER BY FreqLO ASC, Pol ASC, SB ASC")
		or die("Could not get Preamp Params" .mysql_error());

		return $PreampParams;
	}
	function getWarmMixerParams($keyComponent,$fkFacility)
	{
		$mixerparams=mysqli_query($link, "SELECT * FROM CCA_MixerParams WHERE fkComponent='$keyComponent'
		AND fkFacility='$fkFacility'
		ORDER BY FreqLO ASC")
		or die("Could not get mixer params" .mysql_error());

		return $mixerparams;
	}
	function getWarmWCAYig($fkFE_Components,$fkFacility)
	{
		$wca_yig=mysqli_query($link, "SELECT FloYIG,FhiYig FROM WCAs WHERE fkFE_Component='$fkFE_Components'
		AND fkFacility='$fkFacility' ORDER BY TS DESC LIMIT 1")
		or die("Could not get yig values" .mysql_error());

		$yig_lo=ADAPT_mysqli_result($wca_yig,0,"FloYIG");
		$yig_hi=ADAPT_mysqli_result($wca_yig,0,"FhiYig");

		$yig_array=array("yiglo" =>$yig_lo,"yighi" =>$yig_hi);
		return $yig_array;
	}
	function getWarmLOParams($fkFE_Components,$fkFacility)
	{
		$loparams=mysqli_query($link, "SELECT * FROM WCA_LOParams WHERE fkComponent='$fkFE_Components'
		AND fkFacility='$fkFacility' ORDER BY FreqLO ASC")
		or die("Could not get LOParams" .mysql_error());

		return $loparams;
	}
}

?>