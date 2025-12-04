// $Id:$
// Ajax functions for srip form updates

// Usage:
// 1. Set the service URL in 'xhrcaptureservice' global variable.
// 2. Set the refreshInterval global to the interval in seconds.
// 3. Call the startRefresh() function to start the timer.

// Variables (global):
// xh : XHR object.
// xhrcaptureservice : URL to service file (eg xsvc-sripcapturestatus.xas).
// refreshqueuestatusID : Refresh timer object.
// refreshInterval : Refresh interval in seconds.
// qid : Unique identifier in queue for session
// stage : The current stage of the capture process
// Create a new XHR object when loaded

var xhqueuedeleteentry = false;
if (!xhqueuedeleteentry && typeof XMLHttpRequest != 'undefined')
{
	xhqueuedeleteentry = new XMLHttpRequest();
	if (xhqueuedeleteentry.overrideMimeType)
 		xhqueuedeleteentry.overrideMimeType('text/xml');

 	xhqueuedeleteentry.onreadystatechange = xhqueuedeleteentryr_process;
}

// Callback function to process the resulting XML from the service
function xhqueuedeleteentryr_process()
{
	if (xhqueuedeleteentry.readyState == 4)
	{
		if (xhqueuedeleteentry.status == 200)
		{			
			// Do something with the data result set
			var result = JSON.parse(xhqueuedeleteentry.responseText);
			
			// get error if any
			var err = result.error;
			
			if(err != true)
			{
				// hide element
				alert("Entry successfully deleted.");
				xhrqueuestatus_call();
				document.getElementById("myTable").deleteRow(id);
			}
			else
			{
				// get error message
				var errormsg = result.errormsg;
				
				alert("Error: " + errormsg)
			}
		}
	}
}

// Create the XHR call to the remote server using POST to send the data
var xhrsripdeleteentry = "";
var id = 0;
function deleteEntry(devid, eleId)
{
	id =  eleId;
	xhqueuedeleteentry.open("GET", xhrsripdeleteentry + "?devid=" + devid + "&sie=" + Math.random(), true);
	xhqueuedeleteentry.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

	xhqueuedeleteentry.send(null);
}