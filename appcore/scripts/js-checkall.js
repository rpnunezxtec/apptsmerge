function checkAll(source, sectionID)
{
	var checkboxes = document.querySelectorAll('input[name^="'+sectionID+'"]');
	
	for(var i = 0, n = checkboxes.length; i < n; i++) 
	{
		checkboxes[i].checked = source.checked;
    }
}

function rotate(source)
{
	var transform = source.lastElementChild.style.transform;
	
	if(transform == "rotate(180deg)")
		source.lastElementChild.style.transform = "";
	else
		source.lastElementChild.style.transform = "rotate(180deg)";
	
	var disp = source.nextElementSibling.style.display;
	
	if(disp == "block")
		source.nextElementSibling.style.display = "";
	else
		source.nextElementSibling.style.display = "block";
	
	var prntClass = source.parentElement.className;
	
	if(prntClass == "active")
		source.parentElement.className = "";
	else
		source.parentElement.className = "active";
}

function expandUL(source)
{	

	var collapsibleDiv = document.getElementsByClassName("collapsible-body")[0];
	
	var disp = collapsibleDiv.style.display;
	
	if(disp == "block")
		collapsibleDiv.style.display = "";
	else
		collapsibleDiv.style.display = "block";
	
	var prntClass = source.parentElement.className;
	
	if(prntClass == "active")
		source.parentElement.className = "";
	else
		source.parentElement.className = "active";
}

function rotateIcon(source)
{
	var transform = source.style.transform;
	
	if(transform == "rotate(180deg)")
		source.style.transform = "";
	else
		source.style.transform = "rotate(180deg)";
	
	var disp = source.parentElement.nextElementSibling.style.display;
	
	if(disp == "block")
		source.parentElement.nextElementSibling.style.display = "";
	else
		source.parentElement.nextElementSibling.style.display = "block";
	
	var prntClass = source.parentElement.className;
	
	if(prntClass == "active")
		source.parentElement.parentElement.className = "";
	else
		source.parentElement.parentElement.className = "active";
}
