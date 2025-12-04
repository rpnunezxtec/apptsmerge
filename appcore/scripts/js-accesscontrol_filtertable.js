//Javascript to filter a table

//Events
const input_events = document.getElementById("filterEvents");
const events_table = document.getElementById("events");

//Devices
const device_table = document.getElementById("devices");
const input_devices = document.getElementById("filterDevices");


//Events Filter
function filterEventTable() {
    var filter, td, i, j, txtValue, shouldDisplay;
    tr = events_table.getElementsByTagName("tr");
    filter = input_events.value.toUpperCase();

    for (i = 0; i < tr.length; i++) {
        shouldDisplay = false;
        for (j = 1; j <= 5; j++) { // Loop through 2nd to 6th columns (index 1 to 5)
            td = tr[i].getElementsByTagName("td")[j];
            if (td) {
                txtValue = td.textContent || td.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    shouldDisplay = true;
                    break; // No need to check other columns if one already matches
                }
            }
        }
        tr[i].style.display = shouldDisplay ? "" : "none";
    }
}

//Device Filter
function filterDeviceTable() {
    var filter, td, i, j, txtValue, shouldDisplay;
    tr = device_table.getElementsByTagName("tr");
    filter = input_devices.value.toUpperCase();

    for (i = 0; i < tr.length; i++) {
        shouldDisplay = false;
        for (j = 1; j <= 5; j++) { // Loop through 2nd to 6th columns (index 1 to 5)
            td = tr[i].getElementsByTagName("td")[j];
            if (td) {
                txtValue = td.textContent || td.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    shouldDisplay = true;
                    break; // No need to check other columns if one already matches
                }
            }
        }
        tr[i].style.display = shouldDisplay ? "" : "none";
    }
}