<?php 
$args = array(
    'post_type' => 'magazine', //POST TYPE Change
    'paged' => $_REQUEST['page'],
    'posts_per_page' => $_REQUEST['limit'],
    'orderby' => 'meta_value', //if the meta_key (population) is numeric use meta_value_num instead
    'order' => 'DESC', //setting order direction
    'post_status'=> 'publish' ,
    'meta_query'	=> "",
  );

  

if (!empty($_REQUEST['year_date']) || !empty($_REQUEST['mes'])  ) {
    //si solo es mes, se toma de este año
    //si solo es year, de este year to next
  
    $year =$_REQUEST['year_date'] ;
  
  
    if ( empty($_REQUEST['mes']) ) {
        
      $originalDate = "$year-01-01";
    $newDate = date("Y-m-d", strtotime($originalDate)); 
    
    $newDateEND = date("Y-m-d", strtotime( $newDate . "+ 1 year"));  
      $args['order'] = "ASC";
      $args['meta_query'] = array('relation'		=> 'AND',
                                array(
                                'key'	 	=> 'post_date',
                                'compare' 	=> '>=' ,                        
                                'value'	  	=> $newDate,
                                  'type' => 'DATE',
                                ) ,
                                array(
                                  'key'	 	=> 'post_date',
                                  'compare' 	=> '<' ,                        
                                  'value'	  	=> $newDateEND,
                                    'type' => 'DATE',
                                )    
                            );
    }else {
      $year = (!empty($_REQUEST['year_date'])) ? $_REQUEST['year_date'] : date("Y") ;
    $month = $_REQUEST['mes'] ;  
      $originalDate = "$year-$month-01";
      $newDate = date("Y-m-d", strtotime($originalDate));  
      $newDateEND = date("Y-m-d", strtotime( $newDate . "+ 1 month"));  
      $args['order'] = "ASC";
      $args['meta_query'] = array('relation'		=> 'AND',
                                array(
                                'key'	 	=> 'post_date',
                                'compare' 	=> '>=' ,                        
                                'value'	  	=> $newDate,
                                'type' => 'DATE',
                                ) ,
                                array(
                                  'key'	 	=> 'post_date',
                                  'compare' 	=> '<' ,                        
                                  'value'	  	=> $newDateEND,
                                  'type' => 'DATE',
                                )    
                            );
    }
   
  }  
    $wp_query = new  WP_Query();   
    $wp_query->query($args);

if ( $wp_query->have_posts()  ) : ?>
    <?php while ( $wp_query->have_posts() ) : $wp_query->the_post(); ?>
        <?php  $title =  get_the_title() ;  
            $img_post   =   get_field('cover_image');
            if (empty($img_post)) { $img_post   =   get_field('main_img_page');    }
             $img_post =  wp_get_attachment_image_src( $img_post,'full' );

      
        //Asignar una portada por defecto - de manera intercalada
        $n_portada_default = rand(1,3);
        switch ($n_portada_default) {
            case 1:
            $img_post = (!empty($img_post))? $img_post[0] : get_field('img_default_magazine_one','options'); 
                break;
            case 2:
            $img_post = (!empty($img_post))? $img_post[0] : get_field('img_default_magazine_two','options'); 
                break;
                
            default:
            $img_post = (!empty($img_post))? $img_post[0] : get_field('img_default_magazine_three','options'); 
                break;
        }

        $url_redirect =  get_field('url_redirect_magazine');
        $post_date =  get_field('post_date');
        
        ?>


<li class="events-list__item events-posts__item "><a class="events-list__link" href="<?php echo $url_redirect ?>"  target="_blank">  
<h2 class="magazine-list__title"><?php echo $title ?></h2>
                    <img class="events-list__image" src="<?php echo $img_post ?>" alt="" width="" height=""><span class="magazine-list__view-more button_link"> <span>Read Magazine</span><i class="icon">
                        <svg>
                        <use xlink:href="<?php bloginfo('template_url');?>/images/icons/icons.svg#icon-external-link"></use>
                        </svg></i></span></a>
                </li>
               

    <?php endwhile; ?>
<?php else: ?>
    <p>Magazines Not found</p>
<?php  endif;  ?>
   <?php wp_reset_query(); wp_die(); ?> 


 