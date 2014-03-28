
<?php

/************************************************************************************
 * Utilitaire de récupération des informations contenues dans le fichier GanttProject
 ************************************************************************************/
class GanttReaderParser{
	
	/**
	 * @params SimpleXMLElement $gan l'instance du parseur (contient déjà les infos) et la couleur par défaut des projets $defaultColor
	 * @return un array() le tableau des projets organisées selon clé => valeur
	 * chaque projet se présente en tableau associatif contenant chacune les informations extraites
	 */
	static function getProjects(&$gan, &$vacations, $defaultColor, $earliest, $lastest){
		$projects = NULL;	//valeur par défaut, évite les diagrammes vides
		
		if(isset($gan->tasks)){
			foreach($gan->tasks->task as $task){
				GanttReaderParser::getProjectProperties($task, $gan, $defaultColor, $projects, $vacations, $earliest, $lastest);
			}
		}

		return $projects;
	}
	
	/**
	 * @param $task la tâche à insérer
	 * @param $gan l'instance du parseur XML
	 * @param $defaultColor, la couleur par défaut des projets
	 * @param $projects le tableau des projets à compléter avec l'ajout du projet actuel
	 * How : Concatène les données du projet courant au tableau des projets, puis fais de même récursivement avec hacun de ses projets fils
	 */
	 static function getProjectProperties($task, &$gan, $defaultColor,&$projects, &$vacations, $earliest, $lastest){
		 
		 		$id = $task->attributes()->id->__toString();
				$nom = $task->attributes()->name->__toString();
				$duree = $task->attributes()->duration->__toString(); //durée selon GanttProject, ie le nombre de jours ouvrés

				$couleur = isset($task->attributes()->color) ? /*si couleur non précisée, prendre celle par défaut*/
					$task->attributes()->color->__toString() : 
					$defaultColor; 
					
				$debut = $task->attributes()->start->__toString();
				$longueur = GanttReaderDate::projectLength($debut, $duree, $vacations); //taille (en jours) du projet

				if(strtotime($debut)<$earliest){
					$longueur = $longueur - GanttReaderDate::gap(strtotime($debut), $earliest); //enlever le surplus
					$debut = date('Y-m-d', $earliest);

					
				}
				
				if(strtotime('+'.$longueur.' days', strtotime($debut))>$lastest){
					$longueur = GanttReaderDate::gap(strtotime($debut), $lastest)+1;
				}
				
				
				
				$meeting = $task->attributes()->meeting->__toString()==='true';
				$avancement = $task->attributes()->complete->__toString();
				$hasChild = isset($task->task);
			
				
				$projects[] = array(
								'id' => $id,
								'nom' => $nom,
								'couleur' => $couleur,
								'debut' => $debut,
								'duree' => $duree,
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