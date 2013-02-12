<?php
class IFswitch_output_cable extends GenericTable {
	var $amp_data_file;
	var $s11_data_file;
	var $s22_data_file;
	
	var $plot_command_file;
	
	public function Initialize_IFcable($keyId){
		parent::Initialize("IFswitch_output_cables",$keyId);
	}
	
	public function RequestValues_IFCable(){
		parent::RequestValues();
		
		if (isset($_REQUEST['deleterecord_forsure'])){
				$this->DeleteRecord_IFcable();
		}

		
		if (isset($_REQUEST['submit_datafile'])){
			if (isset($_FILES['ifdatafile_s21']['name'])){
				if ($_FILES['ifdatafile_s21']['name'] != ""){
					$this->Upload_datafile("s21",$_FILES['ifdatafile_s21']['tmp_name']);
					$this->CalculateValues();
					$this->GeneratePlot("s21");
				}
			}
			if (isset($_FILES['ifdatafile_s22']['name'])){
				if ($_FILES['ifdatafile_s22']['name'] != ""){
					$this->Upload_datafile("s22",$_FILES['ifdatafile_s22']['tmp_name']);
					$this->CalculateValues();
					$this->GeneratePlot("s22");
				}
			}
			if (isset($_FILES['ifdatafile_s11']['name'])){
				if ($_FILES['ifdatafile_s11']['name'] != ""){
					$this->Upload_datafile("s11",$_FILES['ifdatafile_s11']['tmp_name']);
					$this->CalculateValues();
					$this->GeneratePlot("s11");
				}
			}
			if (isset($_FILES['VNA_image']['name'])){
				if ($_FILES['VNA_image']['name'] != ""){
					$target_path = "files/";
					$newimage_name = date("Ymd_G_i_s") . "_" . basename( $_FILES['VNA_image']['name']);
					$target_path = $target_path . $newimage_name; 
					$this->SetValue('VNA_image',"//www.cv.nrao.edu/php-internal/ntc/Tasks/DatabaseUI/www/IFcables/files/$newimage_name");
					
					move_uploaded_file($_FILES['VNA_image']['tmp_name'], $target_path);
					
					
				}
			}

			$this->Update_IFcable();
		}	

	}
	
