<?php
/**
 * Created by: spyrax10
 */

function sweet_notification($msg, $error = false, $align = 'center') {
   
   if ($error) {
      echo "<script> display_error_msg('$msg', '$align'); </script>";
    }
    else {
      echo "<script> display_info_msg('$msg', '$align'); </script>";
    }
}

function sweet_popup_notification($name, $title = '', $msg = '', $icon = '', $set = true) {
   
   global $Ajax;
   
   $js = "_validate.$name="
      .($set ? 
         "function()
         { 
            return display_popup_msg('$title', '$msg', '$icon');;
         };"
      : 'null;');

   if (in_ajax()) {
      $Ajax->addScript(true, $js);
   } else
      add_js_source($js); 
}

function sweet_confirm($name, $suc_msg = '', $set = true) {
   global $Ajax;

   $js = "_validate.$name="
      .($set ? 
         "function()
         { 
            return confirm_dialog('$suc_msg');
         };"
      : 'null;');

   if (in_ajax()) {
      $Ajax->addScript(true, $js);
   } else
      add_js_source($js);
}