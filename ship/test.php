
<?php
include('header.php');
include ('reader.php');

if (isset($_REQUEST['submit'])){
			// ExcelFile($filename, $encoding);
		$data = new Spreadsheet_Excel_Reader();
		
		
		// Set output Encoding.
		$data->setOutputEncoding('CP1251');
		
		/***
		* if you want you can change 'iconv' to mb_convert_encoding:
		* $data->setUTFEncoder('mb');
		*
		**/
		
		/***
		* By default rows & cols indeces start with 1
		* For change initial index use:
		* $data->setRowColOffset(0);
		*
		**/
		
		
		
		/***
		*  Some function for formatting output.
		* $data->setDefaultFormat('%.2f');
		* setDefaultFormat - set format for columns with unknown formatting
		*
		* $data->setColumnFormat(4, '%.3f');
		* setColumnFormat - set format for column (apply only to number fields)
		*
		**/
		$filename = $_FILES['userfile']['tmp_name'];
		$data->read($filename);
		
		/*
		
		
		 $data->sheets[0]['numRows'] - count rows
		 $data->sheets[0]['numCols'] - count columns
		 $data->sheets[0]['cells'][$i][$j] - data from $i-row $j-column
		
		 $data->sheets[0]['cellsInfo'][$i][$j] - extended info about cell
		    
		    $data->sheets[0]['cellsInfo'][$i][$j]['type'] = "date" | "number" | "unknown"
		        if 'type' == "unknown" - use 'raw' value, because  cell contain value with format '0.00';
		    $data->sheets[0]['cellsInfo'][$i][$j]['raw'] = value if cell without format 
		    $data->sheets[0]['cellsInfo'][$i][$j]['colspan'] 
		    $data->sheets[0]['cellsInfo'][$i][$j]['rowspan'] 
		*/
		
		error_reporting(E_ALL ^ E_NOTICE);
		
		for ($i = 1; $i <= $data->sheets[1]['numRows']; $i++) {
			for ($j = 1; $j <= $data->sheets[1]['numCols']; $j++) {
				echo "\"".$data->sheets[1]['cells'][$i][$j]."\",";
			}
			echo "<br><br>";
		}
}

if (!isset($_REQUEST['submit'])){
	echo'
	<p><div style="width:700px;height:50px;border:1px solid black;background-color: #C3FDB8" align = "left"></p>
	<div align="center">
	<form enctype="multipart/form-data" action="' . $_SERVER["PHP_SELF"] . '" method="POST">
		<br>
		<b>Select an XLS file: </b>
	    <input name="userfile" type="file" />
	    <input type="submit" name="submit" value="SUBMIT" />
	</form>
	</div>
	</div>';
}
include('footer.php');
?>