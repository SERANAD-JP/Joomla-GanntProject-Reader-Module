<?php
/*
 * Utilitaire de récupération des données
 * Helper chargé de retrouver les paramètres fournis dans le backEnd et de fournir les informations du diagramme via le parser
 */


/*  Récupération des paramètres back-end  */
$title = $params->get('title');

$range = $params->get('range');

$defaultColor = $params->get('defaultColor'); //couleur par defaut des projets

$dayBoxColor = $params->get('dayBoxColor'); //couleur des cases "normales"

$dayOffColor = $params->get('dayOffColor');

$constraintColor = $params->get('constraintColor');

$titleColor = $params->get('titleColor');

$textColor = $params->get('textColor');

$todayColor = $params->get('todayColor');

//Ajout des styles parametrés
$styles = 	'
			#ganttDiagram, .dayBox{
    			background-color:'.$dayBoxColor.';
				color:'.$textColor.';
			}
			
			.ganttEmbed{
				color:'.$titleColor.';
			}
			
			time{
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
			
$document->addStyleDeclaration($styles);

/*  Extraction des infos depuis le fichier GanttProject  */
$projects = GanttReaderParser::getProjects($gan, $defaultColor); 
$projects = GanttReaderDate::filterProjects($projects, $range); //filtrage
$constraints = GanttReaderParser::getConstraints($gan, $projects);

$vacations = GanttReaderParser::getVacations($gan);

$earliest = GanttReaderDate::earliestMonth($range); //Les mois les plus étendus à parcourir parmi les projets
$lastest = GanttReaderDate::lastestMonth($range);

?>