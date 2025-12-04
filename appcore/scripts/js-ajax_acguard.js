// $Id:$
// Ajax functions for access guard form updates

// Usage:
// 1. Set the service URL in 'xhrservice' global variable.
// 2a. Assign the initial data hash values in the slothashtable array for each slot.
// 2b. Set the selected configset in the configset variable.
// 3. Set the refreshInterval global to the interval in seconds.
// 4. Call the startRefresh() function to start the timer.

// Variables (global):
// xh : XHR object.
// xhrservice : URL to service file (eg xsvc-acmonitor.xas).
// refreshID : Refresh timer object.
// refreshInterval : Refresh interval in seconds.
// slothashtable : Array of hash values, one per slot (slots are indexed across the page from top right as 0).
// configset : The currently selected config set.

// Create a new XHR object when loaded
var xh = false;
if (!xh && typeof XMLHttpRequest != 'undefined')
{
	xh = new XMLHttpRequest();
	if (xh.overrideMimeType)
 		xh.overrideMimeType('text/xml');

 	xh.onreadystatechange = xhr_process;
}

// Callback function to process the resulting XML from the service
function xhr_process()
{
	if (xh.readyState == 4)
	{
		if (xh.status == 200)
		{
			// Stop the refresh trigger during this processing
			stopRefresh();
			
			// Do something with the data result set
			var xmldata = xh.responseXML;
			
			var servertime = xmldata.getElementsByTagName("servertime")[0];
			if (document.getElementById("servertime"))
			{
				if (servertime.firstChild != null)
					document.getElementById("servertime").innerHTML = servertime.firstChild.nodeValue;
			}
			
			var slotentries = xmldata.getElementsByTagName("slot");
			for (var i = 0; i < slotentries.length; i++)
			{
				var slotentry = slotentries[i];
				var slotid = slotentry.getElementsByTagName("slotid")[0];
				var update = slotentry.getElementsByTagName("update")[0];
				if (update.firstChild.nodeValue == "1")
				{
					// Get the objects from the XML data
					var hval = slotentry.getElementsByTagName("hv")[0];
					var imgurl = slotentry.getElementsByTagName("imgsrc")[0];
					var devgrp = slotentry.getElementsByTagName("devgrp")[0];
					var name = slotentry.getElementsByTagName("name")[0];
					var reader = slotentry.getElementsByTagName("reader")[0];
					var tdate = slotentry.getElementsByTagName("tdate")[0];
					var tstat = slotentry.getElementsByTagName("tstat")[0];
					var slotname = slotid.firstChild.nodeValue;

					// Update the slot contents on the form
					slothashtable[i] = hval.firstChild.nodeValue;

					if (document.getElementById(slotname + "_devgrp"))
					{
						if (devgrp.firstChild == null)
							document.getElementById(slotname + "_devgrp").innerHTML = '';
						else
							document.getElementById(slotname + "_devgrp").innerHTML = devgrp.firstChild.nodeValue;
					}
					if (document.getElementById(slotname + "_name"))
					{
						if (name.firstChild == null)
							document.getElementById(slotname + "_name").innerHTML = '';
						else
							document.getElementById(slotname + "_name").innerHTML = name.firstChild.nodeValue;
					}
					if (document.getElementById(slotname + "_reader"))
					{
						if (reader.firstChild == null)
							document.getElementById(slotname + "_reader").innerHTML = '';
						else
							document.getElementById(slotname + "_reader").innerHTML = reader.firstChild.nodeValue;
					}
					if (document.getElementById(slotname + "_tdate"))
					{
						if (tdate.firstChild == null)
							document.getElementById(slotname + "_tdate").innerHTML = '';
						else
							document.getElementById(slotname + "_tdate").innerHTML = tdate.firstChild.nodeValue;
					}
					
					if (document.getElementById(slotname + "_staturl"))
					{
						if (tstat.firstChild == null)
							document.getElementById(slotname + "_staturl").src = '../appcore/images/acblank.png';
						else
						{
							tstatval = tstat.firstChild.nodeValue;
							if (tstatval == "true")
								document.getElementById(slotname + "_staturl").src = '../appcore/images/acgrant.png';
							else if (tstatval == "false")
								document.getElementById(slotname + "_staturl").src = '../appcore/images/acdeny.png';
							else
								document.getElementById(slotname + "_staturl").src = '../appcore/images/acblank.png';
						}
					}
					
					if (document.getElementById(slotname + "_img"))
						document.getElementById(slotname + "_img").src = imgurl.firstChild.nodeValue;
				}
			}
			// Start the refresh trigger after this processing
			startRefresh();
		}
	}
}

// Create the XHR call to the remote server using POST to send the data
var xhrservice = "";
var xhpoststring = "";
function xhr_call(xhpoststring)
{
	xh.open("POST", xhrservice, true);
	xh.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	// xh.setRequestHeader("Content-length", xhpoststring.length);
	// xh.setRequestHeader("Connection", "close");

	xh.send(xhpoststring);
}

// The refresh function that triggers the XHR calls to the server and updates
// the contents of the form.
// An array of data hashes for the form slots
var slothashtable = new Array();
var configset = 0;

function xhr_refresh()
{
	// This function is triggered by a timer interval, set when the form is loaded.
	// Send the devgroupname and the array of slothashvals to the server using xhr_call().
	// These are encoded as slothash[0]=val0&slothash[1]=val1...
	var nh = slothashtable.length;

	for (var i = 0; i < nh; i++)
	{
		if (i == 0)
			xhpoststring = "hv[" + i + "]=" + slothashtable[i];
		else
			xhpoststring = xhpoststring + "&" + "hv[" + i + "]=" + slothashtable[i];
	}
	
	xhpoststring = xhpoststring + "&" + "configset=" + configset + "&" + "sie=" + Math.random();
	
	xhr_call(xhpoststring);
}

// Setup the interval timer (in seconds). Should be called on form load.
var refreshID = 0;
var refreshinterval = 30;
function startRefresh()
{
	refreshID = window.setInterval(xhr_refresh, refreshinterval * 1000);
}

function stopRefresh()
{
	window.clearInterval(refreshID);
}
