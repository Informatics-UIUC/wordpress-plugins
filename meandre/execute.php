<?php

$strMeandreServer = $_GET['MeandreServer'];
$strFlowURI = $_GET['FlowURI'];

$strToken = time();

if (empty($strMeandreServer)) {
	$strMeandreServer = 'http://demo.seasr.org:1714/';
}

$strExecAPI = $strMeandreServer . 'services/execute/flow.txt?uri=' . $strFlowURI . '&token=' . $strToken;
$strWebUIAPI = $strMeandreServer .  'services/execute/uri_flow.txt?token=' . $strToken;

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"
   "http://www.w3.org/TR/html4/frameset.dtd">
<HTML>
<HEAD>
<TITLE>Execute Flow</TITLE>
</HEAD>
<script language="Javascript">
window.onload = window.setTimeout("Init()", 2000);
function Init() {
	window.frames[1].location.href='execute_webui.php?URI=<?php echo urlencode($strWebUIAPI); ?>';
}
</script>

  <FRAMESET rows="20%, 80%">
      <FRAME src="execute_flow.php?URI=<?php echo urlencode($strExecAPI); ?>">
      <FRAME src="">
  </FRAMESET>
  <NOFRAMES>

  </NOFRAMES>
</FRAMESET>
</HTML>