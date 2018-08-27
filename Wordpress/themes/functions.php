<?php 
// include("widgets-theme.php");
// include("sidebars.php");

//imagen destacada
add_theme_support( 'post-thumbnails' ); 

///Agregar Custom fields Generales: - Tester to view what do this
if (function_exists('acf_add_options_page')) {
        
        $parent = acf_add_options_page(array(
            'page_title' => 'Theme General Settings', 
            'menu_title' => 'Theme Settings',
            'redirect' => false
        ));
        acf_add_options_sub_page(array(
            'page_title' => 'Home Configuration',
            'menu_title' => 'Home',
            'parent_slug' => $parent['menu_slug'],
        ));        
}

//para crear un sidebar y pueda aparecer en el dashboard
if (function_exists('register_sidebar')) {
    
    register_sidebar(array(
            'name' => 'Widgetized Area',
            'id'   => 'katuhu',
            'description'   => 'This is a widgetized area.',
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h4>',
            'after_title'   => '</h4>'
    ));

}


function ae_n_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Side Bar Main', 'ae_n' ),
		'id'            => 'sidebar-1',
		'description'   => __( 'Add widgets here to appear in your sidebar.', 'ae_n' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );
}
add_action( 'widgets_init', 'ae_n_widgets_init' );


// Get menu
function get_array_menu_children($menu_name,$args = null){
    $menu = wp_get_nav_menu_items( $menu_name,$args );
    $tmenu=count($menu);
    $index_parent=0;
      $index_subparent = 0;
    $arr_menu = array ();
    for($n=0;$n<$tmenu;$n++){
        if(($n>0 && $menu[$n]->menu_item_parent==0) ){
            $index_parent++;
             $index_subparent = 0;
        }
        $menu_items=array (
            "id" => $menu[$n]->object_id,
            "title" => $menu[$n]->title,
            "post_id" => $menu[$n]->ID,       
            "post_parent" => $menu[$n]->post_parent,
            "guid" => $menu[$n]->guid,
            "post_type" => $menu[$n]->post_type,
            "url" => $menu[$n]->url,
            "menu_item_parent" => $menu[$n]->menu_item_parent,
        );
        if($menu[$n]->menu_item_parent==0){
            $arr_menu[$index_parent]["data"]=$menu_items;
            // $arr_menu_test[$index_parent]["data"]=$menu_items;
        }else{
            if ( $menu[$n]->menu_item_parent ==  $arr_menu[$index_parent]["data"]['post_id'] ) {
                      $index_subparent++;
                     $arr_menu[$index_parent]["childrens"][$index_subparent]["data"]=$menu_items;
            }else {
                     $arr_menu[$index_parent]["childrens"][$index_subparent]["data"]["subchildrens"][]["data"]=    $menu_items;   
                }
        }

    }

    return $arr_menu;

}

/*Longitud del string que se va a mostrar the_excerpt*/
function new_excerpt_length($length) {
	global $post;
	if ($post->post_type == 'post')
		return 15;
	else if ($post->post_type == 'project')
		return 15;
	else
		return 30;
}
add_filter('excerpt_length', 'new_excerpt_length');

/*quitar el [...] y poner '...'*/
function new_excerpt_more( $more ) {
    return '...';
}
add_filter('excerpt_more', 'new_excerpt_more');





function lists_img_instagram($token ,$limit = 9)
{
   ?> 
    <div class="instagram-widget">
        
        <ul class="instagram-widget__list" id="list_img_instagram" >
        
        </ul>
    </div>
    <input type="hidden" name="" value="<?php echo $token ?>" id="token_id_instagram">
    <input type="hidden" name="" value="" id="next_url_list_img_instagram">
    <input type="hidden" name="" value="<?php echo $limit ?>" id="count_img_per_list_img_instagram">
    <!--<div id="btn_next_url_list_img_instagram" >Next</div>-->
    <?php
    wp_enqueue_script( 'list_img_instagram',  get_bloginfo('template_url' ).'/js/list-img-instagram.js' , array('jquery') );

}


// call in the header - after to get_header
function add_script_facebook_page_()
{
    ?>
    <div id="fb-root"></div>
    <script>(function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s); js.id = id;
    js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.6";
    fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));</script>

    <?php
}

