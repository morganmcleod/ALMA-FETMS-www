<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_dbConnect);

function FixHyperlink($doclink) {
    /*
     * Made links into clickable hyperlink urls for display.
     */

    //In case no changes need to be made, just return the original value.
    $newdoclink = $doclink;
    $FirstChar = substr($doclink, 0, 1);
    if ($FirstChar == "\\") {
        //UNC path
        $newdoclink = str_replace("\\", "/", $doclink);
        //Check if Firefox or Internet Explorer
        if (strlen(strstr(strtolower($_SERVER['HTTP_USER_AGENT']), "firefox")) > 0) {
            //If Firefox...
            $newdoclink = "file:///" . $newdoclink;
        } else {
            //If Internet Explorere
            $newdoclink = "file:" . $newdoclink;
        }
    }

    if (($FirstChar != "\\") && ($FirstChar != "h")) {
        //Add http:// if not a UNC path, and doesn't start with "http"
        $newdoclink = "http://$doclink";
    }

    if (strlen($doclink) < 5) {
        $newdoclink = $doclink;
    }
    return $newdoclink;
}

function FixHyperlinkForMySQL($doclink) {
    $dbconnection = site_getDbConnection();
    /*
     * This will preserve a UNC path when inserted in a MySQL query.
     */
    $newdoclink = trim($doclink);
    if (substr($doclink, 0, 1) == "\\") {
        $newdoclink = mysqli_real_escape_string($dbconnection, $doclink);
    }
    return trim($newdoclink);
}

function Warn($Warning) {
    echo '<script type = "text/javascript">';
    echo 'alert("' . $Warning . '");';
    echo '</script>';
}

function WriteINI($inifile, $key, $value) {
    require(site_get_config_main());

    $progressfile = $main_write_directory . $inifile . ".txt";

    if (GetPHPVersion() < 5.3) {
        $ini_array = parse_ini_file($progressfile);
    }
    if (GetPHPVersion() >= 5.3) {
        //parse_ini needs an extra argument for new versions of php
        $ini_array = parse_ini_file($progressfile, false, INI_SCANNER_RAW);
    }
    $ini_array[$key] = $value;
    write_ini_file($ini_array, $progressfile);
}

function write_ini_file($assoc_arr, $path, $has_sections = FALSE) {
    $content = "";
    if ($has_sections) {
        foreach ($assoc_arr as $key => $elem) {
            $content .= "[" . $key . "]\r\n";
            foreach ($elem as $key2 => $elem2) {
                if (is_array($elem2)) {
                    for ($i = 0; $i < count($elem2); $i++) {
                        $content .= $key . "[] = \"" . $elem2[$i] . "\"\r\n";
                    }
                } else if ($elem2 == "") $content .= $key2 . " = \r\n";
                else $content .= $key . " = \"" . $elem2 . "\"\r\n";
            }
        }
    } else {
        foreach ($assoc_arr as $key => $elem) {
            if (is_array($elem)) {
                for ($i = 0; $i < count($elem); $i++) {
                    $content .= $key . "[] = \"" . $elem[$i] . "\"\r\n";
                }
            } else if ($elem == "") $content .= $key . " = \r\n";
            else $content .= $key . " = \"" . $elem . "\"\r\n";
        }
    }

    if (!$handle = fopen($path, 'w')) {
        return false;
    }
    if (!fwrite($handle, $content)) {
        return false;
    }
    fclose($handle);
    return true;
}

function GetDateTimeString() {
    return date("Y_m_d_H_i_s");
}

function CreateProgressFile($msg = 'no message', $image = '', $url = '', $ProgressFileName = '') {
    require(site_get_config_main());

    $progressfile = $ProgressFileName;

    if ($progressfile == '') {
        $progressfile = "Progress_" . GetDateTimeString();
    }
    $progressfile_path = $main_write_directory . $progressfile . ".txt";

    if (file_exists($progressfile_path)) {
        unlink($progressfile_path);
        sleep(2);
    }

    $fh = fopen($progressfile_path, 'w');
    $pstring  = "plotmessage =$msg\r\n";
    $pstring .= "message =...\r\n";
    $pstring .= "progress =0\r\n";
    $pstring .= "error =0\r\n";
    $pstring .= "errormessage = no error.\r\n";
    $pstring .= "image =$image\r\n";
    $pstring .= "refurl =$url\r\n";
    $pstring .= "abort = 0\r\n";
    fwrite($fh, $pstring);
    fclose($fh);
    return ($progressfile);
}

function GetPHPVersion() {
    $versionarray = explode('.', PHP_VERSION);
    $phpversion = $versionarray[0] . "." . $versionarray[1];
    return $phpversion;
}
