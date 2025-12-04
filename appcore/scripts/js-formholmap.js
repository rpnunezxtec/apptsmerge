// Takes the current;y selected map and highlights the dates on the calendar
// that are set as holidays in the map. Uses styles to do this.
function mapholdata()
{
	var mi=document.forms["mapform"].elements["holidaymap"].selectedIndex;
	var x=document.forms["mapform"].elements["holidaymap"].options[mi].value;
	// Copy the name of the selected map into the text box for saving.
	document.forms["mapform"].elements["hmapname"].value=document.forms["mapform"].elements["holidaymap"].options[mi].text;
	if (x=='')
		return;
	else
	{
		var hml=x.length;
		if (hml!=96)
			return;
		else
		{
			var bitnum=0;
			for (var i=0;i<hml;i+=2)
			{
				var n="0x"+x.substr(i,2);
				for (var j=0;j<8;j++)
				{
					var id="calbit"+bitnum;
					var cell=document.getElementById(id);
					if (cell)
					{
						if (n&(1<<j))
						{
							if (cell.className=="calweekend")
								cell.className="calweekendselect";
							else if (cell.className=="calweekday")
								cell.className="calweekdayselect";
						}
						else
						{
							if (cell.className=="calweekendselect")
								cell.className="calweekend";
							else if (cell.className=="calweekdayselect")
								cell.className="calweekday";
						}
					}
					bitnum++;
				}
			}
		}
	}
}

// sets the bit given by bitnum to value (1 or 0) in the 48-byte (384-bit) 96 digit hex string given by x
// returns the new string
function setbit(x, bitnum, value)
{
	// find the byte offset (0 to 47 bytes)
	var bytecount=Math.floor(bitnum/8);
	// and the bit offset (0 to 7)
	var bitcount=bitnum%8;
	
	// get three strings: the preceeding section, the two characters that make up the byte and the remaining part
	if (bytecount>0)
		var as=x.substr(0,2*bytecount);
	else
		var as='';
	var bs=x.substr(2*bytecount,2);
	if (bytecount<47)
		var cs=x.substr(2*bytecount+2);
	else
		var cs='';
	
	// Now set or reset the bit in the byte of interest
	var bn="0x"+bs;
	if (value==0)
		bn&=(~(1<<bitcount));
	else
		bn|=(1<<bitcount);
	
	// now re-assemble the string
	bs=bn.toString(16);
	if (bs.length==1)
		bs='0'+bs;
	var xnew=as+bs+cs;
	
	return xnew;
}

// When a user clicks on a calendar cell, this function sets or removes the selected highlighting style.
// Also updates the value of the holiday map string selected in the dropdown.
function cellprocess(id)
{
	var cell=document.getElementById(id);
	if (cell)
	{
		// get the dropdown value from the form
		var mi=document.forms["mapform"].elements["holidaymap"].selectedIndex;
		var x=document.forms["mapform"].elements["holidaymap"].options[mi].value
		if (x=='')
			x="000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000";
			
		if (cell.className=="calweekend")
		{
			cell.className="calweekendselect";
			x=setbit(x, id.substr(6), 1);
		}
		else if (cell.className=="calweekendselect")
		{
			cell.className="calweekend";
			x=setbit(x, id.substr(6), 0);
		}
		else if (cell.className=="calweekday")
		{
			cell.className="calweekdayselect";
			x=setbit(x, id.substr(6), 1);
		}
		else if (cell.className=="calweekdayselect")
		{
			cell.className="calweekday";
			x=setbit(x, id.substr(6), 0);
		}
		
		// set the value on the form to the new one
		document.forms["mapform"].elements["holidaymap"].options[mi].value=x;
	}
}

