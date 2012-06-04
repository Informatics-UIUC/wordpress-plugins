<?php

$strMeandreServer = $_GET['MeandreServer'];
$strFlowURI = $_GET['FlowURI'];

$strToken = time();

if (empty($strMeandreServer)) {
	$strMeandreServer = 'http://leovip033.ncsa.uiuc.edu:1714/';
}

$strExecAPI = $strMeandreServer . 'services/execute/flow.txt?uri=' . $strFlowURI . '&token=' . $strToken;
$strWebUIAPI = $strMeandreServer .  'services/execute/uri_flow.json?token=' . $strToken;
$strConsoleAPI = $strMeandreServer . 'services/jobs/job_console.json';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"
   "http://www.w3.org/TR/html4/frameset.dtd">
<HTML>
<HEAD>
<TITLE>Execute Flow</TITLE>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/prototype/1.6.0.2/prototype.js"></script>
<script language="Javascript">
var intTries = 1;
var maxTries = 30;
var delayBetweenTries = 2000;  // total timeout = maxTries * delayBetweenTries
var consoleRefresh = 10000;
var strWebUI = '';
// Call Execute Flow AJAX Request
function ExecFlow() {
	var strExecAPI = '<?php echo $strExecAPI; ?>';
	var strURL = 'execute_flow.php?URI=' + escape(strExecAPI);
	new Ajax.Request(strURL, { method: 'get'});
}

function LoadWebUI() {
	var strWebUIAPI = '<?php echo $strWebUIAPI; ?>';
	var strURL = 'execute_webui.php?URI=' + escape(strWebUIAPI);
	new Ajax.Request(strURL, { method: 'get', onComplete: function(transport) {
		var strResp = transport.responseText;
		WebUI.document.getElementById("count").innerHTML = "[" + intTries + "/" + maxTries + "]";
		if (strResp.length > 0) {
			// Evaluate JSON - Returned as Array so use Index 0
			var objJSON = strResp.evalJSON();
			
			// Retry if localhost, still not ready
			if (objJSON[0].hostname == 'localhost') {
				intTries++;
				if (intTries <= maxTries) {
                               		 window.setTimeout("LoadWebUI()", delayBetweenTries);
                        	} else {
                                	WebUI.document.getElementById("msg").innerHTML = "Timed out - flow execution failed!";
                                	FlowConsole.document.getElementById("msg").innerHTML = "Timed out - flow execution failed!";
                        	}
				return false;
			}
			
			// Find WebUI Address and Redirect Top Frame
			strWebUI = 'http://' + objJSON[0].hostname + ':' + objJSON[0].port;
			WebUI.location.href = strWebUI;
			
			// Find Flow Execution Instance and Feed to Flow Console Proxy, Display in Lower Frame
			strFlowInst = objJSON[0].uri;
			ShowConsole();
		}
		else {
			// Not Ready, Try Again
			intTries++;
			if (intTries <= maxTries) {
				window.setTimeout("LoadWebUI()", delayBetweenTries);
			} else {
				WebUI.document.getElementById("msg").innerHTML = "Timed out - flow execution failed!";
				FlowConsole.document.getElementById("msg").innerHTML = "Timed out - flow execution failed!";
                        }
		}
  }});
}

// Feed Flow Instance to Console Proxy, Display in Lower Frame
function ShowConsole() {
	var strURL = '<?php echo $strConsoleAPI; ?>?uri=' + strFlowInst;
	FlowConsole.location.href = 'execute_console.php?URI=' + escape(strURL);
        window.setTimeout("ShowConsole()", consoleRefresh);
}

ExecFlow();
LoadWebUI();

</script>
</head>
  <FRAMESET rows="80%, 20%">
      <FRAME name="WebUI" src="loading.html"/>
      <FRAME name="FlowConsole" src="loading.html"/>
  </FRAMESET>
  <NOFRAMES>
  <BODY>Sorry, your browser does not support frames!</BODY>
  </NOFRAMES>
</FRAMESET>
</HTML>
