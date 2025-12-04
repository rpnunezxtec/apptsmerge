// $Id:$
// Ajax functions for srip form updates

// Usage:
// 1. Set the service URL in 'xhrcaptureservice' global variable.
// 2. Set the refreshInterval global to the interval in seconds.
// 3. Call the startRefresh() function to start the timer.

// Variables (global):
// xh : XHR object.
// xhrcaptureservice : URL to service file (eg xsvc-sripcapturestatus.xas).
// refreshoperatorstatusID : Refresh timer object.
// refreshInterval : Refresh interval in seconds.
// qid : Unique identifier in operator for session
// stage : The current stage of the capture process
// Create a new XHR object when loaded

var xhoperatordeleteentry = false;
if (!xhoperatordeleteentry && typeof XMLHttpRequest != 'undefined')
{
	xhoperatordeleteentry = new XMLHttpRequest();
	if (xhoperatordeleteentry.overrideMimeType)
 		xhoperatordeleteentry.overrideMimeType('text/xml');

 	xhoperatordeleteentry.onreadystatechange = xhoperatordeleteentryr_process;
}

// Callback function to process the resulting XML from the service
function xhoperatordeleteentryr_process()
{
	if (xhoperatordeleteentry.readyState == 4)
	{
		if (xhoperatordeleteentry.status == 200)
		{			
			// Do something with the data result set
			var result = JSON.parse(xhoperatordeleteentry.responseText);
			
			// get error if any
			var err = result.error;
			
			if(err != true)
			{
				// hide element
				alert("Entry successfully deleted.");
				xhroperatorstatus_call();
				document.getElementById("myTable").deleteRow(iden);
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
var xhrsripoperatordeleteentry = "";
var iden = 0;
function deleteOperatorEntry(operatorID, eleId)
{
	iden =  eleId;
	xhoperatordeleteentry.open("GET", xhrsripoperatordeleteentry + "?eoid=" + operatorID + "&sie=" + Math.random(), true);
	xhoperatordeleteentry.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

	xhoperatordeleteentry.send(null);
}