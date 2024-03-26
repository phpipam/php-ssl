<?php

# default
if(!isset($_params['app'])) { $_params['app'] = "list"; }


# views
if(array_key_exists($_params['app'], $url_items["navigation"]["certificates"]["submenu"])) {
       include("all.php");
}
# cert
else {
       include("certificate.php");
}