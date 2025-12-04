function windowOpener(thisURL)
{
	var sw = screen.availWidth;
	var sh = screen.availHeight-20;
	if (sw > 900)
		sw = 900;
	NewWindow = window.open(thisURL,"authentx","toolbar=no,width=640,height=480,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes");
	NewWindow.focus();
	NewWindow.resizeTo(sw, sh);
	NewWindow.moveTo(100,10);
}
function popupOpener(thisURL,windowname,wsize,hsize)
{
  newPopup = window.open(thisURL,windowname,"toolbar=no,width="+wsize+",height="+hsize+",location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes");
  newPopup.focus();
}
var dirtybit = 0;
function dirtyset()
{
  dirtybit = 1;
}
function dirtyclear()
{
  dirtybit = 0;
}
function frmCheck()
{
  var ok = 1;
  if (ok)
    return true;
  else
    return false;
}
function frmCheckDirty()
{
  if (dirtybit)
  {
    alert("Please Save or Cancel changes");
    return false;
  }
  else
    return true;
}

var numElements=0;
function frmBuildMulti(formName,targetName)
{
	var target = document.forms[formName].elements;

	if (numElements == 0)
		eval('target.'+targetName+'.value = ""');
	else
	{
		var v = new Array(numElements);
		var i = 0;
		var j = 0;
		var x;
		var k;
		var elementName;
		var b = '';

		while (i<numElements)
		{
			elementName = targetName+i;
			x = document.forms[formName].elements;
			k = eval('x.'+elementName+'.value');
			if (k!='')
			{
				if (k.match(/[\|]/))
					alert("| character not permitted");
				else
				{
					if (j == 0)
						b = k;
					else
						b = b+'|'+k;
					j++;
				}
			}
			i++;
		}
		eval('target.'+targetName+'.value = b');
	}
}

function frmEmptyInputCheck (inputNames)
{
	var emptyFound = false;
	var emptyFields = "";
	
	inputNames.forEach(
		function(name, index)
		{
			var value = document.getElementsByName(name)[0].value;
			if(value.length == 0)
			{
				emptyFound = true;
				
				emptyFields += "\n" + getLabelName(name);
			}
		}	
	);
	
	if(emptyFound)
	{
		alert("The following fields cannot be empty: " + emptyFields.replace(/[^0-9a-z\s]/gi, ''));
		
		return false
	}
	
	return true;
}

function getLabelName (name)
{
	// get all labels
	var labels = document.getElementsByTagName('LABEL');
	
	for (var i = 0; i < labels.length; i++) 
	{
		// check if label belongs to input
		if (labels[i].htmlFor == name) 
		{
			return labels[i].innerHTML;         
		}
	}
}

function frmPreview(targetName, previewTitle)
{
	previewOpen = window.open("", "previewOpen","scrollbars=yes,resizable=yes");
    previewOpen.document.body.innerHTML = document.getElementById(targetName).value.replace(/\n/g, '<br>\n').replace(/ /g,'&nbsp;').replace(/%fn%/g,'John').replace(/%ln%/g,'Doe').replace(/%days%/g,'30');
	previewOpen.document.title = previewTitle;
	return false;
}
