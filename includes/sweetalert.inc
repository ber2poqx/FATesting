<?php

/**
 * Created by: spyrax10
 */

global $path_to_root;

//jquery 3.6
add_js_ufile($path_to_root . '/js/jquery.js');
//sweetalert2
add_js_ufile($path_to_root . '/js/sweetalert2.all.min.js');
add_js_ufile($path_to_root . '/css/sweetalert2.css');
//functions
add_js_ufile($path_to_root . '/js/spyrax10.js');
//controls
include_once($path_to_root . "/includes/ui/sweetalert_controls.inc");
