<?php 

//function calendar_mark_post($post_type,$taxonomia,$acf_name_calendar_date) {
gmdate( 'm', $ts );


$post_type              = $_REQUEST['post_type_name'];
$taxonomia              = $_REQUEST['taxo_post_type'];
$acf_name_calendar_date = $_REQUEST['acf_name_date_event'];
$mes = $_REQUEST['mes'];


     $initial = false;
     $echo = true ;
    global $wpdb, $m, $monthnum, $year, $wp_locale, $posts;
    $key = md5( $m . $monthnum . $year );
    $cache = wp_cache_get( 'get_calendar', 'calendar' );
    if ( $cache && is_array( $cache ) && isset( $cache[ $key ] ) ) {
        /** This filter is documented in wp-includes/general-template.php */
        $output = apply_filters( 'get_calendar', $cache[ $key ] );

        if ( $echo ) {
            echo $output;
            return;
        }
        return $output;
    }

    if ( isset( $_GET['w'] ) ) {
        $w = (int) $_GET['w'];
    }
    $week_begins = (int) get_option( 'start_of_week' );
    $ts = current_time( 'timestamp' );
    $thisyear = gmdate( 'Y', $ts );
    $thismonth = $mes;
    $unixmonth = mktime( 0, 0 , 0, $thismonth, 1, $thisyear );

    /* translators: Calendar caption: 1: month name, 2: 4-digit year */
    $calendar_caption = _x('%1$s %2$s', 'calendar caption');

    $calendar_output = '<h2 class="calendar-widget__month">' . strtoupper($wp_locale->get_month( $thismonth )) . '</h2>   
    <table class="calendar-widget__table">
    <thead class="calendar-widget__thead">
    <tr class="calendar-widget__tr" >';
 
    $myweek = array();
 
    for ( $wdcount = 0; $wdcount <= 6; $wdcount++ ) {
        $myweek[] = $wp_locale->get_weekday( ( $wdcount + $week_begins ) % 7 );
    }
 
    foreach ( $myweek as $wd ) {
        $day_name = $initial ? $wp_locale->get_weekday_initial( $wd ) : $wp_locale->get_weekday_abbrev( $wd );
        $wd = esc_attr( $wd );
        $calendar_output .= "\n\t\t<th scope=\"col\" title=\"$wd\" class='calendar-widget__th' >$day_name</th>";
    }
    $calendar_output .= '
    </tr>
    </thead>'.
   ' <tbody class="calendar-widget__tbody" >
    <tr class="calendar-widget__tr" >';
 
    $daywithpost = array();
 
    // Get days with posts---SELECT DAY según mes actual

   $dayswithposts = $wpdb->get_results( "SELECT DAYOFMONTH(pm.meta_value), id  FROM  $wpdb->posts as p INNER JOIN  $wpdb->postmeta as  pm on p.ID = pm.post_id  WHERE  p.post_status = 'publish' and pm.meta_key = '$acf_name_calendar_date' 
  and MONTH(meta_value)  = '$thismonth' and YEAR(meta_value)  = '$thisyear' and post_type = '$post_type' ", ARRAY_N ); 

//   echo '<pre>'; var_dump(  ); echo '</pre>'; die; /***VAR_DUMP_DIE***/  
            if ( $dayswithposts ) {
                    $daywithpost[] = "null";
                    $post_id_of_daywith[] ="null";
                foreach ( (array) $dayswithposts as $daywith ) {
                    $daywithpost[] = $daywith[0];
                    $post_id_of_daywith[] = $daywith[1];
                }
            }
    // See how much we should pad in the beginning
    $pad = calendar_week_mod( date( 'w', $unixmonth ) - $week_begins );
    if ( 0 != $pad ) {
        $calendar_output .= "\n\t\t".'<td colspan="'. esc_attr( $pad ) .'" class=" calendar-widget__td ">&nbsp;</td>';
    }
    $newrow = false;
    $daysinmonth = (int) date( 't', $unixmonth );

    for ( $day = 1; $day <= $daysinmonth; ++$day ) {
        if ( isset($newrow) && $newrow ) {
            $calendar_output .= "\n\t</tr>\n\t<tr>\n\t\t";
        }
        $newrow = false;
 
        if ( $day == gmdate( 'j', $ts ) &&
            $thismonth == gmdate( 'm', $ts ) &&
            $thisyear == gmdate( 'Y', $ts ) ) {
            $calendar_output .= '<td id="today"  class="calendar-widget__td" >';
        } else {
            $calendar_output .= '<td class="calendar-widget__td">';
        }
        //select * day según el mes actual
        $index = array_search($day,$daywithpost) ;
        //categories for the color
            // $args = array('taxonomy' => $taxonomia, 'hide_empty' => true);            
            if (is_array($post_id_of_daywith)) {
                foreach ($post_id_of_daywith as $i => $id_post_) {
                    if ( $value != "null") {
                    $cat_of_post  =  get_the_terms($id_post_,$taxonomia);
                        // Sólo las cat padres
                        foreach ($cat_of_post as $cat_of_postkey => $cat_of_postvalue) {
                                if ($cat_of_postvalue->parent=='0') {
                                $cate_list_[]  = $cat_of_post[0]->name;                    
                                }
                        }
                        // if ($cat_of_post[0]->parent=='0') {
                    // $cate_list_[]  = $cat_of_post[0]->name;    //todas las cat que tenga los events                
                        // }
                    }
                }

                $cate_list_ =  array_unique($cate_list_);
            }
        //LISTAR LAS CATE DE LOS POST ID($daywithpost)
        
        // if (   in_array( $day, $daywithpost ) ) {
            //Se tiene que agregar más campos al arrays colors para que abarque más campos.
        $array_colors_span = array('calendar-widget__event_type-1' ,'calendar-widget__event_type-2',"calendar-widget__event_type-3",
        "calendar-widget__event_type-4", 'calendar-widget__event_type-5' ,'calendar-widget__event_type-6',
        "calendar-widget__event_type-7","calendar-widget__event_type-8", 'calendar-widget__event_type-1' ,'calendar-widget__event_type-2',
        "calendar-widget__event_type-3","calendar-widget__event_type-4",);
        if (  $index > 0 ) {
            // any posts today?
            $date_format = date( _x( 'F j, Y', 'daily archives date format' ), strtotime( "{$thisyear}-{$thismonth}-{$day}" ) );
            /* translators: Post calendar label. 1: Date */
              $post_calendar =   get_post( $post_id_of_daywith[$index] );
              $cat_of_post =  get_the_terms($post_id_of_daywith[$index],$taxonomia);
              $cat_of_post = $cat_of_post[0];

                foreach ($array_colors_span as $k_2 => $v_2) {
                    // $int = array_search ($cat_of_post->name, $cate_list_);
                    // echo '<pre>'; var_dump( $cat_of_post->name . "==". $cate_list_[$k_2] ); echo '</pre>'; /***VAR_DUMP_DIE***/ 
                    if ($cat_of_post->name == $cate_list_[$k_2] ) {

                      $color_span =  $v_2; //add color
                      $array_color_list_show[$k_2] = $v_2;
                      break;
                    } 
                }
                // if ( !isset($color_span )) {
                //       $color_span =  '#fff'; //events outside  list
                //     //   $color_span =  '#b1b1b1'; //events outside  list                                            
                // }
            $url = $post_calendar->guid;
            $label = sprintf( __( 'Posts published on %s' ), $date_format );

            if ( !isset($color_span )) {
            $calendar_output .= '<a href="'.$url.'" aria-label="%s"  class="calendar-widget__event"  style="color:grey;" target="_black" >'. $day.'</a>';
            
            }else {
            $calendar_output .= '<a href="'.$url.'" aria-label="%s"  class="calendar-widget__event '.$color_span.' " target="_black" >'. $day.'</a>';            
            }                      
          
            unset($color_span);
        } else {
            $calendar_output .= $day;
        }
        $calendar_output .= '</td>';
        if ( 6 == calendar_week_mod( date( 'w', mktime(0, 0 , 0, $thismonth, $day, $thisyear ) ) - $week_begins ) ) {
            $newrow = true;
        }
    }    
    $pad = 7 - calendar_week_mod( date( 'w', mktime( 0, 0 , 0, $thismonth, $day, $thisyear ) ) - $week_begins );
    if ( $pad != 0 && $pad != 7 ) {
        $calendar_output .= "\n\t\t".'<td class="calendar-widget__td" colspan="'. esc_attr( $pad ) .'">&nbsp;</td>';
    }
    $calendar_output .= "\n\t</tr>\n\t</tbody>\n\t</table>";
    $cache[ $key ] = $calendar_output;
    wp_cache_set( 'get_calendar', $cache, 'calendar' );
    if ( $echo ) {
         ?>      
         <?php echo apply_filters( 'get_calendar', $calendar_output );  ?>
          <div class="calendar-widget__legend">
         
            <?php  if(count($array_color_list_show) > 0)
                      foreach ($array_color_list_show as $k_2 => $v_2) {
                      ?> 
                         <div class="calendar-widget__legend-type"><span class="calendar-widget__legend-icon <?php  echo $v_2 ?>"></span><span class="calendar-widget__legend-text"><?php echo $cate_list_[$k_2] ?></span></div>                   
            <?php }
        ?>  </div>  <?php
        echo "";
    }
  //  echo apply_filters( 'get_calendar', $calendar_output );

        wp_die();

        


//}