function setupCfgset()
{
	// Gets the selected config set and sets it in the configset variable
	var selcfgset = $("#selcfgset").val();
	
	configset = selcfgset;
	xhr_refresh();
}

function initCfgSelect()
{
	// Gets the current value of configset and changes that option to selectable
	$("#selcfgset").val(configset);
}