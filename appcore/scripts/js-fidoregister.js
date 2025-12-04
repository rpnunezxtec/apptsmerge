const registerBtn = document.getElementById('btn_register');
const messageTxt = document.getElementById('txt_message');

/**
 * convert RFC 1342-like base64 strings to array buffer
 * @param {mixed} obj
 * @returns {undefined}
 */
function recursiveBase64StrToArrayBuffer(obj) {
	let prefix = '=?BINARY?B?';
	let suffix = '?=';
	if (typeof obj === 'object') {
		for (let key in obj) {
			if (typeof obj[key] === 'string') {
				let str = obj[key];
				if (str.substring(0, prefix.length) === prefix && str.substring(str.length - suffix.length) === suffix) {
					str = str.substring(prefix.length, str.length - suffix.length);

					let binary_string = window.atob(str);
					let len = binary_string.length;
					let bytes = new Uint8Array(len);
					for (let i = 0; i < len; i++)        {
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
function getUserID(obj) 
{
	if (typeof obj === 'object') 
	{
		for (let key in obj) 
		{
			if (key === 'user') 
			{
				let userObj = obj[key];
				userUIDValue = userObj["id"];
				
				return userUIDValue;
			} else {
				let userUid = getUserID(obj[key]);
				
				if(typeof userUid == "string")
					return userUid;
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
	let binary = '';
	let bytes = new Uint8Array(buffer);
	let len = bytes.byteLength;
	for (let i = 0; i < len; i++) {
		binary += String.fromCharCode( bytes[ i ] );
	}
	return window.btoa(binary);
}

userregister = async () => {
	
	try{
		if (PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable()) 
		{
			if(useridInp == "")
			{
				alert("Invalid UserID.");
				return false;
			}
			
			const challenge = await fetch(
				'wapi/fidoauth/fido.json/requestregister', 
				{
					method: 'POST',
					headers: {
						'content-type': 'application/json'
					},
					body: JSON.stringify({ userid: useridInp })
				}
			)
			
			if (challenge.status === 200) 
			{
				const createArgs = await challenge.json();
				
				// error handling
				if (createArgs.success === false) {
					throw new Error(createArgs.msg || 'unknown error occured');
				}
				
				// get userUUID berefo converting
				const userUUIDValue = getUserID(createArgs);
				
				// replace binary base64 data with ArrayBuffer.
				recursiveBase64StrToArrayBuffer(createArgs);
				
				const cred = await navigator.credentials.create(createArgs);
				
				// create object
				const authenticatorAttestationResponse = {
					transports: cred.response.getTransports  ? cred.response.getTransports() : null,
					clientDataJSON: cred.response.clientDataJSON  ? arrayBufferToBase64(cred.response.clientDataJSON) : null,
					attestationObject: cred.response.attestationObject ? arrayBufferToBase64(cred.response.attestationObject) : null,
					userUUID: userUUIDValue,
					rpID: location.hostname
				};
		
				const loggedIn = await fetch(
					'wapi/fidoauth/fido.json/register',
					{
						method: 'POST',
						headers: {
						'content-type': 'application/json'
						},
						body: JSON.stringify(authenticatorAttestationResponse)
					}
					);
		
				if (loggedIn.status === 200) 
				{
					const response = await loggedIn.json();
					
					// error handling
					if (response.success === false) 
					{
						throw new Error(response.msg || 'unknown error occured');
					}
					else
					{
						// Store User ID in Local Storage
						localStorage.setItem("lastLoggedInUserID", useridInp);
						const respMessage = response.msg;
						alert(respMessage);
						location.reload();
						return;
					}
				}
				else
				{
					//displayMessage('registration failed');
					throw new Error('Registration Failed');
				}
			}
			else
			{
				//displayMessage('bad registration response');
				throw new Error("Unable to fetch configuration for UserID " + useridInp);
			}
		}
		else 
		{
			//displayMessage('No user-verifying authenticator present. Registration not possible on this device.');
		}
	}
 	catch (err) {
		var error_msg = false;
		if(err.hasOwnProperty('message'))
			error_msg = err.message;
		else if(err)
			error_msg = err;
		
		//displayMessage(error_msg);
		window.alert(error_msg || 'unknown error occured');
	}
};

registerBtn.addEventListener('click', userregister);


