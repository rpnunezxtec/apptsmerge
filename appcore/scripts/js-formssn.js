function validSSN(name)
{
  var x = document.forms["mainform"].elements;

  var ssn = x["ssn"].value;
  var isnum = /^\d+$/.test(ssn);

  if (ssn.toString().length != 0)
  {
	if ((ssn.toString().length != 9) || (!isnum))
	{
		alert(name+": SSN must be 9 digits");
		return false;
	}
  }

  return true;
}
