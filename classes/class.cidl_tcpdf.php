<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/tcpdf/tcpdf.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_dbConnect);

class CIDL_PDF extends TCPDF
{
    var $datalogger; //object class.logger.php
    var $sw_version;
    var $SN;
    var $FEConfiguration;
    var $FE_keyId;
    var $ALMA_docnum;
    var $MakeDate;
    var $PreparedBy;
    var $DocStatus;

    var $margin_left;
    var $margin_top;

    var $table_widths; //array of table widths
    var $total_table_width;

    //var $Y_current;
    var $tablecount;
    var $Y_offset_table;
    var $dbconnection;
    var $font_type;

    function FrontPage()
    {
        $this->sw_version = "1.0.13";
        $this->font_type = 'Times';
        $this->tablecount = 1;
        $this->margin_top = 50;
        $this->table_widths[0] = 10;
        $this->table_widths[1] = 20;
        $this->table_widths[2] = 45;
        $this->table_widths[3] = 10;
        $this->table_widths[4] = 30;
        $this->table_widths[5] = 30;
        $this->table_widths[6] = 20;
        $this->table_widths[7] = 20;
        $this->table_widths[8] = 80;

        $this->table_widths_doc[0] = 10;
        $this->table_widths_doc[1] = 85;
        $this->table_widths_doc[2] = 70;
        $this->table_widths_doc[3] = 30;
        $this->table_widths_doc[4] = 20;
        $this->table_widths_doc[5] = 15;

        $this->total_table_width = 0;
        for ($i= 0; $i< count($this->table_widths); $i++){
            $this->total_table_width += $this->table_widths[$i];
        }
        require(site_get_config_main());
        $this->dbconnection = site_getDbConnection();


        $qcfg = "SELECT MAX(FE_Config.keyFEConfig), Front_Ends.keyFrontEnds
                FROM FE_Config, Front_Ends
                WHERE Front_Ends.SN = $this->SN
                AND FE_Config.fkFront_Ends = Front_Ends.keyFrontEnds
                GROUP BY FE_Config.keyFEConfig DESC LIMIT 1;";
        $rcfg = @mysql_query($qcfg,$this->dbconnection);
        $this->FEConfiguration = @mysql_result($rcfg,0,0);
        $this->FE_keyId        = @mysql_result($rcfg,0,1);

        $this->AliasNbPages();
        $this->AddPage();
        $this->MakeDate = Date('Y-m-d');
        global $title;
        $this->Image('../classes/images/alma1.PNG',20,10,80);
        $this->Ln(70);
        $fill = false;
        // Times bold 15
        $this->SetFont('Times','B',18);

        // Calculate width of title and position
        $w = $this->GetStringWidth('ALMA FRONT END ASSEMBLY')+6;
        $this->SetX((210-$w)/2);
        // Colors of frame, background and text
        $this->SetDrawColor(180,180,0);
        $this->SetFillColor(180,180,180);
        $this->SetTextColor(0,0,0);
        // Thickness of frame (1 mm)
        $this->SetLineWidth(1);
        // Title
        $this->Cell($w,9,'ALMA FRONT END ASSEMBLY',0,0,'C',$fill);
        $this->Ln(10);

        $w = $this->GetStringWidth('CONFIGURATION SUMMARY')+6;
        $this->SetX((210-$w)/2);
        $this->Cell($w,9,'CONFIGURATION SUMMARY',0,0,'C',$fill);
        $this->Ln(10);

        $this->SetFont('Times','',12);
        $textline = 'SN-' . $this->SN;
        $w = $this->GetStringWidth($textline)+6;
        $this->SetX((210-$w)/2);
        $this->Cell($w,9,$textline,0,0,'A',$fill);
        $this->Ln(10);

        $textline = $this->ALMA_docnum;
        $w = $this->GetStringWidth($textline)+6;
        $this->SetX((210-$w)/2);
        $this->Cell($w,9,$textline,0,0,'C',$fill);
        $this->Ln(10);

        $textline = "Status: $this->DocStatus";
        $w = $this->GetStringWidth($textline)+6;
        $this->SetX((210-$w)/2);
        $this->Cell($w,9,$textline,0,0,'C',$fill);
        $this->Ln(10);

        $textline = $this->MakeDate;
        $w = $this->GetStringWidth($textline)+6;
        $this->SetX((210-$w)/2);
        $this->Cell($w,9,$textline,0,0,'C',$fill);
        $this->Ln(20);

        $this->FrontPageTable();
    }

    function AddHeader($page_orientation = 'P', $AddNewPage = 1, $PageNo = ""){
        if ($PageNo == ""){
            $PageNo = $this->page;
        }

        if ($AddNewPage == 1){
            $this->AddPage($page_orientation);
        }
        // Colors, line width and bold font
        $this->SetFillColor(0,0,255);
        $this->SetTextColor(0);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont('','B', 10);

        $this->Image('../classes/images/alma2.PNG',20,10,21);
        $this->SetX($this->margin_left);
        $this->Cell(21,31.5,'',0,0,'C',false);
        $this->MultiCell(72,8,"ALMA Project\nALMA Front End Assembly \nSN-$this->SN \nConfiguration Summary\n\n\n[config. number: $this->FEConfiguration] \n",1,'C',false);
        $this->SetY(10);
        $this->SetX($this->margin_left + 93);
        $this->SetFont('','', 10);

        $totpages = $this->getAliasNbPages();

        $cellstring  = "Doc#:    $this->ALMA_docnum\n\n";
        $cellstring .= "Date:    $this->MakeDate\n\n";
        $cellstring .= "Status:  $this->DocStatus\n\n";
        $cellstring .= "Page:    $PageNo of " . $totpages . " \n";


        $this->MultiCell(72,8,$cellstring,1,'L');
        $this->Ln();
    }

    function Footer()
    {
        /*
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Times italic 8
        $this->SetFont('Times','I',8);
        // Text color in gray
        $this->SetTextColor(128);
        // Page number
        $this->Cell(0,10,'Page '.$this->PageNo(),0,0,'C');
    */
    }

    function FrontPageTable(){

        // Colors, line width and bold font
        //$this->SetFillColor(135,206,250);
        $this->SetFillColor(176,196,222);
        $this->SetTextColor(0);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont('','B');
        // Header

        $this->SetX(35);
        $this->Cell(70,7,'Prepared By:',1,0,'C',true);
        $this->Cell(40,7,'Organization',1,0,'C',true);
        $this->Cell(40,7,'Date',1,0,'C',true);
        $this->Ln();
        $this->SetX(35);
        $this->SetTextColor(0,0,0);
        $this->SetFont('');
        $this->Cell(70,7,$this->PreparedBy,1,0,'C',false);
        $this->Cell(40,7,'NRAO',1,0,'C',false);
        $this->Cell(40,7,$this->MakeDate,1,0,'C',false);
        $this->Ln();


        $this->SetX(35);
        $this->SetTextColor(0);
        $this->SetFont('','B');
        $this->Cell(70,7,'FEIC WP Manager Approval:',1,0,'C',true);
        $this->Cell(40,7,'Organization',1,0,'C',true);
        $this->Cell(40,7,'Date',1,0,'C',true);
        $this->Ln();
        $this->SetX(35);
        $this->SetFont('');
        $this->Cell(70,27,'',1,0,'C',false);
        $this->Cell(40,27,'',1,0,'C',false);
        $this->Cell(40,27,'',1,0,'C',false);
        $this->Ln();
        $this->SetX(35);
        $this->SetTextColor(0);
        $this->SetFont('','B');
        $this->Cell(70,7,'FE System Engineering Approval:',1,0,'C',true);
        $this->Cell(40,7,'Organization',1,0,'C',true);
        $this->Cell(40,7,'Date',1,0,'C',true);
        $this->Ln();
        $this->SetX(35);
        $this->SetFont('');
        $this->Cell(70,27,'',1,0,'C',false);
        $this->Cell(40,27,'',1,0,'C',false);
        $this->Cell(40,27,'',1,0,'C',false);
        $this->Ln();
        $this->SetX(35);
        $this->SetTextColor(0);
        $this->SetFont('','B');
        $this->Cell(70,7,'FE IPT Lead Approval:',1,0,'C',true);
        $this->Cell(40,7,'Organization',1,0,'C',true);
        $this->Cell(40,7,'Date',1,0,'C',true);
        $this->Ln();
        $this->SetX(35);
        $this->SetFont('');
        $this->Cell(70,27,'',1,0,'C',false);
        $this->Cell(40,27,'',1,0,'C',false);
        $this->Cell(40,27,'',1,0,'C',false);
        $this->Ln();

        // Color and font restoration
        $this->SetFillColor(224,235,255);
        $this->SetTextColor(255);
        $this->SetFont('');
        // Data
        $fill = false;
    }

