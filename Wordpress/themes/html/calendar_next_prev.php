<article class="widget widget_calendar">
    <div class="widget__title"><i class="widget__title-icon icon">
        <svg>
        </svg></i>
      <h1 class="widget__title-text">Calendar</h1>
    </div>

      <div class="resource_calendar">
        <input type="hidden" name="" value="<?php echo get_field('post_type_name') ?>" id="cale_post_type_name" >
        <input type="hidden" name="" value="<?php echo get_field('taxo_post_type') ?>" id="cale_taxo_post_type" >
        <input type="hidden" name="" value="<?php echo get_field('acf_name_date_event') ?>" id="cale_acf_name_date_event" >
        <input type="hidden" name="" value="<?php  echo   admin_url( 'admin-ajax.php') ?>" id="url_content_calendar">
          <?php    $ts = current_time( 'timestamp' );
                      $mes = gmdate( 'm', $ts );
           ?>
        <input type="hidden" name="" value="<?php echo $mes ?>" id="calendar_mes_dinamic">                  
      </div>
      <img src="<?php bloginfo('template_url');?>/images/icons/prev.png"  id="calendar_before" alt="prev_month" style="width: 40px; float: left;" >
      <img src="<?php bloginfo('template_url');?>/images/icons/next.png" id="calendar_after" alt="next_month" style="width: 40px; float: right;">
      
      
     <div class="calendar-widget" id="calendar_box">
        <?php //calendar_mark_post(get_field('post_type_name'),get_field('taxo_post_type'),get_field('acf_name_date_event')); ?>
    </div>
    <?php  wp_enqueue_script( 'calendar_js',  get_bloginfo('template_url' ).'/js/calendar_next_prev.js' , array('jquery') );?>

  </article>