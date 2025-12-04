function populateLoginForm() {
  // get last user id if any
  let userID = localStorage.getItem("lastLoggedInUserID");

  if (userID) {
    // set correct height
    document.getElementById("dontHaveAccount").style.marginTop = "8%";

    // hide userid input
    document.getElementById("loginFormInputs").style.display = "none";

    // show last logged in user
    document.getElementById("lastLoggedUserForm").style.display = "block";

    // show and set lastlogged in user
    document.getElementById("last_logged_userid").innerHTML = userID;
    document.getElementById("userid").value = userID;
  } else {
    // set correct height
    document.getElementById("dontHaveAccount").style.marginTop = "2%";

    document.getElementById("loginFormInputs").style.display = "block";
    document.getElementById("lastLoggedUserForm").style.display = "none";
  }
}

function useDifferentUserID() {
  // set correct height
  document.getElementById("dontHaveAccount").style.marginTop = "8%";

  // get last user id if any
  let userID = localStorage.getItem("lastLoggedInUserID");

  if (userID) {
    // reset the lastLoggedInUserID local variable
    localStorage.removeItem("lastLoggedInUserID");
  }

  // reload page
  window.location.reload();
}
