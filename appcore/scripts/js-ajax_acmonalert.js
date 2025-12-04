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
// slothashtable : Array of hash values, one per slot (slots are indexed across the page from top right as 0).
// devgroupname : The device group name (OR)
// devicename : The device name

// Create a new XHR object when loaded
var xha = false;
if (!xha && typeof XMLHttpRequest != 'undefined')
{
	xha = new XMLHttpRequest();
	if (xha.overrideMimeType)
 		xha.overrideMimeType('text/xml');

 	xha.onreadystatechange = xhar_process;
}

var alertMSG = "";

// Callback function to process the resulting XML from the service
function xhar_process()
{
	if (xha.readyState == 4)
	{
		if (xha.status == 200)
		{
			// Do something with the data result set
			var xmldata = xha.responseXML;
			
			var servertime = xmldata.getElementsByTagName("servertime")[0];
			document.getElementById("servertime").innerHTML = servertime.firstChild.nodeValue;
			
			var slotentries = xmldata.getElementsByTagName("slot");
			for (var i = 0; i < slotentries.length; i++)
			{
				var slotentry = slotentries[i];
				var slotid = slotentry.getElementsByTagName("slotid")[0];

				// Get the objects from the XML data
				var devstatus = slotentry.getElementsByTagName("status")[0];
				var alarmtext = slotentry.getElementsByTagName("alarmtext")[0];
				var deviceid = slotentry.getElementsByTagName("deviceid")[0];
				var tdate = slotentry.getElementsByTagName("tdate")[0];
				var tstat = slotentry.getElementsByTagName("tstat")[0];
				var slotname = slotid.firstChild.nodeValue;

				// get the device in the list
				var selectElementID = deviceid.firstChild.nodeValue.replace(" ", "_");
				var deviceElement = document.getElementById(selectElementID);
				
				// get status
				var statusClass = deviceElement.className;
				
				// update class if different 
				if(devstatus.firstChild.nodeValue == "alert")
				{					
					// if the current status is alert, dont set the class anc show a different message
					/*if(statusClass == "alert")
					{
						alertMSG = "Additional alarm for device " + deviceid.firstChild.nodeValue + " detected. Alarm: " + alarmtext.firstChild.nodeValue;
						alert(alertMSG);
					}
					else*/
					if(statusClass != "alert")
					{
						deviceElement.setAttribute("class", devstatus.firstChild.nodeValue);
						alertMSG = "Status changed from " + statusClass + " to " + devstatus.firstChild.nodeValue + " for device " + deviceid.firstChild.nodeValue + ". Alarm: " + alarmtext.firstChild.nodeValue;

						window.open("../accessmonitor/pop-alert.html?alertMSG=" + encodeURI(alertMSG) + "&devid=" + encodeURI(deviceid.firstChild.nodeValue) + "&alarm=" + encodeURI(alarmtext.firstChild.nodeValue), "_blank", "popup");
					}
				}
			}
		}
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
var devicenameAlert = "";

function xhar_refresh()
{
	// This function is triggered by a timer interval, set when the form is loaded.
	// Send the devgroupname and the array of slothashvals to the server using xhr_call().
	// These are encoded as devgroupname=name&slothash[0]=val0&slothash[1]=val1...
	var nh = selectedDevices.length;
	if (devicenameAlert == "")
		xhapoststring = "devgroupname=" + devgroupname;

	for (var i = 0; i < nh; i++)
		xhapoststring = xhapoststring + "&" + "devices[" + i + "]=" + selectedDevices[i];
	
	xhar_call(xhapoststring);
}

// Setup the interval timer (in seconds). Should be called on form load.
var refreshIDAlert = 0;
var refreshintervalAlert = 30;
function startRefreshAlert()
{
	refreshIDAlert = window.setInterval(xhar_refresh, refreshintervalAlert * 1000);
}

