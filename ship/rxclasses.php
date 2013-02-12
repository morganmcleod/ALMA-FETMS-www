<?php
class ReceivedItem_class{
	
	var $keyId;
	var $AssignedLocation;
	var $Staging;
	var $LogNumber;
	var $TS;
	var $Requestor;
	var $reqselect;
	var $PO;
	var $ContentDescription;
	var $Qty;
	var $RxFrom;
	var $NR;
	var $PAS;
	var $VI;
	var $Damage;
	var $PackingList_SOW;
	var $Discrepancy;
	var $TIR;
	var $TIComplete;
	var $LoadedIntoInventory;
	var $Damage_Specific;
	var $Damage_ActionTaken;
	var $Damage_RMANCR;
	var $Damage_TrackingNumber;
	var $Damage_NR;
	var $Discrepancy_Specific;
	var $Discrepancy_ActionTaken;
	var $Discrepancy_RMANCR;
	var $Discrepancy_NRAOShippingRequestNumber;
	var $Discrepancy_TrackingNumber;
	var $Discrepancy_NR;
	var $PAS_url;
	var $Status;
	var $Status_description;
	var $logletter;
	var $BeingEdited;
	var $rxfromselect;
	
	var $Password;
	var $CorrectPassword;

	
	
	function Initialize($in_keyId){
		include ('mysql_connect.php');
		
		$qInit = "SELECT 
		LogNumber,RxDate,Requestor,
		PO,ContentDescription,Qty,
		RxFrom,NR,PAS,
		VI,Damage,PackingList_SOW,
		Discrepancy,Staging,TIR,
		TIComplete,LoadedIntoInventory,AssignedLocation,
		Damage_Specific,Damage_ActionTaken,Damage_RMANCR,
		Damage_TrackingNumber,Damage_NR,Discrepancy_Specific,
		Discrepancy_ActionTaken,Discrepancy_RMANCR,Discrepancy_NRAOShippingRequestNumber,
		Discrepancy_TrackingNumber,Discrepancy_NR,PAS_url,Status
		FROM ReceivedItems 
		WHERE keyId = $in_keyId;";
		
		
		
		$rInit = @mysql_query($qInit,$dbc);
		$rowInit = @mysql_fetch_array($rInit);
		$this->keyId = $in_keyId;
		$this->LogNumber = $rowInit[0];
		$this->TS = $rowInit[1];
		$this->Requestor = $rowInit[2];
		$this->PO = $rowInit[3];
		$this->ContentDescription = $rowInit[4];
		$this->Qty = $rowInit[5];
		$this->RxFrom = $rowInit[6];
		$this->NR = $rowInit[7];
		$this->PAS = $rowInit[8];
		$this->VI = $rowInit[9];
		$this->Damage = $rowInit[10];
		$this->PackingList_SOW = $rowInit[11];
		$this->Discrepancy = $rowInit[12];
		$this->Staging = $rowInit[13];
		$this->TIR = $rowInit[14];
		$this->TIComplete = $rowInit[15];
		$this->LoadedIntoInventory = $rowInit[16];
		$this->AssignedLocation = $rowInit[17];
		$this->Damage_Specific = $rowInit[18];
		$this->Damage_ActionTaken = $rowInit[19];
		$this->Damage_RMANCR = $rowInit[20];
		$this->Damage_TrackingNumber = $rowInit[21];
		$this->Damage_NR = $rowInit[22];
		$this->Discrepancy_Specific = $rowInit[23];
		$this->Discrepancy_ActionTaken = $rowInit[24];
		$this->Discrepancy_RMANCR = $rowInit[25];
		$this->Discrepancy_NRAOShippingRequestNumber = $rowInit[26];
		$this->Discrepancy_TrackingNumber = $rowInit[27];
		$this->Discrepancy_NR = $rowInit[28];
		$this->PAS_url = $rowInit[29];	
		$this->Status = $rowInit[30];	
	
		
		$this->Status_description = "NOT Received";
		if ($this->Status == "1"){
			$this->Status_description = "Received";
		}
		
		$this->CorrectPassword = "nrao1234";
	}
	
	function UpdateRecord(){
		include ('mysql_connect.php');
		$q = 'UPDATE ReceivedItems SET 
		LogNumber="'.$this->LogNumber.'",
		RxDate="'.$this->TS.'",
		Requestor="'.$this->Requestor.'",
		PO="'.$this->PO.'",
		ContentDescription="'.$this->ContentDescription.'",
		Qty="'.$this->Qty.'",
		RxFrom="'.$this->RxFrom.'",
		NR="'.$this->NR.'",
		PAS="'.$this->PAS.'",
		VI="'.$this->VI.'",
		Damage="'.$this->Damage.'",
		PackingList_SOW="'.$this->PackingList_SOW.'",
		Discrepancy="'.$this->Discrepancy.'",
		Staging="'.$this->Staging.'",
		TIR="'.$this->TIR.'",
		TIComplete="'.$this->TIComplete.'",
		LoadedIntoInventory="'.$this->LoadedIntoInventory.'",
		AssignedLocation = "'.$this->AssignedLocation.'",
		Damage_Specific = "'.$this->Damage_Specific.'",
		Damage_ActionTaken = "'.$this->Damage_ActionTaken.'",
		Damage_RMANCR = "'.$this->Damage_RMANCR.'",
		Damage_TrackingNumber = "'.$this->Damage_TrackingNumber.'",
		Damage_NR = "'.$this->Damage_NR.'",
		Discrepancy_Specific = "'.$this->Discrepancy_Specific.'",
		Discrepancy_ActionTaken = "'.$this->Discrepancy_ActionTaken.'",
		Discrepancy_RMANCR = "'.$this->Discrepancy_RMANCR.'",
		Discrepancy_NRAOShippingRequestNumber = "'.$this->Discrepancy_NRAOShippingRequestNumber.'",
		Discrepancy_TrackingNumber = "'.$this->Discrepancy_TrackingNumber.'",
		Discrepancy_NR = "'.$this->Discrepancy_NR.'",
		PAS_url = "'.$this->PAS_url.'",
		Status = "'.$this->Status.'"
		WHERE keyId="'.$this->keyId.'" LIMIT 1';
		
		
		
		//echo "Query string:<br>";
		//echo $q;
		//echo "<br>";
		
		$r = @mysql_query ($q, $dbc);
	}
	
	function GetMaxNumber($FirstLetter){
		include('mysql_connect.php');
		$qfl = "SELECT max(LogNumber) FROM ReceivedItems 
		WHERE LogNumber LIKE '$FirstLetter-%'
		AND LogNumber <> '';";
	
		$rfl = @mysql_query($qfl,$dbc);
		$tempResult = @mysql_result($rfl,0);
		$tempNum = split("-",$tempResult);
		$newNum = substr("0000" . strval($tempNum[1]+1),-4,4);
		$tempResult = "$FirstLetter-$newNum";
		return $tempResult;
	}
	
