<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.dboperations.php');
require_once($site_classes . '/class.mixerparams.php');
require_once($site_classes . '/class.frontend.php');
require_once($site_classes . '/class.cca_preampparams.php');
require_once($site_classes . '/class.cca_tempsensorconfig.php');
require_once($site_classes . '/class.sln.php');
require_once($site_classes . '/zip/pclzip.lib.php');
require_once($site_dbConnect);

class CCA extends FEComponent {
    private $ZipDirectory;
    private $UnzippedFiles;
    private $file_COLDCARTS;
    private $file_MIXERPARAMS;
    private $file_PREAMPPARAMS;
    private $file_TEMPSENSORS;
    private $file_AMPLITUDESTABILITY;
    private $file_PHASE_DRIFT;
    private $file_GAIN_COMPRESSION;
    private $file_IFSPECTRUM;
    private $file_IVCURVE;
    private $file_INBANDPOWER;
    private $file_POLACCURACY;
    private $file_POWERVARIATION;
    private $file_SIDEBANDRATIO;
    private $file_TOTALPOWER;
    private $file_NOISETEMPERATURE;

    var $fkMixer01;
    var $fkMixer02;
    var $fkMixer11;
    var $fkMixer12;
    var $fkPreamp01;
    var $fkPreamp02;
    var $fkPreamp11;
    var $fkPreamp12;
    var $fkTempSensor0;
    var $fkTempSensor1;
    var $fkTempSensor2;
    var $fkTempSensor3;
    var $fkTempSensor4;
    var $fkTempSensor5;

    var $TempSensors; //Array of temp sensor objects (generic table)
    var $MixerParams; //Array of mixer param objects
    var $PreampParams; //Array of preamp param objects (generic table)
    var $preamp01;
    var $preamp02;
    var $preamp11;
    var $preamp12;

    var $swversion;
    var $fkDataStatus;
    var $fc; //facility key
    var $sln; //Status location and notes object (generic table)

    var $CCA_urls; //Generic table object of CCA_urls, fkFE_Component = $this->keyId

    var $writedirectory;
    var $url_directory;

    var $UploadTF;   //0 = do not upload anything, 1= DO upload everything
    var $UpdateMixers; //Set as checkbox in ShowComponents.php. If checked, this value is 1,
    //and Mixers are to be updated.
    var $UpdatePreamps; //Set as checkbox in ShowComponents.php. If checked, this value is 1,
    //and Preamps are to be updated.

    var $SubmittedFileExtension; //Extension of submitted file for update (csv, ini or zip)

    var $SubmittedFileName; //Uploaded file (csv, zip, ini), base name
    var $SubmittedFileTmp;  //Uploaded file (csv, zip, ini), actual path

    var $ErrorArray; //Array of errors

    function __construct($in_keyId, $in_fc, $INIT_Options = self::INIT_ALL) {
        $this->fc = $in_fc;
        parent::__construct(NULL, $in_keyId, NULL, $this->fc);
        $this->fkDataStatus = '7';
        $this->swversion = "1.0.20";

        /*
         * 1.0.20 GetXmlFileContent() don't include VGs in output.  Also remove VGs from most functions.
         * 1.0.19 Fix GetXmlFileContent() to comply with /alma/ste/config/TMCDB_DATA/ for Cycle 8
         * 1.0.18 Add GetXmlFileContent() with support for band 1 and 2.
         * 1.0.17 Fix query in Upload_CCAs_file()
         * 1.0.16 UploadPreampParams supports different format for band 1.
         * 1.0.15 Fix display/edit operating params for band 1.  Code formatting.
         * 1.0.14 Move export_to_ini_cca code into class; delete dead code; make things private!
         * 1.0.13 Fixed UploadPreampParams to filter for temps < 20K, ignore MIXERPARAMS if not provided.
         * 1.0.12 Fixed Upload_PolAccuracy to comply with CCA data spec
         * 1.0.11 Added INIT_Options to Initialize_CCA()
         * 1.0.10 Added XML data file uplaod and fixed related bugs
         * 1.0.9  fixed bugs in CCA data upload
         * 1.0.8  fixes to allow operation with E_NOTICE enabled
         */

        require(site_get_config_main());
        $this->writedirectory = $cca_write_directory;
        $this->url_directory = $cca_url_directory;
        $this->ZipDirectory = $this->writedirectory . "zip";
        $this->ErrorArray = array();

        $this->keyFacility = $in_fc;

        if ($INIT_Options & self::INIT_SLN) {
            //Status location and notes
            $rsln = SLN::getMaxIdFromComponent($this->keyId);
            $slnid = ADAPT_mysqli_result($rsln, 0, 0);
            $this->sln = new SLN($slnid);
        }

        if ($INIT_Options & self::INIT_TEMPSENSORS) {
            //Initialize temp sensors.
            $r = CCA_TempSensorConfig::getIdFromComponent($this->keyId);
            while ($row = mysqli_fetch_array($r)) {
                $tempsensor_id = $row[0];
                $ts_location = $row[1];
                $this->TempSensors[$ts_location] = new CCA_TempSensorConfig($tempsensor_id, $in_fc);
            }
        }

        if ($INIT_Options & self::INIT_MIXERPARAMS) {
            //Initialize mixer params
            $q = "SELECT DISTINCT(FreqLO), keyId FROM CCA_MixerParams
            WHERE fkComponent = $this->keyId
            GROUP BY FreqLO ORDER BY FreqLO ASC;";
            $r = mysqli_query($this->dbConnection, $q);
            $mpcount = 0;
            while ($row = mysqli_fetch_array($r)) {
                $this->MixerParams[$mpcount] = new MixerParams($this->keyId, $row[0], $this->keyFacility);
                $mpcount += 1;
            }
        }

        if ($INIT_Options & self::INIT_PREAMPPARAMS) {
            //Initialize preamp params.
            $r = CCA_PreampParams::getIdFromComponent($this->keyId);
            $pcount = 0;
            while ($row = mysqli_fetch_array($r)) {
                $this->PreampParams[$pcount] = new CCA_PreampParams($row[1], $in_fc);
                //echo $this->PreampParams[$pcount]->FreqLO . "<br>";
                $pcount += 1;
            }
        }

        if ($INIT_Options & self::INIT_TESTDATA) {
            //Test data URLs
            $this->CCA_urls = new GenericTable("CCA_urls", $this->keyId, "fkFE_Component");
            $this->CCA_urls->keyIdName = "fkFE_Component";
        }
    }

    private function AddError($ErrorString) {
        $this->ErrorArray[] = $ErrorString;
    }

    const INIT_SLN          = 0x0001;
    const INIT_TEMPSENSORS  = 0x0002;
    const INIT_MIXERPARAMS  = 0x0004;
    const INIT_PREAMPPARAMS = 0x0008;
    const INIT_TESTDATA     = 0x0010;

    const INIT_NONE         = 0x0000;
    const INIT_ALL          = 0x001F;

    private function hasSB2() {
        switch ($this->Band) {
            case 1:
            case 9:
            case 10:
                return false;
            default:
                break;
        }
        return true;
    }

    private function NewRecord_CCA($in_fc) {
        $tempObj = parent::NewRecord('FE_Components', 'keyId', $in_fc, 'keyFacility');
        $tempObj->fkFE_ComponentType = 20;
        $tempObj->Update();


        $q_url = "INSERT INTO CCA_urls(fkFE_Component) VALUES($this->keyId);";
        $r_url = mysqli_query($this->dbConnection, $q_url);
        $this->CCA_urls = new GenericTable("CCA_urls", $this->keyId, "fkFE_Component");
        $this->CCA_urls->keyIdName = "fkFE_Component";

        $q_status = "INSERT INTO FE_StatusLocationAndNotes
        (fkFEComponents, fkLocationNames,fkStatusType)
        VALUES($this->keyId,'40','7');";
        $r_status = mysqli_query($this->dbConnection, $q_status);
    }

    // return a string formatted as the FrontEndControlDLL.ini section for this CCA:
    public function getFrontEndControlDLL_ini() {
        $band = $this->Band;
        $sn   = ltrim($this->SN, '0');
        $esn  = $this->ESN1;

        $output = "";

        $output .= "[~ColdCart$band-$sn]\r\n";
        $description = "Band $band SN$sn";
        $output .= "Description=$description\r\n";
        $output .= "Band=$band\r\n";
        $output .= "SN=$sn\r\n";
        $output .= "ESN=$esn\r\n";

        $mstring = "";

        switch ($band) {
            case 1:
            case 2:
            case 3:
                $output .= "MagnetParams=0\r\n";
                break;
            case 4:
                $output .= "MagnetParams=0\r\n";
                break;
            case 6:
                $output .= "MagnetParams=1\r\n";
                $mstring = "MagnetParam01=" . number_format($this->MixerParams[0]->lo, 3) . ",";
                $mstring .= number_format($this->MixerParams[0]->imag01, 2) . ",";
                $mstring .= "0.00,";
                $mstring .= number_format($this->MixerParams[0]->imag11, 2) . ",";
                $mstring .= "0.00\r\n";
                break;
            default:
                //Get number of magnet params
                $im01 = 'x';
                $im02 = 'x';
                $im11 = 'x';
                $im12 = 'x';
                $numMags = 0;
                for ($ic = 0; $ic < count($this->MixerParams); $ic++) {
                    if (($this->MixerParams[$ic]->imag01 != $im01)
                        || ($this->MixerParams[$ic]->imag02 != $im02)
                        || ($this->MixerParams[$ic]->imag11 != $im11)
                        || ($this->MixerParams[$ic]->imag12 != $im12)
                    ) {
                        $im01 = $this->MixerParams[$ic]->imag01;
                        $im02 = $this->MixerParams[$ic]->imag02;
                        $im11 = $this->MixerParams[$ic]->imag11;
                        $im12 = $this->MixerParams[$ic]->imag12;
                        $numMags += 2;
                    }
                }

                $output .= "MagnetParams=$numMags\r\n";
                $im01 = 'x';
                $im02 = 'x';
                $im11 = 'x';
                $im12 = 'x';
                $magcount = 1;
                $mstring = '';
                for ($ic = 0; $ic < count($this->MixerParams); $ic++) {
                    $imLO = $this->MixerParams[$ic]->lo;
                    $magcountStr = "MagnetParam0" . $magcount;
                    if ($magcount > 9) {
                        $magcountStr = "MagnetParam" . $magcount;
                    }

                    //Check to see if this set of Imag values is unique
                    if (($this->MixerParams[$ic]->imag01 != $im01)
                        || ($this->MixerParams[$ic]->imag02 != $im02)
                        || ($this->MixerParams[$ic]->imag11 != $im11)
                        || ($this->MixerParams[$ic]->imag12 != $im12)
                    ) {
                        if ($ic > 0) {
                            $mstring .= "$magcountStr=" . number_format($this->MixerParams[$ic - 1]->lo, 3) . ",";
                            $mstring .= number_format($im01, 2) . ",";
                            $mstring .= number_format($im02, 2) . ",";
                            $mstring .= number_format($im11, 2) . ",";
                            $mstring .= number_format($im12, 2) . "\r\n";
                            $magcount += 1;
                            $magcountStr = "MagnetParam0" . $magcount;
                            if ($magcount > 9) {
                                $magcountStr = "MagnetParam" . $magcount;
                            }
                        }
                        $im01 = $this->MixerParams[$ic]->imag01;
                        $im02 = $this->MixerParams[$ic]->imag02;
                        $im11 = $this->MixerParams[$ic]->imag11;
                        $im12 = $this->MixerParams[$ic]->imag12;

                        $mstring .= "$magcountStr=" . number_format($this->MixerParams[$ic]->lo, 3) . ",";
                        $mstring .= number_format($this->MixerParams[$ic]->imag01, 2) . ",";
                        $mstring .= number_format($this->MixerParams[$ic]->imag02, 2) . ",";
                        $mstring .= number_format($this->MixerParams[$ic]->imag11, 2) . ",";
                        $mstring .= number_format($this->MixerParams[$ic]->imag12, 2) . "\r\n";
                        $magcount += 1;
                    }
                    //Put the last string in
                    if ($ic >= count($this->MixerParams) - 1) {
                        $mstring .= "$magcountStr=" . number_format($this->MixerParams[$ic]->lo, 3) . ",";
                        $mstring .= number_format($this->MixerParams[$ic]->imag01, 2) . ",";
                        $mstring .= number_format($this->MixerParams[$ic]->imag02, 2) . ",";
                        $mstring .= number_format($this->MixerParams[$ic]->imag11, 2) . ",";
                        $mstring .= number_format($this->MixerParams[$ic]->imag12, 2) . "\r\n";
                    }
                }
        }

        $output .= $mstring;

        if (!$this->MixerParams) {
            $output .= "MixerParams=0\r\n";
        } else {
            $output .= "MixerParams=" . (count($this->MixerParams)) . "\r\n";
    
            for ($i = 0; $i  < (count($this->MixerParams)); $i++) {
                if ($i < 9) {
                    $mstring = "MixerParam0" . ($i + 1) . "=";
                }
                if ($i >= 9) {
                    $mstring = "MixerParam" . ($i + 1) . "=";
                }
                $mstring .= number_format($this->MixerParams[$i]->lo, 3) . ",";
                $mstring .= number_format($this->MixerParams[$i]->vj01, 3) . ",";
                $mstring .= number_format($this->MixerParams[$i]->vj02, 3) . ",";
                $mstring .= number_format($this->MixerParams[$i]->vj11, 3) . ",";
                $mstring .= number_format($this->MixerParams[$i]->vj12, 3) . ",";
                $mstring .= number_format($this->MixerParams[$i]->ij01, 2) . ",";
                $mstring .= number_format($this->MixerParams[$i]->ij02, 2) . ",";
                $mstring .= number_format($this->MixerParams[$i]->ij11, 2) . ",";
                $mstring .= number_format($this->MixerParams[$i]->ij12, 2) . "\r\n";
                $output .= $mstring;
            }
        }

        $numpa = 4;
        if ($this->Band == 9) {
            $numpa = 2;
        }
        $ij_precision = 2;
        if ($this->Band == 3) {
            $ij_precision = 3;
        }

        $output .= "PreampParams=" . (count($this->PreampParams)) . "\r\n";

        for ($i = 0; $i  < (count($this->PreampParams)); $i++) {
            if ($i < 9) {
                $mstring = "PreampParam0" . ($i + 1) . "=";
            }
            if ($i >= 9) {
                $mstring = "PreampParam" . ($i + 1) . "=";
            }
            $mstring .= number_format($this->PreampParams[$i]->FreqLO, 3) . ",";
            $mstring .= $this->PreampParams[$i]->Pol . ",";
            $mstring .= $this->PreampParams[$i]->SB . ",";
            $mstring .= number_format($this->PreampParams[$i]->VD1, 2) . ",";
            $mstring .= number_format($this->PreampParams[$i]->VD2, 2) . ",";
            $mstring .= number_format($this->PreampParams[$i]->VD3, 2) . ",";
            $mstring .= number_format($this->PreampParams[$i]->ID1, $ij_precision) . ",";
            $mstring .= number_format($this->PreampParams[$i]->ID2, $ij_precision) . ",";
            $mstring .= number_format($this->PreampParams[$i]->ID3, $ij_precision) . ",";
            $mstring .= number_format($this->PreampParams[$i]->VG1, 2) . ",";
            $mstring .= number_format($this->PreampParams[$i]->VG2, 2) . ",";
            $mstring .= number_format($this->PreampParams[$i]->VG3, 2) . "\r\n";
            $output .= $mstring;
        }
        $output .= "\r\n";
        return $output;
    }