    function ChangeRecord(){
        $this->AddHeader();
        //$y_offset = 70;


        $this->SetX(100);
        //$this->Ln(20);
        $this->SetFont('Times','B',16);
        $this->SetTextColor(0,0,0);

        $this->SetY($this->margin_top-5);
        $this->SetX(100);
        $this->Cell(0.5,-10,'Change Record',0,0,'C');
        $this->SetY($this->margin_top + 10);
        $this->SetX(100);
        //$this->Ln(10);

        $this->SetFillColor(176,196,222);
        $this->SetTextColor(0);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont('','B',10);
        // Header

        $this->SetX($this->margin_left);
        $this->Cell(20,7,'Revision',1,0,'C',true);
        $this->Cell(30,7,'Date',1,0,'C',true);
        $this->Cell(40,7,'Affected Pages',1,0,'C',true);
        $this->Cell(20,7,'CRE #',1,0,'C',true);
        $this->Cell(54,7,'Reason/Initiation/Remarks',1,0,'C',true);
        $this->Ln();
        $this->SetX(35);
        $this->SetTextColor(0,0,0);
        $this->SetFont('');

        //Populate Change Record from database
        $qcr = "SELECT * FROM RevHistory WHERE fkFront_Ends = $this->FE_keyId
                ORDER BY Date ASC, keyRevHistory ASC;";
        $rcr = @mysql_query($qcr,$this->dbconnection);

        while ($row_rcr=@mysql_fetch_array($rcr)){
            $this->SetX($this->margin_left);
            $this->Cell(20,7,$row_rcr['Revision'],1,0,'C');
            $this->Cell(30,7,$row_rcr['Date'],1,0,'C');
            $this->Cell(40,7,$row_rcr['AffectedPages'],1,0,'C');
            $this->Cell(20,7,'',1,0,'C');
            $this->Cell(54,7,$row_rcr['Remarks'],1,0,'C');
            $this->Ln();
        }
    }

    function TableOfContents(){
        $y_offset = 80;
        $pagenum_offset = 180;
        $this->AddHeader();
        $this->SetY($this->margin_top);
        $this->SetX(100);
        //$this->Ln(20);
        $this->SetFont('Times','B',16);
        $this->SetTextColor(0,0,0);
        $this->Cell(0.5,-40,'TABLE OF CONTENTS',0,0,'C');
        //$this->Ln(2);

        $this->SetFont('Times','B',12);


        $this->SetY($y_offset - 10);
        $this->SetX($this->margin_left);
        $this->Cell(0,0,'1     INTRODUCTION',0,0,'A');
        $this->SetX($pagenum_offset);
        $this->Cell(0,0,'4',0,0,'B');
        $this->Ln(8);

        $this->SetX($this->margin_left);
        $this->Cell(0,0,'1.1     Purpose',0,0,'A');
        $this->SetX($pagenum_offset);
        $this->Cell(0,0,'4',0,0,'B');
        $this->Ln(8);

        $this->SetX($this->margin_left);
        $this->Cell(0,0,'1.2     Scope',0,0,'A');
        $this->SetX($pagenum_offset);
        $this->Cell(0,0,'4',0,0,'B');
        $this->Ln(8);

        $this->SetX($this->margin_left);
        $this->Cell(0,0,'1.3     Applicable and Reference Documents',0,0,'A');
        $this->SetX($pagenum_offset);
        $this->Cell(0,0,'4',0,0,'B');
        $this->Ln(5);

        $this->SetX($this->margin_left + 5);
        $this->SetFont('','',12);
        $this->Cell(0,0,'1.3.1     Applicable Documents List (ADL)',0,0,'A');
        $this->SetX($pagenum_offset);
        $this->Cell(0,0,'4',0,0,'B');
        $this->Ln(5);

        $this->SetX($this->margin_left + 5);
        $this->SetFont('','',12);
        $this->Cell(0,0,'1.3.2     Reference Documents List (RDL)',0,0,'A');
        $this->SetX($pagenum_offset);
        $this->Cell(0,0,'4',0,0,'B');
        $this->Ln(8);

        $this->SetFont('','B',12);
        $this->SetX($this->margin_left);
        $this->Cell(0,0,'1.4     Abbreviations and Acronyms',0,0,'A');
        $this->SetX($pagenum_offset);
        $this->Cell(0,0,'4',0,0,'B');
        $this->Ln(8);

        $this->SetX($this->margin_left);
        $this->Cell(0,0,"2     AS BUILT CONFIGURATION - FE ASSEMBLY SN-$this->SN",0,0,'A');
        $this->SetX($pagenum_offset);
        $this->Cell(0,0,'6',0,0,'B');
        $this->Ln(8);

        $this->SetX($this->margin_left);
        $this->Cell(0,0,"2.1     List of Components",0,0,'A');
        $this->SetX($pagenum_offset);
        $this->Cell(0,0,'6',0,0,'B');
        $this->Ln(8);

        $this->SetX($this->margin_left);
        $this->Cell(0,0,"2.2     PAI & PAS Report",0,0,'A');
        $this->SetX($pagenum_offset-2);
        //Note make this page number dynamic
        $this->Cell(0,0,'17',0,0,'B');
        $this->Ln(8);

        $this->SetX($this->margin_left);
        $this->Cell(0,0,"2.3     Requests for Waiver",0,0,'A');
        $this->SetX($pagenum_offset-2);
        //Note make this page number dynamic
        $this->Cell(0,0,'19',0,0,'B');
        $this->Ln(5);

        $this->SetX($this->margin_left + 5);
        $this->SetFont('','',12);
        $this->Cell(0,0,"2.3.1     FE Assembly SN-$this->SN Requests for Waiver",0,0,'A');
        $this->SetX($pagenum_offset-2);
        //Note make this page number dynamic
        $this->Cell(0,0,'19',0,0,'B');
        $this->Ln(5);

        $this->SetX($this->margin_left + 5);
        $this->SetFont('','',12);
        $this->Cell(0,0,"2.3.2     Other Requests for Waiver Related to FE Assembly SN-$this->SN",0,0,'A');
        $this->SetX($pagenum_offset-2);
        //Note make this page number dynamic
        $this->Cell(0,0,'19',0,0,'B');
        $this->Ln(8);
    }


    function TableOfContents2(){
        $this->addTOCPage(3);
        $this->AddHeader('P',0,2);
        // write the TOC title
        $this->SetFont('times', 'B', 16);
        $this->SetY($this->margin_top);
        $this->MultiCell(0, 0, 'Table Of Contents', 0, 'C', 0, 1, '', '', true, 0);
        $this->Ln();

        //$this->SetFont('dejavusans', '', 12);

        // add a simple Table Of Content at first page
        // (check the example n. 59 for the HTML version)
        $this->addTOC(3, 'courier', '.', 'INDEX', 'B', array(128,0,0));

        // end of TOC page
        $this->endTOCPage();
    }