	public function DisplayData_IFcable(){
		include('mysql_connect.php');
		echo "<br><font size='+2'><b><u>IF Switch Output Cable</u></b></font>";
		echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
		
			
		if (strtolower(parent::GetValue('PassFail')) == "pass"){
				echo '<table align="left" cellspacing="2" cellpadding="2" bgcolor = "#33cc00" width="1%" >';
			}
			if (strtolower(parent::GetValue('PassFail')) == "fail"){
				echo '<table align="left" cellspacing="2" cellpadding="2" bgcolor = "#ff0000" width="1%" >';
			}
			echo"<tr><td>";
			echo "<select name='PassFail' onChange = submit()>";
				if (parent::GetValue('PassFail')=="pass"){
					$option_pf = "<option value='pass' selected = 'selected'>PASS</option>";
					$option_pf .= "<option value='fail'>FAIL</option>";
				}
				else{
					$option_pf = "<option value='pass'>PASS</option>";
					$option_pf .= "<option value='fail' selected = 'selected'>FAIL</option>";
				}
				echo $option_pf;
			echo "</select><br>";
		
			echo "</td></tr></table><br><br>";
		
		
		
			echo "<div style ='width:100%;height:30%'>";
			echo "<div align='left' style ='width:50%;height:30%'>";
			

			

			echo "<br>SN: <input type='text' name='SN' size='5' 
					maxlength='200' value = '".$this->GetValue('SN')."'>";
			
			echo "Cable Type: ";
			echo "<select name='cabletype'>";
				if (parent::GetValue('cabletype')=="short"){
					$option_cabletype = "<option value='short' selected = 'selected'>Short</option>";
					$option_cabletype .= "<option value='long'>Long</option>";
				}
				else{
					$option_cabletype = "<option value='short'>Short</option>";
					$option_cabletype .= "<option value='long' selected = 'selected'>Long</option>";
				}
				echo $option_cabletype;
			echo "</select><br><br>";
			
			
			
			
			
			echo '<table align="right" cellspacing="1" cellpadding="3" width="120%" >';
			echo "<tr><td>";

			echo "Measured Insertion Loss (dB)<br>";
			echo '<table align="left" cellspacing="1" cellpadding="3" width="80%" bgcolor="#000000">';
			echo '<tr bgcolor="#ffff66">
					<td><b>4 GHz</b></td>
					<td><b>8 GHz</b></td>
					<td><b>12 GHz</b></td>
					<td><b>18 GHz</b></td>
			    </tr>';
			echo '<tr bgcolor="#ffffff">
					<td>
						'.$this->GetValue('MeasIL_4GHz').'
					</td>
					<td>
						'.$this->GetValue('MeasIL_8GHz').'
					</td>
					<td>
						'.$this->GetValue('MeasIL_12GHz').'
					</td>
					<td>
						'.$this->GetValue('MeasIL_18GHz').'
					</td>

					
						
				</tr>';
			echo "</table><br>";
			
			echo "<br><br><br>Manufacturer Supplied Insertion Loss (dB)<br>";
			echo '<table align="left" cellspacing="1" cellpadding="3" width="50%" bgcolor="#000000">';
			echo '<tr bgcolor="#ffff66">
					<td><b>4 GHz</b></td>
					<td><b>8 GHz</b></td>
					<td><b>12 GHz</b></td>
					<td><b>18 GHz</b></td>
			    </tr>';
			echo '<tr bgcolor="#ffffff">
					<td>
						<input type="text" name="MfgIL_4GHz" size="5" 
						maxlength="200" value = "'.$this->GetValue('MfgIL_4GHz').'">
					</td>
					<td>
						<input type="text" name="MfgIL_8GHz" size="5" 
						maxlength="200" value = "'.$this->GetValue('MfgIL_8GHz').'">
					</td>
					<td>
						<input type="text" name="MfgIL_12GHz" size="5" 
						maxlength="200" value = "'.$this->GetValue('MfgIL_12GHz').'">
					</td>
					<td>
						<input type="text" name="MfgIL_18GHz" size="5" 
						maxlength="200" value = "'.$this->GetValue('MfgIL_18GHz').'">
					</td>					
				</tr>';
			echo "</table>";
			
			echo "</td><td>";
			
			
		
			echo "</td>
			
			<td>";
			echo "Measured VSWR (Average 4-12 GHz)<br>";
			echo '<table align="left" cellspacing="1" cellpadding="3" width="50%" bgcolor="#000000">';
			echo '<tr bgcolor="#ffff66">
					<td><b>BMA End</b></td>
					<td><b>SMP End</b></td>
			    </tr>';
			echo '<tr bgcolor="#ffffff">
					<td>
						'.$this->GetValue('VSWR_Meas_BMAend').'
					</td>
					<td>
						'.$this->GetValue('VSWR_Meas_SMPend').'
					</td>				
				</tr>';			
			echo "</table><br><br><br><br>";	
			
			echo "Manufacturer Supplied VSWR<br>";
			echo '<table align="left" cellspacing="1" cellpadding="3" width="100%" bgcolor="#000000">';
			echo '<tr bgcolor="#ffff66">
					<td><b>4 GHz</b></td>
					<td><b>8 GHz</b></td>
					<td><b>12 GHz</b></td>
					<td><b>18 GHz</b></td>
			    </tr>';
			echo '<tr bgcolor="#ffffff">
					<td>
						<input type="text" name="VSWR_Mfg_4GHz" size="5" 
						maxlength="200" value = "'.$this->GetValue('VSWR_Mfg_4GHz').'">
					</td>
					<td>
						<input type="text" name="VSWR_Mfg_8GHz" size="5" 
						maxlength="200" value = "'.$this->GetValue('VSWR_Mfg_8GHz').'">
					</td>	
					<td>
						<input type="text" name="VSWR_Mfg_12GHz" size="5" 
						maxlength="200" value = "'.$this->GetValue('VSWR_Mfg_12GHz').'">
					</td>
					<td>
						<input type="text" name="VSWR_Mfg_18GHz" size="5" 
						maxlength="200" value = "'.$this->GetValue('VSWR_Mfg_18GHz').'">
					</td>				
				</tr>';			
			echo "</table><br>";
			echo "</td>";
			echo "</table></div></div><br><br><br>";
			
			echo'Notes<br><textarea name = "Notes" rows="3" cols="60">';
			echo parent::GetValue('Notes');
			echo '</textarea><br>';
			
			
			echo "<br><b>Insertion Loss (slope): ".$this->GetValue('InsertionLoss') . "</b>";
			
			
			
			
			
	
			echo "<br><br><img src='".$this->GetValue('Amp_plot')."'>";
			echo "<br><br><img src='".$this->GetValue('s22_plot')."'>";
			echo "<br><br><img src='".$this->GetValue('s11_plot')."'>";
			echo "<br><br><img src='".$this->GetValue('VNA_image')."'>";
			
			$keyId = $this->GetValue('keyId');
			$tablename = $this->GetValue('tablename');
			
			echo "<input type='hidden' name='keyId' value='$keyId'>";
			echo "<input type='hidden' name='tablename' value='$tablename'>";
			echo "<br><br><input type='submit' name = 'submitted' value='SAVE CHANGES'>";
			echo "<input type='submit' name = 'deleterecord' value='DELETE RECORD'>";
			echo "</div></div>";	
		echo "</form>";	
		@mysql_close($dbc);
		
		if (parent::GetValue('keyId') != '0'){
			$this->Display_uploadform();
		}
		
	}
	
