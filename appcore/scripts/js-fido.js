const useridBtn = document.getElementById("btn_userid");
const useridInp = document.getElementById("userid");
//const registerBtn = document.getElementById('btn_register');
const messageTxt = document.getElementById("txt_message");

const displayMessage = (message) => {
  messageTxt.value = message;
};

/**
 * convert RFC 1342-like base64 strings to array buffer
 * @param {mixed} obj
 * @returns {undefined}
 */
function recursiveBase64StrToArrayBuffer(obj) {
  let prefix = "=?BINARY?B?";
  let suffix = "?=";
  if (typeof obj === "object") {
    for (let key in obj) {
      if (typeof obj[key] === "string") {
        let str = obj[key];
        if (
          str.substring(0, prefix.length) === prefix &&
          str.substring(str.length - suffix.length) === suffix
        ) {
          str = str.substring(prefix.length, str.length - suffix.length);

          let binary_string = window.atob(str);
          let len = binary_string.length;
          let bytes = new Uint8Array(len);
          for (let i = 0; i < len; i++) {
            bytes[i] = binary_string.charCodeAt(i);
          }
          obj[key] = bytes.buffer;
        }
      } else {
        recursiveBase64StrToArrayBuffer(obj[key]);
      }
    }
  }
}

/**
 * look for user id data
 * @param {mixed} obj
 * @returns user id value
 */
function getUserID(obj) {
  if (typeof obj === "object") {
    for (let key in obj) {
      if (key === "user") {
        let userObj = obj[key];
        userUIDValue = userObj["id"];

        return userUIDValue;
      } else {
        let userUid = getUserID(obj[key]);

        if (typeof userUid == "string") return userUid;
      }
    }
  }
}

/**
 * Convert a ArrayBuffer to Base64
 * @param {ArrayBuffer} buffer
 * @returns {String}
 */
function arrayBufferToBase64(buffer) {
  let binary = "";
  let bytes = new Uint8Array(buffer);
  let len = bytes.byteLength;
  for (let i = 0; i < len; i++) {
    binary += String.fromCharCode(bytes[i]);
  }
  return window.btoa(binary);
}

userregister = async () => {
  try {
    if (PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable()) {
      if (useridInp.value == "") {
        alert("Invalid UserID.");
        return false;
      }

      const challenge = await fetch("wapi/fidoauth/fido.json/requestregister", {
        method: "POST",
        headers: {
          "content-type": "application/json",
        },
        body: JSON.stringify({ userid: useridInp.value }),
      });

      if (challenge.status === 200) {
        const createArgs = await challenge.json();

        // error handling
        if (createArgs.success === false) {
          throw new Error(createArgs.msg || "unknown error occured");
        }

        // get userUUID berefo converting
        const userUUIDValue = getUserID(createArgs);

        // replace binary base64 data with ArrayBuffer.
        recursiveBase64StrToArrayBuffer(createArgs);

        const cred = await navigator.credentials.create(createArgs);

        // create object
        const authenticatorAttestationResponse = {
          transports: cred.response.getTransports
            ? cred.response.getTransports()
            : null,
          clientDataJSON: cred.response.clientDataJSON
            ? arrayBufferToBase64(cred.response.clientDataJSON)
            : null,
          attestationObject: cred.response.attestationObject
            ? arrayBufferToBase64(cred.response.attestationObject)
            : null,
          userUUID: userUUIDValue,
          rpID: location.hostname,
        };

        const loggedIn = await fetch("wapi/fidoauth/fido.json/register", {
          method: "POST",
          headers: {
            "content-type": "application/json",
          },
          body: JSON.stringify(authenticatorAttestationResponse),
        });

        if (loggedIn.status === 200) {
          const response = await loggedIn.json();

          // error handling
          if (response.success === false) {
            throw new Error(response.msg || "unknown error occured");
          } else {
            // Store User ID in Local Storage
            localStorage.setItem("lastLoggedInUserID", useridInp.value);
            const respMessage = response.msg;
            alert(respMessage);
            //displayMessage(respMessage);
            return;
          }
        } else {
          displayMessage("registration failed");
          throw new Error("Registration Failed");
        }
      } else {
        displayMessage("bad registration response");
        throw new Error(
          "Unable to fetch configuration for UserID " + useridInp.value
        );
      }
    } else {
      displayMessage(
        "No user-verifying authenticator present. Registration not possible on this device."
      );
    }
  } catch (err) {
    var error_msg = false;
    if (err.hasOwnProperty("message")) error_msg = err.message;
    else if (err) error_msg = err;

    //displayMessage(error_msg);
    window.alert(error_msg || "unknown error occured");
  }
};

userlogin = async () => {
  try {
    if (PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable()) {
      if (useridInp.value == "") {
        alert("Invalid UserID.");
        return false;
      }

      const challenge = await fetch("wapi/fidoauth/fido.json/login", {
        method: "POST",
        headers: {
          "content-type": "application/json",
        },
        body: JSON.stringify({ userid: useridInp.value }),
      });

      if (challenge.status === 200) {
        const getArgs = await challenge.json();

        // error handling
        if (getArgs.success === false) {
          throw new Error(getArgs.msg || "unknown error occured");
        }

        // get userUUID berefo converting
        const userUUIDValue = getUserID(getArgs);

        // replace binary base64 data with ArrayBuffer.
        recursiveBase64StrToArrayBuffer(getArgs);

        const cred = await navigator.credentials.get(getArgs);

        // get object
        const authenticatorAttestationResponse = {
          id: cred.rawId ? arrayBufferToBase64(cred.rawId) : null,
          clientDataJSON: cred.response.clientDataJSON
            ? arrayBufferToBase64(cred.response.clientDataJSON)
            : null,
          authenticatorData: cred.response.authenticatorData
            ? arrayBufferToBase64(cred.response.authenticatorData)
            : null,
          signature: cred.response.signature
            ? arrayBufferToBase64(cred.response.signature)
            : null,
          userHandle: cred.response.userHandle
            ? arrayBufferToBase64(cred.response.userHandle)
            : null,
          userUUID: userUUIDValue,
          rpID: location.hostname,
        };

        const loggedIn = await fetch("wapi/fidoauth/fido.json/loginchallenge", {
          method: "POST",
          headers: {
            "content-type": "application/json",
          },
          body: JSON.stringify(authenticatorAttestationResponse),
        });

        if (loggedIn.status === 200) {
          const response = await loggedIn.json();

          // error handling
          if (response.success === false) {
            throw new Error(response.msg || "unknown error occured");
          } else {
            // Store User ID in Local Storage
            localStorage.setItem("lastLoggedInUserID", useridInp.value);

            const respMessage = response.msg;
            alert(respMessage);
            //displayMessage(respMessage);
            top.location.href = uri_granted;

            return;
          }
        } else {
          let errorMsg = "Login Failed";
          displayMessage(errorMsg);
          throw new Error(errorMsg);
        }
      } else {
        displayMessage("bad registration response");
        throw new Error(
          "Unable to fetch configuration for UserID " + useridInp.value
        );
      }
    } else {
      displayMessage(
        "No user-verifying authenticator present. Registration not possible on this device."
      );
    }
  } catch (err) {
    displayMessage(err.message);
    window.alert(err.message || "unknown error occured");
  }
};

useridBtn.addEventListener("click", userlogin);
//registerBtn.addEventListener('click', userregister);
