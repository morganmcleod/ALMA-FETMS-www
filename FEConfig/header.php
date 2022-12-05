<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require(site_get_config_main());
require_once($site_dbConnect);
if (!isset($home))
    $home = $url_root . "FEConfig/FEHome.php";
if (!isset($showConfig))
    $showConfig = $url_root . "FEConfig/ShowFEConfig.php";

// URL for bug reporting:
$bugsTo = "http://jira.alma.cl/browse/FETMS/";
?>
<div id="header">
    <div id="header_inside">
        <h1>
            <span>
                <?php
                echo $title;
                ?>
            </span>
        </h1>
        <div class="menu_nav">
            <table>
                <tr>
                    <td>
                        <a href=<?php echo "'{$home}'" ?> class="button gray bigrounded">
                            <span>Home</span>
                        </a>
                    </td>
                    <?php
                    if (!$FETMS_CCA_MODE && isset($feconfig) && $feconfig && isset($fesn) && $fesn) {
                    ?>
                        <td>
                            <a href=<?php echo "'{$showConfig}?key={$feconfig}&fc={$fc}'" ?> class="button gray bigrounded">
                                <span>Front End <?php echo $fesn; ?></span>
                            </a>
                        </td>
                    <?php
                    }
                    ?>
                    <td>
                        <a href=<?php echo "'$bugsTo'" ?> target="_blank" class="button gray bigrounded">
                            <span>Bugs</span>
                        </a>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
<?php
site_warnProductionDb($dbname);
?>