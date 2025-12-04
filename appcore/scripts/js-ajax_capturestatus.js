// $Id:$
// Ajax functions for srip form updates

// Usage:
// 1. Set the service URL in 'xhrcaptureservice' global variable.
// 2. Set the refreshInterval global to the interval in seconds.
// 3. Call the startRefresh() function to start the timer.

// Variables (global):
// xh : XHR object.
// xhrcaptureservice : URL to service file (eg xsvc-sripcapturestatus.xas).
// refreshcaptureID : Refresh timer object.
// refreshInterval : Refresh interval in seconds.
// qid : Unique identifier in queue for session
// stage : The current stage of the capture process
// Create a new XHR object when loaded
var xhcapture = false;
if (!xhcapture && typeof XMLHttpRequest != 'undefined')
{
	xhcapture = new XMLHttpRequest();
	if (xhcapture.overrideMimeType)
 		xhcapture.overrideMimeType('text/xml');

 	xhcapture.onreadystatechange = xhcapturer_process;
}

// Callback function to process the resulting XML from the service
function xhcapturer_process()
{
	if (xhcapture.readyState == 4)
	{
		if (xhcapture.status == 200)
		{
			// Stop the refresh trigger during this processing
			stopCaptureRefresh();
			
			// Do something with the data result set
			var xmldata = xhcapture.responseXML;
			
			var cap = xmldata.getElementsByTagName("capture");

			for (var i = 0; i < cap.length; i++)
			{
				var capentry = cap[i];
				
				// Check for an error first
				// Get type of capture first
				var error = capentry.getElementsByTagName("error")[0];
				error = error.innerHTML;
				if(error != "")
				{
					alert(error);
				}
				else
				{
					// Check for cancelled session next
					var cancelled = capentry.getElementsByTagName("cancelled")[0];
					
					if(cancelled != null)
					{
						alert("Session has been cancelled by the enrollee.");
						window.location.replace("/authentx/srip");
					}
					else
					{
						// Get type of capture first
						var captype = capentry.getElementsByTagName("capturetype")[0];
						captype = captype.innerHTML;
						
						if(captype != "")
						{
							if(captype == "portrait" || captype == "signature")
							{
								// Get the image from the XML data
								var imgsrc = capentry.getElementsByTagName("src")[0];
								imgsrc = imgsrc.innerHTML;
								if(imgsrc != "")
								{
									document.getElementById("captureditem").firstChild.nextSibling.src = 'data:image/jpeg;base64,' + imgsrc;
									
									if(document.getElementById("approve") != null)
										document.getElementById("approve").style.display = "";
									
									if(document.getElementById("reject") != null)
										document.getElementById("reject").style.display = "";
									
									if(document.getElementById("retry") != null)
										document.getElementById("retry").value = "RECAPTURE";
									
									if(document.getElementById("remarksblock") != null)
										document.getElementById("remarksblock").style.display = "";
									
									var captitlesrc = capentry.getElementsByTagName("capturetitle")[0];
									captitlesrc = captitlesrc.innerHTML;
									if(captitlesrc != "")
									{
										document.getElementById("capturetitle").innerHTML = captitlesrc;
									}
								}
							}
							else if(captype == "document")
							{
								// Get the image from the XML data
								var imgsrc = capentry.getElementsByTagName("src")[0];
								imgsrc = imgsrc.innerHTML;
								if(imgsrc != "")
								{
									var caption = capentry.getElementsByTagName("caption")[0];
									caption = caption.innerHTML;
								
									if(document.getElementById("missingdoc") != null)
										document.getElementById("missingdoc").style.display = "none";
									
									document.getElementById("docgrid").style.display = "";
									
									for(var i = 0; i < document.getElementById("docgrid").childNodes.length; i++)
									{
										var elemclass = document.getElementById("docgrid").childNodes[i].className;
										
										if(elemclass == "grid")
										{
											var docimg = document.getElementById("docgrid").childNodes[i].firstElementChild.src;
											
											if(docimg == 'data:image/jpeg;base64,' + imgsrc)
												break;
											
											if(docimg.indexOf("bg_spacer.png") !== -1)
											{
												document.getElementById("docgrid").childNodes[i].firstElementChild.src = 'data:image/jpeg;base64,' + imgsrc;
												document.getElementById("docgrid").childNodes[i].firstElementChild.className = "fpimage";
												
												if(caption != "")
												{
													document.getElementById("docgrid").childNodes[i].firstElementChild.nextElementSibling.innerHTML = caption;
												}
												
												// Display Save button
												document.getElementById("savebtn").style.display = "";
												
												break;
											}
										}
									}							
									
									document.getElementById("approve").style.display = "";
									document.getElementById("reject").style.display = "";
									document.getElementById("remarksblock").style.display = "";
									document.getElementById("docbtns").style.paddingTop = "5%";							
									
									var captitlesrc = capentry.getElementsByTagName("capturetitle")[0];
									captitlesrc = captitlesrc.innerHTML;
									if(captitlesrc != "")
									{
										document.getElementById("capturetitle").innerHTML = captitlesrc;
									}
								}
							}
							else if(captype == "fingerprint")
							{
								// Get the image from the XML data
								var fpsrc = capentry.getElementsByTagName("src")[0];
								
								if(fpsrc != "")
								{
									for(var i = 0; i < fpsrc.childNodes.length; i++)
									{
										var tagname = fpsrc.childNodes[i].tagName;
										if(document.getElementById(tagname + "_img") != null)
											document.getElementById(tagname + "_img").src = 'data:image/jpeg;base64,' + fpsrc.childNodes[i].innerHTML;
										else if(document.getElementById(tagname) != null)
										{
											document.getElementById(tagname).innerHTML = fpsrc.childNodes[i].innerHTML;
											document.getElementById(tagname).style.display = "";
										}
									}

									var captitlesrc = capentry.getElementsByTagName("capturetitle")[0];
									captitlesrc = captitlesrc.innerHTML;
									if(captitlesrc != "")
									{
										document.getElementById("capturetitle").innerHTML = captitlesrc;
									}
									
									document.getElementById("fpbtns").style.paddingTop = "15%";
									document.getElementById("approve").style.display = "";
									document.getElementById("reject").style.display = "";
									document.getElementById("retry").value = "RECAPTURE";
									document.getElementById("remarksblock").style.display = "";
								}
							}
							else if(captype == "iris")
							{
								// Get the image from the XML data
								var irissrc = capentry.getElementsByTagName("src")[0];
								
								if(irissrc != "")
								{
									for(var i = 0; i < irissrc.childNodes.length; i++)
									{
										var tagname = irissrc.childNodes[i].tagName;
										if(document.getElementById(tagname + "_img") != null)
											document.getElementById(tagname + "_img").src = 'data:image/jpeg;base64,' + irissrc.childNodes[i].innerHTML;
										else if(document.getElementById(tagname) != null)
										{
											document.getElementById(tagname).innerHTML = irissrc.childNodes[i].innerHTML;
											document.getElementById(tagname).style.display = "";
										}
									}

									var captitlesrc = capentry.getElementsByTagName("capturetitle")[0];
									captitlesrc = captitlesrc.innerHTML;
									if(captitlesrc != "")
									{
										document.getElementById("capturetitle").innerHTML = captitlesrc;
									}
									
									document.getElementById("irisbtns").style.paddingTop = "15%";
									document.getElementById("approve").style.display = "";
									document.getElementById("reject").style.display = "";
									document.getElementById("retry").value = "RECAPTURE";
									document.getElementById("remarksblock").style.display = "";
								}
							}
						}
					}
				}
			}
			// Start the refresh trigger after this processing
			startCaptureStatus();
		}
	}
}

// Create the XHR call to the remote server using POST to send the data
var xhrsripcaptureservice = "";

function xhrcapture_call()
{
	xhcapture.open("GET", xhrsripcaptureservice + "?qid=" + qid + "&stage=" + stage + "&sie=" + Math.random(), true);
	xhcapture.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

	xhcapture.send(null);
}

// The refresh function that triggers the XHR calls to the server and updates
// the contents of the form.
function xhrcapture_refresh()
{
	xhrcapture_call();
}

// Setup the interval timer (in seconds). Should be called on form load.
var refreshcaptureID = 0;
var refreshcaptureinterval = 2;
function startCaptureStatus()
{
	refreshcaptureID = window.setTimeout(xhrcapture_refresh, refreshcaptureinterval * 1000);
}

function stopCaptureRefresh()
{
	window.clearInterval(refreshcaptureID);
}
