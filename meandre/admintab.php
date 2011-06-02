<?php

$strAction = $_POST['Action'];

if (empty($strAction)) {
	LoadOptions();
} else {
	if ($strAction == 'UpdateStoreURIs') {
		UpdateOptions();
	}
	if ($strAction == 'UpdateFlows') {
		require('updatetab.php');
	}
}

WritePage();

function LoadOptions() {
	global $arrOpts;
	
	$arrOpts = get_option('MeandreOpts');
}

function UpdateOptions() {
	global $arrOpts;
	$arrOpts = array();
	
	$strRepositoryURIs = $_POST['RepositoryURIs'];
	
	$arrOpts['RepositoryURIs'] = $strRepositoryURIs;
	
	update_option('MeandreOpts', $arrOpts);
}

function WritePage() {
	global $arrOpts;
?>

<h2>Meandre</h2>

<h3>Repository URIs</h3>

<form method="post" action="options-general.php?page=meandre/admintab.php">
<input type="hidden" name="Action" value="UpdateStoreURIs"/>
<table class="form-table">
  <tr>
    <th scope="row"><label for="RepositoryURIs">Repository URIs:</label></th>
    <td><span class="setting-description">Repository URIs to search for flows. Enter one URI per line. <strong>Must be public.</strong></span><br/>
	    <textarea name="RepositoryURIs" id="RepositoryURIs" cols="60" rows="4"><?php echo $arrOpts['RepositoryURIs']; ?></textarea></td>
  </tr>
  <tr>
    <td colspan="2" align="center"><input type="submit" value="Update Store URIs" class="button-primary"/></td>
  </tr>
</table>
</form>

<h3>Update Flows</h3>

<form method="post" action="options-general.php?page=meandre/admintab.php">
<input type="hidden" name="Action" value="UpdateFlows"/>
<p>
The following button will update the current Meandre data on this blog using page/post meta data and the above Repositories.<br/>
<input type="submit" value="Update Flows..." class="button-primary"/>
</p>

</form>

<?php } ?>
