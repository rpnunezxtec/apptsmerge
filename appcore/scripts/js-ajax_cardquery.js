// $Id:$
// Ajax functions for card query form updates

// Usage:
// 1. Set the service URL in 'xhrservice' global variable.
// 2. Set the device ID currently selected
// 3. Set the refreshInterval global to the interval in seconds.
// 4. Call the startRefresh() function to start the timer.

// Variables (global):
// xh : XHR object.
// xhrservice : URL to service file (eg xsvc-cardquery.xas).
// refreshID : Refresh timer object.
// refreshInterval : Refresh interval in seconds.
// deviceid : The currently selected device (also in the URL to allow bookmarking).

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
			// Do something with the data result set
			var xmldata = xh.responseXML;
			
			var update = xmldata.getElementsByTagName("update")[0];
			if (update.firstChild.nodeValue == "1")
			{
				// Get the objects from the XML data
				var hval = xmldata.getElementsByTagName("hv")[0];
				var portraiturl = xmldata.getElementsByTagName("portraiturl")[0];
				var portraitclass = xmldata.getElementsByTagName("portraitclass")[0];
				var entstatclass = xmldata.getElementsByTagName("entstatclass")[0];
				var entstatus = xmldata.getElementsByTagName("entstatus")[0];
				var name = xmldata.getElementsByTagName("name")[0];
				var region = xmldata.getElementsByTagName("region")[0];
				var affiliation = xmldata.getElementsByTagName("affiliation")[0];
				var tkntype = xmldata.getElementsByTagName("tkntype")[0];
				var tclearance = xmldata.getElementsByTagName("tclearance")[0];
				var tleo = xmldata.getElementsByTagName("tleo")[0];
				var tleoclass = xmldata.getElementsByTagName("tleoclass")[0];
				var tfero = xmldata.getElementsByTagName("tfero")[0];
				var tferoclass = xmldata.getElementsByTagName("tferoclass")[0];
				var tstatclass = xmldata.getElementsByTagName("tstatclass")[0];
				var tstatus = xmldata.getElementsByTagName("tstatus")[0];
				var ercog = xmldata.getElementsByTagName("ercog")[0];
				var ercogclass = xmldata.getElementsByTagName("ercogclass")[0];
				var ercoop = xmldata.getElementsByTagName("ercoop")[0];
				var ercoopclass = xmldata.getElementsByTagName("ercoopclass")[0];
				var eropron = xmldata.getElementsByTagName("eropron")[0];
				var eropronclass = xmldata.getElementsByTagName("eropronclass")[0];
				var eroprontier = xmldata.getElementsByTagName("eroprontier")[0];
				
				// Update the contents on the form
				formhash = hval.firstChild.nodeValue;

				if (document.getElementById("name"))
				{
					if (name.firstChild == null)
						document.getElementById("name").innerHTML = '';
					else
						document.getElementById("name").innerHTML = name.firstChild.nodeValue;
				}
				
				if (document.getElementById("d_name"))
				{
					if (name.firstChild == null)
						document.getElementById("d_name").innerHTML = '';
					else
						document.getElementById("d_name").innerHTML = name.firstChild.nodeValue;
				}
				
				if (document.getElementById("region"))
				{
					if (region.firstChild == null)
						document.getElementById("region").innerHTML = '';
					else
						document.getElementById("region").innerHTML = region.firstChild.nodeValue;
				}
				
				if (document.getElementById("d_region"))
				{
					if (region.firstChild == null)
						document.getElementById("d_region").innerHTML = '';
					else
						document.getElementById("d_region").innerHTML = region.firstChild.nodeValue;
				}
				
				if (document.getElementById("affiliation"))
				{
					if (affiliation.firstChild == null)
						document.getElementById("affiliation").innerHTML = '';
					else
						document.getElementById("affiliation").innerHTML = affiliation.firstChild.nodeValue;
				}
				
				if (document.getElementById("d_affiliation"))
				{
					if (affiliation.firstChild == null)
						document.getElementById("d_affiliation").innerHTML = '';
					else
						document.getElementById("d_affiliation").innerHTML = affiliation.firstChild.nodeValue;
				}
				
				if (document.getElementById("d_entstatus"))
				{
					if (entstatus.firstChild == null)
						document.getElementById("d_entstatus").innerHTML = '';
					else
						document.getElementById("d_entstatus").innerHTML = entstatus.firstChild.nodeValue;
				}
				
				if (document.getElementById("d_tkntype"))
				{
					if (tkntype.firstChild == null)
						document.getElementById("d_tkntype").innerHTML = '';
					else
						document.getElementById("d_tkntype").innerHTML = tkntype.firstChild.nodeValue;
				}
				
				if (document.getElementById("tclearance"))
				{
					if (tclearance.firstChild == null)
						document.getElementById("tclearance").innerHTML = '';
					else
						document.getElementById("tclearance").innerHTML = tclearance.firstChild.nodeValue;
				}
				
				if (document.getElementById("d_tclearance"))
				{
					if (tclearance.firstChild == null)
						document.getElementById("d_tclearance").innerHTML = '';
					else
						document.getElementById("d_tclearance").innerHTML = tclearance.firstChild.nodeValue;
				}
				
				if (document.getElementById("tleo"))
				{
					if (tleo.firstChild == null)
						document.getElementById("tleo").innerHTML = '';
					else
						document.getElementById("tleo").innerHTML = tleo.firstChild.nodeValue;
				}
				
				if (document.getElementById("d_tleo"))
				{
					if (tleo.firstChild == null)
						document.getElementById("d_tleo").innerHTML = '';
					else
						document.getElementById("d_tleo").innerHTML = tleo.firstChild.nodeValue;
				}
				
				if (document.getElementById("tfero"))
				{
					if (tfero.firstChild == null)
						document.getElementById("tfero").innerHTML = '';
					else
						document.getElementById("tfero").innerHTML = tfero.firstChild.nodeValue;
				}
				
				if (document.getElementById("d_tfero"))
				{
					if (tfero.firstChild == null)
						document.getElementById("d_tfero").innerHTML = '';
					else
						document.getElementById("d_tfero").innerHTML = tfero.firstChild.nodeValue;
				}
				
				if (document.getElementById("tstatus"))
				{
					if (tstatus.firstChild == null)
						document.getElementById("tstatus").innerHTML = '';
					else
						document.getElementById("tstatus").innerHTML = tstatus.firstChild.nodeValue;
				}
				
				if (document.getElementById("d_tstatus"))
				{
					if (tstatus.firstChild == null)
						document.getElementById("d_tstatus").innerHTML = '';
					else
						document.getElementById("d_tstatus").innerHTML = tstatus.firstChild.nodeValue;
				}
				
				if (entstatclass.firstChild == null)
					document.getElementById("d_entstatus").setAttribute("class", "x_stat_none");
				else
				{
					entstatclassval = entstatclass.firstChild.nodeValue;
					document.getElementById("d_entstatus").setAttribute("class", entstatclassval);
				}
				
				if (tstatclass.firstChild == null)
				{
					document.getElementById("d_tstatus").setAttribute("class", "x_stat_none");
					document.getElementById("tstatus").setAttribute("class", "x_stat_none");
				}
				else
				{
					tstatclassval = tstatclass.firstChild.nodeValue;
					document.getElementById("d_tstatus").setAttribute("class", tstatclassval);
					document.getElementById("tstatus").setAttribute("class", tstatclassval);
				}
				
				if (document.getElementById("portrait"))
					document.getElementById("portrait").src = portraiturl.firstChild.nodeValue;
					
				if (portraitclass.firstChild == null)
					document.getElementById("portrait").setAttribute("class", "x_img_none");
				else
				{
					pstatclassval = portraitclass.firstChild.nodeValue;
					document.getElementById("portrait").setAttribute("class", pstatclassval);
				}
				
				if (tleoclass.firstChild == null)
				{
					document.getElementById("tleo").setAttribute("class", "x_leo_hide");
					document.getElementById("d_tleo").setAttribute("class", "x_leo_hide");
				}
				else
				{
					tleoclassval = tleoclass.firstChild.nodeValue;
					document.getElementById("tleo").setAttribute("class", tleoclassval);
					document.getElementById("d_tleo").setAttribute("class", tleoclassval);
				}
				
				if (tferoclass.firstChild == null)
				{
					document.getElementById("tfero").setAttribute("class", "x_fero_hide");
					document.getElementById("d_tfero").setAttribute("class", "x_fero_hide");
				}
				else
				{
					tferoclassval = tferoclass.firstChild.nodeValue;
					document.getElementById("tfero").setAttribute("class", tferoclassval);
					document.getElementById("d_tfero").setAttribute("class", tferoclassval);
				}
				
				if (ercogclass.firstChild == null)
					document.getElementById("d_ercog").setAttribute("class", "x_ercog_hide");
				else
				{
					ercogclassval = ercogclass.firstChild.nodeValue;
					document.getElementById("d_ercog").setAttribute("class", ercogclassval);
				}
				
				if (document.getElementById("d_ercog"))
				{
					if (ercog.firstChild == null)
						document.getElementById("d_ercog").innerHTML = '';
					else
						document.getElementById("d_ercog").innerHTML = ercog.firstChild.nodeValue;
				}
				
				if (ercoopclass.firstChild == null)
					document.getElementById("d_ercoop").setAttribute("class", "x_ercoop_hide");
				else
				{
					ercoopclassval = ercoopclass.firstChild.nodeValue;
					document.getElementById("d_ercoop").setAttribute("class", ercoopclassval);
				}
				
				if (document.getElementById("d_ercoop"))
				{
					if (ercoop.firstChild == null)
						document.getElementById("d_ercoop").innerHTML = '';
					else
						document.getElementById("d_ercoop").innerHTML = ercoop.firstChild.nodeValue;
				}
				
			}
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
//	xh.setRequestHeader("Content-length", xhpoststring.length);
//	xh.setRequestHeader("Connection", "close");

	xh.send(xhpoststring);
}

// The refresh function that triggers the XHR calls to the server and updates
// the contents of the form.
var formhash = 0;
var deviceid = 0;
var stackval = 0;

function xhr_refresh()
{
	// This function is triggered by a timer interval, set when the form is loaded.
	// Send the deviceid and the current stackval and formhash to the server using xhr_call().
	xhpoststring = "deviceid=" + deviceid + "&stackval=" + stackval + "&formhash=" + formhash + "&" + "geometry=" + screen.width + "x" + screen.height;
	
	xhr_call(xhpoststring);
}

// Setup the interval timer (in seconds). Should be called on form load.
var refreshID = 0;
var refreshinterval = 30;
function startRefresh()
{
	refreshID = window.setInterval(xhr_refresh, refreshinterval * 1000);
}
