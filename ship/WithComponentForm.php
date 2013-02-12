<table style="border: solid 1px #000; background: #EEF3E2;"
    width="100%">
      <tr style="border: solid 1px #000;" align="center">
        <td>Select Component Type</td>

        <td>Select Component</td>
      </tr>

      <tr>
        <td>
			<select name="widgetcat" id="widgetcat" onchange="getwidgets(this.value)">
				<option value="-- All --">---------</option>
        	</select>
		</td>
<?php
//$WithCompStr = $fkWithComponent . ', ' . $WithCompDetails;


$WithCompStr = $WithCompDetails;
//echo '<script language="javascript">window.alert("' . $WithCompStr . '");</script>';
echo '
        <td>
			<select name="widgetid" id="widgetid">';
          		//echo '<option value= "' . $WithCompStr . '">' . $WithCompStr . '</option>';
          		
			echo '<option value= "' . $fkWithComponent . '">' . $WithCompStr . '</option>';
echo '
        	</select>
		</td>
      </tr>
</table>';
?>    

  <script type="text/javascript">
  		
                init_widgets();
  </script>




