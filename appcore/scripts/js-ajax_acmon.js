// $Id:$
// Ajax functions for access monitor form updates

// Usage:
// 1. Set the service URL in 'xhrservice' global variable.
// 2. Assign the initial data hash values in the slothashtable array for each slot.
// 3. Set the device group or device name global variable.
// 4. Set the refreshInterval global to the interval in seconds.
// 5. Call the startRefresh() function to start the timer.

// Variables (global):
// xhac : XHR object.
// xhrservice : URL to service file (eg xsvc-acmonitor.xas).
// refreshID : Refresh timer object.
// refreshInterval : Refresh interval in seconds.
// slothashtable : Array of hash values, one per slot (slots are indexed across the page from top right as 0).
// devgroupname : The device group name (OR)
// devicename : The device name

// Create a new XHR object when loaded
var xhac = false;
if (!xhac && typeof XMLHttpRequest != 'undefined')
{
	xhac = new XMLHttpRequest();
	if (xhac.overrideMimeType)
 		xhac.overrideMimeType('text/xml');

 	xhac.onreadystatechange = xhac_process;
}

// Callback function to process the resulting XML from the service
function xhac_process()
{
	if (xhac.readyState == 4)
	{
		if (xhac.status == 200)
		{
			// Stop refresh while html updates
			stopRefreshAcmon()

			// Do something with the data result set
			var xmldata = xhac.responseXML;
			
			var servertime = xmldata.getElementsByTagName("servertime")[0];
			document.getElementById("servertime").innerHTML = servertime.firstChild.nodeValue;
			
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
					var name = slotentry.getElementsByTagName("name")[0];
					var reader = slotentry.getElementsByTagName("reader")[0];
					var tdate = slotentry.getElementsByTagName("tdate")[0];
					var tstat = slotentry.getElementsByTagName("tstat")[0];
					var input = slotentry.getElementsByTagName("input")[0];
					var slotname = slotid.firstChild.nodeValue;

					// Update the slot contents on the form
					slothashtable[i] = hval.firstChild.nodeValue;

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
					if (document.getElementById(slotname + "_tstat"))
					{
						if (tstat.firstChild == null)
						{
							document.getElementById(slotname + "_tstat").innerHTML = "";
							document.getElementById(slotname + "_tstat").setAttribute("class", "proptext");
						}
						else
						{
							tstatval = tstat.firstChild.nodeValue;
							if (tstatval == "true")
							{
								document.getElementById(slotname + "_tstat").innerHTML = "Access Granted";
								document.getElementById(slotname + "_tstat").setAttribute("class", "acgrant");
							}
							else if (tstatval == "false")
							{
								document.getElementById(slotname + "_tstat").innerHTML = "Access Denied";
								document.getElementById(slotname + "_tstat").setAttribute("class", "acdeny");
							}
							else
							{
								document.getElementById(slotname + "_tstat").innerHTML = "";
								document.getElementById(slotname + "_tstat").setAttribute("class", "proptext");
							}
						}
					}
					if (document.getElementById(slotname + "_input")) {
						if (input.firstChild == null)
							document.getElementById(slotname + "_input").innerHTML = '';
						else
							document.getElementById(slotname + "_input").innerHTML = input.firstChild.nodeValue;
					}
					if (document.getElementById(slotname + "_img"))
						document.getElementById(slotname + "_img").src = imgurl.firstChild.nodeValue;
				}
			}

			// Start the refresh trigger after this processing
			startRefreshAcmon()
		}
	}
}

// Create the XHR call to the remote server using POST to send the data
var xhacservice = "";
var xhacpoststring = "";
function xhac_call(xhacpoststring)
{
	xhac.open("POST", xhacservice, true);
	xhac.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	//xhac.setRequestHeader("Content-length", xhpoststring.length);
	//xhac.setRequestHeader("Connection", "close");

	xhac.send(xhacpoststring);
}

// The refresh function that triggers the XHR calls to the server and updates
// the contents of the form.
// An array of data hashes for the form slots
var slothashtable = new Array();
var devgroupname = "";
var devicename = "";

function xhac_refresh()
{
	// This function is triggered by a timer interval, set when the form is loaded.
	// Send the devgroupname and the array of slothashvals to the server using xhr_call().
	// These are encoded as devgroupname=name&slothash[0]=val0&slothash[1]=val1...
	var nh = slothashtable.length;
	if (devicename == "")
		xhacpoststring = "devgroupname=" + devgroupname;
	else
		xhacpoststring = "devicename=" + devicename;

	for (var i = 0; i < nh; i++)
		xhacpoststring = xhacpoststring + "&" + "hv[" + i + "]=" + slothashtable[i];
	
	xhac_call(xhacpoststring);
}

// Setup the interval timer (in seconds). Should be called on form load.
var refreshIDAcmon = 0;
var refreshintervalAcmon = 2;
function startRefreshAcmon()
{
	refreshIDAcmon = window.setInterval(xhac_refresh, refreshintervalAcmon * 1000);
}

function stopRefreshAcmon() {
	window.clearInterval(refreshIDAcmon);
}

// Add event listeners for page unload events
window.addEventListener('beforeunload', stopRefreshAcmon);
window.addEventListener('unload', stopRefreshAcmon);