	function Display_data(){
		$RecTable = new RecordsTable_class();
		$RecTable->Initialize();
		
		
		echo '<br>
		<a href="edit_record_received_item.php?id=' . $this->keyId . '">
		<img src="pics/editbutton.bmp"></a>
		<a href="delete_record_received_item.php?id=' . $this->keyId . '">
		<img src="pics/deletebutton.bmp"></a>';
		
		//DELIVERY INFORMATION
		echo '
		<table align="center" cellspacing="1" cellpadding="1" width="70%"
		bgcolor="#000000">
		<tr>
		<td align="center" bgcolor="'.$RecTable->Color1.'" colspan = "2"><b>
		<font color="#000000" >DELIVERY INFORMATION</b></td>
		</tr>
		
		<tr><td align="left" bgcolor = "'.$RecTable->Color1_2.'">
		<font color="#000000"><b>
		
		
		
		<tr><td align="left" bgcolor = "'.$RecTable->Color1_2.'" width="40%">
		<font color="#000000"><b>
		LOG#</td>
		<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
		'.$this->LogNumber.'</td></tr>
		
		<tr><td align="left" bgcolor = "'.$RecTable->Color1_2.'">
		<font color="#000000"><b>
		Date</td>
		<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
		'.$this->TS.'</td></tr>
		
		<tr><td align="left" bgcolor = "'.$RecTable->Color1_2.'">
		<font color="#000000"><b>
		Requestor</td>
		<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
		'.$this->Requestor.'</td></tr>
		
		<tr><td align="left" bgcolor = "'.$RecTable->Color1_2.'">
		<font color="#000000"><b>
		PO#</td>
		<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
		'.$this->PO.'</td></tr>
		
		<tr><td align="left" bgcolor = "'.$RecTable->Color1_2.'">
		<font color="#000000"><b>
		Content Description</td>
		<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
		'.$this->ContentDescription.'</td></tr>
		
		<tr><td align="left" bgcolor = "'.$RecTable->Color1_2.'">
		<font color="#000000"><b>
		Qty</td>
		<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
		'.$this->Qty.'</td></tr>
		
		<tr><td align="left" bgcolor = "'.$RecTable->Color1_2.'">
		<font color="#000000"><b>
		Received From</td>
		<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
		'.$this->RxFrom.'</td></tr></table>';
		

		//RX INSPECTION
		echo '
		<table align="center" cellspacing="1" cellpadding="1" width="70%"
		bgcolor="#000000">
		<tr>
		<td align="center" bgcolor="'.$RecTable->Color2.'" colspan = "2"><b>
		<font color="#000000" >RX INSPECTION</b></td>
		</tr>
		
		<tr><td align="left" bgcolor = "'.$RecTable->Color2_2.'">
		<font color="#000000"><b>
		Notified Requestor</td>
		<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
		'.$this->NR.'</td></tr>
		
		<tr><td align="left" bgcolor = "'.$RecTable->Color2_2.'">
		<font color="#000000"><b>
		PAS</td>
		<td align="left" bgcolor = "#ffffff"><b><font color="#000000">';
		
		echo $this->PAS;
	
		echo '
		<tr><td align="left" bgcolor = "'.$RecTable->Color2_2.'" width="40%">
		<font color="#000000"><b>
		Visual Insptection</td>
		<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
		'.$this->VI.'</td></tr>
		
		<tr><td align="left" bgcolor = "'.$RecTable->Color2_2.'">
		<font color="#000000"><b>
		Damage</td>
		<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
		'.$this->Damage.'</td></tr>
		
		<tr><td align="left" bgcolor = "'.$RecTable->Color2_2.'">
		<font color="#000000"><b>
		Packing List/SOW</td>
		<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
		'.$this->PackingList_SOW.'</td></tr>
		
		<tr><td align="left" bgcolor = "'.$RecTable->Color2_2.'">
		<font color="#000000"><b>
		Discrepancy</td>
		<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
		'.$this->Discrepancy.'</td></tr>
		
		<tr><td align="left" bgcolor = "'.$RecTable->Color2_2.'">
		<font color="#000000"><b>
		Technical Inspection Required</td>
		<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
		'.$this->TIR.'</td></tr></table>';
		

		//INVENTORY/STORAGE
		echo '
		<table align="center" cellspacing="1" cellpadding="1" width="70%"
		bgcolor="#000000">
		<tr>
		<td align="center" bgcolor="'.$RecTable->Color3.'" colspan = "2"><b>
		<font color="#000000" >INVENTORY/STORAGE</b></td>
		</tr>
		
		<tr><td align="left" bgcolor = "'.$RecTable->Color3_2.'" width="40%">
		<font color="#000000"><b>
		Staging</td>
		<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
		'.$this->Staging.'</td></tr>
		
		<tr><td align="left" bgcolor = "'.$RecTable->Color3_2.'">
		<font color="#000000"><b>
		Technical Inspection Complete</td>
		<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
		'.$this->TIComplete.'</td></tr>
		
		<tr><td align="left" bgcolor = "'.$RecTable->Color3_2.'">
		<font color="#000000"><b>
		Loaded Into Inventory</td>
		<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
		'.$this->LoadedIntoInventory.'</td></tr>
		
		<tr><td align="left" bgcolor = "'.$RecTable->Color3_2.'">
		<font color="#000000"><b>
		Assigned Location</td>
		<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
		'.$this->AssignedLocation.'</td></tr>
		</table>';
		
		
		if ($this->Damage == "YES"){
			echo '
			<table align="center" cellspacing="1" cellpadding="1" width="70%"
			bgcolor="#000000">
			<tr>
			<td align="center" bgcolor="#FF0000" colspan = "2"><b>
			<font color="#000000" >DAMAGE</b></td>
			</tr>
			
			<tr><td align="left" bgcolor = "#FFDADA" width="40%">
			<font color="#000000"><b>
			Specific Damage</td>
			<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
			'.$this->Damage_Specific.'</td></tr>
			
			<tr><td align="left" bgcolor = "#FFDADA">
			<font color="#000000"><b>
			Action Taken</td>
			<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
			'.$this->Damage_ActionTaken.'</td></tr>
			
			<tr><td align="left" bgcolor = "#FFDADA">
			<font color="#000000"><b>
			RMA#/NCR#</td>
			<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
			'.$this->Damage_RMANCR.'</td></tr>
			
			<tr><td align="left" bgcolor = "#FFDADA">
			<font color="#000000"><b>
			Tracking#</td>
			<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
			'.$this->Damage_TrackingNumber.'</td></tr>
			
			<tr><td align="left" bgcolor = "#FFDADA">
			<font color="#000000"><b>
			Notified Requestor</td>
			<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
			'.$this->Damage_NR.'</td></tr>
			
			</table>';
			
			
		}
		
		if ($this->Discrepancy == "YES"){
			echo '
			<table align="center" cellspacing="1" cellpadding="1" width="70%"
			bgcolor="#000000">
			<tr>
			<td align="center" bgcolor="#FF0000" colspan = "2"><b>
			<font color="#000000" >DISCREPANCY</b></td>
			</tr>
			
			<tr><td align="left" bgcolor = "#FFDADA" width="40%">
			<font color="#000000"><b>
			Specific Discrepancy</td>
			<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
			'.$this->Discrepancy_Specific.'</td></tr>
			
			<tr><td align="left" bgcolor = "#FFDADA">
			<font color="#000000"><b>
			Action Taken</td>
			<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
			'.$this->Discrepancy_ActionTaken.'</td></tr>
			
			<tr><td align="left" bgcolor = "#FFDADA">
			<font color="#000000"><b>
			RMA#/NCR#</td>
			<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
			'.$this->Discrepancy_RMANCR.'</td></tr>
			
			<tr><td align="left" bgcolor = "#FFDADA">
			<font color="#000000"><b>
			NRAO Shipping Request Number</td>
			<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
			'.$this->Discrepancy_NRAOShippingRequestNumber.'</td></tr>
			
			<tr><td align="left" bgcolor = "#FFDADA">
			<font color="#000000"><b>
			Tracking#</td>
			<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
			'.$this->Discrepancy_TrackingNumber.'</td></tr>
			
			<tr><td align="left" bgcolor = "#FFDADA">
			<font color="#000000"><b>
			Notified Requestor</td>
			<td align="left" bgcolor = "#ffffff"><b><font color="#000000">
			'.$this->Discrepancy_NR.'</td></tr></table>';
		}
	}

	public function Display_add_form1(){
		/*
		$RecTable = new RecordsTable_class();
		$RecTable->Initialize();
		
		if ($this->TS == ""){
			$this->TS = date('m-d-Y');
		}
		
		echo '
		<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';

		//DELIVERY INFORMATION
		echo '<br>
		<div style ="width:70%;height:100%;background-color:'.$RecTable->Color1_2.';border:2px solid">
		<h2><font color="#000000">DELIVERY INFORMATION</font></h2>
		<p><b>
	
		Log#: <input type="text" name="LogNumber" size="10" maxlength="10" 
		value="'.$this->LogNumber.'" />
		
		<br><br>Date: <input type="text" name="TS" size="15" maxlength="15" 
		value="'.$this->TS.'" />
		
		<br><br>Requestor: <input type="text" name="Requestor" size="15" maxlength="40" 
		value="'.$this->Requestor.'" />
		
		<br><br>PO#: <input type="text" name="PO" size="15" maxlength="200" 
		value="'.$this->PO.'" />
		
		<br><br>ContentDescription:<br> 
		<textarea rows="5" cols="50" name="ContentDescription" size="60" maxlength="80" 
		/>'.$this->ContentDescription.'</textarea>

		<br><br>Qty: <input type="text" name="Qty" size="3" maxlength="5" 
		value="'.$this->Qty.'" />
		
		<br><br>Received From: <input type="text" name="RxFrom" size="50" maxlength="200" 
		value="'.$this->RxFrom.'" />
		</div>';
		
		//RX INSPECTION
		echo '
		<div style ="width:70%;height:100%;background-color:'.$RecTable->Color2_2.';border:2px solid">
		<h2><font color="#000000">RX INSPECTION</font></h2>
		<p><b>';
		$NR_selector = new Yes_No_NA_selector_class();
		$NR_selector->Display_selector($this->NR,"Notified Requestor?","NR");
		$PAS_selector = new Yes_No_NA_selector_class();
		$PAS_selector->Display_selector($this->PAS,"PAS","PAS");
		
		//echo '<br><br>PAS URL: <input type="text" name="PAS_url" size="30" 
		//maxlength="200" value="'.$this->PAS_url.'" />';
		
		
		$VI_selector = new Yes_No_NA_selector_class();
		$VI_selector->Display_selector($this->VI,"Visual Inspection?","VI");
		$Damage_selector = new Yes_No_NA_selector_class();
		$Damage_selector->Display_selector($this->Damage,"Damaged?","Damage","1");
		$PLSOW_selector = new Yes_No_NA_selector_class();
		$PLSOW_selector->Display_selector($this->PackingList_SOW,"Packing List/SOW?","PackingList_SOW");
		$Discrepancy_selector = new Yes_No_NA_selector_class();
		$Discrepancy_selector->Display_selector($this->Discrepancy,"Discrepancy?","Discrepancy","1");
		
		
		
		
		$TIR_selector = new Yes_No_NA_selector_class();
		$TIR_selector->Display_selector($this->TIR,"Technical Inspection Required?","TIR");
		echo "</div>";
		
		//INVENTORY/STORAGE
		echo '
		<div style ="width:70%;height:100%;background-color:'.$RecTable->Color3_2.';border:2px solid">
		<h2><font color="#000000">INVENTORY/STORAGE</font></h2>
		<p><b>';
		echo '
		Staging: <input type="text" name="Staging" size="30" maxlength="200" 
		value="'. $this->Staging .'" />';
	
		$TIComplete_selector = new Yes_No_NA_selector_class();
		$TIComplete_selector->Display_selector($this->TIComplete,"TI Complete","TIComplete");
		
		$LINV_selector = new Yes_No_NA_selector_class();
		$LINV_selector->Display_selector($this->LoadedIntoInventory,"Loaded into Inventory?","LoadedIntoInventory");
		echo '
		<br><br>Assigned Location: <input type="text" name="AssignedLocation" size="30" maxlength="200" 
		value="'. $this->AssignedLocation .'" /></div>';

		
		
		//Damage information, if applicable
		if ($this->Damage == "YES"){
		echo '
			<div style ="width:70%;height:100%;background-color:#FFDADA;border:2px solid">
			<h2><font color="#FF0000">DAMAGE</font></h2>
			<p><b>
			Specific Damage:<br> 
			<textarea rows="5" cols="50" name="Damage_Specific" size="60" maxlength="80" 
			/>'.$this->Damage_Specific.'</textarea>
			
			<br><br>Action Taken:<br> 
			<textarea rows="5" cols="50" name="Damage_ActionTaken" size="60" maxlength="80" 
			/>'.$this->Damage_ActionTaken.'</textarea>

			<br><br>RMA#/NCR#: <input type="text" name="Damage_RMANCR" size="30" 
			maxlength="200" value="'.$this->Damage_RMANCR.'" />
			<br><br>Tracking#: <input type="text" name="Damage_TrackingNumber" size="30" 
			maxlength="200" value="'.$this->Damage_TrackingNumber.'" />';
			$DamageNR_selector = new Yes_No_NA_selector_class();
			$DamageNR_selector->Display_selector($this->Damage_NR,"Notified Requestor?","Damage_NR");
		echo '</p></b></div>';
		}
		
		//Discrepancy information, if applicable
		if ($this->Discrepancy == "YES"){
		echo '
			<div style ="width:70%;height:100%;background-color:#FFDADA;border:2px solid">
			<h2><font color="#FF0000">DISCREPANCY</font></h2>
			<p><b>
			Specific Discrepancy:<br> 
			<textarea rows="5" cols="50" name="Discrepancy_Specific" size="60" maxlength="80">'.$this->Discrepancy_Specific.'</textarea>
			
			<br><br>Action Taken:<br> 
			<textarea rows="5" cols="50" name="Discrepancy_ActionTaken" size="60" maxlength="80">'
			.$this->Discrepancy_ActionTaken.'</textarea>
			
			<br><br>RMA#/NCR#: <input type="text" name="Discrepancy_RMANCR" size="30" 
			maxlength="200" value="'.$this->Discrepancy_RMANCR.'">
			<br><br>Tracking#: <input type="text" name="Discrepancy_TrackingNumber" size="30" 
			maxlength="200" value="'.$this->Discrepancy_TrackingNumber.'" >
			<br><br>NRAO Shipping Request##: <input type="text" name="Discrepancy_NRAOShippingRequestNumber" size="30" 
			maxlength="200" value="'.$this->Discrepancy_NRAOShippingRequestNumber.'" >';
			$DiscrepancyNR_selector = new Yes_No_NA_selector_class();
			$DiscrepancyNR_selector->Display_selector($this->Discrepancy_NR,"Notified Requestor?","Discrepancy_NR");
		echo '</p></div>';
		}
		//echo '
		//<p><input type="image" src="pics/submit.bmp" name="submitted" value="SUBMIT" /></p></b>';
		
		//echo "<p><a href='".$_SERVER["PHP_SELF"]."?submitted=1'><img src='pics/submit.bmp'></a></p>";
		echo '<input type="submit" name = "submitted" value="Submit">';
		echo "<input type='hidden' name='id' value='$this->keyId'></b>
		</form>";		
*/
	}
	