    public function GetXmlFileContent() {
        $band = $this->Band;
        $sn   = ltrim($this->SN, '0');
        if (strlen($sn) == 1)
            $sn = "0" . $sn;
        $esn  = $this->ESN1;
        $esnDec = hexdec($esn);
        $longSn = "CCA$band-$sn";

        $xw = new XMLWriter();
        $xw->openMemory();
        $xw->setIndent(true);
        $xw->setIndentString('    ');
        $xw->startDocument('1.0', 'ISO-8859-1');
        $xw->startElement("ConfigData");
        //         $xw->writeAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
        //         $xw->writeAttribute("xsi:noNamespaceSchemaLocation", "membuffer.xsd");

        $xw->startElement("ASSEMBLY");
        $xw->writeAttribute("value", "ColdCart$band");
        $xw->endElement();

        $xw->startElement("CCAConfig");
        $xw->writeAttribute("value", $this->keyId);
        // make the MySQL timestamp into an ISO 8601 standard timestamp:
        $xw->writeAttribute("timestamp", strtr($this->TS, ' ', 'T'));
        $xw->endElement();

        $xw->startElement("ESN");
        $xw->writeAttribute("value", $esn);
        $xw->endElement();

        $xw->startElement("SN");
        $xw->writeAttribute("value", $longSn);
        $xw->endElement();

        $xw->startElement("TempSensorOffsets");
        for ($i = 0; $i < 6; $i++) {
            if (isset($this->TempSensors[$i]) && $this->TempSensors[$i]->keyId != "") {
                $xw->writeAttribute("Te$i", $this->TempSensors[$i]->OffsetK);
            } else {
                $xw->writeAttribute("Te$i", "0");
            }
        }
        $xw->endElement();

        if ($band > 2 && $this->MixerParams) {
            for ($i = 0; $i  < (count($this->MixerParams)); $i++) {
                $xw->startElement("MixerParams");
                $xw->writeAttribute("FreqLO", number_format($this->MixerParams[$i]->lo, 3) . "E9");
                $xw->writeAttribute("VJ01", number_format($this->MixerParams[$i]->vj01, 3));
                $xw->writeAttribute("VJ02", number_format($this->MixerParams[$i]->vj02, 3));
                $xw->writeAttribute("VJ11", number_format($this->MixerParams[$i]->vj11, 3));
                $xw->writeAttribute("VJ12", number_format($this->MixerParams[$i]->vj12, 3));
                $xw->writeAttribute("IJ01", number_format($this->MixerParams[$i]->ij01, 2));
                $xw->writeAttribute("IJ02", number_format($this->MixerParams[$i]->ij02, 2));
                $xw->writeAttribute("IJ11", number_format($this->MixerParams[$i]->ij11, 2));
                $xw->writeAttribute("IJ12", number_format($this->MixerParams[$i]->ij12, 2));
                $xw->endElement();
            }
        }

        switch ($band) {
            case 1:
            case 2:
            case 3:
            case 4:
                break;
            case 6:
            case 9:
            case 10:
                $xw->startElement("MagnetParams");
                $xw->writeAttribute("FreqLO", number_format($this->MixerParams[0]->lo, 3) . "E9");
                $xw->writeAttribute("IMag01", number_format($this->MixerParams[0]->imag01, 2));
                $xw->writeAttribute("IMag02", "0.00");
                $xw->writeAttribute("IMag11", number_format($this->MixerParams[0]->imag11, 2));
                $xw->writeAttribute("IMag12", "0.00");
                $xw->endElement();
                break;
            default:
                $xw->startElement("MagnetParams");
                $xw->writeAttribute("FreqLO", number_format($this->MixerParams[0]->lo, 3) . "E9");
                $xw->writeAttribute("IMag01", number_format($this->MixerParams[0]->imag01, 2));
                $xw->writeAttribute("IMag02", number_format($this->MixerParams[0]->imag02, 2));
                $xw->writeAttribute("IMag11", number_format($this->MixerParams[0]->imag11, 2));
                $xw->writeAttribute("IMag12", number_format($this->MixerParams[0]->imag12, 2));
                $xw->endElement();
                break;
        }

        if ($band > 2) {
            for ($i = 0; $i  < (count($this->PreampParams)); $i++) {
                $pol = $this->PreampParams[$i]->Pol;
                $sb = $this->PreampParams[$i]->SB;
                $xw->startElement("PreampParamsPol$pol" . "Sb$sb");
                $xw->writeAttribute("FreqLO", number_format($this->PreampParams[$i]->FreqLO, 3) . "E9");
                for ($st = 1; $st <= 3; $st++)
                    $xw->writeAttribute("VD$st", number_format($this->PreampParams[$i]->{"VD$st"}, 2));
                for ($st = 1; $st <= 3; $st++)
                    $xw->writeAttribute("ID$st", number_format($this->PreampParams[$i]->{"ID$st"}, 3));
                $xw->endElement();
            }
        } else {
            // bands 1 and 2 store the stage 4, 5, 6 params in the SB2 records
            $numStage = ($band == 1) ? 5 : 6;

            $output = array();
            // element format:
            // {    "name" => "PreampParamsPol0Sb1",
            //      "FreqLO" => "31.000E9",
            //      "VD1" => "0.70",
            //      ... }

            for ($i = 0; $i  < (count($this->PreampParams)); $i++) {
                $pol = $this->PreampParams[$i]->Pol;
                $sb = $this->PreampParams[$i]->SB;
                $freqLO = number_format($this->PreampParams[$i]->FreqLO, 3) . "E9";
                $name = "PreampParamsPol$pol" . "Sb1";

                for ($pos = 0; $pos < count($output); $pos++) {
                    if ($output[$pos]["name"] == $name && $output[$pos]["FreqLO"] == $freqLO)
                        break;
                }
                if ($pos < count($output))
                    $paramsArray = $output[$pos];
                else
                    $paramsArray = array("name" => $name, "FreqLO" => $freqLO);

                for ($st = 1; $st <= 3; $st++) {
                    $index = $st + (($sb == 1) ? 0 : 3);
                    if ($index <= $numStage) {
                        $paramsArray["VD$index"] = number_format($this->PreampParams[$i]->{"VD$st"}, 2);
                        $paramsArray["ID$index"] = number_format($this->PreampParams[$i]->{"ID$st"}, 3);
                        $paramsArray["VG$index"] = number_format($this->PreampParams[$i]->{"VG$st"}, 2);
                    }
                }
                if ($pos < count($output))
                    $output[$pos] = $paramsArray;
                else
                    array_push($output, $paramsArray);
            }
            foreach ($output as $paramsArray) {
                foreach ($paramsArray as $key => $val)
                    if ($key == "name")
                        $xw->startElement($val);
                    else
                        $xw->writeAttribute($key, $val);
                $xw->endElement();
            }
        }

        if ($band > 1) {
            $xw->startElement("PolarizationOrientation");
            $xw->writeAttribute("PolXAngle", "0");
            $xw->writeAttribute("PolYAngle", "0");
            $xw->endElement();
        }

        $xw->endElement(); // ConfigData
        $xw->endDocument();
        $ret = $xw->outputMemory();
        return $ret;
    }

    public function Display_TempSensors() {
        $locs[0] = "Spare";
        $locs[1] = "110K Stage";
        $locs[2] = "15K Stage";
        $locs[3] = "4K Stage";
        $locs[4] = "Pol 0 Mixer";
        $locs[5] = "Pol 1 Mixer";

        $ts = "";
        if (isset($this->TempSensors[1]) && $this->TempSensors[1]->keyId != '') {
            $ts = $this->TempSensors[1]->TS . ",";
        }

        echo "<div style= 'width: 350px'><table id = 'table1'>";
        echo "<tr class='alt'>
                <th colspan = '4'>TEMPERATURE SENSORS <br><i>($ts CCA " . $this->Band . "-" . $this->SN . ")</i></th>
              </tr>";
        echo "<tr>
                <th>Location</th>
                <th>Model</th>
                <th>SN</th>
                <th>OffsetK</th>

              </tr>";

        for ($i = 1; $i <= 5; $i++) {
            if (isset($this->TempSensors[$i]) && $this->TempSensors[$i]->keyId != "") {
                if ($i % 2 == 0) {
                    echo "<tr>";
                } else {
                    echo "<tr class = 'alt'>";
                }
                echo "
                <td>$locs[$i]</td>
                <td>" . $this->TempSensors[$i]->Model . "</td>
                <td>" . $this->TempSensors[$i]->SN . "</td>
                <td>" . $this->TempSensors[$i]->OffsetK . "</td>

              </tr>";
            }
        }
        echo "</table></div><br>";
    }

    public function Display_MixerParams() {
        $maxSb = ($this->hasSB2()) ? 2 : 1;
        $found = 0;

        for ($pol = 0; $pol <= 1; $pol++) {
            for ($sb = 1; $sb <= $maxSb; $sb++) {
                $q = "SELECT Temperature,FreqLO,VJ,IJ,IMAG,TS
                      FROM CCA_MixerParams
                      WHERE fkComponent = $this->keyId
                      AND Pol = $pol
                      AND SB = $sb
                      ORDER BY FreqLO ASC";

                $r = mysqli_query($this->dbConnection, $q);
                $ts = ADAPT_mysqli_result($r, 0, 5);
                $r = mysqli_query($this->dbConnection, $q);
                if (mysqli_num_rows($r) > 0) {
                    $found++;
                    echo "<div style= 'width: 500px;'><table id = 'table1' border = '1'>";
                    echo "
                        <tr class='alt'><th colspan = '5'>
                            Mixer Pol $pol SB $sb <i>($ts, CCA " . $this->Band . "-" . $this->SN . ")</i>

                            </th>
                        </tr>
                        <tr>
                            <th>LO (GHz)</th>
                            <th>VJ</th>
                            <th>IJ</th>
                            <th>IMAG</th>
                        </tr>";
                    $count = 0;
                    while ($row = mysqli_fetch_array($r)) {
                        if ($count % 2 == 0) {
                            echo "<tr>";
                        } else {
                            echo "<tr class = 'alt'>";
                        }
                        echo "
                            <td>$row[1]</td>
                            <td>$row[2]</td>
                            <td>$row[3]</td>
                            <td>$row[4]</td>
                        </tr>";
                        $count += 1;
                    }
                    echo "</table></div><br>";
                }
            }
        }
        if (!$found) {
            echo "<div style= 'width: 500px;'><table id = 'table1' border = '1'>";
            echo "<tr class='alt'><th>No Mixer Params</th></tr>";
            echo "</table></div><br>";
        }
    }

    public function Display_PreampParams() {
        // get the band number:
        $band = $this->Band;

        // displaying SB2 params?
        $maxSb = ($this->hasSB2()) ? 2 : 1;
        // Override for band 1 because we map VD4, VD5, ID4, ID5 to a fake SB2:
        if ($band == 1)
            $maxSb = 2;

        // loop on PreampParams records:
        $numParams = count($this->PreampParams);
        $found = 0;

        // flags to help decide whent to show table header:
        $lastPol = -1;
        $lastSb = -1;
        $tableOpen = false;

        // we are assuming they are in ascending order by Pol, SB, FreqLO, as loaded above in Initialize_CCA.
        for ($paramIndex = 0; $paramIndex < $numParams; $paramIndex++) {

            // check whether data exists at this index:
            $paramRow = $this->PreampParams[$paramIndex];
            if (isset($paramRow)) {

                // Get Pol and SB
                $pol = $paramRow->Pol;
                $sb = $paramRow->SB;

                // don't display SB2 tables for bands 1, 9, 10:
                if ($sb <= $maxSb) {

                    // show table header if we're on a new pol or sb:
                    if ($pol != $lastPol || $sb != $lastSb) {
                        // but first close the previous table if needed:
                        if ($tableOpen)
                            echo "</table></div><br>";
                        $tableOpen = true;
                        echo "<div style= 'width: 500px'><table id = 'table1' border = '1'>";
                        echo "<tr class='alt'><th colspan = '11'>Preamp Pol $pol SB $sb";

                        if ($paramRow->keyId != "") {
                            echo "<i> (";
                            echo $paramRow->TS;
                            echo ", CCA " . $band . "-" . $this->SN . ")</i>";
                        }
                        echo "</th></tr>";

                        echo "<th>LO (GHz)</th>
                        <th>VD1</th>
                        <th>VD2</th>
                        <th>VD3</th>
                        <th>ID1</th>
                        <th>ID2</th>
                        <th>ID3</th>
                        </tr>";
                    }
                    $lastPol = $pol;
                    $lastSb = $sb;

                    // show table row:
                    echo ($paramIndex % 2 == 0) ? "<tr>" : "<tr class = 'alt'>";
                    echo "
                    <td>" . $paramRow->FreqLO . "</td>
                    <td>" . $paramRow->VD1 . "</td>
                    <td>" . $paramRow->VD2 . "</td>
                    <td>" . $paramRow->VD3 . "</td>
                    <td>" . $paramRow->ID1 . "</td>
                    <td>" . $paramRow->ID2 . "</td>
                    <td>" . $paramRow->ID3 . "</td>
                    </tr>";
                    $found++;
                }
            }
        }
        if (!$found) {
            $tableOpen = true;
            echo "<div style= 'width: 500px'><table id = 'table1' border = '1'>";
            echo "<tr class='alt'><th>No Preamp Params</th></tr>";
        }
        if ($tableOpen)
            echo "</table></div><br>";
    }

