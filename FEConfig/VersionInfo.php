<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html401/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link href="images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
<title>Revision History</title>
</head>
<body style="background-color: #19475E;">
<?php
	$title="Revision History";
	include "header.php";
?>
<div id="maincontent3">

<table id="table2">
<tr class='alt'>
	<th>Version</th>
	<th>Notes</th>
	<th>Date</th>
</tr>

<tr>
	<th>
		1.1.17
	</th>
	<td>
	    Significant refactoring to simplify dependencies amongst PHP files.
	</td>
	<td>
		2012-10-18
	</td>
</tr>


<tr>
	<th>
		1.1.16
	</th>
	<td>
	    1. New Use For PAI button in test data display.<br><br>
	    2. Changed 'Other' tab to 'Components'.<br><br>
	    3. Fixed bug finding wrong noise floor trace for IF spectrum plots.
	</td>
	<td>
		2012-08-17
	</td>
</tr>

<tr>
	<th>
		1.1.15
	</th>
	<td>
	    1. Bug fixes to Beam Efficiency calculations.<br><br>
	    2. Page Layout and performance improvements.
	</td>
	<td>
		2012-08-14
	</td>
</tr>


<tr>
	<th>
		1.1.14
	</th>
	<td>
	    1. Page Layout and performance improvements.
	</td>
	<td>
		2012-07-31
	</td>
</tr>

<tr>
	<th>
		1.1.13
	</th>
	<td>
		1. IF Spectrum data sets can be put into groups before plotting. This allows more than one data set
		   to be plotted for a single band of a front end.<br><br>
		2. LO Lock test data sets can be put into groups before plotting.<br><br>
		3. CCA file upload is now done by clicking a button in the toolbar above the tabbed enclosure in a CCA page. <br><br>
	</td>
	<td>
		2012-04-02
	</td>
</tr>


<tr>
	<th>
		1.1.12
	</th>
	<td>
		1. Added feature for editing CIDL change record. <br><br>
		2. WCA can now be updated via uploading FrontEndControlDLL.ini file. <br><br>
		3. CCA temp sensor offset file can be generated from a button on a ShowComponents.php. <br><br>
	</td>
	<td>
		2012-02-09
	</td>
</tr>

<tr>
	<th>
		1.1.11
	</th>
	<td>
		1. Progress update is shown when generating a CIDL. <br><br>
		2. CCA configuration update uses a single file browser instead of three different ones. It also creates new mixer and preamp params from a FrontEndControllDLL.ini file.<br><br>
		3. IF Spectrum analysis is much faster now.<br><br>
		4. Cleaned up grids in AddComponents.php, UpdateFE.php, and added user name selector.<br><br>
		5. Updated how notes are added to/from a  front end for various operations (import CIDL, add/remove a component
		   manually, etc.) Previously there were extraneous and/or nondescriptive notes.<br><br>
		6. Moved buttons from side panel into ExtJS toolbars in ShowFEConfig.php, FEHome.php, bp.php,
		ifspectrum.php, ShowComponents.php.<br><br>
		7. Added Temp Sensor Offset export funtion when viewing a CCA.<br><br>
		8. Plotting functions added for Noise Temperature and CCA image rejection.<br><br>
		9. Javascript spinners added in several places to indicate loading
		while generating plots, selecting a component type on the main page, and importing a CIDL.
	</td>
	<td>
		2011-12-07
	</td>
</tr>

<tr>
	<th>
		1.1.10
	</th>
	<td>
		1. Using ExtJS toolbars with buttons in FEHome.php, ShowFEConfig.php and ShowComponents.php. <br><br>
		2. Some plots are drawn automatically the first time without requiring the user to push the button.<br><br>
		3. CCA IV curve now has multiple plots (one for each LO and IF Channel).<br><br>
		4. LO Lock test plot has spec lines.<br><br>
		5. Fixed bug with displaying notes contaning quotes in extjs grids.<br><br>
	</td>
	<td>
		2011-12-07
	</td>
</tr>

<tr>
	<th>
		1.1.9
	</th>
	<td>
		1. Recent Tests page now uses ExtJS 4.<br><br>
		2. AJAX status page now shows live progress updates during IF Spectrum plotting.<br><br>
		3. Fixed bug where CCA object was initialized with duplicate MixerParam objects (this was discovered in generated FE ini files).<br><br>
		4. When viewing ShowFEConfig, the table of components will show links along with the other information, if applicable.<br><br>

		<u>Files affected:</u><br>
		<i>
		classes/class.cca.php<br>
		classes/class.fecomponent.php<br>
		classes/class.frontend.php<br>
		classes/class.ifspectrum.php<br>
		FEConfig/EditComponent.php<br>
		FEConfig/ifspectrum/ifspectrum.php<br>
		FEConfig/pbar (entire directory added)<br>
		</i>


	</td>
	<td>
		2011-11-30
	</td>
</tr>



<tr>
	<th>
		1.1.8
	</th>
	<td>
		1. New page format for beam patterns.<br><br>
		2. New page format for if spectrum.<br><br>
		3. ExtJS table now display tooltip when mouse hovers over notes field.<br><br>
		4. Configuration history tables now done using ExtJS.<br><br>
		5. Spinner and message are displayed while plots are being generated.<br><br>
		6. Added feature for adding a document to a front end.<br><br>


	</td>
	<td>
		2011-11-04
	</td>
</tr>