	public function Display_add_form(){
		$RecTable = new RecordsTable_class();
		$RecTable->Initialize();
		
		if ($this->TS == ""){
			$this->TS = ltrim(date('Y-m-d'),"0");
		}
		
		echo '
		<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';

		//DELIVERY INFORMATION
		echo '<br>
		<div style ="width:70%;height:100%;background-color:'.$RecTable->Color1_2.';border:2px solid">
		<h2><font color="#000000">DELIVERY INFORMATION</font></h2>
		<p><b>';
		
		/*
		$Status_selector = new Status_selector_class();
		$Status_selector->Display_selector($this->Status,"Status","Status");
		echo "</p><p>";
*/
		

		
		echo 'Log#:'; 
		if ($this->BeingEdited != TRUE){
		echo "<select name='logletter' onChange='submit()'>"; 
		//$option_logletter .= "<option value='A' selected = 'selected'>A</option>";
		for ($i=65;$i<91;$i++){
			$tempLetter = chr($i);
				
			if ($tempLetter!=$this->logletter){
				$option_logletter .= "<option value='$tempLetter'>$tempLetter</option>";
				}
			else {	
					$option_logletter .= "<option value='$tempLetter'  selected = 'selected'>$tempLetter</option>";
				}
		}	
		echo $option_logletter;
		echo '</select>';
		}
		//echo "Being Edited: $this->BeingEdited";
		
		if ($this->BeingEdited != TRUE){
		$this->LogNumber = $this->GetMaxNumber($this->logletter);
		}
		
		echo'
		<input type="text" name="LogNumber" size="10" maxlength="10" 
		value="'.$this->LogNumber.'" />
		
		<br><br>Date: <input type="text" name="TS" size="15" maxlength="15" 
		value="'.$this->TS.'" />';
		
		include('mysql_connect.php');

		//echo "Current requestor= $this->Requestor<br>";
		echo "<br><br><b>Requestor:</b><select name='reqselect' onChange='submit()'>"; 
		$q_req = 'select distinct(Requestor) from ReceivedItems where Requestor <> "" ORDER BY Requestor ASC';
		$r_req  = mysql_query($q_req,$dbc);
		
		
		if ($this->Requestor == ""){
		$option_block_req  .= "<option value='Other' selected = 'selected'>Other</option>";
		}
		else{
			$option_block_req  .= "<option value='Other'>Other</option>";
		}
		while ($options = @mysql_fetch_array($r_req )){
				
			if (($options[0]!=$this->reqselect) && ($options[0]!=$this->Requestor)) {
				//if ($options[0]!=$this->reqselect) {	
				$option_block_req .= "<option value='$options[0]'>$options[0]</option>";
				}
				
			//if (($options[0]==$this->Requestor) & ($this->Requestor != "Other")){
			else {	
					$option_block_req .= "<option value='$options[0]' selected = 'selected'>$options[0]</option>";
				}
		}	
		echo $option_block_req;
		echo '</select>';
		//echo "</form>";		

		
		if (($this->reqselect == "Other") || ($this->Requestor == "")){
		echo '
		<input type="text" name="Requestor" size="30" maxlength="40" 
		value="'.$this->Requestor.'" />';
		}
		
		echo '
		<br><br>PO#: <input type="text" name="PO" size="15" maxlength="200" 
		value="'.$this->PO.'" />
		
		<br><br>ContentDescription:<br> 
		<textarea rows="5" cols="50" name="ContentDescription" size="60" maxlength="500" 
		/>'.$this->ContentDescription.'</textarea>

		<br><br>Qty: <input type="text" name="Qty" size="3" maxlength="5" 
		value="'.$this->Qty.'" />';
		
		
		echo "<br><br><b>Received From:</b><select name='rxfromselect' onChange='submit()'>"; 
		$q_rx = 'select distinct(RxFrom) from ReceivedItems where RxFrom <> "" ORDER BY RxFrom ASC';
		$r_rx  = mysql_query($q_rx,$dbc);
		
		
		if ($this->RxFrom == ""){
		$option_block_rx  .= "<option value='Other' selected = 'selected'>Other</option>";
		}
		else{
			$option_block_rx  .= "<option value='Other'>Other</option>";
		}
		while ($optionsrx = @mysql_fetch_array($r_rx )){
				
			if (($optionsrx[0]!=$this->RxFrom) && ($optionsrx[0]!=$this->rxfromselect)) {
				$option_block_rx .= "<option value='$optionsrx[0]'>$optionsrx[0]</option>";
				}		
			else {	
					$option_block_rx .= "<option value='$optionsrx[0]' selected = 'selected'>$optionsrx[0]</option>";
				}
		}	
		echo $option_block_rx;
		echo '</select>';
		
		//echo "Current RxFrom: $this->RxFrom<br>";
		
		if (($this->rxfromselect == "Other") || ($this->RxFrom == "")){
		echo '
		<br><br>Received From: <input type="text" name="RxFrom" size="50" maxlength="200" 
		value="'.$this->RxFrom.'" />';
		
		}
		
		echo '<br><br></div>';
		
		//RX INSPECTION
		echo '
		<div style ="width:70%;height:100%;background-color:'.$RecTable->Color2_2.';border:2px solid">
		<h2><font color="#000000">RX INSPECTION</font></h2>
		<p><b>';
		$NR_selector = new Yes_No_NA_selector_class();
		$NR_selector->Display_selector($this->NR,"Notified Requestor?","NR");
		$PAS_selector = new Yes_No_NA_selector_class();
		$PAS_selector->Display_selector($this->PAS,"PAS","PAS");
		
		//echo '<br><br>PAS URL: <input type="text" name="PAS_url" size="30" 
		//maxlength="200" value="'.$this->PAS_url.'" />';
		
		
		$VI_selector = new Yes_No_NA_selector_class();
		$VI_selector->Display_selector($this->VI,"Visual Inspection?","VI");
		$Damage_selector = new Yes_No_NA_selector_class();
		$Damage_selector->Display_selector($this->Damage,"Damaged?","Damage","1");
		
			//Damage information, if applicable
			if ($this->Damage == "YES"){
			echo '<div align = "center">
				<div align = "left" style ="width:80%;height:100%;background-color:#FFDADA;border:1px solid">
				<h2><font color="#FF0000">DAMAGE</font></h2>
				<p><b>
				Specific Damage:<br> 
				<textarea rows="5" cols="45" name="Damage_Specific" size="60" maxlength="80" 
				/>'.$this->Damage_Specific.'</textarea>
				
				<br><br>Action Taken:<br> 
				<textarea rows="5" cols="45" name="Damage_ActionTaken" size="60" maxlength="80" 
				/>'.$this->Damage_ActionTaken.'</textarea>
	
				<br><br>RMA#/NCR#: <input type="text" name="Damage_RMANCR" size="30" 
				maxlength="200" value="'.$this->Damage_RMANCR.'" />
				<br><br>Tracking#: <input type="text" name="Damage_TrackingNumber" size="30" 
				maxlength="200" value="'.$this->Damage_TrackingNumber.'" />';
				$DamageNR_selector = new Yes_No_NA_selector_class();
				$DamageNR_selector->Display_selector($this->Damage_NR,"Notified Requestor?","Damage_NR");
			echo '</p></b></div></div>';
			}
		
		
		
		$PLSOW_selector = new Yes_No_NA_selector_class();
		$PLSOW_selector->Display_selector($this->PackingList_SOW,"Packing List/SOW?","PackingList_SOW");
		$Discrepancy_selector = new Yes_No_NA_selector_class();
		$Discrepancy_selector->Display_selector($this->Discrepancy,"Discrepancy?","Discrepancy","1");
		
			//Discrepancy information, if applicable
			if ($this->Discrepancy == "YES"){
			echo '<div align = "center">
				<div align = "left" style ="width:80%;height:100%;background-color:#FFDADA;border:1px solid">
				<h2><font color="#FF0000">DISCREPANCY</font></h2>
				<p><b>
				Specific Discrepancy:<br> 
				<textarea rows="5" cols="45" name="Discrepancy_Specific" size="60" maxlength="80">'.$this->Discrepancy_Specific.'</textarea>
				
				<br><br>Action Taken:<br> 
				<textarea rows="5" cols="45" name="Discrepancy_ActionTaken" size="60" maxlength="80">'
				.$this->Discrepancy_ActionTaken.'</textarea>
				
				<br><br>RMA#/NCR#: <input type="text" name="Discrepancy_RMANCR" size="30" 
				maxlength="200" value="'.$this->Discrepancy_RMANCR.'">
				<br><br>Tracking#: <input type="text" name="Discrepancy_TrackingNumber" size="30" 
				maxlength="200" value="'.$this->Discrepancy_TrackingNumber.'" >
				<br><br>NRAO Shipping Request#: <input type="text" name="Discrepancy_NRAOShippingRequestNumber" size="30" 
				maxlength="200" value="'.$this->Discrepancy_NRAOShippingRequestNumber.'" >';
				$DiscrepancyNR_selector = new Yes_No_NA_selector_class();
				$DiscrepancyNR_selector->Display_selector($this->Discrepancy_NR,"Notified Requestor?","Discrepancy_NR");
			echo '</p></div></div>';
			}
		
		
		$TIR_selector = new Yes_No_NA_selector_class();
		$TIR_selector->Display_selector($this->TIR,"Technical Inspection Required?","TIR");
		echo "</div>";
		
		//INVENTORY/STORAGE
		echo '
		<div style ="width:70%;height:100%;background-color:'.$RecTable->Color3_2.';border:2px solid">
		<h2><font color="#000000">INVENTORY/STORAGE</font></h2>
		<p><b>';
		echo '
		Staging: <input type="text" name="Staging" size="30" maxlength="200" 
		value="'. $this->Staging .'" />';
	
		$TIComplete_selector = new Yes_No_NA_selector_class();
		$TIComplete_selector->Display_selector($this->TIComplete,"TI Complete","TIComplete");
		
		$LINV_selector = new Yes_No_NA_selector_class();
		$LINV_selector->Display_selector($this->LoadedIntoInventory,"Loaded into Inventory?","LoadedIntoInventory");
		echo '
		<br><br>Assigned Location: <input type="text" name="AssignedLocation" size="30" maxlength="200" 
		value="'. $this->AssignedLocation .'" /></div>';

		
		
		echo '
		Password: <input type="text" name="Password" size="30" maxlength="200" 
		value="'. $this->Password .'" /><br>';
		
		
		//echo '
		//<p><input type="image" src="pics/submit.bmp" name="submitted" value="SUBMIT" /></p></b>';
		
		//echo "<p><a href='".$_SERVER["PHP_SELF"]."?submitted=1'><img src='pics/submit.bmp'></a></p>";
		echo '<input type="submit" name = "submitted" value="Submit">';
		echo "<input type='hidden' name='id' value='$this->keyId'></b>";
		
		
		
		echo '</form>';		
	}
	

	
	public function RequestValues(){
		if (isset($_REQUEST['LogNumber'])){
		$this->LogNumber = $_REQUEST['LogNumber'];
		}
		$this->logletter="A";
		
		if (isset($_REQUEST['logletter'])){
		$this->logletter = $_REQUEST['logletter'];
		}
		if ($this->LogNumber == ""){
			$this->LogNumber = "none";
		}
		if (isset($_REQUEST['TS'])){
		$this->TS = str_replace("/","-",$_REQUEST['TS']);
		//$this->TS = ltrim($this->TS,"0");
		
		
		}
		if (isset($_REQUEST['Requestor'])){
		$this->Requestor = $_REQUEST['Requestor'];	
		}
		
		//$this->reqselect = "Other";
		if (isset($_REQUEST['reqselect'])){
			$this->reqselect = $_REQUEST['reqselect'];
			if($_REQUEST['reqselect'] != "Other"){		
				$this->Requestor = $_REQUEST['reqselect'];	
			}	
		}
		
		if (isset($_REQUEST['RxFrom'])){
			$this->RxFrom = $_REQUEST['RxFrom'];
			//$this->logletter = strtoupper(substr($_REQUEST['RxFrom'],0,1));
			
			
		}
		
		if (isset($_REQUEST['rxfromselect'])){
			$this->rxfromselect = $_REQUEST['rxfromselect'];
			if($_REQUEST['rxfromselect'] != "Other"){		
				$this->RxFrom = $_REQUEST['rxfromselect'];	
				$this->logletter = strtoupper(substr($this->RxFrom,0,1));
		
			}
			if($_REQUEST['rxfromselect'] == "Other"){		
				//$this->RxFrom = $_REQUEST['rxfromselect'];	
				$this->logletter = strtoupper(substr($_REQUEST['RxFrom'],0,1));
		
			}
			//if($_REQUEST['rxfromselect'] == "Other"){		
				//$this->RxFrom = $_REQUEST['RxFrom'];
			//}
				
		}
		
		
		if (isset($_REQUEST['PO'])){
		$this->PO = $_REQUEST['PO'];	
		}
		if (isset($_REQUEST['ContentDescription'])){
		$this->ContentDescription = mysql_real_escape_string($_REQUEST['ContentDescription']);
		}
		if (isset($_REQUEST['Qty'])){
		$this->Qty = $_REQUEST['Qty'];	
		}
		
		if (isset($_REQUEST['NR'])){
		$this->NR = $_REQUEST['NR'];	
		}
		if (isset($_REQUEST['PAS'])){
		$this->PAS = $_REQUEST['PAS'];	
		}
		if (isset($_REQUEST['VI'])){
		$this->VI = $_REQUEST['VI'];	
		}
		if (isset($_REQUEST['Damage'])){
		$this->Damage = $_REQUEST['Damage'];
		}
		if (isset($_REQUEST['PackingList_SOW'])){
		$this->PackingList_SOW = $_REQUEST['PackingList_SOW'];	
		}
		if (isset($_REQUEST['Discrepancy'])){
		$this->Discrepancy = $_REQUEST['Discrepancy'];
		}	
		if (isset($_REQUEST['Staging'])){
		$this->Staging = $_REQUEST['Staging'];	
		}
		if (isset($_REQUEST['TIR'])){
		$this->TIR = $_REQUEST['TIR'];	
		}
		if (isset($_REQUEST['TIComplete'])){
		$this->TIComplete = $_REQUEST['TIComplete'];
		}
		if (isset($_REQUEST['LoadedIntoInventory'])){
		$this->LoadedIntoInventory = $_REQUEST['LoadedIntoInventory'];
		}	
		if (isset($_REQUEST['AssignedLocation'])){
		$this->AssignedLocation = $_REQUEST['AssignedLocation'];	
		}
		if (isset($_REQUEST['Damage_Specific'])){
		$this->Damage_Specific = $_REQUEST['Damage_Specific'];
		}
		if (isset($_REQUEST['Damage_ActionTaken'])){
		$this->Damage_ActionTaken = $_REQUEST['Damage_ActionTaken'];
		}
		if (isset($_REQUEST['Damage_RMANCR'])){
		$this->Damage_RMANCR = $_REQUEST['Damage_RMANCR'];
		}
		if (isset($_REQUEST['Damage_TrackingNumber'])){
		$this->Damage_TrackingNumber = $_REQUEST['Damage_TrackingNumber'];
		}
		if (isset($_REQUEST['Damage_NR'])){
		$this->Damage_NR = $_REQUEST['Damage_NR'];
		}
		if (isset($_REQUEST['Discrepancy_Specific'])){
		$this->Discrepancy_Specific = $_REQUEST['Discrepancy_Specific'];
		}
		if (isset($_REQUEST['Discrepancy_ActionTaken'])){
		$this->Discrepancy_ActionTaken = $_REQUEST['Discrepancy_ActionTaken'];
		}
		if (isset($_REQUEST['Discrepancy_RMANCR'])){
		$this->Discrepancy_RMANCR = $_REQUEST['Discrepancy_RMANCR'];
		}
		if (isset($_REQUEST['Discrepancy_NRAOShippingRequestNumber'])){
		$this->Discrepancy_NRAOShippingRequestNumber = $_REQUEST['Discrepancy_NRAOShippingRequestNumber'];
		}
		if (isset($_REQUEST['Discrepancy_TrackingNumber'])){
		$this->Discrepancy_TrackingNumber = $_REQUEST['Discrepancy_TrackingNumber'];
		}
		if (isset($_REQUEST['Discrepancy_NR'])){
		$this->Discrepancy_NR = $_REQUEST['Discrepancy_NR'];
		}
		if (isset($_REQUEST['PAS_url'])){
		$this->PAS_url = mysql_real_escape_string($_REQUEST['PAS_url']);	
		}	
		
	    $this->Status_description = "NOT Received";
		if (isset($_REQUEST['Status'])){
			$this->Status = $_REQUEST['Status'];	
				if ($this->Status == "1"){
					$this->Status_description = "Received";
				}
		}	
		if (isset($_REQUEST['Password'])){
		$this->Password = $_REQUEST['Password'];	
		}	
	}
	
