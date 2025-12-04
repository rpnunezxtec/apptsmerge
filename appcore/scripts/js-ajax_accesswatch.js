// $Id:$
// Ajax functions for card query form updates

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
			// Do something with the data result set
			var xmldata = xh.responseXML;
			
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
					var entstat = slotentry.getElementsByTagName("entstat")[0];
					var region = slotentry.getElementsByTagName("region")[0];
					var affiliation = slotentry.getElementsByTagName("affiliation")[0];
					var edipi = slotentry.getElementsByTagName("edipi")[0];
					var tstatus = slotentry.getElementsByTagName("tstatus")[0];
					var tleo = slotentry.getElementsByTagName("tleo")[0];
					var tfero = slotentry.getElementsByTagName("tfero")[0];
					var tclearance = slotentry.getElementsByTagName("tclearance")[0];
					var ttype = slotentry.getElementsByTagName("ttype")[0];
					var ercog = slotentry.getElementsByTagName("ercog")[0];
					var ercoop = slotentry.getElementsByTagName("ercoop")[0];
					var eropron = slotentry.getElementsByTagName("eropron")[0];
					var eroprontier = slotentry.getElementsByTagName("eroprontier")[0];
					var imgclass = slotentry.getElementsByTagName("imgclass")[0];
					var entstatclass = slotentry.getElementsByTagName("entstatclass")[0];
					var tstatclass = slotentry.getElementsByTagName("tstatclass")[0];
					var tleoclass = slotentry.getElementsByTagName("tleoclass")[0];
					var tferoclass = slotentry.getElementsByTagName("tferoclass")[0];
					var ercogclass = slotentry.getElementsByTagName("ercogclass")[0];
					var ercoopclass = slotentry.getElementsByTagName("ercoopclass")[0];
					var eropronclass = slotentry.getElementsByTagName("eropronclass")[0];
					
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
					if (document.getElementById(slotname + "_tstat"))
					{
						if (tstat.firstChild == null)
						{
							document.getElementById(slotname + "_tstat").innerHTML = "";
							document.getElementById(slotname + "_tstat").setAttribute("class", "x_formtext");
						}
						else
						{
							tstatval = tstat.firstChild.nodeValue;
							if (tstatval == "true")
							{
								document.getElementById(slotname + "_tstat").innerHTML = "Access Granted";
								document.getElementById(slotname + "_tstat").setAttribute("class", "x_acgrant");
							}
							else if (tstatval == "false")
							{
								document.getElementById(slotname + "_tstat").innerHTML = "Access Denied";
								document.getElementById(slotname + "_tstat").setAttribute("class", "x_acdeny");
							}
							else
							{
								document.getElementById(slotname + "_tstat").innerHTML = "";
								document.getElementById(slotname + "_tstat").setAttribute("class", "x_formtext");
							}
						}
					}
					if (document.getElementById(slotname + "_img"))
					{
						document.getElementById(slotname + "_img").src = imgurl.firstChild.nodeValue;
						if (imgclass.firstChild == null)
							document.getElementById(slotname + "_img").setAttribute("class", "x_img_none");
						else
						{
							imgclassval = imgclass.firstChild.nodeValue;
							document.getElementById(slotname + "_img").setAttribute("class", imgclassval);
						}
					}
					
					// Details form
					if (document.getElementById(slotname + "_d_name"))
					{
						if (name.firstChild == null)
							document.getElementById(slotname + "_d_name").innerHTML = '';
						else
							document.getElementById(slotname + "_d_name").innerHTML = name.firstChild.nodeValue;
					}
					if (document.getElementById(slotname + "_d_region"))
					{
						if (region.firstChild == null)
							document.getElementById(slotname + "_d_region").innerHTML = '';
						else
							document.getElementById(slotname + "_d_region").innerHTML = region.firstChild.nodeValue;
					}
					if (document.getElementById(slotname + "_d_affiliation"))
					{
						if (affiliation.firstChild == null)
							document.getElementById(slotname + "_d_affiliation").innerHTML = '';
						else
							document.getElementById(slotname + "_d_affiliation").innerHTML = affiliation.firstChild.nodeValue;
					}
					if (document.getElementById(slotname + "_d_edipi"))
					{
						if (edipi.firstChild == null)
							document.getElementById(slotname + "_d_edipi").innerHTML = '';
						else
							document.getElementById(slotname + "_d_edipi").innerHTML = edipi.firstChild.nodeValue;
					}
					if (document.getElementById(slotname + "_d_entstatus"))
					{
						if (entstat.firstChild == null)
							document.getElementById(slotname + "_d_entstatus").innerHTML = '';
						else
							document.getElementById(slotname + "_d_entstatus").innerHTML = entstat.firstChild.nodeValue;
						
						if (entstatclass.firstChild == null)
							document.getElementById(slotname + "_d_entstatus").setAttribute("class", "x_stat_none");
						else
						{
							entstatclassval = entstatclass.firstChild.nodeValue;
							document.getElementById(slotname + "_d_entstatus").setAttribute("class", entstatclassval);
						}
					}
					if (document.getElementById(slotname + "_d_ttype"))
					{
						if (ttype.firstChild == null)
							document.getElementById(slotname + "_d_ttype").innerHTML = '';
						else
							document.getElementById(slotname + "_d_ttype").innerHTML = ttype.firstChild.nodeValue;
					}
					if (document.getElementById(slotname + "_d_tstatus"))
					{
						if (tstatus.firstChild == null)
							document.getElementById(slotname + "_d_tstatus").innerHTML = '';
						else
							document.getElementById(slotname + "_d_tstatus").innerHTML = tstatus.firstChild.nodeValue;
							
						if (tstatclass.firstChild == null)
							document.getElementById(slotname + "_d_tstatus").setAttribute("class", "x_stat_none");
						else
						{
							tstatclassval = tstatclass.firstChild.nodeValue;
							document.getElementById(slotname + "_d_tstatus").setAttribute("class", tstatclassval);
						}
					}
					if (document.getElementById(slotname + "_d_tleo"))
					{
						if (tleo.firstChild == null)
							document.getElementById(slotname + "_d_tleo").innerHTML = '';
						else
							document.getElementById(slotname + "_d_tleo").innerHTML = tleo.firstChild.nodeValue;
							
						if (tleoclass.firstChild == null)
							document.getElementById(slotname + "_d_tleo").setAttribute("class", "x_leo_hide");
						else
						{
							tleoclassval = tleoclass.firstChild.nodeValue;
							document.getElementById(slotname + "_d_tleo").setAttribute("class", tleoclassval);
						}
					}
					if (document.getElementById(slotname + "_d_tfero"))
					{
						if (tfero.firstChild == null)
							document.getElementById(slotname + "_d_tfero").innerHTML = '';
						else
							document.getElementById(slotname + "_d_tfero").innerHTML = tfero.firstChild.nodeValue;
							
						if (tferoclass.firstChild == null)
							document.getElementById(slotname + "_d_tfero").setAttribute("class", "x_fero_hide");
						else
						{
							tferoclassval = tferoclass.firstChild.nodeValue;
							document.getElementById(slotname + "_d_tfero").setAttribute("class", tferoclassval);
						}
					}
					if (document.getElementById(slotname + "_d_tclearance"))
					{
						if (tclearance.firstChild == null)
							document.getElementById(slotname + "_d_tclearance").innerHTML = '';
						else
							document.getElementById(slotname + "_d_tclearance").innerHTML = tclearance.firstChild.nodeValue;
					}
					if (document.getElementById(slotname + "_d_ercog"))
					{
						if (ercog.firstChild == null)
							document.getElementById(slotname + "_d_ercog").innerHTML = '';
						else
							document.getElementById(slotname + "_d_ercog").innerHTML = ercog.firstChild.nodeValue;
							
						if (ercogclass.firstChild == null)
							document.getElementById(slotname + "_d_ercog").setAttribute("class", "x_ercog_hide");
						else
						{
							ercogclassval = ercogclass.firstChild.nodeValue;
							document.getElementById(slotname + "_d_ercog").setAttribute("class", ercogclassval);
						}
					}
					if (document.getElementById(slotname + "_d_ercoop"))
					{
						if (ercoop.firstChild == null)
							document.getElementById(slotname + "_d_ercoop").innerHTML = '';
						else
							document.getElementById(slotname + "_d_ercoop").innerHTML = ercoop.firstChild.nodeValue;
							
						if (ercoopclass.firstChild == null)
							document.getElementById(slotname + "_d_ercoop").setAttribute("class", "x_ercoop_hide");
						else
						{
							ercoopclassval = ercoopclass.firstChild.nodeValue;
							document.getElementById(slotname + "_d_ercoop").setAttribute("class", ercoopclassval);
						}
					}
					if (document.getElementById(slotname + "_d_eropron"))
					{
						if (eropron.firstChild == null)
							document.getElementById(slotname + "_d_eropron").innerHTML = '';
						else
							document.getElementById(slotname + "_d_eropron").innerHTML = eropron.firstChild.nodeValue;
							
						if (eropronclass.firstChild == null)
							document.getElementById(slotname + "_d_eropron").setAttribute("class", "x_eropron_hide");
						else
						{
							eropronclassval = eropronclass.firstChild.nodeValue;
							document.getElementById(slotname + "_d_eropron").setAttribute("class", eropronclassval);
						}
					}
					if (document.getElementById(slotname + "_d_eroprontier"))
					{
						if (eroprontier.firstChild == null)
							document.getElementById(slotname + "_d_eroprontier").innerHTML = '';
						else
							document.getElementById(slotname + "_d_eroprontier").innerHTML = eroprontier.firstChild.nodeValue;
					}
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
	
	xhpoststring = xhpoststring + "&" + "configset=" + configset + "&" + "geometry=" + screen.width + "x" + screen.height;
	
	xhr_call(xhpoststring);
}

// Setup the interval timer (in seconds). Should be called on form load.
var refreshID = 0;
var refreshinterval = 30;
function startRefresh()
{
	refreshID = window.setInterval(xhr_refresh, refreshinterval * 1000);
}
