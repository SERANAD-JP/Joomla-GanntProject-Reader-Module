<?php

defined('_JEXEC') or die('Restricted access');

/***********************************************************************************************************************************
 * Modèle de rendu
 * Recense les méthodes permettant d'obtenir un rendu visuel du diagramme
 * @return par défaut des méthodes, sauf mention contraire : $out le rendu visuel en HTML, prétraité pour le rendu dans le template
 * Noe : l'objet timestamp fait référence à un int formaté pour représenter un timestamp
 ***********************************************************************************************************************************/
class GanttReaderDrawer{
	
	/**
	 * @param (String) $titre le titre du diagramme
	 * @params (array) $projets, $vacances, $contraintes les listes des projets, des plages de congés et des contraintes
	 * @params (timestamp) les dates limites du diagramme : $earliest et $lastest
	 * @return void, affiche le rendu HTML du diagramme de Gantt
	 */
	static function drawDiagram($titre, &$projets, &$vacances, &$contraintes, $earliest, $lastest){
		
	$out=('<div id="ganttDiagram">');
	
	$out.= GanttReaderDrawer::drawTitle($titre);
	
	$out.= GanttReaderDrawer::drawHeader($vacances, $earliest, $lastest);
	
	$out.= GanttReaderDrawer::drawSider($projets);

	$out.= GanttReaderDrawer::drawProjects($projets, $vacances, $contraintes, $earliest, $lastest);
	
	$out.=('</div>');
		
	echo $out;
	
	}

	/**
	 * Dessine les projets et leur conteneur
	 * @params (array) $projets, $vacances, $contraintes les listes des projets, des plages de congés et des contraintes de suivi
	 * @params (timestamp) $earliest et $lastest les dates au plus tôt et au plus tard de la vue
	 * How : dans le conteneur ganttDays (dont les scrolls sont synchronisés avec d'autres éléments),
	 *		pour chaque projet à afficher, dessiner sa ligne (bourrage à gauche, projet, bourrage à droite)
	 */
	static function drawProjects(&$projets, &$vacances, &$contraintes, $earliest, $lastest){
		
		$paddings = NULL; //Par défaut, pas de paddings : protège de l'absence de projets
		
		$out='<div id="ganttDays" onscroll="'.
		
				'document.getElementById(\'ganttSider\').scrollTop=this.scrollTop; '.
				
				'document.getElementById(\'ganttHeader\').scrollLeft=this.scrollLeft;"'.
				
				'><table>';

		//dessiner les projets eux-mêmes
		if(isset($projets)){
			
			foreach($projets as $projet){
				
				$out.= GanttReaderDrawer::drawLine($projet, $vacances, $earliest, $lastest);

			}
			
		}
		
		//ensuite dessiner les objets (barre d'aujourd'hui et contraintes) entre les projets
		$out.= GanttReaderDrawer::drawObjects($earliest, $contraintes, $projets);
		
		$out.='</table></div>';
		
		//nombre de pixels bord gauche --> barre du jour
		$scroll = (round((GanttReaderDate::gap($earliest, strtotime(date('Y-m-d', time())))*36)-232)); 
		
		$out.='<script type="text/javascript">'.
		
			'document.getElementById(\'ganttDays\').scrollLeft = '.$scroll.';'.
			
        	'</script>';

		return $out;
	}
	
	/**
	 * Dessine le bloc de gauche (titres des projets et avancement respectif)
	 * @param (array) la liste des projets
	 */
	static function drawSider(&$projets){
		
		$out='<div id="ganttSider"><table>';
		
		if(isset($projets)){
			
			foreach($projets as $projet){
				
				$out.='<tr><td>'.$projet['nom'].' ('.$projet['avancement'].'%)</td></tr>';
				
			}
			
		}
		
		$out.='</table></div>';
		
		return $out;
	}
	
