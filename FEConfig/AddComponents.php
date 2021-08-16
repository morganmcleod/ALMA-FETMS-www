<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link type="text/css" href="../ext/resources/css/ext-all.css" media="screen" rel="Stylesheet" />
    <link rel="stylesheet" type="text/css" href="recordform/css/icons.css">
    <link rel="stylesheet" type="text/css" href="recordform/css/Ext.ux.grid.RowActions.css">
    <link rel="stylesheet" type="text/css" href="../ext/examples/ux/css/RowEditor.css">
    <link rel="stylesheet" type="text/css" href="recordform/css/empty.css" id="theme">
    <link rel="stylesheet" type="text/css" href="recordform/css/recordform.css">
    <link rel="shortcut icon" href="../img/extjs.ico">
    <link rel="stylesheet" type="text/css" href="Cartstyle.css">
    <link rel="stylesheet" type="text/css" href="buttons.css">
    <link href="images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
    <script src="../ext/adapter/ext/ext-base.js" type="text/javascript"></script>
    <script src="../ext/ext-all.js" type="text/javascript"></script>

    <script src="../ext/examples/ux/fileuploadfield/FileUploadField.js" type="text/javascript"></script>
    <script type="text/javascript" src="../ext/examples/ux/RowEditor.js"></script>
    <script type="text/javascript" src="recordform/js/WebPage.js"></script>
    <script type="text/javascript" src="recordform/js/Ext.ux.util.js"></script>
    <script type="text/javascript" src="recordform/js/Ext.ux.form.ThemeCombo.js"></script>
    <script type="text/javascript" src="recordform/js/Ext.ux.Toast.js"></script>
    <script type="text/javascript" src="recordform/js/Ext.ux.grid.Search.js"></script>
    <script type="text/javascript" src="recordform/js/Ext.ux.menu.IconMenu.js"></script>
    <script type="text/javascript" src="recordform/js/Ext.ux.grid.RowActions.js"></script>
    <script type="text/javascript" src="recordform/js/Ext.ux.grid.RecordForm.js"></script>
    <script type="text/javascript" src="recordform/js/Ext.ux.form.DateTime.js"></script>
    <script type="text/javascript" src="recordform/js/Example.Grid.js"></script>
    <script type="text/javascript" src="recordform/AddCompsForm.js"></script>
    <script type="text/javascript" src="recordform/AddStatLoc.js"></script>

    <link rel="stylesheet" type="text/css" href="headerbuttons.css">

    <title>Add Components</title>
</head>

<body onload="javascript:AddComponents();">

    <?php
    $title = "Add Components";
    include "header.php";
    ?>
    <div id="wrap">
        <form action='AddComponents.php' method='post' name="addComponents" id="addComponents">
            <input type=hidden name="submitornot" id="submitornot" value=0>
            <div id="spe">
                <div id="west-content" class="x-hidden">
                    <div id="Description">
                    </div>
                </div>
            </div>
        </form>
    </div>
    <?php
    include "footer.php";
    ?>
</body>

</html>
