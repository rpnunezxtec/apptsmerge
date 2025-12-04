// $Id:$
// Ajax functions for srip form updates

// Usage:
// 1. Set the service URL in 'xhrservice' global variable.
// 2. Set the refreshInterval global to the interval in seconds.
// 3. Call the startRefresh() function to start the timer.

// Variables (global):
// xh : XHR object.
// xhrservice : URL to service file (eg xsvc-usercon.xas).
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
			var response = JSON.parse(xh.responseText);
			var result = response.result;
			
			if(result != "error")
			{
				// success					
				var devid = response.devid;
				var qid = response.qid;
				
				var statustext = document.getElementById("statustext");
				if(statustext != null)
					statustext.innerHTML = "Enrollee Available";
				
				var loading = document.getElementById("loading");
				if(loading != null)
					loading.style.display = "none";
				
				var waiting = document.getElementById("waiting");
				if(waiting != null)
					waiting.style.display = "";
				
				var accept = document.getElementById("accept");
				if(accept != null)
					accept.style.display = "";
				
				document.getElementById("devid").value = devid;
				document.getElementById("qid").value = qid;
				
				var space1 = document.getElementById("space1");
				if(space1 != null)
					space1.className = "inputtitlespacer10";
				
				var space2 = document.getElementById("space2");
				if(space2 != null)
					space2.className = "inputtitlespacer5";
				
				var space3 = document.getElementById("space3");
				if(space3 != null)
					space3.className = "inputtitlespacer5";
			}
		}
	}
}

// Create the XHR call to the remote server using POST to send the data
var xhrsripservice = "";
function xhr_call()
{	
		xh.open("GET", xhrsripservice, true);
		xh.setRequestHeader("Content-type", "application/json");

		xh.send(null);
}

// The refresh function that triggers the XHR calls to the server and updates
// the contents of the form.
// An array of data hashes for the form slots
function xhr_refresh()
{
	xhr_call();
}

// Setup the interval timer (in seconds). Should be called on form load.
var refreshID = 0;
var refreshsripinterval = 2;
function startSripStatus()
{
	refreshID = window.setInterval(xhr_refresh, refreshsripinterval * 1000);
}
