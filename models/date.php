<?php

defined('_JEXEC') or die('Restricted access');

/********************************************************************************
 * Modèle de gestion des dates
 * Fournis les méthodes relatives au calendrier, aux dates et au calcul de durée
 ********************************************************************************/
 
class GanttReaderDate{

	/**
	 * @params (int) $range le nombre de mois à afficher avant le mois du jour $current (par défaut, aujourd'hui)
	 * @return (timestamp) la date du premier jour du mois d'il y a $range mois
	 */
	static function earliestMonth($range, $current=NULL){
		
		if($current==NULL){
			$current = time(); //par défaut : aujourd'hui
		}
		
		$year = date('Y', $current);
		$month = date('m', $current);
		
		$month = $month-$range;
		
		if($month<0){
			$month = 12+$month;
			$year--;
		}
		
		return strtotime($year.'-'.$month.'-01');
	}
	
	/**
	 * @params (int) $range le nombre de mois à afficher avant le mois du jour $current (par défaut, aujourd'hui)
	 * @return (timestamp) la date du dernier jour du mois dans $range mois
	 */
	static function lastestMonth($range, $current=NULL){
		
		if($current==NULL){
			$current = time(); //par défaut : aujourd'hui
		}
		
		$year = date('Y', $current);
		$month = date('m', $current);
		
		$month = $month+$range;
		
		if($month>12){
			$month = $month-12;
			$year++;
		}
		
		$firstDay = strtotime($year.'-'.$month.'-01');
		$lastDay = date('Y-m-t', $firstDay);
		
		return strtotime($lastDay);	
	}
	
	
	/**
	 * @param (timestamp) la date du jour à étudier
	 * @return (boolean) true si le jour concerné est un week end, faux sinon
	 */
	static function isWeekEnd($timestamp){
		$day = date('N', $timestamp);
		return ($day>5); //true si le 5ème jour de la semaine est déjà passé
	}
	
