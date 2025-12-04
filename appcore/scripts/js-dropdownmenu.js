/* When the user clicks on the button, 
toggle between hiding and showing the dropdown menu */
function menuDrop() {
  document.getElementById("dropdownMenu").classList.toggle("show");
}

function userMenuDrop() {
  document.getElementById("userdropdownMenu").classList.toggle("show");
}

function processDrop(id) {
  var displayval = document.getElementById(id).style.display;
  
  if(displayval == "block")
	  document.getElementById(id).style.display = "none";
  else
	  document.getElementById(id).style.display = "block";
}