    public function Display_uploadform_Zip() {
        require(site_get_config_main());

        echo '



        <tr class="alt" ><td colspan="2">
        <!-- The data encoding type, enctype, MUST be specified as below -->
    <form enctype="multipart/form-data" action="' . $_SERVER['PHP_SELF'] . '" method="POST">
        <!-- MAX_FILE_SIZE must precede the file input field -->
        <!-- <input type="hidden" name="MAX_FILE_SIZE" value="32000000000" /> -->
        <!-- Name of input element determines name in $_FILES array -->
        <input name="zip" type="file" size = "100" />


        ';
        echo '&nbsp &nbsp &nbsp<input type="submit" class = "submit" name= "submitted_zip" value="Submit" />';
        echo "<input type='hidden' name='fc' value='$fc' />";
        echo "<input type='hidden' name='keyFacility' value='$fc' /></form></td></tr>";
    }

    public function Display_uploadform_AnyFile($keyId, $fc) {
        echo "<div style='width:850px'>";
        echo "<table id = 'table8'>";
        echo "<tr class='alt'><th colspan = '2'>Upload a file.</th></tr>";
        echo '
            <tr class="alt" ><td colspan="2">
            <!-- The data encoding type, enctype, MUST be specified as below -->
        <form enctype="multipart/form-data" action="' . $_SERVER['PHP_SELF'] . '" method="POST">
            <!-- MAX_FILE_SIZE must precede the file input field -->
            <!-- <input type="hidden" name="MAX_FILE_SIZE" value="32000000000" /> -->
            <!-- Name of input element determines name in $_FILES array -->
            <input name="ccafile" type="file" size = "100" />


            ';
        echo '&nbsp &nbsp &nbsp<input type="submit" class = "submit" name= "submitted_ccafile" value="Submit" />';
        echo "<input type='hidden' name='fc' value='$fc' />";
        echo "<input type='hidden' name='conf' value='$keyId' />";
        echo "<input type='hidden' name='keyFacility' value='$fc' /></form></td></tr>";


        echo "<tr class = 'alt2'><td colspan = '2'>
        <br><u>Types of files that may be uploaded</u>
        <br><br>
        1. ZIP- Zipped package of configuration and test data.<br><br>
        2. CSV or TXT- Single file of test data.<br><br>
        3. FrontEndControlDLL.ini file.
        </td></tr>";
        echo "</table></div>";
    }

    public function Display_uploadform_SingleCSVfile() {
        require(site_get_config_main());

        echo '<tr class="alt"><td colspan="2">
        <!-- The data encoding type, enctype, MUST be specified as below -->
        <form enctype="multipart/form-data" action="' . $_SERVER['PHP_SELF'] . '" method="POST">
            <!-- MAX_FILE_SIZE must precede the file input field -->
            <!-- <input type="hidden" name="MAX_FILE_SIZE" value="32000000000" /> -->
            <!-- Name of input element determines name in $_FILES array -->
            <input name="single_file" type="file" size = "100" />';
        echo '&nbsp &nbsp &nbsp &nbsp<input type="submit" class = "submit" name= "submitted_singlefile" value="Submit" /></td></tr>';
        echo "<input type='hidden' name='fc' value='$fc' />";
        echo "<input type='hidden' name='keyFacility' value='$fc' />";
        echo "

        </form>";
    }

    public function Display_uploadform() {
        require(site_get_config_main());


        echo '
        <p><div style="width:500px;height:80px; align = "left"></p>
        <!-- The data encoding type, enctype, MUST be specified as below -->
        <form enctype="multipart/form-data" action="' . $_SERVER['PHP_SELF'] . '" method="POST">
            <!-- MAX_FILE_SIZE must precede the file input field -->
            <!-- <input type="hidden" name="MAX_FILE_SIZE" value="32000000000" /> -->
            <!-- Name of input element determines name in $_FILES array -->
            <br>
            <font size="+1"><b><u>Zipped file collection (CSV)</u></b></font><br>
                <br></b><input class = "submit" name="zip" type="file" />';
        echo '<input type="submit"  class = "submit" name= "submitted_zip" value="Submit" />';
        echo "<input type='hidden' name='fc' value='$fc' />";
        echo "<input type='hidden' name='keyFacility' value='$fc' />";
        echo "

        </form>
        </div>";


        echo '
        <p><div style="width:500px;height:80px; align = "left"></p>
        <!-- The data encoding type, enctype, MUST be specified as below -->
        <form enctype="multipart/form-data" action="' . $_SERVER['PHP_SELF'] . '" method="POST">
            <!-- MAX_FILE_SIZE must precede the file input field -->
            <!-- <input type="hidden" name="MAX_FILE_SIZE" value="32000000000" /> -->
            <!-- Name of input element determines name in $_FILES array -->
            <br>
            <font size="+1"><b><u>Single CSV data file</u></b></font><br>
                <br></b><input name="single_file" type="file" />';
        echo '<input type="submit" name= "submitted_singlefile" value="Submit" />';

        echo "<input type='hidden' name='fc' value='$fc' />";
        echo "
        </form>
        </div>";
    }

    public function RequestValues_CCA($In_SubmittedFileName = '', $In_SubmittedFileTmp = '') {
        $fc = $this->keyFacility;
        parent::RequestValues();
        $this->keyFacility = $fc;

        if (isset($_REQUEST['deleterecord_forsure'])) {
            $this->DeleteRecord_CCA();
        }

        $this->SubmittedFileName = $In_SubmittedFileName;
        $this->SubmittedFileTmp  = $In_SubmittedFileTmp;

        if (isset($_REQUEST['submitted_ccafile'])) {
            $this->SubmittedFile = $_REQUEST['submitted_ccafile'];
            $this->SubmittedFileName = $_FILES['ccafile']['name'];
            $this->SubmittedFileTmp = $_FILES['ccafile']['tmp_name'];
        }

        $filenamearr = explode(".", $this->SubmittedFileName);
        $this->SubmittedFileExtension = strtolower($filenamearr[count($filenamearr) - 1]);

        if ($this->SubmittedFileExtension == 'zip') {
            $this->UploadExtractZipFile();
        } else if (($this->SubmittedFileExtension == 'csv') || ($this->SubmittedFileExtension == 'txt')) {
            $this->Upload_TestDataFile();
        } else if ($this->SubmittedFileExtension == 'ini') {
            $this->Update_Configuration_From_INI($this->SubmittedFileTmp);
        } else if ($this->SubmittedFileExtension == 'xml') {
            $this->Update_Configuration_From_ALMA_XML($this->SubmittedFileTmp);
        } else {
            $this->AddError("Error: Unable to upload file $this->SubmittedFileName.");
        }
    }

    public function Upload_TestDataFile() {
        if (strpos(strtolower($this->SubmittedFileName), "amplitude_stability") != "") {
            $this->file_AMPLITUDESTABILITY = $this->SubmittedFileTmp;
            $this->Upload_AmplitudeStability();
        }
        if (strpos(strtolower($this->SubmittedFileName), "phase_drift") != "") {
            $this->file_PHASE_DRIFT = $this->SubmittedFileTmp;
            $this->Upload_PhaseDrift();
        }
        if (strpos(strtolower($this->SubmittedFileName), "gain_compression") != "") {
            $this->file_GAIN_COMPRESSION = $this->SubmittedFileTmp;
            $this->Upload_GainCompression();
        }
        if (strpos(strtolower($this->SubmittedFileName), "total_power") != "") {
            $this->file_TOTALPOWER = $this->SubmittedFileTmp;
            $this->Upload_TotalPower();
        }
        if (strpos(strtolower($this->SubmittedFileName), "inband_power") != "") {
            $this->file_INBANDPOWER = $this->SubmittedFileTmp;
            $this->Upload_InBandPower();
        }
        if (strpos(strtolower($this->SubmittedFileName), "iv_curve") != "") {
            $this->file_IVCURVE = $this->SubmittedFileTmp;
            $this->Upload_IVCurve();
        }
        if (strpos(strtolower($this->SubmittedFileName), "sideband_ratio") != "") {
            $this->file_SIDEBANDRATIO = $this->SubmittedFileTmp;
            $this->Upload_SidebandRatio();
        }
        if (strpos(strtolower($this->SubmittedFileName), "image_suppression") != "") {
            $this->file_SIDEBANDRATIO = $this->SubmittedFileTmp;
            $this->Upload_SidebandRatio();
        }
        if (strpos(strtolower($this->SubmittedFileName), "power_var") != "") {
            $this->file_POWERVARIATION = $this->SubmittedFileTmp;
            $this->Upload_PowerVariation();
        }
        if (strpos(strtolower($this->SubmittedFileName), "polarization_accuracy") != "") {
            $this->file_POLACCURACY = $this->SubmittedFileTmp;
            $this->Upload_PolAccuracy();
        }
        if (strpos(strtolower($this->SubmittedFileName), "if_spectrum") != "") {
            $this->file_IFSPECTRUM = $this->SubmittedFileTmp;
            $this->Upload_IFSpectrum();
        }
        if (strpos(strtolower($this->SubmittedFileName), "noise_temperature") != "") {
            $this->file_NOISETEMPERATURE = $this->SubmittedFileTmp;
            $this->Upload_NoiseTemperature();
        }
    }

    public function DeleteRecord_CCA() {
        $this->Delete_ALL_TestData();
        $qDel = "DELETE FROM FE_Components WHERE fkFE_Component = $this->keyId;";
        $rDel = mysqli_query($this->dbConnection, $qDel);
        $qDel = "DELETE FROM CCA_MixerParams WHERE fkComponent = $this->keyId;";
        $rDel = mysqli_query($this->dbConnection, $qDel);
        $qDel = "DELETE FROM CCA_PreampParams WHERE fkComponent = $this->keyId;";
        $rDel = mysqli_query($this->dbConnection, $qDel);
        $qDel = "DELETE FROM CCA_TempSensorConfig WHERE fkComponent = $this->keyId;";
        $rDel = mysqli_query($this->dbConnection, $qDel);
        $qDel = "DELETE FROM CCA_urls WHERE fkFE_Component = $this->keyId;";
        $rDel = mysqli_query($this->dbConnection, $qDel);
        $qDel = "DELETE FROM TestData_header WHERE fkFE_Components = $this->keyId;";
        $rDel = mysqli_query($this->dbConnection, $qDel);
        $qDel = "DELETE FROM FE_StatusLocationAndNotes WHERE fkFEComponents = $this->keyId;";
        $rDel = mysqli_query($this->dbConnection, $qDel);

        parent::Delete_record();
        echo '<p>The record has been deleted.</p>';
        echo '<meta http-equiv="Refresh" content="1;url=cca_main.php">';
    }

    public function Delete_ALL_TestData() {
        $q = "SELECT keyId FROM TestData_header
              WHERE keyFacility = '$this->fc' AND fkFE_Components = '$this->keyId' AND fkDataStatus = 7;";
        $r = mysqli_query($this->dbConnection, $q);

        $keyList = "(";
        $first = true;
        while ($row_testdata = mysqli_fetch_array($r)) {
            if ($first)
                $first = false;
            else
                $keyList .= ",";
            $keyList .= $row_testdata[0];
        }
        $keyList .= ")";

        $qDelete = "DELETE FROM CCA_TEST_AmplitudeStability WHERE fkFacility = '$this->fc' AND fkHeader IN $keyList;";
        $rDelete = mysqli_query($this->dbConnection, $qDelete);
        $qDelete = "DELETE FROM CCA_TEST_GainCompression WHERE fkFacility = '$this->fc' AND fkHeader IN $keyList;";
        $rDelete = mysqli_query($this->dbConnection, $qDelete);
        $qDelete = "DELETE FROM CCA_TEST_IFSpectrum WHERE fkFacility = '$this->fc' AND fkHeader IN $keyList;";
        $rDelete = mysqli_query($this->dbConnection, $qDelete);
        $qDelete = "DELETE FROM CCA_TEST_InBandPower WHERE fkFacility = '$this->fc' AND fkHeader IN $keyList;";
        $rDelete = mysqli_query($this->dbConnection, $qDelete);
        $qDelete = "DELETE FROM CCA_TEST_NoiseTemperature WHERE fkFacility = '$this->fc' AND fkHeader IN $keyList;";
        $rDelete = mysqli_query($this->dbConnection, $qDelete);
        $qDelete = "DELETE FROM CCA_TEST_PhaseDrift WHERE fkFacility = '$this->fc' AND fkHeader IN $keyList;";
        $rDelete = mysqli_query($this->dbConnection, $qDelete);
        $qDelete = "DELETE FROM CCA_TEST_PolAccuracy WHERE fkFacility = '$this->fc' AND fkHeader IN $keyList;";
        $rDelete = mysqli_query($this->dbConnection, $qDelete);
        $qDelete = "DELETE FROM CCA_TEST_PowerVariation WHERE fkFacility = '$this->fc' AND fkHeader IN $keyList;";
        $rDelete = mysqli_query($this->dbConnection, $qDelete);
        $qDelete = "DELETE FROM CCA_TEST_SidebandRatio WHERE fkFacility = '$this->fc' AND fkHeader IN $keyList;";
        $rDelete = mysqli_query($this->dbConnection, $qDelete);
        $qDelete = "DELETE FROM CCA_TEST_TotalPower WHERE fkFacility = '$this->fc' AND fkHeader IN $keyList;";
        $rDelete = mysqli_query($this->dbConnection, $qDelete);
        $qDelete = "DELETE FROM CCA_TEST_IVCurve WHERE fkFacility = '$this->fc' AND fkHeader IN $keyList;";
        $rDelete = mysqli_query($this->dbConnection, $qDelete);
        $qDelete = "DELETE FROM TestData_header WHERE keyFacility = '$this->fc' AND keyId IN $keyList;";
        $rDelete = mysqli_query($this->dbConnection, $qDelete);
    }


