// js-logo_resize.js
// Resize the logo image to fit better in the header
// Assumes the logo image has a src attribute containing "banner_logo.png"
document.addEventListener("DOMContentLoaded", function () {
  const logo = document.querySelector('img[src*="banner_logo.png"]');
  if (logo) {
    // resize the logo
    logo.style.maxWidth = "150px";
    logo.style.height = "auto";
    logo.style.display = "block";
    logo.style.margin = "5px auto"; // center horizontally
  }
});
