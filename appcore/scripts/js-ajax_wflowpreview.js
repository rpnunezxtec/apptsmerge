// $Id:$
// Ajax functions for site editor workflow preview form updates

// Usage:
// 1. Set the service URL in 'xhrservice' global variable.
// 4. Call the startRefresh() function to start the timer.

// Variables (global):
// xh : XHR object.
// xhrservice : URL to service file (eg xsvc-wflowpreview.xas).

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
			// The resulting data is pure html page table contents
			var doc = document.implementation.createHTMLDocument();
			var htmlObject = doc.createElement('div');
			htmlObject.innerHTML = xh.responseText;
			
			var id = htmlObject.firstElementChild.id;
			
			document.getElementById(id).parentElement.innerHTML = xh.responseText;
			document.getElementById(id).style.display = 'block';
		}
	}
}

// Create the XHR call to the remote server using POST to send the data
var xhrservice = "";
var xhpoststring = "";
function xhr_post(xhpoststring)
{
	xh.open("POST", xhrservice, true);
	xh.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

	xh.send(xhpoststring);
}

// The called function that triggers the XHR calls to the server and updates
// the contents of the form.
function xhr_call(btn, wflow)
{
	// This function is triggered by a click of the wflow preview button
	var xhpoststring = "wflow=" + wflow;
	
	var cats = btn.parentElement.parentElement.parentElement.previousElementSibling.children;

	var i;
	for (i = 0; i < cats.length; i++) 
	{
		var boxes = cats[i].lastElementChild.children;
		
		var j;
		for(j = 0; j < boxes.length; j++)
		{
			if(boxes[j].firstElementChild != null)
			{
				if(boxes[j].firstElementChild.className != "popupbutton")
				{
					var check = boxes[j].firstElementChild.firstElementChild;
					
					if(check.checked == true)
					{
						xhpoststring = xhpoststring + "&" + check.name + "=" + check.value;
					}
					
				}
			}
		}
	}
	
	xhr_post(xhpoststring);
}
