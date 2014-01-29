<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.logger.php');
require_once('GetData.php');

function createfiles($frontend_sn,$warmconf_sn)
{
	$logger = new Logger('CreateXMLDoc.txt','w');

	$facility=40;

	//create object
	$oComponent=new GetData;

	$dom=new DOMDocument('1.0','UTF-8');
	$doc->formatOutput = true;

	$logger->WriteLogFile('CreateTempTable()');

	$oComponent->CreateTempTable($frontend_sn,$facility);

	// for <FEICDataSet>
	$rootElement=$dom->createElement('FEICDataSet','');
	$dom->appendChild($rootElement);
	$rootElement->setAttribute('version','1.0');


	//============================================for Warm PAS data======================================================

	$warmpas=$dom->createElement('FrontEndWarmConfig','');
	$rootElement->appendChild($warmpas);
	$warmpas->setAttribute('id',$warmconf_sn);

	if ($warmconf_sn != "" and $warmconf_sn != "0")
	{

		//get frontend Warm PAS data
		$logger->WriteLogFile('getFrontEndData($warmconf_sn)');

		$frontend_warm_rs=$oComponent->getFrontEndData($warmconf_sn,$facility);
		while($frontend_warm_value=mysql_fetch_array($frontend_warm_rs))
		{
			//for <docs>
			$docs=$dom->createElement('Docs',$frontend_warm_value['Docs']);
			$warmpas->appendChild($docs);
			//for <Notes>
			$notes=$dom->createElement('Notes',$frontend_warm_value['Notes']);
			$warmpas->appendChild($notes);
			//for <TS>
			$ts=$dom->createElement('TS',$frontend_warm_value['TS']);
			$warmpas->appendChild($ts);

			//$fkFrontEnd=$frontend_value['keyFrontEnds'];
		}

		$fe_warm_maxkey=$oComponent->getMaxKey($warmconf_sn,$facility);

		$Component_Array=array("Cryostat"=>6,"LPR"=>17,"Floog Distributor"=>96,"IF Switch Assembly"=>129,"CPDS"=>4,
								"CCA"=>20,"WCA"=>11);

		//go through each component in the array
		$logger->WriteLogFile('starting fetch components loop...');

		foreach($Component_Array as $key=>$value)
		{
			$logger->WriteLogFile('getComponents: '.$value);

			$warm_components_rs=$oComponent->getComponents($value,$fe_warm_maxkey,$facility);
			while($warm_components_value=mysql_fetch_array($warm_components_rs))
			{
				//for <component>
				$warmcomponent=$dom->createElement('component','');
				$warmpas->appendChild($warmcomponent);
				$warmcomponent->setAttribute('id',$warm_components_value['keyId']);
				$warmcomponent->setAttribute('type',$value);
				$warmcomponent->setAttribute('typeDesc',$key);
				// for <TS>
				$componentType=$dom->createElement('TS',$warm_components_value['TS']);
				$warmcomponent->appendChild($componentType);
				//for <Band>
				$componentType=$dom->createElement('Band',$warm_components_value['Band']);
				$warmcomponent->appendChild($componentType);
				//for <SN>
				if($warm_components_value['SN'] == "")
				{
					$warm_component_sn= "null";
				}
				else
				{
					$warm_component_sn=$warm_components_value['SN'];
				}
				$componentType=$dom->createElement('SN',$warm_component_sn);
				$warmcomponent->appendChild($componentType);
				//for <ESN>
				if($warm_components_value['ESN1'] == "")
				{
					$warm_component_esn1= "null";
				}
				else
				{
					$warm_component_esn1=$warm_components_value['ESN1'];
				}
				$componentType=$dom->createElement('ESN1',$warm_component_esn1);
				$warmcomponent->appendChild($componentType);

				if($warm_components_value['ESN2'] == "")
				{
					$warm_component_esn2= "null";
				}
				else
				{
					$warm_component_esn2=$warm_components_value['ESN2'];
				}
				$componentType=$dom->createElement('ESN2',$warm_component_esn2);
				$warmcomponent->appendChild($componentType);

				//for <Description>
				$componentType=$dom->createElement('Description',$warm_components_value['Component_Description']);
				$warmcomponent->appendChild($componentType);

				if($value==11 )//if component is WCA
				{
					$yigvalues_array=$oComponent->getWarmWCAYig($warm_components_value['keyId'],$facility);
					if($yigvalues_array['yiglo'] != "")//if wca has yig values
					{
						$yig=$dom->createElement('FLOYIG',$yigvalues_array['yiglo']);
						$warmcomponent->appendChild($yig);

						$yig=$dom->createElement('FHIYIG',$yigvalues_array['yighi']);
						$warmcomponent->appendChild($yig);

						$LOParams=$dom->createElement('LOParams','');
						$warmcomponent->appendChild($LOParams);

						$loparams_rs=$oComponent->getWarmLOParams($warm_components_value['keyId'],$facility);

							while($loparams_array=mysql_fetch_array($loparams_rs))
							{
								$freqlo=$dom->createElement('LOParam','');
								$freqlo->setAttribute('freq',$loparams_array['FreqLO']);
								$LOParams->appendChild($freqlo);

								$vdpa_a=$dom->createElement('VDP0',$loparams_array['VDP0']);
								$freqlo->appendChild($vdpa_a);

								$vdpa_b=$dom->createElement('VDP1',$loparams_array['VDP1']);
								$freqlo->appendChild($vdpa_b);

								$vgpa_a=$dom->createElement('VGP0',$loparams_array['VGP0']);
								$freqlo->appendChild($vgpa_a);

								$vgpa_b=$dom->createElement('VGP1',$loparams_array['VGP1']);
								$freqlo->appendChild($vgpa_b);
							}
						}
				}
				if($value==20) //if component is CCA
				{
					//get Mixer Parameters
					$mixerparams_rs=$oComponent->getWarmMixerParams($warm_components_value['keyId'],$facility);
					//$num_rec_returned=mysql_num_rows($mixerparams_rs);

					//for MixerParams
					$mixerparams=$dom->createElement('MixerParams','');
					$warmcomponent->appendChild($mixerparams);

					while($mixerparams_array=mysql_fetch_array($mixerparams_rs))
					{
						$freqlo_current=$mixerparams_array['FreqLO'];

						if($freqlo_current != $freqlo_previous) //to group by freqLO
						{
							$freqlo_previous=$freqlo_current;
							//for MixerParam
							$mp_freq=$dom->createElement('MixerParam','');
							$mixerparams->appendChild($mp_freq);
							$mp_freq->setAttribute('LOFreq',$freqlo_current);
						}
						//sis tag is SIS+POL+SB
						$sis_val="SIS" . $mixerparams_array['Pol'] . $mixerparams_array['SB'];
						// for sis
						$sis=$dom->createElement($sis_val,'');
						$mp_freq->appendChild($sis);
						// for VJ_set
						$vj=$dom->createElement('VJ_set',$mixerparams_array['VJ']);
						$sis->appendChild($vj);
						// for IJ_set
						$ij=$dom->createElement('IJ_set',$mixerparams_array['IJ']);
						$sis->appendChild($ij);
						$imag=$dom->createElement('IMag',$mixerparams_array['IMAG']);
						$sis->appendChild($imag);
					}

					//get Preamp Parameters.
					$preampparams_rs=$oComponent->getWarmPreampParams($warm_components_value['keyId'],$facility);

					//for PreampParams
					$preampparams=$dom->createElement('PreampParams','');
					$warmcomponent->appendChild($preampparams);

					while($preampParams_array=mysql_fetch_array($preampparams_rs))
					{
						$preamp_freqlo_current=$preampParams_array['FreqLO'];

						if($preamp_freqlo_current != $preamp_freqlo_previous) //to group by freqLO
						{
							$preamp_freqlo_previous=$preamp_freqlo_current;
							//for PreampParam
							$pp_freq=$dom->createElement('PreampParam','');
							$preampparams->appendChild($pp_freq);
							$pp_freq->setAttribute('LOFreq',$preamp_freqlo_current);
						}

						//lna tag is LNA+POL+SB
						$lna_val="LNA" . $preampParams_array['Pol'] . $preampParams_array['SB'];
						// for LNA
						$lna=$dom->createElement($lna_val,'');
						$pp_freq->appendChild($lna);
						// for VD_set
						$vd1=$dom->createElement('VD1',$preampParams_array['VD1']);
						$lna->appendChild($vd1);

						$vd2=$dom->createElement('VD2',$preampParams_array['VD2']);
						$lna->appendChild($vd2);

						$vd3=$dom->createElement('VD3',$preampParams_array['VD3']);
						$lna->appendChild($vd3);
						// for ID_set
						$id1=$dom->createElement('ID1',$preampParams_array['ID1']);
						$lna->appendChild($id1);

						$id2=$dom->createElement('ID2',$preampParams_array['ID2']);
						$lna->appendChild($id2);

						$id3=$dom->createElement('ID3',$preampParams_array['ID3']);
						$lna->appendChild($id3);
						//for VG_Set
						$vg1=$dom->createElement('VG1',$preampParams_array['VG1']);
						$lna->appendChild($vg1);

						$vg2=$dom->createElement('VG2',$preampParams_array['VG2']);
						$lna->appendChild($vg2);

						$vg3=$dom->createElement('VG3',$preampParams_array['VG3']);
						$lna->appendChild($vg3);
					}
				}
			}
		}
	}

	//================================================for <FrontEnd>=====================================================
	$frontend=$dom->createElement('FrontEnd','');
	$rootElement->appendChild($frontend);
	$frontend->setAttribute('id',$frontend_sn);

	$fe_maxkey=$oComponent->getMaxKey($frontend_sn,$facility);

	//get frontend data
	$logger->WriteLogFile('getFrontEndData($frontend_sn,$facility)');

	$frontend_rs=$oComponent->getFrontEndData($frontend_sn,$facility);

	while($frontend_value=mysql_fetch_array($frontend_rs))
	{
		//for <docs>
		$docs=$dom->createElement('Docs',$frontend_value['Docs']);
		$frontend->appendChild($docs);
		//for <Notes>
		$notes=$dom->createElement('Notes',$frontend_value['Notes']);
		$frontend->appendChild($notes);
		//for <TS>
		$ts=$dom->createElement('TS',$frontend_value['TS']);
		$frontend->appendChild($ts);

		$fkFrontEnd=$frontend_value['keyFrontEnds'];
	}

	//create array for all devices with thier names and keyComponentType value.
	$Component_Array=array("Cryostat"=>6,"LPR"=>17,"Floog Distributor"=>96,"IF Switch Assembly"=>129,"CPDS"=>4,
							"CCA"=>20,"WCA"=>11);

	//go through each component in the array
	$logger->WriteLogFile('starting fetch components loop...');

	foreach($Component_Array as $key=>$value)
	{
		$logger->WriteLogFile('getComponents: '.$value);

		$components_rs=$oComponent->getComponents($value,$fe_maxkey,$facility);
		while($components_value=mysql_fetch_array($components_rs))
		{
			//for <component>
			$component=$dom->createElement('component','');
			$frontend->appendChild($component);
			$component->setAttribute('id',$components_value['keyId']);
			$component->setAttribute('type',$value);
			$component->setAttribute('typeDesc',$key);
			// for <TS>
			$componentType=$dom->createElement('TS',$components_value['TS']);
			$component->appendChild($componentType);
			//for <Band>
			$componentType=$dom->createElement('Band',$components_value['Band']);
			$component->appendChild($componentType);
			//for <SN>
			$componentType=$dom->createElement('SN',$components_value['SN']);
			$component->appendChild($componentType);
			//for <ESN>
			$componentType=$dom->createElement('ESN1',$components_value['ESN1']);
			$component->appendChild($componentType);
			$componentType=$dom->createElement('ESN2',$components_value['ESN2']);
			$component->appendChild($componentType);
			//for <Description>
			$componentType=$dom->createElement('Description',$components_value['Component_Description']);
			$component->appendChild($componentType);

			if($value==11 )//if component is WCA
			{
				$yigvalues_array=$oComponent->getWCAYig($components_value['keyId'],$facility);
				if($yigvalues_array['yiglo'] != "")//if wca has yig values
				{
					$yig=$dom->createElement('FLOYIG',$yigvalues_array['yiglo']);
					$component->appendChild($yig);

					$yig=$dom->createElement('FHIYIG',$yigvalues_array['yighi']);
					$component->appendChild($yig);

					$LOParams=$dom->createElement('LOParams','');
					$component->appendChild($LOParams);

					$loparams_rs=$oComponent->getLOParams($components_value['keyId'],$facility);

	                if($loparams_rs != "")
	                {
	                    while($loparams_array=mysql_fetch_array($loparams_rs))
	                    {
	                        $freqlo=$dom->createElement('LOParam','');
	                        $freqlo->setAttribute('freq',$loparams_array['FreqLO']);
	                        $LOParams->appendChild($freqlo);

	                        $vdpa_a=$dom->createElement('VDP0',$loparams_array['VDP0']);
	                        $freqlo->appendChild($vdpa_a);

	                        $vdpa_b=$dom->createElement('VDP1',$loparams_array['VDP1']);
	                        $freqlo->appendChild($vdpa_b);

	                        $vgpa_a=$dom->createElement('VGP0',$loparams_array['VGP0']);
	                        $freqlo->appendChild($vgpa_a);

	                        $vgpa_b=$dom->createElement('VGP1',$loparams_array['VGP1']);
	                        $freqlo->appendChild($vgpa_b);
	                    }
	                }
				}
			}
			if($value==20) //if component is CCA
			{
				//get Mixer Parameters
				$mixerparams_rs=$oComponent->getMixerParams($components_value['keyId'],$facility);
				//$num_rec_returned=mysql_num_rows($mixerparams_rs);

				//for MixerParams
				$mixerparams=$dom->createElement('MixerParams','');
				$component->appendChild($mixerparams);
				if($mixerparams_rs != "")
	            {
	                while($mixerparams_array=mysql_fetch_array($mixerparams_rs))
	                {
	                    $freqlo_current=$mixerparams_array['FreqLO'];

	                    if($freqlo_current != $freqlo_previous) //to group by freqLO
	                    {
	                        $freqlo_previous=$freqlo_current;
	                        //for MixerParam
	                        $mp_freq=$dom->createElement('MixerParam','');
	                        $mixerparams->appendChild($mp_freq);
	                        $mp_freq->setAttribute('LOFreq',$freqlo_current);
	                    }
	                    //sis tag is SIS+POL+SB
	                    $sis_val="SIS" . $mixerparams_array['Pol'] . $mixerparams_array['SB'];
	                    // for sis
	                    $sis=$dom->createElement($sis_val,'');
	                    $mp_freq->appendChild($sis);
	                    // for VJ_set
	                    $vj=$dom->createElement('VJ_set',$mixerparams_array['VJ']);
	                    $sis->appendChild($vj);
	                    // for IJ_set
	                    $ij=$dom->createElement('IJ_set',$mixerparams_array['IJ']);
	                    $sis->appendChild($ij);
	                    $imag=$dom->createElement('IMag',$mixerparams_array['IMAG']);
	                    $sis->appendChild($imag);
	                }
				}
				//get Preamp Parameters.
				$preampparams_rs=$oComponent->getPreampParams($components_value['keyId'],$facility);

				//for PreampParams
				$preampparams=$dom->createElement('PreampParams','');
				$component->appendChild($preampparams);
				if($preampparams_rs != "")
	            {
	                while($preampParams_array=mysql_fetch_array($preampparams_rs))
	                {
	                    $preamp_freqlo_current=$preampParams_array['FreqLO'];

	                    if($preamp_freqlo_current != $preamp_freqlo_previous) //to group by freqLO
	                    {
	                        $preamp_freqlo_previous=$preamp_freqlo_current;
	                        //for PreampParam
	                        $pp_freq=$dom->createElement('PreampParam','');
	                        $preampparams->appendChild($pp_freq);
	                        $pp_freq->setAttribute('LOFreq',$preamp_freqlo_current);
	                    }

	                    //lna tag is LNA+POL+SB
	                    $lna_val="LNA" . $preampParams_array['Pol'] . $preampParams_array['SB'];
	                    // for LNA
	                    $lna=$dom->createElement($lna_val,'');
	                    $pp_freq->appendChild($lna);
	                    // for VD_set
	                    $vd1=$dom->createElement('VD1',$preampParams_array['VD1']);
	                    $lna->appendChild($vd1);

	                    $vd2=$dom->createElement('VD2',$preampParams_array['VD2']);
	                    $lna->appendChild($vd2);

	                    $vd3=$dom->createElement('VD3',$preampParams_array['VD3']);
	                    $lna->appendChild($vd3);
	                    // for ID_set
	                    $id1=$dom->createElement('ID1',$preampParams_array['ID1']);
	                    $lna->appendChild($id1);

	                    $id2=$dom->createElement('ID2',$preampParams_array['ID2']);
	                    $lna->appendChild($id2);

	                    $id3=$dom->createElement('ID3',$preampParams_array['ID3']);
	                    $lna->appendChild($id3);
	                    //for VG_Set
	                    $vg1=$dom->createElement('VG1',$preampParams_array['VG1']);
	                    $lna->appendChild($vg1);

	                    $vg2=$dom->createElement('VG2',$preampParams_array['VG2']);
	                    $lna->appendChild($vg2);

	                    $vg3=$dom->createElement('VG3',$preampParams_array['VG3']);
	                    $lna->appendChild($vg3);
	                }
	            }
			}
		}
	}

	// ====================================================Test Data===================================================

	$testdata=$dom->createElement('TestData','');
	$rootElement->appendChild($testdata);

	$testTypes_array=array("PAS YFactor" => 15,"IF Spectrum" => 7, "IF Total Power" => 6,"CCA LNA Bias" => 1,
							"CCA SIS Bias" => 3, "CCA Temperature" => 2, "Cryostat Temps" => 4,
							"LPR and Photomixer Health" => 8, "IF Switch Temps" => 10 ,
							"FLOOG Distributor Health" => 5, "WCA PA Bias" => 13,"WCA AMC Bias" => 12,
							"WCA Misc Bias" => 14, "CPDS Monitor" => 24, "Cryostat Rate of Rise" => 25);

	$ifchannel_array=array("IF0", "IF1","IF2","IF3");


	//for Pas Y Factor data
	$logger->WriteLogFile('PAS YFactor');

	$yfactor_config=$oComponent->getConfigData($testTypes_array['PAS YFactor'],1,$frontend_sn,$facility);
		if($yfactor_config != "")
		{
			while($yfactor_config_array=mysql_fetch_array($yfactor_config))
			{
				$yig_TestItem=$dom->createElement('TestItem','');
				$testdata->appendChild($yig_TestItem);
				$yig_TestItem->setAttribute("id",$yfactor_config_array['keyId']);
				$yig_TestItem->setAttribute("type",$testTypes_array['PAS YFactor']);
				$yig_TestItem->setAttribute("typeDesc",'PAS YFactor');
				$yig_TestItem->setAttribute("dataStatus",1);

				//y factor configuration data
				$yig_band=$dom->createElement('Band',$yfactor_config_array['Band']);
				$yig_TestItem->appendChild($yig_band);

				$yig_component=$dom->createElement('Component','');
				$yig_TestItem->appendChild($yig_component);

				$yig_frontend=$dom->createElement('FrontEnd',$frontend_sn);
				$yig_TestItem->appendChild($yig_frontend);

				$yig_ts=$dom->createElement('TS',$yfactor_config_array['TS']);
				$yig_TestItem->appendChild($yig_ts);

				// y factor test data.
				$yig_content=$dom->createElement('Content','');
				$yig_TestItem->appendChild($yig_content);

				$yig_main=$dom->createElement('yfactor','');
				$yig_content->appendChild($yig_main);

				$yfactor_rs_1=$oComponent->getYFactorData($testTypes_array['PAS YFactor'],$yfactor_config_array['fkFE_Config'],$yfactor_config_array['Band'],$facility);
				$yfactor_rs_2=$oComponent->getYFactorData($testTypes_array['PAS YFactor'],$yfactor_config_array['fkFE_Config'],$yfactor_config_array['Band'],$facility);

                if(mysql_num_rows($yfactor_rs_1) > 0)
                {
				    $yfactor_freqLO=mysql_result($yfactor_rs_1,0,"FreqLO");
				}
				//for freqLO
				$yig_freqLO=$dom->createElement('LOFreq',$yfactor_freqLO);
				$yig_main->appendChild($yig_freqLO);

				$i=0;
				while($yfactor_array=mysql_fetch_array($yfactor_rs_2))
				{
					//$ifchannel=utf8_encode($yfactor_array['IFchannel']);
					//for some reason an error is raised when trying to use $yfactor_array['IFchannel'] as tag name.
					//so had to put the tag names in $ifchannel_array.

					$yig_IF_channel="IF". $yfactor_array['IFchannel'];

					$yig_ifchannel=$dom->createElement($yig_IF_channel,'');
					$yig_main->appendChild($yig_ifchannel);

					//$yig_ifchannel=$dom->createElement($yfactor_array['IFchannel'],'');
					//$yig_main->appendChild($yig_ifchannel);

					$yig_switchgain=$dom->createElement('IFSwitchGain','15.0');
					$yig_ifchannel->appendChild($yig_switchgain);

					$yig_phot=$dom->createElement('PHot',$yfactor_array['Phot_dBm']);
					$yig_ifchannel->appendChild($yig_phot);

					$yig_pcold=$dom->createElement('PCold',$yfactor_array['Pcold_dBm']);
					$yig_ifchannel->appendChild($yig_pcold);

					$yig_yfactor=$dom->createElement('Yfactor',$yfactor_array['Y']);
					$yig_ifchannel->appendChild($yig_yfactor);

					$i=$i+1;
				}
			}
		}

	//for IF Spectrum
	Include_once "PASFunction.php";

	$logger->WriteLogFile('IF Spectrum');

	//get the TestDataHeader records for IF spectrum where the UseForPAI flag is TRUE:
	$ifspectrum_config=$oComponent->getConfigData($testTypes_array['IF Spectrum'],3,$frontend_sn,$facility,TRUE);
	if($ifspectrum_config != "")
	{
	    //loop through the test data header records:
	    $prevBand = "";
	    $allKeys = "";
		while($ifspectrum_config_array=mysql_fetch_array($ifspectrum_config))
		{
		    //get the IFSpectrum_Subheader records corresponding to the TDH record, at the required LO frequency and IFGain=15:
		    $band = $ifspectrum_config_array['Band'];
		    $testDataHeaderId = $ifspectrum_config_array['keyId'];

		    // match records at either loFreq1 or loFreq2 (to handle some special cases which have come up):
		    switch ($band) {
		        case 1:
		            $loFreq1 = '0';
		            $loFreq2 = '0';
		            break;
	            case 2:
		            $loFreq1 = '0';
		            $loFreq2 = '0';
	                break;
                case 3:
		            $loFreq1 = '100';
		            $loFreq2 = '100';
                    break;
                case 4:
		            $loFreq1 = '145';
		            $loFreq2 = '145';
                    break;
                case 5:
		            $loFreq1 = '181';
		            $loFreq2 = '181';
                    break;
                case 6:
		            $loFreq1 = '241';
		            $loFreq2 = '245';
                    break;
                case 7:
		            $loFreq1 = '323';
		            $loFreq2 = '323';
                    break;
                case 8:
		            $loFreq1 = '440';
		            $loFreq2 = '440';
                    break;
                case 9:
		            $loFreq1 = '662';
		            $loFreq2 = '662';
                    break;
                case 10:
		            $loFreq1 = '870';
		            $loFreq2 = '874';
                    break;
                default:
		            $loFreq1 = '0';
		            $loFreq2 = '0';
		    }
		    $ifspectrum_rs=$oComponent -> getIFSpectrumData($testDataHeaderId, $loFreq1, $loFreq2, $facility);

		    // Fetch the first IFSpectrum_Subheader record:
		    $ifspectrum_subheader_array = mysql_fetch_array($ifspectrum_rs);

		    // if an IFSpectrum_Subheader record was found...
		    if ($ifspectrum_subheader_array) {

		        if ($band != $prevBand) {
		            $prevBand = $band;
		            $allKeys = "";
    		        // Start adding XML output data:
        		    $ifspectrum_TestItem=$dom->createElement('TestItem','');
        			$testdata->appendChild($ifspectrum_TestItem);
        			$ifspectrum_TestItem->setAttribute("id", $testDataHeaderId);
        			$ifspectrum_TestItem->setAttribute("type", $testTypes_array['IF Spectrum']);
        			$ifspectrum_TestItem->setAttribute("typeDesc", 'IF Spectrum');
        			$ifspectrum_TestItem->setAttribute("dataStatus", 3);

        			//IF Spectrum configuration data
        			$ifspectrum_band=$dom->createElement('Band',$band);
        			$ifspectrum_TestItem->appendChild($ifspectrum_band);

        			$ifspectrum_component=$dom->createElement('Component','');
        			$ifspectrum_TestItem->appendChild($ifspectrum_component);

        			$ifspectrum_frontend=$dom->createElement('FrontEnd',$frontend_sn);
        			$ifspectrum_TestItem->appendChild($ifspectrum_frontend);

        			$ifspectrum_ts=$dom->createElement('TS',$ifspectrum_config_array['TS']);
        			$ifspectrum_TestItem->appendChild($ifspectrum_ts);

        			// IF Spectrum test data.
        			$ifspectrum_content=$dom->createElement('Content', '');
        			$ifspectrum_TestItem->appendChild($ifspectrum_content);

    				$ifspec_main=$dom->createElement('IFSpectrum', '');
    				$ifspectrum_content->appendChild($ifspec_main);

    				$ifspec_lofreq=$dom->createElement('LOFreq', $loFreq);
    				$ifspec_main->appendChild($ifspec_lofreq);

    				$ifspec_rbw=$dom->createElement('RBW_Hz','3000000');
    				$ifspec_main->appendChild($ifspec_rbw);

    				$ifspec_vbw=$dom->createElement('VBW_Hz','3000');
    				$ifspec_main->appendChild($ifspec_vbw);

    				$startFreq = $ifspectrum_subheader_array['StartFreq_Hz'];
    				$stopFreq = $ifspectrum_subheader_array['StopFreq_Hz'];

    				if ($stopFreq < 5000) {
    				    $startFreq = 0;
    				    $stopFreq = 18000000000;
    				}

    				$ifspec_startf=$dom->createElement('StartFreq_Hz', $startFreq);
    				$ifspec_main->appendChild($ifspec_startf);

    				$ifspec_stopf=$dom->createElement('StopFreq_Hz', $stopFreq);
    				$ifspec_main->appendChild($ifspec_stopf);

    				$ifspec_ipattn=$dom->createElement('InputAttn_dB',$ifspectrum_subheader_array['InputAtten_dB']);
    				$ifspec_main->appendChild($ifspec_ipattn);

    				$ifspec_numpts=$dom->createElement('NumPoints',$ifspectrum_subheader_array['NumPts']);
    				$ifspec_main->appendChild($ifspec_numpts);
		        }

		        $allKeys .= $testDataHeaderId . " ";

		        // Add <Trace> elements for each additional IFSpectrum_Subheader record found:
		        do {
      				if ($ifspectrum_subheader_array['Filename'] == '')
    					$filename = getPASfileName($ifspectrum_subheader_array['IFChannel'], $band, $loFreq);
      				else
      					$filename = $ifspectrum_subheader_array['Filename'];

      				$ifspec_trace=$dom->createElement('Trace', '');
    				$ifspec_main->appendChild($ifspec_trace);
    				$ifspec_channel= "IF" . $ifspectrum_subheader_array['IFChannel'];
    				$ifspec_trace->setAttribute('channel', $ifspec_channel);
    				$ifspec_trace->setAttribute('IFGain', $ifspectrum_subheader_array['IFGain']);
    				$ifspec_trace->setAttribute('file', $filename);

    				// Create the output text file:
    				generatePASfile($ifspectrum_subheader_array['keyId'], $ifspectrum_subheader_array['IFChannel'], $band, $loFreq, $facility);

		        } while ($ifspectrum_subheader_array = mysql_fetch_array($ifspectrum_rs));
		    }
		}
	}

	//one iteration for cold test data and one for warm
	for($datastatus_count=1;$datastatus_count<=2;$datastatus_count++)
	{
		//IF Total Power

		$logger->WriteLogFile('IF Total Power ' . $datastatus_count);

		$totpwr_config=$oComponent->getConfigData($testTypes_array['IF Total Power'],$datastatus_count,$frontend_sn,$facility);
		while($totpwr_config_array=mysql_fetch_array($totpwr_config))
		{
			//IF Total Power configuration data

			$totpwr_TestItem=$dom->createElement('TestItem','');
			$testdata->appendChild($totpwr_TestItem);
			$totpwr_TestItem->setAttribute("id",$totpwr_config_array['keyId']);
			$totpwr_TestItem->setAttribute("type",$testTypes_array['IF Total Power']);
			$totpwr_TestItem->setAttribute("typeDesc",'IF Total Power');
			$totpwr_TestItem->setAttribute("dataStatus",$datastatus_count);

			$totpwr_band=$dom->createElement('Band',$totpwr_config_array['Band']);
			$totpwr_TestItem->appendChild($totpwr_band);

			$totpwr_component=$dom->createElement('Component','');
			$totpwr_TestItem->appendChild($totpwr_component);

			$totpwr_frontend=$dom->createElement('FrontEnd',$frontend_sn);
			$totpwr_TestItem->appendChild($totpwr_frontend);

			$totpwr_ts=$dom->createElement('TS',$totpwr_config_array['TS']);
			$totpwr_TestItem->appendChild($totpwr_ts);

			//Total Power test data
			$totpwr_content=$dom->createElement('Content','');
			$totpwr_TestItem->appendChild($totpwr_content);

			$totpwr_rs=$oComponent->getIFTotalPower($testTypes_array['IF Total Power'],$totpwr_config_array['fkFE_Config'],$datastatus_count,$totpwr_config_array['Band'],$facility);
			$totpwr_freqlo_previous="";
			while($totpwr_array=mysql_fetch_array($totpwr_rs))
			{
				$totpwr_freqlo_current=$totpwr_array['FreqLO'];

				if($totpwr_freqlo_current != $totpwr_freqlo_previous)
				{
					$totpwr_freqlo_previous=$totpwr_freqlo_current;
					$tp_count=0;

					$totpwr_main=$dom->createElement('IFTotalPower','');
					$totpwr_content->appendChild($totpwr_main);

					$totpwr_freqlo=$dom->createElement('LOFreq',$totpwr_freqlo_current);
					$totpwr_main->appendChild($totpwr_freqlo);
				}

				$if_channel="IF" . $totpwr_array['IFChannel'];

				//$totpwr_ifchannel=$dom->createElement($ifchannel_array[$tp_count],'');
				$totpwr_ifchannel=$dom->createElement($if_channel,'');
				$totpwr_main->appendChild($totpwr_ifchannel);

				$totpwr_pwr0gain=$dom->createElement('Power_0Gain',$totpwr_array['Power_0dB_gain']);
				$totpwr_ifchannel->appendChild($totpwr_pwr0gain);

				$totpwr_pwr15gain=$dom->createElement('Power_15Gain',$totpwr_array['Power_15dB_gain']);
				$totpwr_ifchannel->appendChild($totpwr_pwr15gain);

				$tp_count=$tp_count+1;
			}
		}
	//CCA_LNA_Bias

		$logger->WriteLogFile('CCA LNA Bias ' . $datastatus_count);

		$lnabias_cold_config=$oComponent->getConfigData($testTypes_array['CCA LNA Bias'],$datastatus_count,$frontend_sn,$facility);
	   while($lnabias_cold_config_array=mysql_fetch_array($lnabias_cold_config))
	   {
		//LNA Bias configuration data
		$lnabias_cold_TestItem=$dom->createElement('TestItem','');
		$testdata->appendChild($lnabias_cold_TestItem);
		$lnabias_cold_TestItem->setAttribute("id",$lnabias_cold_config_array['keyId']);
		$lnabias_cold_TestItem->setAttribute("type",$testTypes_array['CCA LNA Bias']);
		$lnabias_cold_TestItem->setAttribute("typeDesc",$lnabias_cold_config_array['Notes']);
		$lnabias_cold_TestItem->setAttribute("dataStatus",$lnabias_cold_config_array['fkDataStatus']);

	   	$lnabias_cold_band=$dom->createElement('Band',$lnabias_cold_config_array['Band']);
		$lnabias_cold_TestItem->appendChild($lnabias_cold_band);

		$lnabias_cold_component=$dom->createElement('Component',$lnabias_cold_config_array['fkFE_Components']);
		$lnabias_cold_TestItem->appendChild($lnabias_cold_component);

		$lnabias_cold_frontend=$dom->createElement('FrontEnd',$frontend_sn);
		$lnabias_cold_TestItem->appendChild($lnabias_cold_frontend);

		$lnabias_cold_ts=$dom->createElement('TS',$lnabias_cold_config_array['TS']);
		$lnabias_cold_TestItem->appendChild($lnabias_cold_ts);

		$lnabias_freqlo_previous="";

		$lnabias_rs=$oComponent->getCCALNABias($testTypes_array['CCA LNA Bias'], $lnabias_cold_config_array['fkFE_Config'],$datastatus_count,$lnabias_cold_config_array['fkFE_Components'],$facility);
		//LNA bias test data

		$lnabias_content=$dom->createElement('Content','');
		$lnabias_cold_TestItem->appendChild($lnabias_content);

		while($lnabias_array=mysql_fetch_array($lnabias_rs))
		{
			$lnabias_freqlo_current=$lnabias_array['FreqLO'];

			if($lnabias_freqlo_current != $lnabias_freqlo_previous) //to group by freqLO
			{
				$lnabias_freqlo_previous=$lnabias_freqlo_current;

				$lnabias_main=$dom->createElement('CCALNABias','');
				$lnabias_content->appendChild($lnabias_main);

				$lnabias_freqlo=$dom->createElement('LOFreq',$lnabias_freqlo_current);
				$lnabias_main->appendChild($lnabias_freqlo);
			}

			$lna_val_current=$lnabias_array['Pol'] . $lnabias_array['SB'];

			if($lna_val_current != $lna_val_previous)
			{

				$lna_val_previous=$lna_val_current;
				//lna tag is LNA+POL+SB
				$lna_val="LNA" . $lna_val_current;
				// for LNA
				$cca_lna=$dom->createElement($lna_val,'');
				$lnabias_main->appendChild($cca_lna);
			}
			$param_count=$lnabias_array['Stage'];
			//for VD_read
			$vd_read=$dom->createElement('VD' . $param_count . '_read',$lnabias_array['VdRead']);
			$cca_lna->appendChild($vd_read);
			//for ID_read
			$id_read=$dom->createElement('ID'. $param_count . '_read',$lnabias_array['IdRead']);
			$cca_lna->appendChild($id_read);
			//for VG_read
			$vg_read=$dom->createElement('VG'. $param_count . '_read',$lnabias_array['VgRead']);
			$cca_lna->appendChild($vg_read);

			//$param_count=$param_count+1;
		}
	   }
	//CCA_SIS_Bias

		$logger->WriteLogFile('CCA SIS Bias ' . $datastatus_count);

	 $sis_cold_config=$oComponent->getConfigData($testTypes_array['CCA SIS Bias'],$datastatus_count,$frontend_sn,$facility);
	  while($sis_cold_config_array=mysql_fetch_array($sis_cold_config))
	  {
		//SIS Bias configuration data
		$sis_cold_TestItem=$dom->createElement('TestItem','');
		$testdata->appendChild($sis_cold_TestItem);
		$sis_cold_TestItem->setAttribute("id",$sis_cold_config_array['keyId']);
		$sis_cold_TestItem->setAttribute("type",$testTypes_array['CCA SIS Bias']);
		$sis_cold_TestItem->setAttribute("typeDesc",$sis_cold_config_array['Notes']);
		$sis_cold_TestItem->setAttribute("dataStatus",$sis_cold_config_array['fkDataStatus']);

		$sis_cold_band=$dom->createElement('Band',$sis_cold_config_array['Band']);
		$sis_cold_TestItem->appendChild($sis_cold_band);

		$sis_cold_component=$dom->createElement('Component',$sis_cold_config_array['fkFE_Components']);
		$sis_cold_TestItem->appendChild($sis_cold_component);

		$sis_cold_frontend=$dom->createElement('FrontEnd',$frontend_sn);
		$sis_cold_TestItem->appendChild($sis_cold_frontend);

		$sis_cold_ts=$dom->createElement('TS',$sis_cold_config_array['TS']);
		$sis_cold_TestItem->appendChild($sis_cold_ts);

		//SIS Bias Test data
		$sis_content=$dom->createElement('Content','');
		$sis_cold_TestItem->appendChild($sis_content);

		$sis_freqlo_previous="";

		$sis_rs=$oComponent->getCCASISBias($testTypes_array['CCA SIS Bias'],$sis_cold_config_array['fkFE_Config'],$datastatus_count,$sis_cold_config_array['fkFE_Components'],$facility);

		while($sis_array=mysql_fetch_array($sis_rs))
		{
			$sis_freqlo_current=$sis_array['FreqLO'];

			if($sis_freqlo_current != $sis_freqlo_previous) //to group by freqLO
			{
				$sis_freqlo_previous=$sis_freqlo_current;

				$sisbias_main=$dom->createElement('CCASISBias','');
				$sis_content->appendChild($sisbias_main);

				//for LOFreq
				$sis_freq=$dom->createElement('LOFreq',$sis_freqlo_current);
				$sisbias_main->appendChild($sis_freq);

			}
			//sis tag is SIS+POL+SB
			$sis_val="SIS" . $sis_array['Pol'] . $sis_array['SB'];
			// for sis
			$cca_sis=$dom->createElement($sis_val,'');
			$sisbias_main->appendChild($cca_sis);
			$vj_read=$dom->createElement('VJ_read',$sis_array['VjRead']);
			$cca_sis->appendChild($vj_read);
			//for IJ_read
			$ij_read=$dom->createElement('IJ_read',$sis_array['IjRead']);
			$cca_sis->appendChild($ij_read);
			$vmag_read=$dom->createElement('VMag_read',$sis_array['VmagRead']);
			$cca_sis->appendChild($vmag_read);
			//for IMag_read
			$imag_read=$dom->createElement('IMag_read',$sis_array['ImagRead']);
			$cca_sis->appendChild($imag_read);

		}
	}
	//CCA Temperatures

		$logger->WriteLogFile('CCA Temperature ' . $datastatus_count);

	$cca_temp_config=$oComponent->getConfigData($testTypes_array['CCA Temperature'],$datastatus_count,$frontend_sn,$facility);
		while($cca_temp_config_array=mysql_fetch_array($cca_temp_config))
		{
		//CCA Temperature configuration data

			$cca_temp_TestItem=$dom->createElement('TestItem','');
			$testdata->appendChild($cca_temp_TestItem);
			$cca_temp_TestItem->setAttribute("id",$cca_temp_config_array['keyId']);
			$cca_temp_TestItem->setAttribute("type",$testTypes_array['CCA Temperature']);
			$cca_temp_TestItem->setAttribute("typeDesc",$cca_temp_config_array['Notes']);
			$cca_temp_TestItem->setAttribute("dataStatus",$cca_temp_config_array['fkDataStatus']);

			$cca_temp_band=$dom->createElement('Band',$cca_temp_config_array['Band']);
			$cca_temp_TestItem->appendChild($cca_temp_band);

			$cca_temp_component=$dom->createElement('Component',$cca_temp_config_array['fkFE_Components']);
			$cca_temp_TestItem->appendChild($cca_temp_component);

			$cca_temp_frontend=$dom->createElement('FrontEnd',$frontend_sn);
			$cca_temp_TestItem->appendChild($cca_temp_frontend);

			$cca_temp_ts=$dom->createElement('TS',$cca_temp_config_array['TS']);
			$cca_temp_TestItem->appendChild($cca_temp_ts);

			//CCA Temperature test data

			$cca_temp_content=$dom->createElement('Content','');
			$cca_temp_TestItem->appendChild($cca_temp_content);

			$cca_temp_rs=$oComponent->getCCATemps($testTypes_array['CCA Temperature'],$cca_temp_config_array['fkFE_Config'],$datastatus_count,$cca_temp_config_array['fkFE_Components'],$facility);

			while($cca_temp_array=mysql_fetch_array($cca_temp_rs))
			{
				$cca_temp_main=$dom->createElement('CCATempSensor','');
				$cca_temp_content->appendChild($cca_temp_main);

				$cca_temp4k=$dom->createElement('Temp4KStage',$cca_temp_array['4k']);
				$cca_temp_main->appendChild($cca_temp4k);

				$cca_temp110k=$dom->createElement('Temp110KStage',$cca_temp_array['110k']);
				$cca_temp_main->appendChild($cca_temp110k);

				$cca_temp_pol0mixer=$dom->createElement('TempPol0Mixer',$cca_temp_array['Pol0_mixer']);
				$cca_temp_main->appendChild($cca_temp_pol0mixer);

				$cca_tempSpare=$dom->createElement('TempSpare',$cca_temp_array['Spare']);
				$cca_temp_main->appendChild($cca_tempSpare);

				$cca_temp15k=$dom->createElement('Temp15KStage',$cca_temp_array['15k']);
				$cca_temp_main->appendChild($cca_temp15k);

				$cca_temp_pol1mixer=$dom->createElement('TempPol1Mixer',$cca_temp_array['Pol1_mixer']);
				$cca_temp_main->appendChild($cca_temp_pol1mixer);
			}
		}
	}
	// Cryostat temps
		$logger->WriteLogFile('Cryostat Temps');

		$cryostat_temp_config=$oComponent->getConfigData($testTypes_array['Cryostat Temps'],1,$frontend_sn,$facility);
		$cryostat_temp_config_array=mysql_fetch_array($cryostat_temp_config);

		//cryostat Temperature configuration data
		$cryostat_temp_TestItem=$dom->createElement('TestItem','');
		$testdata->appendChild($cryostat_temp_TestItem);
		$cryostat_temp_TestItem->setAttribute("id",$cryostat_temp_config_array['keyId']);
		$cryostat_temp_TestItem->setAttribute("type",$testTypes_array['Cryostat Temps']);
		$cryostat_temp_TestItem->setAttribute("typeDesc",$cryostat_temp_config_array['Notes']);
		$cryostat_temp_TestItem->setAttribute("dataStatus",$cryostat_temp_config_array['fkDataStatus']);

		if($cryostat_temp_config_array['Band']==0)
		{
			$cryo_band="";
		}

		$cryostat_temp_band=$dom->createElement('Band',$cryo_band);
		$cryostat_temp_TestItem->appendChild($cryostat_temp_band);

		$cryostat_temp_component=$dom->createElement('Component',$cryostat_temp_config_array['fkFE_Components']);
		$cryostat_temp_TestItem->appendChild($cryostat_temp_component);

		$cryostat_temp_frontend=$dom->createElement('FrontEnd',$frontend_sn);
		$cryostat_temp_TestItem->appendChild($cryostat_temp_frontend);

		$cryostat_temp_ts=$dom->createElement('TS',$cryostat_temp_config_array['TS']);
		$cryostat_temp_TestItem->appendChild($cryostat_temp_ts);

		//cryostat temps testdata
		$cryostat_temp_content=$dom->createElement('Content','');
		$cryostat_temp_TestItem->appendChild($cryostat_temp_content);

		$cryostat_temp_rs=$oComponent->getCryostatTemps($testTypes_array['Cryostat Temps'],$cryostat_temp_config_array['fkFE_Config'],1,$cryostat_temp_config_array['fkFE_Components'],$facility);

		while($cryostst_temp_array=mysql_fetch_array($cryostat_temp_rs))
		{
			$cryostat_temp_4Ks=$dom->createElement('Temp4KStage',$cryostst_temp_array['4k_CryoCooler']);
			$cryostat_temp_content->appendChild($cryostat_temp_4Ks);

			$cryostat_Temp4KPlateLink1=$dom->createElement('Temp4KPlateLink1',$cryostst_temp_array['4k_PlateLink1']);
			$cryostat_temp_content->appendChild($cryostat_Temp4KPlateLink1);

			$cryostat_Temp4KPlateLink2=$dom->createElement('Temp4KPlateLink2',$cryostst_temp_array['4k_PlateLink2']);
			$cryostat_temp_content->appendChild($cryostat_Temp4KPlateLink2);

			$cryostat_Temp4KFarSide1=$dom->createElement('Temp4KFarSide1',$cryostst_temp_array['4k_PlateFarSide1']);
			$cryostat_temp_content->appendChild($cryostat_Temp4KFarSide1);

			$cryostat_Temp4KFarSide2=$dom->createElement('Temp4KFarSide2',$cryostst_temp_array['4k_PlateFarSide2']);
			$cryostat_temp_content->appendChild($cryostat_Temp4KFarSide2);

			$cryostat_Temp15KStage=$dom->createElement('Temp15KStage',$cryostst_temp_array['15k_CryoCooler']);
			$cryostat_temp_content->appendChild($cryostat_Temp15KStage);

			$cryostat_Temp15KPlateLink=$dom->createElement('Temp15KPlateLink',$cryostst_temp_array['15k_PlateLink']);
			$cryostat_temp_content->appendChild($cryostat_Temp15KPlateLink);

			$cryostat_Temp15KFarSide=$dom->createElement('Temp15KFarSide',$cryostst_temp_array['15k_PlateFarSide']);
			$cryostat_temp_content->appendChild($cryostat_Temp15KFarSide);

			$cryostat_Temp15KShield=$dom->createElement('Temp15KShield',$cryostst_temp_array['15k_Shield']);
			$cryostat_temp_content->appendChild($cryostat_Temp15KShield);

			$cryostat_Temp110KStage=$dom->createElement('Temp110KStage',$cryostst_temp_array['110k_CryoCooler']);
			$cryostat_temp_content->appendChild($cryostat_Temp110KStage);

			$cryostat_Temp110KPlateLink=$dom->createElement('Temp110KPlateLink',$cryostst_temp_array['110k_PlateLink']);
			$cryostat_temp_content->appendChild($cryostat_Temp110KPlateLink);

			$cryostat_Temp110KFarSide=$dom->createElement('Temp110KFarSide',$cryostst_temp_array['110k_PlateFarSide']);
			$cryostat_temp_content->appendChild($cryostat_Temp110KFarSide);

			$cryostat_Temp110KShield=$dom->createElement('Temp110KShield',$cryostst_temp_array['110k_Shield']);
			$cryostat_temp_content->appendChild($cryostat_Temp110KShield);

		}
		//LPR and Photomixer health

		$logger->WriteLogFile('LPR');

		//LPR configuration data
		$lpr_config=$oComponent->getConfigData($testTypes_array['LPR and Photomixer Health'],2,$frontend_sn,$facility);
		$lpr_config_array=mysql_fetch_array($lpr_config);

		//configuration data
		$lpr_TestItem=$dom->createElement('TestItem','');
		$testdata->appendChild($lpr_TestItem);
		$lpr_TestItem->setAttribute("id",$lpr_config_array['keyId']);
		$lpr_TestItem->setAttribute("type",$testTypes_array['LPR and Photomixer Health']);
		$lpr_TestItem->setAttribute("typeDesc",'LPR and Photomixer Warm Health');
		$lpr_TestItem->setAttribute("dataStatus",$lpr_config_array['fkDataStatus']);

		if($lpr_config_array['Band']==0)
		{
			$l_band="";
		}

		$lpr_band=$dom->createElement('Band',$l_band);
		$lpr_TestItem->appendChild($lpr_band);

		$lpr_component=$dom->createElement('Component',$lpr_config_array['fkFE_Components']);
		$lpr_TestItem->appendChild($lpr_component);

		$lpr_frontend=$dom->createElement('FrontEnd',$frontend_sn);
		$lpr_TestItem->appendChild($lpr_frontend);

		$lpr_ts=$dom->createElement('TS',$lpr_config_array['TS']);
		$lpr_TestItem->appendChild($lpr_ts);

		//lpr photomixer health test data

		$lpr_phm_content=$dom->createElement('Content','');
		$lpr_TestItem->appendChild($lpr_phm_content);

		$lpr_phm_rs=$oComponent->getLPRWarmHealth($testTypes_array['LPR and Photomixer Health'],$lpr_config_array['fkFE_Config'],2,$lpr_config_array['fkFE_Components'],$facility);

		while($lpr_phm_array=mysql_fetch_array($lpr_phm_rs))
		{
			$lpr_laserPump=$dom->createElement('LaserPumpTemp',$lpr_phm_array['LaserPumpTemp']);
			$lpr_phm_content->appendChild($lpr_laserPump);

			$lpr_laserDrive=$dom->createElement('LaserDrive',$lpr_phm_array['LaserDrive']);
			$lpr_phm_content->appendChild($lpr_laserDrive);

			$lpr_laserpd=$dom->createElement('LaserPhotodetector',$lpr_phm_array['LaserPhotodetector']);
			$lpr_phm_content->appendChild($lpr_laserpd);

			$lpr_pdma=$dom->createElement('Photodetector_mA',$lpr_phm_array['Photodetector_mA']);
			$lpr_phm_content->appendChild($lpr_pdma);

			$lpr_pdmw=$dom->createElement('Photodetector_mW',$lpr_phm_array['Photodetector_mW']);
			$lpr_phm_content->appendChild($lpr_pdmw);

			$lpr_modi=$dom->createElement('ModInput',$lpr_phm_array['ModInput']);
			$lpr_phm_content->appendChild($lpr_modi);

			$lpr_modi=$dom->createElement('TempSensor0',$lpr_phm_array['TempSensor0']);
			$lpr_phm_content->appendChild($lpr_modi);

			$lpr_modi=$dom->createElement('TempSensor1',$lpr_phm_array['TempSensor1']);
			$lpr_phm_content->appendChild($lpr_modi);
		}

		$logger->WriteLogFile('...and Photomixer Health');

		$pm_wh_rs=$oComponent->getPhotomixerHealth(9,$lpr_config_array['fkFE_Config'],2,$facility);

		while($pm_wh_array=mysql_fetch_array($pm_wh_rs))
		{
			$pm_band=$dom->createElement('Photomixer','');
			$lpr_phm_content->appendChild($pm_band);
			$pm_band->setAttribute('Band',$pm_wh_array['Band']);

			$pm_vp=$dom->createElement('Voltage',$pm_wh_array['Vpmx']);
			$pm_band->appendChild($pm_vp);

			$pm_ip=$dom->createElement('Current',$pm_wh_array['Ipmx']);
			$pm_band->appendChild($pm_ip);

		}

		//Floog distributor health
		$logger->WriteLogFile('FLOOG Distributor Health');

		//config data
		$floog_config_rs=$oComponent->getConfigData($testTypes_array['FLOOG Distributor Health'],2,$frontend_sn,$facility);
		$floog_config_array=mysql_fetch_array($floog_config_rs);

		$floog_TestItem=$dom->createElement('TestItem','');
		$testdata->appendChild($floog_TestItem);
		$floog_TestItem->setAttribute("id",$floog_config_array['keyId']);
		$floog_TestItem->setAttribute("type",$testTypes_array['FLOOG Distributor Health']);
		$floog_TestItem->setAttribute("typeDesc",$floog_config_array['Notes']);
		$floog_TestItem->setAttribute("dataStatus",$floog_config_array['fkDataStatus']);

		if($floog_config_array['Band']==0)
		{
			$fdh_band="";
		}

		$floog_band=$dom->createElement('Band',$fdh_band);
		$floog_TestItem->appendChild($floog_band);

		$floog_component=$dom->createElement('Component',$floog_config_array['fkFE_Components']);
		$floog_TestItem->appendChild($floog_component);

		$floog_frontend=$dom->createElement('FrontEnd',$frontend_sn);
		$floog_TestItem->appendChild($floog_frontend);

		$floog_ts=$dom->createElement('TS',$floog_config_array['TS']);
		$floog_TestItem->appendChild($floog_ts);

		//floog distributor test data
		$floog_content=$dom->createElement('Content','');
		$floog_TestItem->appendChild($floog_content);

		$floog_rs=$oComponent->getFloogHealth($testTypes_array['FLOOG Distributor Health'],$floog_config_array['fkFE_Config'],2,$floog_config_array['fkFE_Components'],$facility);

		while($floog_array=mysql_fetch_array($floog_rs))
		{
			$reftotal_pwr=$dom->createElement('RefTotalPower',$floog_array['RefTotalPower']);
			$floog_content->appendChild($reftotal_pwr);
			$reftotal_pwr->setAttribute("Band",$floog_array['Band']);
		}

		//IF switch temps
		$logger->WriteLogFile('IF Switch Temps');

		$ifswitch_config_rs=$oComponent->getConfigData($testTypes_array['IF Switch Temps'],2,$frontend_sn,$facility);
		$ifswitch_config_array=mysql_fetch_array($ifswitch_config_rs);

		$ifswitch_TestItem=$dom->createElement('TestItem','');
		$testdata->appendChild($ifswitch_TestItem);
		$ifswitch_TestItem->setAttribute("id",$ifswitch_config_array['keyId']);
		$ifswitch_TestItem->setAttribute("type",$testTypes_array['IF Switch Temps']);
		$ifswitch_TestItem->setAttribute("typeDesc",$ifswitch_config_array['Notes']);
		$ifswitch_TestItem->setAttribute("dataStatus",$ifswitch_config_array['fkDataStatus']);


		if($ifswitch_config_array['Band']==0)
		{
			$ifs_band="";
		}

		$ifswitch_band=$dom->createElement('Band',$ifs_band);
		$ifswitch_TestItem->appendChild($ifswitch_band);

		$ifswitch_component=$dom->createElement('Component',$ifswitch_config_array['fkFE_Components']);
		$ifswitch_TestItem->appendChild($ifswitch_component);

		$ifswitch_frontend=$dom->createElement('FrontEnd',$frontend_sn);
		$ifswitch_TestItem->appendChild($ifswitch_frontend);

		$ifswitch_ts=$dom->createElement('TS',$ifswitch_config_array['TS']);
		$ifswitch_TestItem->appendChild($ifswitch_ts);

		//IF switch test data
		$ifswitch_content=$dom->createElement('Content','');
		$ifswitch_TestItem->appendChild($ifswitch_content);

		$ifswitch_rs=$oComponent->getIFSwitchTemps($testTypes_array['IF Switch Temps'],$ifswitch_config_array['fkFE_Config'],2,$ifswitch_config_array['fkFE_Components'],$facility);

		while($ifswitch_array=mysql_fetch_array($ifswitch_rs))
		{
			$temp01=$dom->createElement('TempPol0SB1',$ifswitch_array['pol0sb1']);
			$ifswitch_content->appendChild($temp01);

			$temp02=$dom->createElement('TempPol0SB2',$ifswitch_array['pol0sb2']);
			$ifswitch_content->appendChild($temp02);

			$temp11=$dom->createElement('TempPol1SB1',$ifswitch_array['pol1sb1']);
			$ifswitch_content->appendChild($temp11);

			$temp12=$dom->createElement('TempPol1SB2',$ifswitch_array['pol1sb2']);
			$ifswitch_content->appendChild($temp12);
		}

		//WCA PA Bias

		//wca_pa_bias configuration data
		$logger->WriteLogFile('WCA PA Bias');

		$wca_pa_config_rs=$oComponent->getConfigData($testTypes_array['WCA PA Bias'],2,$frontend_sn,$facility);

		while($wca_pa_array=mysql_fetch_array($wca_pa_config_rs))
		{
			$wca_pa_TestItem=$dom->createElement('TestItem','');
			$testdata->appendChild($wca_pa_TestItem);
			$wca_pa_TestItem->setAttribute("id",$wca_pa_array['keyId']);
			$wca_pa_TestItem->setAttribute("type",$testTypes_array['WCA PA Bias']);
			$wca_pa_TestItem->setAttribute("typeDesc",'WCA PA Bias');
			$wca_pa_TestItem->setAttribute("dataStatus",'2');


			$wca_pa_main_band=$dom->createElement('Band',$wca_pa_array['Band']);
			$wca_pa_TestItem->appendChild($wca_pa_main_band);

			$wca_pa_component=$dom->createElement('Component',$wca_pa_array['fkFE_Components']);
			$wca_pa_TestItem->appendChild($wca_pa_component);

			$wca_pa_ts=$dom->createElement('TS',$wca_pa_array['TS']);
			$wca_pa_TestItem->appendChild($wca_pa_ts);

			$wca_pa_content=$dom->createElement('Content','');
			$wca_pa_TestItem->appendChild($wca_pa_content);

			$wca_pa_main=$dom->CreateElement('WCAPABias','');
			$wca_pa_content->appendChild($wca_pa_main);

			$wca_pa_rs=$oComponent->getWCApaBias($testTypes_array['WCA PA Bias'],$wca_pa_array['fkFE_Config'],2,$wca_pa_array['fkFE_Components'],$facility);

			while($wca_pa_array=mysql_fetch_array($wca_pa_rs))
			{
				//$wca_pa_band=$dom->createElement('Band',$wca_pa_array['Band']);
				//$wca_pa_main->appendChild($wca_pa_band);

				$wca_pa_freqlo=$dom->createElement('LOFreq',$wca_pa_array['FreqLO']);
				$wca_pa_main->appendChild($wca_pa_freqlo);

				$wca_pa_vdp0=$dom->createElement('VDP0',$wca_pa_array['VDp0']);
				$wca_pa_main->appendChild($wca_pa_vdp0);

				$wca_pa_vdp1=$dom->createElement('VDP1',$wca_pa_array['VDp1']);
				$wca_pa_main->appendChild($wca_pa_vdp1);

				$wca_pa_idp0=$dom->createElement('IDP0',$wca_pa_array['IDp0']);
				$wca_pa_main->appendChild($wca_pa_idp0);

				$wca_pa_idp1=$dom->createElement('IDP1',$wca_pa_array['IDp1']);
				$wca_pa_main->appendChild($wca_pa_idp1);

				$wca_pa_vgp0=$dom->createElement('VGP0',$wca_pa_array['VGp0']);
				$wca_pa_main->appendChild($wca_pa_vgp0);

				$wca_pa_vgp1=$dom->createElement('VGP1',$wca_pa_array['VGp1']);
				$wca_pa_main->appendChild($wca_pa_vgp1);

				$wca_pa_3vs=$dom->createElement('Supply3V',$wca_pa_array['3Vsupply']);
				$wca_pa_main->appendChild($wca_pa_3vs);

				$wca_pa_5vs=$dom->createElement('Supply5V',$wca_pa_array['5Vsupply']);
				$wca_pa_main->appendChild($wca_pa_5vs);

			}
		}
		//WCA AMC Bias

		$logger->WriteLogFile('WCA AMC Bias');

		//wca amc configuration data

	$wca_amc_config_rs=$oComponent->getConfigData($testTypes_array['WCA AMC Bias'],2,$frontend_sn,$facility);
	while($wca_amc_array=mysql_fetch_array($wca_amc_config_rs))
	{
		$wca_amc_TestItem=$dom->createElement('TestItem','');
		$testdata->appendChild($wca_amc_TestItem);
		$wca_amc_TestItem->setAttribute("id",$wca_amc_array['keyId']);
		$wca_amc_TestItem->setAttribute("type",$testTypes_array['WCA AMC Bias']);
		$wca_amc_TestItem->setAttribute("typeDesc",'WCA AMC Bias');
		$wca_amc_TestItem->setAttribute("dataStatus",'2');

		$wca_amc_main_band=$dom->createElement('Band',$wca_amc_array['Band']);
		$wca_amc_TestItem->appendChild($wca_amc_main_band);

		$wca_amc_component=$dom->createElement('Component',$wca_amc_array['fkFE_Components']);
		$wca_amc_TestItem->appendChild($wca_amc_component);

		$wca_amc_ts=$dom->createElement('TS',$wca_amc_array['TS']);
		$wca_amc_TestItem->appendChild($wca_amc_ts);

		//wca amc test data
		$wca_amc_content=$dom->createElement('Content','');
		$wca_amc_TestItem->appendChild($wca_amc_content);

		$wca_amc_main=$dom->CreateElement('WCAAMCBias','');
		$wca_amc_content->appendChild($wca_amc_main);

		$wca_amc_rs=$oComponent->getWCAamcBias($testTypes_array['WCA AMC Bias'],$wca_amc_array['fkFE_Config'],2,$wca_amc_array['fkFE_Components'],$facility);

		while($wca_amc_array=mysql_fetch_array($wca_amc_rs))
		{
			//$wca_amc_band=$dom->createElement('Band',$wca_amc_array['Band']);
			//$wca_amc_main->appendChild($wca_amc_band);

			$wca_amc_freqlo=$dom->createElement('LOFreq',$wca_amc_array['FreqLO']);
			$wca_amc_main->appendChild($wca_amc_freqlo);

			$wca_amc_vda=$dom->createElement('VDA_read',$wca_amc_array['VDA']);
			$wca_amc_main->appendChild($wca_amc_vda);

			$wca_amc_vdb=$dom->createElement('VDB_read',$wca_amc_array['VDB']);
			$wca_amc_main->appendChild($wca_amc_vdb);

			$wca_amc_vde=$dom->createElement('VDE_read',$wca_amc_array['VDE']);
			$wca_amc_main->appendChild($wca_amc_vde);

			$wca_amc_ida=$dom->createElement('IDA_read',$wca_amc_array['IDA']);
			$wca_amc_main->appendChild($wca_amc_ida);

			$wca_amc_idb=$dom->createElement('IDB_read',$wca_amc_array['IDB']);
			$wca_amc_main->appendChild($wca_amc_idb);

			$wca_amc_ide=$dom->createElement('IDE_read',$wca_amc_array['IDE']);
			$wca_amc_main->appendChild($wca_amc_ide);

			$wca_amc_vga=$dom->createElement('VGA_read',$wca_amc_array['VGA']);
			$wca_amc_main->appendChild($wca_amc_vga);

			$wca_amc_vgb=$dom->createElement('VGB_read',$wca_amc_array['VGB']);
			$wca_amc_main->appendChild($wca_amc_vgb);

			$wca_amc_vge=$dom->createElement('VGE_read',$wca_amc_array['VGE']);
			$wca_amc_main->appendChild($wca_amc_vge);

			$wca_amc_multD=$dom->createElement('MultD_read',$wca_amc_array['MultD']);
			$wca_amc_main->appendChild($wca_amc_multD);

			$wca_amc_multDI=$dom->createElement('MultDCurrent_read',$wca_amc_array['MultD_Current']);
			$wca_amc_main->appendChild($wca_amc_multDI);

			$wca_amc_AMC5VSupply=$dom->createElement('AMC5VSupply',$wca_amc_array['5Vsupply']);
			$wca_amc_main->appendChild($wca_amc_AMC5VSupply);
		}
	}
	//WCA misc data
		$logger->WriteLogFile('WCA Misc Bias');

		$wca_misc_config_rs=$oComponent->getConfigData($testTypes_array['WCA Misc Bias'],2,$frontend_sn,$facility);
		while($wca_misc_array=mysql_fetch_array($wca_misc_config_rs))
		{
			$wca_misc_TestItem=$dom->createElement('TestItem','');
			$testdata->appendChild($wca_misc_TestItem);
			$wca_misc_TestItem->setAttribute("id",$wca_misc_array['keyId']);
			$wca_misc_TestItem->setAttribute("type",$testTypes_array['WCA Misc Bias']);
			$wca_misc_TestItem->setAttribute("typeDesc",'WCA Misc Bias');
			$wca_misc_TestItem->setAttribute("dataStatus",'2');

			$wca_misc_band=$dom->createElement('Band',$wca_misc_array['Band']);
			$wca_misc_TestItem->appendChild($wca_misc_band);

			$wca_misc_component=$dom->createElement('Component',$wca_misc_array['fkFE_Components']);
			$wca_misc_TestItem->appendChild($wca_misc_component);

			$wca_misc_ts=$dom->createElement('TS',$wca_misc_array['TS']);
			$wca_misc_TestItem->appendChild($wca_misc_ts);

			//wca misc test data
			$wca_misc_content=$dom->createElement('Content','');
			$wca_misc_TestItem->appendChild($wca_misc_content);

			$wca_misc_main=$dom->CreateElement('WCAMiscBias','');
			$wca_misc_content->appendChild($wca_misc_main);

			$wca_misc_rs=$oComponent->getWCAmiscData($testTypes_array['WCA Misc Bias'],$wca_misc_array['fkFE_Config'],2,$wca_misc_array['fkFE_Components'],$facility);

			while($wca_misc_array=mysql_fetch_array($wca_misc_rs))
			{
				//$wca_misc_band=$dom->createElement('Band',$wca_misc_array['Band']);
				//$wca_misc_main->appendChild($wca_misc_band);

				$wca_misc_freqlo=$dom->createElement('LOFreq',$wca_misc_array['FreqLO']);
				$wca_misc_main->appendChild($wca_misc_freqlo);

				$wca_misc_pllTemp=$dom->createElement('TempPLL',$wca_misc_array['PLLtemp']);
				$wca_misc_main->appendChild($wca_misc_pllTemp);

				$wca_misc_yto=$dom->createElement('YTOHeaterCurrent',$wca_misc_array['YTO_heatercurrent']);
				$wca_misc_main->appendChild($wca_misc_yto);
			}
		}
		//cpds monitor
		//cpds configurstion data

		$logger->WriteLogFile('CPDS Monitor');

		$cpds_config_rs=$oComponent->getConfigData($testTypes_array['CPDS Monitor'],2,$frontend_sn,$facility);
		$cpds_config_array=mysql_fetch_array($cpds_config_rs);

		$cpds_TestItem=$dom->createElement('TestItem','');
		$testdata->appendChild($cpds_TestItem);
		$cpds_TestItem->setAttribute("id",$cpds_config_array['keyId']);
		$cpds_TestItem->setAttribute("type",$testTypes_array['CPDS Monitor']);
		$cpds_TestItem->setAttribute("typeDesc",$cpds_config_array['Notes']);
		$cpds_TestItem->setAttribute("dataStatus",$cpds_config_array['fkDataStatus']);

		if($cpds_config_array['Band']==0)
		{
			$cpds_band="";
		}

		$cpds_main_band=$dom->createElement('Band',$cpds_band);
		$cpds_TestItem->appendChild($cpds_main_band);

		$cpds_component=$dom->createElement('Component',$cpds_config_array['fkFE_Components']);
		$cpds_TestItem->appendChild($cpds_component);

		$cpds_frontend=$dom->createElement('FrontEnd',$frontend_sn);
		$cpds_TestItem->appendChild($cpds_frontend);

		$cpds_ts=$dom->createElement('TS',$cpds_config_array['TS']);
		$cpds_TestItem->appendChild($cpds_ts);

		//cpds test data
		$cpds_content=$dom->createElement('Content','');
		$cpds_TestItem->appendChild($cpds_content);

		$cpds_rs=$oComponent->getCPDSdata($testTypes_array['CPDS Monitor'],$cpds_config_array['fkFE_Config'],2,$cpds_config_array['fkFE_Components'],$facility);

		while($cpds_array=mysql_fetch_array($cpds_rs))
		{
			$cpds_main=$dom->CreateElement('CPDSMonitorData','');
			$cpds_content->appendChild($cpds_main);
			$cpds_main->setAttribute('Band',$cpds_array['Band']);

			$cpds_lofreq=$dom->createElement('LOFreq',$cpds_array['FreqLO']);
			$cpds_main->appendChild($cpds_lofreq);

	  		$cpds_p6v=$dom->createElement('P6V',$cpds_array['P6V_V']);
	  		$cpds_main->appendChild($cpds_p6v);

	  		$cpds_n6v=$dom->createElement('N6V',$cpds_array['N6V_V']);
	  		$cpds_main->appendChild($cpds_n6v);

	  		$cpds_p15v=$dom->createElement('P15V',$cpds_array['P15V_V']);
	  		$cpds_main->appendChild($cpds_p15v);

	  		$cpds_n15v=$dom->createElement('N15V',$cpds_array['N15V_V']);
	  		$cpds_main->appendChild($cpds_n15v);

	  		$cpds_p24v=$dom->createElement('P24V',$cpds_array['P24V_V']);
	  		$cpds_main->appendChild($cpds_p24v);

	  		$cpds_p8v=$dom->createElement('P8V',$cpds_array['P8V_V']);
	  		$cpds_main->appendChild($cpds_p8v);

	  		$cpds_p6i=$dom->createElement('P6I',$cpds_array['P6V_I']);
	  		$cpds_main->appendChild($cpds_p6i);

	  		$cpds_n6i=$dom->createElement('N6I',$cpds_array['N6V_I']);
	  		$cpds_main->appendChild($cpds_n6i);

	  		$cpds_p15i=$dom->createElement('P15I',$cpds_array['P15V_I']);
	  		$cpds_main->appendChild($cpds_p15i);

	  		$cpds_n15i=$dom->createElement('N15I',$cpds_array['N15V_I']);
	  		$cpds_main->appendChild($cpds_n15i);

	  		$cpds_p24i=$dom->createElement('P24I',$cpds_array['P24V_I']);
	  		$cpds_main->appendChild($cpds_p24i);

	  		$cpds_p8i=$dom->createElement('P8I',$cpds_array['P8V_I']);
	  		$cpds_main->appendChild($cpds_p8i);
		}
/*
		//cryostat rate of rise
		$ror_config_rs=$oComponent->getConfigData($testTypes_array['Cryostat Rate of Rise'],2,$frontend_sn,$facility);
		$ror_config_array=mysql_fetch_array($ror_config_rs);

		$ror_TestItem=$dom->createElement('TestItem','');
		$testdata->appendChild($ror_TestItem);
		$ror_TestItem->setAttribute("id",$ror_config_array['keyId']);
		$ror_TestItem->setAttribute("type",$testTypes_array['Cryostat Rate of Rise']);
		$ror_TestItem->setAttribute("typeDesc",$ror_config_array['Notes']);
		$ror_TestItem->setAttribute("dataStatus",$ror_config_array['fkDataStatus']);

		$ror_main_band=$dom->createElement('Band',"");
		$ror_TestItem->appendChild($ror_main_band);

		$ror_component=$dom->createElement('Component',$ror_band);
		$ror_TestItem->appendChild($ror_component);

		$ror_frontend=$dom->createElement('FrontEnd',$frontend_sn);
		$ror_TestItem->appendChild($ror_frontend);

		$ror_ts=$dom->createElement('TS',$ror_config_array['TS']);
		$ror_TestItem->appendChild($cpds_ts);

		$ror_content=$dom->createElement('Content','');
		$ror_TestItem->appendChild($ror_content);

		$ror_rs=$oComponent->getRateofRise($testTypes_array['Cryostat Rate of Rise'],$ror_config_array['fkFE_Config'],2,$facility);

		while($ror_array=mysql_fetch_array($ror_rs))
		{
			$ror_main=$dom->CreateElement('RateOfRise',$ror_array['RateOfRise']);
			$ror_content->appendChild($ror_main);
		}
*/

		$logger->WriteLogFile('Save XML...');

		include(site_get_config_main());

		$dom->save("$main_write_directory/PASData/PASData.xml");

		$logger->WriteLogFile('done.');

}
?>