    function IntroPage(){
        $this->AddHeader();
        $y_offset = 60;


        $this->SetY($y_offset);
        $this->SetX($this->margin_left);
        $this->SetFont('Times','B',10);
        $this->Cell(0,0,"1     INTRODUCTION",0,0,'A');
        $this->Bookmark('1     INTRODUCTION', 1, 0, '', '', array(128,0,0));
        $this->Ln(8);

        //PURPOSE
        $this->SetFont('Times','B',10);
        $this->SetX($this->margin_left);
        $this->Cell(0,0,"1.1     Purpose",0,0,'A');
        $this->Bookmark('1.1     Purpose', 1, 0, '', '', array(128,0,0));
        $this->Ln(8);
        $cellstring =
        "This document summarises the “as-built” configuration overview for FE Assembly SN-$this->SN with respect to \n
    the identification of serial numbers, etc. of the sub-assemblies and components in this FE Assembly at\n
    some point in time as defined in the change record on page 2 of this document.\n
    Additional information of version or revision numbers for configuration items as well as information of\n
    possible upgrades is included.
    ";
        $this->SetX($this->margin_left);
        $this->SetFont('Times','',10);
        $this->MultiCell(0,1,$cellstring,0,'A',false);
        $this->Ln(8);

        //SCOPE
        $this->SetFont('Times','B',10);
        $this->SetX($this->margin_left);
        $this->Cell(0,0,"1.2     Scope",0,0,'A');
        $this->Bookmark('1.2     Scope', 1, 0, '', '', array(128,0,0));
        $this->Ln(8);
        $cellstring =
        "This document applies to FE Assembly SN-$this->SN.\n
    The front end configuraion number was $this->FEConfiguration.\n
    Generated using class.cidl_tcpdf.php version $this->sw_version.
    ";
        $this->SetX($this->margin_left);
        $this->SetFont('Times','',10);
        $this->MultiCell(0,2,$cellstring,0,'A',false);
        $this->Ln(8);

        //APPLICABLE DOCUMENTS
        $this->SetFont('Times','B',10);
        $this->SetX($this->margin_left);
        $this->Cell(0,0,"1.3     Applicable and Reference Documents",0,0,'A');
        $this->Bookmark('1.3     Applicable and Reference Documents', 1, 0, '', '', array(128,0,0));
        $this->Ln(8);

        $this->SetFont('Times','BI',10);
        $this->SetX($this->margin_left);
        $this->Cell(0,0,"1.3.1     Applicable Documents List (ADL)",0,0,'A');
        $this->Bookmark('1.3.1     Applicable Documents List (ADL)', 1, 0, '', '', array(128,0,0));

        $this->Ln(8);

        $cellstring =
        "The following documents are part of this document to the extent specified herein.  If not explicitly stated
        \ndifferently, the latest issue of the document is valid.";
        $this->SetX($this->margin_left);
        $this->SetFont('Times','',10);
        $this->SetX($this->margin_left);
        $this->MultiCell(0,2,$cellstring,0,'A',false);
        $this->Ln(2);

        //ADL TABLE
        $this->SetFillColor(176,196,222);
        $this->SetTextColor(0);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont('','B');
        $this->SetX($this->margin_left);
        $this->Cell(20,7,'Reference',1,0,'C',true);
        $this->Cell(80,7,'Document Title',1,0,'C',true);
        $this->Cell(50,7,'Document ID',1,0,'C',true);
        $this->Cell(15,7,'Status',1,0,'C',true);
        $this->Ln();
        $this->SetX($this->margin_left);
        $this->SetFont('','',10);
        $this->SetTextColor(0,0,0);
        $this->Cell(20,7,'[AD1]',1,0,'C');
        $this->SetFont('','',8);
        $this->Cell(80,7,'ALMA Front End Assembly Configured Items Data List',1,0,'C');
        $this->Cell(50,7,'FEND-40.00.00.00-004-A-LIS',1,0,'C');
        $this->Cell(15,7,'Draft',1,0,'C');
        $this->Ln(16);


        //REFERENCE DOCUMENTS
        $this->SetFont('Times','BI',10);
        $this->SetX($this->margin_left);
        $this->Cell(0,0,"1.3.2     Reference Documents List (RDL)",0,0,'A');
        $this->Bookmark('1.3.2     Reference Documents List (RDL)', 1, 0, '', '', array(128,0,0));
        $this->Ln(8);

        $cellstring =
        "The following documents are part of this document to the extent specified herein.  If not explicitly stated
        \ndifferently, the latest issue of the document is valid.";
        $this->SetX($this->margin_left);
        $this->SetFont('Times','',10);
        $this->SetX($this->margin_left);
        $this->MultiCell(0,2,$cellstring,0,'A',false);
        $this->Ln(2);

        //RDL TABLE
        $this->SetFillColor(176,196,222);
        $this->SetTextColor(0);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont('','B');
        $this->SetX($this->margin_left);
        $this->Cell(20,7,'Reference',1,0,'C',true);
        $this->Cell(80,7,'Document Title',1,0,'C',true);
        $this->Cell(50,7,'Document ID',1,0,'C',true);
        $this->Cell(15,7,'Status',1,0,'C',true);
        $this->Ln();
        $this->SetX($this->margin_left);
        $this->SetFont('','',10);
        $this->SetTextColor(0,0,0);
        $this->Cell(20,7,'[RD1]',1,0,'C');
        $this->SetFont('','',8);

        $this->Cell(80,7,'ALMA Product Assurance Requirements',1,0,'A');

        $url = "http://edm.alma.cl/forums/alma/dispatch.cgi/documents/docProfile/100649/d20040810200756/No/t100649.htm";
        $html = '<a href="'. $url . '">ALMA-80.11.00.00-001-D-GEN</a>';
        //$this->writeHTMLCell(50, '', '', '', $html, 0, 0, 0, false, 'C', false);

        $this->Cell(50,7,'ALMA-80.11.00.00-001-D-GEN',1,0,'A');
        $this->Cell(15,7,'Released',1,0,'A');
        $this->Ln();

        $this->SetX($this->margin_left);
        $this->SetFont('','',10);
        $this->SetTextColor(0,0,0);
        $this->Cell(20,7,'[RD2]',1,0,'C');
        $this->SetFont('','',8);
        $this->Cell(80,7,'ALMA Reviews Definitions, Guidelines and Procedure',1,0,'A');

        $url = "http://edm.alma.cl/forums/alma/dispatch.cgi/documents/docProfile/100650/d20060901153648/No/t100650.htm";
        $html = '<a href="'. $url . '">ALMA-80.09.00.00-001-D-PLA</a>';
        //$this->writeHTMLCell(50, '', '', '', $html, 0, 0, 0, false, 'C', false);

        $this->Cell(50,7,'ALMA-80.09.00.00-001-D-PLA',1,0,'A');
        $this->Cell(15,7,'Released',1,0,'A');
        $this->Ln();

        $this->SetX($this->margin_left);
        $this->SetFont('','',10);
        $this->SetTextColor(0,0,0);
        $this->Cell(20,7,'[RD3]',1,0,'C');
        $this->SetFont('','',8);
        $this->Cell(80,7,'ALMA Documentation Standards',1,0,'A');

        $url = "http://edm.alma.cl/forums/alma/dispatch.cgi/documents/docProfile/100650/d20060901153648/No/t100650.htm";
        $html = '<a href="'. $url . '" target = "blank">ALMA-80.02.00.00-003-G-STD</a>';
        //$this->writeHTMLCell(50, '', '', '', $html, 0, 0, 0, false, 'C', false);

        $this->Cell(50,7,'ALMA-80.02.00.00-003-G-STD',1,0,'A');
        $this->Cell(15,7,'Released',1,0,'A');
        $this->Ln();

        $this->SetX($this->margin_left);
        $this->SetFont('','',10);
        $this->SetTextColor(0,0,0);
        $this->Cell(20,7,'[RD4]',1,0,'C');
        $this->SetFont('','',8);
        $this->Cell(80,7,'ALMA Acronyms and Abbreviations List',1,0,'A');

        $url = "http://edm.alma.cl/forums/alma/dispatch.cgi/docsyseng/docProfile/100417/d20051019125642/No/t100417.htm";
        $html = '<a href="'. $url . '">ALMA-80.02.00.00-004-A-LIS</a>';
        //$this->writeHTMLCell(50, '', '', '', $html, 0, 0, 0, false, 'C', false);
        //$this->MultiCell(50,7,'',1,'C',0);

        $this->Cell(50,7,'ALMA-80.02.00.00-004-A-LIS',1,0,'A');
        $this->Cell(15,7,'Draft',1,0,'A');
        $this->Ln();

    }


