<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link rel="stylesheet" type="text/css" href="../../ext4/resources/css/ext-all.css" />
<script type="text/javascript" src="../../ext4/ext-all-debug.js"></script>
<script type="text/javascript" src="pickComponent.js"></script>
<title>pickComponent Test</title>
</head><body>

<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once(site_get_config_main());

echo "<div id='here'></div>";

echo "<script type='text/javascript'>
        function callback(obj) { alert(obj['name'] + ' : ' + obj['id']); }
        Ext.onReady(function() {
            pickComponent(ComponentTypes.FE, 'here', 'Select Front End', callback);
            pickComponent(ComponentTypes.CCA, 'here', 'Select CCA', callback, 3);
            pickComponent(ComponentTypes.WCA, 'here', 'Select WCA', callback, 3);
        });</script>";

?>
</body>
</html>
