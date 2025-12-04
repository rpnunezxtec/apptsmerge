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
var xhd = false;
if (!xhd && typeof XMLHttpRequest !== 'undefined')
{
	xhd = new XMLHttpRequest();
	if (xhd.overrideMimeType)
 		xhd.overrideMimeType('text/html');

 	xhd.onreadystatechange = xhdr_process;
}

// Callback function to process the resulting XML from the service
function xhdr_process()
{
	if (xhd.readyState == 4) {
		if (xhd.status == 200) {
			// Stop refresh while html updates
			stopRefreshDevices()

			// The resulting data is pure html page table contents
			document.getElementById("devices").innerHTML = xhd.responseText;

			// Start the refresh trigger after this processing
			startRefreshDevices();
		}
		//else if (xhd.status == 204) {
		//	console.log("No changes since last AJAX...");
		//}
	}
}

// Create the XHR call to the remote server using POST to send the data
var xhdrservice = "";
var xhdpoststring = "";
function xhdr_call(xhdpoststring)
{
	xhd.open("POST", xhdrservice, true);
	xhd.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	//xhd.setRequestHeader("Content-length", xhdpoststring.length);
	//xhd.setRequestHeader("Connection", "close");

	xhd.send(xhdpoststring);
}

// The refresh function that triggers the XHR calls to the server and updates
// the contents of the form.
var devgroupname = "";
var devicenameDevices = "";

function xhdr_refresh()
{
	// This function is triggered by a timer interval, set when the form is loaded.
	// Send the devgroupname and the array of slothashvals to the server using xhr_call().
	// These are encoded as devgroupname=name&slothash[0]=val0&slothash[1]=val1...
	if (devicenameDevices == "")
		xhdpoststring = "devgroupname=" + devgroupname;
	
	xhdr_call(xhdpoststring);
}

// Setup the interval timer (in seconds). Should be called on form load.
var refreshIDDevices = 0;
var refreshintervalDevices = 5;
function startRefreshDevices()
{
	refreshIDDevices = window.setInterval(xhdr_refresh, refreshintervalDevices * 1000);
}

function stopRefreshDevices() {
	window.clearInterval(refreshIDDevices);
}

// Add event listeners for page unload events
window.addEventListener('beforeunload', stopRefreshDevices);
window.addEventListener('unload', stopRefreshDevices);