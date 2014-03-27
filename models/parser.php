
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
	static function getProjects(&$gan, $defaultColor){
		$projects = NULL;	//valeur par défaut, évite les diagrammes vides
		
		if(isset($gan->tasks)){
			foreach($gan->tasks->task as $task){
				
				GanttReaderParser::getProjectProperties($task, $gan, $defaultColor, $projects);
			}
		}
		
		return $projects;
	}
	
	/**
	 * @param $task la tâche à insérer
	 * @param $gan l'instance du parseur XML
	 * @param $defaultColor, la couleur par défaut des projets
	 * @param $projects le tableau des projets à compléter avec l'ajout du projet actuel
	 */
	 static function getProjectProperties($task, &$gan, $defaultColor,&$projects){
		 
		 		//TODO si n'a pas d'enfant task, renvoyer la suite, sinon renvoyer le tableau de retour des enfants
				//NB noter récursif
		 
		 		$id = $task->attributes()->id->__toString();
				$nom = $task->attributes()->name->__toString();
				
				$couleur = isset($task->attributes()->color) ? /*si couleur non précisée, prendre celle par défaut*/
					$task->attributes()->color->__toString() : 
					$defaultColor; 
					
				$debut = $task->attributes()->start->__toString();
				$meeting = $task->attributes()->meeting->__toString()==='true';
				$duree = $task->attributes()->duration->__toString();
				$avancement = $task->attributes()->complete->__toString();
				$hasChild = isset($task->task);
				
				$projects[] = array(
								'id' => $id,
								'nom' => $nom,
								'couleur' => $couleur,
								'debut' => $debut,
								'duree' => $duree,
								'avancement' => $avancement,
								'meeting' =>$meeting,
								'hasChild' => $hasChild,
								);
								
				if(isset($task->task)){ //s'il existe une sous-tâche de cette tâche
					foreach($task->task as $subTask){
						GanttReaderParser::getProjectProperties($subTask, $gan, $defaultColor, $projects);
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
			
			foreach($task->depend as $dep){ //récupérer les contraintes (dépendances) avec les id	
						
				$constraints[]= array(	
										'from' => $indexes[$task->attributes()->id->__toString()],
										'to' => $indexes[$dep->attributes()->id->__toString()]
										);
			}
		}
		
		return $constraints;
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