    function AbbreviationsPage(){
        $this->AddHeader();

        $this->SetFont('Times','B',10);
        $this->SetX($this->margin_left);
        $this->SetY($this->margin_top);
        $this->Cell(0,0,"1.4     Abbreviations and Acronyms",0,0,'A');
        $this->Bookmark('1.4     Abbreviations and Acronyms', 1, 0, '', '', array(128,0,0));
        $this->Ln(8);
        $cellstring =
        "A limited set of basic acronyms used in this document is given below.  A comprehensive list of\n
        abbreviations and acronyms is available in [RD4].  An acronym search tool is available on ALMA EDM.
        \n";

        $cellstring =
        "ADL\n
    ALMA\n
    AOS\n
    CCA\n
    CIDL\n
    CMMS\n
    EDM\n
    ESN\n
    FE\n
    FEIC\n
    N/A\n
    OSF\n
    PT\n
    RDL\n
    SN\n
    SOW\n";

        $this->SetY(57);
        $this->SetX($this->margin_left);
        $this->SetFont('Times','',10);
        $this->MultiCell(25,2,$cellstring,0,'A',false);

        $cellstring =
        "Applicable Documents List\n
    Atacama Large Millimeter/Sub-millimeter Array\n
    Array Operations Site\n
    Cold Cartridge Assembly\n
    Configured Items Data List\n
    Computerised Maintenance Management System\n
    Electronic Document Management\n
    Electronic Serial Number\n
    Front End\n
    Front End Integration Centre\n
    Not Applicable\n
    Operational Support Facility\n
    Product Tree\n
    Reference Documents List\n
    Serial Number\n
    Statement of Work\n";


        $this->SetY(57);
        $this->SetX($this->margin_left + 25);
        $this->SetFont('Times','',10);
        $this->MultiCell(100,1,$cellstring,0,'A',false);
    }

    function AddComponentTables(){
        $this->AddHeader('L');
        $this->SetFont('Times','B',10);
        $this->SetY($this->margin_top);
        $this->Cell(0,0,"2     As Built Configuration - FE Assembly SN- $this->SN",0,0,'A');
        $this->Bookmark("2     As Built Configuration - FE Assembly SN- $this->SN", 0, 0, '', '', array(128,0,0));
        $this->Ln();
        $this->Cell(0,0,"2.1     List of Components",0,0,'A');
        $this->Bookmark('2.1     List of Components', 1, 0, '', '', array(128,0,0));
        $this->Ln();
        $this->SetY($this->margin_top + 40);
        $this->AddComponentTableHeaderRow();
        $this->Table_Components();
    }

    function AddPage_PAIPASReports(){
        $this->AddHeader('L');

        $this->SetFont('Times','B',10);
        $this->SetY($this->margin_top);
        $this->Cell(0,0,"2.2     PAI & PAS Reports",0,0,'A');
        $this->Bookmark("2.2     PAI & PAS Reports", 0, 0, '', '', array(128,0,0));
        $this->SetFillColor(176,196,222);
        $this->SetTextColor(0);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont('','B');
        // Header

        $this->SetY($this->margin_top + 30);
        $this->SetX($this->margin_left);
        $this->AddDocTableHeaderRow();
        $this->Table_Documents(217);
    }

    function AddPage_CARNotices(){
        $this->AddHeader('L');

        $this->SetFont('Times','B',10);
        $this->SetY($this->margin_top);
        $this->Cell(0,0,"2.5     CAR Notices",0,0,'A');
        $this->Bookmark("2.5     CAR Notices", 0, 0, '', '', array(128,0,0));
        $this->SetFillColor(176,196,222);
        $this->SetTextColor(0);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont('','B');
        // Header

        $this->SetY($this->margin_top + 30);
        $this->SetX($this->margin_left);
        $this->AddDocTableHeaderRow();
        $this->Table_Documents(222);
    }

    function AddPage_RFW(){
        $this->AddHeader('L');

        $this->SetFont('Times','B',10);
        $this->SetY($this->margin_top);
        $this->Cell(0,0,"2.3     Requests for Waiver",0,0,'A');
        $this->Bookmark("2.3     Requests for Waiver", 0, 0, '', '', array(128,0,0));
        $this->Ln();
        $this->SetFillColor(176,196,222);
        $this->SetTextColor(0);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont('','B');
        // Header
        $this->SetY($this->margin_top + 30);
        $this->SetX($this->margin_left);
        $this->AddDocTableHeaderRow();
        $this->Table_Documents(218);
    }

    function AddPage_NonConformances(){
        $this->AddHeader('L');

        $this->SetFont('Times','B',10);
        $this->SetY($this->margin_top);
        $this->Cell(0,0,"2.4     FE Assembly SN-$this->SN Non-Conformances",0,0,'A');
        $this->Bookmark("2.4     FE Assembly SN-$this->SN Non-Conformances", 0, 0, '', '', array(128,0,0));
        $this->SetFillColor(176,196,222);
        $this->SetTextColor(0);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont('','B');
        // Header
        $this->SetY($this->margin_top + 30);
        $this->SetX($this->margin_left);
        $this->AddDocTableHeaderRow();
        $this->Table_Documents(219);
    }
    function AddPage_OtherDocs(){
        $this->AddHeader('L');

        $this->SetFont('Times','B',10);
        $this->SetY($this->margin_top);
        $this->Cell(0,0,"2.6     FE Assembly SN-$this->SN Other Documents",0,0,'A');
        $this->Bookmark("2.6     FE Assembly SN-$this->SN Other Document", 0, 0, '', '', array(128,0,0));
        $this->SetFillColor(176,196,222);
        $this->SetTextColor(0);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont('','B');
        // Header
        $this->SetY($this->margin_top + 30);
        $this->SetX($this->margin_left);
        $this->AddDocTableHeaderRow();
        $this->Table_Documents(220);
    }

    function Table_Documents($DocType){
        $this->SetFillColor(176,196,222);
        $this->SetTextColor(0);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont($this->font_type,'B');

        $this->SetX($this->margin_left);
        $this->GetRowsFromDB_Doc($DocType, "");
        $this->Ln();

    }

