<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<link href="../images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
<link rel="stylesheet" type="text/css" href="../Cartstyle.css">
<link rel="stylesheet" type="text/css" href="../buttons.css">

<head>
    <meta name="author" content="Darko Bunic" />
    <meta name="description" content="AJAX progress bar" />
    <link rel="stylesheet" href="style.css" type="text/css" media="screen" />
    <script type="text/javascript" src="statusscript.js"></script>
    <title>Generating</title>
</head>

<?php

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.frontend.php');
require_once($site_classes . '/class.logger.php');
require_once($site_config_main);

$lf = $_REQUEST['lf'];
$fecfg = $_REQUEST['fecfg'];
$fc = $_REQUEST['fc'];
$pb = $_REQUEST['pb'];
$adn = $_REQUEST['adn'];

$fe = new FrontEnd();
$fe->Initialize_FrontEnd_FromConfig($fecfg, $fc, FrontEnd::INIT_NONE);

$title = "CIDL Front End " . $fe->GetValue('SN');
include('header.php');

$progressfile = $main_write_directory . $fecfg . "_" . "$fc.txt";

if (file_exists($progressfile)) {
    unlink($progressfile);
}

$url = "../GenerateCIDL.php?fecfg=$fecfg&fc=$fc&pb=$pb&adn=$adn";

$l = new Logger("STATUSCIDL.txt");
$l->WriteLogFile($url);

echo "<meta http-equiv='refresh' content='3;URL=$url'>";

?>

<body style="background-color: #19475E;">
    <div id="maincontent">
        <div id="progressbar_container">
            <table cellspacing="10">
                <tr>
                    <td>
                        <div id="message_container">

                            <h2>
                                <font color="#ffffff">
                                    <div id="mainmessage"></div>
                                </font>
                            </h2>

                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div id="progress_container">
                            <div id="progress" style="width: 0%"></div>
                            <script type='text/javascript'>
                                polling_start('<?php echo $lf; ?>');
                            </script>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div id="message_container">
                            <div id="pmessage" style="width: 100%"></div></img>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td align='left'>
                        <div id="message_container">
                            <a style="width:90px" onclick="javascript:polling_stop()" class="button redbigrounded'
								<span style=" width:130px">STOP</span>
                            </a>
                        </div>
                    </td>
                </tr>
            </table>

        </div>
        <br>
        <div id="progressbar_container">
            <font color="#ffffff"><b>Result Page (clicking will open in new window):</font></b><br><br>
            <div id="refurl"></div>
        </div>
        <br>
        <div id="refurl"></div>
        <div id="pimage_container">
            <div id="pimage" style="width: 50%"></div>
        </div>
    </div>
</body>

</html>
