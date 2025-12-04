// $Id:$
// Ajax function for acknowledging alerts

// Usage:
// 1. Click on flashing status alert bubble.
// 2. Calls AJAX that triggers a handler for acknowledging alerts.

// Variables (global):
// idString : String that holds variables for alertid, deviceid, and input
// prevIdString : String that holds the previous idString


// Create a new XHR object when loaded

let idString = "";

// Create the XHR call to the remote server using POST to send the data
let acknowledgeservice = "";

// Handles an acknowledged alert
const handleAcknowledge = (e) => {
	if (e.target.id.includes("alertid") && e.target.id.includes("deviceid") && e.target.id.includes("input")) {
		idString = e.target.id;

		// Preventing multiple clicks for the same alert
		if (idString.localeCompare(prevIdString) !== 0) {
			prevIdString = idString;
			lastAcknowledged = idString.substring(8, 18);

			e.target.className = "circle alert-acknowledged";

			// Hide the target element
			e.target.parentNode.parentNode.style.display = "none";

			// Create XMLHttpRequest object
			let xhr = new XMLHttpRequest();

			// Configure POST request
			xhr.open("POST", acknowledgeservice, true);
			xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

			// Handle response
			xhr.onreadystatechange = function () {
				if (xhr.readyState === 4 && xhr.status === 200) {
					//alert(`Alert-${lastAcknowledged.substring(6, 10)} has been acknowledged.`);
				}
			};

			// Send POST request with JavaScript variable as data
			xhr.send(idString);
		}
	}
}