    function Table_Components(){
        $this->SetFillColor(176,196,222);
        $this->SetTextColor(0);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont($this->font_type,'B');
        $this->SetX($this->margin_left);

        //Warm Optics
        $this->SetX($this->margin_left);
        $this->SetFont($this->font_type,'B');
        $this->Cell(265,7,'Warm Optics',1,0,'C',true);
        $this->Ln();

        for ($band = 0; $band <= 10; $band++){
            $bandval = $band;
            $addstring = " (Band $bandval)";
            if ($band == 0){
                $bandval = '';
                $addstring = '';
            }
            $this->GetRowFromDB(127, $band, $addstring);
            $this->GetRowFromDB(130, $band, $addstring);
            $this->GetRowFromDB(131, $band, $addstring);
            $this->GetRowFromDB(132, $band, $addstring);
        }

        //Cartridges
        $cryo_components = array(20,133,11,134,16);

        for ($band = 1; $band <= 10; $band++){
            if ($this->CheckCartridgeExists($band) == 1){
                $bandval = $band;
                $addstring = " (Band $bandval)";
                if ($band == 0){
                    $bandval = '';
                    $addstring = '';
                }

                $this->SetX($this->margin_left);
                $this->SetFont($this->font_type,'B');
                $this->Cell(265,7,"Band $band Cartridge",1,0,'C',true);
                $this->Ln();
                for ($i=0;$i< count($cryo_components); $i++){

                    if ($cryo_components[$i] != 133){
                        $this->GetRowFromDB($cryo_components[$i], $band, $addstring);
                    }
                    if ($cryo_components[$i] == 133){
                        $this->GetRowsFromDB($cryo_components[$i], $band, $addstring);
                    }
                }
            }
        }

        //Cryostat
        $this->SetX($this->margin_left);
        $this->SetFont($this->font_type,'B');
        $this->Cell(265,7,'Cryostat',1,0,'C',true);
        $this->Ln();

        $cryo_components = array(6, 135,136,137,138,139,140,141,142,143,144,145,146);

        for ($i=0;$i< count($cryo_components); $i++){
            for ($band = 0; $band <= 10; $band++){

                $bandval = $band;
                $addstring = " (Band $bandval)";
                if ($band == 0){
                    $bandval = '';
                    $addstring = '';
                }
                $this->GetRowFromDB($cryo_components[$i], $band, $addstring);
            }
        }

        //Vaccum Window
        for ($band = 0; $band <= 10; $band++){
            $bandval = $band;
            $addstring = " (Band $bandval)";
            if ($band == 0){
                $bandval = '';
                $addstring = '';
            }
            $this->GetRowFromDB(147, $band, $addstring);
        }

        //Vacuum blanking window
        for ($band = 0; $band <= 10; $band++){
            $bandval = $band;
            $addstring = " (Band $bandval)";
            if ($band == 0){
                $bandval = '';
                $addstring = '';
            }
            $this->GetRowFromDB(148, $band, $addstring);
        }

        //Cryostat Parts Not Attached Directly To Vessel
        $this->SetX($this->margin_left);
        $this->SetFont($this->font_type,'B');
        $this->Cell(265,7,'Cryostat Parts Not Attached Directly To Vessel',1,0,'C',true);
        $this->Ln();

        $cryo_components = array(149,150,151,152);
        for ($i=0;$i< count($cryo_components); $i++){
            for ($band = 0; $band <= 10; $band++){
                $bandval = $band;
                $addstring = " (Band $bandval)";
                if ($band == 0){
                    $bandval = '';
                    $addstring = '';
                }
                $this->GetRowFromDB($cryo_components[$i], $band, $addstring);
            }
        }

        //Cryostat M&C Subrack
        $this->SetX($this->margin_left);
        $this->SetFont('','B');
        $this->Cell(265,7,'Cryostat M&C Subrack',1,0,'C',true);
        $this->Ln();

        $cryo_components = array(153,154,155,156,157);
        for ($i=0;$i< count($cryo_components); $i++){
            for ($band = 0; $band <= 10; $band++){

                $bandval = $band;
                $addstring = " (Band $bandval)";
                if ($band == 0){
                    $bandval = '';
                    $addstring = '';
                }
                $this->GetRowFromDB($cryo_components[$i], $band, $addstring);
            }
        }

        //CPDS
        $this->SetX($this->margin_left);
        $this->SetFont('','B');
        $this->Cell(265,7,'CPDS Subrack',1,0,'C',true);
        $this->Ln();

        $cpds_components = array(4,158,159,160,161);
        for ($i=0;$i< count($cpds_components); $i++){
            for ($band = 0; $band <= 10; $band++){

                $bandval = $band;
                $addstring = " (Band $bandval)";
                if ($band == 0){
                    $bandval = '';
                    $addstring = '';
                }
                $this->GetRowFromDB($cpds_components[$i], $band, $addstring);
            }
        }

        //IF Switch System
        $this->SetX($this->margin_left);
        $this->SetFont('','B');
        $this->Cell(265,7,'IF Switch System',1,0,'C',true);
        $this->Ln();

        $if_components = array(129,168,162,163,43,169,181);
        for ($i=0;$i< count($if_components); $i++){

            for ($band = 0; $band <= 10; $band++){

                $bandval = $band;
                $addstring = " (Band $bandval)";
                if ($band == 0){
                    $bandval = '';
                    $addstring = '';
                }
                $this->GetRowFromDB($if_components[$i], $band, $addstring);
            }


            $this->SetX($this->margin_left);
            $this->SetFont('','B');
            $this->Ln();

            $if_components = array(43,163);
            for ($i=0;$i< count($if_components); $i++){

                for ($band = 0; $band <= 10; $band++){

                    $bandval = $band;
                    $addstring = " (Band $bandval)";
                    if ($band == 0){
                        $bandval = '';
                        $addstring = '';
                    }
                    $this->GetRowsFromDB($if_components[$i], $band, $addstring);
                }
            }
        }

        //FE M&C Subrack
        $this->SetX($this->margin_left);
        $this->SetFont('','B');
        $this->Cell(265,7,'FE M&C Subrack',1,0,'C',true);
        $this->Ln();

        $femc_components = array(164,10,96,165);
        for ($i=0;$i< count($femc_components); $i++){
            for ($band = 0; $band <= 10; $band++){

                $bandval = $band;
                $addstring = " (Band $bandval)";
                if ($band == 0){
                    $bandval = '';
                    $addstring = '';
                }
                $this->GetRowFromDB($femc_components[$i], $band, $addstring);
            }
        }

        //LPR- First Local Oscillator Photonic Reference
        $this->SetX($this->margin_left);
        $this->SetFont('','B');
        $this->Cell(265,7,'LPR- First Local Oscillator Photonic Reference',1,0,'C',true);
        $this->Ln();

        $lo_components = array(17);
        for ($i=0;$i< count($lo_components); $i++){
            for ($band = 0; $band <= 10; $band++){

                $bandval = $band;
                $addstring = " (Band $bandval)";
                if ($band == 0){
                    $bandval = '';
                    $addstring = '';
                }
                $this->GetRowFromDB($lo_components[$i], $band, $addstring);
            }
        }

        //LPR Cables
        $this->SetX($this->margin_left);
        $this->SetFont('','B');
        $this->Cell(265,7,'LPR Cables',1,0,'C',true);
        $this->Ln();
        $lpr_components = array(166,167);
        for ($i=0;$i< count($lpr_components); $i++){
            for ($band = 0; $band <= 10; $band++){
                $bandval = $band;
                $addstring = " (Band $bandval)";
                if ($band == 0){
                    $bandval = '';
                    $addstring = '';
                }
                $this->GetRowFromDB($lpr_components[$i], $band, $addstring);
            }
        }

        //RF Coaxial Cables
        $this->SetX($this->margin_left);
        $this->SetFont('','B');
        $this->Cell(265,7,'RF Coaxial Cables',1,0,'C',true);
        $this->Ln();

        $rf_components = array(168,169,170,171,172);
        for ($i=0;$i< count($rf_components); $i++){
            for ($band = 0; $band <= 10; $band++){

                $bandval = $band;
                $addstring = " (Band $bandval)";
                if ($band == 0){
                    $bandval = '';
                    $addstring = '';
                }
                $this->GetRowFromDB($rf_components[$i], $band, $addstring);
            }
        }

        //FE Electronics Chassis - Power Supply Cables
        $this->SetX($this->margin_left);
        $this->SetFont('','B');
        $this->Cell(265,7,'FE Electronics Chassis - Power Supply Cables',1,0,'C',true);
        $this->Ln();

        $fec_components = array(173,174,175,176,177,178,179,180,181,182,183,184,185);
        for ($i=0;$i< count($fec_components); $i++){
            for ($band = 0; $band <= 10; $band++){

                $bandval = $band;
                $addstring = " (Band $bandval)";
                if ($band == 0){
                    $bandval = '';
                    $addstring = '';
                }
                $this->GetRowFromDB($fec_components[$i], $band, $addstring);
            }
        }

        //FE Electronics Chassis - M&C Cabling
        $this->SetX($this->margin_left);
        $this->SetFont('','B');
        $this->Cell(265,7,'FE Electronics Chassis - M&C Cabling',1,0,'C',true);
        $this->Ln();

        $fecmc_components = array(186,187,188,189);
        for ($i=0;$i< count($fecmc_components); $i++){
            for ($band = 0; $band <= 10; $band++){

                $bandval = $band;
                $addstring = " (Band $bandval)";
                if ($band == 0){
                    $bandval = '';
                    $addstring = '';
                }
                $this->GetRowFromDB($fecmc_components[$i], $band, $addstring);
            }
        }

        //FE Electronics Chassis - Cryostat M&C Cabling
        $this->SetX($this->margin_left);
        $this->SetFont('','B');
        $this->Cell(265,7,'FE Electronics Chassis - Cryostat M&C Cabling',1,0,'C',true);
        $this->Ln();

        $feccmc_components = array(190,191,192,193,194,195,196,197,198,199,200,201,202,203,204,205);
        for ($i=0;$i< count($feccmc_components); $i++){
            for ($band = 0; $band <= 10; $band++){

                $bandval = $band;
                $addstring = " (Band $bandval)";
                if ($band == 0){
                    $bandval = '';
                    $addstring = '';
                }
                $this->GetRowFromDB($feccmc_components[$i], $band, $addstring);
            }
        }
        //FE Electronics Chassis Frame
        $this->SetX($this->margin_left);
        $this->SetFont('','B');
        $this->Cell(265,7,'FE Electronics Chassis Frame',1,0,'C',true);
        $this->Ln();

        $fecf_components = array(206,207,208,209);
        for ($i=0;$i< count($fecf_components); $i++){
            for ($band = 0; $band <= 10; $band++){

                $bandval = $band;
                $addstring = " (Band $bandval)";
                if ($band == 0){
                    $bandval = '';
                    $addstring = '';
                }
                $this->GetRowFromDB($fecf_components[$i], $band, $addstring);
            }
        }

        //FE Software and Firmware
        $this->SetX($this->margin_left);
        $this->SetFont('','B');
        $this->Cell(265,7,'FE Software and Firmware',1,0,'C',true);
        $this->Ln();

        $sw_components = array(210,211,212,213);
        for ($i=0;$i< count($sw_components); $i++){
            for ($band = 0; $band <= 10; $band++){

                $bandval = $band;
                $addstring = " (Band $bandval)";
                if ($band == 0){
                    $bandval = '';
                    $addstring = '';
                }
                $this->GetRowFromDB($sw_components[$i], $band, $addstring);
            }
        }
        for ($band = 0; $band <= 10; $band++){
            $bandval = $band;
            $addstring = " (Band $bandval)";
            if ($band == 0){
                $bandval = '';
                $addstring = '';
            }
            $this->GetRowFromDB(214, $band, $addstring);
        }
    }


