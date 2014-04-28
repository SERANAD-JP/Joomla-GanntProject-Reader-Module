<?php

defined('_JEXEC') or die('Restricted access');

/***********************************************************************************************************************************
 * @author Theo KRISZT
 * @copyright (C) 2014 - Theo Kriszt
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Renderer model
 * Provides the methods allowing the needed elements to be displayed
 * @return by default for all methods of this class, if nothing else is specified : (String) $out the HTML render of the element
 * Note : the timestamp Object refers to a timestamp formatted int.
 ***********************************************************************************************************************************/
class GanttReaderDrawer{
	
	/**
	 * @param (String) $title the diagram's title
	 * @params (array) $projects, $vacations, $constraints the lists containing the named informations
	 * @params (timestamp) $earliest & $lastest he limit dates of the view
	 * @return void, draws the diagram's HTML render
	 */
	static function drawDiagram($title, &$projects, &$vacations, &$constraints, $earliest, $lastest){
		
	$out=('<div id="ganttDiagram">');
	
	$out.= GanttReaderDrawer::drawTitle($title);
	
	$out.= GanttReaderDrawer::drawHeader($vacations, $earliest, $lastest);
	
	$out.= GanttReaderDrawer::drawSider($projects);

	$out.= GanttReaderDrawer::drawProjects($projects, $vacations, $constraints, $earliest, $lastest);
	
	$out.=('</div>');
		
	echo $out;
	
	}

	/**
	 * Draws the projects and their container
	 * @params (array) $projects, $vacations, $constraints the extracted informations from the parser
	 * @params (timestamp) $earliest & $lastest dates of the view
	 * How : in the ganttDays container (whose scrollbars are synced to the corresponding elements : ganttHeader & ganttSider),
	 *		for each project , draw its line (left padding, the project itself, then right padding)
	 */
	static function drawProjects(&$projects, &$vacations, &$constraints, $earliest, $lastest){
		/* TODO : remove if still working (as expected) */
		//$paddings = NULL; //The default is no padding (if there's no project to display)
		
		$out='<div id="ganttDays" onscroll="'.
		
				'document.getElementById(\'ganttSider\').scrollTop=this.scrollTop; '.
				
				'document.getElementById(\'ganttHeader\').scrollLeft=this.scrollLeft;"'.
				
				'><table>';

		//draw the projects itselves
		if(isset($projects)){
			
			foreach($projects as $project){
				$out.= GanttReaderDrawer::drawLine($project, $vacations, $earliest, $lastest);
			}
			
		}
		
		//then draw the "floating objects" (i.e bar of the current day & the constraints)
		$out.= GanttReaderDrawer::drawObjects($earliest, $constraints, $projects);
		
		$out.='</table></div>';
		
		//how many pixels are counted between the left-border of the container and the today marker ?
		$scroll = (round((GanttReaderDate::gap($earliest, strtotime(date('Y-m-d', time())))*36)-232)); 
		
		$out.='<script type="text/javascript">'. //now javascript scrolls to  the today marker
		
			'document.getElementById(\'ganttDays\').scrollLeft = '.$scroll.';'.
			
        	'</script>';

		return $out;
	}
	
	/**
	 * Draws the left block (projects names and progress percentage)
	 * @param (array) the projects list
	 */
	static function drawSider(&$projects){
		
		$out='<div id="ganttSider"><table>';
		
		if(isset($projects)){
			
			foreach($projects as $project){
				
				$out.='<tr><td>'.$project['name'].' ('.$project['progress'].'%)</td></tr>';

			}
			
		}
		
		$out.='</table></div>';
		
		return $out;
	}
	
	/**
	 * Draws the diagram's title
	 * @param (String) the $title
	 */
	static function drawTitle($title){
		$out= '<div id="ganttTitle" class="ganttEmbed">'.$title.'</div>';
		return $out;
	}
	
	
	/**
	 * Draws the top block (Months & days listing)
	 * @param(array) $vacations the vacations frames list
	 * @params (timestamp) the $earliest & $lastest dates of the view
	 */
	static function drawHeader(&$vacations, $earliest, $lastest){
		$out='<div id="ganttHeader">'.
			 '<table>'.
			 '<tr>';
				
		$out.=GanttReaderDrawer::drawMonths($earliest, $lastest);
		
		$out.='	</tr>
				<tr>';
				
		$out.=GanttReaderDrawer::drawDays($earliest, $lastest, $vacations);
		
		$out.='</tr></table></div>';
		
		return $out;
	}
	