	/**
	 * Dessine le titre du diagramme
	 * @param (String) $titre le titre du diagramme
	 */
	static function drawTitle($titre){
		$out= '<div id="ganttTitle" class="ganttEmbed">'.$titre.'</div>';
		return $out;
	}
	
	
	/**
	 * Dessine le bloc du haut (Liste des mois et de leur jours)
	 * @param(array) $vacances la liste des plages de congés,
	 * @params $earliest et $lastest : les dates de début et de fin du diagramme
	 */
	static function drawHeader(&$vacances, $earliest, $lastest){
		$out='<div id="ganttHeader">'.
			 '<table>'.
			 '<tr>';
				
		$out.=GanttReaderDrawer::drawMonths($earliest, $lastest);
		
		$out.='	</tr>
				<tr>';
				
		$out.=GanttReaderDrawer::drawDays($earliest, $lastest, $vacances);
		
		$out.='</tr></table></div>';
		
		return $out;
	}
	
	/**
	 * Dessine la liste des mois entre les deux dates données
	 * @params (timestamp) $earliest et $lastest les dates entre lesquelles on veut la liste des mois
	 */
	static function drawMonths($earliest, $lastest){
		
		$months = GanttReaderDate::listMonths($earliest, $lastest);
		
		$out='';
		
		foreach($months as $month){
			$out.='<td colspan="'.($month['length']).'" class="dayBox">'.$month['name'].'</td>';
		}
		
		return $out;
	}
	
	/**
	 * Dessine les objets flottants du siagramme, à savoir les flèches de contraintes et la barre du jour
	 * @param (timestamp) $earliest la date du premier jour du diagramme,
	 * @params (array) $contraintes, $projets les listes des contraintes et des projets
	 */
	static function drawObjects($earliest, $contraintes, $projets){
		
		$out='<div '.
		'style="height:'.(count($projets)*36).'px; '.
		'left:'.(round((GanttReaderDate::gap($earliest, strtotime(date('Y-m-d', time())))*36)+35/2)).'px;" '.
		'id="time" '.
		'>'.
		'</div>';
		
		for($i=0; $i<count($contraintes); $i++){
			$out.=GanttReaderDrawer::drawConstraint($contraintes[$i], $projets, $earliest);
		}
		
		return $out;	
	}
	