	public function Display_uploadform(){
		echo '
		<p><div style="width:700px;height:80px; align = "left"></p>
		<!-- The data encoding type, enctype, MUST be specified as below -->
		<form enctype="multipart/form-data" action="' . $PHP_SELF . '" method="POST">
		    <!-- MAX_FILE_SIZE must precede the file input field -->
		    <!-- <input type="hidden" name="MAX_FILE_SIZE" value="30000" /> -->
		    <!-- Name of input element determines name in $_FILES array -->
		    <br>
		    <font size="+1"><b><u>IF Cable data files</u></b></font><br>
		    S21 (Frequency, Amplitude): </b><input name="ifdatafile_s21" type="file" /><br>
		    S11 (Frequency, SWR SMA End): </b><input name="ifdatafile_s11" type="file" /><br>
		    S22 (Frequency, SWR BMP End): </b><input name="ifdatafile_s22" type="file" /><br>
		    VNA Image: </b><input name="VNA_image" type="file" /><br>
		    <input type="submit" name= "submit_datafile" value="Submit" />
		</form>
		</div>';
	}
	
	public function Upload_datafile($data_type, $datafile_name){
		include('mysql_connect.php');
		$keyId = $this->GetValue('keyId');
		$if_filecontents = file($datafile_name);
		
		$qDelete = "DELETE FROM IFswitch_output_cable_data
					WHERE fk_cable = $keyId
					AND data_type='$data_type';";
		$rDelete = @mysql_query ($qDelete, $dbc); 
		
		for($i=0; $i<sizeof($if_filecontents); $i++) { 
		      $line_ifdata = trim($if_filecontents[$i]);
		      $IFArray   = explode(",", $line_ifdata); 

		      if (is_numeric($IFArray[0]) == true){  
		      	  $temp_frequency      = $IFArray[0];
			      $temp_data           = $IFArray[1];

			    if ($temp_frequency < 1000){  	
			    	$qInsert = "INSERT INTO IFswitch_output_cable_data
			    	(fk_cable, frequency, meas_data, data_type) 
			    	VALUES ('$keyId','$temp_frequency','$temp_data', '$data_type')";	
			    }
			    else{
			    	$qInsert = "INSERT INTO IFswitch_output_cable_data
			    	(fk_cable, frequency, meas_data, data_type) 
			   	    VALUES ('$keyId','".($temp_frequency/1.0e9)."','$temp_data', '$data_type')";
			    }
				$rInsert = @mysql_query ($qInsert, $dbc); 
		      }     
		}
		@mysql_close($dbc);
		unlink($datafile_name);	
		fclose($if_filecontents);	
	}

