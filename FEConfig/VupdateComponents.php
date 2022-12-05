<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <link rel="stylesheet" type="text/css" href="Cartstyle.css">
    <link rel="stylesheet" type="text/css" href="buttons.css">
    <link href="images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
    <script>
        function checkComp(comptype) {
            if (comptype == 11) {
                //location.href="https://safe.nrao.edu/php/ntc/wca/wca.php";
            } else if (comptype == 20) {
                //location.href="https://safe.nrao.edu/php/ntc/cca/cca.php";
            }
        }
    </script>
</head>

<body>

    <?php
    require_once(dirname(__FILE__) . '/../SiteConfig.php');
    require_once($site_classes . '/class.fecomponent.php');
    require_once('jsFunctions.php');

    $title = "Components";
    include "header.php";

    $c = new FEComponent(NULL, $comp_max_key, NULL, $fc);

    ?>

    <form action='CupdateComponents.php' method='post' name="addComponents" id="addComponents" onsubmit="CompValidate(2)">

        <div id="wrap2">
            <div id="sidebar2">

                <!--
<input type=button name="Update" value="Update" onClick="CompValidate(2);">
        &nbsp;&nbsp;<input type=reset name="reset" value="Reset">
-->


                <table>
                    <tr>
                        <td>
                            <input type=submit name="submit" class="button blue2 bigrounded value=" Submit" style="width:120px">
                            <br>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type=reset name="reset" value="Reset" class="button blue2 bigrounded style=" width:120px">
                        </td>
                    </tr>
                </table>
            </div>
            <div id="maincontent">

                <div id="wrapper">

                    <div id="compform" style="width:700px">
                        <input type=hidden name="submitornot" id="submitornot" value=0>
                        <input type=hidden name="maxkey" id="maxkey" value="<?php echo $c->keyId; ?>">
                        <input type=hidden name="comptype" id="comptype" value="<?php echo $c->fkFE_ComponentType; ?>">
                        <input type=hidden name="facility" id="facility" value="<?php echo $c->keyFacility; ?>">
                        <table id='table2'>
                            <tr class='alt'>
                                <th colspan='2'>
                                    Component Information
                                </th>
                            </tr>
                            <tr>
                                <th>Serial Number:</th>
                                <td><b><?php echo $c->SN; ?></b></tdh>
                            </tr>
                            <tr>
                                <th>Component Type:</th>
                                <td><b><?php echo $c->ComponentType; ?></b></td>
                            </tr>
                            <tr>
                                <th>ESN1:</th>
                                <td><input type=text name="esn1" id="esn1" value=<?php echo $esn1; ?>></td>
                            </tr>
                            <tr>
                                <th>ESN2:</th>
                                <td><input type=text name="esn2" id="esn2" value=<?php echo $esn2; ?>></td>
                            </tr>
                            <tr>
                                <th>Component Description:</th>
                                <td><textarea rows=3 cols=40 name='descr' id='descr'><?php echo $c->Description; ?></textarea></td>
                            </tr>
                            <tr>
                                <th>Documentation:</th>
                                <td><textarea rows=3 cols=40 name="docs" id="docs" value="<?php echo $c->Docs; ?>"></textarea></td>
                            </tr>
                            <tr>
                                <th>Band:</th>
                                <td><input type=text name='band' id='band' value=<?php echo $c->Band; ?>>
                                </td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td><Select name='stat' id='stat'>
                                        <option></option><?php echo $status_block; ?>
                                    </Select></td>
                            </tr>
                            <tr>
                                <th>Location:</th>
                                <td><Select name='loc' id='loc'>
                                        <option></option><?php echo $location_block; ?>
                                    </Select></td>
                            </tr>
                            <tr>
                                <th>Updated By:</th>
                                <td><Select name='updatedby' id='updatedby'>
                                        <option></option>
                                        <option value="DN">DN</option>
                                        <option value="JC">JC</option>
                                        <option value="JE">JE</option>
                                        <option value="MM">MM</option>
                                    </Select></td>
                            </tr>
                            <tr>
                                <th>Notes:</th>
                                <td><textarea rows=3 cols=40 name='notes' id='notes'></textarea></td>
                            </tr>
                        </table>
                    </div>

                    <input type="hidden" name="fc" value="<?php echo $c->keyFacility; ?>">

                </div>

            </div>
        </div>
        <!--
<div id='nocontent'>
        <input type=button name="Update" value="Update" onClick="CompValidate(2);">
        &nbsp;&nbsp;<input type=reset name="reset" value="Reset">
        </div>
        -->
    </form>
    <?php
    include "footer.php";
    ?>
</body>

</html>