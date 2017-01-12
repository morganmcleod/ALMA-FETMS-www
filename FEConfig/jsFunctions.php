<script type='text/javascript' language='JavaScript'>
function RecAddedAlert(loc)
{
    //alert('Record added!',0);
    window.location = loc;
}
function RecModifiedAlert(loc)
{
    alert('Record Modified!',0);
    window.location = loc;
}
function getNumberofRecords(serialnum)
{
    var ajaxRequest;
    try
    {
        // Opera 8.0+, Firefox, Safari
        ajaxRequest = new XMLHttpRequest();
    }
    catch (e)
    {
        // Internet Explorer Browsers
        try
        {
            ajaxRequest = new ActiveXObject("Msxml2.XMLHTTP");
        }
        catch (e)
        {
            try
            {
                ajaxRequest = new ActiveXObject("Microsoft.XMLHTTP");
            }
            catch (e)
            {
                // Something went wrong
                alert("Your browser broke!");
                return false;
            }
        }
    }
    // Create a function that will receive data sent from the server
    ajaxRequest.onreadystatechange = function()
    {
        if(ajaxRequest.readyState == 4)
        {
            var result= ajaxRequest.responseText;
            alert("This serial number has " + result + " records. Please go to the update page to view these records.");
        }
    }
    var url="CountRetrndRecs.php";
    url=url+"?sn="+escape(serialnum);
    ajaxRequest.open("GET",url,true);
    ajaxRequest.send(null);
}

function ShouldConfigChange()
{
    //2010-06-16 dn called from VupdateTestSystem.php
    if(document.FEUpdate.newconfig.checked == true && document.FEUpdate.compchanged.value == 0)
    {
            //alert(value);
        answer=Ext.MessageBox.confirm('Confirm', 'Are you sure you want to change the configuration number?', function(btn){
        if (btn == 'no')
        {
            document.FEUpdate.newconfig.checked=false;
        }
        else
        {
            document.getElementById('submitornot').value=1;
            document.FEUpdate.submit();
        }
        });
    }

    else if(document.FEUpdate.newconfig.checked == false)
    {
        //alert(value);
        answer=Ext.MessageBox.confirm('Confirm', 'Are you sure you DONT want to change the configuration number?', function(btn){
        if (btn == 'no')
        {
            document.FEUpdate.newconfig.checked=true;
        }
        else
        {
            document.getElementById('submitornot').value=1;
            document.FEUpdate.submit();
        }
        });
    }
    else
    {
        document.getElementById('submitornot').value=1;
        document.FEUpdate.submit();
    }
}
function FormValidate(flag)
{
    var err1 = "";
    var err2 = "";
    var err3 = "";

      if(flag==1)
    {
          err1 += validateEmp(document.FEUpdate.sn);
          err2 += validateEmp(document.FEUpdate.notes);
          err3 += validateDropdown(document.FEUpdate.updatedby);
      }
      else if (flag==2)
      {
          err2 += validateEmp(document.FEUpdate.notes);
          err3 += validateDropdown(document.FEUpdate.updatedby);
      }

     if (err1 != "")
     {
        alert("Please enter Serial Number\n",0);
        return false;
      }
     if (err2 !="")
     {
         alert("Please enter Notes\n",0);
         return false;
     }
     if(err3 !="")
     {
         alert("Please enter Updated by\n",0);
         return false;
     }

     document.getElementById('submitornot').value=1;
    document.FEUpdate.submit();

        /*if(flag==2)
        {
        ShouldConfigChange();
        }
        else
        {
            return true;
        }*/
 }
function validateEmp(fld)
{
    var error = "";

    if (fld.value.length == 0)
    {
        fld.style.background = 'DarkGray';
        error = "The required field has not been filled in.\n" ;
    }
    else
    {
        fld.style.background = 'White';
    }
    return error;
}
function validateDropdown(fld)
{
    var error="";

    if(fld.options[fld.selectedIndex].value == "")
    {
        fld.style.background = 'DarkGray';
        error = "The required field has not been filled in.\n" ;
    }
    else
    {
        fld.style.background = 'White';
    }
    return error;
}

function OpenAllFiles(lnk)
{
    if(lnk.substr(0,4)=="http")
    {
        location.href=lnk;
    }
    else
    {
        window.location='file:///' + lnk;
    }
}
function CompValidate(flag)
{
    var err1 = "";
    var err2 = "";
    var err3 = "";

      if(flag==1)
    {
          err1 += validateEmp(document.addComponents.sn);
          err2 += validateEmp(document.addComponents.notes);
          err3 += validateDropdown(document.addComponents.updatedby);
      }
      else if (flag==2)
      {
          err2 += validateEmp(document.addComponents.notes);
          err3 += validateDropdown(document.addComponents.updatedby);
      }

     if (err1 != "")
     {
        alert("Please enter Serial Number\n",0);
        return false;
      }
     if (err2 !="")
     {
         alert("Please enter Notes\n",0);
         return false;
     }
     if(err3 !="")
     {
         alert("Please enter Updated by\n",0);
         return false;
     }
     document.getElementById('submitornot').value=1;
     document.addComponents.submit();
}
function getWarmPASConfig(fesn)
{
    window.open('../DatabaseDeliveryTest/GeneratePASZip.php?warmconf=&frontend=' + fesn);

    //called from getFrontEndData.php
//    Ext.MessageBox.prompt('Warm Config SN', 'Enter Warm Config Serial Number:',function(clickedwhat,text)
//    {
//        if(clickedwhat == 'ok')
//        {
//            window.open('../DatabaseDeliveryTest/GeneratePASZip.php?warmconf=' + text + '&frontend=' + fesn);
//        }
//    });
}
</script>
