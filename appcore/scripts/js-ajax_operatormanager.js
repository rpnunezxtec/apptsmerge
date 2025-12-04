// $Id:$
// Ajax functions for srip form updates

// Usage:
// 1. Set the service URL in 'xhrcaptureservice' global variable.
// 2. Set the refreshInterval global to the interval in seconds.
// 3. Call the startRefresh() function to start the timer.

// Variables (global):
// xh : XHR object.
// xhrcaptureservice : URL to service file (eg xsvc-sripcapturestatus.xas).
// refreshoperatorstatusID : Refresh timer object.
// refreshInterval : Refresh interval in seconds.
// qid : Unique identifier in operator for session
// stage : The current stage of the capture process
// Create a new XHR object when loaded

var xhoperatorstatus = false;
if (!xhoperatorstatus && typeof XMLHttpRequest != 'undefined')
{
	xhoperatorstatus = new XMLHttpRequest();
	if (xhoperatorstatus.overrideMimeType)
 		xhoperatorstatus.overrideMimeType('text/xml');

 	xhoperatorstatus.onreadystatechange = xhoperatorstatusr_process;
}

var cnt = 0;
// Callback function to process the resulting XML from the service
function xhoperatorstatusr_process()
{
	if (xhoperatorstatus.readyState == 4)
	{
		if (xhoperatorstatus.status == 200)
		{
			// Stop the refresh trigger during this processing
			stopOperatorStatusRefresh();
			
			// Do something with the data result set
			var result = JSON.parse(xhoperatorstatus.responseText);
			
			// get error if any
			var err = result.error;
			
			var searchTable = document.getElementById("myInput");
			
			
			if(searchTable.value == "")
			{
				if(err != true)
				{
					// rfeset table first
					//resetTable();
					// get table elementFromPoint
					var table = document.getElementById('myTable');
					
					// get expanded elements first
					var expDevIds = getExpandedOperatorElements(table);
					
					//reset table and add header
					table.innerHTML = "<thead><tr style=\"padding-top:20px\"><th class=\"light-xtec-blue\" style=\"width:25%\">Position</th><th class=\"light-xtec-blue\" style=\"width 25%\">Operator ID</th></tr><thead>";
					
					// get operator data
					var operatorData = result.operatordata;
					var cnt = result.cnt;
					
					// remove loader first
					if(cnt > 0)
						document.getElementById('loader-div').style.display = 'none';
						
					for(var i = 1; i < cnt; i++)
					{
						var operatorID = operatorData[i].operatorid;
						var qtimestamp = operatorData[i].qtimestamp;
						var opstat = operatorData[i].opstat;
						var permissions = operatorData[i].permissions;
						
						// create row
						var newtr = document.createElement('tr');
						newtr.setAttribute('id','list_name_' + i);
						if(i%2 != 0)
							newtr.setAttribute("style", "background-color: rgba(230, 230, 230, 0.5);");
						else
							newtr.setAttribute("style", "background-color: rgba(245, 245, 245, 0.5);");
						
						// create td position row and set attributes
						var newtd = document.createElement('td');
						newtd.setAttribute('id','list_name');
						newtd.setAttribute('style','border-bottom: 0px;');
						newtd.innerHTML = i;
						newtr.appendChild(newtd);
						
						// create button 
						var btn = document.createElement("button");
						btn.className = "collapsiblebtn";
						btn.innerHTML = operatorID;
						btn.setAttribute("style", "font-size: 1.5em;");
						btn.addEventListener ("click", expandDiv);
						//newtButton.appendChild(newi);
						
						// create outer div
						var newdiv = document.createElement('div');
						newdiv.className = "contenttable";
						newdiv.setAttribute("style", "font-size:12px; overflow:auto;");
						
						// create inner div
						var newdiv1 = document.createElement('div');
						newdiv1.className = "gridcontainer";
						
						// create table div
						var newdiv2 = document.createElement('div');
						newdiv2.className = "griditems";
						newdiv2.setAttribute("id", "griditems");
						
						// create ul
						var newul = document.createElement('ul');
						newul.setAttribute("id", "values");
						
						// create li header
						var newlih = document.createElement('li');
						newlih.setAttribute("style", "height:30px;display:flex;");
						
						// create input header uid
						var newInputhUID = document.createElement('input');
						newInputhUID.setAttribute("type", "text");
						newInputhUID.setAttribute("style", "background-color: aliceblue; font-weight: 700; font-size:large; width: 40%");
						newInputhUID.setAttribute("value", "Operator Status");
						
						// create input header Sessionstat
						var newInputhSStat= document.createElement('input');
						newInputhSStat.setAttribute("type", "text");
						newInputhSStat.setAttribute("style", "background-color: aliceblue; font-weight: 700; font-size:large; width: 35%");
						newInputhSStat.setAttribute("value", "Operation Permissions");
						
						// create input header timestamp
						var newInputhTime= document.createElement('input');
						newInputhTime.setAttribute("type", "text");
						newInputhTime.setAttribute("style", "background-color: aliceblue; font-weight: 700; font-size:large; width: 33%");
						newInputhTime.setAttribute("value", "Entry Time");
						
						// add header inout to header li
						newlih.appendChild(newInputhUID);
						newlih.appendChild(newInputhSStat);
						newlih.appendChild(newInputhTime);
						
						// create li
						var newli = document.createElement('li');
						newli.setAttribute("style", "display:flex;");
						
						// create input session status
						var newInputUID = document.createElement('input');
						newInputUID.setAttribute("type", "text");
						newInputUID.setAttribute("style", "width:40%; height:30px");
						newInputUID.setAttribute("value", opstat);
						
						// create input user 
						var newInputStatus = document.createElement('input');
						newInputStatus.setAttribute("type", "text");
						newInputStatus.setAttribute("style", "width:35%; height:30px");
						newInputStatus.setAttribute("value", permissions);
						
						// create input time
						var newInputhTime = document.createElement('input');
						newInputhTime.setAttribute("type", "text");
						newInputhTime.setAttribute("style", "width:33%; height:30px");
						newInputhTime.setAttribute("value", formatDate(qtimestamp));
						
						//add input to li
						newli.appendChild(newInputUID);
						newli.appendChild(newInputStatus);
						newli.appendChild(newInputhTime);
						
						// add li to ul
						newul.appendChild(newli);
						
						// add delete entry btns
						var btnDel = document.createElement("input");
						btnDel.type = "submit";
						btnDel.className = "inputbtn red";
						btnDel.value = "Delete Entry";
						btnDel.setAttribute ("onclick", "deleteOperatorEntry('" + operatorID + "', " + i + ");");
						
						// add div with btns
						var newdivBtns = document.createElement('div');
						newdivBtns.className = "griditems";
						newdivBtns.setAttribute("style", "padding-top:30px;padding-left:10px;padding-bottom:20px");
						newdivBtns.appendChild(btnDel);
						
						// add ul and header to griditems div 
						newdiv2.appendChild(newlih);
						newdiv2.appendChild(newul);
						
						newdiv1.appendChild(newdiv2);
						newdiv.appendChild(newdiv1);
						newdiv.appendChild(newdivBtns);
						
						// create td id row and set attributes
						var newtd = document.createElement('td');
						newtd.appendChild(btn);
						newtd.appendChild(newdiv);
						
						newtr.appendChild(newtd);
						
						// add elements to table
						table.appendChild(newtr);
						
						if(expDevIds.includes(operatorID))
							expandDivBtn(btn);
					}
				}
				else
				{
					// get error message
					var errormsg = result.errormsg;
					
					alert("Error: " + errormsg)
				}
			}
			
			startOperatorStatus();
		}
	}
}

