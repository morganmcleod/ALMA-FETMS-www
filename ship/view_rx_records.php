<?php
include('header_wide.php');

include('rxclasses.php');
//include ('ship_classes.php');
include('reader.php');
include('mysql_connect.php');

$requestor_selected = "%";
$RxFrom_selected = "%";
$POsearch = "%";

if (isset($_REQUEST['Requestor'])) {
    $requestor_selected = urldecode($_REQUEST['Requestor']);
    //echo "Requestor: $requestor_selected<br>";
}
if (isset($_REQUEST['RxFrom'])) {
    $RxFrom_selected = urldecode($_REQUEST['RxFrom']);
    //echo "RxFrom: $RxFrom_selected<br>";
}
if (isset($_REQUEST['POsearch'])) {
    $POsearch = urldecode($_REQUEST['POsearch']);
    if ($POsearch == "") {
        $POsearch = "%";
    }
    //echo "PO Search: $POsearch<br>";
}


$showLegend = 0;
if (isset($_REQUEST['legend'])) {
    $showLegend = $_REQUEST['legend'];
}

/*
echo '<br><a href="files/example_rx_sheet.xls">
	 <i><b>(Click here for example Rx Checklist spreadsheet)</i>
	 </a></b><br>';
*/

/*
echo '<br>
<table align="left" cellspacing="1" cellpadding="1" width="70%">
	<td align="left">
		<tr>';

		if (isset($_REQUEST['submit'])){
			$XLS = new ExcelReader_class();
			$XLS->Initialize(0);
			$XLS->ImportData();
			echo '<meta http-equiv="Refresh" content="1;url=view_rx_records.php">';
		}
		if (!isset($_REQUEST['submit'])){
			$XLS = new ExcelReader_class();
			//$XLS->Display_ImportForm();
		}
		
	
		echo '
		</tr>
	</td>

	<td align="left">
		<tr>
			<a href="view_damages.php">	
			<img src="pics/damagesbutton.bmp"></a>
		</tr>
		<tr>
			<a href="view_discrepancies.php">	
			<img src="pics/discrepanciesbutton.bmp"></a>
		</tr>
		<tr>
			<a href="http://www.cv.nrao.edu/php-internal/ntc/Tasks/DatabaseUI/www/ship/view_rx_records.php?legend=1">
			<img src="pics/showlegendbutton.bmp"></a>
		</tr>
	
		<tr>
			<a href="add_record_received_item.php">	
			<img src="pics/addrecordbutton.bmp"></a>
		</tr>
	</td>
</table>';
		*/
echo '
		<br>
		<a href="view_damages.php">	
		<img src="pics/damagesbutton.bmp"></a>
		<br>			
		<a href="view_discrepancies.php">	
		<img src="pics/discrepanciesbutton.bmp"></a>
		<br>
		<a href="http://www.cv.nrao.edu/php-internal/ntc/Tasks/DatabaseUI/www/ship/view_rx_records.php?legend=1">
		<img src="pics/showlegendbutton.bmp"></a>
		<br>
		<a href="add_record_received_item.php">	
		<img src="pics/addrecordbutton.bmp"></a>
		<br>';

?>
<form name='requestor_select' action="<?php echo $_SERVER['PHP_SELF']; ?>" method='get'>
    <?php
    echo "<br><br><b>Requestor:</b><select name='Requestor' onChange='submit()'>";
    $q_req = 'select distinct(Requestor) from ReceivedItems where Requestor <> "" ORDER BY Requestor ASC';
    $r_req = mysqli_query($link, $q_req, $dbc);

    $option_block_req .= "<option value='All'>All</option>";
    while ($options = mysqli_fetch_array($r_req)) {
        if ($options[0] != $requestor_selected) {
            $option_block_req .= "<option value='$options[0]'>$options[0]</option>";
        } else {
            $option_block_req .= "<option value='$options[0]' selected = 'selected'>$options[0]</option>";
        }
    }
    echo $option_block_req;
    echo '</select>';



    echo "<b>Rx From:</b><select name='RxFrom' onChange='submit()'>";
    $q_rxfrom = 'select distinct(RxFrom) from ReceivedItems where RxFrom <> "" ORDER BY RxFrom ASC';
    $r_rxfrom = mysqli_query($link, $q_rxfrom, $dbc);

    $option_block_rxfrom .= "<option value='All'>All</option>";
    while ($options = mysqli_fetch_array($r_rxfrom)) {
        if ($options[0] != $RxFrom_selected) {
            $option_block_rxfrom .= "<option value='$options[0]'>$options[0]</option>";
        } else {
            $option_block_rxfrom .= "<option value='$options[0]' selected = 'selected'>$options[0]</option>";
        }
    }
    echo $option_block_rxfrom;
    echo '</select>';
    echo '

<br><br>
PO: <input type="text" name="POsearch" value = "' . $POsearch . '" />
<input type="submit" value="POsearch" />
<br></form><br>';

    $RecordsTable = new RecordsTable_class();
    $RecordsTable->Initialize(1000);
    $RecordsTable->Requestor = $requestor_selected;
    $RecordsTable->RxFrom = $RxFrom_selected;
    $RecordsTable->POsearch = $POsearch;

    $RecordsTable->Color1 = "#98c4f0"; //blue
    $RecordsTable->Color1_2 = "#D9EEFF"; //light blue
    $RecordsTable->Color2 = "#ffb521"; //orange
    $RecordsTable->Color2_2 = "#fffab2"; //light orange
    $RecordsTable->Color3 = "#32ed41"; //green
    $RecordsTable->Color3_2 = "#C9FEC7"; //light green

    if ($showLegend == '1') {
        echo "<br><br>";
        $RecordsTable->ShowLegend();
        echo "<br><br>";
    }

    if ((isset($_REQUEST['Requestor'])) & (isset($_REQUEST['RxFrom']))) {
        if ($_REQUEST['Requestor'] == "All") {
            $requestor_selected = "%";
        }
        if ($_REQUEST['RxFrom'] == "All") {
            $RxFrom_selected = "%";
        }
        $RecordsTable->OrderByString = " WHERE PO LIKE '$POsearch' AND
								Requestor LIKE '$requestor_selected' 
								
								AND RxFrom LIKE '$RxFrom_selected' $RecordsTable->OrderByString";
    }

    $datatype = "allrx";
    include('save_to_csvfile_form.php');
    $RecordsTable->TableHeader();
    $RecordsTable->TableRows();
    $RecordsTable->TableFooter();

    echo "</div>";
    echo "<div align = 'center'>";
    $RecordsTable->DisplayPageNumbers();
    echo "</div>";
    include('footer.php');

    ?>