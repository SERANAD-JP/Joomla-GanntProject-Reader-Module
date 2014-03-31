
<?php

/************************************************************************************
 * Utilitaire de récupération des informations contenues dans le fichier GanttProject
 * Récupération des éléments suivants :
 *	Projets contenus dans le fichier
 *		Propriétés de ces projets
 *	Contraintes de suite entre différents projets
 *	Plages de congés
 ************************************************************************************/
class GanttReaderParser{
	
	/**
	 * @param (SimpleXMLElement) $gan l'instance du parseur (l'objet contient les informations qui étaient contenues dans le fichier GanttProject)
	 * @param (array) $vacations le tableau des plages de congés
	 * @param (String) $defaultColor la couleur par défaut des projets
	 * @params (timestamp) $earliest et $lastest les dates des dates au plus tôt et au plus tard de la vue 
	 * @return un array() le tableau des projets organisées selon clé => valeur
	 $ @see http://php.net/manual/fr/book.simplexml.php
	 */
	static function getProjects(&$gan, &$vacations, $defaultColor, $earliest, $lastest){

		$projects = NULL;	//valeur par défaut, protège des diagrammes vides
		
		if(isset($gan->tasks)){
			foreach($gan->tasks->task as $task){
				GanttReaderParser::getProjectProperties($task, $gan, $defaultColor, $projects, $vacations, $earliest, $lastest);
			}
		}
		
		return $projects;
	}
	
	/**
	 * @param (SimpleXMLElement) $task la tâche à insérer
	 * @param (SimpleXMLElement) $gan l'instance du parseur XML
	 * @param (String) $defaultColor, la couleur par défaut du projet
	 * @param (array) $projects le tableau des projets à compléter avec l'ajout du projet actuel
	 * How : Ajoute les données du projet courant au tableau des projets, puis fais de même récursivement avec chacun de ses projets fils s'il en a
	 */
	 static function getProjectProperties($task, &$gan, $defaultColor,&$projects, &$vacations, $earliest, $lastest){
		 
		 		$id = $task->attributes()->id->__toString();
				$nom = $task->attributes()->name->__toString();

				$couleur = isset($task->attributes()->color) ? /*si couleur non précisée, prendre celle par défaut*/
					$task->attributes()->color->__toString() : 
					$defaultColor; 
					
				$debut = $task->attributes()->start->__toString();
				$duree = $task->attributes()->duration->__toString(); //durée selon GanttProject, ie le nombre de jours ouvrés
				$meeting = $task->attributes()->meeting->__toString()==='true';

				$longueur = GanttReaderDate::projectLength($debut, $duree, $vacations); //taille (en jours) du projet

				$fin = strtotime('+'.($longueur).' days', strtotime($debut));
				
				
				

				/* On tronque les projets qui ne rentrent pas en entier */
				
				if(strtotime($debut)<$earliest && $fin>$earliest){ // Si commence trop tôt

					$longueur = GanttReaderDate::gap($fin, $earliest); //enlever le surplus
					
					$debut = date('Y-m-d', $earliest); //placer le commencement au début de la vue
					
				}
				
				if($fin>$lastest && strtotime($debut)<$lastest){//si finis trop tard
					$longueur = GanttReaderDate::gap(strtotime($debut), $lastest);
				}
				
				//echo($nom.' '.date('d/m/Y', strtotime($debut)).' -> '.date('d/m/Y', strtotime('+'.($longueur).' days', strtotime($debut))).'<br />');
				//echo $nom.' -> '.$longueur.'<br />';
				
				$avancement = $task->attributes()->complete->__toString();
				$hasChild = isset($task->task);
			
				$projects[] = array(
								'id' => $id,
								'nom' => $nom,
								'couleur' => $couleur,
								'debut' => $debut,
								'longueur' => $longueur,
								'avancement' => $avancement,
								'meeting' =>$meeting,
								'hasChild' => $hasChild,
								);
								
						
				if(isset($task->task)){ //s'il existe une sous-tâche de cette tâche
					foreach($task->task as $subTask){
						GanttReaderParser::getProjectProperties($subTask, $gan, $defaultColor, $projects, $vacations, $earliest, $lastest);
					}
				}
				
	 }
	
	/*
	 * @param SimpleXMLElement $gan l'instance du parseur du diagramme de gantt et la liste des $projects extraits
	 * @return le tableau des $constraints selon 
	 * 		['from']=> provenance de la contrainte
	 *		['to']=> projet cible de la contrainte
	 * How : Dans un tableau $indexes[] on associe l'id d'un projet avec son ordre d'apparition dans le parser
	 */
	static function getConstraints(&$gan, &$projects){
		
		$constraints=NULL; //null par défaut, contre l'absence de contraintes
	
		if(!isset($projects)){ //protection VS absence de projets
			return NULL;
		}
		
		$i=0; //Index d'apparition
		
		foreach($projects as $project){ //établir le lien id => index pour chaque projet
			$indexes[$project['id']] = $i++;	
		}
		
		
		foreach($gan->tasks->task as $task){
			
			GanttReaderParser::getConstraintProperties($task, $gan, $projects, $constraints, $indexes);
		}
		
		return $constraints;
	}
	
	
	/**
	 *
	 */
	static function getConstraintProperties(&$task, &$gan, &$projects, &$constraints, &$indexes){
		if(isset($task->task)){
				foreach($task->task as $subTask){
					GanttReaderParser::getConstraintProperties($subTask, $gan, $projects, $constraints, $indexes);
				}
			}
			
			foreach($task->depend as $dep){ //récupérer les contraintes (dépendances) avec les id	
				if(isset($indexes[$task->attributes()->id->__toString()], $indexes[$dep->attributes()->id->__toString()])) //seulement si la source de la contrainte s'affiche dans le diagramme
				$constraints[]= array(	
										'from' => $indexes[$task->attributes()->id->__toString()],
										'to' => $indexes[$dep->attributes()->id->__toString()]
										);
			}
	}
	
	
	/*
	 * @param SimpleXMLElement $gan l'instance du parseur
	 * @return array() les plages de congés avec dates de début et de fin
	 */
	static function getVacations(&$gan){
		
		$vacations=NULL;//null par défaut, contre l'absence de congés
		
		if(isset($gan->vacations->vacation))
		
			foreach ($gan->vacations->vacation as $vacation){
				$start = $vacation->attributes()->start->__toString();
				$end = $vacation->attributes()->end->__toString();
				$vacations[] = array(
									'start' => $start,
									'end' => $end
									);
			}
			
		return $vacations;
	}
}


?>