<tr>
	<th>
		1.1.7
	</th>
	<td>
		1. Added ability to edit configuration when viewing a CCA page. This allows user<br>
		to change mixer, temperature sensor and preamp params.<br><br>
		2. Recent Test list page includes a selector to specify test data status.<br><br>
		3. IF Spectrum uses temporary tables for plotting.<br><br>
		4. CIDL generator includes documents now instead of just making blank tables.<br><br>


	</td>
	<td>
		2011-10-19
	</td>
</tr>


<tr>
	<th>
		1.1.6
	</th>
	<td>
		1. Added Doc column to Front End list at FEHome.<br><br>
		2. Added CIDL Import feature, linked from FEHome.<br><br>
		3. Added feature to update CCA configuration from an INI file.<br><br>
		4. Component edit screen allows editing of WCA parameters (YIG Hi/Lo, VG0, VG1).<br><br>


	</td>
	<td>
		2011-10-19
	</td>
</tr>
<tr>
	<th>
		1.1.5
	</th>
	<td>
		1. For front end, new functions for adding notes or editing the front end.<br><br>
		Edit FE- Previous info is retained in entry form, and now a dropdown "Updated By" list is shown.
		Upon submission, the FE configuration is incremented, and the FE Config links table is updated accordingly.<br><br>
		Add Notes- Similar to previous implementation, but now the Updated By list is populated from
		the database, and the previous values are retained in the entry form.<br><br>
		2. DBOperations class now handles the case when you want to update SLN for a front
		end as a standalone function, not called while doing an add/remove operation on
		a component.<br><br>
		3. Added HelperFunction.php, which includes functions for fixing a UNC path before inserting
		into a table or displaying as a hyperlink.<br><br>
		4. Fixed issues with links not being displayed as clickable hyperlinks.<br><br>

	</td>
	<td>
		2011-10-07
	</td>
</tr>

<tr>
	<th>
		1.1.4
	</th>
	<td>
		1. Added Cancel buttons when editing or adding notes to component.<br><br>
		2. Placed Add Notes button next to Status/Location/Notes history table for front ends and components.<br><br>
		3. Fixed issues with div heights/padding/background color on FEHome.php, ShowFEConfig.php, ShowComponents.php<br><br>
		4. When viewing a page for a CCA or WCA (ShowComponents.php), the INI file link is shown in the left sidebar.<br><br>
		5. Added VersionInfo page.<br><br>
	</td>
	<td>
		2011-10-06
	</td>
</tr>
<tr>
	<th>
		1.1.3
	</th>
	<td>
		1. Added two buttons when viewing a component (ShowComponents.php).<br><br>
			Add Notes- Lets you create a new SLN record for the component.<br><br>
			Edit Component- Allows you to change ESN1, ESN2, Docs, Description for a component.
			User specifies his initials in the Updated By selector.When submitted,
			the appropriate info is updated. A new Status Location And Notes record is created,
			with notes indicating who changed what ("JC changed ESN1, Description"). the
			other SLN info is copied from the previous record. <br><br>
		2. Placed Add Notes button next to Status/Location/Notes history table for front ends and components.<br><br>
		3. Fixed issues with div heights/padding/backgroudn color on FEHome.php, ShowFEConfig.php, ShowComponents.php<br><br>
		4. New DBOperations class handles the following functions:
			-Add Component to Front End<br><br>
			-Remove Component from Front End- This copies the previous SLN record for the front end to
			a new record, and changes the notes to indicate which component was removed.<br><br>
		5. Fixed bug where components weren't being associated with a Front End after being created
			using the grid.<br><br>
		6. Added Recent Tests button on front page.<br><br>
	</td>
	<td>
		2011-10-05
	</td>
</tr>
<tr>
	<th>
		1.1.2
	</th>
	<td>
		1. Passing facility code through all pages.<br><br>
		2. Testdata.php modified to have similar appearance as the rest of the site.<br><br>
		3. Added buttons.css, and modified headers and sidebars to use css format.<br><br>
	</td>
	<td>
		2011-09-21
	</td>
</tr>
<tr>
<tr>
	<th>
		1.1.1
	</th>
	<td>
		1. Incorporated Cryostat features that were previously used in a separate application.<br><br>
		2. Testdata.php modified to have similar appearance as the rest of the site.<br><br>
		3. Incorporated CCA features that were previously used in a separate application.<br><br>
	</td>
	<td>
		2011-09-19
	</td>
</tr>
<tr>
<tr>
	<th>
		1.1.0
	</th>
	<td>
		1. Changed some table appearances.<br><br>
		2. Added buttons.css, and modified headers and sidebars to use css format.<br><br>
	</td>
	<td>
		2011-09-13
	</td>
</tr>
<tr>
	<th>
		1.0.2
	</th>
	<td>
		1. Added comments and cleaned up code.<br><br>
	</td>
	<td>
		2011-08-24
	</td>
</tr>
<tr>
	<th>
		1.0.1
	</th>
	<td>
		1. WCA Max Safe and LO params to ShowComponents.php.<br><br>
	</td>
	<td>
		2011-08-05
	</td>
</tr>

<tr>
	<th>
		1.0.0
	</th>
	<td>
		1. Initial Version put on production workspace.<br><br>
	</td>
	<td>
		2011-05-26
	</td>
</tr>


</table>

</div>

</body>
</html>

<?php
    include('footer.php');
?>