function frmCheckPEUser()
{
	var ok = 1;
  
	var x = document.forms["mainform"].elements;
	if (isNaN(x['weight'].value))
	{
		ok = 0;
		alert('Weight must be numeric only');
	}
	
	if (isNaN(x['height'].value))
	{
		ok = 0;
		alert('Height must be numeric only');
	}
  
	if (ok)
		return true;
	else
		return false;
}

function validateNumber(ev) {
	
	// Only allow numbers, decimal points and essential keys
	
	return (	// Ctrl and Alt keys
				ev.ctrlKey || ev.altKey 
				// Numbers 0-9 not including characters on keys above numbers
				|| (47<ev.keyCode && ev.keyCode<58 && ev.shiftKey==false)
				// Numbers 0-9 on number pad
				|| (95<ev.keyCode && ev.keyCode<106)
				// Backspace and tab
				|| (ev.keyCode==8) || (ev.keyCode==9)
				// Decimal Point or period
				|| (ev.keyCode==110) || (ev.keyCode==190 && ev.shiftKey==false)
				// Arrows, page down end and home
				|| (ev.keyCode>34 && ev.keyCode<40)
				// Delete
				|| (ev.keyCode==46) 
			);
};

function validateInpt(input) 
{
	var type = input.name;
	var val = input.value;
	
	if(type.includes('upn') == true)
	{
		if(input.value.includes('@') == false)
			alert('Wrong UPN Value Format. Please check your value and try again.');
	}
	else
	{
		var badChars = /[\'^£$%&*()}{#~?><>|=_+¬]/;
	
		var found = val.match(badChars);

		if(found != null)
		{
			alert('Wrong Value Format. Please check your value and try again.');
		}
	}
}