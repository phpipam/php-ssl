<div class="position-sticky pt-3" style='padding-top:29px !important;'>

    <?php

    // print menu
    foreach ($url_items as $title=>$items) {

        // header
        print '<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">';
        print '<span>'._($title).'</span>';
        print '</h6>';
        print '<hr style="margin: 0px;">';

        // items
        print '<ul class="nav flex-column">';

        foreach ($items as $item_name=>$item) {
            // active ?
            $active = $_params['route'] == $item_name ? "active" : "";
            // subitems ?
            $icon_subitems = isset($item['submenu']) ? '<i class="fa fa-plus float-end submenu-toggle"></i>' : "";

            // main menu
            print '<li class="nav-item '.$active.'"><a class="nav-link '.$active.'" href="/'.$user->href.'/'.$item_name.'/"><i class="'.$item['icon'].'"></i> '.$item['title'].$icon_subitems.' </a></li>';

            // submenu ?
            if ( isset($item['submenu'])) {
                print '<ul class="nav nav-2 flex-column">';
                foreach ($item['submenu'] as $href=>$submenu_title) {
                    // active ?
                    $active2 = $_params['app'] == $href ? "active" : "";
                    // print
                    print '<li class="nav-item '.$active2.'"><a class="nav-link '.$active2.'" href="/'.$user->href.'/'.$item_name.'/'.$href.'/"><i class="fa fa-chevron-right"></i> '.$submenu_title.' </a></li>';
                }
                print '</ul>';
            }
        }

        print '</ul>';

    }

    ?>
</div>