    public function UploadExtractZipFile() {
        $this->rmdir_recursive($this->ZipDirectory);
        mkdir($this->ZipDirectory);
        $upload_dir = $this->ZipDirectory; //your upload directory NOTE: CHMODD 0777
        $filename = $this->SubmittedFileName; //the filename

        //move file
        if (!move_uploaded_file($this->SubmittedFileTmp, $upload_dir . '/' . $filename)) {
            $this->AddError("Error : Unable to upload file $this->SubmittedFileName.");
        }

        $zip_dir = basename(strtolower($filename), ".zip"); //get filename without extension fpr directory creation

        //create directory in $upload_dir and chmodd directory
        $createdir = $upload_dir . '/' . $zip_dir;
        if (!@mkdir($upload_dir . '/' . $zip_dir, 0777)) {
            $this->AddError("Error : Unable to create directory $createdir");
        }

        //Update notes for front end to reflect that this zip file was uploaded.
        $updatestring  = 'Uploaded Zip file ' . $filename = $this->SubmittedFileName;
        $updatestring .= " for CCA " . $this->Band . "-" . $this->SN . ".";

        $feconfig = $this->FEConfig;
        $dbopszip = new DBOperations();

        $this->UpdateStatus(7);
        /*
        $dbopszip->UpdateStatusLocationAndNotes_FE($this->FEfc, '', '',$updatestring,$feconfig, $feconfig, ' ','');
        $dbopszip->UpdateStatusLocationAndNotes_Component('', '', '',$updatestring,$this->keyId, ' ','');
        $this->sln->SetValue('Notes',$updatestring);
        $this->sln->Update();
        */

        $archive = new PclZip($upload_dir . '/' . $filename);
        if ($archive->extract(PCLZIP_OPT_PATH, $upload_dir . '/' . $zip_dir) == 0) {
            $this->AddError("Error : Unable to unzip archive");
        }

        //show what was just extracted
        $list = $archive->listContent();

        for ($i = 0; $i < sizeof($list); $i++) {
            if (!$list[$i]['folder'])
                $bytes = " - " . $list[$i]['size'] . " bytes";
            else
                $bytes = "";

            //echo "".$list[$i]['filename']."$bytes<br />";
            $this->UnzippedFiles[$i] = $upload_dir . '/' . $zip_dir . "/" . $list[$i]['filename'];

            if (strpos(strtolower($this->UnzippedFiles[$i]), "mixerparams") != "") {
                $this->file_MIXERPARAMS = $this->UnzippedFiles[$i];
            }
            if (strpos(strtolower($this->UnzippedFiles[$i]), "tempsensors") != "") {
                $this->file_TEMPSENSORS = $this->UnzippedFiles[$i];
            }
            if (strpos(strtolower($this->UnzippedFiles[$i]), "coldcarts") != "") {
                $this->file_COLDCARTS = $this->UnzippedFiles[$i];
            }
            if (strpos(strtolower($this->UnzippedFiles[$i]), "preampparams") != "") {
                $this->file_PREAMPPARAMS = $this->UnzippedFiles[$i];
            }
            if (strpos(strtolower($this->UnzippedFiles[$i]), "amplitude_stability") != "") {
                $this->file_AMPLITUDESTABILITY = $this->UnzippedFiles[$i];
            }
            if (strpos(strtolower($this->UnzippedFiles[$i]), "gain_compression") != "") {
                $this->file_GAIN_COMPRESSION = $this->UnzippedFiles[$i];
            }
            if (strpos(strtolower($this->UnzippedFiles[$i]), "polarization_accuracy") != "") {
                $this->file_POLACCURACY = $this->UnzippedFiles[$i];
            }
            if (strpos(strtolower($this->UnzippedFiles[$i]), "inband_power") != "") {
                $this->file_INBANDPOWER = $this->UnzippedFiles[$i];
            }
            if (strpos(strtolower($this->UnzippedFiles[$i]), "total_power") != "") {
                $this->file_TOTALPOWER = $this->UnzippedFiles[$i];
            }
            if (strpos(strtolower($this->UnzippedFiles[$i]), "sideband_ratio") != "") {
                $this->file_SIDEBANDRATIO = $this->UnzippedFiles[$i];
            }
            if (strpos(strtolower($this->UnzippedFiles[$i]), "image_suppression") != "") {
                $this->file_SIDEBANDRATIO = $this->UnzippedFiles[$i];
            }
            if (strpos(strtolower($this->UnzippedFiles[$i]), "iv_curve") != "") {
                $this->file_IVCURVE = $this->UnzippedFiles[$i];
            }
            if (strpos(strtolower($this->UnzippedFiles[$i]), "power_var") != "") {
                $this->file_POWERVARIATION = $this->UnzippedFiles[$i];
            }
            if (strpos(strtolower($this->UnzippedFiles[$i]), "if_spectrum") != "") {
                $this->file_IFSPECTRUM = $this->UnzippedFiles[$i];
            }
            if (strpos(strtolower($this->UnzippedFiles[$i]), "noise_temperature") != "") {
                $this->file_NOISETEMPERATURE = $this->UnzippedFiles[$i];
            }
            if (strpos(strtolower($this->UnzippedFiles[$i]), "phase_drift") != "") {
                $this->file_PHASE_DRIFT = $this->UnzippedFiles[$i];
            }
        }

        if (file_exists($upload_dir . '/' . $filename))
            unlink($upload_dir . '/' . $filename); //delete uploaded file

        if ($this->file_COLDCARTS != "") {
            $this->Upload_CCAs_file();

            //UploadTF is set to 0 or 1 in Upload_CCAs_file.
            //0= This CCA SN not found in zip file
            //1= This CCA found, go ahead and upload everything.
            if ($this->UploadTF == 1) {
                $this->UploadTempSensors();
                $this->UploadMixerParams();
                $this->UploadPreampParams();

                // Update the SLN for the front end this is installed in, if any:
                if ($feconfig)
                    $dbopszip->UpdateStatusLocationAndNotes_FE($this->FEfc, '', '', $updatestring, $feconfig, $feconfig, ' ', '');
                // Update SLN for this component:
                $dbopszip->UpdateStatusLocationAndNotes_Component($this->fc, '', '', $updatestring, $this->keyId, ' ', '');
                $this->sln->Notes = $updatestring;
                $this->sln->Update();
            }
        }

        if ($this->file_AMPLITUDESTABILITY != "") {
            $this->Upload_AmplitudeStability();
        }
        if ($this->file_GAIN_COMPRESSION != "") {
            $this->Upload_GainCompression();
        }
        if ($this->file_POLACCURACY != "") {
            $this->Upload_PolAccuracy();
        }
        if ($this->file_INBANDPOWER != "") {
            $this->Upload_InBandPower();
        }
        if ($this->file_TOTALPOWER != "") {
            $this->Upload_TotalPower();
        }
        if ($this->file_SIDEBANDRATIO != "") {
            $this->Upload_SidebandRatio();
        }
        if ($this->file_IVCURVE != "") {
            $this->Upload_IVCurve();
        }
        if ($this->file_POWERVARIATION != "") {
            $this->Upload_PowerVariation();
        }
        if ($this->file_IFSPECTRUM != "") {
            $this->Upload_IFSpectrum();
        }
        if ($this->file_NOISETEMPERATURE != "") {
            $this->Upload_NoiseTemperature();
        }
        if ($this->file_PHASE_DRIFT != "") {
            $this->Upload_PhaseDrift();
        }
    }

    public function Upload_CCAs_file() {
        $this->UploadTF = 0;
        $filecontents = file($this->file_COLDCARTS);
        $sn = '';

        // iterate over COLDCARTS record to find the first line we can use...
        $i = 0;
        while (($this->UploadTF == 0) && ($i < sizeof($filecontents))) {
            // trim and separate by commas:
            $line_data = trim($filecontents[$i]);
            $tempArray = explode(",", $line_data);
            // if too short still, try separating by tabs:
            if (count($tempArray) < 2) {
                $tempArray = explode("\t", $line_data);
            }
            // if the first column is numeric, assume data not header row:
            if (is_numeric(substr($tempArray[0], 0, 1))) {
                // get the serial number:
                $SNtemp = explode(".", $tempArray[20]);
                $sn = trim($SNtemp[count($SNtemp) - 1] + 0);
                // get the TS_Removed:
                $tsr = trim($tempArray[19]);

                // same as this CCA and not marked as Removed?
                if ($sn == $this->SN && !$tsr) {
                    // found a record to upload:
                    $this->UploadTF = 1;

                    // save the Status Location Notes records about the upload:
                    $this->Band = $tempArray[0];
                    $this->sln->TS = Date('Y-m-d H:i:s');
                    $this->sln->Notes = $this->sln->Notes . " \r\nTimestamp from COLDCARTS file: " . $tempArray[18];
                    $this->sln->Update();

                    // Really?!
                    $this->SN = $sn;

                    //Check if record already exists...
                    $qc = "SELECT MAX(keyId) FROM FE_Components
                           WHERE TRIM(LEADING 0 FROM SN) = {$this->SN}
                           AND Band = {$this->Band}
                           AND keyFacility = {$this->keyFacility}
                           AND fkFE_ComponentType = 20;";

                    $rc = mysqli_query($this->dbConnection, $qc);
                    $numrows = mysqli_num_rows($rc);

                    if ($numrows > 1) {
                        // one already exists
                        $this->AddError("Warning- More than one CCA exist in the database for this Band ($tempArray[0]) and SN ($sn)");
                        $this->AddError("The files will be uploaded for this CCA configuration.");
                    }
                    if ($numrows < 1) {
                        // add the CCA record:
                        $this->NewRecord_CCA($this->keyFacility);
                        $this->Band = $tempArray[0];
                        $this->SN = $sn;
                        parent::Update();
                    }
                    if ($numrows > 0) {
                        // update the existing CCA:
                        $this->__construct($this->keyId, $this->keyFacility);
                        $this->Delete_ALL_TestData();
                    }

                    $ESN = trim(str_replace('"', '', $tempArray[21]));

                    if ($this->ESN1 == '') {
                        $this->ESN1 = $ESN;
                    }
                    $this->fkMixer01     = $tempArray[2];
                    $this->fkMixer02     = $tempArray[3];
                    $this->fkMixer11     = $tempArray[4];
                    $this->fkMixer12     = $tempArray[5];
                    $this->fkPreamp01    = $tempArray[6];
                    $this->fkPreamp02    = $tempArray[7];
                    $this->fkPreamp11    = $tempArray[8];
                    $this->fkPreamp12    = $tempArray[9];
                    $this->fkTempSensor0 = $tempArray[12];
                    $this->fkTempSensor1 = $tempArray[13];
                    $this->fkTempSensor2 = $tempArray[14];
                    $this->fkTempSensor3 = $tempArray[15];
                    $this->fkTempSensor4 = $tempArray[16];
                    $this->fkTempSensor5 = $tempArray[17];
                }
            }
            $i++;
        }

        if ($this->UploadTF == 0) {
            // no matching or suitable COLDCART row found to upload:
            $thisSN = $this->SN;

            $warning = "Warning- The SN in the uploaded file ($sn)\\n";
            $warning .= "does not match the SN of this CCA ($thisSN).\\n";
            $warning .= "(Or no suitable record was found in COLDCARTS.)\\n";
            $warning .= "Please check the SN in the COLDCARTS csv file.\\n";
            $warning .= "Upload operation terminated.";

            $this->AddError($warning);
        }
    }

    public function UploadTempSensors() {
        $qdelete = "DELETE FROM CCA_TempSensorConfig WHERE fkComponent = $this->keyId;";
        $rdelete = mysqli_query($this->dbConnection, $qdelete);

        if (!isset($this->file_TEMPSENSORS))
            return;

        $filecontents = file($this->file_TEMPSENSORS);
        for ($i = 0; $i < sizeof($filecontents); $i++) {
            $line_data = trim($filecontents[$i]);
            $tempArray   = explode(",", $line_data);
            if (count($tempArray) < 2) {
                $tempArray   = explode("\t", $line_data);
            }
            if (is_numeric(substr($tempArray[0], 0, 1)) == true) {
                $TempSensor = CCA_TempSensorConfig::NewRecord('CCA_TempSensorConfig', 'keyId', $this->keyFacility, 'fkFacility');
                $TempSensor->keyIdName = "keyId";
                $TempSensor->fkComponent = $this->keyId;
                $TempSensor->Location = $tempArray[4];
                $TempSensor->Model = $tempArray[5];
                $TempSensor->SN = $tempArray[6];
                $TempSensor->OffsetK = $tempArray[7];
                $TempSensor->Notes = $tempArray[8];
                $TempSensor->Update();
                unset($TempSensor);
            }
        }
    }

    public function UploadMixerParams() {
        $qdelete = "DELETE FROM CCA_MixerParams WHERE fkComponent = $this->keyId;";
        $rdelete = mysqli_query($this->dbConnection, $qdelete);

        if (!isset($this->file_MIXERPARAMS))
            return;

        $filecontents = file($this->file_MIXERPARAMS);
        for ($i = 0; $i < sizeof($filecontents); $i++) {
            $line_data = trim($filecontents[$i]);
            $tempArray   = explode(",", $line_data);
            if (count($tempArray) < 2) {
                $tempArray   = explode("\t", $line_data);
            }

            if (is_numeric(substr($tempArray[0], 0, 1)) == true) {
                $MixerParam = MixerParams::NewRecord('CCA_MixerParams', 'keyId', $this->keyFacility, 'fkFacility');
                $MixerParam->fkComponent = $this->keyId;

                $fkMixers = $tempArray[2];
                $MixerParam->Temperature = $tempArray[3];
                $MixerParam->FreqLO = $tempArray[4];
                $MixerParam->VJ = $tempArray[6];
                $MixerParam->IJ = $tempArray[7];
                $MixerParam->IMAG = $tempArray[8];

                switch ($fkMixers) {
                    case $this->fkMixer01:
                        $MixerParam->Pol = 0;
                        $MixerParam->SB = 1;
                        $MixerParam->Update();
                        break;
                    case $this->fkMixer02:
                        $MixerParam->Pol = 0;
                        $MixerParam->SB = 2;
                        $MixerParam->Update();
                        break;
                    case $this->fkMixer11:
                        $MixerParam->Pol = 1;
                        $MixerParam->SB = 1;
                        $MixerParam->Update();
                        break;
                    case $this->fkMixer12:
                        $MixerParam->Pol = 1;
                        $MixerParam->SB = 2;
                        $MixerParam->Update();
                        break;
                    default:
                        $MixerParam->Delete_record();
                }

                unset($MixerParam);
            }
        }
    }

    private function UploadPreampParams() {
        $qdelete = "DELETE FROM CCA_PreampParams WHERE fkComponent = $this->keyId
                    AND fkFacility = " . $this->keyFacility . ";";
        $rdelete = mysqli_query($this->dbConnection, $qdelete);

        if (!isset($this->file_PREAMPPARAMS))
            return;

        $band = $this->Band;

        $filecontents = file($this->file_PREAMPPARAMS);

        foreach ($filecontents as $row) {
            $line_data = trim($row);
            $tempArray = explode(",", $line_data);
            if (count($tempArray) < 2) {
                $tempArray = explode("\t", $line_data);
            }
            // Check for header row:
            if (is_numeric(substr($tempArray[0], 0, 1))) {

                // Check for import only cryogenic temps:
                if ($tempArray[3] <= 20) {

                    // Upload row implementation varies by band:
                    if ($band == 1)
                        $this->UploadPreampParamsB1($tempArray);
                    else if ($band >= 2 && $band <= 10)
                        $this->UploadPreampParamsB3to10($tempArray);
                }
            }
        }
    }

