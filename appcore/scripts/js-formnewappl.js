function showForm(id)
{
	var citizenship = document.getElementById("citizenship");
	var yesno = citizenship.value;
	var yes = 'yes';
	var no = 'no';
	
	if(yesno.toUpperCase() === yes.toUpperCase() || yesno.toUpperCase() === no.toUpperCase())
	{
		document.getElementById("citizenshipfields").style.display = '';
		document.getElementById("ssn").style.display = '';
		
		if(yesno.toUpperCase() === yes.toUpperCase())
		{
			document.getElementById("citizenshipbannertext").innerHTML = 'US Citizen';
			document.getElementById("citizenshipbannertext").style.display = '';
			document.getElementById("ssnlabel").innerHTML = 'SSN *';
			document.getElementById("fsn").style.display = 'none';
			if(document.getElementById("origin") != null)
				document.getElementById("origin").style.display = 'none';
			if(document.getElementById("foreigndoc") != null)
				document.getElementById("foreigndoc").style.display = 'none';
			if(document.getElementById("docnorow") != null)
				document.getElementById("docnorow").style.display = 'none';
			if(document.getElementById("docno") != null)
				document.getElementById("docno").style.display = 'none';
		}
		else if(yesno.toUpperCase() === no.toUpperCase())
		{
			document.getElementById("citizenshipbannertext").innerHTML = 'Non US Citizen';
			document.getElementById("citizenshipbannertext").style.display = '';
			document.getElementById("ssnlabel").innerHTML = 'SSN';
			document.getElementById("fsn").style.display = '';
			if(document.getElementById("origin") != null)
				document.getElementById("origin").style.display = '';
			if(document.getElementById("foreigndoc") != null)
				document.getElementById("foreigndoc").style.display = '';
			if(document.getElementById("docnorow") != null)
				document.getElementById("docnorow").style.display = '';
			if(document.getElementById("docno") != null)
				document.getElementById("docno").style.display = '';
		}
	}
	else
	{
		document.getElementById("citizenshipbannertext").style.display = 'none';
		document.getElementById("citizenshipfields").style.display = 'none';
		document.getElementById("ssn").style.display = 'none';
		document.getElementById("fsn").style.display = 'none';
		if(document.getElementById("origin") != null)
			document.getElementById("origin").style.display = 'none';
		if(document.getElementById("foreigndoc") != null)
			document.getElementById("foreigndoc").style.display = 'none';
		if(document.getElementById("docnorow") != null)
			document.getElementById("docnorow").style.display = 'none';
		if(document.getElementById("docno") != null)
			document.getElementById("docno").style.display = 'none';
	}
}