<?php

set_time_limit(0);

LoadPostFlows();
LoadOptionFlows();

function ListFlowPosts() {
	global $arrFlowPosts;
	
	foreach ($arrFlowPosts as $strThisFlow) {
		$intThisPostID = FlowPostExists($strThisFlow);		
?>
  <tr>
    <td><a href="<?php echo get_permalink($intThisPostID); ?>"><?php echo get_the_title($intThisPostID); ?></a></td>
	<td><?php echo $strThisFlow; ?></td>
	<td><a href="page.php?action=edit&post=<?php echo $intThisPostID; ?>">Edit</a></td>
  </tr>
<?php
	}
}

function ListMissingPosts() {
	global $arrMissingFlows;

	if (!is_array($arrMissingFlows)) {
		return false;
	}

	foreach ($arrMissingFlows as $strThisStore => $arrThisFlows) {
		foreach ($arrThisFlows as $strThisFlow) {
?>
  <tr>
	<th scope="row" class="check-column"><input type="checkbox" name="<?php echo md5($strThisFlow); ?>" value="<?php echo $strThisStore; ?>"/><input type="hidden" name="Flows[]" value="<?php echo $strThisFlow; ?>"/></th>
	<td><?php echo $strThisFlow; ?></td>
	<td><?php echo $strThisStore; ?></td>
  </tr>
<?php
		}
	}
}

?>
<h2>Meandre</h2>

<h3>Updated Pages</h3>

<table class="widefat">
  <thead>
  <tr>
    <th scope="col">Post</th>
    <th scope="col">Flow URI</th>
	<th scope="col">Action</th>
  </tr>
  </thead>
  <tbody>
<?php ListFlowPosts(); ?>
  </tbody>
</table>

<h3>Missing Pages</h3>

<form method="post" action="options-general.php?page=meandre/missingtab.php">
<table class="widefat">
  <thead>
  <tr>
    <th scope="col" class="check-column">&nbsp;</th>
    <th scope="col">Flow URI</th>
    <th scope="col">Store URI</th>
  </tr>
  </thead>
  <tbody>
<?php ListMissingPosts(); ?>
  </tbody>
</table>

<p align="center"><strong><label for="page_id">Page Parent:</label></strong> <?php wp_dropdown_pages(); ?></p>

<p align="center"><input type="submit" value="Create Draft Pages..."/></p>

</form>