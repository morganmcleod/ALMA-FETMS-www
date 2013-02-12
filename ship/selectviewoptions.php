<br><br>
<center>
<div style="width:65%;height:110%;border:1px solid black" align="right">
<form name = "SelectViewOptions" action="view_components_sort.php" method="get">
<div align = "center">
<b>View Filter Options</b>
</div><font color = "#000099">
<?php 

if (isset($_GET['ComponentTypeView'])){
	$CurrentComponentType = $_GET['ComponentTypeView'];
	$CurrentLocation = $_GET['LocationView'];
	$CurrentBand = $_GET['BandView'];
}

	//This part creates and displays a drop-down list for
	//selecting a component type
	$option_blockComponentType .= "<option value='All'>All</option>";
	echo'
	<p><font size="+1"><b>Component Type:</b></font><select name="ComponentTypeView">'; 
	$qComponentType = "SELECT keyId AS ID, Description AS Descript FROM ComponentTypes ORDER BY Description ASC";		
	$rComponentType = @mysql_query ($qComponentType, $dbc);
	while ($row = mysql_fetch_array($rComponentType)) {
			$keyIdTEMP=$row['ID'];
			$descriptionTEMP=$row['Descript'];
			if ($keyIdTEMP == $CurrentComponentType){	
	   		$option_blockComponentType .= "<option value=$keyIdTEMP selected ='selected'>$descriptionTEMP</option>";
			}
			else{
			$option_blockComponentType .= "<option value='$keyIdTEMP'>$descriptionTEMP</option>";
			}
			
	} // End of WHILE loop.
	echo $option_blockComponentType;
	echo '</select>';
	?>
	
	
	<p><font size="+1"><b>Location:</b></font><select name="LocationView"> 
	<?php 
	//This part creates and displays a drop-down list for
	//selecting a location
	$option_blockLoc .= "<option value='All'>All</option>";
	$qLoc = "SELECT keyId AS IDLoc, Description AS DescriptionLoc, Notes AS NotesLoc FROM Locations ORDER BY Description ASC";		
	$rLoc = @mysql_query ($qLoc, $dbc);
	//$option_blockLoc = "<option value='0'>Unknown Location</option>"; 
	while ($rowLoc = mysql_fetch_array($rLoc)) {
			$keyIdTEMPLoc=$rowLoc['IDLoc'];
			$DescTEMPLoc=$rowLoc['DescriptionLoc'];
			$NotesTEMPLoc=$rowLoc['NotesLoc'];
			
			if ($keyIdTEMPLoc == $CurrentLocation){	
	   		$option_blockLoc .= "<option value='$keyIdTEMPLoc' selected ='selected'>$DescTEMPLoc ($NotesTEMPLoc)</option>";
			}
			else{
			$option_blockLoc .= "<option value='$keyIdTEMPLoc'>$DescTEMPLoc ($NotesTEMPLoc)</option>";
			}
			
	} // End of WHILE loop.
	
	echo $option_blockLoc;
	?>
	</select></p>
	
	<p><font size="+1"><b>Band:</b></font><select name="BandView"> 
	<?php 
	//This part creates and displays a drop-down list for
	//selecting a Band
	$option_blockBand .= "<option value='All'>All</option>";

	//$option_blockLoc = "<option value='0'>Unknown Location</option>"; 

    for($i=1;$i<10;$i++){
			if ($i == $CurrentBand){	
	   		$option_blockBand .= "<option value='$i' selected ='selected'>$i</option>";
			}
			else{
			$option_blockBand .= "<option value='$i'>$i</option>";
			}
			
	} // End of WHILE loop.
	
	echo $option_blockBand;
	?>
	</select></p>
	
	<center>
	<p><input type="image" SRC="pics/applyfilteroptions.bmp" name="submit" value="APPLY FILTER OPTIONS" /></p>
	
	</center>
	</font>

</form>
</div>
</center>
<br><br><br>

