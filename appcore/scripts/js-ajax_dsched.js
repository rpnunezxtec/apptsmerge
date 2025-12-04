// $Id:$
// Ajax functions for daily schedule updates

// Usage:
// 1. Set the service URL in 'xhrservice' global variable.
// 2. Set the site variable with the siteid.
// 3. Set the datestamp variable with the selected date timestamp.
// 4. Set the refreshInterval global to the interval in seconds.
// 5. Call the startRefresh() function to start the timer.

// Variables (global):
// xh : XHR object.
// xhrservice : URL to service file (eg xsvc-appt.xas).
// site : siteID selected.
// datestamp : date timestamp.
// refreshID : Refresh timer object.
// refreshInterval : Refresh interval in seconds.

// Create a new XHR object when loaded
var xh = false;
if (!xh && typeof XMLHttpRequest != 'undefined')
{
	xh = new XMLHttpRequest();
 	xh.onreadystatechange = xhr_process;
}

// Callback function to process the resulting XML from the service
function xhr_process()
{
	if (xh.readyState == 4)
	{
		if (xh.status == 200)
		{
			// The resulting data is pure html page table contents
			var pagedata = document.getElementById("pagedata");
			pagedata.innerHTML = xh.responseText;
		}
	}
}

// Create the XHR call to the remote server using POST to send the data
var xhrservice = "";
var site = "";
var datestamp = "";
function xhr_call()
{
	// Check for value in 'site' and add the URL parameters if necessary
	var getstr = "";
	getstr = "?siteid=" + site;
	getstr += "&datestamp=" + datestamp;
	
	xh.open("GET", xhrservice + getstr, true);
	xh.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xh.setRequestHeader("Connection", "close");

	xh.send(null);
}

// The refresh function that triggers the XHR calls to the server and updates
// the contents of the form.
// An array of data hashes for the form slots
function xhr_refresh()
{
	// This function is triggered by a timer interval, set when the form is loaded.
	xhr_call();
}

// Setup the interval timer (in seconds). Should be called on form load.
var refreshID = 0;
var refreshinterval = 30;
function startRefresh()
{
	refreshID = window.setInterval(xhr_refresh, refreshinterval * 1000);
}