	public function NewRecord(){
		include('mysql_connect.php');
		$qNew = "INSERT INTO ReceivedItems() VALUES();";
		$rNew = @mysql_query($qNew,$dbc);
		$qNew = "SELECT keyId FROM ReceivedItems ORDER BY keyId DESC LIMIT 1;";
		$rNew = @mysql_query($qNew,$dbc);
		$rowNew = @mysql_fetch_array($rNew);
		$this->keyId = $rowNew[0];
	}
	
	
	public function Display_delete_form(){
		echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">
		<h3>' . $this->LogNumber . '</h3>
		<b><p><font size="+1">
		Are you sure you want to delete this record?<br />
		<input type="radio" name="sure" value="Yes" /> Yes 
		<input type="radio" name="sure" value="No" checked="checked" /> No</p>
		
		<p><input type="image" src="pics/submit.bmp" value="Submit" /></p>
		<input type="hidden" name="submitted" value="TRUE" />
		<input type="hidden" name="id" value="' . $this->keyId . '" />
		Password: <input type="text" name="Password" size="30" maxlength="200" 
		value="'. $this->Password .'" />
		</form>';
	}
	
	
	public function Delete_record(){
		include('mysql_connect.php');
		$q = "DELETE FROM ReceivedItems WHERE keyId=$this->keyId";		
		$r = @mysql_query ($q, $dbc);
		echo '<p>The record has been deleted.</p>';	
		echo '<meta http-equiv="Refresh" content="1;url=view_rx_records.php">';
	}
	
}
class Status_selector_class {
	
	public function Display_selector($CurrentValue = '0',$DisplayText='',$SelectName='',$ChangeSubmit=''){
		$options[0] = "0";
		$options[1] = "1";
		$option_text[0] = "NOT Received";
		$option_text[1] = "Received";
		
		if ($ChangeSubmit==''){
		echo "<br><br>$DisplayText:<select name='$SelectName'>"; 
		}
		if ($ChangeSubmit!=''){
		echo "<br><br>$DisplayText:<select name='$SelectName' onChange='submit()'>"; 
		}
		
	
		for ($i=0;$i<2;$i++){
			if ($CurrentValue == $options[$i]){
				$option_block .= "<option value='$options[$i]' selected = 'selected'>$option_text[$i]</option>";
			}
			if ($CurrentValue != $options[$i]){
				$option_block .= "<option value='$options[$i]'>$option_text[$i]</option>";
			}
		}
		echo $option_block;
		echo '</select>';
	}//end function
}

class Yes_No_NA_selector_class {
	
	public function Display_selector($CurrentValue = 'NA',$DisplayText='',$SelectName='',$ChangeSubmit=''){
		$options[0] = "NO";
		$options[1] = "YES";
		$options[2] = "NA";
		
		if ($ChangeSubmit==''){
		echo "<br><br>$DisplayText:<select name='$SelectName'>"; 
		}
		if ($ChangeSubmit!=''){
		echo "<br><br>$DisplayText:<select name='$SelectName' onChange='submit()'>"; 
		}
		
	
		for ($i=0;$i<3;$i++){
			if ($CurrentValue == $options[$i]){
				$option_block .= "<option value='$options[$i]' selected = 'selected'>$options[$i]</option>";
			}
			if ($CurrentValue != $options[$i]){
				$option_block .= "<option value='$options[$i]'>$options[$i]</option>";
			}
		}
		echo $option_block;
		echo '</select>';
	}//end function
}





class RecordsTable_class {
	
	var $NumberOfRecords;
	var $Color1;
	var $Color2;
	var $Color3;
	var $Color1_2;
	var $Color2_2;
	var $Color3_2;
	var $SortColumn;
	var $SortOrder;
	var $OrderByString;
	
	var $ResultsPerPage;
	var $RecordsStart;
	var $RecordsStop;
	var $CurrentPage;
	
