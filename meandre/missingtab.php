<?php

set_time_limit(0);

function PostMissing() {
	global $wpdb;
	$arrFlows = $_POST['Flows'];
	$intParentPageID = $_POST['page_id'];
	
	if (!is_array($arrFlows)) {
		return false;
	}
	
	$strPostBody = '[MeandreDescribeFlow] [MeandreListFlowsByFlowTags]';
	
	foreach ($arrFlows as $strThisFlow) {
		if (!empty($_POST[md5($strThisFlow)])) {
			$strThisStore = $_POST[md5($strThisFlow)];
			
			if (strlen($strThisFlow) < 1 || strlen($strThisStore) < 1) {
				continue;
			}
			
			$intPostID = wp_insert_post(array('post_status' => 'draft',
								'post_type' => 'page',
								'post_parent' => $intParentPageID,
								'post_title' => $strThisFlow,
								'post_content' => $strPostBody));
			
			if (is_numeric($intPostID)) {
				$strSQL = 'INSERT INTO ' . $wpdb->prefix . 'postmeta (post_id, meta_key, meta_value) VALUES (' . $intPostID . ',\'FlowURI\', \'' . $strThisFlow . '\')';
				$wpdb->query($strSQL);
				
				$strSQL = 'INSERT INTO ' . $wpdb->prefix . 'postmeta (post_id, meta_key, meta_value) VALUES (' . $intPostID . ',\'StoreURI\', \'' . $strThisStore . '\')';
				$wpdb->query($strSQL);
?>
  <tr>
    <td><?php echo $strThisFlow; ?></td>
    <td><a href="page.php?action=edit&post=<?php echo $intPostID; ?>">Edit</a></td>
  </tr>
<?php
			}
		}
	}
} 

?>

<h2>Meandre</h2>

<h3>Draft Flow Pages Created</h3>

<table class="widefat">
  <thead>
  <tr>
    <th scope="col">Post</th>
    <th scope="col">Action</th>
  </tr>
  </thead>
  <tbody>
<?php PostMissing(); ?>
  </tbody>
</table>