<?php

/***********************************************************************************************************************************
 * Modele de rendu
 * Recense les méthodes permettant d'obtenir un rendu visuel du diagramme
 * @return par défaut des méthodes, sauf mention contraire : $out le rendu visuel en HTML, prétraité pour le rendu dans le template
 ***********************************************************************************************************************************/
class GanttReaderDrawer{
	
	/*
	 * @params le $title du diagrame, les tableaux des $projetcs, des $vacations, des $consraints 
	 * 		et les dates limites du diagramme : $earliest et $lastest
	 * @return void, affiche le rendu HTML du diagramme de Gantt
	 */
	static function drawDiagram($title, &$projects, &$vacations, &$constraints, $earliest, $lastest){
		
	$out=('<div id="ganttDiagram">');
	
	$out.= GanttReaderDrawer::drawTitle($title);
	
	$out.= GanttReaderDrawer::drawHeader($vacations, $earliest, $lastest, $constraints);

	$out.= GanttReaderDrawer::drawSider($projects);
	
	$out.= GanttReaderDrawer::drawProjects($projects, $vacations, $earliest, $lastest, $constraints);
	
	$out.=('</div>');
	
	echo $out;
	}

	/*
	 * @params les tableaux des $projects,des $vacations et les dates en timestamps $timeA et $timeB entre lesquels il faut afficher le diagramme
	 * How : dans le conteneur ganttDays (dont les scrolls sont synchronisés avec d'autres éléments),
	 *		Pour chaque projet à afficher, dessiner sa ligne et garder son padding (taille projet = durée + padding)
	 */
	static function drawProjects(&$projects, &$vacations, $timeA, $timeB, &$constraints){
		
		$paddings = NULL; //Par défaut, pas de paddings : protège de l'absence de projets
		
		$out='<div id="ganttDays" onscroll="'.
				'document.getElementById(\'ganttSider\').scrollTop=this.scrollTop; '.
				'document.getElementById(\'ganttHeader\').scrollLeft=this.scrollLeft;"'.
				'>'.
				'<table>';

		//dessiner les projets eux-mêmes
		if(isset($projects)){
			foreach($projects as $project){
				$line = GanttReaderDrawer::drawLine($project, $vacations, $timeA, $timeB);
				$out.=$line['out'];
				$paddings[] = $line['padding']; //stocker les décalages pour ensuite dessiner les contraintes
			}
		}
		
		//ensuite dessiner les objets ([barre d'aujourd'hui et] contraintes) entre les projets
		$out.= GanttReaderDrawer::drawObjects($timeA, $constraints, $projects, $paddings);
		
		$out.='</table></div>';
		return $out;
	}
	
	/*
	 * @param le tableau des $projects
	 * dessine le bloc de gauche (titres des projets)
	 */
	static function drawSider(&$projects){
		$out='<div id="ganttSider"><table>';
		
		//d'abord écrire les titres + % avancement
		if(isset($projects)){
			foreach($projects as $project){
				$out.='<tr><td>'.$project['nom'].' ('.$project['avancement'].'%)</td></tr>';
			}
		}
		
		$out.='</table></div>';
		
		return $out;
	}
	
	static function drawTitle($title){
		$out= '<div id="ganttTitle" class="ganttEmbed">'.$title.'</div>';
		return $out;
	}
	
	
	/*
	 * @params $title le titre du diagramme, le tableau des $vacations,  $timeA et $timeB : les timestamps de début et de fin du diagramme
	 */
	static function drawHeader(&$vacations, $timeA, $timeB){
		$out='<div id="ganttHeader">';
		$out.='	<table>
				<tr>';
				
		$out.=GanttReaderDrawer::drawMonths($timeA, $timeB);
		
		$out.='	</tr>
				<tr>';
				
		$out.=GanttReaderDrawer::drawDays($timeA, $timeB, $vacations);
		
		$out.='</tr></table></div>';
		
		return $out;
	}
	
	/*
	 * @params les timestamps $timeA et $timeB les dates entre lesquelles on veut la liste des mois
	 */
	static function drawMonths($timeA, $timeB){
		
		$months = GanttReaderDate::listMonths($timeA, $timeB);
		$out='';
		
		foreach($months as $month){
			$out.='<td colspan="'.($month['length']).'" class="dayBox">'.$month['name'].'</td>';
		}
		
		return $out;
	}
	