	var $Requestor;
	var $RxFrom;
	var $POsearch;
	
	
	public function Initialize($In_ResultsPerPage=200){
		include('mysql_connect.php');
		$q = "SELECT keyId FROM ReceivedItems;";
		$r = @mysql_query($q,$dbc);
		$this->NumberOfRecords = mysql_num_rows($r);
		
		$this->Color1 = "#98c4f0"; //blue
		$this->Color1_2 = "#D9EEFF"; //light blue
		$this->Color2 = "#ffb521"; //orange
		$this->Color2_2 = "#fffab2"; //light orange
		$this->Color3 = "#32ed41"; //green
		$this->Color3_2 = "#C9FEC7"; //light green
		
		
		$this->ResultsPerPage = $In_ResultsPerPage;
    	$this->RecordsStart = 0;
    	$this->CurrentPage = 0;
    	
    	if (isset($_REQUEST['start'])){
    		$this->RecordsStart = $_REQUEST['start'];
    		$this->CurrentPage = $_REQUEST['page'];
    	}
    	$this->RecordsStop = $this->RecordsStart + $this->ResultsPerPage;
		
		$this->SortOrder = "RxDateDESC";
		$this->OrderByString = "ORDER BY RxDate DESC";
		
		
		if (isset($_REQUEST['sort'])){
			$this->SortOrder = $_REQUEST['sort'];
			
			$OrderBy = "ORDER BY RxDate DESC";	
			switch ($this->SortOrder) {
		    case "RxDateASC":
		        $this->OrderByString = "ORDER BY RxDate ASC";
		        break;
		    case "RxDateDESC":
		        $this->OrderByString = "ORDER BY RxDate DESC";
		        break;    
		        
			case "Requestor":
		        $this->OrderByString = "ORDER BY Requestor ASC";
		        break;
		    case "Log":
		        $this->OrderByString = "ORDER BY LogNumber ASC";
		        break;
		    case "PO":
		        $this->OrderByString = "ORDER BY PO ASC";
		        break;
		    case "ContentDescription":
		        $this->OrderByString = "ORDER BY ContentDescription ASC";
		        break;
		    case "Qty":
		        $this->OrderByString = "ORDER BY Qty ASC";
		        break;
		    case "RxFrom":
		        $this->OrderByString = "ORDER BY RxFrom ASC, LogNumber ASC";
		        break;    
		    case "NR":
		        $this->OrderByString = "ORDER BY NR ASC";
		        break;   
		    case "PAS":
		        $this->OrderByString = "ORDER BY PAS ASC";
		        break;
			case "VI":
		        $this->OrderByString = "ORDER BY VI ASC";
		        break;
		    case "Damage":
		        $this->OrderByString = "ORDER BY Damage ASC, RxDate ASC";
		        break;
		    case "PackingList_SOW":
		        $this->OrderByString = "ORDER BY PackingList_SOW ASC";
		        break;
		    case "Discrepancy":
		        $this->OrderByString = "ORDER BY Discrepancy ASC, RxDate ASC";
		        break;
		    case "Staging":
		        $this->OrderByString = "ORDER BY Staging ASC";
		        break;
		    case "TIR":
		        $this->OrderByString = "ORDER BY TIR ASC";
		        break;       
		    case "TIComplete":
		        $this->OrderByString = "ORDER BY TIComplete ASC";
		        break;   
		    case "LoadedIntoInventory":
		        $this->OrderByString = "ORDER BY LoadedIntoInventory ASC";
		        break;
			case "AssignedLocation":
		        $this->OrderByString = "ORDER BY AssignedLocation ASC";
		        break;
		    case "Damage_Specifc":
		        $this->OrderByString = "ORDER BY Damage_Specifc ASC";
		        break;
		    case "Damage_ActionTaken":
		        $this->OrderByString = "ORDER BY Damage_ActionTaken ASC";
		        break;
		    case "Damage_RMANCR":
		        $this->OrderByString = "ORDER BY Damage_RMANCR ASC";
		        break;
		    case "Damage_TrackingNumber":
		        $this->OrderByString = "ORDER BY Damage_TrackingNumber ASC";
		        break;
		    case "Damage_NR":
		        $this->OrderByString = "ORDER BY Damage_NR ASC";
		        break;   
		    case "Discrepancy_Specifc":
		        $this->OrderByString = "ORDER BY Discrepancy_Specifc ASC";
		        break;
		    case "Discrepancy_ActionTaken":
		        $this->OrderByString = "ORDER BY Discrepancy_ActionTaken ASC";
		        break;
		    case "Discrepancy_RMANCR":
		        $this->OrderByString = "ORDER BY Discrepancy_RMANCR ASC";
		        break;
		    case "Discrepancy_TrackingNumber":
		        $this->OrderByString = "ORDER BY Discrepancy_TrackingNumber ASC";
		        break;
		    case "Discrepancy_NR":
		        $this->OrderByString = "ORDER BY Discrepancy_NR ASC";
		        break;      
		    case "Discrepancy_NRAOsrq":
		        $this->OrderByString = "ORDER BY Discrepancy_NRAOShippingRequestNumber ASC";
		        break; 
			case "Staging":
		        $this->OrderByString = "ORDER BY Staging ASC";
		        break; 
			
			case "TIComplete":
		        $this->OrderByString = "ORDER BY TIComplete ASC";
		        break; 
			
			case "LoadedIntoInventory":
		        $this->OrderByString = "ORDER BY LoadedIntoInventory ASC";
		        break; 
			
			case "AssignedLocation":
		        $this->OrderByString = "ORDER BY AssignedLocation ASC";
		        break; 
			
			}
		}

		
		
		
		
	}
	
	
	public function ShowLegend(){

		//DELIVERY INFORMATION
		echo '
		<table align="center" cellspacing="1" cellpadding="1" width="115%"
		bgcolor="#000000">
		<tr>
		
		<td align="center" bgcolor="'.$this->Color1.'" colspan = "2"><b>
		<font color="#000000" >DELIVERY INFORMATION</b></td>
		</tr>
		
		<tr><td align="center" bgcolor = "'.$this->Color1_2.'">
		<font color="#000000">
		<b>NRAO RECEIVING "LOG#"</a></td>
		<td align="left" bgcolor = "'.$this->Color1_2.'">
		<font color="#000000">
		Indicates the receiving log number.  This number is assigned to each package received by the NA FEIC.  
		<br>It consists of the first letter of the receiving location and 4 digit sequential identfier.
		<br>This also acts as a reference number for the damaged goods and discrepancies spreadsheets.
		</a></td></tr>
		
		<tr><td align="center" bgcolor = "'.$this->Color1_2.'">
		<font color="#000000">
		<b>DATE RECEIVED</a></td>
		<td align="left" bgcolor = "'.$this->Color1_2.'">
		<font color="#000000">
		Indicates the date on which the items were signed for / received into the building
		</a></td></tr>
		
		<tr><td align="center" bgcolor = "'.$this->Color1_2.'">
		<font color="#000000">
		<b>REQUESTOR</a></td>
		<td align="left" bgcolor = "'.$this->Color1_2.'">
		<font color="#000000">
		Indicates whom in the NA FEIC requested the received items or to whom it was addressed (RECIPIENT).
		</a></td></tr>
		
		<tr><td align="center" bgcolor = "'.$this->Color1_2.'">
		<font color="#000000">
		<b>PO#</a></td>
		<td align="left" bgcolor = "'.$this->Color1_2.'">
		<font color="#000000">
		Indicates the purchase order number(s) for the received items (may not be applicable in all instances).
		</a></td></tr>
		
		<tr><td align="center" bgcolor = "'.$this->Color1_2.'">
		<font color="#000000">
		<b>CONTENT DESCRIPTION</a></td>
		<td align="left" bgcolor = "'.$this->Color1_2.'">
		<font color="#000000">
		Provides a brief description of the items received in an order.  
		<br>Should be in proper ALMA format.
		</a></td></tr>
		
		<tr><td align="center" bgcolor = "'.$this->Color1_2.'">
		<font color="#000000">
		<b>QTY</a></td>
		<td align="left" bgcolor = "'.$this->Color1_2.'">
		<font color="#000000">
		Indicates the quantity of items received in an order.
		</a></td></tr>
		
		<tr><td align="center" bgcolor = "'.$this->Color1_2.'">
		<font color="#000000">
		<b>RX FROM</a></td>
		<td align="left" bgcolor = "'.$this->Color1_2.'">
		<font color="#000000">
		Indicates the ALMA vendor or location from which items originated
		</a></td></tr>';
		
		
		//RX INSPECTION
		echo '
		<tr>
		<td align="center" bgcolor="'.$this->Color2.'" colspan = "2"><b>
		<font color="#000000">RX INSPECTION</b></td>
		</tr>
		
		<tr><td align="center" bgcolor = "'.$this->Color2_2.'">
		<font color="#000000">
		<b>NR (NOTIFIED REQUESTOR)</a></td>
		<td align="left" bgcolor = "'.$this->Color2_2.'">
		<font color="#000000">
		Indicates whether the requestor been notified, via email, of 
		<br>(i) shipment arrival, 
		<br>(ii) to sign purchase receiver and 
		<br>(iii) any damage or discrepancies that were noted.
		</a></td></tr>
		
		<tr><td align="center" bgcolor = "'.$this->Color2_2.'">
		<font color="#000000">
		<b>PAS</a></td>
		<td align="left" bgcolor = "'.$this->Color2_2.'">
		<font color="#000000">
		Indicates whether a QA/PAS procedural document was (or has been)
		<br>provided by the Rx organization.
		</a></td></tr>
		
		<tr><td align="center" bgcolor = "'.$this->Color2_2.'">
		<font color="#000000">
		<b>VI (VISUAL INSPECTION)</a></td>
		<td align="left" bgcolor = "'.$this->Color2_2.'">
		<font color="#000000">
		Indicates whether the shipment was visually inspected for physical damage to 
		<br>container or items received.  (If yes, pictures need to be taken of the 
		<br>damage and saved).
		</a></td></tr>

		<tr><td align="center" bgcolor = "'.$this->Color2_2.'">
		<font color="#000000">
		<b>DAMAGE</a></td>
		<td align="left" bgcolor = "'.$this->Color2_2.'">
		<font color="#000000">
		Indicates whether physical damage was noted for either the shipping container 
		<br>or the items received  (If yes, damage report should be created, vendor 
		<br>notified and RMA generated).
		</a></td></tr>

		<tr><td align="center" bgcolor = "'.$this->Color2_2.'">
		<font color="#000000">
		<b>DAMAGED GOODS ACTION TAKEN</a></td>
		<td align="left" bgcolor = "'.$this->Color2_2.'">
		<font color="#000000">
		Indicates what action(s) was(were) taken with regards to any damaged goods 
		<br>(View record for more information pertaining to damage).
		</a></td></tr>
		
		<tr><td align="center" bgcolor = "'.$this->Color2_2.'">
		<font color="#000000">
		<b>PACKING LIST / SOW</a></td>
		<td align="left" bgcolor = "'.$this->Color2_2.'">
		<font color="#000000">
		Indicates that a packing slip was found and that all items received were checked 
		<br>against the packing slip, the statement of work (or requisition instructions).
		</a></td></tr>
		
		<tr><td align="center" bgcolor = "'.$this->Color2_2.'">
		<font color="#000000">
		<b>DISCREPANCIES</a></td>
		<td align="left" bgcolor = "'.$this->Color2_2.'">
		<font color="#000000">
		Indicates whether descrepancies were found during inspection of packing slip 
		<br>and the items physically received.
		</a></td></tr>

		<tr><td align="center" bgcolor = "'.$this->Color2_2.'">
		<font color="#000000">
		<b>TIR (TECHNICAL INSPECTION REQUIRED)</a></td>
		<td align="left" bgcolor = "'.$this->Color2_2.'">
		<font color="#000000">
		Indicates whether a technical inspection/test is required for the items received.
		<br>General Guidelines:
		<br>Machine Shop Items - Not Required (Visual Inspection Only)
		<br>PCBs - Required
		<br>Items ordered via SOW - Required
		<br>Components from ALMA partner or partern organization - Required
		<br>COTS (i.e.,digikey,McMaster-Carr, etc.) - NOT Required.
		</td></tr>';
		
		
		//INVENTORY/STORAGE
		echo '
		<tr>
		<td align="center" bgcolor="'.$this->Color3.'" colspan = "2"><b>
		<font color="#000000">INVENTORY/STORAGE</b></td>
		</tr>
		
		<tr><td align="center" bgcolor = "'.$this->Color3_2.'">
		<font color="#000000">
		<b>STAGING</a></td>
		<td align="left" bgcolor = "'.$this->Color3_2.'">
		<font color="#000000">
		Indicates whether items have been placed in the staging area to be 
		<br>tested prior to final inventory step.
		</a></td></tr>
		
		<tr><td align="center" bgcolor = "'.$this->Color3_2.'">
		<font color="#000000">
		<b>TI COMPLETE</a></td>
		<td align="left" bgcolor = "'.$this->Color3_2.'">
		<font color="#000000">
		Indicates whether required technical inspection/test for received items has been completed 
		<br>(This cell MUST be updated once the TI has been completed).
		</a></td></tr>
		
		<tr><td align="center" bgcolor = "'.$this->Color3_2.'">
		<font color="#000000">
		<b>LOADED INTO INVENTORY</a></td>
		<td align="left" bgcolor = "'.$this->Color3_2.'">
		<font color="#000000">
		Indicates whether items have been loaded into the electronic inventory system 
		<br>(P&V) and physically placed in an inventory location.
		</a></td></tr>
		
		<tr><td align="center" bgcolor = "'.$this->Color3_2.'">
		<font color="#000000">
		<b>ASSIGNED LOCATION</a></td>
		<td align="left" bgcolor = "'.$this->Color3_2.'">
		<font color="#000000">
		Storage location(s) to which the received items were assigned.
		</a></td></tr>';
		
		echo '
		</table>';
		
		
	}
	
	
	public function TableHeader(){
		// Table header:
		echo '
		<table align="left" cellspacing="1" cellpadding="1" width="100%"
		bgcolor="#000000">
		<tr>';
		
			echo '
			<td align="center" bgcolor="'.$this->Color1.'" colspan = "7"><b>
			<font color="#000000" size = "+2" >DELIVERY INFORMATION</b></td>
			<td align="center" bgcolor="'.$this->Color2.'" colspan = "7"><b>
			<font color="#000000" size = "+2">RX INSPECTION</b></td>
			<td align="center" bgcolor="'.$this->Color3.'" colspan = "4"><b>
			<font color="#000000" size = "+2">INVENTORY/STORAGE</b></td>
			<td align="center" bgcolor="#ffffff" ></td>
			</tr>';
			
			//DELIVERY INFORMATION
			echo '
			<td align="center" bgcolor="'.$this->Color1.'"><b>
		
			<a href="view_rx_records.php?sort=Log&start='.$this->RecordsStart.'&page='.$this->CurrentPage.'">
			<font color="#000000">&nbsp;LOG#&nbsp;</b></td></a>
			<td align="center" bgcolor="'.$this->Color1.'"><b>
			<a href="view_rx_records.php?sort=RxDateASC&start='.$this->RecordsStart.'&page='.$this->CurrentPage.'">
			<font color="#000000">&nbsp;Rx&nbsp;Date&nbsp;</b></td></a>
			<td align="center" bgcolor="'.$this->Color1.'"><b>
			<a href="view_rx_records.php?sort=Requestor&start='.$this->RecordsStart.'&page='.$this->CurrentPage.'">
			<font color="#000000">Requestor</b></td></a>
			<td align="center" bgcolor="'.$this->Color1.'"><b>
			<a href="view_rx_records.php?sort=PO&start='.$this->RecordsStart.'&page='.$this->CurrentPage.'">
			<font color="#000000">PO#</b></td></a>
			<td align="center" bgcolor="'.$this->Color1.'"><b>
			<a href="view_rx_records.php?sort=ContentDescription&start='.$this->RecordsStart.'&page='.$this->CurrentPage.'">
			<font color="#000000">&nbsp;&nbsp;&nbsp;&nbsp;Content&nbsp;Description&nbsp;&nbsp;&nbsp;&nbsp;</b></td></a>
			<td align="center" bgcolor="'.$this->Color1.'"><b>
			<a href="view_rx_records.php?sort=Qty&start='.$this->RecordsStart.'&page='.$this->CurrentPage.'">
			<font color="#000000">QTY</b></td></a>
			<td align="center" bgcolor="'.$this->Color1.'"><b>
			<a href="view_rx_records.php?sort=RxFrom&start='.$this->RecordsStart.'&page='.$this->CurrentPage.'">
			<font color="#000000">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Rx&nbsp;From&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</b></a></td>';
		
			//RX INSPECTION
			echo '
			<td align="center" bgcolor="'.$this->Color2.'"><b>
			<a href="view_rx_records.php?sort=NR&start='.$this->RecordsStart.'&page='.$this->CurrentPage.'">
			<font color="#000000">NR</b></td></a>
			<td align="center" bgcolor="'.$this->Color2.'"><b>
			<a href="view_rx_records.php?sort=PAS&start='.$this->RecordsStart.'&page='.$this->CurrentPage.'">
			<font color="#000000">PAS</b></td></a>
			<td align="center" bgcolor="'.$this->Color2.'"><b>
			<a href="view_rx_records.php?sort=VI&start='.$this->RecordsStart.'&page='.$this->CurrentPage.'">
			<font color="#000000">VI</b></td></a>
			<td align="center" bgcolor="'.$this->Color2.'"><b>
			<a href="view_rx_records.php?sort=Damage&start='.$this->RecordsStart.'&page='.$this->CurrentPage.'">
			<font color="#000000">Damage</b></td></a>
			<td align="center" bgcolor="'.$this->Color2.'"><b>
			<a href="view_rx_records.php?sort=PackingList_SOW&start='.$this->RecordsStart.'&page='.$this->CurrentPage.'">
			<font color="#000000">Packing List/SOW</b></td></a>
			<td align="center" bgcolor="'.$this->Color2.'"><b>
			<a href="view_rx_records.php?sort=Discrepancy&start='.$this->RecordsStart.'&page='.$this->CurrentPage.'">
			<font color="#000000">Discrepancies</b></td></a>
			<td align="center" bgcolor="'.$this->Color2.'"><b>
			<a href="view_rx_records.php?sort=TIR&start='.$this->RecordsStart.'&page='.$this->CurrentPage.'">
			<font color="#000000">TIR</b></td></a>';
			
			//INVENTORY/STORAGE
			echo '
			<td align="center" bgcolor="'.$this->Color3.'"><b>
			<a href="view_rx_records.php?sort=Staging&start='.$this->RecordsStart.'&page='.$this->CurrentPage.'">
			<font color="#000000">Staging</b></td></a>
			<td align="center" bgcolor="'.$this->Color3.'"><b>
			<a href="view_rx_records.php?sort=TIComplete&start='.$this->RecordsStart.'&page='.$this->CurrentPage.'">
			<font color="#000000">T.I. Complete</b></td></a>
			<td align="center" bgcolor="'.$this->Color3.'"><b>
			<a href="view_rx_records.php?sort=LoadedIntoInventory&start='.$this->RecordsStart.'&page='.$this->CurrentPage.'">
			<font color="#000000">Loaded Into Inventory</b></td></a>
			<td align="center" bgcolor="'.$this->Color3.'"><b>
			<a href="view_rx_records.php?sort=AssignedLocation&start='.$this->RecordsStart.'&page='.$this->CurrentPage.'">
			<font color="#000000">Assigned&nbsp;Location</b></td></a>
			
		</tr>';
	}
	
