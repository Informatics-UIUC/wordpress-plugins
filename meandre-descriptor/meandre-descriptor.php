<?php
/*
Plugin Name: Meandre Descriptor
Plugin URI: http://seasr.org/meandre
Description: A filter for WordPress that displays the description of a component or flow.
Version: 0.1
Author: Xavier Llor&agrave;
Author URI: http://www.xavierllora.net

Plugin based on SlideShare plugin (http://wordpress.org/extend/plugins/slideshare/)

Installation: copy meandre-descriptor.php to the wp-content/plugins directory of your Wordpress installation, then activate the plugin. For embedding a Meandre description for a component or flow type

[meandre-desc URL URI]

into the text area. Replace "URL" and "URI" with the corresponding values from the repository URL and the component/flow URI. For embedding the description of the default demo flow you can add

[meandre-desc http://localhost:1714/public/services/demo_repository.rdf http://test.org/flow/test-hello-world-with-python-and-lisp/]

This assumes you are running the Meandre server on the same host as your Wordpress instance.

*/

#include_once($_SERVER["DOCUMENT_ROOT"]."/wp-content/plugins/meandre-descriptor/arc/ARC2.php");
#include_once($_SERVER["DOCUMENT_ROOT"]."/wp-content/plugins/meandre-descriptor/rdfapi-php/api/RdfAPI.php");

define("RDFAPI_INCLUDE_DIR", $_SERVER["DOCUMENT_ROOT"]."/wp-content/plugins/meandre-descriptor/rdfapi-php/api/");
include_once($_SERVER["DOCUMENT_ROOT"]."/wp-content/plugins/meandre-descriptor/rdfapi-php/api/RdfAPI.php");

define("MRD_REGEXP", "/\[meandre-desc ([[:print:]]+) ([[:print:]]+)\]/");

function mdr_plugin_callback($match)
{
	// Load and run the query
	$model = ModelFactory::getDefaultModel();
	$model->load($match[1]);

	$q = 'SELECT ?name, ?creator, ?date, ?description, ?rights, ?tag
		WHERE ( <'.$match[2].'>, <meandre:name>, ?name ),
		      ( <'.$match[2].'>, <dc:creator>, ?creator ),
		      ( <'.$match[2].'>, <dc:date>, ?date ),
		      ( <'.$match[2].'>, <dc:rights>, ?rights ),
		      ( <'.$match[2].'>, <dc:description>, ?description ),
		      ( <'.$match[2].'>, <meandre:tag>, ?tag )
		USING meandre for <http://www.meandre.org/ontology/>
                      dc for <ihttp://purl.org/dc/elements/1.1/>';

	$rdqlIter = $model->rdqlQueryasIterator($q);

	// Dump the output
	$r ='<table id="meandre-description">';
	$tags = array();
	$desc = '';
	$cnt = 0;
	while ($rdqlIter->hasNext()) {
		$row = $rdqlIter->next();
		if ( $cnt==0 ) {
			$r .=  "<tr><td><b>Source:</b></td><td>".$match[1]. "</td></tr>";
			$r .=  "<tr><td><b>URI:</b></td><td>".$match[2]. "</td></tr>";
			$r .=  "<tr><td><b>Name:</b></td><td>".$row['?name']->getLabel(). "</td></tr>";
			$r .=  "<tr><td><b>Creator:</b></td><td>".$row['?creator']->getLabel(). "</td></tr>";
			$r .=  "<tr><td><b>Date:</b></td><td>".str_replace("T"," (",$row['?date']->getLabel()). ")</td></tr>";
			$r .=  "<tr><td><b>Rights:</b></td><td>".$row['?rights']->getLabel(). "</td></tr>";
			$desc =  "<tr><td><b>Description:</b></td><td>".$row['?description']->getLabel(). "</td></tr>";
		}
		$tags[] =  $row['?tag']->getLabel();
		$cnt = $cnt+1;
	}
	if ( $cnt==0 ) {
		// Description not found
		return "<div id=\"meandre-description\">Could not retrieve description for ".$match[2]." from repository ".$match[1]."</div>";
	} 
	else {
		// Return the description
		$r .= "<tr><td><b>Tags:</b></td><td>".implode(", ",$tags)."</td></tr>";
		$r .= $desc;
		$r .= "</table>";
	
		// Return the result
		return ($r);
	}
}

function mdr_plugin($content)
{
	return (preg_replace_callback(MRD_REGEXP, 'mdr_plugin_callback', $content));
}

add_filter('the_content', 'mdr_plugin');
add_filter('comment_text', 'mdr_plugin');

?>
