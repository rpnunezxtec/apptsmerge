function getElementLabel(name)
{
	var labels = document.getElementsByTagName('LABEL');
	
	for (var i = 0; i < labels.length; i++)
	{
		if(labels[i].htmlFor == name)
		{
			return labels[i].innerHTML;
		}
	}
}

function validDay(name)
{
  var x = document.forms["mainform"].elements;
  var day = parseInt(x["dd_"+name].value,10);
  if ((day < 1) || (day > 31))
    alert(name+": DD must be between 1 and 31");
  dirtyset();
  return;
}
function validMonth(name)
{
  var x = document.forms["mainform"].elements;
  var month = parseInt(x["mm_"+name].value,10);
  if ((month < 1) || (month > 12))
    alert(name+": MM must be between 1 and 12");
  dirtyset();
  return;
}
function validYear(name)
{
  var x = document.forms["mainform"].elements;
  var year = parseInt(x["yy_"+name].value,10);
  if ((year < 1901) || (year > 2099))
    alert(name+": YYYY must be between 1901 and 2099");
  dirtyset();
  return;
}
var monthLength = new Array(31,28,31,30,31,30,31,31,30,31,30,31);
function validDate(name, expDate)
{
  var x = document.forms["mainform"].elements;
  var day = parseInt(x["dd_"+name].value,10);
  var month = parseInt(x["mm_"+name].value,10);
  var year = parseInt(x["yy_"+name].value,10);
  
  //Get today's date
  var today = new Date();
  var dd = today.getDate();
  var mm = today.getMonth()+1;
  var yyyy = today.getFullYear();
  
  // get label for the element
  var labelName = getElementLabel(name);
  
  // check if the date has to be in the future or past
  if(expDate === undefined)
  {
	  // return true if date in the past
	  if (((year == yyyy) && (month == mm) && (day > dd)) || (year > yyyy) || ((year == yyyy) && (month > mm)))
	  {
		  alert(labelName+": Date cannot be in the future");
		  return false;
	  }
  }
  else
  {
	// return false if date in the past
	if (((year == yyyy) && (month == mm) && (day < dd)) || (year < yyyy) || ((year == yyyy) && (month < mm)))
	{
	  alert(labelName+": Date cannot be in the past");
	  return false;
	}
	else
	{
		// check if date is greater than alowed date
		var input_date = new Date(year, month, day);
		var exp_date = new Date(yyyy + expDate, mm, dd);
		
		const diffTime = exp_date - input_date;
		
		if(diffTime < 0)
		{
			alert("Card Expiration Date cannot be greater than " + expDate + " years.");
			return false;
		}
	}
  }
  
  if ((day < 1) || (day > 31))
  {
    alert(labelName+": DD must be between 1 and 31");
    return false;
  }
  if ((month < 1) || (month > 12))
  {
    alert(labelName+": MM must be between 1 and 12");
    return false;
  }
  if ((year < 1901) || (year > 2099))
  {
    alert(labelName+": YYYY must be between 1901 and 2099");
    return false;
  }
  if (year/4 == parseInt(year/4))
    monthLength[1] = 29;
  if (day > monthLength[month-1])
  {
    alert(labelName+": date is invalid");
    monthLength[1] = 28;
    return false;
  }
  monthLength[1] = 28;
  return true;
}