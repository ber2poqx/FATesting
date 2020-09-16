<?php

/* List of installed additional extensions. If extensions are added to the list manually
	make sure they have unique and so far never used extension_ids as a keys,
	and $next_extension_id is also updated. More about format of this file yo will find in 
	FA extension system documentation.
*/

$next_extension_id = 20; // unique id for next installed extension

$installed_extensions = array (
  3 => 
  array (
    'name' => 'Inventory Items CSV Import',
    'package' => 'import_items',
    'version' => '2.4.0-3',
    'type' => 'extension',
    'active' => false,
    'path' => 'modules/import_items',
  ),
  4 => 
  array (
    'name' => 'Thinker theme',
    'package' => 'thinker-theme',
    'version' => '2.4.0-1',
    'type' => 'theme',
    'active' => false,
    'path' => 'themes/thinker',
  ),
  5 => 
  array (
    'name' => 'Theme Studio',
    'package' => 'studio',
    'version' => '2.4.0-1',
    'type' => 'theme',
    'active' => false,
    'path' => 'themes/studio',
  ),
  6 => 
  array (
    'name' => 'Theme Anterp',
    'package' => 'anterp',
    'version' => '2.4.0-1',
    'type' => 'theme',
    'active' => false,
    'path' => 'themes/anterp',
  ),
  7 => 
  array (
    'name' => 'Theme Bluecollar',
    'package' => 'bluecollar',
    'version' => '2.4.0-3',
    'type' => 'theme',
    'active' => false,
    'path' => 'themes/bluecollar',
  ),
  8 => 
  array (
    'name' => 'Theme Classic',
    'package' => 'classic',
    'version' => '2.4.1-1',
    'type' => 'theme',
    'active' => false,
    'path' => 'themes/classic',
  ),
  9 => 
  array (
    'name' => 'Theme Dynamic',
    'package' => 'dynamic',
    'version' => '2.4.0-3',
    'type' => 'theme',
    'active' => false,
    'path' => 'themes/dynamic',
  ),
  10 => 
  array (
    'name' => 'Theme Elegant',
    'package' => 'elegant',
    'version' => '2.4.0-2',
    'type' => 'theme',
    'active' => false,
    'path' => 'themes/elegant',
  ),
  11 => 
  array (
    'name' => 'Theme Exclusive',
    'package' => 'exclusive',
    'version' => '2.4.0-1',
    'type' => 'theme',
    'active' => false,
    'path' => 'themes/exclusive',
  ),
  12 => 
  array (
    'name' => 'Theme Exclusive for Dashboard',
    'package' => 'exclusive_db',
    'version' => '2.4.0-1',
    'type' => 'theme',
    'active' => false,
    'path' => 'themes/exclusive_db',
  ),
  13 => 
  array (
    'name' => 'Theme Fancy',
    'package' => 'fancy',
    'version' => '2.4.0-1',
    'type' => 'theme',
    'active' => false,
    'path' => 'themes/fancy',
  ),
  14 => 
  array (
    'name' => 'Theme Modern',
    'package' => 'modern',
    'version' => '2.4.0-1',
    'type' => 'theme',
    'active' => false,
    'path' => 'themes/modern',
  ),
  15 => 
  array (
    'name' => 'Theme Newwave',
    'package' => 'newwave',
    'version' => '2.4.0-1',
    'type' => 'theme',
    'active' => false,
    'path' => 'themes/newwave',
  ),
  16 => 
  array (
    'name' => 'Theme Response',
    'package' => 'response',
    'version' => '2.4.0-4',
    'type' => 'theme',
    'active' => false,
    'path' => 'themes/response',
  ),
  18 => 
  array (
    'package' => 'serial_items',
    'name' => 'serial_items',
    'version' => '-',
    'available' => '',
    'type' => 'extension',
    'path' => 'modules/serial_items',
    'active' => false,
  ),
);
