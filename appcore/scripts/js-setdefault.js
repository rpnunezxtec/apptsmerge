let pageDefaults = "";

function setDefaults() {
	if (typeof pageDefaults !== 'undefined') {
	  // Loop through each key in pageDefaults
	  for (var key in pageDefaults) {
		if (pageDefaults.hasOwnProperty(key)) {
		  var fields = document.getElementsByName(key);
  
		  // If the default is an array (for checkbox groups)
		  if (Array.isArray(pageDefaults[key])) {
			var visibleFields = [];
			// Filter out hidden elements
			for (var i = 0; i < fields.length; i++) {
			  if (fields[i].type !== 'hidden') {
				visibleFields.push(fields[i]);
			  }
			}
			// Apply boolean defaults to visible checkboxes by index
			for (var i = 0; i < visibleFields.length; i++) {
			  if (i < pageDefaults[key].length) {
				visibleFields[i].checked = pageDefaults[key][i];
			  }
			}
		  } else {
			// Use the last element (could be hidden or visible) for non-array defaults
			var targetField = fields[fields.length - 1];
			var fieldType = targetField.type;
  
			if (fieldType === 'radio') {
			  // Check the radio button that matches the default value
			  for (var i = 0; i < fields.length; i++) {
				fields[i].checked = (fields[i].value == pageDefaults[key]);
			  }
			} else if (fieldType === 'checkbox') {
			  // Set singular checkbox's state based on the default
			  targetField.checked = !!pageDefaults[key];
			} else {
			  // Set the value for text inputs, selects, hidden fields, etc.
			  targetField.value = pageDefaults[key];
			}
		  }
		}
	  }
	}
  }
  