<?php

$strMeandreServer = $_GET['MeandreServer'];
$strFlowURI = $_GET['FlowURI'];

$strToken = time();

if (empty($strMeandreServer)) {
	$strMeandreServer = 'http://demo.seasr.org:1714/';
}

$strExecAPI = $strMeandreServer . 'services/execute/flow.txt?uri=' . $strFlowURI . '&token=' . $strToken;
$strWebUIAPI = $strMeandreServer .  'services/execute/uri_flow.json?token=' . $strToken;
$strConsoleAPI = $strMeandreServer . 'services/jobs/job_console.txt';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"
   "http://www.w3.org/TR/html4/frameset.dtd">
<HTML>
<HEAD>
<TITLE>Execute Flow</TITLE>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/prototype/1.6.0.2/prototype.js"></script>
<script language="Javascript">
var intTries = 0;
var strWebUI = '';
// Call Execute Flow AJAX Request
function ExecFlow() {
	var strExecAPI = '<?php echo $strExecAPI; ?>';
	var strURL = 'execute_flow.php?URI=' + escape(strExecAPI);
	new Ajax.Request(strURL, { method: 'get'});
}

// Load Web UI Service, Try Until Ready (up to 30 seconds)
function LoadWebUI() {
	var strWebUIAPI = '<?php echo $strWebUIAPI; ?>';
	var strURL = 'execute_webui.php?URI=' + escape(strWebUIAPI);
	new Ajax.Request(strURL, { method: 'get', onComplete: function(transport) {
		var strResp = transport.responseText;
		if (strResp.length > 1) {
			// Evaluate JSON - Returned as Array so use Index 0
			var objJSON = strResp.evalJSON();
			
			// Retry if localhost, still not ready
			if (objJSON[0].hostname == 'localhost') {
				intTries++;
				window.setTimeout("LoadWebUI()", 2000);
				return false;
			}
			
			// Find WebUI Address and Redirect Top Frame
			strWebUI = 'http://' + objJSON[0].hostname + ':' + objJSON[0].port;
			window.frames[0].location.href = strWebUI;
			
			// Find Flow Execution Instance and Feed to Flow Console Proxy, Display in Lower Frame
			strFlowInst = objJSON[0].uri;
			ShowConsole();
		}
		else {
			// Not Ready, Try Again
			intTries++;
			if (intTries < 15) {
				window.setTimeout("LoadWebUI()", 2000);
			}
		}
  }});
}

// Feed Flow Instance to Console Proxy, Display in Lower Frame
function ShowConsole() {
	var strURL = '<?php echo $strConsoleAPI; ?>?uri=' + strFlowInst;
	window.frames[1].location.href = 'execute_console.php?URI=' + escape(strURL);
}

ExecFlow();
LoadWebUI();

</script>
</head>
  <FRAMESET rows="80%, 20%">
      <FRAME name="WebUI" src=""/>
	  <FRAME name="FlowConsole" src=""/>
  </FRAMESET>
  <NOFRAMES>

  </NOFRAMES>
</FRAMESET>
</HTML>