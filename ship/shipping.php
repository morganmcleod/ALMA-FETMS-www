<?php
$page_title = 'FEIC Shipment Tracking Application';
include ('header.php');

echo'<h1>Shipping</h1>';
?>


<br><br>
<a href="shippingchecklist.php"><font size="+1" color = "#000099">
<img src="pics/createsiclbutton.bmp">
</font></a>
<br><br>

<a href="add_record_shipment.php"><font size="+1" color = "#000099">
<img src="pics/createshipmentbutton.bmp">
</font></a>
<br><br>

<a href="view_shipments_sort.php"><font size="+1" color = "#000099">
<img src="pics/browseshipmentsbutton.bmp">
</font></a>
<br><br>

<a href="view_shipitems_sort.php?initial=1"><font size="+1" color = "#000099">
<img src="pics/browseshippeditemsbutton.bmp">
</font></a>
<br><br>

<?php
include ('footer.php');
?>