	/**
	 * @param (timestamp) $jour la date du jour a étudier
	 * @param (array) $vacations le tableau des plages de congés
	 * @return true s'il s'agit d'un jour en congé, faux sinon
	 * How : parcours partiel, retour direct avec true si une plage de congé correspondante est balayée
	 * 		 Si toutes les plages ont été balayées et qu'aucune n'intègre le jour donné, alors renvoyer false
	 */
	static function isVacation($jour, &$vacations){
		if(isset($vacations)){
			foreach($vacations as $vacation){
				$start = strtotime($vacation['start']);
				$end = strtotime($vacation['end']);
				
				if(GanttReaderDate::inSight($jour, $start, $end)){ //si la date est dans une période de congé
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * @params (timestamp) $jour la date du jour à étudier
	 * @return (boolean) true si le jour est vaqué ( càd non travaillé), faux sinon
	 */
	static function inRest($jour, &$vacations){
		return (GanttReaderDate::isVacation($jour, $vacations) || GanttReaderDate::isWeekEnd($jour));	
	}
	
	
	/**
	 * @params (timestamp) $dateA et $dateB  les dates entre lesquelles il faut mesurer l'écart
	 * @return (int) le nombre de jours entre les deux dates
	 */
	static function gap($dateA, $dateB){
		$start = new DateTime(date('Y-m-d', $dateA));
		$end = new DateTime(date('Y-m-d', $dateB)); 
		$diff = $start->diff($end);
		return $diff->days;
	}
	
	/**
	 * @params (timestamp) $jour la date du jour à étudier, $earliest et $lastest les dates des premier et dernier jours de la vue
	 * @return true si le jour donné est compris entre $earliest et $lastest, faux sinon
	 */
	static function inSight($jour, $earliest, $lastest){
		return ($jour>=$earliest && $jour<=$lastest);
	}
	
	/**
	 * @param (array) $project le projet à étudier
	 * @param (int) $case le numéro de la $case à traiter (0 est la première case du projet, $project['longueur']-1 en est la dernière)
	 * @return (boolean) true si la case doit adopter le style "complétée", false sinon
	 */
	 static function completed($project, $case){
		 
		$duree = $project['longueur'];
		
		if($duree==0){
			return $project['avancement']==100; //Les projets d'un seul jour ne sont remplis qu'à 100%, pas avant
		}
		
		$rapport = round((($case+1)/$duree)*100); //rapport entre l'avancement relatif de la case sur l'avancement réel

		return ($rapport<=($project['avancement'])); // true si l'avancement "recouvre" la case actuelle 
	 }
	
	
	/**
	 * @params (timestamp) $earliest et $lastest, les  dates des mois entre lesquels il faut donner les noms
	 * @return array les noms des mois situés entre $earliest et $lastest (inclus) ainsi que leur durée en jours
	 * ex. ['name'] => 'January'; ['length'] => 31 ... ['name'] => 'February'; ['length'] => 28 etc.
	 * NB : les mois sont traduits dans la langue de l'utilisateur Joomla
	 * @see http://docs.joomla.org/JText/_()
	 * How : parcours total
	 */
	static function listMonths($earliest, $lastest){
		
		$current = $earliest; //pointer vers le premier mois
		
		do{
			$name = JText::_(strtoupper(date('F', $current))); //mois en majuscules, traduit dans la langue de l'utilisateur via l'API Joomla
			$length = date('t', $current);
			$months[]= array(
							'name' => $name, 
							'length' => $length
							);
			
			$current = GanttReaderDate::lastestMonth(1, $current); //sauter au mois suivant
			
		} while($current<=$lastest);
		
		return $months;
		
	}
	
	/**
	 * @params (timestamp) $earliest et $lastest, les  dates des jours entre lesquels il faut donner les numéros
	 * @param (array) $vacations le tableau des plages de congés
	 * @return array les numéros des jours situés entre $earliest et $lastest (inclus) selon 
	 *		['jour'] => (int) numero
	 *		['vacation'] => (boolean) estEnVacance
	 *		['today'] => (boolean) estAujourd'hui
	 * How : Parcours total
	 */
	static function listDays($earliest, $lastest, &$vacations){
		
		$current = $earliest; //pointer sur le premier jour
		
		do{
			$days[] = array(
							'jour' => date('d', $current),
							'vacation' => GanttReaderDate::inRest($current, $vacations),
							'today' => (strtotime(date('Y-m-d', time()))==$current) //vrai si la date == aujourd'hui, faux sinon
							);
							
			$current = strtotime('+1 day', $current);//passer au jour suivant
			
		} while($current<=$lastest);
		
		return $days;
	}
	
	/**
	 * @param (int) $range le nombre de mois à afficher de part et d'autre du mois courant,
	 * @param (array) $project le projet à traiter
	 * @return (boolean) true si le projet est censé être affiché dans le rendu (i.e au moins une partie se trouve dans la fenêtre), false sinon
	 */
	 static function inWindow($range, $project){
		 
		$earliest = GanttReaderDate::earliestMonth($range);
		$lastest = GanttReaderDate::lastestMonth($range);
		
		
			$start = strtotime($project['debut']);	// date de début du projet
			$end = strtotime('+'.$project['longueur'].' days', $start); // date de fin du projet
			
			return(
				GanttReaderDate::inSight($start, $earliest, $lastest)||	//Si début du projet inclus ...
				GanttReaderDate::inSight($end, $earliest, $lastest)|| 	//ou si fin incluse ...
				($start==$earliest && $end==$lastest)||					//ou si fait exactement le même taills
				GanttReaderDate::inSight($earliest, $start, $end));		//ou si le projet recouvre la vue entière : renvoyer vrai
	}
	
	
	/**
	 * @param (array) $projects le tableau des projets originel,
	 * @param (int) $range le nombre de mois à afficher de part et d'autre du mois courant
	 * @return (array) le tableau des projets, filtrés (i.e qui devront apparaitre dans le rendu)
	 */
	static function filterProjects(&$projects, $range){	
	  	$out=null;
		if(isset($projects)){
			foreach($projects as &$project){	
				if(GanttReaderDate::inWindow($range, $project)){
					$out[]=$project;
				}//else echo($project['nom'].' a été supprimé <br />');
			}
		}
		
		return $out;
	}
	
	/**
	 * @param (array) $project le projet à étudier
	 * @param (array) $vacations le tableau des plages de congés
	 * @return (int) la taille finale du projet, càd le nombre de jours occupés par le projet donné
	 * How : parcourir le projet depuis la date de début et ce, sur la durée du projet.
	 * 		(NB : GanttProject ne compte que les jours ouvrés)
	 * 		On compte donc les jours vaqués puis on additionne ouvrés et vaqués
	 */
	 static function projectLength($debut, $duree, $vacations){
		 
		 if($duree==0){ // Un meeting ou un projet ne dure rien selon GanttProject, corriger en renvoyant 1
			return 1;
		}
		 
		$current = strtotime($debut); //placer un repère sur le jour de début
		
		$conges = 0; //compter le nombre de jours vaqués
		
		$ouvres = $duree; //nombre de jours ouvrés à parcourir
		
		
		
		 
		 
		 do{
			 			 
			 $current = strtotime('+1 day', $current);
			 
			 if(!GanttReaderDate::inRest($current, $vacations)){
				$ouvres--; 
			 } else{
				$conges++; 
			 }
			 
		 }while($ouvres>1);
		 

		 return ($duree+$conges)-1;
	 }
}


?>