	public function TableRows(){
		include ('mysql_connect.php');
		
		
	



	$q = "SELECT keyId,
		LogNumber,RxDate,Requestor,
		PO,ContentDescription,Qty,
		RxFrom,NR,PAS,
		VI,Damage,PackingList_SOW,
		Discrepancy,Staging,TIR,
		TIComplete,LoadedIntoInventory,AssignedLocation
		FROM ReceivedItems $this->OrderByString 
		
		LIMIT $this->RecordsStart, $this->ResultsPerPage;";
		
		//echo $q;
	

	
		$r = @mysql_query($q,$dbc);

		while ($row = mysql_fetch_array($r)) {
			$bg1 = ($bg1==$this->Color1_2 ? '#ffffff' : $this->Color1_2);
			$bg2 = ($bg2==$this->Color2_2 ? '#ffffff' : $this->Color2_2);
			$bg3 = ($bg3==$this->Color3_2 ? '#ffffff' : $this->Color3_2);
			$bgRED = "#ffb3b3";
			
			$TIR_temp = $row[15];
			$TIcomplete_temp = $row[16];
			
			if (($TIR_temp == "YES") && ($TIcomplete_temp != "YES")){
				//$bg1 = $bgRED;
				//$bg2 = $bgRED;
				//$bg3 = $bgRED;
				
			}
			
				echo '<tr>
				<td align="center" bgcolor="'.$bg1.'">
				<a href="view_rxitem_full_record.php?id='.$row[0].'">
				<b><font size="0.5" color = "#0033cc">' . $row[1] . '</a></td>
				<td align="center" bgcolor="'.$bg1.'"><font size="0.5" color = "#000000">' . $row[2] . '</td>
				<td align="center" bgcolor="'.$bg1.'"><font size="0.5" color = "#000000">' . $row[3] . '</td>
				<td align="center" bgcolor="'.$bg1.'"><font size="0.5" color = "#000000">' . $row[4] . '</td>
				<td align="left" bgcolor="'.$bg1.'"><font size="0.5" color = "#000000">' . $row[5] . '</td>
				<td align="center" bgcolor="'.$bg1.'"><font size="0.5" color = "#000000">' . $row[6] . '</td>
				<td align="left" bgcolor="'.$bg1.'"><font size="0.5" color = "#000000">' . $row[7] . '</td>
				<td align="center" bgcolor="'.$bg2.'"><font size="0.5" color = "#000000">' . $row[8] . '</td>
				<td align="center" bgcolor="'.$bg2.'"><font size="0.5" color = "#000000">' . $row[9] . '</td>
				<td align="center" bgcolor="'.$bg2.'"><font size="0.5" color = "#000000">' . $row[10] . '</td>';
				
				if (strtoupper($row[11]) == "YES"){
					echo '<td align="center" bgcolor="'.$bgRED.'"><font size="0.5" color = "#000000">' . $row[11] . '</td>';
				}
				else{
					echo '<td align="center" bgcolor="'.$bg2.'"><font size="0.5" color = "#000000">' . $row[11] . '</td>';
				}
				
				
				echo '
				<td align="center" bgcolor="'.$bg2.'"><font size="0.5" color = "#000000">' . $row[12] . '</td>';
				
				if (strtoupper($row[13]) == "YES"){
					echo '<td align="center" bgcolor="'.$bgRED.'"><font size="0.5" color = "#000000">' . $row[13] . '</td>';
				}
				else{
					echo '<td align="center" bgcolor="'.$bg2.'"><font size="0.5" color = "#000000">' . $row[13] . '</td>';
				}
				
				if (($TIR_temp == "YES") && ($TIcomplete_temp != "YES")){
				echo '<td align="center" bgcolor="'.$bgRED.'"><font size="0.5" color = "#000000">' . $row[15] . '</td>';
				} 
				
				else{
					echo '<td align="center" bgcolor="'.$bg2.'"><font size="0.5" color = "#000000">' . $row[15] . '</td>';
				}
				
				
				
				echo '
				<td align="center" bgcolor="'.$bg3.'"><font size="0.5" color = "#000000">' . $row[14] . '</td>';
				
				if (($TIR_temp == "YES") && ($TIcomplete_temp != "YES")){
				echo '<td align="center" bgcolor="'.$bgRED.'"><font size="0.5" color = "#000000">' . $row[16] . '</td>';
				} 
				
				else{
					echo '<td align="center" bgcolor="'.$bg3.'"><font size="0.5" color = "#000000">' . $row[16] . '</td>';
				}
				
				
				echo '
				<td align="center" bgcolor="'.$bg3.'"><font size="0.5" color = "#000000">' . $row[17] . '</td>
				<td align="center" bgcolor="'.$bg3.'"><font size="0.5" color = "#000000">' . $row[18] . '</td>
				
				
			
				
			</tr>';
		} // End of WHILE loop.
	}
	