// Create the XHR call to the remote server using POST to send the data
var xhrsripoperatorservice = "";

function xhroperatorstatus_call()
{
	xhoperatorstatus.open("GET", xhrsripoperatorservice + "?sie=" + Math.random(), true);
	xhoperatorstatus.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

	xhoperatorstatus.send(null);
}

// The refresh function that triggers the XHR calls to the server and updates
// the contents of the form.
function xhroperatorstatus_refresh()
{
	xhroperatorstatus_call();
}

// Setup the interval timer (in seconds). Should be called on form load.
var refreshoperatorstatusID = 0;
var refreshcaptureinterval = 2;
function startOperatorStatus()
{
	refreshoperatorstatusID = window.setTimeout(xhroperatorstatus_refresh, refreshcaptureinterval * 1000);
}

function stopOperatorStatusRefresh()
{
	window.clearInterval(refreshoperatorstatusID);
}

function expandDiv()
{	
	this.classList.toggle("active");

    var contenttable = this.nextElementSibling;
    if (contenttable.style.maxHeight){
      contenttable.style.maxHeight = null;
    } else {
      contenttable.style.maxHeight = content.scrollHeight + "px";
    } 
}

function expandDivBtn(btn)
{	
	//btn.classList.toggle("active");

    var content = btn.nextElementSibling;
    if (content.style.maxHeight)
	{
      content.style.maxHeight = null;
    } else 
	{
      content.style.maxHeight = "707px";
    } 
}

function formatDate (unixTimestamp)
{
	var date = new Date(unixTimestamp*1000);
	var day = date.getDate();
	var month = date.getMonth();
	var year = date.getFullYear();
	var fullDate = (month + 1) + "-" + day + "-" + year;
	
	return fullDate;
}

function getExpandedOperatorElements(table)
{
	var colButtonElements = table.getElementsByClassName("collapsiblebtn");
	var expElements = [];
	
	for(var i = 0; i < colButtonElements.length; i++)
	{
		
		var contenttable = colButtonElements[i].nextElementSibling;
		
		if (contenttable.style.maxHeight)
		{
		  var operatorID = colButtonElements[i].innerHTML;
		  expElements.push(operatorID);
		}
	}
	
	return expElements;
}

function filterTable() 
{
  var input, filter, table, tr, td, i, txtValue;
  input = document.getElementById("myInput");
  filter = input.value.toUpperCase();
  table = document.getElementById("myTable");
  tr = table.getElementsByTagName("tr");
 
  for (i = 1; i < tr.length; i++) {
    td = tr[i].getElementsByTagName("td")[1];
    if (td) {
      txtValue = td.textContent || td.innerText;
      if (txtValue.toUpperCase().indexOf(filter) > -1) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    }
  }
}