    function Table_WarmOptics(){
        $this->SetFillColor(176,196,222);
        $this->SetTextColor(0);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont('','B');

        $this->SetX($this->margin_left);
        $this->Cell(265,7,'Warm Optics',1,0,'C',true);
        $this->Ln();

        $rowcontents = $this->GetRowFromDB(130, 3);
        $this->AddTableRow($rowcontents);
        $rowcontents = $this->GetRowFromDB(131, 3);
        $this->AddTableRow($rowcontents);
        $rowcontents = $this->GetRowFromDB(132, 3);
        $this->AddTableRow($rowcontents);
    }

    function Table_BandXCartridge($band){
        $this->SetFillColor(176,196,222);
        $this->SetTextColor(0);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont('','B');

        $this->SetX($this->margin_left);
        $this->Cell(265,7,"Band $band Cartridge",1,0,'C',true);
        $this->Ln();

        $q = "SELECT ComponentTypes.Description, FE_Components.keyId, ComponentTypes.keyId AS Ctype_Id
            FROM ComponentTypes, FE_Components, FE_ConfigLink
            WHERE FE_Components.Band = $band
            AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
            AND FE_ConfigLink.fkFE_Config = $this->FEConfiguration
            AND FE_Components.fkFE_ComponentType = ComponentTypes.keyId
            AND FE_Components.fkFE_ComponentType <> 130
            AND FE_Components.fkFE_ComponentType <> 131
            AND FE_Components.fkFE_ComponentType <> 132
            GROUP BY FE_Components.keyId;";
        $r = @mysql_query($q, $this->dbconnection);
        while ($row = @mysql_fetch_array($r)){
            $Ctype_Id = $row[2];
            $rowcontents = $this->GetRowFromDB($Ctype_Id, $band);
            $this->AddTableRow($rowcontents);


        }
    }

    function NewPageCartridgeTables(){
        if ($this->GetY() > 170){
            $this->AddHeader('L');
            $this->AddComponentTableHeaderRow();
        }
    }

    function NewPageDocs(){
        if ($this->GetY() > 170){
            $this->AddHeader('L');
            $this->AddDocTableHeaderRow();
        }
    }

    function AddTableRowMulti($multirowcontents){
        for ($i=0; $i < count($multirowcontents); $i ++){
            $this->AddTableRow($multirowcontents[$i]);
        }
    }

