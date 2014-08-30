/*
 * @author Theo KRISZT
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Provides some useful JS / jQuery methods for ganttReader user interface
 *
 * Tip : respect jQuery explicit context ( != $ )
 */

function ganttRetract(){

    //Show / hide matching buttons
    jQuery('#ganttRetracter').hide();
    jQuery('#ganttExpander').show();

    //Clean styles
    $diagram.removeAttr('style');
    $header.removeAttr('style');
    $sider.removeAttr('style');
    $days.removeAttr('style');
    $legend.removeAttr('style');

    //Fix header spacing
    $header.width($header.width()-20);
}

function ganttExpand(){
    //Show / hide matching buttons
    jQuery('#ganttRetracter').show();
    jQuery('#ganttExpander').hide();

    console.log('$dWidth : '+$dWidth);

    //Clean styles
    $header.removeAttr('style');

    //Animate diagram from current state to full-sized display
    $diagram
        .stop()
        .css('position', 'fixed')
        .css('margin', '0 auto') //horizontally center diagram
        .css('left', $dLeft) //start with originals left & top
        .css('top', $dTop)

        .css('width', $dWidth+'px') // start with originals width & height
        .css('height', $dHeight+'px')

        .animate({
            'left' : '0',
            'top' : '0',
            //'width' : 'auto',
            'width' : '100%',
            //'maxWidth' : '100%',
            'height' : '100%'
        },
        'slow',
        function(){//when animation is completed

            $sider.height($diagram.height()-72-50-20);

            $days.height($diagram.height()-72-50);

            $legend.height($legend.height()-3);

            $header.width($header.width()-20);

        });
}

jQuery(document).ready(function(){
    /*Global vars*/
    $diagram = jQuery('#ganttDiagram');
    $header = jQuery('#ganttHeader');
    $sider = jQuery('#ganttSider');
    $days = jQuery('#ganttDays');
    $legend = jQuery('#ganttLegend');
    $retracter = jQuery('#ganttRetracter');
    $expander = jQuery('#ganttExpander');
    $dTop = $diagram.position().top;
    $dLeft = $diagram.position().left;
    $dWidth = $diagram.width();
    $dHeight = $diagram.height();

    //Complete projects lines according to the first (and pre-filled) line
    var $lines = jQuery('#ganttDays > table > tbody > tr'); //All lines
    var $padding = jQuery('#ganttDays > table > tbody > tr:nth-child(1)').html();//First's line content

    $lines.each(
        function(){
            jQuery(this).html($padding);
        }
    );

    //Now just reduce the header to fit with the verticals scrollbar
        $header.width($header.width()-20);

        //Bind scroll button to matching action
        jQuery('#ganttBackToday').click(function(){
            var $leftMarker = jQuery('#ganttToday').css('left'); //e.g. : "54px"
            var $frameSize = jQuery('#ganttDays').width();
            var $leftLength = $leftMarker.length; //e.g. "54"
            $leftMarker = $leftMarker.substr(0, $leftLength-2);

                $days.animate({
                    scrollLeft: ($leftMarker-$frameSize/2)+'px'
                }, "fast");
        });


        $retracter
            .hide()//Retracter is hidden on loading
            .click(function(){
                ganttRetract();
            });

        $expander
            .click(function(){
                ganttExpand();
            });

});