    private function UploadPreampParamsB1($tempArray) {
        // private helper method for UploadPreampParams()
        // For band 1 we map VD4, VD5, ID4, ID5, VG4, VG5 onto a fake SB2 record
        //   since those are the bias module circuits which controls them:
        for ($sb = 1; $sb <= 2; $sb++) {
            $PreampParam = CCA_PreampParams::NewRecord('CCA_PreampParams', 'keyId', $this->keyFacility, 'fkFacility');
            $PreampParam->fkComponent = $this->keyId;
            $PreampParam->Temperature = $tempArray[3];
            $PreampParam->FreqLO = $tempArray[4];

            $fkPreamps = $tempArray[2];

            switch ($fkPreamps) {
                case $this->fkPreamp01:
                    $PreampParam->Pol = 0;
                    $PreampParam->SB = $sb;
                    break;
                case $this->fkPreamp11:
                    $PreampParam->Pol = 1;
                    $PreampParam->SB = $sb;
                    break;
            }

            // Column order for band 1:
            // keyBand, keyPreampParams, fkPreamps, Temperature, FreqLO, TS, VD1, VD2, VD3, VD4, VD5, ID1, ID2, ID3, ID4, ID5, VG1, VG2, VG3, VG4, VG5
            if ($sb == 1) {
                $PreampParam->VD1 = $tempArray[6];
                $PreampParam->VD2 = $tempArray[7];
                $PreampParam->VD3 = $tempArray[8];
                $PreampParam->ID1 = $tempArray[11];
                $PreampParam->ID2 = $tempArray[12];
                $PreampParam->ID3 = $tempArray[13];
                $PreampParam->VG1 = $tempArray[16];
                $PreampParam->VG2 = $tempArray[17];
                $PreampParam->VG3 = $tempArray[18];
            } else {
                $PreampParam->VD1 = $tempArray[9];
                $PreampParam->VD2 = $tempArray[10];
                $PreampParam->VD3 = 0;
                $PreampParam->ID1 = $tempArray[14];
                $PreampParam->ID2 = $tempArray[15];
                $PreampParam->ID3 = 0;
                $PreampParam->VG1 = $tempArray[19];
                $PreampParam->VG2 = $tempArray[20];
                $PreampParam->VG3 = 0;
            }
            $PreampParam->Update();
            unset($PreampParam);
        }
    }

    private function UploadPreampParamsB3to10($tempArray) {
        // private helper method for UploadPreampParams()
        $PreampParam = CCA_PreampParams::NewRecord('CCA_PreampParams', 'keyId', $this->keyFacility, 'fkFacility');
        $PreampParam->fkComponent = $this->keyId;
        $PreampParam->Temperature = $tempArray[3];
        $PreampParam->FreqLO = $tempArray[4];

        $fkPreamps = $tempArray[2];

        switch ($fkPreamps) {
            case $this->fkPreamp01:
                $PreampParam->Pol = 0;
                $PreampParam->SB = 1;
                break;
            case $this->fkPreamp02:
                $PreampParam->Pol = 0;
                $PreampParam->SB = 2;
                break;
            case $this->fkPreamp11:
                $PreampParam->Pol = 1;
                $PreampParam->SB = 1;
                break;
            case $this->fkPreamp12:
                $PreampParam->Pol = 1;
                $PreampParam->SB = 2;
                break;
        }
        // Column order for bands 3-10:
        // keyBand, keyPreampParams, fkPreamps, Temperature, FreqLO, TS, VD1, VD2, VD3, ID1, ID2, ID3, VG1, VG2, VG3
        $PreampParam->VD1 = $tempArray[6];
        $PreampParam->VD2 = $tempArray[7];
        $PreampParam->VD3 = $tempArray[8];
        $PreampParam->ID1 = $tempArray[9];
        $PreampParam->ID2 = $tempArray[10];
        $PreampParam->ID3 = $tempArray[11];
        $PreampParam->VG1 = $tempArray[12];
        $PreampParam->VG2 = $tempArray[13];
        $PreampParam->VG3 = $tempArray[14];
        $PreampParam->Update();
        unset($PreampParam);
    }

    public function Upload_AmplitudeStability() {
        $TestData_header = TestData_header::NewRecord("TestData_header", 'keyId', $this->keyFacility, 'keyFacility');
        $TestData_header->fkTestData_Type = 43;
        $TestData_header->fkFE_Components = $this->keyId;
        $TestData_header->fkDataStatus = $this->fkDataStatus;
        $TestData_header->Band = $this->Band;
        $TestData_header->Update();

        //Upload file contents
        $ct = 0;
        $TS = '';

        $filecontents = file($this->file_AMPLITUDESTABILITY);
        for ($i = 0; $i < sizeof($filecontents); $i++) {
            $line_data = trim($filecontents[$i]);
            $tempArray   = explode(",", $line_data);
            if (count($tempArray) < 2) {
                $tempArray   = explode("\t", $line_data);
            }
            if (is_numeric(substr($tempArray[0], 0, 1)) == true) {
                $ds = $tempArray[1];
                $FreqLO = $tempArray[4];
                $Pol = $tempArray[5];
                $SB = $tempArray[6];
                $TS = $tempArray[3];
                if (trim(strtoupper($SB)) == "U") {
                    $SB = 1;
                }
                if (trim(strtoupper($SB)) == "L") {
                    $SB = 2;
                }
                $Time = $tempArray[7];
                $AllanVar = $tempArray[8];

                $qAS = "INSERT INTO CCA_TEST_AmplitudeStability(FreqLO,Pol,SB,Time,AllanVar,fkHeader,keyDataSet)
                VALUES('$FreqLO','$Pol','$SB','$Time','$AllanVar','$TestData_header->keyId','$ds')";
                $rAS = mysqli_query($this->dbConnection, $qAS);

                $ct += 1;
            }
            $TestData_header->TS = $TS;
            //$TestData_header->Update();
        }
        unset($TestData_header);
    }

    public function Upload_PhaseDrift() {
        $TestData_header = TestData_header::NewRecord("TestData_header", 'keyId', $this->keyFacility, 'keyFacility');
        $TestData_header->fkTestData_Type = 33;
        $TestData_header->fkDataStatus = $this->fkDataStatus;
        $TestData_header->fkFE_Components = $this->keyId;
        $TestData_header->Band = $this->Band;
        $TestData_header->Update();

        //Upload file contents
        $TS = '';

        $filecontents = file($this->file_PHASE_DRIFT);
        for ($i = 0; $i < sizeof($filecontents); $i++) {
            $line_data = trim($filecontents[$i]);
            $tempArray   = explode(",", $line_data);

            if (count($tempArray) < 2) {
                $tempArray   = explode("\t", $line_data);
            }
            if (is_numeric(substr($tempArray[0], 0, 1)) == true) {
                if ($this->Band != 7) {
                    $FreqLO      = $tempArray[4];
                    $FreqCarrier = $tempArray[5];
                    $Pol         = $tempArray[6];
                    $SB          = $tempArray[7];
                    $Time        = $tempArray[8];
                    $AllanPhase    = $tempArray[9];
                }
                if ($this->Band == 7) {
                    $FreqLO      = $tempArray[4];
                    $FreqCarrier = 0;
                    $Pol         = $tempArray[5];
                    $SB          = $tempArray[6];
                    $Time        = $tempArray[7];
                    $AllanPhase    = $tempArray[8];
                }
                $ds = $tempArray[1];
                $TS = $tempArray[3];
                if (trim(strtoupper($SB)) == "U") {
                    $SB = 1;
                }
                if (trim(strtoupper($SB)) == "L") {
                    $SB = 2;
                }

                $qAS = "INSERT INTO CCA_TEST_PhaseDrift(FreqLO,FreqCarrier,Pol,SB,Time,AllanPhase,fkHeader,keyDataSet)
                  VALUES('$FreqLO','$FreqCarrier','$Pol','$SB','$Time','$AllanPhase','$TestData_header->keyId','$ds')";
                $rAS = mysqli_query($this->dbConnection, $qAS);
            }
        }
        $TestData_header->TS = $TS;
        unset($TestData_header);
    }

    public function Upload_GainCompression() {
        $TestData_header = TestData_header::NewRecord("TestData_header", 'keyId', $this->keyFacility, 'keyFacility');
        $TestData_header->fkTestData_Type = 34;
        $TestData_header->fkDataStatus = $this->fkDataStatus;
        $TestData_header->fkFE_Components = $this->keyId;
        $TestData_header->Band = $this->Band;
        $TestData_header->Update();

        //Upload file contents
        $TS = '';

        $filecontents = file($this->file_GAIN_COMPRESSION);
        for ($i = 0; $i < sizeof($filecontents); $i++) {
            $line_data = trim($filecontents[$i]);
            $tempArray   = explode(",", $line_data);
            if (count($tempArray) < 2) {
                $tempArray   = explode("\t", $line_data);
            }
            if (is_numeric(substr($tempArray[0], 0, 1)) == true) {
                $ds = $tempArray[1];
                $FreqLO      = $tempArray[4];
                $Pol         = $tempArray[5];
                $SB          = $tempArray[6];
                $TS          = $tempArray[3];
                if (trim(strtoupper($SB)) == "U") {
                    $SB = 1;
                }
                if (trim(strtoupper($SB)) == "L") {
                    $SB = 2;
                }
                $Compression = $tempArray[7];

                $qAS = "INSERT INTO CCA_TEST_GainCompression(FreqLO,Pol,SB,Compression,fkHeader,keyDataSet)
                  VALUES('$FreqLO','$Pol','$SB','$Compression','$TestData_header->keyId','$ds')";
                $rAS = mysqli_query($this->dbConnection, $qAS);
            }
        }
        $TestData_header->TS = $TS;
        unset($TestData_header);
    }

    public function Upload_PolAccuracy() {
        $TestData_header = TestData_header::NewRecord("TestData_header", 'keyId', $this->keyFacility, 'keyFacility');
        $TestData_header->fkTestData_Type = 35;
        $TestData_header->fkDataStatus = $this->fkDataStatus;
        $TestData_header->fkFE_Components = $this->keyId;
        $TestData_header->Band = $this->Band;
        $TestData_header->Update();

        //Upload file contents
        $TS = '';

        $filecontents = file($this->file_POLACCURACY);
        for ($i = 0; $i < sizeof($filecontents); $i++) {
            $line_data = trim($filecontents[$i]);
            $tempArray   = explode(",", $line_data);
            if (count($tempArray) < 2) {
                $tempArray   = explode("\t", $line_data);
            }
            if (is_numeric(substr($tempArray[0], 0, 1)) == true) {
                $FreqLO      = $tempArray[4];
                $FreqCarrier = 0;
                $Pol         = $tempArray[5];
                $AngleError  = $tempArray[6];
                $TS             = $tempArray[3];
                $ds = $tempArray[1];

                $qAS = "INSERT INTO CCA_TEST_PolAccuracy(FreqLO,FreqCarrier,Pol,AngleError,fkHeader,keyDataSet)
                  VALUES('$FreqLO','$FreqCarrier','$Pol','$AngleError','$TestData_header->keyId','$ds')";
                $rAS = mysqli_query($this->dbConnection, $qAS);
            }
        }
        $TestData_header->TS = $TS;
        unset($TestData_header);
    }

    public function Upload_InBandPower() {
        $TestData_header = TestData_header::NewRecord("TestData_header", 'keyId', $this->keyFacility, 'keyFacility');
        $TestData_header->fkTestData_Type = 36;
        $TestData_header->fkDataStatus = $this->fkDataStatus;
        $TestData_header->fkFE_Components = $this->keyId;
        $TestData_header->Band = $this->Band;
        $TestData_header->Update();

        //Upload file contents
        $TS = '';

        $filecontents = file($this->file_INBANDPOWER);
        for ($i = 0; $i < sizeof($filecontents); $i++) {
            $line_data = trim($filecontents[$i]);
            $tempArray   = explode(",", $line_data);
            if (count($tempArray) < 2) {
                $tempArray   = explode("\t", $line_data);
            }
            if (is_numeric(substr($tempArray[0], 0, 1)) == true) {
                $ds = $tempArray[1];
                $FreqLO      = $tempArray[4];
                $Pol         = $tempArray[5];
                $SB          = $tempArray[6];
                $TS           = $tempArray[3];
                if (trim(strtoupper($SB)) == "U") {
                    $SB = 1;
                }
                if (trim(strtoupper($SB)) == "L") {
                    $SB = 2;
                }
                $Power       = $tempArray[7];

                $qAS = "INSERT INTO CCA_TEST_InBandPower(FreqLO,Pol,SB,Power,fkHeader,keyDataSet)
                  VALUES('$FreqLO','$Pol','$SB','$Power','$TestData_header->keyId','$ds')";
                $rAS = mysqli_query($this->dbConnection, $qAS);
            }
        }
        $TestData_header->TS = $TS;
        unset($TestData_header);
    }

    public function Upload_TotalPower() {
        $TestData_header = TestData_header::NewRecord("TestData_header", 'keyId', $this->keyFacility, 'keyFacility');
        $TestData_header->fkTestData_Type = 37;
        $TestData_header->fkDataStatus = $this->fkDataStatus;
        $TestData_header->fkFE_Components = $this->keyId;
        $TestData_header->Band = $this->Band;
        $TestData_header->Update();

        //Upload file contents
        $TS = '';

        $filecontents = file($this->file_TOTALPOWER);
        for ($i = 0; $i < sizeof($filecontents); $i++) {
            $line_data = trim($filecontents[$i]);
            $tempArray   = explode(",", $line_data);
            if (count($tempArray) < 2) {
                $tempArray   = explode("\t", $line_data);
            }
            if (is_numeric(substr($tempArray[0], 0, 1)) == true) {
                $ds = $tempArray[1];
                $FreqLO      = $tempArray[4];
                $Pol         = $tempArray[5];
                $SB          = $tempArray[6];
                $TS             = $tempArray[3];
                if (trim(strtoupper($SB)) == "U") {
                    $SB = 1;
                }
                if (trim(strtoupper($SB)) == "L") {
                    $SB = 2;
                }
                $Power       = $tempArray[7];

                $qAS = "INSERT INTO CCA_TEST_TotalPower(FreqLO,Pol,SB,Power,fkHeader,keyDataSet)
                  VALUES('$FreqLO','$Pol','$SB','$Power','$TestData_header->keyId','$ds')";
                $rAS = mysqli_query($this->dbConnection, $qAS);
            }
        }
        $TestData_header->TS = $TS;
        //$TestData_header->Update();
        unset($TestData_header);
    }