	public function TableFooter(){
		echo '</table>';
	}

	
	public function DisplayPageNumbers(){
		$pagecount = 0;
		$numiters = round(($this->NumberOfRecords / $this->ResultsPerPage),0);
		
	

		
		//echo "view_shipments_sort.php?start=$i&stop=$stop&page=$pagecount&AssyPNView=$AssyPNView&LocationView=$LocationView&StatusView=$StatusView&VendorView=$VendorView&StartDateView=$StartDateView&EndDateView=$EndDateView";
	
		for ($i=0;$i<$this->NumberOfRecords;$i+=$this->ResultsPerPage){
			//echo "max= " . $this->NumberOfRecords / $this->ResultsPerPage;
			//echo "<br>";
			//echo "numiters= $numiters <br>";
			//echo "i= $i <br>";
			
			
			$CurrentPageColor = "#ff3300";
			
			$stop = $i + $this->ResultsPerPage;
			if ($pagecount==$this->CurrentPage){
				$fontcolor = $CurrentPageColor;
			}
			else{
				$fontcolor = "#939393";
			}
			
			$urlstring = "view_rx_records.php?start=$i&page=$pagecount&sort=$this->SortOrder";
			
			
			//if ($this->AssyPNView!="'%'"){
				//$urlstring .= str_replace("'","","&AssyPNView=$this->AssyPNView");
				//$urlstring .= str_replace(" ","+","&AssyPNView=$this->AssyPNView");
			//}

			
			//echo $urlstring;
			
			
			
			echo "
			<a href=$urlstring>
			<font color=$fontcolor>
			$pagecount
			</font>
			</a>";
			$pagecount+=1;	
			
			
		}
	
		
		
	}	
	
	
}







class DamagesTable_class {
	var $Color1;
	var $Color2;
	var $Color3;
	var $Color1_2;
	var $Color2_2;
	var $Color3_2;
	var $SortColumn;
	
	
	public function Initialize(){
		$this->Color1 = "#98c4f0"; //blue
		$this->Color1_2 = "#D9EEFF"; //light blue
		$this->Color2 = "#ffb521"; //orange
		$this->Color2_2 = "#fffab2"; //light orange
		$this->Color3 = "#32ed41"; //green
		$this->Color3_2 = "#C9FEC7"; //light green
	}
	
	public function TableHeader(){
		// Table header:
		echo '
		<table align="left" cellspacing="1" cellpadding="1" width="100%"
		bgcolor="#000000">
		<tr>';
		
			echo '
			<td align="center" bgcolor="'.$this->Color1.'" colspan = "2"><b>
			<font color="#000000" >DELIVERY INFORMATION</b></td>
			<td align="center" bgcolor="#FF0000" colspan = "6"><b>
			<font color="#000000" >DAMAGES</b></td>
			
			</tr>';
			
			//DELIVERY INFORMATION
			echo '
			<td align="center" bgcolor="'.$this->Color1_2.'"><b>
			<font color="#000000">LOG#</b></td>
			<td align="center" bgcolor="'.$this->Color1_2.'"><b>
			<font color="#000000">Rx Date</b></td>
			<td align="center" bgcolor="#FFDADA"><b>
			<font color="#000000">Specific Damage</b></td>
			<td align="center" bgcolor="#FFDADA"><b>
			<font color="#000000">Action Taken</b></td>
			<td align="center" bgcolor="#FFDADA"><b>
			<font color="#000000">RMA#/NCR#</b></td>
			<td align="center" bgcolor="#FFDADA"><b>
			<font color="#000000">Tracking#</b></td>
			<td align="center" bgcolor="#FFDADA"><b>
			<font color="#000000">Notified Requestor?</b></td>
			<td align="center" bgcolor="#FFDADA"><b>
			<font color="#000000">View Record</b></td>
		</tr>';
	}

	public function TableRows(){
		include ('mysql_connect.php');
		
		$q = "SELECT keyId,LogNumber,TS,
		Damage_Specific,Damage_ActionTaken,Damage_RMANCR,
		Damage_TrackingNumber,Damage_NR
		FROM ReceivedItems 
		WHERE Damage = 'YES'
		ORDER BY keyId ASC;";
		
		$r = @mysql_query($q,$dbc);

		while ($row = mysql_fetch_array($r)) {
				echo '
				<tr>
					<td align="center" bgcolor="#ffffff"><font size="0.5" color = "#000000">' . $row[1] . '</td>
					<td align="center" bgcolor="#ffffff"><font size="0.5" color = "#000000">' . $row[2] . '</td>
					<td align="center" bgcolor="#ffffff"><font size="0.5" color = "#000000">' . $row[3] . '</td>
					<td align="center" bgcolor="#ffffff"><font size="0.5" color = "#000000">' . $row[4] . '</td>
					<td align="center" bgcolor="#ffffff"><font size="0.5" color = "#000000">' . $row[5] . '</td>
					<td align="center" bgcolor="#ffffff"><font size="0.5" color = "#000000">' . $row[6] . '</td>
					<td align="center" bgcolor="#ffffff"><font size="0.5" color = "#000000">' . $row[7] . '</td>

					
					<td align="left" bgcolor = "#ffffff"><a href="view_rxitem_full_record.php?id=' . $row[0] . '">
					<img src="pics/viewrecordbutton.bmp"></td>
				</tr>';
		} // End of WHILE loop.
	}
	
	
	public function TableFooter(){
		echo "</table>";
		
	}
	
	
}

class DiscrepanciesTable_class {
var $Color1;
	var $Color2;
	var $Color3;
	var $Color1_2;
	var $Color2_2;
	var $Color3_2;
	var $SortColumn;
	
	
	public function Initialize(){
		$this->Color1 = "#98c4f0"; //blue
		$this->Color1_2 = "#D9EEFF"; //light blue
		$this->Color2 = "#ffb521"; //orange
		$this->Color2_2 = "#fffab2"; //light orange
		$this->Color3 = "#32ed41"; //green
		$this->Color3_2 = "#C9FEC7"; //light green
	}
	
	public function TableHeader(){
		// Table header:
		echo '
		<table align="left" cellspacing="1" cellpadding="1" width="100%"
		bgcolor="#000000">
		<tr>';
		
			echo '
			<td align="center" bgcolor="'.$this->Color1.'" colspan = "2"><b>
			<font color="#000000" >DELIVERY INFORMATION</b></td>
			<td align="center" bgcolor="#FF0000" colspan = "7"><b>
			<font color="#000000" >DISCREPANCIES</b></td>
			
			</tr>';
			
			//DELIVERY INFORMATION
			echo '
			<td align="center" bgcolor="'.$this->Color1_2.'"><b>
			<font color="#000000">LOG#</b></td>
			<td align="center" bgcolor="'.$this->Color1_2.'"><b>
			<font color="#000000">Rx Date</b></td>
			<td align="center" bgcolor="#FFDADA"><b>
			<font color="#000000">Specific Discrepancies</b></td>
			<td align="center" bgcolor="#FFDADA"><b>
			<font color="#000000">Action Taken</b></td>
			<td align="center" bgcolor="#FFDADA"><b>
			<font color="#000000">RMA#/NCR#</b></td>
			<td align="center" bgcolor="#FFDADA"><b>
			<font color="#000000">NRAO Shipping Request#</b></td>
			<td align="center" bgcolor="#FFDADA"><b>
			<font color="#000000">Tracking#</b></td>
			<td align="center" bgcolor="#FFDADA"><b>
			<font color="#000000">Notified Requestor?</b></td>
			<td align="center" bgcolor="#FFDADA"><b>
			<font color="#000000">View Record</b></td>
		</tr>';
	}

	public function TableRows(){
		include ('mysql_connect.php');
		
		$q = "SELECT keyId,LogNumber,TS,
		Discrepancy_Specific,Discrepancy_ActionTaken,Discrepancy_RMANCR,
		Discrepancy_NRAOShippingRequestNumber,
		Discrepancy_TrackingNumber,Discrepancy_NR
		FROM ReceivedItems 
		WHERE Discrepancy = 'YES'
		ORDER BY keyId ASC;";
		
		$r = @mysql_query($q,$dbc);

		while ($row = mysql_fetch_array($r)) {
				echo '
				<tr>
					<td align="center" bgcolor="#ffffff"><font size="0.5" color = "#000000">' . $row[1] . '</td>
					<td align="center" bgcolor="#ffffff"><font size="0.5" color = "#000000">' . $row[2] . '</td>
					<td align="center" bgcolor="#ffffff"><font size="0.5" color = "#000000">' . $row[3] . '</td>
					<td align="center" bgcolor="#ffffff"><font size="0.5" color = "#000000">' . $row[4] . '</td>
					<td align="center" bgcolor="#ffffff"><font size="0.5" color = "#000000">' . $row[5] . '</td>
					<td align="center" bgcolor="#ffffff"><font size="0.5" color = "#000000">' . $row[6] . '</td>
					<td align="center" bgcolor="#ffffff"><font size="0.5" color = "#000000">' . $row[7] . '</td>
					<td align="center" bgcolor="#ffffff"><font size="0.5" color = "#000000">' . $row[8] . '</td>
					
					<td align="left" bgcolor = "#ffffff"><a href="view_rxitem_full_record.php?id=' . $row[0] . '">
					<img src="pics/viewrecordbutton.bmp"></td>
				</tr>';
		} // End of WHILE loop.
	}
	
	
	public function TableFooter(){
		echo "</table>";
		
	}
	
}














