<?php 
echo '
<form name = "ExportCSVButton" action= "save_to_csv.php" method="get">
<input type="image" src="pics/exportcsvbutton.bmp" name="submit" value="EXPORT TO CSV FILE" /></p>';

echo "<input type='hidden' name='qShipItems' value='" . $qShipItems . "' />";
echo '<input type="hidden" name="id" value="' . $id . '" />';
echo '<input type="hidden" name="datatype" value="' . $datatype . '" />';
echo '<input type="hidden" name="requestor_selected" value="' . $requestor_selected . '" />';
echo '<input type="hidden" name="RxFrom_selected" value="' . $RxFrom_selected . '" />';



echo '</form>';
?>