    public function Upload_SidebandRatio() {
        $TestData_header = TestData_header::NewRecord("TestData_header", 'keyId', $this->keyFacility, 'keyFacility');
        $TestData_header->fkTestData_Type = 38;
        $TestData_header->fkDataStatus = $this->fkDataStatus;
        $TestData_header->fkFE_Components = $this->keyId;
        $TestData_header->Band = $this->Band;
        $TestData_header->Update();

        //Upload file contents
        $TS = '';
        $qAS = "INSERT INTO CCA_TEST_SidebandRatio(FreqLO,CenterIF,BWIF,Pol,SB,SBR,fkHeader,keyDataSet) VALUES ";
        $qVals = '';
        $filecontents = file($this->file_SIDEBANDRATIO);
        for ($i = 0; $i < sizeof($filecontents); $i++) {
            $line_data = trim($filecontents[$i]);
            $tempArray   = explode(",", $line_data);
            if (count($tempArray) < 2) {
                $tempArray   = explode("\t", $line_data);
            }
            $ds          = $tempArray[1];
            $FreqLO      = $tempArray[4];
            $CenterIF    = $tempArray[5];
            $BWIF        = $tempArray[6];
            $Pol         = $tempArray[7];
            $SB          = $tempArray[8];
            $TS          = $tempArray[3];
            if (trim(strtoupper($SB)) == "U") {
                $SB = 1;
            }
            if (trim(strtoupper($SB)) == "L") {
                $SB = 2;
            }
            $SBR         = $tempArray[9];
            if (is_numeric(substr($ds, 0, 1)) && $SBR != 'Nan' && $SBR != 0) {
                if ($qVals != "")
                    $qVals .= ",";
                $qVals .= "('$FreqLO','$CenterIF','$BWIF','$Pol','$SB','$SBR','$TestData_header->keyId','$ds')";
            }
        }
        $qAS .= $qVals . ";";
        $rAS = mysqli_query($this->dbConnection, $qAS);
        $TestData_header->TS = $TS;
        unset($TestData_header);
    }

    public function Upload_IVCurve() {
        $TestData_header = TestData_header::NewRecord("TestData_header", 'keyId', $this->keyFacility, 'keyFacility');
        $TestData_header->fkTestData_Type = 39;
        $TestData_header->fkDataStatus = $this->fkDataStatus;
        $TestData_header->fkFE_Components = $this->keyId;
        $TestData_header->Band = $this->Band;
        $TestData_header->Update();

        //Upload file contents
        $TS = '';

        $filecontents = file($this->file_IVCURVE);
        for ($i = 0; $i < sizeof($filecontents); $i++) {
            $line_data = trim($filecontents[$i]);
            $tempArray   = explode(",", $line_data);
            if (count($tempArray) < 2) {
                $tempArray   = explode("\t", $line_data);
            }
            if (is_numeric(substr($tempArray[0], 0, 1)) == true) {
                $ds = $tempArray[1];
                $FreqLO      = $tempArray[4];
                $Pol         = $tempArray[5];
                $SB          = $tempArray[6];
                $TS             = $tempArray[3];
                if (trim(strtoupper($SB)) == "U") {
                    $SB = 1;
                }
                if (trim(strtoupper($SB)) == "L") {
                    $SB = 2;
                }
                $VJ          = $tempArray[7];
                $IJ          = $tempArray[8];

                $qAS = "INSERT INTO CCA_TEST_IVCurve(FreqLO,Pol,SB,VJ,IJ,fkHeader,keyDataSet)
                  VALUES('$FreqLO','$Pol','$SB','$VJ','$IJ','$TestData_header->keyId','$ds')";
                $rAS = mysqli_query($this->dbConnection, $qAS);
            }
        }
        $TestData_header->TS = $TS;
        unset($TestData_header);
    }

    public function Upload_PowerVariation() {
        $TestData_header = TestData_header::NewRecord("TestData_header", 'keyId', $this->keyFacility, 'keyFacility');
        $TestData_header->fkTestData_Type = 40;
        $TestData_header->fkDataStatus = $this->fkDataStatus;
        $TestData_header->fkFE_Components = $this->keyId;
        $TestData_header->Band = $this->Band;
        $TestData_header->Update();

        //Upload file contents
        $TS = '';

        $filecontents = file($this->file_POWERVARIATION);
        for ($i = 0; $i < sizeof($filecontents); $i++) {
            $line_data = trim($filecontents[$i]);
            $tempArray   = explode(",", $line_data);
            if (count($tempArray) < 2) {
                $tempArray   = explode("\t", $line_data);
            }
            if (is_numeric(substr($tempArray[0], 0, 1)) == true) {
                $ds = $tempArray[1];
                $FreqLO      = $tempArray[4];
                $Pol         = $tempArray[5];
                $SB          = $tempArray[6];
                $TS             = $tempArray[3];
                if (trim(strtoupper($SB)) == "U") {
                    $SB = 1;
                }
                if (trim(strtoupper($SB)) == "L") {
                    $SB = 2;
                }
                $CenterIF    = $tempArray[7];
                $BWIF        = $tempArray[8];
                $PowerVar    = $tempArray[9];

                $qAS = "INSERT INTO CCA_TEST_PowerVariation(FreqLO,Pol,SB,CenterIF,BWIF,PowerVar,fkHeader,keyDataSet)
                  VALUES('$FreqLO','$Pol','$SB','$CenterIF','$BWIF','$PowerVar','$TestData_header->keyId','$ds')";
                $rAS = mysqli_query($this->dbConnection, $qAS);
            }
        }
        $TestData_header->TS = $TS;
        unset($TestData_header);
    }

    public function Upload_IFSpectrum() {
        $TestData_header = TestData_header::NewRecord("TestData_header", 'keyId', $this->keyFacility, 'keyFacility');
        $TestData_header->fkTestData_Type = 41;
        $TestData_header->fkDataStatus = $this->fkDataStatus;
        $TestData_header->fkFE_Components = $this->keyId;
        $TestData_header->Band = $this->Band;
        $TestData_header->Update();

        //Upload file contents
        $TS = '';

        $filecontents = file($this->file_IFSPECTRUM);
        for ($i = 0; $i < sizeof($filecontents); $i++) {
            $line_data = trim($filecontents[$i]);
            $tempArray   = explode(",", $line_data);
            if (count($tempArray) < 2) {
                $tempArray   = explode("\t", $line_data);
            }
            if (is_numeric(substr($tempArray[0], 0, 1)) == true) {
                $ds = $tempArray[1];
                $FreqLO      = $tempArray[4];
                $Pol         = $tempArray[8];
                $SB          = $tempArray[6];
                $TS             = $tempArray[3];
                if (trim(strtoupper($SB)) == "U") {
                    $SB = 1;
                }
                if (trim(strtoupper($SB)) == "L") {
                    $SB = 2;
                }
                $CenterIF    = $tempArray[5];
                $BWIF        = $tempArray[7];
                $Power       = $tempArray[9];

                $qAS = "INSERT INTO CCA_TEST_IFSpectrum(FreqLO,Pol,SB,CenterIF,BWIF,Power,fkHeader,keyDataSet)
                  VALUES('$FreqLO','$Pol','$SB','$CenterIF','$BWIF','$Power','$TestData_header->keyId','$ds')";
                $rAS = mysqli_query($this->dbConnection, $qAS);
            }
        }
        $TestData_header->TS = $TS;
        unset($TestData_header);
    }

    public function Upload_NoiseTemperature() {
        $TestData_header = TestData_header::NewRecord("TestData_header", 'keyId', $this->keyFacility, 'keyFacility');
        $TestData_header->fkTestData_Type = 42;
        $TestData_header->fkDataStatus = $this->fkDataStatus;
        $TestData_header->fkFE_Components = $this->keyId;
        $TestData_header->Band = $this->Band;
        $TestData_header->Update();

        //Upload file contents
        $TS = '';
        $qAS = "INSERT INTO CCA_TEST_NoiseTemperature(FreqLO,CenterIF,BWIF,Pol,SB,Treceiver,fkHeader,keyDataSet) VALUES ";
        $qVals = "";
        $filecontents = file($this->file_NOISETEMPERATURE);
        for ($i = 0; $i < sizeof($filecontents); $i++) {
            $line_data = trim($filecontents[$i]);
            $tempArray   = explode(",", $line_data);
            if (count($tempArray) < 2) {
                $tempArray   = explode("\t", $line_data);
            }
            $ds = $tempArray[1];
            $FreqLO      = $tempArray[4];
            $CenterIF    = $tempArray[5];
            $BWIF        = $tempArray[6];
            $Pol         = $tempArray[7];
            $SB          = $tempArray[8];
            $TS          = $tempArray[3];
            if (trim(strtoupper($SB)) == "U") {
                $SB = 1;
            }
            if (trim(strtoupper($SB)) == "L") {
                $SB = 2;
            }
            $Treceiver   = $tempArray[9];

            if (is_numeric(substr($ds, 0, 1)) && $CenterIF != 0 && $Treceiver != 'NaN' && $Treceiver != 0) {
                if ($qVals != "")
                    $qVals .= ",";
                $qVals .= "('$FreqLO','$CenterIF','$BWIF','$Pol','$SB','$Treceiver','$TestData_header->keyId','$ds')";
            }
        }
        $qAS .= $qVals . ";";
        $rAS = mysqli_query($this->dbConnection, $qAS);
        $TestData_header->TS = $TS;
        unset($TestData_header);
    }

