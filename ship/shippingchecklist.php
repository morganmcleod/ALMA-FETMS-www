<?php
$page_title = 'Create Shipping Inspection Checklist';
if (!isset($_REQUEST['submitted'])){
	include('header.php');
}

include ('mysql_connect.php');
$title1 = "FEND-40.09.07.00-XXX-A-LIS";
$heading1 = "EU-FEIC Front End";
$heading2 = "Electronics Chassis #1 and #2";
$section1title = "section1";
$section2title = "none";
$section3title = "none";
$docnotes = "Enter notes";
$docintro = "This document is the Shipping Inspection Checklist for...";



$shipID = 0;
if (isset($_REQUEST['id'])){
	$shipID = $_REQUEST['id'];
	$qSICL = "SELECT Title, SICL, Notes FROM Shipments WHERE keyId = $shipID;";
	$rSICL = @mysql_query($qSICL,$dbc);
	$rowSICL = @mysql_fetch_array($rSICL);
	$title1 = $rowSICL[1];
	$docnotes = $rowSICL[2];
	$heading1 = $rowSICL[0];
}


if (isset($_REQUEST["title1"])){
	$title1 = trim($_REQUEST["title1"]);
	$docname = $title1 . '.doc';
}

if (isset($_REQUEST["heading1"])){
	$heading1 = $_REQUEST["heading1"];
}

if (isset($_REQUEST["heading2"])){
	$heading2 = $_REQUEST["heading2"];
}

if (isset($_REQUEST["section1title"])){
	$section1title = $_REQUEST["section1title"];
}

if (isset($_REQUEST["section2title"])){
	$section2title = $_REQUEST["section2title"];
	if ($section2title == ""){
		$section2title = "none";
	}
}

if (isset($_REQUEST["section3title"])){
	$section3title = $_REQUEST["section3title"];
	if ($section3title == ""){
		$section3title = "none";
	}
}


if (isset($_REQUEST["docnotes"])){
	$docnotes = $_REQUEST["docnotes"];
}

if (isset($_REQUEST["docintro"])){
	$docintro = $_REQUEST["docintro"];
}


if (!isset($_REQUEST['submitted'])){
	include('SICL_form.php');
}



if (isset($_REQUEST['submitted'])){
	$date = date(Y . "-" . m . "-" . d);

	$filename1 = $_FILES['section1file']['name'];
	$filename2 = $_FILES['section2file']['name'];
	$filename3 = $_FILES['section3file']['name'];


	
	include('SICL_body.php');


	
	//Sections for the uploaded CSV files

	$sectiontitle = $section1title;
	$filecontents = file(($_FILES['section1file']['tmp_name']));
	$TableTitle = "FE Electronics Chassis S/N-05 Shipping List";
	$HeaderBG = "#ffcc00";
	include('WordTable.php');
	 ?>
	
	<p class=MsoNormal><span style='font-size: 9.0pt; font-family: Arial'><o:p>&nbsp;</o:p></span></p>
	<p class=MsoNormal><span style='font-size: 9.0pt; font-family: Arial'><o:p>&nbsp;</o:p></span></p>
	<p class=MsoNormal><span style='font-size: 9.0pt; font-family: Arial'><o:p>&nbsp;</o:p></span></p>
	<p class=MsoNormal><span style='font-size: 9.0pt; font-family: Arial'><o:p>&nbsp;</o:p></span></p>
	<p class=MsoNormal><span style='font-size: 9.0pt; font-family: Arial'><o:p>&nbsp;</o:p></span></p>
	
	<?php
	if ($section2title != "none"){
		$sectiontitle = $section2title;
		$filecontents = file(($_FILES['section2file']['tmp_name']));
		$TableTitle = "FE Electronics Chassis S/N-06 Shipping List";
		$HeaderBG = "#33ffff";
		include('WordTable.php');
	}
	 ?>
	
	
	<p class=MsoNormal><o:p>&nbsp;</o:p></p>
	<p class=MsoNormal><o:p>&nbsp;</o:p></p>
	<p class=MsoNormal><o:p>&nbsp;</o:p></p>
	<p class=MsoNormal><o:p>&nbsp;</o:p></p>
	<p class=MsoNormal><o:p>&nbsp;</o:p></p>
	
	<?php
	if ($section3title!="none"){
	$sectiontitle = $section3title;
	$filecontents = file(($_FILES['section3file']['tmp_name']));
	$TableTitle = "Parts not listed under sections 3 and 4 Shipping List";
	$HeaderBG = "#99ff33";
	include('WordTable.php');
	}
	?>
	
	
	<p class=MsoNormal><o:p>&nbsp;</o:p></p>
	</div>
	</body>
	</html>
	
	<?php 
}//end if submitted
	

if (!isset($_REQUEST['submitted'])){	
	include('footer.php');
}

?>