    function AddTableRow($rowcontents, $fill = false, $isheader = 0){
        $maxcount = count($rowcontents) - 4;

        if ($isheader == 1){
            $maxcount = count($rowcontents);
        }

        if ($rowcontents[2] != ""){
            $component = new FEComponent();
            $component->Initialize_FEComponent($rowcontents[9],$rowcontents[10]);

            $this->SetFont($this->font_type,'',8);

            $this->SetX($this->margin_left);



            $maxnocells = 0;
            $cellcount = 0;



            $Ycurrent = $this->GetY();
            $margin_add = 0;

            for ($i= 0; $i < $maxcount; $i++){
                $this->SetY($Ycurrent);
                $this->SetX($this->margin_left + $margin_add);

                  if ($isheader == 1){
                          $cellcount = $this->MultiCell($this->table_widths[$i],3,$rowcontents[$i],0,'C',$fill);
                      }
                      if ($isheader != 1){
                    if (($i != 4) && ($i != 7)){
                        $cellcount = $this->MultiCell($this->table_widths[$i],3,$rowcontents[$i],0,'C',$fill);
                    }
                    if ($i == 4){
                        $url = "https://safe.nrao.edu/php/ntc/FEConfig/ShowComponents.php?";
                        $url .= "conf="      . $component->keyId;
                        $url .= "&fc="     . $rowcontents[10];
                        $html = '<a href="'. $url . '">'. $rowcontents[4] . '</a>';
                        $this->writeHTMLCell($this->table_widths[$i], '', '', '', $html, 0, 0, 0, false, 'C', false);
                    }

                      if ($i == 7){
                          $Link = "";
                          $html = "";
                          $LinkArray = explode(",", $rowcontents[$i]);
                          if (strlen($LinkArray[0]) > 5){
                              $html = '<a href="'. $LinkArray[0] . '">Link</a>';
                          }
                          if (strlen($LinkArray[1]) > 5){
                              $html .= ', <a href="'. $LinkArray[1] . '">SICL</a>';
                          }
                        $this->writeHTMLCell($this->table_widths_doc[$i], '', '', '', $html, 0, 0, 0, false, 'B', false);
                    }

                  }


                if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}

                $margin_add += $this->table_widths[$i];
            }

            $margin_add = 0;
            for ($i= 0; $i < $maxcount; $i++){
                //$this->SetY($this->Y_offset_table + $this->Y_current);
                $this->SetY($Ycurrent);
                $this->SetX($this->margin_left + $margin_add);
            //str_repeat("\n",$numbreaks)
                  $this->MultiCell($this->table_widths[$i],$maxnocells * 4,'',1,'C',$fill);
                  $margin_add += $this->table_widths[$i];
            }

            $this->tablecount += 1;
            $this->NewPageCartridgeTables();
            unset($component);
        }
    }

    function AddTableRowDoc($rowcontents, $fill = false, $isheader = 0){
        $maxcount = count($rowcontents) - 2;

        if ($isheader == 1){
            $maxcount = count($rowcontents);
        }


        if ($rowcontents[1] != ""){
            $this->SetFont($this->font_type,'',8);
            $this->SetX($this->margin_left);
            $maxnocells = 0;
            $cellcount = 0;

            $Ycurrent = $this->GetY();
            $margin_add = 0;
            for ($i= 0; $i < $maxcount; $i++){
                $this->SetY($Ycurrent);
                $this->SetX($this->margin_left + $margin_add);
                  if ($isheader == 1){
                          $cellcount = $this->MultiCell($this->table_widths_doc[$i],3,$rowcontents[$i],0,'C',$fill);
                      }
                      if ($isheader != 1){
                        //$cellcount = $this->MultiCell($this->table_widths_doc[$i],3,$rowcontents[$i],0,'C',$fill);

                      if ($i == 0){
                          $Num = 'NA';
                          if (strlen($rowcontents[$i]) > 2){
                              $Num = $rowcontents[$i];
                          }
                        $url = "https://safe.nrao.edu/php/ntc/FEConfig/ShowComponents.php?";
                        $url .= "conf="      . $rowcontents[7];
                        $url .= "&fc="     . $rowcontents[6];
                        $html = '<a href="'. $url . '">'. $this->tablecount . '</a>';
                        $this->writeHTMLCell($this->table_widths[$i], '', '', '', $html, 0, 0, 0, false, 'C', false);
                    }
                    if (($i != 5) &&($i != 1) &&($i != 0)){
                        $cellcount = $this->MultiCell($this->table_widths_doc[$i],3,$rowcontents[$i],0,'C',$fill);
                    }
                      if ($i == 1){
                        $cellcount = $this->MultiCell($this->table_widths_doc[$i],3,$rowcontents[$i],0,'A',$fill);
                    }
                    if ($i == 5){
                        $Link = "";
                        if (strlen($rowcontents[$i] > 5)){
                            $Link = "Link";
                        }
                        $html = '<a href="'. $rowcontents[$i] . '">Link</a>';
                        $this->writeHTMLCell($this->table_widths_doc[$i], '', '', '', $html, 0, 0, 0, false, 'A', false);
                    }

                  }


                if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}

                $margin_add += $this->table_widths_doc[$i];
            }

            $margin_add = 0;
            for ($i= 0; $i < $maxcount; $i++){
                //$this->SetY($this->Y_offset_table + $this->Y_current);
                $this->SetY($Ycurrent);
                $this->SetX($this->margin_left + $margin_add);
            //str_repeat("\n",$numbreaks)
                  $this->MultiCell($this->table_widths_doc[$i],$maxnocells * 4,'',1,'C',$fill);
                  $margin_add += $this->table_widths_doc[$i];
            }

            $this->tablecount += 1;
            $this->NewPageDocs();
            unset($component);
        }
    }



    function AddComponentTableHeaderRow(){
        $this->tablecount -= 1;
        $y = $this->GetY();
        $this->SetY($y - 25);
        $this->SetFillColor(0,0,205);
        $this->SetTextColor(255);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont($this->font_type,'B');

        $row[0] ='Nr';
        $row[1] ='PT Nr';
        $row[2] ='Product Name';
        $row[3] ='Qty';
        $row[4] ='SN';
        $row[5] ='ESN';
        $row[6] ='Status';
        $row[7] ='As-Built CIDL';
        $row[8] ='Comments';
        $this->AddTableRow($row, true, 1);
        $this->SetFillColor(176,196,222);
        $this->SetTextColor(0);
    }

    function AddDocTableHeaderRow(){
        $this->tablecount -= 1;
        $y = $this->GetY();
        $this->SetY($y - 25);
        $this->SetFillColor(0,0,205);
        $this->SetTextColor(255);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont($this->font_type,'B');

        $row[0] ='Nr';
        $row[1] ='Title';
        $row[2] ='Comments';
        $row[3] ='Date';
        $row[4] ='Status';
        $row[5] ='Link';
        $this->AddTableRowDoc($row, true, 1);
        $this->SetFillColor(176,196,222);
        $this->SetTextColor(0);
    }

    function test1(){
        $this->AddPage();
            $maxnocells = 0;
        $cellcount = 0;
        //write text first
        $startX = $this->GetX();
        $startY = $this->GetY();
        //draw cells and record maximum cellcount
        //cell height is 6 and width is 80
        $cellcount = $this->MultiCell(80,6,$row['cell1data'],0,'L',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(80,6,$row['cell2data'],0,'L',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $cellcount = $this->MultiCell(80,6,$row['cell3data'],0,'L',0,0);
        if ($cellcount > $maxnocells ) {$maxnocells = $cellcount;}
        $this->SetXY($startX,$startY);

        //now do borders and fill
        //cell height is 6 times the max number of cells
        $this->MultiCell(80,$maxnocells * 6,'','LR','L',0,0);
        $this->MultiCell(80,$maxnocells * 6,'','LR','L',0,0);
        $this->MultiCell(80,$maxnocells * 6,'','LR','L',0,0);

        $this->Ln();

    }

    function GetRowFromDB($component_type, $band,  $AdditionalNameString = ""){
        $q = "SELECT ComponentTypes.Description, ComponentTypes.ProductTreeNumber,FE_Components.Link2,
            FE_Components.keyId, FE_StatusLocationAndNotes.keyId AS StatusId,
            FE_ConfigLink.Quantity,FE_Components.keyFacility,FE_Components.Link1
            FROM ComponentTypes, FE_Components, FE_ConfigLink, FE_StatusLocationAndNotes
            WHERE FE_Components.fkFE_ComponentType = $component_type
            AND FE_Components.Band LIKE  '$band'
            AND ComponentTypes.keyId = $component_type
            AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
            AND FE_ConfigLink.fkFE_Config = $this->FEConfiguration
            AND FE_StatusLocationAndNotes.fkFEComponents = FE_Components.keyId
            LIMIT 1;";
        $r = @mysql_query($q,$this->dbconnection);

        $ProductName     = @mysql_result($r,0,0);
        if ($ProductName != ""){
            $ProductName  = @mysql_result($r,0,0) . $AdditionalNameString;
        }
        $PTNr            = @mysql_result($r,0,1);
        $Docs            = @mysql_result($r,0,2);
        $Component_keyId = @mysql_result($r,0,3);
        $slnid           = @mysql_result($r,0,4);
        $Qty             = @mysql_result($r,0,5);
        $fc              = @mysql_result($r,0,6);
        $SICL             = @mysql_result($r,0,7);

        if ($Qty < 1){
            $Qty = 1;
        }

        $sln = new GenericTable();
        $sln->Initialize('FE_StatusLocationAndNotes', $slnid,'keyId',$fc,'keyFacility');

    //    $StatusType = new GenericTable();
    //    $StatusType->Initialize('StatusTypes',$sln->GetValue('fkStatusType'),'keyStatusType');
    //    $Status = $StatusType->GetValue('Status');
        $Status = "";

        $c = new GenericTable();
        $c->Initialize('FE_Components',$Component_keyId,'keyId',$fc,'keyFacility');

        $SN = $c->GetValue('SN');
        if (strlen($SN) < 1){
            $SN = "NA";
        }

        $RowArray[0] = $this->tablecount;
        $RowArray[1] = $PTNr;
        $RowArray[2] = $ProductName;
        $RowArray[3] = $Qty;
        $RowArray[4] = $SN;
        $RowArray[5] = $c->GetValue('ESN1');
        if (($c->GetValue('ESN2') != "") && ($c->GetValue('ESN2') != "N/A")){
            $RowArray[5] = $c->GetValue('ESN1') . " " . $c->GetValue('ESN2');
        }
        $RowArray[6]  = "$Status";
        $RowArray[7]  = "$Docs,$SICL";
        $RowArray[8]  = $c->GetValue('Description');
        $RowArray[9]  = $Component_keyId;
        $RowArray[10] = $fc;
        $RowArray[11] = $DocTitle;
        $RowArray[12] = $SICL;

        $this->AddTableRow($RowArray);

        return $RowArray;


        unset($c);
        unset($sln);
    }


    function GetRowsFromDB_Doc($component_type){


        $q = "SELECT FE_Components.keyId, FE_Components.keyFacility, FE_ConfigLink.Quantity
            FROM FE_Components, FE_ConfigLink, FE_Config, Front_Ends
            WHERE FE_Components.fkFE_ComponentType = $component_type
            AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
            AND FE_ConfigLink.fkFE_Config = FE_Config.keyFEConfig
            AND FE_Config.fkFront_Ends = Front_Ends.keyFrontEnds
            AND FE_ConfigLink.fkFE_Config = $this->FEConfiguration
            GROUP BY FE_Components.DocumentTitle ASC;";

        $r = @mysql_query($q,$this->dbconnection);


            $r = @mysql_query($q,$this->dbconnection);
            while ($row = @mysql_fetch_array($r)){
                $ctemp = new FEComponent();
                $ctemp->Initialize_FEComponent($row['keyId'],$row['keyFacility']);

                $Revision        = $ctemp->GetValue('Description');
                $PTNr            = $ctemp->ComponentType->GetValue('ProductTreeNumber');
                $Link            = $ctemp->GetValue('Link2');
                $Component_keyId = $ctemp->keyId;
                $slnid           = $ctemp->sln->keyId;
                $Qty             = $row['Quantity'];
                $fc              = $row['keyFacility'];
                $DocTitle        = $ctemp->GetValue('DocumentTitle');
                $ProdStatus      = $ctemp->GetValue('Production_Status');
                $TS                 = $ctemp->GetValue('TS');

                $sln = new GenericTable();
                $sln->Initialize('FE_StatusLocationAndNotes', $slnid,'keyId');

                $StatusType = new GenericTable();
                $StatusType->Initialize('StatusTypes',$sln->GetValue('fkStatusType'),'keyStatusType');

                $SN = $ctemp->GetValue('SN');
                if ($SN == ''){
                    $SN = "NA";
                }

                $RowArray[0] = $this->tablecount;
                //$RowArray[1] = $PTNr;
                $RowArray[1] = $DocTitle;
                $RowArray[2] = $Revision;
                $RowArray[3] = $TS;
                $RowArray[4] = $ProdStatus;
                $RowArray[5] = $Link;
                $RowArray[6] = $fc;
                $RowArray[7] = $Component_keyId;

                $this->AddTableRowDoc($RowArray);
                unset($ctemp);
                unset($sln);
            }//end while

    }

    function GetRowsFromDB($component_type, $band, $AdditionalNameString = ""){
        $q = "SELECT ComponentTypes.Description, ComponentTypes.ProductTreeNumber,FE_Components.Link2,
            FE_Components.keyId, FE_StatusLocationAndNotes.keyId AS StatusId,
            FE_ConfigLink.Quantity,FE_Components.keyFacility
            FROM ComponentTypes, FE_Components, FE_ConfigLink, FE_StatusLocationAndNotes
            WHERE FE_Components.fkFE_ComponentType = $component_type
            AND FE_Components.Band LIKE  '$band'
            AND ComponentTypes.keyId = $component_type
            AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
            AND FE_ConfigLink.fkFE_Config =$this->FEConfiguration
            AND FE_StatusLocationAndNotes.fkFEComponents = FE_Components.keyId
            GROUP BY FE_Components.keyId, FE_Components.SN ASC;";
        $r = @mysql_query($q,$this->dbconnection);

        while ($row = @mysql_fetch_array($r)){

            $ProductName     = $row[0] . $AdditionalNameString;
            $PTNr            = $row[1];
            $Docs            = $row[2];
            $Component_keyId = $row[3];
            $slnid           = $row[4];
            $Qty             = $row[5];
            $fc              = $row[6];


            if ($Qty < 1){
                $Qty = 1;
            }

            $sln = new GenericTable();
            $sln->Initialize('FE_StatusLocationAndNotes', $slnid,'keyId');

    //         $StatusType = new GenericTable();
    //         $StatusType->Initialize('StatusTypes',$sln->GetValue('fkStatusType'),'keyStatusType');
    //         $Status = $StatusType->GetValue('Status');
            $Status = "";

            $c = new GenericTable();
            $c->Initialize('FE_Components',$Component_keyId,'keyId');

            $SN = $c->GetValue('SN');
            if ($SN == ''){
                $SN = "NA";
            }

            $RowArray[0] = $this->tablecount;
            $RowArray[1] = $PTNr;
            $RowArray[2] = $ProductName;
            $RowArray[3] = $Qty;
            $RowArray[4] = $SN;
            $RowArray[5] = $c->GetValue('ESN1');
            if ($c->GetValue('ESN2') != ""){
                $RowArray[5] = $c->GetValue('ESN1') . " " . $c->GetValue('ESN2');
            }
            $RowArray[6]  = "$Status";
            $RowArray[7]  = $Docs;
            $RowArray[8]  = ""; //$sln->GetValue('Notes');
            $RowArray[9]  = $Component_keyId;
            $RowArray[10] = $fc;
            $RowArray[11] = $DocTitle;
            $RowArray[12] = " ";

            $IsDoc = 0;
            switch($component_type){
                case 217:
                    $IsDoc = 1;
                    break;
                case 218:
                    $IsDoc = 1;
                    break;
                case 219:
                    $IsDoc = 1;
                    break;
                case 220:
                    $IsDoc = 1;
                    break;
            }

            if ($IsDoc == 0){
                $this->AddTableRow($RowArray);
            }
            if ($IsDoc == 1){
                $this->AddTableRowDoc($RowArray);
            }

            unset($c);
            unset($sln);
        }
    }


    function CheckCartridgeExists($band){
        // check for component type 20 which is the CCA:

        $q = "SELECT ComponentTypes.Description, FE_Components.keyId
            FROM ComponentTypes, FE_Components, FE_ConfigLink
            WHERE FE_Components.Band = $band
            AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
            AND FE_ConfigLink.fkFE_Config = $this->FEConfiguration
            AND FE_Components.fkFE_ComponentType = ComponentTypes.keyId
            AND (FE_Components.fkFE_ComponentType = 20
              OR FE_Components.fkFE_ComponentType = 133
              OR FE_Components.fkFE_ComponentType = 11
              OR FE_Components.fkFE_ComponentType = 134
              OR FE_Components.fkFE_ComponentType = 16)
            GROUP BY FE_Components.keyId;";

        $r = @mysql_query($q,$this->dbconnection);
        $numrows = @mysql_num_rows($r);
        if ($numrows > 0){
            return 1;
        }
        if ($numrows == 0){
            return 0;
        }
    }
    function CheckComponentExists($band, $component_type){
        $q = "SELECT ComponentTypes.Description, FE_Components.keyId
            FROM ComponentTypes, FE_Components, FE_ConfigLink
            WHERE FE_Components.Band = $band
            AND FE_Components.fkFE_ComponentType = $component_type
            AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
            AND FE_ConfigLink.fkFE_Config = $this->FEConfiguration
            AND FE_Components.fkFE_ComponentType = ComponentTypes.keyId
            GROUP BY FE_Components.keyId;";
        $r = @mysql_query($q,$this->dbconnection);
        $numrows = @mysql_num_rows($r);
        if ($numrows > 0){
            return 1;
        }
        if ($numrows == 0){
            return 0;
        }
    }
}
?>
