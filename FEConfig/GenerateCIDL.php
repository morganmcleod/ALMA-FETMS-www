<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.cidl_tcpdf.php');
require_once($site_classes . '/class.frontend.php');
require_once('HelperFunctions.php');
require_once($site_config_main);

$fecfg = $_REQUEST['fecfg'];
$fc = $_REQUEST['fc'];
$PreparedBy = $_REQUEST['pb'];
$ALMADocNum = $_REQUEST['adn'];

$fe = new FrontEnd();
$fe->Initialize_FrontEnd_FromConfig($fecfg, $fc, FrontEnd::INIT_NONE);

//Create progress update ini file
$url = '"' . $rootdir_url . "FEConfig/ShowFEConfig.php?key=$fecfg&fc=$fc" . '"';
$progressfile = CreateProgressFile('Generating CIDL Report','www.nrao.edu',$url,$fecfg . "_" . $fc);

if (file_exists($progressfile)){
    //unlink($progressfile);
}
WriteINI($progressfile,'progress',1);
WriteINI($progressfile,'message','Starting...');

$title = 'ALMA FRONT END ASSEMBLY CONFIGURATION SUMMARY';

$pdf = new CIDL_PDF();
$pdf->margin_left = 20;
$pdf->margin_top = 10;
$pdf->SetTitle($title);
$pdf->SetAuthor($PreparedBy);
$pdf->SN = $fe->GetValue('SN');
$pdf->ALMA_docnum = $ALMADocNum;
$pdf->PreparedBy = $PreparedBy;
$pdf->DocStatus = "Released";

WriteINI($progressfile,'progress',10);
WriteINI($progressfile,'message','Introduction pages...');

$pdf->FrontPage();
$pdf->ChangeRecord();
$pdf->AddPage();
$pdf->IntroPage();
$pdf->AbbreviationsPage();

WriteINI($progressfile,'progress',50);
WriteINI($progressfile,'message','Adding component tables...');
$pdf->AddComponentTables();


WriteINI($progressfile,'progress',70);
WriteINI($progressfile,'message','TOC, Docs, etc...');
$pdf->AddPage_PAIPASReports();
$pdf->AddPage_RFW();
$pdf->AddPage_NonConformances();
$pdf->AddPage_CARNotices();
$pdf->AddPage_OtherDocs();
$pdf->TableOfContents2();
$pdf->DeletePage(4);

WriteINI($progressfile,'progress',100);
WriteINI($progressfile,'message','Generating output pdf...');

$pdf->Output($ALMADocNum . ".pdf", 'I');
echo "<meta http-equiv='refresh' content='10;URL=$url'>";
WriteINI($f,'progress',100);
WriteINI($f,'message','Finished.');

?>