	public function CalculateValues(){
		include('mysql_connect.php');
		$fk_cable=$this->GetValue('keyId');
		
		
		$qCV="SELECT AVG(meas_data) FROM IFswitch_output_cable_data 
		WHERE fk_cable=$fk_cable
		AND data_type = 's11'
		AND frequency <= 4e9 
		AND frequency <= 12e9;";
		$rCV=@mysql_query($qCV,$dbc);
		parent::SetValue('VSWR_Meas_BMAend',@mysql_result($rCV,0));
	
		$qCV="SELECT AVG(meas_data) FROM IFswitch_output_cable_data 
		WHERE fk_cable=$fk_cable
		AND data_type = 's22'
		AND frequency <= 4e9 
		AND frequency <= 12e9;";
		$rCV=@mysql_query($qCV,$dbc);
		parent::SetValue('VSWR_Meas_SMPend',@mysql_result($rCV,0));
		

		$q_slope='
		SELECT
		@n := COUNT(meas_data) AS N,
		@meanX := AVG(frequency) AS "X mean",
		@sumX := SUM(frequency) AS "X sum",
		@sumXX := SUM(frequency*frequency) AS "X sum of squares",
		@meanY := AVG(meas_data) AS "Y mean",
		@sumY := SUM(meas_data) AS "Y sum",
		@sumYY := SUM(meas_data*meas_data) AS "Y sum of square",
		@sumXY := SUM(frequency*meas_data) AS "X*Y sum"
		FROM IFswitch_output_cable_data
		WHERE frequency >= 4 AND
		frequency <= 12 AND
		data_type="s21" AND
		fk_cable = '.$fk_cable.';';
		
		$r_slope=@mysql_query($q_slope,$dbc);
		$res=@mysql_fetch_array($r_slope);

		$N          =$res[0];
		$Xmean      =$res[1];
		$Xsum       =$res[2];
		$Xsumsquares=$res[3];
		$Ymean      =$res[4];
		$Ysum       =$res[5];
		$Ysumsquares=$res[6];
		$XYsum      =$res[7];

		$slope = round(($N * $XYsum - $Xsum*$Ysum) / ($N * $Xsumsquares - $Xsum * $Xsum),4);
		$intercept = round($Ymean - ($slope * $Xmean),4);
		
		parent::SetValue('InsertionLoss',$slope);
		parent::SetValue('Yintercept',$intercept);
		
		if ($intercept < 0){
			$LinearEquation = "y = " . $slope . "x " . $intercept;
		}
	    else {
			$LinearEquation = "y = " . $slope . "x + " . $intercept;
		}

		parent::SetValue('LinearEquation',$LinearEquation);
		
		
		//Get Insertion Loss (dB at 4, 8, 12, 18 GHz) for s22
		
		
		$qIL = "SELECT meas_data FROM IFswitch_output_cable_data 
		WHERE fk_cable=$this->keyId
		AND data_type = 's21'
		AND frequency >= 3.9
		AND frequency <= 4.1
		ORDER BY frequency DESC
		LIMIT 1;";
		$rIL=@mysql_query($qIL,$dbc);
		parent::SetValue('MeasIL_4GHz',abs(round(@mysql_result($rIL,0),4)));
		
		$qIL = "SELECT meas_data FROM IFswitch_output_cable_data 
		WHERE fk_cable=$this->keyId
		AND data_type = 's21'
		AND frequency >= 7.9
		AND frequency <= 8.1
		ORDER BY frequency DESC
		LIMIT 1;";
		$rIL=@mysql_query($qIL,$dbc);
		parent::SetValue('MeasIL_8GHz',abs(round(@mysql_result($rIL,0),4)));
		
		$qIL = "SELECT meas_data FROM IFswitch_output_cable_data 
		WHERE fk_cable=$this->keyId
		AND data_type = 's21'
		AND frequency >= 11.9
		AND frequency <= 12.1
		ORDER BY frequency DESC
		LIMIT 1;";
		$rIL=@mysql_query($qIL,$dbc);
		parent::SetValue('MeasIL_12GHz',abs(round(@mysql_result($rIL,0),4)));
		
		
		$qIL = "SELECT meas_data FROM IFswitch_output_cable_data 
		WHERE fk_cable=$this->keyId
		AND data_type = 's21'
		AND frequency >= 17.9
		AND frequency <= 18.1
		ORDER BY frequency DESC
		LIMIT 1;";
		$rIL=@mysql_query($qIL,$dbc);
		parent::SetValue('MeasIL_18GHz',abs(round(@mysql_result($rIL,0),4)));
		
		

		$this->Determine_PassFail();
		
		
		@mysql_close($dbc);
	}
	