	/**
	 * Draws the months list between two given dates
	 * @params (timestamp) the $earliest & $lastest between which list the names
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
	 * Draws the diagram's floating objects, like constraints & the today marker
	 * @param (timestamp) $earliest the first date of the view
	 * @params (array) $constraints, $projects the parsed informations of the same name
	 */
	static function drawObjects($earliest, $constraints, $projects){
		
		$out='<div '.
		'style="height:'.(count($projects)*36).'px; '.
		'left:'.(round((GanttReaderDate::gap($earliest, strtotime(date('Y-m-d', time())))*36)+35/2)).'px;" '.
		'id="time" ></div>';
		
		for($i=0; $i<count($constraints); $i++){
			
			$out.=GanttReaderDrawer::drawConstraint($constraints[$i], $projects, $earliest);
			
		}
		
		return $out;	
	}
	
	/**
	 * Draws the following constraints between the tasks
	 * @params (array) $constraint to draw, the $projects list & the $paddings list
	 * @param (timestamp) the $earliest date of the view
	 * How : Define the end date of the source project via its length
	 * 		 Define the start coordinates of the constraint (xA, yA) & of end (xB, yB)
	 *		 X axis = count of days since $earliest * cells width
	 *		 Y axis = index of project's appearance * cells height + 2 cells (+0.5 cell if starts at mid-height)
	 *		 Each constraint is modelled through a SVG graph according to its properties
	 * @see www.w3.org/Graphics/SVG/
	 */
	static function drawConstraint($constraint, $projects, $earliest){
		
		$projA = $projects[$constraint['from']];
		$projB = $projects[$constraint['to']];
				
		$endA = strtotime($projA['start'].'+'.($projA['length']).'days'); //source project's end : start date + length

		
		$startB = strtotime($projB['start']);		
		
		$xA = GanttReaderDate::gap($earliest, $endA)*36;
		$yA = $constraint['from']*36+35*2.5;
		
		$xB = GanttReaderDate::gap($earliest, $startB)*36+35/2;
		$yB = $constraint['to']*36+35*2;
		

		
		if($endA > $startB){ //if start and end are the same day (for instance with meetings)
			$xA = $xB; 		//so don't move the X axis
			
			/* Correct the start and end heights */
			if($constraint['from']>$constraint['to']){ //if comes from below
				$yA = $yA -15;
				$yB = $yB -15;

			}elseif($constraint['to']>$constraint['from']){ //if comes from above
				$yA = $yA +10;
				$yB = $yB + 10;
			}

		}
		
		if($constraint['from']>$constraint['to']){ //if comes from below (but from a different day)
			$yB = $yB + 31+10; //arrival point from below
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
				
					<!-- End layout\'s marker : constraints points with an arrow at their end -->
					<marker id="arrow" markerWidth="20" markerHeight="15" refX="2.5" refY="7.5" markerUnits="userSpaceOnUse" orient="auto">
						<!-- arrow -->
						<path
       						d="M 7.5,7.5 0,0 0,15 z"
       						style="fill-rule:evenodd; 
							stroke-width:1px; stroke-linecap:round; stroke-linejoin:round; stroke-opacity:1" />
					</marker>
					
				</defs>
								
				<!-- the constraint layout itself -->
				<path 
					class="ganttObject" 
					d="
					M '.$xA.','.$yA.' 
					H '.$xB.'
					V'.$yB.'" 
					style="marker-end:url(#arrow)" />
			</svg>';
		
		return $out;
	}
	
	/**
	 * Draws the list days between two dates
	 * ex: 01, 02, 03 ... 28, 29, 30, 01, 02, 03, ...
	 * @params (timestamp) the $earliest & $lastest dates of the view
	 */
	static function drawDays($earliest, $lastest, &$vacations){
		
		$out='';
		
		$days = GanttReaderDate::listDays($earliest, $lastest, $vacations);
		
		foreach($days as $day){
			
			$out.='<td class="dayBox';
			
			if($day['vacation']){
				$out.=' dayOff';
			}
			
			if($day['today']){
				$out.=' ganttEmbed';	
			}
			
			$out.='">'.$day['day'];
			
			$out.='</td>';
			
		}
		
		return $out;
	}
	
	/**
	 * Draws the empty days (special style for of days) from a date to another
	 * @params (timestamp) the $earliest & $lastest dates to fill up
	 * @param (array) the $vacations frames list
	 */
	static function drawPadding($earliest, $lastest, &$vacations){
		
		if($earliest>=$lastest){ //If error with parameters or off-zone padding (i.e latest is before earliest)
			return ''; //then return nothing : no padding
		}
		
		$out='';
		
		$days = GanttReaderDate::listDays($earliest, $lastest, $vacations);
		
		
		
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
	 * Draws a project line : the projects render, surrounded with a padding on each side to fill up the line
	 * @params (array) the $project to process, the list of $vacations frames
	 * @params (timestamp) $earliest et $lastest the limit dates of the view
	 */
	static function drawLine($project, &$vacations, $earliest, $lastest){
	
		$before = strtotime('-1 day', strtotime($project['start'])); //1 day before the project
		
		$out='<tr>';
		
		$out.= GanttReaderDrawer::drawPadding($earliest, $before, $vacations); //before padding
		
		$out.= GanttReaderDrawer::drawProject($project, $vacations, $earliest, $lastest);// the project itself

		$after = strtotime('+'.($project['length']).' days', strtotime($project['start'])); //+1 day after project's end


		
		$out.= GanttReaderDrawer::drawPadding($after, $lastest, $vacations);//padding after
		
		$out.='</tr>';

		return $out;
	}
	
	/**
	 * Draws the cells of the given project
	 * @params the $project & the $vacations frames
	 * How :
	 * 		Process the particular cases (meetings, one-day projects, father projects) with a specific procedure
	 *		The special cases past, draw successively the first day, the middle cells and then the last day
	 * @see www.w3.org/Graphics/SVG/
	 */
	static function drawProject($project, $vacations, $earliest, $lastest){
		
		if($project['hasChild']){
			return ganttReaderDrawer::drawFather($project, $vacations); // If the project has a child or more
		}
		
		$start = strtotime($project['start']);
		
		$end = strtotime('+'.($project['length']-1).' days', $start);

		$current = strtotime($project['start']); //place a mark on the start date
		
		$now = 0; //counts the total progress of the drawing
		
		$out='<td class="daybox';
		
			if (GanttReaderDate::inRest($current, $vacations)){
				$out.= ' dayOff';
			}
			
		$out.='">';
		
		
		if($project['meeting']){
						
			/* Vectorial drawing of a star in SVG */
			$out.= GanttReaderDrawer::drawStar($project['color']);	
					
			$out.='</td>';
			
		} elseif($project['length']==1){ // if lasts a single day

			$out.='<div style="background-color:'.$project['color'].'" class="ganttProjectEnd ganttProjectStart';
		
			if($project['progress']==100){
				$out.=' complete';
			}
		
			$out.='"></div></td>';
			
		} else{ // classic case : the project lasts several days
		
		    /*First day's drawing*/  
			$out.='<div style="background-color:'.$project['color'].';" class="ganttProjectStart';
			
			if(GanttReaderDate::completed($project, $now)){
				$out.=' complete';
			}
			
			$out.='"></div></td>';
			
			$current = strtotime('+1 day', $current);
			   
			   
			/*Middle days*/
			for(; $now<$project['length']-2; $now++){
				$out.='<td class="dayBox ';
				
				if(GanttReaderDate::inRest($current, $vacations)){
					$out.=' dayOff';
				}
		
				$out.='"><div style="background-color:'.$project['color'].'" class="ganttProject';
				
				if(GanttReaderDate::completed($project, $now)){
						$out.=' complete';
				}
				
				$out.='"></div></td>';
				
				$current = strtotime('+1 day', $current);
			}
		
		/*Last day's drawing*/
		$out.='<td class="dayBox';
			  
		if(GanttReaderDate::inRest($current, $vacations)){
			$out.=' dayOff';
		}
		
		$out.='"><div style="background-color:'.$project['color'].';" class="ganttProjectEnd';
		
		if(GanttReaderDate::completed($project, $now)){
			$out.=' complete';
		}
		
		$out.='"></div></td>';
			   
		}
		  
		return $out;
	}
	
	/**
	 * Draws a star of the given color in the current cell
	 * @param (String) the hex-coded color of the star (#ABC123)
	 */
	static function drawStar($color){
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
								fill:'.$color.';
								fill-rule:evenodd; 
								stroke:#000000; 
								stroke-width:1px; 
								stroke-linecap:butt; 
								stroke-linejoin:round; 
								stroke-opacity:1" />
  					
					</svg>';
	}
	
	/*
	 * Draws a father project (a project that surround other projects as sub-projects)
	 * @params (array) the $project to process, $vacations the vacations frames
	 */
	static function drawFather(&$project, &$vacations){
		$current = strtotime($project['start']);
		$now = 0;
		
		/*first day*/
		$out='<td class="daybox';
		
			if (GanttReaderDate::inRest($current, $vacations)){
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
							
    						<polygon points="0,30 0,0 35,0 35,10 23,10" style="fill:'.$project['color'].';stroke:none;" />
  					
					</svg>';
		$out.='</td>';
		$current = strtotime('+1 day', $current);
		$now++;
		
		/*middle days*/
		
		for(; $now<$project['length']-1; $now++){
			$out.='<td class="dayBox ';
			if(GanttReaderDate::inRest($current, $vacations)){
				$out.=' dayOff';
			}
		
			$out.='">';
			$out.='<div class="ganttFatherProject" style="background:'.$project['color'].'"></div>';
			$out.='</td>';
			$current = strtotime('+1 day', $current);
	 	 }
		 
		 /*last day*/
		 $out.='<td class="daybox';
			if (GanttReaderDate::inRest($current, $vacations)){
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
							
    						<polygon points="0,0 35,0 35,30 12,10 0,10" style="fill:'.$project['color'].';stroke:none" />
  					
					</svg>';
		$out.='</td>';
		 
		 
		 return $out;
	}
}

?>