<script type='text/javascript' language='JavaScript'>
    function FormValidate(flag) {
        var err1 = "";
        var err2 = "";
        var err3 = "";

        if (flag == 1) {
            err1 += validateEmp(document.FEUpdate.sn);
            err2 += validateEmp(document.FEUpdate.notes);
            err3 += validateDropdown(document.FEUpdate.updatedby);
        } else if (flag == 2) {
            err2 += validateEmp(document.FEUpdate.notes);
            err3 += validateDropdown(document.FEUpdate.updatedby);
        }

        if (err1 != "") {
            alert("Please enter Serial Number\n", 0);
            return false;
        }
        if (err2 != "") {
            alert("Please enter Notes\n", 0);
            return false;
        }
        if (err3 != "") {
            alert("Please enter Updated by\n", 0);
            return false;
        }

        document.getElementById('submitornot').value = 1;
        document.FEUpdate.submit();
    }

    function validateEmp(fld) {
        var error = "";

        if (fld.value.length == 0) {
            fld.style.background = 'DarkGray';
            error = "The required field has not been filled in.\n";
        } else {
            fld.style.background = 'White';
        }
        return error;
    }

    function validateDropdown(fld) {
        var error = "";

        if (fld.options[fld.selectedIndex].value == "") {
            fld.style.background = 'DarkGray';
            error = "The required field has not been filled in.\n";
        } else {
            fld.style.background = 'White';
        }
        return error;
    }

    function CompValidate(flag) {
        var err1 = "";
        var err2 = "";
        var err3 = "";

        if (flag == 1) {
            err1 += validateEmp(document.addComponents.sn);
            err2 += validateEmp(document.addComponents.notes);
            err3 += validateDropdown(document.addComponents.updatedby);
        } else if (flag == 2) {
            err2 += validateEmp(document.addComponents.notes);
            err3 += validateDropdown(document.addComponents.updatedby);
        }

        if (err1 != "") {
            alert("Please enter Serial Number\n", 0);
            return false;
        }
        if (err2 != "") {
            alert("Please enter Notes\n", 0);
            return false;
        }
        if (err3 != "") {
            alert("Please enter Updated by\n", 0);
            return false;
        }
        document.getElementById('submitornot').value = 1;
        document.addComponents.submit();
    }
</script>
