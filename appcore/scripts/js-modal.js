function showModal(img)
{
	// Get the modal
	var modal = document.getElementById("fpModal");

	// Get the image and insert it inside the modal - use its "alt" text as a caption
	var modalImg = document.getElementById("fpimg");
	var captionText = document.getElementById("caption");
	
	modal.style.display = "block";
	modalImg.src = img.src;
	captionText.innerHTML = img.alt;

	// Get the <span> element that closes the modal
	var span = document.getElementsByClassName("close")[0];
}

function closeModal()
{
	// Get the modal
	var modal = document.getElementById("fpModal");
	
	// When the user clicks on <span> (x), close the modal
	modal.style.display = "none";
}