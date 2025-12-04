function showModal(count, locName, devname)
{
	// Get the modal
	var modal = document.getElementById("myModal");
	
	var cx = window.innerWidth / 2.5;
	var cy = window.innerHeight / 2.5;

	// Get the <span> element that closes the modal
	document.getElementById("active_count").innerHTML = count;
	document.getElementById("table_loc").innerHTML = locName;
	document.getElementById("dev_name").innerHTML = devname;
	
	// add device id to input
	document.getElementById("devNameInput").value = devname;

	// When the user clicks on the button, open the modal
	modal.style.left = cx + "px";
	modal.style.top = cy + "px";
	modal.style.display = "block";

	// When the user clicks anywhere outside of the modal, close it
	window.onclick = function(event) 
	{
	  if (event.target == modal) {
		modal.style.display = "none";
	  }
	}
}

function closeModal()
{
	// Get the modal
	var modal = document.getElementById("myModal");
	modal.style.display = "none";
}