    private function rmdir_recursive($dir) {
        $files = scandir($dir);
        array_shift($files);    // remove '.' from array
        array_shift($files);    // remove '..' from array
        foreach ($files as $file) {
            $file = $dir . '/' . $file;
            if (is_dir($file)) {
                $this->rmdir_recursive($file);
                //rmdir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dir);
    }

    public function Display_StatusSelector() {
        $qt = "SELECT keyStatusType, Status
               FROM StatusTypes
               ORDER BY keyStatusType ASC;";
        $rt = mysqli_query($this->dbConnection, $qt);

        echo "<select name = 'status_selector'>";

        while ($rowt = mysqli_fetch_array($rt)) {
            if ($rowt[0] == $this->sln->fkStatusType) {
                echo "<option  value='$rowt[0]' selected='selected'>$rowt[1]</option>";
            } else {
                echo "<option value='$rowt[0]'>$rowt[1]</option>";
            }
        }
        echo "</select>";
    }

    public function Display_UpdatedBySelector() {
        $qt = "SELECT keyStatusType, Status
               FROM StatusTypes
               ORDER BY keyStatusType ASC;";
        $rt = mysqli_query($this->dbConnection, $qt);

        echo "<select name = 'status_selector'>";

        while ($rowt = mysqli_fetch_array($rt)) {
            if ($rowt[0] == $this->sln->fkStatusType) {
                echo "<option  value='$rowt[0]' selected='selected'>$rowt[1]</option>";
            } else {
                echo "<option value='$rowt[0]'>$rowt[1]</option>";
            }
        }
        echo "</select>";
    }


    public function Display_LocationSelector() {
        $qt = "SELECT keyId, Description, Notes
               FROM Locations
               ORDER BY Description ASC;";
        $rt = mysqli_query($this->dbConnection, $qt);

        echo "<select name = 'location_selector'>";

        while ($rowt = mysqli_fetch_array($rt)) {
            if ($rowt[0] == $this->sln->fkLocationNames) {
                echo "<option  value='$rowt[0]' selected='selected'>$rowt[1] ($rowt[2])</option>";
            } else {
                echo "<option value='$rowt[0]'>$rowt[1] ($rowt[2])</option>";
            }
        }
        echo "</select>";
    }

    public function UpdateStatus($newStatus) {
        $sln = new SLN($this->keyId);
        if ($sln->keyId == "") {
            unset($sln);
            $sln = SLN::NewRecord("FE_StatusLocationAndNotes");
            $sln->fkFEComponents = $this->keyId;
        }
        $sln->fkStatusType = $newStatus;
        $sln->Update();
    }

    public function UpdateLocation($newLocation) {
        $sln = new SLN("FE_StatusLocationAndNotes", $this->keyId, "fkFEComponents");
        if ($sln->keyId == "") {
            unset($sln);
            $sln = SLN::NewRecord("FE_StatusLocationAndNotes");
            $sln->fkFEComponents = $this->keyId;
        }
        $sln->fkLocationNames = $newLocation;
        $sln->Update();
    }

    public function Update_Configuration_From_INI($INIfile) {
        // Parse the ini file with sections
        $ini_array = parse_ini_file($INIfile, true);
        //Is this CCA in the file? If not, exit
        //Check to see if the Band value in the file section matches this CCA band value.
        //If the values don't match,. or if the section doesn't exist, the operation
        //will be aborted.
        $sectionname = '~ColdCart' . $this->Band . "-" . $this->SN;
        $CheckBand = $ini_array[$sectionname]['Band'];
        $ccafound = false;
        if ($CheckBand == $this->Band) {
            $ccafound = true;
        }

        if ($ccafound) {
            //Warn user that CCA not found in the file
            $this->AddError("CCA " . $this->SN . " not found in this file!  Upload aborted.");
        } else {
            //Remove this CCA from the front end
            $dbops = new DBOperations();

            //Preserve these values in the new SLN record
            $oldStatus = $this->sln->fkStatusType;
            $oldLocation = $this->sln->fkLocationNames;

            //Get old status and location for the front end
            $ccaFE = new FrontEnd(NULL, $this->FEfc, FrontEnd::INIT_SLN, $this->FEConfig);
            $oldStatusFE = $ccaFE->fesln->fkStatusType;
            $oldLocationFE = $ccaFE->fesln->fkLocationNames;

            $dbops->RemoveComponentFromFrontEnd($this->keyFacility, $this->keyId, '', -1, -1);
            $FEid_old = $this->FEid;

            $this->GetFEConfig();

            //Create new component record, duplicate everything from the existing.
            $this->DuplicateRecord_CCA();

            //Get magnet params
            $keyVal = $ini_array[$sectionname]['MagnetParam01'];
            $tempArray = explode(',', $keyVal);
            $imag01 = $tempArray[1];
            $imag02 = $tempArray[2];
            $imag11 = $tempArray[3];
            $imag12 = $tempArray[4];

            //delete newly created duplicate mixer/preamp params, to be replaced from the contents of the ini file.
            $qdel = "DELETE FROM CCA_MixerParams WHERE fkComponent = $this->keyId;";
            $rdel = mysqli_query($this->dbConnection, $qdel);
            $qdel = "DELETE FROM CCA_PreampParams WHERE fkComponent = $this->keyId;";
            $rdel = mysqli_query($this->dbConnection, $qdel);

            for ($i_mp = 0; $i_mp < 100; $i_mp++) {
                $keyName = "MixerParam" . str_pad($i_mp + 1, 2, "0", STR_PAD_LEFT);
                $keyVal = $ini_array[$sectionname][$keyName];
                if (strlen($keyVal) > 2) {
                    $tempArray = explode(',', $keyVal);

                    //Create a set of four mixer params (or two if band 9)
                    //Use 01,02,11,12 all with same LO and fc, fkComponent
                    $lo = $tempArray[0];

                    $qmx01 = "INSERT INTO CCA_MixerParams(FreqLO,Pol,SB,fkComponent,fkFacility) ";
                    $qmx01 .= "VALUES('$lo','0','1','$this->keyId','" . $this->keyFacility . "');";
                    $rmx01 = mysqli_query($this->dbConnection, $qmx01);

                    $qmx11 = "INSERT INTO CCA_MixerParams(FreqLO,Pol,SB,fkComponent,fkFacility) ";
                    $qmx11 .= "VALUES('$lo','1','1','$this->keyId','" . $this->keyFacility . "');";
                    $rmx11 = mysqli_query($this->dbConnection, $qmx11);

                    if ($this->Band < 9) {
                        $qmx02 = "INSERT INTO CCA_MixerParams(FreqLO,Pol,SB,fkComponent,fkFacility) ";
                        $qmx02 .= "VALUES('$lo','0','2','$this->keyId','" . $this->keyFacility . "');";
                        $rmx02 = mysqli_query($this->dbConnection, $qmx02);

                        $qmx12 = "INSERT INTO CCA_MixerParams(FreqLO,Pol,SB,fkComponent,fkFacility) ";
                        $qmx12 .= "VALUES('$lo','1','2','$this->keyId','" . $this->keyFacility . "');";
                        $rmx12 = mysqli_query($this->dbConnection, $qmx12);
                    }

                    $this->MixerParams[$i_mp] = new MixerParams($this->keyId, $lo, $this->keyFacility);
                    $this->MixerParams[$i_mp]->lo     = $tempArray[0];
                    $this->MixerParams[$i_mp]->vj01   = $tempArray[1];
                    $this->MixerParams[$i_mp]->vj02   = $tempArray[2];
                    $this->MixerParams[$i_mp]->vj11   = $tempArray[3];
                    $this->MixerParams[$i_mp]->vj12   = $tempArray[4];
                    $this->MixerParams[$i_mp]->ij01   = $tempArray[5];
                    $this->MixerParams[$i_mp]->ij02   = $tempArray[6];
                    $this->MixerParams[$i_mp]->ij11   = $tempArray[7];
                    $this->MixerParams[$i_mp]->ij12   = $tempArray[8];
                    $this->MixerParams[$i_mp]->imag01 = $imag01;
                    $this->MixerParams[$i_mp]->imag02 = $imag02;
                    $this->MixerParams[$i_mp]->imag11 = $imag11;
                    $this->MixerParams[$i_mp]->imag12 = $imag12;
                    $this->MixerParams[$i_mp]->Update_MixerParams($this->keyId, $this->keyFacility);
                }
            }

            for ($i_pa = 0; $i_pa < 100; $i_pa++) {
                $keyName = "PreampParam" . str_pad(($i_pa + 1), 2, "0", STR_PAD_LEFT);
                $keyVal = $ini_array[$sectionname][$keyName];
                if (strlen($keyVal) > 2) {
                    $tempArray = explode(',', $keyVal);

                    $this->PreampParams[$i_pa] = CCA_PreampParams::NewRecord('CCA_PreampParams', 'keyId', $this->keyFacility, 'fkFacility');
                    $this->PreampParams[$i_pa]->fkComponent = $this->keyId;
                    $this->PreampParams[$i_pa]->FreqLO = $tempArray[0];
                    $this->PreampParams[$i_pa]->Pol = $tempArray[1];
                    $this->PreampParams[$i_pa]->SB = $tempArray[2];
                    $this->PreampParams[$i_pa]->VD1 = $tempArray[3];
                    $this->PreampParams[$i_pa]->VD2 = $tempArray[4];
                    $this->PreampParams[$i_pa]->VD3 = $tempArray[5];
                    $this->PreampParams[$i_pa]->ID1 = $tempArray[6];
                    $this->PreampParams[$i_pa]->ID2 = $tempArray[7];
                    $this->PreampParams[$i_pa]->ID3 = $tempArray[8];
                    $this->PreampParams[$i_pa]->VG1 = $tempArray[9];
                    $this->PreampParams[$i_pa]->VG2 = $tempArray[10];
                    $this->PreampParams[$i_pa]->VG3 = $tempArray[11];
                    $this->PreampParams[$i_pa]->Update();
                }
            }
            $updatestring = "Updated mixer, preamp params for CCA " . $this->Band . "-" . $this->SN . ".";

            //Add CCA to Front End
            $feconfig = $this->FEfc;
            $dbops->AddComponentToFrontEnd($FEid_old, $this->keyId, $this->FEfc, $this->keyFacility, '', $updatestring, ' ', -1);
            $dbops->UpdateStatusLocationAndNotes_Component($this->fc, $oldStatus, $oldLocation, $updatestring, $this->keyId, ' ', '');
            $this->GetFEConfig();
            $dbops->UpdateStatusLocationAndNotes_FE($this->FEfc, $oldStatusFE, $oldLocationFE, $updatestring, $this->FEConfig, $this->FEConfig, ' ', '');
            unset($dbops);
        }
        if (file_exists($INIfile)) {
            unlink($INIfile);
        }
    }

    public function Update_Configuration_From_ALMA_XML($XMLfile) {

        $ConfigData = simplexml_load_file($XMLfile);
        $ccaFound = false;
        if ($ConfigData) {
            $assy = (string) $ConfigData->ASSEMBLY['value'];
            list($band) = sscanf($assy, "ColdCart%d");
            if ($band && $band == $this->Band)
                $ccaFound = true;
        }
        if (!$ccaFound) {
            //Warn user that CCA not found in the file
            $this->AddError("CCA band " . $this->Band . " not found in this file!  Upload aborted.");
        } else {
            //Remove this CCA from the front end
            $dbops = new DBOperations();

            //Preserve these values in the new SLN record
            $oldStatus = $this->sln->fkStatusType;
            $oldLocation = $this->sln->fkLocationNames;

            //Get old status and location for the front end
            $ccaFE = new FrontEnd(NULL, $this->FEfc, FrontEnd::INIT_SLN, $this->FEConfig);
            $oldStatusFE = $ccaFE->fesln->fkStatusType;
            $oldLocationFE = $ccaFE->fesln->fkLocationNames;

            $dbops->RemoveComponentFromFrontEnd($this->keyFacility, $this->keyId, '', -1, -1);
            $FEid_old = $this->FEid;

            $this->GetFEConfig();

            //Create new component record, duplicate everything from the existing.
            $this->DuplicateRecord_CCA();

            //delete newly created duplicate mixer/preamp params, to be replaced from the contents of the ini file.
            $qdel = "DELETE FROM CCA_MixerParams WHERE fkComponent = $this->keyId;";
            $rdel = mysqli_query($this->dbConnection, $qdel);
            $qdel = "DELETE FROM CCA_PreampParams WHERE fkComponent = $this->keyId;";
            $rdel = mysqli_query($this->dbConnection, $qdel);

            //Get magnet params array indexed by LO string:
            $magnetParams = array();
            foreach ($ConfigData->MagnetParams as $param) {
                $FreqLO = ((float) $param['FreqLO']) / 1E9;
                $magnetParams[$FreqLO] = array(
                    'IMag01' => (float) $param['IMag01'],
                    'IMag02' => (float) $param['IMag02'],
                    'IMag11' => (float) $param['IMag11'],
                    'IMag12' => (float) $param['IMag12']
                );
            }

            //Get mixer params:
            $i = 0;
            foreach ($ConfigData->MixerParams as $param) {
                $FreqLO = ((float) $param['FreqLO']) / 1E9;
                $IMag01 = $magnetParams[$FreqLO]['IMag01'];
                $IMag02 = $magnetParams[$FreqLO]['IMag02'];
                $IMag11 = $magnetParams[$FreqLO]['IMag11'];
                $IMag12 = $magnetParams[$FreqLO]['IMag12'];

                // Create empty CCA_MixerParams records to update:
                $qmx01 = "INSERT INTO CCA_MixerParams(FreqLO,Pol,SB,fkComponent,fkFacility) ";
                $qmx01 .= "VALUES('$FreqLO','0','1','$this->keyId','" . $this->keyFacility . "');";
                $rmx01 = mysqli_query($this->dbConnection, $qmx01);

                $qmx11 = "INSERT INTO CCA_MixerParams(FreqLO,Pol,SB,fkComponent,fkFacility) ";
                $qmx11 .= "VALUES('$FreqLO','1','1','$this->keyId','" . $this->keyFacility . "');";
                $rmx11 = mysqli_query($this->dbConnection, $qmx11);

                if ($this->Band < 9) {
                    $qmx02 = "INSERT INTO CCA_MixerParams(FreqLO,Pol,SB,fkComponent,fkFacility) ";
                    $qmx02 .= "VALUES('$FreqLO','0','2','$this->keyId','" . $this->keyFacility . "');";
                    $rmx02 = mysqli_query($this->dbConnection, $qmx02);

                    $qmx12 = "INSERT INTO CCA_MixerParams(FreqLO,Pol,SB,fkComponent,fkFacility) ";
                    $qmx12 .= "VALUES('$FreqLO','1','2','$this->keyId','" . $this->keyFacility . "');";
                    $rmx12 = mysqli_query($this->dbConnection, $qmx12);
                }

                $this->MixerParams[$i] = new MixerParams($this->keyId, $FreqLO, $this->keyFacility);
                $this->MixerParams[$i]->lo     = (string) $FreqLO;
                $this->MixerParams[$i]->vj01   = (string) $param['VJ01'];
                $this->MixerParams[$i]->vj02   = (string) $param['VJ02'];
                $this->MixerParams[$i]->vj11   = (string) $param['VJ11'];
                $this->MixerParams[$i]->vj12   = (string) $param['VJ12'];
                $this->MixerParams[$i]->ij01   = (string) $param['IJ01'];
                $this->MixerParams[$i]->ij02   = (string) $param['IJ02'];
                $this->MixerParams[$i]->ij11   = (string) $param['IJ11'];
                $this->MixerParams[$i]->ij12   = (string) $param['IJ12'];
                $this->MixerParams[$i]->imag01 = $IMag01;
                $this->MixerParams[$i]->imag02 = $IMag02;
                $this->MixerParams[$i]->imag11 = $IMag11;
                $this->MixerParams[$i]->imag12 = $IMag12;
                $this->MixerParams[$i]->Update_MixerParams($this->keyId, $this->keyFacility);
                $i++;
            }

            $i = 0;
            foreach ($ConfigData->PreampParamsPol0Sb1 as $param) {
                $this->PreampParams[$i] = CCA_PreampParams::NewRecord('CCA_PreampParams', 'keyId', $this->keyFacility, 'fkFacility');
                $this->PreampParams[$i]->fkComponent = $this->keyId;
                $this->PreampParams[$i]->FreqLO = (string)((float) $param['FreqLO']) / 1E9;
                $this->PreampParams[$i]->Pol = '0';
                $this->PreampParams[$i]->SB = '1';
                $this->PreampParams[$i]->VD1 = (string) $param['VD1'];
                $this->PreampParams[$i]->VD2 = (string) $param['VD2'];
                $this->PreampParams[$i]->VD3 = (string) $param['VD3'];
                $this->PreampParams[$i]->ID1 = (string) $param['ID1'];
                $this->PreampParams[$i]->ID2 = (string) $param['ID2'];
                $this->PreampParams[$i]->ID3 = (string) $param['ID3'];
                $this->PreampParams[$i]->VG1 = (string) 0;
                $this->PreampParams[$i]->VG2 = (string) 0;
                $this->PreampParams[$i]->VG3 = (string) 0;
                $this->PreampParams[$i]->Update();
                $i++;
            }
            foreach ($ConfigData->PreampParamsPol0Sb2 as $param) {
                $this->PreampParams[$i] = CCA_PreampParams::NewRecord('CCA_PreampParams', 'keyId', $this->keyFacility, 'fkFacility');
                $this->PreampParams[$i]->fkComponent = $this->keyId;
                $this->PreampParams[$i]->FreqLO = (string)((float) $param['FreqLO']) / 1E9;
                $this->PreampParams[$i]->Pol = '0';
                $this->PreampParams[$i]->SB = '2';
                $this->PreampParams[$i]->VD1 = (string) $param['VD1'];
                $this->PreampParams[$i]->VD2 = (string) $param['VD2'];
                $this->PreampParams[$i]->VD3 = (string) $param['VD3'];
                $this->PreampParams[$i]->ID1 = (string) $param['ID1'];
                $this->PreampParams[$i]->ID2 = (string) $param['ID2'];
                $this->PreampParams[$i]->ID3 = (string) $param['ID3'];
                $this->PreampParams[$i]->VG1 = (string) 0;
                $this->PreampParams[$i]->VG2 = (string) 0;
                $this->PreampParams[$i]->VG3 = (string) 0;
                $this->PreampParams[$i]->Update();
                $i++;
            }
            foreach ($ConfigData->PreampParamsPol1Sb1 as $param) {
                $this->PreampParams[$i] = CCA_PreampParams::NewRecord('CCA_PreampParams', 'keyId', $this->keyFacility, 'fkFacility');
                $this->PreampParams[$i]->fkComponent = $this->keyId;
                $this->PreampParams[$i]->FreqLO = (string)((float) $param['FreqLO']) / 1E9;
                $this->PreampParams[$i]->Pol = '1';
                $this->PreampParams[$i]->SB = '1';
                $this->PreampParams[$i]->VD1 = (string) $param['VD1'];
                $this->PreampParams[$i]->VD2 = (string) $param['VD2'];
                $this->PreampParams[$i]->VD3 = (string) $param['VD3'];
                $this->PreampParams[$i]->ID1 = (string) $param['ID1'];
                $this->PreampParams[$i]->ID2 = (string) $param['ID2'];
                $this->PreampParams[$i]->ID3 = (string) $param['ID3'];
                $this->PreampParams[$i]->VG1 = (string) 0;
                $this->PreampParams[$i]->VG2 = (string) 0;
                $this->PreampParams[$i]->VG3 = (string) 0;
                $this->PreampParams[$i]->Update();
                $i++;
            }
            foreach ($ConfigData->PreampParamsPol1Sb2 as $param) {
                $this->PreampParams[$i] = CCA_PreampParams::NewRecord('CCA_PreampParams', 'keyId', $this->keyFacility, 'fkFacility');
                $this->PreampParams[$i]->fkComponent = $this->keyId;
                $this->PreampParams[$i]->FreqLO = (string)((float) $param['FreqLO']) / 1E9;
                $this->PreampParams[$i]->Pol = '1';
                $this->PreampParams[$i]->SB = '2';
                $this->PreampParams[$i]->VD1 = (string) $param['VD1'];
                $this->PreampParams[$i]->VD2 = (string) $param['VD2'];
                $this->PreampParams[$i]->VD3 = (string) $param['VD3'];
                $this->PreampParams[$i]->ID1 = (string) $param['ID1'];
                $this->PreampParams[$i]->ID2 = (string) $param['ID2'];
                $this->PreampParams[$i]->ID3 = (string) $param['ID3'];
                $this->PreampParams[$i]->VG1 = (string) 0;
                $this->PreampParams[$i]->VG2 = (string) 0;
                $this->PreampParams[$i]->VG3 = (string) 0;
                $this->PreampParams[$i]->Update();
                $i++;
            }

            $updatestring = "Updated mixer, preamp params for CCA " . $this->Band . "-" . $this->SN . ".";

            //Add CCA to Front End
            $feconfig = $this->FEfc;
            $dbops->AddComponentToFrontEnd($FEid_old, $this->keyId, $this->FEfc, $this->keyFacility, '', $updatestring, ' ', -1);
            $dbops->UpdateStatusLocationAndNotes_Component($this->fc, $oldStatus, $oldLocation, $updatestring, $this->keyId, ' ', '');
            $this->GetFEConfig();
            $dbops->UpdateStatusLocationAndNotes_FE($this->FEfc, $oldStatusFE, $oldLocationFE, $updatestring, $this->FEConfig, $this->FEConfig, ' ', '');
            unset($dbops);
        }
        if (file_exists($XMLfile)) {
            unlink($XMLfile);
        }
    }

    private function DuplicateRecord_CCA() {
        self::DuplicateRecord();

        //Duplicate Mixer Params
        $rmx = MixerParams::getIdFromComponent($this->keyId);
        while ($rowmx = mysqli_fetch_array($rmx)) {
            $mx_temp = new MixerParams($rowmx[0], "", $this->keyFacility);
            $mx_temp->DuplicateRecord();
            $mx_temp->fkComponent = $this->keyId;
            $mx_temp->Update();
            unset($mx_temp);
        }

        if (isset($this->PreampParams)) {
            for ($i = 0; $i < count($this->PreampParams); $i++) {
                $this->PreampParams[$i]->DuplicateRecord();
                $this->PreampParams[$i]->fkComponent = $this->keyId;
                $this->PreampParams[$i]->Update();
            }
        }

        if (isset($this->TempSensors)) {
            foreach ($this->TempSensors as $tempSensor) {
                if ($tempSensor->keyId != '') {
                    $tempSensor->DuplicateRecord();
                    $tempSensor->fkComponent = $this->keyId;
                    $tempSensor->Update();
                }
            }
        }
    }

    public function Display_MixerParams_Edit() {
        for ($pol = 0; $pol <= 1; $pol++) {
            for ($sb = 1; $sb <= 2; $sb++) {
                $q = "SELECT Temperature,FreqLO,VJ,IJ,IMAG,TS
                      FROM CCA_MixerParams
                      WHERE fkComponent = $this->keyId
                      AND Pol = $pol
                      AND SB = $sb
                      ORDER BY FreqLO ASC";

                $r = mysqli_query($this->dbConnection, $q);
                $ts = ADAPT_mysqli_result($r, 0, 5);
                $r = mysqli_query($this->dbConnection, $q);
                if (mysqli_num_rows($r) > 0) {
                    echo "
                    <div style= 'width: 500px;'>
                    <table id = 'table6' border = '1'>";

                    echo "
                        <tr class='alt'><th colspan = '5'>
                            Mixer Pol $pol SB $sb <i>($ts, CCA " . $this->Band . "-" . $this->SN . ")</i>

                            </th>
                        </tr>
                        <tr>
                        <th>LO (GHz)</th>
                        <th>VJ</th>
                        <th>IJ</th>
                        <th>IMAG</th>
                      </tr>";
                    $count = 0;
                    while ($row = mysqli_fetch_array($r)) {
                        if ($count % 2 == 0) {
                            echo "<tr>";
                        } else {
                            echo "<tr class = 'alt'>";
                        }
                        echo "

                        <td>$row[1]</td>
                        <td><input name = 'Mixer$row[1]VJ" .  $pol . "" . $sb . "' value='$row[2]' size = '5'></td>
                        <td><input name = 'Mixer$row[1]IJ" .  $pol . "" . $sb . "' value='$row[3]' size = '5'></td>
                        <td><input name = 'Mixer$row[1]IMAG" . $pol . "" . $sb . "' value='$row[4]' size = '5'></td>
                    </tr>";
                        $count += 1;
                    }
                    echo "</table></div><br><br>";
                } //end check for numrows
            } //end for sb
        } //end for pol
    }

    public function Display_PreampParams_Edit() {
        $maxSb = ($this->hasSB2()) ? 2 : 1;
        $pcount = 0;
        if (isset($this->PreampParams[0])) {
            for ($pol = 0; $pol <= 1; $pol++) {
                for ($sb = 1; $sb <= $maxSb; $sb++) {
                    echo "
                        <div style= 'width: 500px'>
                        <table id = 'table6' border = '1'>";

                    echo "<tr class='alt'><th colspan = '11'>
                                Preamp Pol " . $pol . "
                                SB " . $sb . " <i>
                                (";
                    if (isset($this->PreampParams[$pcount])) {
                        if ($this->PreampParams[$pcount]->keyId != "") {
                            echo $this->PreampParams[$pcount]->TS;
                        }
                    }

                    echo ", CCA " . $this->Band . "-" . $this->SN . ")</i>

                                </th>
                            </tr>";

                    echo "<th>LO (GHz)</th>
                            <th>VD1</th>
                            <th>VD2</th>
                            <th>VD3</th>
                            <th>ID1</th>
                            <th>ID2</th>
                            <th>ID3</th>
                          </tr>";
                    $count = 0;
                    for ($i = 0; $i < count($this->PreampParams); $i++) {
                        //while($row = mysqli_fetch_array($r)) {
                        if (($this->PreampParams[$i]->Pol == $pol)
                            && ($this->PreampParams[$i]->SB == $sb)
                        ) {


                            if ($count % 2 == 0) {
                                echo "<tr>";
                            } else {
                                echo "<tr class = 'alt'>";
                            }
                            echo "
                            <td>{$this->PreampParams[$i]->FreqLO}</td>
                            <td><input name = 'PreampVD1_$i' size = '5' value = '{$this->PreampParams[$i]->VD1}'></input></td>
                            <td><input name = 'PreampVD2_$i' size = '5' value = '{$this->PreampParams[$i]->VD2}'></input></td>
                            <td><input name = 'PreampVD3_$i' size = '5' value = '{$this->PreampParams[$i]->VD3}'></input></td>
                            <td><input name = 'PreampID1_$i' size = '5' value = '{$this->PreampParams[$i]->ID1}'></input></td>
                            <td><input name = 'PreampID2_$i' size = '5' value = '{$this->PreampParams[$i]->ID2}'></input></td>
                            <td><input name = 'PreampID3_$i' size = '5' value = '{$this->PreampParams[$i]->ID3}'></input></td>
                        </tr>";
                            $count += 1;
                        }
                    } // end for count preampparams
                    //}//end check for numrows
                    echo "</table></div><br>";
                    $pcount += 1;
                }
            }
        }
        echo "<br><br><br><br><br><br><br><br><br><br><br>.<br>";
    }


    public function Display_TempSensors_Edit() {
        $locs[0] = "Spare";
        $locs[1] = "110K Stage";
        $locs[2] = "15K Stage";
        $locs[3] = "4K Stage";
        $locs[4] = "Pol 0 Mixer";
        $locs[5] = "Pol 1 Mixer";


        $ts = "";
        if (isset($this->TempSensors[1]) && $this->TempSensors[1]->keyId != '') {
            $ts = $this->TempSensors[1]->TS . ",";
        }

        echo "<div style= 'width: 350px'><table id = 'table6'>";
        echo "<tr class='alt'>
                <th colspan = '4'>TEMPERATURE SENSORS <br><i>($ts CCA {$this->Band}-{$this->SN})</i></th>
              </tr>";
        echo "<tr>
                <th>Location</th>
                <th>Model</th>
                <th>SN</th>
                <th>OffsetK</th>

              </tr>";

        for ($i = 1; $i <= count($this->TempSensors); $i++) {
            if (isset($this->TempSensors[$i]) && $this->TempSensors[$i]->keyId != "") {
                if ($i % 2 == 0) {
                    echo "<tr>";
                } else {
                    echo "<tr class = 'alt'>";
                }
                echo "
                <td>$locs[$i]</td>
                <td>{$this->TempSensors[$i]->Model}</td>
                <td>{$this->TempSensors[$i]->SN}</td>
                <td><input name = 'TempSensorOffsetK_$i' size ='5' value='{$this->TempSensors[$i]->OffsetK}'></input></td>

              </tr>";
            }
        }
        echo "</table></div><br><br>";
    }

    public function Update_Configuration() {
        $dbops = new DBOperations();
        //This initialize function gets the front end info associated with this CCA.
        self::__construct($this->keyId, $this->keyFacility);

        //Preserve these values in the new SLN record
        $oldStatus = $this->sln->fkStatusType;
        $oldLocation = $this->sln->fkLocationNames;

        //Get old FE status and location
        $ccaFE = new FrontEnd(NULL, $this->FEfc, FrontEnd::INIT_SLN, $this->FEConfig);
        $oldStatusFE = $ccaFE->fesln->fkStatusType;
        $oldLocationFE = $ccaFE->fesln->fkLocationNames;

        $dbops->RemoveComponentFromFrontEnd($this->keyFacility, $this->keyId, '', -1, -1);
        $FEid_old = $this->FEid;
        $this->DuplicateRecord_CCA();

        //Update temp sensors
        foreach ($this->TempSensors as $key => $tempSensor) {

            if (isset($_REQUEST["TempSensorOffsetK_$key"])) {

                $tempSensor->OffsetK = $_REQUEST["TempSensorOffsetK_$key"];
            }
            $tempSensor->fkFE_Components = $this->keyId;
            $tempSensor->Update();
        }

        //Update Preamps
        for ($i = 0; $i < count($this->PreampParams); $i++) {
            if (isset($_REQUEST["PreampVD1_$i"])) {
                $this->PreampParams[$i]->VD1 = $_REQUEST["PreampVD1_$i"];
                $this->PreampParams[$i]->VD2 = $_REQUEST["PreampVD2_$i"];
                $this->PreampParams[$i]->VD3 = $_REQUEST["PreampVD3_$i"];
                $this->PreampParams[$i]->ID1 = $_REQUEST["PreampID1_$i"];
                $this->PreampParams[$i]->ID2 = $_REQUEST["PreampID2_$i"];
                $this->PreampParams[$i]->ID3 = $_REQUEST["PreampID3_$i"];
                $this->PreampParams[$i]->VG1 = (string) 0;
                $this->PreampParams[$i]->VG2 = (string) 0;
                $this->PreampParams[$i]->VG3 = (string) 0;
            }
            $this->PreampParams[$i]->fkFE_Components = $this->keyId;
            $this->PreampParams[$i]->Update();
        }

        //Update Mixer Params
        for ($i = 0; $i < count($this->MixerParams); $i++) {
            if (isset($_REQUEST["Mixer{$this->MixerParams[$i]->lo}VJ01"])) {
                $this->MixerParams[$i]->vj01 = $_REQUEST["Mixer{$this->MixerParams[$i]->lo}VJ01"];
                $this->MixerParams[$i]->vj11 = $_REQUEST["Mixer{$this->MixerParams[$i]->lo}VJ11"];
                $this->MixerParams[$i]->ij01 = $_REQUEST["Mixer{$this->MixerParams[$i]->lo}IJ01"];
                $this->MixerParams[$i]->ij11 = $_REQUEST["Mixer{$this->MixerParams[$i]->lo}IJ11"];
                $this->MixerParams[$i]->imag01 = $_REQUEST["Mixer{$this->MixerParams[$i]->lo}IMAG01"];
                $this->MixerParams[$i]->imag11 = $_REQUEST["Mixer{$this->MixerParams[$i]->lo}IMAG11"];
                if ($this->Band <= 8) {
                    $this->MixerParams[$i]->vj02 = $_REQUEST["Mixer{$this->MixerParams[$i]->lo}VJ02"];
                    $this->MixerParams[$i]->vj12 = $_REQUEST["Mixer{$this->MixerParams[$i]->lo}VJ12"];
                    $this->MixerParams[$i]->ij02 = $_REQUEST["Mixer{$this->MixerParams[$i]->lo}IJ02"];
                    $this->MixerParams[$i]->ij12 = $_REQUEST["Mixer{$this->MixerParams[$i]->lo}IJ12"];
                    $this->MixerParams[$i]->imag02 = $_REQUEST["Mixer{$this->MixerParams[$i]->lo}IMAG02"];
                    $this->MixerParams[$i]->imag12 = $_REQUEST["Mixer{$this->MixerParams[$i]->lo}IMAG12"];
                }

                $this->MixerParams[$i]->Update_MixerParams($this->keyId, $this->keyFacility);
            }
        }

        //Add CCA back to Front End
        $updatestring = "Edited Mixer/Preamp Params for CCA " . $this->Band . "-" . $this->SN;
        $feconfig = $this->FEConfig;
        $dbops->AddComponentToFrontEnd($FEid_old, $this->keyId, $this->FEfc, $this->keyFacility, '', $updatestring, '', -1);
        $dbops->UpdateStatusLocationAndNotes_Component($this->keyFacility, $oldStatus, $oldLocation, $updatestring, $this->keyId, ' ', '');
        $this->GetFEConfig();
        $dbops->UpdateStatusLocationAndNotes_FE($this->FEfc, $oldStatusFE, $oldLocationFE, $updatestring, $this->FEConfig, $this->FEConfig, ' ', '');
        unset($dbops);
    }

    public function DuplicateRecord() {
        /*
         * This function creates a new record in the database, and copies the current parameter values into the
         * new record.
         */

        //Create the INSERT query using all the parameter names and values of this object.
        $qCopy = "INSERT INTO $this->tableName (";

        foreach ($this->columnNames as $column) {
            if (($column != $this->keyIdName) && ($column != "TS")) {
                $qCopy .= $column . ",";
            }
        }
        $qCopy = rtrim($qCopy, ",") . ") VALUES (";
        foreach ($this->columnNames as $column) {
            if (($column != $this->keyIdName) && ($column != "TS")) {
                $qCopy .= "'{$this->{$column}}',";
            }
        }
        $qCopy = rtrim($qCopy, ",") . ");";
        mysqli_query($this->dbConnection, $qCopy);

        //After the new duplicate record is created, get the primary key value and reinitialize
        //this object to the newly created record.
        $qMax = "SELECT MAX($this->keyIdName) FROM $this->tableName;";
        $rMax = mysqli_query($this->dbConnection, $qMax);
        $newId = ADAPT_mysqli_result($rMax, 0, 0);
        $this->__construct($newId, $this->keyFacility);
    }
}