class ExcelReader_class {
	var $SheetNumber;
	
	public function Initialize($In_SheetNumber){
		$this->SheetNumber = $In_SheetNumber;
	}
	
	public function Display_ImportForm(){
		echo'
		<form enctype="multipart/form-data" action="' . $_SERVER["PHP_SELF"] . '" method="POST">
		<div align="left">	
		<b>Import records from an XLS file: </b>
		    <input name="userfile" type="file" />
		    <input type="submit" name="submit" value="SUBMIT" />
		    </div>
		</form>';	
	}
	
	public function Display_XLS_data(){
		$data = new Spreadsheet_Excel_Reader();

		// Set output Encoding.
		$data->setOutputEncoding('CP1251');
		$filename = $_FILES['userfile']['tmp_name'];
		$data->read($filename);
		error_reporting(E_ALL ^ E_NOTICE);
		
		for ($i = 3; $i <= $data->sheets[$this->SheetNumber]['numRows']; $i++) {
			for ($j = 1; $j <= $data->sheets[$this->SheetNumber]['numCols']; $j++) {
				echo "\"".$data->sheets[$this->SheetNumber]['cells'][$i][$j]."\",";
			}
			echo "<br><br>";
		}
	}
	
	public function ImportData(){
		
		include('mysql_connect.php');
		$data = new Spreadsheet_Excel_Reader();

		// Set output Encoding.
		$data->setOutputEncoding('CP1251');
		$filename = $_FILES['userfile']['tmp_name'];
		$data->read($filename);
		error_reporting(E_ALL ^ E_NOTICE);
		
		
		for ($i = 3; $i <= $data->sheets[0]['numRows']; $i++) {	

				//First check to see if record already exists
				$LogNo = $data->sheets[0]['cells'][$i][1];
				$qLog = "SELECT keyId FROM ReceivedItems WHERE
						LogNumber = '$LogNo';";
				$rLog = @mysql_query($qLog,$dbc);
				
				
				if ((@mysql_num_rows($rLog) < 1) && ($LogNo != "")){
					//import record if not found
					$RxItem = new ReceivedItem_class();
					$RxItem->NewRecord();
					
					//DELIVERY INFORMATION
					$RxItem->LogNumber = $data->sheets[0]['cells'][$i][1];
					if ($RxItem->LogNumber == ""){
						$RxItem->LogNumber = "none";
					}
					
					$RxItem->TS = $data->sheets[0]['cells'][$i][2];
					$tempTS = split("/",$RxItem->TS);
					if (sizeof($tempTS) > 1){
						$RxItem->TS = $tempTS[2] . "-" . $tempTS[1] . "-" . $tempTS[0];
					}
					
					$RxItem->Requestor = $data->sheets[0]['cells'][$i][3];
					$RxItem->PO = $data->sheets[0]['cells'][$i][4];
					$RxItem->ContentDescription = $data->sheets[0]['cells'][$i][5];
					$RxItem->Qty = $data->sheets[0]['cells'][$i][6];
					$RxItem->RxFrom = $data->sheets[0]['cells'][$i][7];
					
					//RX INSPECTION
					$RxItem->NR = strtoupper($data->sheets[0]['cells'][$i][8]);
					$RxItem->PAS = strtoupper($data->sheets[0]['cells'][$i][9]);
					if ($RxItem->PAS == "N/A"){
						$RxItem->PAS = "NA";
					}
					
					$RxItem->VI = strtoupper($data->sheets[0]['cells'][$i][10]);
					if ($RxItem->VI == "N/A"){
						$RxItem->VI = "NA";
					}
					
					$RxItem->Damage = strtoupper($data->sheets[0]['cells'][$i][11]);
					//Read in from Damaged Goods Checklist if necessary
					
					$RxItem->PackingList_SOW = strtoupper($data->sheets[0]['cells'][$i][13]);
					if ($RxItem->PackingList_SOW == "N/A"){
						$RxItem->PackingList_SOW = "NA";
					}
					
					$RxItem->Discrepancy = strtoupper($data->sheets[0]['cells'][$i][14]);
					//Read in from Discrepancies Checklist if necessary
					
					$RxItem->TIR = strtoupper($data->sheets[0]['cells'][$i][16]);
					if ($RxItem->TIR == "N/A"){
						$RxItem->TIR = "NA";
					}
					
					
					//INVENTORY/STORAGE
					$RxItem->Staging = strtoupper($data->sheets[0]['cells'][$i][17]);
					if ($RxItem->Staging == "N/A"){
						$RxItem->Staging = "NA";
					}
					
					$RxItem->TIComplete = strtoupper($data->sheets[0]['cells'][$i][18]);
					if ($RxItem->TIComplete == "N/A"){
						$RxItem->TIComplete = "NA";
					}
					
					$RxItem->LoadedIntoInventory = strtoupper($data->sheets[0]['cells'][$i][19]);
					if ($RxItem->LoadedIntoInventory == "N/A"){
						$RxItem->LoadedIntoInventory = "NA";
					}

					$RxItem->AssignedLocation = strtoupper($data->sheets[0]['cells'][$i][20]);
					
					
					//DAMAGES
					for ($j = 2; $j <= $data->sheets[2]['numRows']; $j++) {	
						
						$tempLog = $data->sheets[2]['cells'][$j][1];
						if ($tempLog == $RxItem->LogNumber){
							$RxItem->Damage = "YES";
							$RxItem->Damage_Specific = $data->sheets[2]['cells'][$j][3];
							$RxItem->Damage_ActionTaken = $data->sheets[2]['cells'][$j][4];
							$RxItem->Damage_RMANCR = $data->sheets[2]['cells'][$j][5];
							$RxItem->Damage_TrackingNumber = $data->sheets[2]['cells'][$j][6];
							$RxItem->Damage_NR = strtoupper($data->sheets[2]['cells'][$j][7]);
						}
					}
					//DISCREPANCIES
					for ($j = 2; $j <= $data->sheets[3]['numRows']; $j++) {	
						$tempLog = $data->sheets[3]['cells'][$j][1];
						if ($tempLog == $RxItem->LogNumber){
							$RxItem->Discrepancy = "YES";
							$RxItem->Discrepancy_Specific = $data->sheets[3]['cells'][$j][3];
							$RxItem->Discrepancy_ActionTaken = $data->sheets[3]['cells'][$j][4];
							$RxItem->Discrepancy_RMANCR = $data->sheets[3]['cells'][$j][5];
							$RxItem->Discrepancy_NRAOShippingRequestNumber = $data->sheets[3]['cells'][$j][6];
							$RxItem->Discrepancy_TrackingNumber = $data->sheets[3]['cells'][$j][7];
							$RxItem->Discrepancy_NR = strtoupper($data->sheets[3]['cells'][$j][8]);
						}
					}

					$RxItem->UpdateRecord();
				
				}//end if mysql_num_rows != 0
		}
		
		
		
	}
	
	
}


















class StorageLocation_class {
	var $keyId;
	var $LocationName;
	
	public function Initialize($In_keyId){
		include ('mysql_connect.php');
		
		$qInit = "SELECT 
		LocationName
		FROM StorageLocations 
		WHERE keyId = $In_keyId;";
		
		$rInit = @mysql_query($qInit,$dbc);
		$rowInit = @mysql_fetch_array($rInit);
		$this->keyId = $in_keyId;
		$this->LocationName = $rowInit[0];
	}
}

class StorageLocation_selector_class {
	
	public function Display_selector($CurrentValue = '',$DisplayText='',$SelectName='',$ChangeSubmit=''){
		include('mysql_connect.php');
		
		
		if ($ChangeSubmit==''){
		echo "<br><br>$DisplayText:<select name='$SelectName'>"; 
		}
		if ($ChangeSubmit!=''){
		echo "<br><br>$DisplayText:<select name='$SelectName' onChange='submit()'>"; 
		}
		
		$q = "SELECT keyId FROM StorageLocations;";
		echo $q . "<br>";
		$r = @mysql_query($q,$dbc);
		while($row = @mysql_fetch_array($r)){
			$tempLocation = new StorageLocation_class();
			$tempLocation->Initialize($row[0]);
			
			if ($tempLocation->keyId == $CurrentValue){
				$option_block .= "<option value='$tempLocation->keyId' selected = 'selected'>
				$tempLocation->LocationName</option>";
			}
			if ($tempLocation->keyId != $CurrentValue){
				$option_block .= "<option value='$tempLocation->keyId'>
				$tempLocation->LocationName</option>";
			}	
		}
		
		echo $option_block;
		echo '</select>';
	}//end function
}


class Staging_class {
	var $keyId;
	var $StagingName;
	
	public function Initialize($In_keyId){
		include ('mysql_connect.php');
		
		$qInit = "SELECT 
		StagingName
		FROM Staging
		WHERE keyId = $In_keyId;";
		echo $qInit;
		$rInit = @mysql_query($qInit,$dbc);
		$rowInit = @mysql_fetch_array($rInit);
		$this->keyId = $in_keyId;
		$this->StagingName = $rowInit[0];
	}
}

class Staging_selector_class {
	
	public function Display_selector($CurrentValue = '',$DisplayText='',$SelectName='',$ChangeSubmit=''){
		include('mysql_connect.php');
		
		if ($ChangeSubmit==''){
		echo "<br><br>$DisplayText:<select name='$SelectName'>"; 
		}
		if ($ChangeSubmit!=''){
		echo "<br><br>$DisplayText:<select name='$SelectName' onChange='submit()'>"; 
		}
		
		$q = "SELECT keyId FROM Staging;";
		$r = @mysql_query($q,$dbc);
		while($row = @mysql_fetch_array($r)){
			$tempStaging = new Staging_class();
			$tempStaging->Initialize($row[0]);
			
			if ($tempStaging->keyId == $CurrentValue){
				$option_block .= "<option value='$tempStaging->keyId' selected = 'selected'>
				$tempStaging->StagingName</option>";
			}
			if ($tempStaging->keyId != $CurrentValue){
				$option_block .= "<option value='$tempStaging->keyId'>
				$tempStaging->StagingName</option>";
			}	
		}
		
		echo $option_block;
		echo '</select>';
	}//end function
}
?>