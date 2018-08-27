<nav class="nav-site">
        <ul class="nav-site__list">
           <?php
              //nombre del menú en wp 'main-navigation'
              $menu_name = 'Menu main'; //menu ´s name
              $arr_menu=get_array_menu_children($menu_name);
      
               foreach ($arr_menu as $menu ) :
                  $childrens=count($menu["childrens"]);
                  $url = ($childrens>0) ?'#' : $menu["data"]["url"] ;
                  if($childrens == 0): ?>
                    <?php //if ($menu["data"]["title"] == "Events") { ?> <?php //} ?> 
                      <li id="menu-main-<?php  echo $menu["data"]["id"]; ?>" class="nav-site__item " >
                      <a href="<?php echo $url; ?>" class="nav-site__link"  ><?php echo $menu["data"]["title"]; ?></a>
                      </li> 
                  <?php else: ?>                       
                      <li id="menu-main-<?php  echo $menu["data"]["id"]; ?>" class="nav-site__item nav-site__item_with-sublist"  >
                        <a href="<?php echo $url; ?>" class="nav-site__link nav-site__link_has-sublist" ><?php echo $menu["data"]["title"]; ?>
                        </a>
                          <ul class="nav-site__sublist">
                              <?php foreach ($menu["childrens"] as $submenu) : ?>
                                <li class="nav-site__subitem" >
                                  <?php if (count( $submenu['data']['subchildrens'])  > 0 ) :  ?>
                                  <a href="<?php echo $submenu["data"]["url"] ?>" class="nav-site__sublink  nav-site__sublink_has-sublist "  ><?php echo $submenu["data"]["title"] ?></a>
                                        <ul class="nav-site__sublist2">                                
                                            <?php foreach ($submenu['data']['subchildrens'] as $subsubmenu) : ?>
                                                    <li class="nav-site__subitem2" ><a href="<?php echo $subsubmenu["data"]["url"] ?>" class="nav-site__sublink2"  ><?php echo $subsubmenu["data"]["title"] ?></a></li>                                                                      
                                            <?php endforeach; ?>
                                        </ul>                                                                                                                    
                                  <?php else :  ?>
                                  <a href="<?php echo $submenu["data"]["url"] ?>" class="nav-site__sublink   "  ><?php echo $submenu["data"]["title"] ?></a>
                                    </li>                                            
                                  <?php  endif;  ?>
                            
                              <?php endforeach; ?>
                          </ul>
                      </li>
                  <?php endif; 
               endforeach; ?>
        </ul>
      </nav>