function revokeConfirm()
{
	var x = confirm("Warning: This action cannot be undone. Continue?");
	if (x)
		return true;
	else
		return false;
}

function reasonCheck()
{
	if(document.revokeform.reason.value == "")
	{
		alert("You must select a reason.");
		return false;
	}
	else
		return true;
}

function revokeCheck()
{
	if(revokeConfirm())
		if(reasonCheck())
			return true;
		else
			return false;
	else
		return false;

}

function reasonCheck2()
{
	if(document.mainform.reasondbval.value == "")
	{
		alert("You must select a reason.\nBe sure to save the reason to the database.");
		return false;
	}
	else
		return true;
}

function revokeCheck2()
{
	if(revokeConfirm())
		if(reasonCheck2())
			return true;
		else
			return false;
	else
		return false;

}