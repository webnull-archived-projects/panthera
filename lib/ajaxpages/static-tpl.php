<?php
/**
  * Displays static templates
  *
  * @package Panthera\core\ajaxpages
  * @author Damian Kęska
  * @author Mateusz Warzyński
  * @license GNU Affero General Public License 3, see license.txt
  */

if (!defined('IN_PANTHERA'))
    exit;

if (!checkUserPermissions($user, True))
{
    $template->display('no_access.tpl');
    $panthera->finish();
    pa_exit();
}

$template -> display($_GET['name']. '.tpl');
pa_exit();