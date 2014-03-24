<?php
/*
 * Modèle de gestion des dates
 * Fournis les méthodes relative au calendrier et aux dates en général
 */
class GanttReaderDate{

	/*
	 * @param $range la taille de la fenêtre (en mois) avant la date $current (par défaut, aujourd'hui)
	 * @return timestamp la date du premier jour d'il y a $range mois
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
	
	/*
	 * @param $range la taille de la fenêtre (en mois) après la date $current (par défaut, aujourd'hui)
	 * @return timestamp la date du dernier jour du mois dans $range mois
	 */
	static function lastestMonth($range, $current=NULL){
		if($current==NULL){
			$current = time();	
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
	
	
	/*
	 * @param timestamp du jour a étudier
	 * @return true si le jour concerné est un WE, faux sinon
	 */
	static function isWeekEnd($timestamp){
		$day = date('N', $timestamp);
		return ($day>5); //true si le 5ème jour de la semaine est déjà passé
	}
	
	/*
	 * @params $time le timestamp du jour a étudier, $vacations le tableau des congés
	 * @return true s'il s'agit d'un jour en congé, faux sinon
	 */
	static function isVacation($time, &$vacations){
		if(isset($vacations))
		foreach($vacations as $vacation){
			$start = strtotime($vacation['start']);
			$end = strtotime($vacation['end']);
			
			if(GanttReaderDate::inSight($time, $start, $end)){ //si la date est dans une période de congé
				return true;
			}
		}
		return false;
	}
	
	/*
	 * @params $timestamp le timestamp du jour à étudier
	 * @return true si le jour est vaqué (non travaillé), faux sinon 
	 */
	static function inRest($timestamp, &$vacations){
		return (GanttReaderDate::isVacation($timestamp, $vacations) || GanttReaderDate::isWeekEnd($timestamp));	
	}
	
	static function gap($dateA, $dateB){
		$start = new DateTime(date('Y-m-d', $dateA));
		$end = new DateTime(date('Y-m-d', $dateB)); 
		$diff = $start->diff($end);
		return $diff->days;
	}
	
	/*
	 * @param le $timestamp du jour a étudier, la $dateA du premier jour de la fenêtre , la $dateB du dernier jour de la fenêtre
	 * les dates sont en timestamps
	 * @return true si le $timestamp est compris entre $dateA et $dateB, faux sinon
	 */
	static function inSight($timestamp, $dateA, $dateB){
		return ($timestamp>$dateA && $timestamp<$dateB);
	}
	
	/*
	 * @params le $project, le numero de la $case à traiter (de 0 à durée projet) et la liste des $vacations
	 */
	 static function completed($project, $case, &$vacations){
		 $duree = GanttReaderDate::projectLength($project, $vacations);//5
		$rapport = round((($case+1)/$duree)*100);
		//echo($project['nom'].' Durée : '.$duree.' NumCase : '.$case.' Rapport : '.$rapport.' Avancement : '.$project['avancement']);
		//if($rapport<=($project['avancement'])) echo(' marqué !');
		//echo('<br />');

		 return ($rapport<=($project['avancement']));
		 //return $actuel<$project['avancement'];
		 
	 }
	
	
	/*
	 * @param $timeA et $timeB, les  timestamps des mois entre lesquels il faut donner les noms
	  *@return array les noms des mois situés entre timeA et timeB (inclus) ainsi que leur durée en jours
	  * ex. Jan => 31, Feb => 28, Mar => 31 etc...
	  * @see Joomla API::JText
	 */
	static function listMonths($timeA, $timeB){
		
		$current = $timeA; //pointer vers le premier mois
		
		do{
			$name = JText::_(strtoupper(date('F', $current))); //mois en majuscules, traduit dans la langue de l'utilisateur
			$length = date('t', $current);
			$months[]= array(
							'name' => $name, 
							'length' => $length
							);
			
			$current = GanttReaderDate::lastestMonth(1, $current); //sauter au mois suivant
			
		} while($current<=$timeB);
		
		return $months;
		
	}
	
	/*
	 * @param $timeA et $timeB, les  timestamps des jours entre lesquels il faut donner les numeros et $vacations le tableau des congés
	  *@return array les numéros des jours situés entre timeA et timeB (inclus) selon numéro => estEnVacances
	 */
	static function listDays($timeA, $timeB, $vacations){
		$current = $timeA;
		
		do{
			$days[] = array(
							'jour' => date('d', $current),
							'vacation' => GanttReaderDate::inRest($current, $vacations),
							'today' => (strtotime(date('Y-m-d', time()))==$current) //vrai si la date == aujourd'hui, faux sinon
							);
							

							
			$current = strtotime('+1 day', $current);//passer au jour suivant
		} while($current<=$timeB);
		return $days;
	}
	
	/*
	 * @param $range la taille originelle de la fenêtre, $project le projet
	 * @return true si le projet est censé être affiché dans le rendu, false sinon (i.e au moins une partie se trouve dans la fenêtre)
	 */
	 static function inWindow($range, $project){
		$earliest = GanttReaderDate::earliestMonth($range);
		$lastest = GanttReaderDate::lastestMonth($range);
		
		
			$start = strtotime($project['debut']);	//début puis fin de la fenêtre originelle
			$end = strtotime('+'.$project['duree'].' days', $start); //fin = début + durée
			
			return(
				GanttReaderDate::inSight($start, $earliest, $lastest)||	//Si début inclus
				GanttReaderDate::inSight($end, $earliest, $lastest)|| 	//ou si fin incluse
				GanttReaderDate::inSight($earliest, $start, $end));		//ou si le projet recouvre la fenêtre			
	}
	
	/*
	 * @param la taille originelle de la fenêtre, le tableau des projets
	 * @return array() ['min'], ['max'] le début et la fin que devrait avoir la fenêtre
	 * Pré-requis : avoir filtré les projets qui sont complètement hors champ au préalable.
	 */
	 static function windowRange($range, $projects){
		$earliest = GanttReaderDate::earliestMonth($range); //limites d'origine de la fenêtre
		$lastest = GanttReaderDate::lastestMonth($range);
		
		$min = $earliest; //nouvelles limites, à adapter
		$max = $lastest;
		if(isset($projects)){
		foreach($projects as $project){
			$start = strtotime($project['debut']);	//début et fin de la frontière originelle
			$end = strtotime('+'.$project['duree'].' days', $start);
			

			if(GanttReaderDate::inSight($lastest, $start, $end)){//si plus tard que la fenêtre
				$max = $end;
			}
			if(GanttReaderDate::inSight($earliest, $start, $end)){//si plus tôt que la fenêtre
				$min = $start;
			}
		}
		}
		$min = GanttReaderDate::earliestMonth(0, $min); //on élargit au début et à la fin des mois pour avoir des mois complets
		$max = GanttReaderDate::lastestMonth(0, $max);
		
		return array(
					'min' => $min,
					'max' => $max
					);
	}
	
	/*
	 * @params l'array des projets source, $range le nombre de mois autour de la date courante à conserver
	 * @return l'array des projets, filtrés (i.e qui devront apparaitre dans le rendu)
	 */
	static function filterProjects($projects, $range){	
	
	$out = NULL;//(protège de l'absence de projets dans la fenêtre)
	  	
		  if(isset($projects)){
			foreach($projects as &$project){
				
				if(GanttReaderDate::inWindow($range, $project)){
					$out[]=$project;	
				}
			}
		}
		return $out;
	}
	
	/*
	 * retourne la taille finale du projet : durée nominale + décalage des congés
	 */
	 static function projectLength($project, $vacations){
		 //$debug = $project['nom']==='Modification Salaires';
		 
		
		 $duree = 1; //compteur de l'avancement total
		 $i=0; //compteur de l'avancement nominal
		 $current = strtotime($project['debut']);
		 while($i<$project['duree']-1){
			 //if($debug) echo('duree: '.$duree.' date : '.date('d m Y', $current).'<br />');
			 
			 $i++;
			 $current = strtotime('+1 day', $current);
			 $duree++;
			 if(GanttReaderDate::inRest($current, $vacations)){
				$i--; 
			 }
		 }
		 return $duree;
	 }
}


?>