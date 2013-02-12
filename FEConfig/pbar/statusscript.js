/*jslint white: true, browser: true, undef: true, nomen: true, eqeqeq: true, plusplus: false, bitwise: true, regexp: true, strict: true, newcap: true, immed: true, maxerr: 14 */
/*global window: false, ActiveXObject: false*/

/*
The onreadystatechange property is a function that receives the feedback. It is important to note that the feedback
function must be assigned before each send, because upon request completion the onreadystatechange property is reset.
This is evident in the Mozilla and Firefox source.
*/

/* enable strict mode */
//"use strict";

// global variables
var progress,				// progress element reference
	pmessage,				// message with status update
	mainmessage,			// message above status bar
	refurl,				    // url to redirect to after process is complete
	pimage,				    // image
	request,				// request object
	intervalID = false,		// interval ID
	number_max = 10000000,	// limit of how many times to request the server
	number,					// current number of requests
							// method definition
	initXMLHttpClient,		// create XMLHttp request object in a cross-browser manner
	send_request,			// send request to the server
	send_request_abort,		// send abort request to the server
	request_handler,		// request handler (started from send_request)
	progress_logfile,		// log file to be read
	polling_start,			// button start action
	polling_stop;			// button start action


// define reference to the progress bar and create XMLHttp request object
window.onload = function () {
	progress 	= document.getElementById('progress');
	pmessage 	= document.getElementById('pmessage');
	mainmessage = document.getElementById('mainmessage');
	pimage 		= document.getElementById('pimage');
	refurl 		= document.getElementById('refurl');
	request  	= initXMLHttpClient();
};


// create XMLHttp request object in a cross-browser manner
initXMLHttpClient = function () {
	var XMLHTTP_IDS,
		xmlhttp,
		success = false,
		i;
	// Mozilla/Chrome/Safari/IE7+ (normal browsers)
	try {
		xmlhttp = new XMLHttpRequest(); 
	}
	// IE(?!)
	catch (e1) {
		XMLHTTP_IDS = [ 'MSXML2.XMLHTTP.5.0', 'MSXML2.XMLHTTP.4.0',
						'MSXML2.XMLHTTP.3.0', 'MSXML2.XMLHTTP', 'Microsoft.XMLHTTP' ];
		for (i = 0; i < XMLHTTP_IDS.length && !success; i++) {
			try {
				success = true;
				xmlhttp = new ActiveXObject(XMLHTTP_IDS[i]);
			}
			catch (e2) {}
		}
		if (!success) {
			throw new Error('Unable to create XMLHttpRequest!');
		}
	}
	return xmlhttp;
};


// send request to the server
send_request = function () {
	if (number < number_max) {
		request.open('GET', 'getprogress.php?lf=' + progress_logfile, true);	// open asynchronus request
		request.onreadystatechange = request_handler;		// set request handler	
		request.send(null);									// send request
		number++;											// increase counter
	}
	else {
		polling_stop();
	}
};

//send abort request to the server
send_request_abort = function () {
	//Update "abort" key value to "1" in the status file
	if (number < number_max) {
		request.open('GET', 'abort.php?lf=' + progress_logfile, true);	// open asynchronus request
		request.onreadystatechange = request_handler;		// set request handler	
		request.send(null);									// send request
		number++;											// increase counter
	}
	else {
		polling_stop();
	}
};


// request handler (started from send_request)
request_handler = function () {
	var level;
	var pmess;
	var mainmess;
	var image;
	var error;
	var errormsg;
	var purl;
	var pabort;
	if (request.readyState === 4) { // if state = 4 (operation is completed)
		if (request.status === 200) { // and the HTTP status is OK
			// get progress from the XML node and set progress bar width and innerHTML
			level 	 = request.responseXML.getElementsByTagName('PROGRESS')[0].firstChild;
			pmess 	 = request.responseXML.getElementsByTagName('PMESSAGE')[0].firstChild;
			mainmess = request.responseXML.getElementsByTagName('MAINMESSAGE')[0].firstChild;
			image 	 = request.responseXML.getElementsByTagName('PIMAGE')[0].firstChild;
			purl     = request.responseXML.getElementsByTagName('PURL')[0].firstChild;
			error 	 = request.responseXML.getElementsByTagName('PERROR')[0].firstChild;
			errormsg = request.responseXML.getElementsByTagName('PERRORMSG')[0].firstChild;
			pabort   = request.responseXML.getElementsByTagName('PABORT')[0].firstChild;
			
			progress.style.width      = level.nodeValue + '%';
			progress.innerHTML        = level.nodeValue + '%';
			pmessage.innerHTML        = pmess.nodeValue;
			refurl.innerHTML          = "<a href='" + purl.nodeValue + "' target='blank'><font color='#C9C9D1'>" + purl.nodeValue + "</font></a>";
			mainmessage.innerHTML     = "<img src = 'spin1.gif'>" + mainmess.nodeValue;
			pimage.innerHTML          = "<img src='" + image.nodeValue + "'>";
		
			if (level.nodeValue == 100){
				polling_stop();
				window.location= purl.nodeValue.replace("&amp","&");
			}
			if (error.nodeValue == 1){
				pmessage.innerHTML        = "<font color='#ff0000'>" + errormsg.nodeValue + "</font>";
				request.readyState = 1;
				polling_stop_error();
			}
			if (pabort.nodeValue == 1){
				pmessage.innerHTML        = "<font color='#ff0000'>Aborted.</font>";
				request.readyState = 1;
				polling_stop();
				window.location= purl.nodeValue.replace("&amp","&");
			}
		}
		else { // if request status is not OK
			//progress.style.width = '100%';
			//progress.innerHTML = 'Error:[' + request.status + ']' + request.statusText;
			polling_stop();
		}
	}
};


// button start
polling_start = function (in_progress_logfile) {
	progress_logfile = in_progress_logfile;
	if (!intervalID) {
		// set initial value for current number of requests
		number = 0;
		// start polling
		//The setInterval() method will continue calling the function until clearInterval() 
		//is called, or the window is closed.
		intervalID = window.setInterval('send_request()', 1000);
	}
};


// button stop
polling_stop = function () {
	// abort current request if status is 1, 2, 3
	// 0: request not initialized 
	// 1: server connection established
	// 2: request received 
	// 3: processing request 
	// 4: request finished and response is ready
	if (0 < request.readyState && request.readyState < 4) {
		request.abort();
	}
	window.clearInterval(intervalID);
	intervalID = false;
	// open asynchronus request

	// display message
	pmessage.innerHTML = 'Stopped.';
	send_request_abort();
};




//error stop
polling_stop_error = function () {
	//open asynchronus request
	request.open('GET', 'abort.php?lf=' + progress_logfile, true);	
	request.onreadystatechange = request_handler;		// set request handler	
	request.send(null);									// send request
	number++;											// increase counter
	
	request.abort();

	window.clearInterval(intervalID);
	intervalID = false;
};
