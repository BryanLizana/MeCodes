<?php 

    // Title de yoast, y si no hay uno el default, si no uno genérico
  $title = get_post_meta(get_the_ID(), '_yoast_wpseo_title', true); 
  if (!empty(awful_replace_title())) {
      $title = awful_replace_title();
   }
  
  if (!empty($title)) {
      echo $title;
  } else if (!empty(get_the_title())) {
      the_title();
  } else {
     echo 'Knowledge Center'; 
  } 


?>