	/**
	 * Dessine les contraintes de suivi entre les tâches
	 * @params la $contrainte à dessiner, les tableaux des $projets et des $paddings et la date au plus tôt du diagramme $earliest
	 * How : Calculer la taille du premier projet pour définir sa date de fin
	 * 		 Calculer les coordonnées de départ de la contrainte (xA, yA) et d'arrivée (xB, yB)
	 *		 abscisse = nombre de jours depuis la date au plus tôt * largeur des cases
	 *		 ordonnée = index d'apparition du projet * hauteur des cases + 2 cases (+0.5 case si se place à mi-hauteur d'une case)
	 *		 Chaque contrainte est ensuite modélisée dans un graphique SVG à partir de ses propriétés
	 * @see www.w3.org/Graphics/SVG/
	 */
	static function drawConstraint($contrainte, $projets, $earliest){
		
		$projA = $projets[$contrainte['from']];
		$projB = $projets[$contrainte['to']];
				
		$endA = strtotime($projA['debut'].'+'.($projA['longueur']+1).'days'); //fin du projet source : date de début + durée + décalage

		
		$startB = strtotime($projB['debut']);		
		
		$xA = GanttReaderDate::gap($earliest, $endA)*36;
		$yA = $contrainte['from']*36+35*2.5;
		
		$xB = GanttReaderDate::gap($earliest, $startB)*36+35/2;
		$yB = $contrainte['to']*36+35*2;
		

		
		if($endA > $startB){ //si départ et arrivée le même jour (meetings)
			$xA = $xB; 		//ne pas déplacer horizontalement
			
			/* On corrige les hauteurs de départ et d'arrivée */
			if($contrainte['from']>$contrainte['to']){ //si viens d'en bas
				$yA = $yA -15;
				$yB = $yB -15;

			}elseif($contrainte['to']>$contrainte['from']){ //si viens d'en haut
				$yA = $yA +10;
				$yB = $yB + 10;
			}

		}
		
		if($contrainte['from']>$contrainte['to']){ //si la contrainte viens d'en bas (mais d'un jour différent)
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
	
	/**
	 * Dessine la liste des jours présents entre deux dates données
	 * ex: 01, 02, 03 ... 28, 29, 30, 01, 02, 03, ...
	 * @params timestamp) $earliest et $lastest, les dates entre lesquelles on veut la liste des jours
	 */
	static function drawDays($earliest, $lastest, &$vacances){
		
		$out='';
		
		$days = GanttReaderDate::listDays($earliest, $lastest, $vacances);
		
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
	
	
	
	/**
	 * Dessine des jours vides (style spécial pour les jours vaqués) d'une date à une autre
	 * @params (timestamp) $earliest et $lastest les dates entre lesquelles on veut placer des jours vides
	 * @param (array) $vacances la liste des plages de congés
	 */
	static function drawPadding($earliest, $lastest, &$vacances){
		
		if($earliest>=$lastest){ //Si erreur dans les paramètres ou padding hors-zone (résulte que lastest est avant earliest)
			return '';
		}
		
		$out='';
		
		$days = GanttReaderDate::listDays($earliest, $lastest, $vacances);
		
		foreach($days as $day){
			$out.='<td class="dayBox';
			
			if($day['vacation']){
				$out.=', dayOff';	
			}
			
			$out.='"></td>';
			
		}
		return $out;
	}
	
	/**
	 * Dessine une ligne de projet : la représentation du projet, avec bourrage avant et après pour remplir la vue
	 * @params (array) $projet, $vacances les listes des projets et des plages de congés
	 * @params (timestamp) $earliest et $lastest les dates de début et de fin du diagramme
	 * How : on dessine les cases vides avant le projet, 
	 *		on récupère le rendu du projet
	 * 		on dessine le projet puis les cases vides qui suivent le projet (de fin du projet + décalage à fin de la fenêtre)
	 */
	static function drawLine($projet, &$vacances, $earliest, $lastest){
	
		$before = strtotime('-1 day', strtotime($projet['debut'])); //1 jour avant le projet
		
		$out='<tr>';
		
		$out.= GanttReaderDrawer::drawPadding($earliest, $before, $vacances); //bourrage avant
		
		$out.= GanttReaderDrawer::drawProject($projet, $vacances, $earliest, $lastest); //array (rendu du projet + décalage)

		$after = strtotime('+'.($projet['longueur']+1).' days', strtotime($projet['debut'])); //le décalage +1 jour après le projet
		
		if($projet['meeting']){
			$after = strtotime('-1 day', $after);
		}
		
		$out.= GanttReaderDrawer::drawPadding($after, $lastest, $vacances);//bourrage après
		
		$out.='</tr>';

		return $out;
	}
	
	/**
	 * Dessine les cases du projet donné
	 * @params le $projet et le tableau des $vacances
	 * How :
	 * 		On traite les cas particuliers (meetings, projets d'une seule journée, projets pères) avec une procédure spécifique
	 *		Les cas spéciaux passés, dessiner successivement le premier jour, les cases intermédiaires puis le dernier jour du projet.
	 * @see www.w3.org/Graphics/SVG/
	 */
	static function drawProject($projet, $vacances, $earliest, $lastest){
		
		if($projet['hasChild']){
			return ganttReaderDrawer::drawFather($projet, $vacances); //Si projet englobant
		}
		
		$start = strtotime($projet['debut']);
		$end = strtotime('+'.($projet['longueur']).' days', $start);
		
		$current = strtotime($projet['debut']); //on place un repère sur la date de début
		$actuel = 0; //compteur de l'avancement total du dessin, décalages compris
		
		$out='<td class="daybox';
		
			if (GanttReaderDate::inRest($current, $vacances)){
				$out.= ' dayOff';
			}
			
		$out.='">';
		
		
		if($projet['meeting']){
						
			/*Dessin vectoriel SVG d'une étoile*/
			$out.= GanttReaderDrawer::drawStar($projet['couleur']);			
			$out.='</td>';
			
		} elseif($projet['longueur']==0){ //si ne dure qu'un jour
			  
			$out.='<div style="background-color:'.$projet['couleur'].'" class="ganttProjectEnd ganttProjectStart';
		
			if($projet['avancement']==100){
				$out.=' complete';
			}
		
			$out.='"></div></td>';
			
		} else{ // cas classique : le projet dure pusieurs jours
		
		    /*Dessin du premier jour*/  
			$out.='<div style="background-color:'.$projet['couleur'].';" class="ganttProjectStart';
			
			if(GanttReaderDate::completed($projet, $actuel)){
				$out.=' complete';
			}
			
			$out.='"></div></td>';
			
			$current = strtotime('+1 day', $current);
			   
			   
			/*Jours du centre*/
			for(; $actuel<$projet['longueur']-1; $actuel++){
				$out.='<td class="dayBox ';
				
				if(GanttReaderDate::inRest($current, $vacances)){
					$out.=' dayOff';
				}
		
				$out.='"><div style="background-color:'.$projet['couleur'].'" class="ganttProject';
				
				if(GanttReaderDate::completed($projet, $actuel)){
						$out.=' complete';
				}
				
				$out.='"></div></td>';
				
				$current = strtotime('+1 day', $current);
			}
		
		/*Dessin du dernier jour*/
		$out.='<td class="dayBox';
			  
		if(GanttReaderDate::inRest($current, $vacances)){
			$out.=' dayOff';
		}
		
		$out.='"><div style="background-color:'.$projet['couleur'].';" class="ganttProjectEnd';
		
		if(GanttReaderDate::completed($projet, $actuel)){
			$out.=' complete';
		}
		
		$out.='"></div></td>';
			   
		}
		  
		return $out;
	}
	
	/**
	 * Dessine une étoile de la couleur donnée, dans la case courante
	 * @param (String) la couleur de l'étoile, codée en hexadécimal (#123ABC)
	 */
	static function drawStar($couleur){
		return '	<?xml version="1.0" encoding="UTF-8" standalone="no"?>
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
								fill:'.$couleur.';
								fill-rule:evenodd; 
								stroke:#000000; 
								stroke-width:1px; 
								stroke-linecap:butt; 
								stroke-linejoin:round; 
								stroke-opacity:1" />
  					
					</svg>';
	}
	
	/*
	 * Dessine un projet père (projet qui englobe un ou plusieurs autres sous-projets)
	 * @params (array) $projet, $vacances respectivement le projet à traiter et les plages de congés
	 */
	static function drawFather(&$projet, &$vacances){
		$current = strtotime($projet['debut']);
		$actuel = 0;
		
		/*premier jour*/
		$out='<td class="daybox';
		
			if (GanttReaderDate::inRest($current, $vacances)){
				$out.= ' dayOff';
			}
			
		$out.='">';
		$out.='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
					<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 20010904//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">
					<svg 
						xmlns:svg="http://www.w3.org/2000/svg"
   						xmlns="http://www.w3.org/2000/svg"
   						version="1.1"
   						width="35px"
   						height="30px"
						top="0">
							
    						<polygon points="0,30 0,0 35,0 35,10 23,10" style="fill:'.$projet['couleur'].';stroke:none;" />
  					
					</svg>';
		$out.='</td>';
		$current = strtotime('+1 day', $current);
		$actuel++;
		
		/*jours du centre*/
		
		for(; $actuel<$projet['longueur']; $actuel++){
			$out.='<td class="dayBox ';
			if(GanttReaderDate::inRest($current, $vacances)){
				$out.=' dayOff';
			}
		
			$out.='">';
			$out.='<div class="ganttFatherProject" style="background:'.$projet['couleur'].'"></div>';
			$out.='</td>';
			$current = strtotime('+1 day', $current);
	 	 }
		 
		 /*jour de fin*/
		 $out.='<td class="daybox';
			if (GanttReaderDate::inRest($current, $vacances)){
				$out.= ' dayOff';
			}
		$out.='">';
		$out.='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
					<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 20010904//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">
					<svg 
						xmlns:svg="http://www.w3.org/2000/svg"
   						xmlns="http://www.w3.org/2000/svg"
   						version="1.1"
   						width="35px"
   						height="30px">
							
    						<polygon points="0,0 35,0 35,30 12,10 0,10" style="fill:'.$projet['couleur'].';stroke:none" />
  					
					</svg>';
		$out.='</td>';
		 
		 
		 return $out;
	}
}

?>