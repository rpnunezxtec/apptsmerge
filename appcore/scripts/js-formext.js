function deleteCheck()
{
  if (delbit)
  {
    var x = confirm("Warning: Delete action will destroy data. Continue?");
    if (x)
      return true;
    else
      return false;
  }
  else
    return true;
}
var delbit = 0;
function delSet()
{
  delbit = 1;
}
function delClear()
{
  delbit = 0;
}

function delEnable(doc)
{
	doc.form.btn_delete.disabled = false; 
	doc.form.btn_delete.style.opacity = '';
	doc.form.btn_delete.classList.remove("disabled");
}