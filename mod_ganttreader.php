<?php
/*Style declaration*/


defined('_JEXEC') or die('Restricted access');

require_once(dirname(__FILE__).'/helper.php' );

//gets parameters setted in bak-end
$title= modXmlReaderHelper::getTitle($params);

$color=modXmlReaderHelper::getColor($params);

//Picks-up useful files
$ganfile = JPATH_SITE.'/media/mod_xmlreader/gantt.gan';

$cssfile = JURI::root().'media/mod_xmlreader/mod_xmlreader.css';

$document = JFactory::getDocument();


//Defines CSS by default then specific style using defined parameters
$document->addStyleSheet($cssfile);

$customStyle = 	
	'fieldset{
		background-color: '.modXmlReaderHelper::getColor($params).';
	}';

$document->addStyleDeclaration($customStyle);

//Loads gantt file through a SimpleXMLElement parser
$gan = simplexml_load_file($ganfile);

//gets the right layout path (default JModuleHelper::getLayoutPath( 'mod_xmlreader' ) doesn't work)

require( JModuleHelper::getLayoutPath( 'mod_xmlreader', $params->get('layout') ) );
?>