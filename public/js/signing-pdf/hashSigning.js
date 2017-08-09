var element = document.getElementById("sign");
callbackSigning('Information','Veuillez séléctionner un certificat pour signer');
if(element){
	console.log("Added event listener");
	element.addEventListener('click', signHash, false);
}

// Checks if the certificate and private key are stored in the session
if (typeof(Storage) !== "undefined") {
    if(sessionStorage.getItem("certificat") && sessionStorage.getItem("password")){
    	signHash();
    }
}

// Get the hash to be signed
function getHash(){
	console.log("Hash : " + document.getElementById("file-hash").innerHTML);
	var element = document.getElementById("file-hash").innerHTML;
	if(element == ""){
		callbackSigning('Echec','Le hash n\'a pu être généré : votre PDF est soit corrompu, soit verrouillé');
	}
	return element;
}

// Get the certificate password value
function getPassword(){
	console.log("Password : " + document.getElementById("keyPassword").value);
	var element = document.getElementById("keyPassword").value;
	if(element == ""){
		callbackSigning('Attention','Vous devez entrer un mot de passe pour le certificat');
	}
	return element;
}

// Get the data to be signed from the DOM and sign it
function signHash(evnt) {
    if(sessionStorage.getItem("certificat") && sessionStorage.getItem("password")){
    	console.log("Found certificate and privatekey");
		console.log("Certif : "+sessionStorage.getItem("certificat"));
		certif = sessionStorage.getItem("certificat");
		password = sessionStorage.getItem("password");
		var p12Der = forge.util.decode64(certif);				
		var pkcs12Asn1 = forge.asn1.fromDer(p12Der);				
		var pkcs12 = forge.pkcs12.pkcs12FromAsn1(pkcs12Asn1, password);
		signData(pkcs12,password,getHash(),false);
    }
	else{
		var file = document.getElementById("certificate").files[0];
		if (file) {
			console.log("File found");

			var reader = new FileReader();
		
			reader.readAsDataURL(file);
			reader.onload = function (evt) {
				try{
					var content = evt.target.result.split(',')[1];

					if (typeof(Storage) !== "undefined") {
					    console.log("Storage available !");
					    sessionStorage.setItem("certificat", content);
						sessionStorage.setItem("password", getPassword() );
					} else {
					    console.log("No storage available !");
					}
					
					var p12Der = forge.util.decode64(content);
					
					var pkcs12Asn1 = forge.asn1.fromDer(p12Der);
					
					var pkcs12 = forge.pkcs12.pkcs12FromAsn1(pkcs12Asn1, getPassword());

					signData(pkcs12,getPassword(),getHash(),false);
				}
				catch(error){
					console.log(error.message);
					callbackSigning('Echec','Votre certificat n\'a pu être lu : mauvais mot de passe ou format (.p12)');
				}
			};
			reader.onerror = function(event) {
			    console.log(event.target.error);
				callbackSigning('Echec','Votre certificat n\'a pu être lu : le fichier est peut-être corrompu');
			};
		}
		else{
			callbackSigning('Attention','Aucun certificat n\'est sélectionné');
		}
	}
}

