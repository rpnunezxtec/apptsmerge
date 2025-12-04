// $Id:$
// Ajax functions for session refresh

// Usage:
// 1. Set the service URL in 'xhrservice' global variable.
// 2. Set the refreshInterval global to the interval in seconds (session timeout value).
// 3. Call the startRefresh() function to start the timer.

// When the timer expires it will alert the user that the session is about to expire, 
// and wait for the user to click 'OK' in the confirm dialogue.
// Another timer will be started with the grace period counting down for display if required.
// Clicking refresh will call the refresh service, otherwise the session will timeout.

// Variables (global):
// xh : XHR object.
// xhrservice : URL to service file.
// refreshID : Refresh timer object.
// refreshInterval : Refresh interval in seconds.
// gracetimeID : Grace timer object.
// gracetime : Grace time in seconds.

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
			// Read the new time values in the XML result
			// <reportresponse>
			// <sesstime>$sessiontimeleft</sesstime>
			// <sesstimeout>SESSION_TIMEOUT</sesstimeout>
			// <sessgrace>SESSION_TIMEOUT_GRACE</sessgrace>
			// </reportresponse>
			var xmldata = xh.responseText;
			
			var parser = new DOMParser();
			var xmlDoc = parser.parseFromString(xmldata, "application/xml");
			
			var error = xmlDoc.getElementsByTagName("error")[0].innerHTML;
			
			if(error != '1')
			{
				// proceed with request
				var reportElementstag = xmlDoc.getElementsByTagName("reportelements")[0];
				var reportElementsArray = reportElementstag.childNodes;
				
				var i;
				
				// process each element
				for (i = 0; i < reportElementsArray.length; i++) 
				{
					var elementName = reportElementsArray[i].nodeName;					
					var elementValue = reportElementsArray[i].innerHTML;
					var domElement = document.getElementsByName(elementName)[0];
					var elementType = domElement.type;
					
					switch(elementType) 
					{
						case "checkbox":
						
							if(elementValue == 'on')
							{
								domElement.checked = true;
							}
							else
								domElement.checked = false;
							
						break;
					 
						case "text":
							
							domElement.value = elementValue;
							
						break;
						
						case "select-one":
							
							document.body.classList.add('keyboard-focused');
							
							elementValue = decodeHTMLEntities(elementValue);
							
							var liList = domElement.previousSibling.previousSibling.getElementsByTagName("li");
							
							var len = liList.length;
							
							for (var j = 0; j < len; j++) 
							{
								if (decodeHTMLEntities(liList[j].firstChild.innerHTML) == elementValue) 
								{
									liList[j].classList.add("selected");
									
									$('select[name="' + elementName + '"]').val(elementValue);
								}
								else
								{
									liList[j].classList.remove("selected");
								}									
							}
							
							$('select[name="' + elementName + '"]').formSelect();
							
						break;
						
					  default:
						alert("Unable to determine type of element.");
					}
				}
			}
			else
			{
				var errorMsg = xmlDoc.getElementsByTagName("errormsg")[0].innerHTML;
				
				alert("Error fetching report: " + errorMsg);
			}
		}
	}
}

// Create the XHR call to the remote server using POST to send the data
var xhrservice = "";
function xhr_call(reportName)
{
	// Check for value in 'site' and add the URL parameters if necessary
	var getstr = "";
	getstr = "?rn=" + reportName;

	xh.open("GET", xhrservice + getstr, true);
	xh.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

	xh.send();
}

function selectReport(selectElement)
{
	var reportName = selectElement.value;
	
	//if(reportName.length == 0)
	//{
		//alert("Select valid report.");
		//return;
	//}
	
	xhr_call(reportName);
}

function decodeHTMLEntities(text) {
    var entities = [
        ['amp', '&'],
        ['apos', '\''],
        ['#x27', '\''],
        ['#x2F', '/'],
        ['#39', '\''],
        ['#47', '/'],
        ['lt', '<'],
        ['gt', '>'],
        ['nbsp', ' '],
        ['quot', '"']
    ];

    for (var i = 0, max = entities.length; i < max; ++i) 
        text = text.replace(new RegExp('&'+entities[i][0]+';', 'g'), entities[i][1]);

    return text;
}