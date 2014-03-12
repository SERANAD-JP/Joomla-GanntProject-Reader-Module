<?php

/*
 * Modele de rendu
 * Recense les méthodes permettant d'obtenir un rendu visuel du diagramme
 * @return par défaut des méthodes, sauf mention contraire : $out le rendu visuel en HTML, prétraité pour le rendu dans le template
 */
class GanttReaderDrawer{

	/*
	 * @params la liste des $projects, la liste des $vacations et les dates en timestamps $timeA et $timeB entre lesquels il faut afficher le diagramme
	 */
	static function drawProjects($projects, $vacations, $timeA, $timeB){
		$out='<table>';
		foreach($projects as $project){
			$out.= GanttReaderDrawer::drawLine($project, $vacations, $timeA, $timeB);
		}
		$out.='</table>';
		return $out;
	}
	
	
	/*
	 * @params $title le titre du diagramme, le tableau des $vacations,  $timeA et $timeB : les timestamps de début et de fin du diagramme
	 */
	static function drawHeader($title, &$vacations, $timeA, $timeB){

		$out='<table>';
		//$out.='<date>&nbsp;</date>';
		$out.='<tr>';
		$out.='<td class="ganttEmbed ganttSider" rowspan="2">'.$title.'</td>';
		$out.=GanttReaderDrawer::drawMonths($timeA, $timeB);
		$out.='</tr><tr>';
		$out.=GanttReaderDrawer::drawDays($timeA, $timeB, $vacations);
		$out.='</tr></table>';
		return $out;
	}
	
	/*
	 * @params les timestamps $timeA et $timeB les dates entre lesquelles on veut la liste des mois
	 */
	static function drawMonths($timeA, $timeB){
		$months = GanttReaderDate::listMonths($timeA, $timeB);
		$out='';
		foreach($months as $month){
			
			$out.='<td colspan="'.($month['length']).'">'.$month['name'].'('.$month['length'].')</td>';
			
		}
		return $out;
	}
	
	/*
	 * @params les timestamps $timeA et $timeB les dates entre lesquelles on veut la liste des jours (numériques)
	 * ex: 01, 02, 03 ... 28, 29, 30, 01, 02, 03, ...
	 */
	static function drawDays($timeA, $timeB, &$vacations){
		$out='';
		$days = GanttReaderDate::listDays($timeA, $timeB, $vacations);
		foreach($days as $day){
			
				
			$out.='<td class="dayBox';
			if($day['vacation']){
				$out.=' dayOff';
			}
			if($day['today']){
				$out.=' ganttEmbed';	
			}
			$out.='">'.$day['jour'];
			
			$out.='</td>';
			
		}
		return $out;
	}
	
	
	
	/*
	 * @params les dates timstamps $timeA et $timeB entre lesquelles on veut placer des jours vides et le tableau des $vacations
	 * Action : dessine des jours vides (style spécial pour les jours vaqués) d'une date à une autre
	 */
	static function drawPadding($timeA, $timeB, &$vacations){
		$out='';
		$days = GanttReaderDate::listDays($timeA, $timeB, $vacations);
		foreach($days as $day){
			$out.='<td class="dayBox';
			if($day['vacation']){
				$out.=', dayOff';	
			}
			$out.='"></td>';
			
		}
		return $out;
	}
	
	/*
	 * @params le $project à dessiner, le tableau des $vacations et les dates timestamps $timeA et $timeB de début et de fin du diagramme
	 * How : on dessine les cases vides avant le projet, 
	 *		on récupère le rendu du projet et la taille du surplus (décalage à droite),
	 * 		on dessine le projet puis les cases vides qui suivent le projet (de fin du projet + décalage à fin de la fenêtre)
	 */
	static function drawLine($project, $vacations, $timeA, $timeB){
		$before = strtotime('-1 day', strtotime($project['debut'])); //1 jour avant le projet
		
		$out='<tr>';
		$out.='<td class="ganttSider">'.$project['nom'].' ('.$project['avancement'].'/'.$project['duree'].')</td>';
		$out.= GanttReaderDrawer::drawPadding($timeA, $before, $vacations); //bourrage avant 
		$line = GanttReaderDrawer::drawProject($project, $vacations); //array (rendu du projet + décalage)
		$out.= $line['out']; //rendu du projet
		$after = strtotime('+'.($project['duree']+$line['padding']+1).' days', $before); //le décalage +1 jour après le projet
		$out.= GanttReaderDrawer::drawPadding($after, $timeB, $vacations);//bourrage après
		$out.='</tr>';
		
		return $out;
	}
	
	/*
	 * @params le $project et le tableau des $vacations
	 * @return array(['out'] => le rendu du projet lui-même, ['padding'] => le décalage à droite créé par les jours de congé)
	 */
	static function drawProject($project, $vacations){
		$out='';
		$current = strtotime($project['debut']);
		$i=0; //compteur de l'avancement du projet
		$padding=0; //le décalage à renvoyer
		while($i<$project['duree']){
			
			$out.='<td class="dayBox';
			
			if(GanttReaderDate::inRest($current, $vacations)){ // si un décalage a lieu
				$out.=', dayOff';
				$padding++;
				$i--;
			}
			$out.='">';
			if($i==0){
				$out.='<div style="background-color:'.$project['couleur'].';" class=ganttProjectStart>&nbsp;</div>';
			} elseif($i==$project['duree']-1){
				$out.='<div style="background-color:'.$project['couleur'].';" class=ganttProjectEnd>&nbsp;</div>';
			}
			else{
				$out.='<div style="background-color:'.$project['couleur'].';" class=ganttProject>&nbsp;</div>';	
			}
			
			
			
			$out.='</td>';
			$current = strtotime('+1 day', $current);
			
			$i++;
		}
		return array(
					'out' => $out,
					'padding' => $padding
					);
	}
	
}

?>