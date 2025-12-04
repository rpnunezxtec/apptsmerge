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
			// Stop the refresh counter
			stopRefresh();
			stopSessionTimer();
			
			// Read the new time values in the XML result
			// <sessionrefresh>
			// <sesstime>$sessiontimeleft</sesstime>
			// <sesstimeout>SESSION_TIMEOUT</sesstimeout>
			// <sessgrace>SESSION_TIMEOUT_GRACE</sessgrace>
			// </sessionrefresh>
			var xmldata = xh.responseXML;
			
			var sesstime = xmldata.getElementsByTagName("sesstime")[0];
			if (sesstime.firstChild != null)
				refreshinterval = sesstime.firstChild.nodeValue;
			
			var sesstimeout = xmldata.getElementsByTagName("sesstimeout")[0];
			if (sesstimeout.firstChild != null)
				sessionTime = sesstimeout.firstChild.nodeValue;
			
			
			var sessgrace = xmldata.getElementsByTagName("sessgrace")[0];
			if (sessgrace.firstChild != null)
				gracetime = sessgrace.firstChild.nodeValue;
			
			// Start the refresh counter
			startRefresh();
			startSessionTimer();
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
	xh.send(xhpoststring);
}

function xhr_refresh()
{
	// Called when the session is to be refreshed.
	// Stops the grace time counter and contacts the session refresh service.
	// The result will contain a new session time and grace period, which are
	// loaded into the variables and the refresh timer restarted.
		
	xhpoststring = "sie=" + Math.random();
	xhr_call(xhpoststring);
}

// Setup the interval timer (in seconds). Should be called on form load.
// This will trigger session_alert() after the refreshInterval has expired.
var refreshID = 0;
var refreshinterval = 120;
function startRefresh()
{
	refreshID = window.setInterval(session_alert, refreshinterval * 1000);
}

function stopRefresh()
{
	window.clearInterval(refreshID);
}

// Triggered every second during the session period to update the remaining time
// Calls sessionCountdown() to perform the update
var sessionTimerID = 0;
var sessionTime = 120;
function startSessionTimer()
{
	sessionTimerID = window.setInterval(sessionCountdown, 1000);
}

function stopSessionTimer()
{
	window.clearInterval(sessionTimerID);
}

// Triggered every second during the grace period to update the remaining time
// Calls gracecountdown() to perform the update
var gracetimeID = 0;
var gracetime = 30;
function startGrace()
{
	gracetimeID = window.setInterval(gracecountdown, 1000);
}

function stopGrace()
{
	window.clearInterval(gracetimeID);
}

// Called when the session is about to expire
// Starts the gracetime countdown
function session_alert()
{
	startGrace();
	if (confirm("Session is about to expire. Click OK to refresh."))
	{
		stopGrace();
		xhr_refresh();
	}
}

// Called every second during the grace period countdown
function gracecountdown()
{
	if (gracetime > 0)
	{
		gracetime--;
		if (document.getElementById("gracetime"))
			document.getElementById("gracetime").innerHTML = gracetime;
	}
}

// Called every second during the session period countdown
function sessionCountdown()
{
	if (sessionTime > 0)
	{
		sessionTime--;
		if (document.getElementById("remainingsessiontime"))
			document.getElementById("remainingsessiontime").innerHTML = sessionTime;
	}
}
