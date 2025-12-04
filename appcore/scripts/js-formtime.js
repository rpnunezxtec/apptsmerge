function filltime(starttime, endtime)
{
	document.forms["mainform"].elements["startmon"].value = starttime;
	document.forms["mainform"].elements["endmon"].value = endtime;
	document.forms["mainform"].elements["starttue"].value = starttime;
	document.forms["mainform"].elements["endtue"].value = endtime;
	document.forms["mainform"].elements["startwed"].value = starttime;
	document.forms["mainform"].elements["endwed"].value = endtime;
	document.forms["mainform"].elements["startthu"].value = starttime;
	document.forms["mainform"].elements["endthu"].value = endtime;
	document.forms["mainform"].elements["startfri"].value = starttime;
	document.forms["mainform"].elements["endfri"].value = endtime;
	document.forms["mainform"].elements["startsat"].value = starttime;
	document.forms["mainform"].elements["endsat"].value = endtime;
	document.forms["mainform"].elements["startsun"].value = starttime;
	document.forms["mainform"].elements["endsun"].value = endtime;
	document.forms["mainform"].elements["starthol"].value = starttime;
	document.forms["mainform"].elements["endhol"].value = endtime;
}