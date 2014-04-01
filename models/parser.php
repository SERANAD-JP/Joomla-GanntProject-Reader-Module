<?php

defined('_JEXEC') or die('Restricted access');

/************************************************************************************
 * Utilitaire de récupération des informations contenues dans le fichier GanttProject
 * Récupération des éléments suivants :
 *	Projets contenus dans le fichier
 *	 --> Propriétés de ces projets
 *	Contraintes de suite entre différents projets
 *	Plages de congés
 ************************************************************************************/
class GanttReaderParser{
	
	/**
	 * Renvoie la liste des projets du fichier GanttProject
	 * @param (SimpleXMLElement) $gan l'instance du parseur (l'objet contient les informations qui étaient contenues dans le fichier GanttProject)
	 * @param (array) $vacances le tableau des plages de congés
	 * @param (String) $defaultColor la couleur par défaut des projets
	 * @params (timestamp) $earliest et $lastest les dates des dates au plus tôt et au plus tard de la vue 
	 * @return (array) le tableau des projets organisées selon clé => valeur
	 $ @see http://php.net/manual/fr/book.simplexml.php
	 */
	static function getProjects(&$gan, &$vacances, $defaultColor, $earliest, $lastest){

		$projets = NULL;	//valeur par défaut, protège des diagrammes vides
		
		if(isset($gan->tasks)){
			foreach($gan->tasks->task as $tache){
				GanttReaderParser::getProjectProperties($tache, $gan, $defaultColor, $projets, $vacances, $earliest, $lastest);
			}
		}
		
		return $projets;
	}
	
	/**
	 * @param (SimpleXMLElement) $task la tâche à insérer
	 * @param (SimpleXMLElement) $gan l'instance du parseur XML
	 * @param (String) $defaultColor, la couleur par défaut du projet
	 * @param (array) $projects le tableau des projets à compléter avec l'ajout du projet actuel
	 * How : Ajoute les données du projet courant au tableau des projets, puis fais de même récursivement avec chacun de ses projets fils s'il en a
	 */
	 static function getProjectProperties($tache, &$gan, $defaultColor,&$projets, &$vacances, $earliest, $lastest){
		 
		 		$id = $tache->attributes()->id->__toString();
				$nom = $tache->attributes()->name->__toString();

				$couleur = isset($tache->attributes()->color) ? /*si couleur non précisée, prendre celle par défaut*/
					$tache->attributes()->color->__toString() : 
					$defaultColor; 
					
				$debut = $tache->attributes()->start->__toString();
				$duree = $tache->attributes()->duration->__toString(); //durée selon GanttProject, ie le nombre de jours ouvrés
				$meeting = $tache->attributes()->meeting->__toString()==='true';

				$longueur = GanttReaderDate::projectLength($debut, $duree, $vacances); //taille (en jours) du projet

				$fin = strtotime('+'.($longueur).' days', strtotime($debut));
				
				
				

				/* On tronque les projets qui ne rentrent pas en entier */
				
				if(strtotime($debut)<$earliest && $fin>$earliest){ // Si commence trop tôt

					$longueur = GanttReaderDate::gap($fin, $earliest); //enlever le surplus
					
					$debut = date('Y-m-d', $earliest); //placer le commencement au début de la vue
					
				}
				
				if($fin>$lastest && strtotime($debut)<$lastest){//si finis trop tard
					$longueur = GanttReaderDate::gap(strtotime($debut), $lastest);
				}
				
				$avancement = $tache->attributes()->complete->__toString();
				$hasChild = isset($tache->task);
			
				$projets[] = array(
								'id' => $id,
								'nom' => $nom,
								'couleur' => $couleur,
								'debut' => $debut,
								'longueur' => $longueur,
								'avancement' => $avancement,
								'meeting' =>$meeting,
								'hasChild' => $hasChild,
								);
								
						
				if(isset($tache->task)){ //s'il existe une sous-tâche de cette tâche
					foreach($tache->task as $sousTache){
						GanttReaderParser::getProjectProperties($sousTache, $gan, $defaultColor, $projets, $vacances, $earliest, $lastest);
					}
				}
				
	 }
	
	/**
	 * @param (SimpleXMLElement) $gan l'instance du parseur du diagramme de gantt et la liste des $projects extraits
	 * @return le tableau des $constraints selon 
	 * 		['from']=> provenance de la contrainte
	 *		['to']=> projet cible de la contrainte
	 * How : Dans un tableau $indexes[] on associe l'id d'un projet avec son ordre d'apparition dans le parser
	 */
	static function getConstraints(&$gan, &$projets){
		
		$contraintes=NULL; //null par défaut, contre l'absence de contraintes
	
		if(!isset($projets)){ //protection VS absence de projets
			return NULL;
		}
		
		$i=0; //Index d'apparition
		
		foreach($projets as $projet){ //établir le lien id => index pour chaque projet
			$indexes[$projet['id']] = $i++;	
		}
		
		
		foreach($gan->tasks->task as $tache){
			
			GanttReaderParser::getConstraintProperties($tache, $gan, $projets, $contraintes, $indexes);
		}
		
		return $contraintes;
	}
	
	
	/**
	 * @params (SimpleXMLElement) $tache, $gan les instances du parseur du projet à étudier
	 */
	static function getConstraintProperties(&$tache, &$gan, &$projets, &$contraintes, &$indexes){
		if(isset($tache->task)){
				foreach($tache->task as $sousTache){
					GanttReaderParser::getConstraintProperties($sousTache, $gan, $projets, $contraintes, $indexes);
				}
			}
			
			foreach($tache->depend as $dep){ //récupérer les contraintes (dépendances) avec les id
			
				//seulement si la source et la cible de la contrainte s'affichent dans le diagramme
				if(isset($indexes[$tache->attributes()->id->__toString()], $indexes[$dep->attributes()->id->__toString()])) 
				$contraintes[]= array(	
										'from' => $indexes[$tache->attributes()->id->__toString()],
										'to' => $indexes[$dep->attributes()->id->__toString()]
										);
			}
	}
	
	
	/*
	 * Renvoie la liste des plages de congés
	 * @param (SimpleXMLElement) $gan l'instance du parseur
	 * @return (array) les plages de congés avec dates de début et de fin
	 */
	static function getVacations(&$gan){
		
		$vacances=NULL;//null par défaut, contre l'absence de congés
		
		if(isset($gan->vacations->vacation))
		
			foreach ($gan->vacations->vacation as $plage){
				$start = $plage->attributes()->start->__toString();
				$end = $plage->attributes()->end->__toString();
				$vacances[] = array(
									'start' => $start,
									'end' => $end
									);
			}
			
		return $vacances;
	}
}


?>