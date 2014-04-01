<?php

defined('_JEXEC') or die('Restricted access');

/* Récupération des fichiers media */
$cssFile = JURI::root().'media/mod_ganttreader/mod_ganttreader.css';

$stripesPic = JURI::root().'media/mod_ganttreader/stripes.png';


/* Définition des CSS statiques */
JFactory::getDocument()->addStyleSheet($cssFile);


/*Chargement des modèles*/
require_once(dirname(__FILE__).'/models/parser.php');

require_once(dirname(__FILE__).'/models/date.php');

require_once(dirname(__FILE__).'/models/drawer.php');


/* Traitement des informations */
require(dirname(__FILE__).'/helper.php');


/* Chargement de la vue */
require( JModuleHelper::getLayoutPath( 'mod_ganttreader', $params->get('layout') ) ); //Selectionne le template choisi dans le backend

?>