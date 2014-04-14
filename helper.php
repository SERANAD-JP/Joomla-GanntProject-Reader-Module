<?php

defined('_JEXEC') or die('Restricted access');

/*******************************************************************************************************************************
 * Utilitaire de récupération des données
 * Le helper est chargé de retrouver les paramètres fournis dans le backend, d'extraire les données via le parseur et chercher des erreurs
 *******************************************************************************************************************************/

$errors = ''; //On initialise les erreurs à (éventuellement) afficher plus tard

/*  Récupération des paramètres back-end  */

$isLocal = $params->get('isLocal'); //le fichier est-il dans l'arborescence Joomla ?

$path = $params->get('path'); //chemin vers le fichier GanttProject

$range = $params->get('range'); //nombre de mois à afficher de part et d'autre du mois courant

$defaultColor = $params->get('defaultColor'); //couleur par defaut des projets

$dayBoxColor = $params->get('dayBoxColor'); //couleur des cases "normales"

$dayOffColor = $params->get('dayOffColor'); //couleur des jours vaqués

$constraintColor = $params->get('constraintColor'); //couleur des flèches

$titleColor = $params->get('titleColor');

$textColor = $params->get('textColor'); 

$todayColor = $params->get('todayColor'); //couleur de la barre marquant la date du jour actuel



/* On vérifie si les couleurs du backEnd fournies sont correctes(correspondent au masque "#ABCDEF")*/

$colorPattern='(#{1}(?:[A-F0-9]){6})(?![0-9A-F])';	// Pattern regex pour une couleur codée en hexadécimal

if (!preg_match_all ("/".$colorPattern."/is", $defaultColor, $matches))
{
	$errors.=JText::_('MOD_GANTTREADER_DEFAULTCOLOR_ERROR').'<br />';
}
if (!preg_match_all ("/".$colorPattern."/is", $dayBoxColor, $matches))
{
	$errors.=JText::_('MOD_GANTTREADER_BOXCOLOR_ERROR').'<br />';
}
if (!preg_match_all ("/".$colorPattern."/is", $dayOffColor, $matches))
{
	$errors.=JText::_('MOD_GANTTREADER_OFFCOLOR_ERROR').'<br />';
}
if (!preg_match_all ("/".$colorPattern."/is", $constraintColor, $matches))
{
	$errors.=JText::_('MOD_GANTTREADER_CONSTRAINTCOLOR_ERROR').'<br />';
}
if (!preg_match_all ("/".$colorPattern."/is", $titleColor, $matches))
{
	$errors.=JText::_('MOD_GANTTREADER_TITLECOLOR_ERROR').'<br />';
}
if (!preg_match_all ("/".$colorPattern."/is", $textColor, $matches))
{
	$errors.=JText::_('MOD_GANTTREADER_TEXTCOLOR_ERROR').'<br />';
}
if (!preg_match_all ("/".$colorPattern."/is", $todayColor, $matches))
{
	$errors.=JText::_('MOD_GANTTREADER_TODAYCOLOR_ERROR').'<br />';
}
/* Fin de vérification des couleurs */

switch(JFactory::getLanguage()->getTag()){ //afficher le titre dans quelle langue ? (anglais = par défaut)
/* strucuture en switch pour implémentation facile  de langages supplémentaires */
	case 'fr-FR':
		$title = $params->get('frenchTitle');
	break;
	
	default:
		$title = $params->get('englishTitle');
}

if(empty($title)){
	$errors.=JText::_('MOD_GANTTREADER_MISSINGTITLE_ERROR').'<br />';
}

/* Déclaration du parseur */
if($isLocal){
	$ganttPath =(JPATH_SITE.'/'.$path);
	
} else{ //si fichier externe, le télécharger dans le repertoire temporaire
	
		@copy($path, sys_get_temp_dir().'/temp.gan'); // le @ bloque l'affichage de la possible erreur 404
		@$ganttPath = sys_get_temp_dir().'/temp.gan';
	}

if(!@$gan=simplexml_load_file($ganttPath)){//Si le chargement échoue, alors ajouter l'erreur 404 à la liste d'erreurs
	$errors.=JText::_('MOD_GANTTREADER_404_ERROR').'<br />';
}

if(!$isLocal){
	unlink($ganttPath); //supprimer le fichier temporaire quand on a fini la lecture
}


//Ajout des styles en fonction des paramètres
if(empty($errors)){
$styles = 	'
			#ganttDiagram, .dayBox{
    			background-color:'.$dayBoxColor.';
				color:'.$textColor.';
			}
			
			.ganttEmbed{
				color:'.$titleColor.';
			}
			
			#time{
				background-color:'.$todayColor.';
			}
			
			marker, path{
				background-color:'.$constraintColor.';
				stroke:'.$constraintColor.';
			}
			
			td.dayOff{
				background-color:'.$dayOffColor.';
			}
			
			.ganttProject, .ganttProjectEnd, .ganttProjectStart{
				background-color:'.$defaultColor.';
				
			}
			
			.complete{
				background:url('.$stripesPic.');
			}
			
			';		
	JFactory::getDocument()->addStyleDeclaration($styles);	
}


$earliest = GanttReaderDate::earliestMonth($range); //mois le plus ancien à afficher
$lastest = GanttReaderDate::lastestMonth($range); //mois le plus avancé à afficher


/*  Extraction des infos depuis le fichier GanttProject  */

$vacations = GanttReaderParser::getVacations($gan); //extraction des plages de congés

$projects = GanttReaderParser::getProjects($gan, $vacations, $defaultColor, $earliest, $lastest); //extraction brute des projets

if(empty($projects)){
	$errors.=JText::_('MOD_GANTTREADER_EMPTYDIAGRAM_ERROR').'<br />';
} else{
	$projects = GanttReaderDate::filterProjects($projects, $range); //filtrage des projets à afficher

	if(empty($projects)){
		$errors.=Jtext::_('MOD_GANTTREADER_NOTHINGTODISPLAY_ERROR').'<br />';
	}
}

$constraints = GanttReaderParser::getConstraints($gan, $projects); //extraction des contraintes

?>