// Print FanPage-FB
function get_facebook_page_($url_page)
{
    $url_page = trim($url_page);
    ?>
        <!-- Plugin de página de facebook -->
        <!--<style>
           .content_fb{
                     display: inline-block;
                     width: 100%;
                     height: 400px;
                     margin: 15px;
                  }
        </style>-->
        <div class="fb-page" data-href="<?php echo $url_page ;?>" data-tabs="timeline" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="true" data-height="323" ></div>
    <?php

}



///subcribe from - mailchimp
function prefix_send_email_to_admin() {

    $data = [
       'email'     => $_POST['email'],
       'status'    => 'pending',
       'firstname' => $_POST['name'],
       'lastname' => $_POST['name']    
   ];
    $r =  syncMailchimp($data);
    wp_redirect( home_url().'?status='.$r ); exit;
   
   }
   add_action( 'admin_post_nopriv_suscribe_form', 'prefix_send_email_to_admin' );
   add_action( 'admin_post_suscribe_form', 'prefix_send_email_to_admin' ); 
   
   
   ///subcribe from -  usar api de Mailchimp 
   function syncMailchimp($data) {
       $page = get_page_by_path('home'); 
       $id_home= $page->ID; 
   
       $apiKey = get_field('api_key_mailchimp',$id_home);  // '1ad3b084e9db96b03004ef3f6c77f9b0-us15';
       $listId = get_field('list_id_mailchimp',$id_home)  ;// '9127061a13';
   
       $memberId = md5(strtolower($data['email']));
       $dataCenter = substr($apiKey,strpos($apiKey,'-')+1);
       $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . $listId . '/members/' . $memberId;
   
       $json = json_encode([
           'email_address' => $data['email'],
           'status'        => $data['status'], // "subscribed","unsubscribed","cleaned","pending"
           'merge_fields'  => [
               'FNAME'     => $data['firstname'],
               'LNAME'     => $data['lastname']
           ]
       ]);
       $ch = curl_init($url);
   
       curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
       curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_TIMEOUT, 10);
       curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($ch, CURLOPT_POSTFIELDS, $json);                                                                                                                 
   
       $result = curl_exec($ch);
       $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
       curl_close($ch);
   
       return $httpCode;
   }



///Senf Email from
function prefix_contact_us_form() {

    $id_page_contact = $_REQUEST['id_page_contact'];
    $para      = get_field('email_destino',$id_page_contact);//'nobody@example.com';
    $titulo    = get_field('email_title',$id_page_contact); //'El título';
    $mensaje   = '<table> '."\r\n" .'
            <tr>  '."\r\n" .'
                <td>Option Select</td> '."\r\n" .'
                <td>'.$_REQUEST['option'].'</td> '."\r\n" .'
            </tr> '."\r\n" .'
            <tr> '."\r\n" .'
                <td>First Name</td> '."\r\n" .'
                <td>'.$_REQUEST['first-name'].'</td> '."\r\n" .'
            </tr> '."\r\n" .'
            <tr> '."\r\n" .'
                <td>Last- Name</td> '."\r\n" .'
                <td>'.$_REQUEST['last-name'].'</td> '."\r\n" .'
            </tr> '."\r\n" .'
            <tr> '."\r\n" .'
                <td>Phone Number</td> '."\r\n" .'
                <td>'.$_REQUEST['phone-number'].'</td> '."\r\n" .'
            </tr> '."\r\n" .'
            <tr> '."\r\n" .'
                <td>Email</td> '."\r\n" .'
                <td>'.$_REQUEST['email'].'</td> '."\r\n" .'
            </tr>       '."\r\n" .'
            <tr>  '."\r\n" .'
                <td>Member number</td> '."\r\n" .'
                <td>'.$_REQUEST['member_number'].'</td> '."\r\n" .'
            </tr> '."\r\n" .'       
            <tr>  '."\r\n" .'
                <td>City</td> '."\r\n" .'
                <td>'.$_REQUEST['city'].'</td> '."\r\n" .'
            </tr> '."\r\n" .'  
            <tr>  '."\r\n" .'
                <td>State</td> '."\r\n" .'
                <td>'.$_REQUEST['state'].'</td> '."\r\n" .'
            </tr> '."\r\n" .'        
            <tr>  '."\r\n" .'
                <td>Zip Code</td> '."\r\n" .'
                <td>'.$_REQUEST['zip_code'].'</td> '."\r\n" .'
            </tr> '."\r\n" .'                         
            <tr> '."\r\n" .'
                <td>Comments</td>'."\r\n" .'
                <td>'.$_REQUEST['comments'].'</td>'."\r\n" .'
            </tr>    '."\r\n" .'                                
        </table>
        ';
    
    $from = get_field('email_from',$id_page_contact);
    $cabeceras = 'From: ' . $from ."\r\n" .
        'X-Mailer: PHP/' . phpversion() ."\r\n" .
        'MIME-Version: 1.0' ."\r\n" .
        'Content-Type: text/html; charset=UTF-8';
    
    
    if (mail($para, $titulo, $mensaje, $cabeceras)) {
            
       $r = "200";
    }
    $url_redirect  = get_permalink( $id_page_contact );
     wp_redirect( $url_redirect .'?status='.$r ); exit;
    
    }
    add_action( 'admin_post_nopriv_contact_us_form', 'prefix_contact_us_form' );
    add_action( 'admin_post_contact_us_form', 'prefix_contact_us_form' ); 




    // AJAX PROCCESS
