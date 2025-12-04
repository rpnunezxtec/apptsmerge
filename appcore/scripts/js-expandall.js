function expandAll()
{
	var btnName = document.getElementById("expandcollapse").value;
	
	var rowChildren = document.getElementsByClassName("row")[0].childNodes;
	
	for(var i = 0; i < rowChildren.length; i++)
	{
		var tag = rowChildren[i].tagName;
		
		if(tag == "UL")
		{
			if(btnName == "Expand All")
			{
				rowChildren[i].firstElementChild.firstElementChild.nextElementSibling.style.display = "block";
				rowChildren[i].firstElementChild.className = "active";
			}
			else
			{
				rowChildren[i].firstElementChild.firstElementChild.nextElementSibling.style.display = "none";
				rowChildren[i].firstElementChild.className = "";
			}
			
			var transform = rowChildren[i].firstElementChild.firstElementChild.firstElementChild.nextElementSibling.style.transform;
			
			if(transform == "rotate(180deg)")
				rowChildren[i].firstElementChild.firstElementChild.firstElementChild.nextElementSibling.style.transform = "";
			else
				rowChildren[i].firstElementChild.firstElementChild.firstElementChild.nextElementSibling.style.transform = "rotate(180deg)";
		}
		else
		{
			if(rowChildren[i].firstElementChild != null)
			{
				if(rowChildren[i].firstElementChild.nextElementSibling != null)
				{
					var tag = rowChildren[i].firstElementChild.nextElementSibling.tagName;
					
					if(tag == "UL")
					{
						if(btnName == "Expand All")
						{
							rowChildren[i].firstElementChild.nextElementSibling.firstElementChild.firstElementChild.nextElementSibling.style.display = "block";
							rowChildren[i].firstElementChild.nextElementSibling.firstElementChild.className = "active";
						}
						else
						{
							rowChildren[i].firstElementChild.nextElementSibling.firstElementChild.firstElementChild.nextElementSibling.style.display = "none";
							rowChildren[i].firstElementChild.nextElementSibling.firstElementChild.className = "";
						}
						
						var transform = rowChildren[i].firstElementChild.nextElementSibling.firstElementChild.firstElementChild.firstElementChild.nextElementSibling.style.transform;
						
						if(transform == "rotate(180deg)")
							rowChildren[i].firstElementChild.nextElementSibling.firstElementChild.firstElementChild.firstElementChild.nextElementSibling.style.transform = "";
						else
							rowChildren[i].firstElementChild.nextElementSibling.firstElementChild.firstElementChild.firstElementChild.nextElementSibling.style.transform = "rotate(180deg)";
					}
				}
			}
		}
	}
	
	if(btnName == "Expand All")
		document.getElementById("expandcollapse").value = "Collapse All";
	else
		document.getElementById("expandcollapse").value = "Expand All";
}

function expandAll2()
{
	var btnName = document.getElementById("expandcollapse").value;
	
	var rowChildren = document.getElementsByClassName("row")[0].childNodes;
	
	for(var i = 0; i < rowChildren.length; i++)
	{
		var tag = rowChildren[i].tagName;
		
		if(tag == "FORM")
		{
			if(rowChildren[i].firstElementChild.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling != null)
			{
				if(btnName == "Expand All")
				{
					rowChildren[i].firstElementChild.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.nextElementSibling.style.display = "block";
					rowChildren[i].firstElementChild.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.className = "active";
				}
				else
				{
					rowChildren[i].firstElementChild.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.nextElementSibling.style.display = "none";
					rowChildren[i].firstElementChild.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.className = "";
				}
				
				var transform = rowChildren[i].firstElementChild.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.lastElementChild.style.transform;
		
				if(transform == "rotate(180deg)")
					rowChildren[i].firstElementChild.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.lastElementChild.style.transform = "";
				else
					rowChildren[i].firstElementChild.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.lastElementChild.style.transform = "rotate(180deg)";
			}
		}
	}
	
	if(btnName == "Expand All")
		document.getElementById("expandcollapse").value = "Collapse All";
	else
		document.getElementById("expandcollapse").value = "Expand All";
}

function expandAllList()
{
	var btnName = document.getElementById("expandcollapse").value;
	
	if(btnName == "Expand All")
		document.getElementById("expandcollapse").value = "Collapse All";
	else
		document.getElementById("expandcollapse").value = "Expand All";
	
	// get ul element
	var collapsibleElements = document.getElementsByName("iallist");
	
	expandAllElements(collapsibleElements, btnName);
	
	// get wflow ul element
	var collapsibleElements = document.getElementsByName("wflowlist");
	
	expandAllElements(collapsibleElements, btnName);
	
}

function expandAllElements(expandElements, btnName)
{
	
	for(var i = 0; i < expandElements.length; i++)
	{
		// get ul element
		var list = expandElements[i].firstElementChild;
		
		// get header
		var header = list.firstElementChild;
		
		// get rotate img
		var rotateImage = header.getElementsByTagName("img")[0];
		
		if(rotateImage.style.transform == "rotate(180deg)")
			rotateImage.style.transform = "";
		else
			rotateImage.style.transform = "rotate(180deg)";
		
		// get body element
		var collapsibleBody = header.nextElementSibling;
		
		if(btnName == "Expand All")
		{
			collapsibleBody.style.display = "block";
		}
		else
			collapsibleBody.style.display = "";
	}
}

function showLogs()
{
	var btnName = document.getElementById("showlogs").value;
	
	if(btnName == "Show Logs")
	{
		document.getElementById("logs").style.display = "";
		document.getElementById("showlogs").value = "Hide Logs";
	}
	else
	{
		document.getElementById("logs").style.display = "none";
		document.getElementById("showlogs").value = "Show Logs";
	}
}

function addProc(source,cat)
{
	var div = document.createElement("div");
	div.className = "inputtitle1";
	
	var innerdiv = document.createElement("div");
	innerdiv.style.display = "inline";
	
	var chckbox = document.createElement("input");
	chckbox.setAttribute("type", "checkbox");
	chckbox.setAttribute("name", "");
	chckbox.innerHTML = "";
	
	var inputline = document.createElement("input");
	inputline.setAttribute("type", "text");
	inputline.innerHTML = "";
	inputline.setAttribute("name", "");
	inputline.className = "thinline";
	inputline.setAttribute("onchange", "setValues(this,'" + cat + "')");
	
	innerdiv.appendChild(chckbox);
	innerdiv.appendChild(inputline);
	
	source.parentElement.parentElement.parentElement.insertAdjacentElement('beforebegin', div);
	
	source.parentElement.parentElement.parentElement.previousElementSibling.appendChild(innerdiv);
}

function setValues(source, cat)
{
	var val = source.value;
	
	source.previousElementSibling.name = val;
	source.previousElementSibling.value = "add_" + cat + "_" + val;
}
