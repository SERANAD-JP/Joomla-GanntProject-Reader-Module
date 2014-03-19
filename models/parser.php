
<?php

/*
 * Utilitaire de récupération des informations contenues dans le fichier GanttProject
*/
class GanttReaderParser{
	/*
	 * @param SimpleXMLElement $gan l'instance du parseur du diagramme de gantt à traiter
	 * @return array() le tableau des projets organisées selon clé => valeur
	 * chaque projet se présente en tableau associatif contenant chacune des informations extraites
	 */
	static function getProjects(&$gan, $defaultColor="#a9890a"){
		$projects = NULL;	//valeur par défaut, évite les diagrammes vides
		$index=0;
		foreach($gan->tasks->task as $task){
			$id = $task->attributes()->id->__toString();
			$nom = $task->attributes()->name->__toString();

			$couleur = isset($task->attributes()->color) ? /*si couleur non précisée, prendre celle par défaut*/
				$task->attributes()->color->__toString() : 
				$defaultColor; 
				
			$debut = $task->attributes()->start->__toString();
			$meeting = $task->attributes()->meeting->__toString();
			$meeting = $meeting==='true';
			$duree = $task->attributes()->duration->__toString();
			$avancement = $task->attributes()->complete->__toString();
			$notes = $task->notes->__toString();
			
			$projects[] = array(
							'id' => $id,
							'nom' => $nom,
							'couleur' => $couleur,
							'debut' => $debut,
							'duree' => $duree,
							'avancement' => $avancement,
							'meeting' =>$meeting,
							'notes' => $notes
							);
			$index++;
		}

		
		return $projects;
	}
	
	/*
	 * @param SimpleXMLElement $gan l'instance du parseur du diagramme de gantt et la liste des projets extraits
	 * @return void, insère le tableau des contraintes inter-tâches dans les propriétés des projets concernés
	 */
	static function getConstraints(&$gan, &$projects){
	
		if(!isset($projects)){ //protection VS absence de projets
			return NULL;
		}
		//numérotation
		$i=0;
		foreach($projects as $project){ //établir le lien id => index pour chaque projet
			$indexes[$project['id']] = $i++;	
		}
		
		$constraints=NULL; //null par défaut, contre l'absence de contraintes
		
		foreach($gan->tasks->task as $task){
			
			foreach($task->depend as $dep){ //récupérer les contraintes avec les id				
				$constraints[]= array(	
										'from' => $indexes[$task->attributes()->id->__toString()],
										'to' => $indexes[$dep->attributes()->id->__toString()]
										);
			}
		}
		return $constraints;
		
	}
	
	
	/*
	 * @param SimpleXMLElement $gan l'instance du parseur du diagramme de gantt à traiter
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