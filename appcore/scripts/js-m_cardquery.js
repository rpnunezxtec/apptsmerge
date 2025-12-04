function setupDevice()
{
	// Gets the selected deviceID and sets it in the deviceid variable
	var selDevice = $("#seldeviceid").val();
	
	deviceid = selDevice;
	formhash = '0';
	xhr_refresh();
}

