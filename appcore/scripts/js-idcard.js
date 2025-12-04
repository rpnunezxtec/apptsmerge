
function chkexpdate()
{
	//calculate maximum date and compare

	var current = new Date();
	var thisyear = current.getFullYear();
	var year = thisyear + 5;
	//var day = current.getDate();
	var day = 1;
	var month = current.getMonth() + 1;

	if(month > 12)
	{
		month = month % 11;
		year = year + 1;
	}

	if(document.mainform.yy_crdexpdate.value > year || document.mainform.yy_crdexpdate.value < thisyear)
	{
    	alert("Card Expiration cannot be greater than 5 years from today.");
		dirtyset();
		return false;
	}
	else if(document.mainform.yy_crdexpdate.value == year)
	{
		if(document.mainform.mm_crdexpdate.value > month)
		{
			alert("Card Expiration cannot be greater than 3 years from today.");
			dirtyset();
			return false;
		}
	}
	return true;
}

function calccrdexpdate()
{
	var current = new Date();
	var year = current.getFullYear() + 5;
	//var day = current.getDate();
	var day = 1;
	var month = current.getMonth() + 1;

	if(month > 12)
	{
		month = month % 11;
		year = year + 1;
	}

	// fill in values if blank

	if(document.mainform.dd_crdexpdate.value == "")
	{
		document.mainform.dd_crdexpdate.value = day.toString(10);
		dirtyset(document.mainform.dd_crdexpdate);
	}
	if(document.mainform.mm_crdexpdate.value == "")
	{
		document.mainform.mm_crdexpdate.value = month.toString(10);
		dirtyset(document.mainform.mm_crdexpdate);
	}
	if(document.mainform.yy_crdexpdate.value == "")
	{
		document.mainform.yy_crdexpdate.value = year.toString(10);
		dirtyset(document.mainform.yy_crdexpdate);
	}

}

// calculate issue date
function calcIssDate()
{
	var current = new Date();
	var year = current.getFullYear();
	var day = current.getDate();
	var month = current.getMonth() + 1;

	document.mainform.dd_credissdate.value = day.toString(10);
	dirtyset(document.mainform.dd_credissdate);
	document.mainform.mm_credissdate.value = month.toString(10);
	dirtyset(document.mainform.mm_credissdate);
	document.mainform.yy_credissdate.value = year.toString(10);
	dirtyset(document.mainform.yy_credissdate);
}

function fillinIssDate()
{
	if(document.mainform.dd_credissdate.value == ""
		|| document.mainform.mm_credissdate.value == ""
		|| document.mainform.yy_credissdate.value == "")
	{
		calcIssDate();
	}
}

// increment issuance counter
function incIssCntr()
{

	document.mainform.isscntr.value++;
	if(document.mainform.isscntr.value % 10 == 0)
		document.mainform.isscntr.value++;
//	document.mainform.isscntr.value = document.mainform.isscntrx.value;
	dirtyset(document.mainform.isscntr);
	return true;
}

// updateCred button clicked
function updateCred()
{
  	var readyaction = document.mainform.readyaction.value;

   	if(document.mainform.readyaction.value == "Export")
   	{
   	}
 	else if(document.mainform.readyaction.value == "Print")
  	{
  		readyaction = "PrintEncode";
  		dirtyset(document.mainform.readyaction);
  	}
  	else if(document.mainform.readyaction.value !=  "PrintEncode")
  	{
  		readyaction = "Encode";
  		dirtyset(document.mainform.readyaction);
  	}
  	fillinIssDate();
  	document.mainform.readyaction.value = readyaction;
  	document.mainform.readyactionx.value = readyaction;
	document.mainform.issuedby.value = document.mainform.admincid.value;
	document.mainform.localapp.value = "vec-dbcredupdate.php";
	dirtyset(document.mainform.credupdatebtn);
	return true;
}

// createCred button clicked
function createCred()
{
	// set status and action just for show, actual values are set in xml.

	document.mainform.credstatus.value = "Active";
	dirtyset(document.mainform.credstatus);
	document.mainform.readyaction.value = "Export";
	dirtyset(document.mainform.readyaction);

	document.mainform.issuedby.value = document.mainform.admincid.value;
	document.mainform.localapp.value = "vec-dbcredcreate.php";
	dirtyset(document.mainform.credcreatebtn);

	calccrdexpdate();
	calcIssDate()
	genpin();

	return true;
}

// changeStatus button clicked
function changeStatus()
{
	var matchvlaue = /^Disabled/;

	var statusvalue = document.mainform.changestatus.value;
	var matchresult = statusvalue.match(matchvlaue);


	if(document.mainform.changestatus.value == "Reprint")
	{
		if(document.mainform.readyactionx.value == "Encode")
		{
			document.mainform.readyactionx.value = "PrintEncode";
			document.mainform.readyaction.value = "PrintEncode";
			dirtyset(document.mainform.readyaction);
			incIssCntr();
		}
		else if(document.mainform.readyactionx.value != "Print" && document.mainform.readyactionx.value != "PrintEncode")
		{
			document.mainform.readyactionx.value = "Print";
			document.mainform.readyaction.value = "Print";
			dirtyset(document.mainform.readyaction);
			incIssCntr();
		}
	}
	else if(document.mainform.changestatus.value == "Export")
	{
		if(document.mainform.readyactionx.value != "Export")
		{
			document.mainform.readyactionx.value = "Export";
			document.mainform.readyaction.value = "Export";
			dirtyset(document.mainform.readyaction);
			incIssCntr();
		}
	}
	else if(matchresult != null)
	{
		document.mainform.credstatusx.value = statusvalue;
		document.mainform.credstatus.value = statusvalue;
		dirtyset(document.mainform.credstatus);
		document.mainform.readyactionx.value = "None";
		document.mainform.readyaction.value = "None";
		dirtyset(document.mainform.readyaction);
	}
	else if(document.mainform.changestatus.value != "")
	{
		document.mainform.credstatusx.value = document.mainform.changestatus.value;
		document.mainform.credstatus.value = document.mainform.changestatus.value;
		dirtyset(document.mainform.credstatus);
	}
	dirtyset(document.mainform.credstatusbtn);
	return true;
}

// generate random pin value
function genpin()
{
	var current = new Date();
	var pinbase = current.getMinutes();
	pinbase += current.getMilliseconds();

	var newpin = pinbase / Math.random();
	newpin = Math.round(newpin);
	document.mainform.pin.value = newpin;
	dirtyset(document.mainform.pin);

	var defaultpin = document.mainform.defaultphysaccpin.value;
	if(defaultpin != "")
	{
		newpin = defaultpin;
	}
	else
	{
		newpin = pinbase / Math.random();
		newpin = Math.round(newpin);
	}
	document.mainform.accesspin.value = newpin;
	dirtyset(document.mainform.accesspin);
}

// check form entries
function checkForm()
{
	calccrdexpdate();
	if(!validDate("crdexpdate"))  return false;
	if(!chkexpdate())  return false;
	if(!checkRequired())  return false;
	//if(!validDate("issuedate"))  return false;
	//if(!validDate("changedate")) return false;
	return true;
}

