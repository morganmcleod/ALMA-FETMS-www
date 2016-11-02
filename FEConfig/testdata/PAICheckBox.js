function PAIcheckBox(id, checked) {
    var txt = new String("");
    if (checked)
        txt = "checked";

    var received = function(response) {
    };

    var failed = function(response) {
        alert("Error:  The database was not updated due to a network error.  id="
                + id + " " + txt);
    };

    Ext.Ajax.request({
        // Send the JSON object
        url : 'updateTestDataUseForPAI.php?action=checkbox&key=' + id
                + '&checked=' + checked,
        success : received,
        failure : failed
    });
}