///Calendar_next_prev
add_action('wp_ajax_nopriv_calendar_next_prev', 'calendar_next_prev');
add_action('wp_ajax_calendar_next_prev', 'calendar_next_prev');
function calendar_next_prev(){
include ('./calendar_next_prev.php');
}



//Permite la estructura /categorory/subcategory/topic-name/ sin que haya páginas 404 
// -Pero tre errores como el que se puede acceder desde cualquier url mientras la última sección de la url sea un slug válido de categoría
function permalinkWithCategoryBaseFix() {
    global $wp_query;
    // Only check on 404's
    if ( true === $wp_query->is_404) {
        $currentURI = !empty($_SERVER['REQUEST_URI']) ? trim($_SERVER['REQUEST_URI'], '/') : '';        
        if ($currentURI) {
            // $categoryBaseName = trim(get_option('category_base'), '/.'); // Remove / and . from base

            // if ($categoryBaseName) {
                // Perform fixes for category_base matching start of permalink custom structure
                // if ( substr($currentURI, 0, strlen($categoryBaseName)) == $categoryBaseName ) {
                    // Find the proper category
                    $childCategoryObject = get_category_by_slug($wp_query->query_vars['name']);
                    if (is_object($childCategoryObject)) {
                        $paged = ($wp_query->query_vars['paged']) ? $wp_query->query_vars['paged']: 1;
                    
                        $wp_query->query(array(
                                              'cat' => $childCategoryObject->term_id,
                                              'paged'=> $paged
                                         )
                        );
                        // Set our accepted header
                        status_header( 200 ); // Prevents 404 status
                    }
                    unset($childCategoryObject);
                // }
            // }
            // unset($categoryBaseName);
        }
        unset($currentURI);
    }
}

add_action('template_redirect', 'permalinkWithCategoryBaseFix');


//Definir los templates para las subcategorías (O cualquier condición que se requiera)
function new_subcategory_hierarchy() {
    $category = get_queried_object();

    $parent_id = $category->category_parent;

    $templates = array();

    if ( $parent_id == 0 ) {
        // Use default values from get_category_template()
        $templates[] = "category-{$category->slug}.php";
        $templates[] = "category-{$category->term_id}.php";
        $templates[] = 'category.php';     
    } else {
        // Create replacement $templates array
        $parent = get_category( $parent_id );

        if (get_field("sub_category_is_faq",$category) ) { //template específico para la página de FAQ
            $templates[] = 'subcategory-faq.php';
        }if (get_field("sub_category_is_glossary",$category) ) {//template específico para la página de Glossary
            $templates[] = 'subcategory-glossary.php';
        }else {
            // Current first
            $templates[] = "subcategory-{$category->slug}.php";
            $templates[] = "subcategory-{$category->term_id}.php";

            // Parent second
            $templates[] = "subcategory-{$parent->slug}.php";
            $templates[] = "subcategory-{$parent->term_id}.php";
            $templates[] = 'subcategory.php'; 
        }
    }

    return locate_template( $templates );
}

add_filter( 'category_template', 'new_subcategory_hierarchy' );


//Para cambiar la ruta de búsqueda page
function fb_change_search_url_rewrite() {
    if ( is_search() && ! empty( $_GET['s'] ) ) {
        wp_redirect( home_url( "/search/" ) . urlencode( get_query_var( 's' ) ) );
        exit();
    }	
}
add_action( 'template_redirect', 'fb_change_search_url_rewrite' );