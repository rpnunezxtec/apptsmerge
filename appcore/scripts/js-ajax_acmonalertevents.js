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
var xhe = false;
if (!xhe && typeof XMLHttpRequest !== 'undefined')
{
	xhe = new XMLHttpRequest();
	if (xhe.overrideMimeType)
 		xhe.overrideMimeType('text/html');

 	xhe.onreadystatechange = xher_process;
}

// Callback function to process the resulting XML from the service
function xher_process()
{
	if (xhe.readyState == 4) {
		if (xhe.status == 200) {
			// Stop refresh while html updates
			stopRefreshEvents()

			// The resulting data is pure html page table contents
			document.getElementById("events").innerHTML = xhe.responseText;

			// Start the refresh trigger after this processing
			startRefreshEvents();
		}
	}
}

// Create the XHR call to the remote server using POST to send the data
var xherservice = "";
var xhepoststring = "";
function xher_call(xhepoststring)
{
	xhe.open("POST", xherservice, true);
	xhe.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	//xhe.setRequestHeader("Content-length", xhepoststring.length);
	//xhe.setRequestHeader("Connection", "close");

	xhe.send(xhepoststring);
}

// The refresh function that triggers the XHR calls to the server and updates
// the contents of the form.
var devgroupname = "";
var selectedDevices = [];
var lastAcknowledged = "";
var devicenameEvents = "";
var eventsFilter = "";

function xher_refresh()
{
	// This function is triggered by a timer interval, set when the form is loaded.
	// Send the devgroupname and the array of slothashvals to the server using xhr_call().
	// These are encoded as devgroupname=name&slothash[0]=val0&slothash[1]=val1...
	var nh = selectedDevices.length;
	if (devicenameEvents == "")
		xhepoststring = "devgroupname=" + devgroupname;

	for (var i = 0; i < nh; i++)
		xhepoststring += "&" + "devices[" + i + "]=" + selectedDevices[i];

	xhepoststring += "&" + "lastAcknowledged=" + lastAcknowledged + "&" + "eventfilter=" + eventsFilter;
	
	xher_call(xhepoststring);
}

// Setup the interval timer (in seconds). Should be called on form load.
var refreshIDEvents = 0;
var refreshintervalEvents = 2;
function startRefreshEvents()
{
	refreshIDEvents = window.setInterval(xher_refresh, refreshintervalEvents * 1000);
}

function stopRefreshEvents() {
	window.clearInterval(refreshIDEvents);
}

// Add event listeners for page unload events
window.addEventListener('beforeunload', stopRefreshEvents);
window.addEventListener('unload', stopRefreshEvents);