// Sign some data with a PKCS12 certificate
function signData(pkcs12,password,data,debugMode){
 
	// load keypair and cert chain from safe content(s) and map to key ID
	var map = {};
	var privatekey;
	var certif;

	for(var sci = 0; sci < pkcs12.safeContents.length; ++sci) {
		var safeContents = pkcs12.safeContents[sci];
		//console.log('safeContents ' + (sci + 1));

		for(var sbi = 0; sbi < safeContents.safeBags.length; ++sbi) {
			var safeBag = safeContents.safeBags[sbi];
			//console.log('safeBag.type: ' + safeBag.type);

			var localKeyId = null;
			if(safeBag.attributes.localKeyId) {
				localKeyId = forge.util.bytesToHex(
					safeBag.attributes.localKeyId[0]);
				//console.log('localKeyId: ' + localKeyId);
				if(!(localKeyId in map)) {
					map[localKeyId] = {
						privateKey: null,
						certChain: []
					};
				}
			} else {
			// No local key ID, skip bag
				continue;
			}

			// this bag has a private key
			if(safeBag.type === forge.pki.oids.pkcs8ShroudedKeyBag) {
				map[localKeyId].privateKey = safeBag.key;
				privatekey = safeBag.key;
			} else if(safeBag.type === forge.pki.oids.certBag) {
				// this bag has a certificate
				map[localKeyId].certChain.push(safeBag.cert);
				certif = safeBag.cert;
			}
		}
	}

	if(debugMode) logCertificateInfo(map,password);
	
	var pkcs7 = forge.pkcs7.createSignedData();
	pkcs7.content = forge.util.createBuffer(data,'utf8');

	pkcs7.addCertificate(certif);
	
	pkcs7.addSigner({
		  key: privatekey,
		  certificate: certif,
		  digestAlgorithm: forge.pki.oids.sha256,
		  authenticatedAttributes: [{
		    type: forge.pki.oids.contentType,
		    value: forge.pki.oids.data
		  }, {
		    type: forge.pki.oids.messageDigest,
		    value: forge.util.hexToBytes(data)
		  }, {
		    type: forge.pki.oids.signingTime,
		    value: new Date()
		  }]
		});

	pkcs7.signDetached(); // DETACHED MODE (data not contained in the object)

	var result = forge.asn1.toDer(pkcs7.toAsn1()).getBytes();;
	var result = strHex(result);

	console.log("#PKCS7 object (HEX) : " + result);

	document.getElementById('signed-file-hash').innerHTML = result;
	callbackSigning('Succès','Hash généré');
	document.getElementById('signing-form').submit();
}


/* Subsidiary functions */


// Visual feedback when the signing has successed or failed
function callbackSigning(state,message){
	var callbackMessage = document.getElementById('callbackMessage');
	if(state == 'Succès'){
		document.getElementById('callbackMessage').style = 'display: inline; color: green;';
	}
	else if(state == 'Echec'){
		document.getElementById('callbackMessage').style = 'display: inline; color: red;'
	}
	else if(state == 'Attention'){
		document.getElementById('callbackMessage').style = 'display: inline; color: orange;'
	}
	else if(state == 'Information'){
		document.getElementById('callbackMessage').style = 'display: inline; color: #6495ED ;' // BLUE
	}	
	document.getElementById('state').innerHTML = state;
	document.getElementById('message').innerHTML = message;
}

// Logs all of the information about a certificate in the browser console
function logCertificateInfo(map,password){

	console.log('\nPKCS#12 Info:');
	for(var localKeyId in map) {
		var entry = map[localKeyId];
		console.log('\nLocal Key ID: ' + localKeyId);
		if(entry.privateKey) {
			var privateKeyP12Pem = forge.pki.privateKeyToPem(entry.privateKey);
			var encryptedPrivateKeyP12Pem = forge.pki.encryptRsaPrivateKey(
				entry.privateKey, password);

			console.log('\nPrivate Key:');
			console.log(privateKeyP12Pem);
			console.log('Encrypted Private Key (password: "' + password + '"):');
			console.log(encryptedPrivateKeyP12Pem);
		} else {
			console.log('');
		}
		if(entry.certChain.length > 0) {
			console.log('Certificate chain:');
			var certChain = entry.certChain;
			for(var i = 0; i < certChain.length; ++i) {
				var certP12Pem = forge.pki.certificateToPem(certChain[i]);
				console.log(certP12Pem);
			}
		}
	}
}

function strHex(s) {
    var a = "";
    for( var i=0; i<s.length; i++ ) {
		a = a + pad2(s.charCodeAt(i).toString(16));
    }
    return a;
}

function pad2(num) {
    var s = "0" + num;
    return s.substr(s.length-2);
}