	public function Determine_PassFail(){
		
		parent::SetValue('PassFail','pass');
		
		//Determine Pass/Fail if "short"
		if (parent::GetValue('cabletype') == "short"){
			//If ((float)parent::GetValue('InsertionLoss') >= 0.08){
				//parent::SetValue('PassFail','fail');
			//}
			If ((float)parent::GetValue('MeasIL_4GHz') > 1.4){
				parent::SetValue('PassFail','fail');
			}
			If ((float)parent::GetValue('MeasIL_8GHz') > 1.4){
				parent::SetValue('PassFail','fail');
			}
			If ((float)parent::GetValue('MeasIL_12GHz') > 1.4){
				parent::SetValue('PassFail','fail');
			}	

			If ((float)parent::GetValue('MfgIL_4GHz') > 1.4){
				parent::SetValue('PassFail','fail');
			}
			If ((float)parent::GetValue('MfgIL_8GHz') > 1.4){
				parent::SetValue('PassFail','fail');
			}
			If ((float)parent::GetValue('MfgIL_12GHz') > 1.4){
				parent::SetValue('PassFail','fail');
			}	
	
			If ((float)parent::GetValue('Avg_SWR_BMA') > 1.2){
				parent::SetValue('PassFail','fail');
			}
			If ((float)parent::GetValue('Avg_SWR_SMP') > 1.2){
				parent::SetValue('PassFail','fail');
			}		
			If ((float)parent::GetValue('VSWR_Mfg_4GHz') > 1.2){
				parent::SetValue('PassFail','fail');
			}
			If ((float)parent::GetValue('VSWR_Mfg_8GHz') > 1.2){
				parent::SetValue('PassFail','fail');
			}
			If ((float)parent::GetValue('VSWR_Mfg_12GHz') > 1.2){
				parent::SetValue('PassFail','fail');
			}

		}

		//Determine Pass/Fail if "long"
		if (parent::GetValue('cabletype') == "long"){
			//If ((float)parent::GetValue('InsertionLoss') >= 0.18){
				//parent::SetValue('PassFail','fail');
			//}
			If ((float)parent::GetValue('MeasIL_4GHz') > 3.6){
				parent::SetValue('PassFail','fail');
			}
			If ((float)parent::GetValue('MeasIL_8GHz') > 3.6){
				parent::SetValue('PassFail','fail');
			}
			If ((float)parent::GetValue('MeasIL_12GHz') > 3.6){
				parent::SetValue('PassFail','fail');
			}	

			If ((float)parent::GetValue('MfgIL_4GHz') > 3.6){
				parent::SetValue('PassFail','fail');
			}
			If ((float)parent::GetValue('MfgIL_8GHz') > 3.6){
				parent::SetValue('PassFail','fail');
			}
			If ((float)parent::GetValue('MfgIL_12GHz') > 3.6){
				parent::SetValue('PassFail','fail');
			}	
		
			If ((float)parent::GetValue('Avg_SWR_BMA') > 1.2){
				parent::SetValue('PassFail','fail');
			}
			If ((float)parent::GetValue('Avg_SWR_SMP') > 1.2){
				parent::SetValue('PassFail','fail');
			}		
			If ((float)parent::GetValue('VSWR_Mfg_4GHz') > 1.2){
				parent::SetValue('PassFail','fail');
			}
			If ((float)parent::GetValue('VSWR_Mfg_8GHz') > 1.2){
				parent::SetValue('PassFail','fail');
			}
			If ((float)parent::GetValue('VSWR_Mfg_12GHz') > 1.2){
				parent::SetValue('PassFail','fail');
			}

		}
		
		
		
	}

