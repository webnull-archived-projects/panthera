<?php
/**
  * Admin Panel front controller
  *
  * @package Panthera\core
  * @author Damian Kęska
  * @license GNU Affero General Public License 3, see license.txt
  */

require 'content/app.php';

if (!checkUserPermissions($user))
    pa_redirect('pa-login.php');

$panthera -> template -> setTemplate('admin');
$panthera -> template -> setTitle($panthera -> config -> getKey('site_title', 'Panthera', 'string'));

if (!isset($_GET['display']))
    $_GET['display'] = 'dash';

if ($_SERVER['QUERY_STRING'] != '')
    $panthera -> template -> push ('navigateTo', $_SERVER['QUERY_STRING']);

$panthera -> importModule('simpleMenu');

// build a menu
$menu = new simpleMenu();
$menu -> add('dash', localize('Dash'), '?display=dash', '', '', '');

// other built-in pages
if (getUserRightAttribute($user, 'can_see_debug'))
    $menu -> add('debug', localize('Debugging center'), '?display=debug', '', '', '');

$menu -> add('users', localize('Users'), '?display=settings&action=users', '', '', '');

// end of built-in pages
$menu -> loadFromDB('admin');

// allow plugins modify admin menu
$panthera -> get_options('admin_menu', $menu);

// set current active menu (optional)
$menu -> setActive(@$_GET['display']);

$panthera -> template -> push('admin_menu', $menu->show());
$panthera -> template -> push('display_page', $_GET['display']);
$panthera -> template -> push('query_string', $_SERVER['QUERY_STRING']);
$panthera -> template -> display();
$panthera -> finish();
?>