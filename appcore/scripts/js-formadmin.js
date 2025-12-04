function searchfieldsetup()
{
	var idx=document.forms["finduform"].elements["acidprefix"].selectedIndex;
	var xv=document.forms["finduform"].elements["acidprefix"].options[idx].value;
	var xt=document.forms["finduform"].elements["acidprefix"].options[idx].text;
	
	// set the search input to default
	var searchUser = document.getElementById("searchuser");
	searchuser.maxLength = 80;
	
	if (xv=='')
		return;
	else if (xv=="*LNM")
	{
		document.getElementById("lblsrchusr").innerHTML="Lastname";
		document.getElementById("lblauxval").innerHTML="";
		document.getElementById("auxdatatype").value="";
		setVisibility(document.getElementById("auxvalue"), 0);
	}
	else if (xv=="@@@")
	{
		document.getElementById("lblsrchusr").innerHTML="Login ID";
		document.getElementById("lblauxval").innerHTML="";
		document.getElementById("auxdatatype").value="";
		setVisibility(document.getElementById("auxvalue"), 0);
	}
	else if (xv=="EML")
	{
		document.getElementById("lblsrchusr").innerHTML="Email address";
		document.getElementById("lblauxval").innerHTML="";
		document.getElementById("auxdatatype").value="";
		setVisibility(document.getElementById("auxvalue"), 0);
	}
	else if (xv=="SSN")
	{
		document.getElementById("lblsrchusr").innerHTML="SSN";
		document.getElementById("lblauxval").innerHTML="";
		document.getElementById("auxdatatype").value="";
		setVisibility(document.getElementById("auxvalue"), 0);
		searchuser.maxLength = 10;
	}
	else if (xv=="*SSN")
	{
		document.getElementById("lblsrchusr").innerHTML="SSN";
		document.getElementById("lblauxval").innerHTML="";
		document.getElementById("auxdatatype").value="";
		setVisibility(document.getElementById("auxvalue"), 0);
		searchuser.maxLength = 10;
	}
	else if (xv=="EDR")
	{
		document.getElementById("lblsrchusr").innerHTML="EDR";
		document.getElementById("lblauxval").innerHTML="";
		document.getElementById("auxdatatype").value="";
		setVisibility(document.getElementById("auxvalue"), 0);
		searchuser.maxLength = 20;
	}
	else if (xv=="SDR")
	{
		document.getElementById("lblsrchusr").innerHTML="SSNDOBREGION";
		document.getElementById("lblauxval").innerHTML="";
		document.getElementById("auxdatatype").value="";
		setVisibility(document.getElementById("auxvalue"), 0);
		searchuser.maxLength = 20;
	}
	else if (xv=="SDB")
	{
		document.getElementById("lblsrchusr").innerHTML="SSNDOB (YYYYMMDD)";
		document.getElementById("lblauxval").innerHTML="";
		document.getElementById("auxdatatype").value="";
		setVisibility(document.getElementById("auxvalue"), 0);
	}
	else if (xv=="*SDB")
	{
		document.getElementById("lblsrchusr").innerHTML="SSNDOB (YYYYMMDD)";
		document.getElementById("lblauxval").innerHTML="";
		document.getElementById("auxdatatype").value="";
		setVisibility(document.getElementById("auxvalue"), 0);
	}
	else if (xv=="*LDB")
	{
		document.getElementById("lblsrchusr").innerHTML="Lastname";
		document.getElementById("lblauxval").innerHTML="DOB (MMDDYYYY)";
		document.getElementById("auxdatatype").value="DOB";
		setVisibility(document.getElementById("auxvalue"), 1);
	}
	else if (xv=="*LSS")
	{
		document.getElementById("lblsrchusr").innerHTML="Lastname";
		document.getElementById("lblauxval").innerHTML="SSN";
		document.getElementById("auxdatatype").value="SSN";
		setVisibility(document.getElementById("auxvalue"), 1);
	}
	else if (xv=="EDI")
	{
		document.getElementById("lblsrchusr").innerHTML="EDIPI";
		document.getElementById("lblauxval").innerHTML="";
		document.getElementById("auxdatatype").value="";
		setVisibility(document.getElementById("auxvalue"), 0);
		searchuser.maxLength = 10;
	}
	else if (xv=="*EDI")
	{
		document.getElementById("lblsrchusr").innerHTML="EDIPI";
		document.getElementById("lblauxval").innerHTML="";
		document.getElementById("auxdatatype").value="";
		setVisibility(document.getElementById("auxvalue"), 0);
		searchuser.maxLength = 10;
	}
	else if (xv=="UPN")
	{
		document.getElementById("lblsrchusr").innerHTML="UPN";
		document.getElementById("lblauxval").innerHTML="";
		document.getElementById("auxdatatype").value="";
		setVisibility(document.getElementById("auxvalue"), 0);
	}
	else if (xv=="UPI")
	{
		document.getElementById("lblsrchusr").innerHTML="UPI";
		document.getElementById("lblauxval").innerHTML="";
		document.getElementById("auxdatatype").value="";
		setVisibility(document.getElementById("auxvalue"), 0);
	}
	else if (xv=="FSN")
	{
		document.getElementById("lblsrchusr").innerHTML="FSN";
		document.getElementById("lblauxval").innerHTML="";
		document.getElementById("auxdatatype").value="";
		setVisibility(document.getElementById("auxvalue"), 0);
	}
	else if (xv=="*FSN")
	{
		document.getElementById("lblsrchusr").innerHTML="FSN";
		document.getElementById("lblauxval").innerHTML="";
		document.getElementById("auxdatatype").value="";
		setVisibility(document.getElementById("auxvalue"), 0);
	}
	else if (xv=="+UDB")
	{
		document.getElementById("lblsrchusr").innerHTML="Search Text";
		document.getElementById("lblauxval").innerHTML="";
		document.getElementById("auxdatatype").value="";
		setVisibility(document.getElementById("auxvalue"), 0);
	}
	else
		return;
}


function setVisibility(me,vis)
{
	if (vis==0)
	{
		me.style.display="none";
	}
	else 
	{
		me.style.display="";
	}
}
