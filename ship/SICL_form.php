<?php

$formaction = $_SERVER['PHP_SELF'];


echo "
<h1>Information for Shipping Inspection Checklist</h1>
<form enctype='multipart/form-data' action='$formaction' method='post'>
	<font size='+1'>
	
	
	
	<p>Title: <input type='text' name='heading1' size='60' maxlength='80' 
	value='$heading1' /></p>
	
	<p>Subtitle: <input type='text' name='heading2' size='60' maxlength='80' 
	value='$heading2' /></p>
	
	<p>ALMA EDM No. : <input type='text' name='title1' size='30' maxlength='30' 
	value='$title1'  /> </p>
	</font>
	<p>
	
	<div align = 'center'>
	<div align = 'left' style='width:400px;height:100%;border:1px solid black;background-color: #ffffcc'>
	<b>
	
	<font color = '#ff0000'>CAUTION:</font> <font color='#000000'> Must log on to ALMA EDM to get the next available<br> 
	document # (i.e., XXX) before proceeding further.</font><br> 
	<a href='http://edm.alma.cl/addto3third.html?projectcode=FEND&level1code=40&level2code=09&level3code=07&level4code=00' target = 'blank'>
	<br>
	<div align = 'center'><img src='pics/getnextdocnumber.bmp'></div>
	</b></a>
	</div></div></p>

	<font size = '+1'>
	
	<p>Introduction: <br>
	<textarea rows='10' cols='50' name='docintro' size='60' maxlength='300'>";
	echo $docintro;
	
	echo "</textarea></p>
	
	<p>Notes: </font><br>
	<textarea rows='15' cols='50' name='docnotes' size='60' maxlength='300'>";
	
	echo trim($docnotes);
	
	echo "
	</textarea></p>
	<br><br>
	<br>
	<h1>Data for Tables in the Word Document</h1>
	<br><b>";
	
	echo "<p><a href='example_format.csv'>Click here for example CSV file</a></p>";
	
	/*
	<div align = 'center'>
	<div align = 'left' style='width:450px;height:100%;border:1px solid black;background-color: #fffff3'>
	<font color = '#ff0000'>CAUTION: </font>  
	If there are any '/' characters in the file, remove them or the <br>
	file will not import. There may be forward slashes in the header row, <br>
	so delete that row before importing the file contents.</b>
	</b></a>
	</div></font></div></p>";*/
	?>

	<br>
	<b>
	    <br>
	    <div align = 'center'>
	    <div align = 'left' style="height:100%;width:70%;border:1px solid black;background-color: #ffff99">
	    Section 1 Title: <input type="text" name="section1title" size="60" maxlength="80"/><br><br>
	    Section 1 csv file: <input name="section1file" type="file" /><br>
		</div>
	    
	    <br>
	    <div align = 'left' style="height:100%;width:70%;border:1px solid black;background-color: #ffff99">
	    Section 2 Title: <input type="text" name="section2title" size="60" maxlength="80"/><br><br>
	    Section 2 csv file: <input name="section2file" type="file" /><br>
	    </div>
	    
	    <br>
	    <div align = 'left' style="height:100%;width:70%;border:1px solid black;background-color: #ffff99">
	    Section 3 Title: <input type="text" name="section3title" size="60" maxlength="80"/><br><br>
	    Section 3 csv file: <input name="section3file" type="file" /><br>
	    </div></div>
	    </b>
	<br>
	<p><input type='image' src='pics/createdocbutton.bmp' name='submit' value='SUBMIT' /></p>
	<input type='hidden' name='submitted' value='TRUE' /></b>
	
</form>