	public function Write_S21_DataFile(){
		include('mysql_connect.php');
		$fk_cable = parent::GetValue('keyId');
		$this->amp_data_file = "/home/www.cv.nrao.edu/active/php-internal/ntc/Tasks/DatabaseUI/www/IFcables/files/amp_data.txt";
		unlink($this->amp_data_file);
		
		$q_amp = "SELECT frequency, meas_data FROM IFswitch_output_cable_data
				WHERE fk_cable = $fk_cable 
				AND data_type = 's21'
				ORDER BY frequency ASC;";
		$r_amp = @mysql_query($q_amp,$dbc);

		$fh = fopen($this->amp_data_file, 'w');

		while($row_amp = @mysql_fetch_array($r_amp)){
			$stringData = "$row_amp[0]\t$row_amp[1]\r\n";
			fwrite($fh, $stringData);
		}

		fclose($fh);
	}
	public function Write_S22_DataFile(){
		include('mysql_connect.php');
		$fk_cable = parent::GetValue('keyId');
		$this->s22_data_file = "/home/www.cv.nrao.edu/active/php-internal/ntc/Tasks/DatabaseUI/www/IFcables/files/s22_data.txt";
		unlink($this->s22_data_file);
		
		$q_amp = "SELECT frequency, meas_data FROM IFswitch_output_cable_data
				WHERE fk_cable = $fk_cable 
				AND data_type = 's22'
				ORDER BY frequency ASC;";
		$r_amp = @mysql_query($q_amp,$dbc);

		$fh = fopen($this->s22_data_file, 'w');

		while($row_amp = @mysql_fetch_array($r_amp)){
			$stringData = "$row_amp[0]\t$row_amp[1]\r\n";
			fwrite($fh, $stringData);
		}
		fclose($fh);
	}
	public function Write_S11_DataFile(){
		include('mysql_connect.php');
		$fk_cable = parent::GetValue('keyId');
		$this->s11_data_file = "/home/www.cv.nrao.edu/active/php-internal/ntc/Tasks/DatabaseUI/www/IFcables/files/s11_data.txt";
		unlink($this->s11_data_file);
		
		$q_amp = "SELECT frequency, meas_data FROM IFswitch_output_cable_data
				WHERE fk_cable = $fk_cable 
				AND data_type = 's11'
				ORDER BY frequency ASC;";
		$r_amp = @mysql_query($q_amp,$dbc);

		$fh = fopen($this->s11_data_file, 'w');

		while($row_amp = @mysql_fetch_array($r_amp)){
			$stringData = "$row_amp[0]\t$row_amp[1]\r\n";
			fwrite($fh, $stringData);
		}
		fclose($fh);
	}
		
	
	
	
	public function Write_s21_CommandFile(){
		$this->plot_command_file = "/home/www.cv.nrao.edu/active/php-internal/ntc/Tasks/DatabaseUI/www/IFcables/files/command.txt";
		unlink($this->plot_command_file);
		$imagedirectory = "/home/www.cv.nrao.edu/active/php-internal/ntc/Tasks/DatabaseUI/www/IFcables/files/";
		$imagename = "Cable_SN" . $this->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";  
		$this->SetValue('Amp_plot',"//www.cv.nrao.edu/php-internal/ntc/Tasks/DatabaseUI/www/IFcables/files/$imagename");
		$imagepath = $imagedirectory . $imagename;
		$plot_title = "IF Cable SN " . $this->GetValue('SN');
		$fk_cable = parent::GetValue('keyId');
		$fh = fopen($this->plot_command_file, 'w');
		fwrite($fh, "set terminal png\r\n");
		fwrite($fh, "set output '$imagepath'\r\n");
		fwrite($fh, "set title '$plot_title'\r\n");
		fwrite($fh, "set grid\r\n");
		fwrite($fh, "set xlabel 'Frequency (GHz)'\r\n");
		fwrite($fh, "set ylabel 'Amplitude (dB)'\r\n");
		$m=$this->GetValue('InsertionLoss');
		$b=$this->GetValue('Yintercept');
		$f_x = str_replace("x","*x",$this->GetValue('LinearEquation'));
		$f_x = str_replace("y","f(x)",$f_x);
		fwrite($fh, "$f_x\r\n");
		fwrite($fh, "plot '$this->amp_data_file' using 1:2 title 'Amplitude (dB)' with points pointsize 0.7 pt 5 lt 3,f(x) lt 1 lw 3 title '" .$this->GetValue('LinearEquation') . "  \r\n");
		fclose($fh);	
	}
	public function Write_s22_CommandFile(){
		$this->plot_command_file = "/home/www.cv.nrao.edu/active/php-internal/ntc/Tasks/DatabaseUI/www/IFcables/files/command.txt";
		unlink($this->plot_command_file);
		$imagedirectory = "/home/www.cv.nrao.edu/active/php-internal/ntc/Tasks/DatabaseUI/www/IFcables/files/";
		$imagename = "Cable_SN_s22_" . $this->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";  
		$this->SetValue('s22_plot',"//www.cv.nrao.edu/php-internal/ntc/Tasks/DatabaseUI/www/IFcables/files/$imagename");
		$imagepath = $imagedirectory . $imagename;
		$plot_title = "IF Cable SN " . $this->GetValue('SN');
		$fk_cable = parent::GetValue('keyId');
		$fh = fopen($this->plot_command_file, 'w');
		fwrite($fh, "set terminal png\r\n");
		fwrite($fh, "set output '$imagepath'\r\n");
		fwrite($fh, "set title '$plot_title'\r\n");
		fwrite($fh, "set grid\r\n");
		fwrite($fh, "set xlabel 'Frequency (GHz)'\r\n");
		fwrite($fh, "set ylabel 'VSWR'\r\n");
		fwrite($fh, "plot '$this->s22_data_file' using 1:2 title 'VSWR S22 (BMP End)' with lines\r\n\r\n");
		fclose($fh);	
	}
	public function Write_s11_CommandFile(){
		$this->plot_command_file = "/home/www.cv.nrao.edu/active/php-internal/ntc/Tasks/DatabaseUI/www/IFcables/files/command.txt";
		unlink($this->plot_command_file);
		$imagedirectory = "/home/www.cv.nrao.edu/active/php-internal/ntc/Tasks/DatabaseUI/www/IFcables/files/";
		$imagename = "Cable_SN_s11_" . $this->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";  
		$this->SetValue('s11_plot',"//www.cv.nrao.edu/php-internal/ntc/Tasks/DatabaseUI/www/IFcables/files/$imagename");
		$imagepath = $imagedirectory . $imagename;
		$plot_title = "IF Cable SN " . $this->GetValue('SN');
		$fk_cable = parent::GetValue('keyId');
		$fh = fopen($this->plot_command_file, 'w');
		fwrite($fh, "set terminal png\r\n");
		fwrite($fh, "set output '$imagepath'\r\n");
		fwrite($fh, "set title '$plot_title'\r\n");
		fwrite($fh, "set grid\r\n");
		fwrite($fh, "set xlabel 'Frequency (GHz)'\r\n");
		fwrite($fh, "set ylabel 'VSWR'\r\n");
		fwrite($fh, "plot '$this->s11_data_file' using 1:2 title 'VSWR S11 (SMA End)' with lines\r\n\r\n");
		fclose($fh);	
	}
	
	
	
