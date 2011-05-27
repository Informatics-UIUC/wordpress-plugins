<?php

require('functions.php');

$strURI = $_GET['URI'];

if (empty($strURI)) {
	return false;
}

$consoleOutput = GetFlowConsole();
if (!$consoleOutput) {
	return false;
}

function GetFlowConsole() {
	global $strURI;
	
	$result = CurlIt($strURI, 'admin', 'admin');
	if (!$result) {
		return false;
	}

	$json = json_decode($result);
	return $json[0]->console;
}

?>
<html>

<body onload="window.scrollTo(0,document['body'].offsetHeight);">

<pre>
<?php echo $consoleOutput; ?>
</pre>

</body>

</html>
