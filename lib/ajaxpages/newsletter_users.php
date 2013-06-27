<?php
/**
  * Show and manage newsletter users
  *
  * @package Panthera
  * @subpackage core
  * @copyright (C) Damian Kęska, Mateusz Warzyński
  * @license GNU Affero General Public License 3, see license.txt
  */

if (!defined('IN_PANTHERA'))
    exit;

if (!getUserRightAttribute($panthera->user, 'can_manage_newsletter_users')) {
    $panthera->template->display('no_access.tpl');
    pa_exit();
}

// import /lib/modules/newsletter.module.php file to access its classes and functions
$panthera -> importModule('newsletter');

// GET newsletter by `nid` (from GET parameter)
$newsletter = new newsletter('nid', $_GET['nid']);

// exit if newsletter does not exists (exists method is a built-in method of pantheraFetchDB's abstract class)
if (!$newsletter->exists())
{
    $panthera -> template -> push('error_message', localize('Selected newsletter does not exists'));
    $panthera -> template -> display('_ajax_admin_error.tpl');
    pa_exit();
}

// removing subscriber
if ($_GET['action'] == 'remove_subscriber')
{
    if(newsletterManagement::removeSubscriber($_POST['id']))
        ajax_exit(array('status' => 'success'));

    ajax_exit(array('status' => 'failed', 'messsage' => localize('Cannot find subscriber')));
}

$panthera -> template -> push ('nid', $_GET['nid']);

if ($_GET['action'] == 'show_table')
    $panthera -> template -> push ('action', 'show_table');
else
    $panthera -> template -> push ('action', '');

// get count of newsletter users
$usersCount = $newsletter -> getUsers(False); // false means we dont want to get records but it's count

// show error information that there are no users in current newsletter
if ($usersCount == 0)
{
    $panthera -> template -> push ('error_message', localize('There no any users subscribing this newsletter'));
    $panthera -> template -> display('error.tpl');
    pa_exit();
}

$page = intval($_GET['pagenum']);

// pages are only > -1 (we are counting from 0, so the real page is page-1 means page 1 is 0 in code)
if ($page < 0)
    $page = 0;

// get records only for current page
$pager = new Pager($usersCount, $panthera->config->getKey('pager_newsletter', 25, 'int'));
$pager -> maxLinks = 6; // max links in pager
$limit = $pager -> getPageLimit($page);

$panthera -> template -> push('pager', $pager->getPages($page));
$panthera -> template -> push('page_from', $limit[0]);
$panthera -> template -> push('page_to', $limit[1]);

// get all users from current page
$users = $newsletter -> getUsers($limit[0], $limit[1]);

if (count($users) == 0)
{
    $panthera -> template -> push ('error_message', localize('There no any users subscribing this newsletter'));
    $panthera -> template -> display('error.tpl');
    pa_exit();
}

$panthera -> template -> push ('newsletter_users', $users);
$panthera -> template -> display('newsletter_users.tpl');
pa_exit();
?>