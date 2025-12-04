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