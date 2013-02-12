<?php

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.logger.php');
require_once($site_FEConfig . '/HelperFunctions.php');
require_once($site_config_main);

$progressfile = $main_write_directory . $_REQUEST['lf'] . ".txt";
$ini_array = parse_ini_file($progressfile);
$ini_array['abort'] = 1;
write_ini_file($ini_array, $progressfile);
?>