	public function GeneratePlot($plot_type){
		$GNUPLOT = '/usr/bin/gnuplot';  
		
		switch ($plot_type) {
	    	case "s21":
				$this->Write_S21_DataFile();
				$this->Write_s21_CommandFile();
	        	break;
	        case "s22":
				$this->Write_S22_DataFile();
				$this->Write_s22_CommandFile();
	        	break;
	        case "s11":
				$this->Write_S11_DataFile();
				$this->Write_s11_CommandFile();
	        	break;
		}

		$CommandString = "$GNUPLOT $this->plot_command_file";
		system($CommandString);
	}
	
	public function Update_IFcable(){	
		//$this->Determine_PassFail();
		parent::Update();
		$keyId = $this->GetValue('keyId');
		echo '<meta http-equiv="Refresh" content="1;url=http://www.cv.nrao.edu/php-internal/ntc/Tasks/DatabaseUI/www/IFcables/ifcable.php?keyId='.$keyId.'">';
	}
	
	public function NewRecord_IFcable(){
		parent::NewRecord("IFswitch_output_cables");
	}
	
	public function DeleteRecord_IFcable(){
		include('mysql_connect.php');
		$qdelete = "DELETE FROM IFswitch_output_cable_data WHERE fk_cable = ".parent::GetValue('keyId').";";
		$rdelete = @mysql_query($qdelete,$dbc);
		echo '<meta http-equiv="Refresh" content="1;url=http://www.cv.nrao.edu/php-internal/ntc/Tasks/DatabaseUI/www/IFcables/ifcable_list.php">';
	}
	
	
}



?>