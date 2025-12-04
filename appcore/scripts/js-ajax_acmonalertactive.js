// $Id:$
// Ajax functions for access monitor form updates

// Usage:
// 1. Set the service URL in 'xhrservice' global variable.
// 2. Assign the initial data hash values in the slothashtable array for each slot.
// 3. Set the device group or device name global variable.
// 4. Set the refreshInterval global to the interval in seconds.
// 5. Call the startRefresh() function to start the timer.

// Variables (global):
// xh : XHR object.
// xhrservice : URL to service file (eg xsvc-acmonitor.xas).
// refreshID : Refresh timer object.
// refreshInterval : Refresh interval in seconds.
// devgroupname : The device group name (OR)
// devicename : The device name
// lastAcknowledged : The last acknowledged alert

// Create a new XHR object when loaded
var xha = false;
if (!xha && typeof XMLHttpRequest !== 'undefined')
{
	xha = new XMLHttpRequest();
	if (xha.overrideMimeType)
 		xha.overrideMimeType('text/html');

 	xha.onreadystatechange = xhar_process;
}

// Callback function to process the resulting XML from the service
function xhar_process()
{
	if (xha.readyState == 4) {
		if (xha.status == 200) {
			// Stop refresh while html updates
			stopRefreshActive()

			// The resulting data is pure html page table contents
			document.getElementById("active-alerts").innerHTML = xha.responseText;

			// Start the refresh trigger after this processing
			startRefreshActive();
		}
		//else if (xha.status == 204) {
		//	console.log("No changes since last AJAX...");
		//}
	}
}

// Create the XHR call to the remote server using POST to send the data
var xharservice = "";
var xhapoststring = "";
function xhar_call(xhapoststring)
{
	xha.open("POST", xharservice, true);
	xha.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	//xha.setRequestHeader("Content-length", xhapoststring.length);
	//xha.setRequestHeader("Connection", "close");

	xha.send(xhapoststring);
}

// The refresh function that triggers the XHR calls to the server and updates
// the contents of the form.
var devgroupname = "";
var selectedDevices = [];
var lastAcknowledged = "";
var devicenameActive = "";

function xhar_refresh()
{
	// This function is triggered by a timer interval, set when the form is loaded.
	// Send the devgroupname and the array of slothashvals to the server using xhr_call().
	// These are encoded as devgroupname=name&slothash[0]=val0&slothash[1]=val1...
	var nh = selectedDevices.length;
	if (devicenameActive == "")
		xhapoststring = "devgroupname=" + devgroupname;

	for (var i = 0; i < nh; i++)
		xhapoststring += "&" + "devices[" + i + "]=" + selectedDevices[i];

	xhapoststring += "&" + "lastAcknowledged=" + lastAcknowledged;
	
	xhar_call(xhapoststring);
}

// Setup the interval timer (in seconds). Should be called on form load.
var refreshIDActive = 0;
var refreshintervalActive = 2;
function startRefreshActive()
{
	refreshIDActive = window.setInterval(xhar_refresh, refreshintervalActive * 1000);
}

function stopRefreshActive() {
	window.clearInterval(refreshIDActive);
}

// Add event listeners for page unload events
window.addEventListener('beforeunload', stopRefreshActive);
window.addEventListener('unload', stopRefreshActive);