function attrCheck()
{
	if(document.searchform.region.value != "all")
	{
 		if(document.searchform.attr.value != "lastname")
		{
			 alert("ERROR: Attribute selection must be Last Name when searching within an Agency. To search by Login ID, select all for the Agency value.");
			 return false;
		}
	}
	return true;
	
}
