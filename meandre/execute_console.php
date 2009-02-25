<?php

require('functions.php');

$strURI = $_GET['URI'];

if (empty($strURI)) {
	return false;
}

function WriteFlowConsole() {
	global $strURI;
	
	$strResponse = CurlIt($strURI, 'admin', 'admin');
	echo $strResponse;
}

?>
<html>

<body>

<pre>
<?php WriteFlowConsole(); ?>
</pre>

</body>

</html>