	/*
	 * @params le timestamp de la premiere date du diagramme $earliest, le tableau des $constraints, des $projects et des $paddings
	 */
	static function drawObjects($earliest, $constraints, $projects, $paddings){
		
		$out='<time style="height:'.(count($projects)*36).'px;'.
		'left:'.((GanttReaderDate::gap($earliest, strtotime(date('Y-m-d', time())))*36)+35/2).'px;"></time>';
		
		for($i=0; $i<count($constraints); $i++){
			$out.=GanttReaderDrawer::drawConstraint($constraints[$i], $projects, $earliest, $paddings);
		}
		
		return $out;	
	}
	
	/*
	 * @params la $constraint à dessiner, les tableaux des $projects et des $paddings et la date au plus tôt du diagramme $earliest
	 * How : Calculer la taille du premier projet pour définir sa date de fin
	 * 		 Calculer les coordonnées de départ de la contrainte (xA, yA) et d'arrivée (xB, yB)
	 *		 abscisse = nombre de jours depuis la date au plus tôt * largeur des cases
	 *		 ordonnée = index d'apparition du projet * hauteur des cases + 2 cases (+0.5 case si se place à mi-hauteur d'une case)
	 *		 Chaque contrainte est ensuite modélisée dans un graphique SVG à partir de ses propriétés
	 * @see www.w3.org/Graphics/SVG/
	 */
	static function drawConstraint($constraint, $projects, $earliest, $paddings){
		
		$projA = $projects[$constraint['from']];
		$projB = $projects[$constraint['to']];
		
		$add = $projA['duree']+$paddings[$constraint['from']]; //durée réelle du projet
		
		$endA = strtotime($projA['debut'].'+'.$add.'days'); //fin du projet source : date de début + durée + décalage
		
		$startB = strtotime($projB['debut']);		
		
		$xA = GanttReaderDate::gap($earliest, $endA)*36;
		$yA = $constraint['from']*36+35*2.5;
		
		$xB = GanttReaderDate::gap($earliest, $startB)*36+35/2;
		$yB = $constraint['to']*36+35*2;
		

		
		if($endA > $startB){ //si départ et arrivée le même jour (meetings)
			$xA = $xB; 		//ne pas déplacer horizontalement
			
			/* On corrige les hauteurs de départ */
			if($constraint['from']>$constraint['to']){ //si viens d'en bas
				$yA = $yA -15;
				$yB = $yB -15;
			}elseif($constraint['to']>$constraint['from']){ //si viens d'en haut
				$yA = $yA +10;
				$yB = $yB + 10;
			}

		}
		
		if($constraint['from']>$constraint['to']){ //si la contrainte viens d'en bas (mais d'un jour différent)
			$yB = $yB + 31+10; //point d'arrivée par le bas
		}
		
		
		$out='	<?xml version="1.0" encoding="utf-8"?>
				<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 20010904//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">
					
				<svg
					class="ganttObject" 
					style="
						min-width:'.($xB+10).'px;
						height:'.(max(array($yA, $yB))+10).'px;
						left:0; 
						top:0;"
					xml:lang="fr" 
					xmlns="http://www.w3.org/2000/svg">
				
				<defs>
				
					<!-- Marqueur de la fin d\'un tracé : les contraintes pointent avec une flèche à leur extremité -->
					<marker id="fleche" markerWidth="20" markerHeight="15" refX="2.5" refY="7.5" markerUnits="userSpaceOnUse" orient="auto">
						<!-- flèche -->
						<path
       						d="M 7.5,7.5 0,0 0,15 z"
       						style="fill:white; fill-rule:evenodd; stroke:white; 
							stroke-width:1px; stroke-linecap:round; stroke-linejoin:round; stroke-opacity:1" />
					</marker>
					
				</defs>
								
				<!-- Le tracé de la contrainte elle-même -->
				<path 
					class="ganttObject" 
					d="
					M '.$xA.','.$yA.' 
					H '.$xB.'
					V'.$yB.'" 
					style="marker-end:url(#fleche)" />
			</svg>';
		
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
	 *		on récupère le rendu du projet et la taille du surplus (décalage à droite = padding),
	 * 		on dessine le projet puis les cases vides qui suivent le projet (de fin du projet + décalage à fin de la fenêtre)
	 */
	static function drawLine($project, $vacations, $timeA, $timeB){
		$before = strtotime('-1 day', strtotime($project['debut'])); //1 jour avant le projet
		
		$out='<tr>';
		//$out.='<td class="ganttSider">'.$project['nom'].' ('.$project['avancement'].'%)</td>';
		$out.= GanttReaderDrawer::drawPadding($timeA, $before, $vacations); //bourrage avant
		
		$line = GanttReaderDrawer::drawProject($project, $vacations); //array (rendu du projet + décalage)
		$out.= $line['out']; //rendu du projet
		
		$after = strtotime('+'.($project['duree']+$line['padding']+1).' days', $before); //le décalage +1 jour après le projet
		
		$out.= GanttReaderDrawer::drawPadding($after, $timeB, $vacations);//bourrage après
		$out.='</tr>';
		
		return array('out' => $out, 'padding' => $line['padding']);
	}
	
	/*
	 * @params le $project et le tableau des $vacations
	 * @return array(['out'] => le rendu du projet lui-même, ['padding'] => le décalage à droite créé par les jours de congé)
	 * @see www.w3.org/Graphics/SVG/
	 */
	static function drawProject($project, $vacations){
		$out='';
		$current = strtotime($project['debut']);
		$actuel = 0; //compteur de l'avancement total du dessin, décalages compris
		$i=0; //compteur de l'avancement du projet
		$padding=0; //le décalage à renvoyer
		$duree = $project['duree'];
		
		if($project['meeting']){
			$out.='<td class="daybox';
			if (GanttReaderDate::inRest($current, $vacations)){
				$out.= ' dayOff">';
			}
			$out.='">';
			$padding++; //correction du fait que les meetings durent 0 jours selon GanttProject
			
			/*Dessin vectoriel SVG d'une étoile*/
			
			$out.='	<?xml version="1.0" encoding="UTF-8" standalone="no"?>
					<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 20010904//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">
					<svg 
						xmlns:svg="http://www.w3.org/2000/svg"
   						xmlns="http://www.w3.org/2000/svg"
   						version="1.1"
   						width="35px"
   						height="30px">
							
    						<path
       							d="m
								16.518376,1.0831728 
								3.788202,7.6757353 
								8.470677,1.2308622 
								-6.129439,5.9747267 
								1.446963,8.43645 
								-7.576404,-3.983151 
								-7.576404,3.983151 
								1.446964,-8.43645 
								
								L 
								4.2594961,9.9897697 
								12.730173,8.7589082 
								
								z"
						
       						style="
								fill:'.$project['couleur'].';
								fill-rule:evenodd; 
								stroke:#000000; 
								stroke-width:1px; 
								stroke-linecap:butt; 
								stroke-linejoin:round; 
								stroke-opacity:1" />
  					
					</svg>';
			
			$out.='</td>';
			
		}
		
		elseif($duree==1){ //si ne dure qu'un jour
		$out.='<td class="dayBox';
		if(GanttReaderDate::inRest($current, $vacations)){
				  $out.=' dayOff';
			  };
		
			  
		$out.='"><div style="background-color:'.$project['couleur'].'" class="ganttProjectEnd ganttProjectStart';
		if(GanttReaderDate::completed($project, 0, $vacations)){
			$out.=' complete';
		}
		$out.='"></div></td>';
		}
		
		 else{
		
		/*Dessin du premier jour*/
		$out.='<td class="dayBox';
			  
			  if(GanttReaderDate::inRest($current, $vacations)){
				  $out.=' dayOff';
				  $padding++;
			  };
			  
			   $out.='"><div style="background-color:'.$project['couleur'].';" class="ganttProjectStart';
			   if(GanttReaderDate::completed($project, $actuel, $vacations)){
				$out.=' complete';
			}
			   $out.='">';
			   $out.='';
			   $out.='</div></td>';
			   $current = strtotime('+1 day', $current);
			   $i++;
			   $actuel++;
			   
			   
		/*Jours du centre*/
		while($i<$duree-1 || GanttReaderDate::inRest($current, $vacations) ){
		$out.='<td class="dayBox ';
		if(GanttReaderDate::inRest($current, $vacations)){
				  $out.=' dayOff';
			  }
		$out.='">';
		$out.='<div style="background-color:'.$project['couleur'].'" class="ganttProject';
		if(GanttReaderDate::completed($project, $actuel, $vacations)){
				$out.=' complete';
		}
		$out.='">';
		$out.='</div>';
		$out.='</td>';
		if( GanttReaderDate::inRest($current, $vacations)){	$padding++; $i--;}
		$current = strtotime('+1 day', $current);
		$i++;
		$actuel++;
		}
		
		/*Dessin du dernier jour*/
		$out.='<td class="dayBox';
			  
		if(GanttReaderDate::inRest($current, $vacations)){
			$out.=' dayOff';
			$padding++;
		}
			  
		$out.='"><div style="background-color:'.$project['couleur'].';" class="ganttProjectEnd';
		if(GanttReaderDate::completed($project, $actuel, $vacations)){
			$out.=' complete';
		}
		$out.='"></div></td>';
		$current = strtotime('+1 day', $current);
			   
		 }
		return array(
					'out' => $out,
					'padding' => $padding
					);
	}
}

?>