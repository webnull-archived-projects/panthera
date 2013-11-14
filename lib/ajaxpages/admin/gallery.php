<?php
/**
  * Gallery ajax pages
  *
  * @package Panthera\core\ajaxpages
  * @author Damian Kęska
  * @author Mateusz Warzyński
  * @license GNU Affero General Public License 3, see license.txt
  */

if (!defined('IN_PANTHERA'))
    exit;

// get active locale with override if avaliable
$language = $panthera -> locale -> getFromOverride($_GET['language']);

$panthera -> importModule('simpleImage');
$panthera -> importModule('gallery');
$panthera -> locale -> loadDomain('gallery');

$viewOnly = getUserRightAttribute($user, 'can_read_own_galleries');
$viewOnlyAll = getUserRightAttribute($user, 'can_read_all_gallaries');
$manageAll = getUserRightAttribute($user, 'can_manage_galleries');

/**
  * Save category details
  *
  * @author Damian Kęska
  * @author Mateusz Warzyński 
  */

if ($_GET['action'] == 'saveCategoryDetails')
{
    if (!$manageAll and !getUserRightAttribute($user, 'can_manage_gallery_' .$_GET['id']))
    {
        $noAccess = new uiNoAccess();
        
        $noAccess -> addMetas (array(
            'can_manage_gallery_' .$_GET['id']
        ));
        
        $noAccess -> display();
    }

    $gallery = new galleryCategory('id', intval($_GET['id']));
    $language = $_POST['language'];
    
    // check if this category already exists
    $statement = new whereClause();
    $statement -> add('', 'unique', '=', $gallery->unique);
    $statement -> add('AND', 'language', '=', $language);
    
    // if we change language, we must check if this category exists
    if ($gallery->language != $_POST['language']) {
        $checkCategory = new galleryCategory($statement, null);
    
        if ($checkCategory->exists())
            ajax_exit(array('status' => 'failed', 'message' => localize('Category in this language already exists!', 'gallery')));
    }
    if (!$gallery -> exists())
        ajax_exit(array('status' => 'failed', 'message' => localize('There is no such category', 'gallery')));

    if ($panthera->locale->exists($_POST['language']))
        $gallery -> language = $language;

    if (strlen($_POST['title']) > 0)
        $gallery -> title = $_POST['title'];
    
    if ($_POST['visibility'] == True)
        $gallery->visibility = True;
    else
        $gallery->visibility = False;

    if (isset($_POST['all_langs']))
        $gallery->meta('unique')->set('all_langs', $gallery->id);
    else
        $gallery->meta('unique')->set('all_langs', False);

    $gallery->meta('unique')->save();
    $gallery -> save(); // just to be sure

    ajax_exit(array('status' => 'success', 'language' => $gallery -> language, 'unique' => $gallery -> unique));
}

/**
  * Add selected uploads to gallery
  *
  * @input json {1, 2, 3, 4, 10, 50, 60} - upload id's
  * @author Damian Kęska 
  */

if ($_GET['action'] == 'adduploads')
{
    if (!$manageAll and !getUserRightAttribute($user, 'can_manage_gallery_' .$_GET['gid']))
    {
        $noAccess = new uiNoAccess();
        
        $noAccess -> addMetas (array(
            'can_manage_gallery_' .$_GET['gid']
        ));
        
        $noAccess -> display();
    }

    // here are all files-related functions
    $panthera -> importModule('filesystem');

    // see @input
    $files = json_decode($_POST['ids']);
    $added = 0;

    foreach ($files as $id)
    {
        // get uploaded file
        $file = new uploadedFile('id', $id);

        // validate
        if ($file->exists())
        {
            // accept only images
            if (filesystem::fileTypeByMime($file->mime) != 'image')
            {
                continue;
            }
        
            // add to gallery
            createGalleryItem(basename($file->location), $file->description, pantheraUrl($file->getLink(), True), intval($_GET['gid']), True, $file);
            $added++;
        }
    }

    // return success and simple result
    if ($added > 0)
        ajax_exit(array('status' => 'success', 'count' => $added));

    ajax_exit(array('status' => 'failed'));
}

/**
  * Delete an item from gallery
  *
  * @author Damian Kęska
  * @author Mateusz Warzyński
  */

if ($_GET['action'] == 'deleteItem')
{
    $id = intval($_GET['image_id']);
    
    $item = new galleryItem('id', $id);
    
    // display that there is no access when there is no such item
    if (!$item -> exists())
    {
        $noAccess = new uiNoAccess();
        $noAccess -> display();
    }
    
    // manage all galleries and images, manage selected gallery, manage selected image
    if (!$manageAll and !getUserRightAttribute($user, 'can_manage_gallery_' .$item->getGalleryID()) and !getUserRightAttribute($user, 'can_manage_gimage_' .$id))
    {
        $noAccess = new uiNoAccess;
        $noAccess -> addMetas(array(
            'can_manage_gallery_' .$item->getGalleryID(),
            'can_manage_gimage_' .$id,
            'can_manage_galleries'
        ));
        $noAccess -> display();
    }

    if (gallery::removeImage($id))
        ajax_exit(array('status' => 'success'));
    else
        ajax_exit(array('status' => 'failed', 'error' => localize('Databse error, please refresh the page and try again', 'messages')));
}

/**
  * Remove category from gallery
  *
  * @author Damian Kęska
  * @author Mateusz Warzyński
  */

if ($_GET['action'] == 'deleteCategory')
{
    if (!isset($_GET['id']))
        pa_exit();

    if (strpos($_GET['id'], '.'))
        $id = explode(".", $_GET['id']);
    else
        $id = intval($_GET['id']);
    
    if (is_array($id)) {
        // check permissions
        if ($manageAll) {
            foreach ($id as $i) {
                if (!getUserRightAttribute($user, 'can_manage_gallery_' .$i)) {
                    $noAccess = new uiNoAccess;
            
                    $noAccess -> addMetas(array( 
                        'can_manage_gallery_' .$i
                    ));
                       
                    $noAccess -> display();
                }
            }
        } else {
            $noAccess = new uiNoAccess;
            
            $noAccess -> addMetas(array(
                'can_manage_galleries', 
                'can_manage_gallery_' .$id
            ));
               
            $noAccess -> display();
        }
        
        // remove categories
        foreach ($id as $i) {
            if (!gallery::removeCategory($i))
                $notRemoved = true;
        }
        
        if (isset($notRemoved)) {
            ajax_exit(array('status' => 'success', 'message' => localize("Some categories haven't been deleted!")));
        }
        
        ajax_exit(array('status' => 'success'));
        
    } else {
    
        if (!$manageAll and !getUserRightAttribute($user, 'can_manage_gallery_' .$id))
        {
            $noAccess = new uiNoAccess;
            
            $noAccess -> addMetas(array(
                'can_manage_galleries',
                'can_manage_gallery_' .$id
            ));
            
            $noAccess -> display();
        }

        if (gallery::removeCategory($id))
            ajax_exit(array('status' => 'success'));
        else
            ajax_exit(array('status' => 'failed', 'error' => localize('Unknown error', 'messages')));
    }
}

/**
  * Toggle gallery visibility
  *
  * @author Damian Kęska
  * @author Mateusz Warzyński
  */

if ($_GET['action'] == 'toggleGalleryVisibility')
{
    if (!isset($_GET['ctgid']))
        pa_exit();

    if (strpos($_GET['ctgid'], '.'))
        $id = explode(".", $_GET['ctgid']);
    else
        $id = intval($_GET['ctgid']);
    
    if (is_array($id)) {
        // check permissions
        if ($manageAll) {
            foreach ($id as $i) {
                if (!getUserRightAttribute($user, 'can_manage_gallery_' .$i)) {
                    $noAccess = new uiNoAccess;
            
                    $noAccess -> addMetas(array( 
                        'can_manage_gallery_' .$i
                    ));
                       
                    $noAccess -> display();
                }
            }
        } else {
            $noAccess = new uiNoAccess;
            
            $noAccess -> addMetas(array(
                'can_manage_galleries', 
                'can_manage_gallery_' .$id
            ));
               
            $noAccess -> display();
        }
        
        // get visibility of one category to make the same for all
        $item = new galleryCategory('id', $id[0]);
        $visibility = !(bool)$item->visibility;
        $item -> visibility = $visibility;
        $item -> save();
        unset($id[0]);
        
        // set the same visibility for all categories
        foreach ($id as $i) {
            $item = new galleryCategory('id', $i);
            
            if ($item->exists()) {
                $item -> visibility = $visibility;
                $item -> save();
            }
            
            unset($item);
        }
        
        ajax_exit(array('status' => 'success', 'visible' => $visibility));
        
    } else {
        
        if (!$manageAll and !getUserRightAttribute($user, 'can_manage_gallery_' .$id))
        {
            $noAccess = new uiNoAccess;
                
            $noAccess -> addMetas(array(
                'can_manage_galleries', 
                'can_manage_gallery_' .$id
            ));
               
            $noAccess -> display();
        }
        
        $item = new galleryCategory('id', $id);
        
        if ($item -> exists())
        {
            $item -> visibility = !(bool)$item->visibility;
            $item -> save();
            ajax_exit(array('status' => 'success', 'visible' => $item->visibility));
        } else {
            
        }
    }
    
    ajax_exit(array('status' => 'failed', 'error' => localize('Error!')));
}

/**
  * Toggle image visibility
  *
  * @author Damian Kęska
  * @author Mateusz Warzyński
  */

if (@$_GET['action'] == 'toggleItemVisibility')
{
    if (!isset($_POST['ctgid']))
        pa_exit();

    $id = intval($_POST['ctgid']);
    $item = new galleryItem('id', $id);

    if ($item -> exists())
    {
        if (!$manageAll and !getUserRightAttribute($user, 'can_manage_gallery_' .$item->getGalleryID()) and !getUserRightAttribute($user, 'can_manage_gimage_' .$id))
        {
            $noAccess = new uiNoAccess;
            
            $noAccess -> addMetas(array(
                'can_manage_galleries', 
                'can_manage_gallery_' .$item->getGalleryID(),
                'can_manage_gimage_' .$id
            ));
            
            $noAccess -> display();
        }
    
        $item -> visibility = !(bool)$item->visibility;
        ajax_exit(array('status' => 'success', 'visible' => $item -> visibility));
    } else
        ajax_exit(array('status' => 'failed', 'error' => localize('Item does not exists')));
}

/**
  * Creating a new category
  *
  * @author Damian Kęska
  * @author Mateusz Warzyński
  */

if ($_GET['action'] == 'createCategory')
{
    if (!$manageAll)
    {
        $noAccess = new uiNoAccess;
        
        $noAccess -> addMetas(array(
            'can_manage_galleries'
        ));
            
        $noAccess -> display();
    }

    if (!$_POST['name'] or !isset($_POST['visibility']))
    {
        ajax_exit(array('status' => 'failed', 'message' => localize('Please fill all form fields', 'gallery')));
    }
    
    if (isset($_POST['visibility']))
    {
        if (gallery::createCategory(htmlspecialchars($_POST['name']), $panthera->user->login, $panthera->user->id, $_POST['language'], intval($_POST['visibility']), $panthera->user->full_name))
            ajax_exit(array('status' => 'success'));
        else
            ajax_exit(array('status' => 'failed', 'error' => localize('Unknown error', 'gallery')));
    }
}

/**
  * Display list with gallery items
  *
  * @author Damian Kęska
  * @author Mateusz Warzyński
  */

if ($_GET['action'] == 'displayCategory')
{
    if (!isset($_GET['unique']))
        pa_exit();

    $panthera -> importModule('pager');
    
    $sBar = new uiSearchbar('uiTop');
    //$sBar -> setMethod('POST');
    $sBar -> setQuery($_GET['query']);
    $sBar -> setAddress('?display=gallery&cat=admin&action=displayCategory&unique='.$_GET['unique']);
    $sBar -> navigate(True);
    $sBar -> addSetting('order', localize('Order by', 'search'), 'select', array(
            'id' => array('title' => 'id', 'selected' => ($_GET['order'] == 'id')),
            'title' => array('title' => localize('Title', 'gallery'), 'selected' => ($_GET['order'] == 'title')),
            'description' => array('title' => localize('Description', 'gallery'), 'selected' => ($_GET['order'] == 'description'))
        ));
    $sBar -> addSetting('direction', localize('Direction', 'search'), 'select', array(
            'ASC' => array('title' => localize('Ascending', 'search'), 'selected' => ($_GET['direction'] == 'ASC')),
            'DESC' => array('title' => localize('Descending', 'search'), 'selected' => ($_GET['direction'] == 'DESC'))
        ));
    // $sBar->addIcon( '{$PANTHERA_URL}/images/admin/ui/permissions.png', '#', '?display=acl&cat=admin&popup=true&name=can_manage_galleries', localize( 'Manage permissions' ) );
    
    // query for a page using `unique` and `language` columns
    $statement = new whereClause();
    $statement -> add('', 'unique', '=', $_GET['unique']);
    $statement -> add('AND', 'language', '=', $language);
    
    $category = new galleryCategory($statement, null);

    if (!$category->exists())
    {
        if (!$manageAll) {
             $noAccess = new uiNoAccess;
        
            $noAccess -> addMetas(array(
                'can_manage_galleries',
                'can_manage_gallery_' .$ctg->id
            ));
                
            $noAccess -> display();
        }
        
        $ctg = new galleryCategory('unique', $_GET['unique']);

        if ($ctg -> exists())
        {
            if ($ctg->meta('unique')->get('all_langs') != intval($category->id))
            {
                $newID = $ctg->meta('unique')->get('all_langs');
                $category = new galleryCategory('id', $newID);
                unset($ctg);
            } else {
            
                // create a category in a new language
                if (!$manageAll)
                {
                    $noAccess = new uiNoAccess;
                    
                    $noAccess -> addMetas(array(
                        'can_manage_galleries'
                    ));
                        
                    $noAccess -> display();
                }

                gallery::createCategory($ctg->title, $panthera->user->login, $panthera->user->id, $language, 0, $panthera->user->full_name, $ctg->unique);
                $statement = new whereClause();
                $statement -> add('', 'unique', '=', $_GET['unique']);
                $statement -> add('AND', 'language', '=', $language);
                $category = new galleryCategory($statement, null);
                $category -> thumb_url = $ctg->thumb_url;
                $category -> thumb_id = $ctg->thumb_id;
                unset($ctg);
            }
        }
    }

    $sBar->addIcon( '{$PANTHERA_URL}/images/admin/ui/permissions.png', '#', '?display=acl&cat=admin&popup=true&name=can_manage_gallery_'.$category->id, localize( 'Manage permissions' ) );
    
    $author = $category->getAuthor();
    
    if (!$manageAll and !getUserRightAttribute($user, 'can_manage_gallery_' .$category->id) and !($author['id'] == $panthera->user->id or $viewOnlyAll))
    {
        $noAccess = new uiNoAccess;
        
        $noAccess -> addMetas(array(
            'can_manage_galleries',
            'can_manage_gallery_' .$ctg->id
        ));
            
        $noAccess -> display();
    }

    // check language
    if (intval($category->meta('unique')->get('all_langs')) > 0)
    {
        if (intval($category->meta('unique')->get('all_langs')) != intval($category->id))
        {
            // load other category which is marked as for all languages
            $ctg = new galleryCategory('id', $category->meta('unique')->get('all_langs'));

            // replace only if new category exists
            if ($ctg->exists())
                $category = $ctg;
         }
    }
    
    $order = 'id'; $orderColumns = array('id', 'title', 'description');
    $direction = 'DESC';

    // order by
    if (in_array($_GET['order'], $orderColumns))
    {
        $order = $_GET['order'];
    }
    
    // direction    
    if ($_GET['direction'] == 'DESC' or $_GET['direction'] == 'ASC')
    {
        $direction = $_GET['direction'];
    }
    
    $w = new whereClause();
    $w -> add( 'AND', 'gallery_id', '=', $category->id);
        
    if ($_GET['query'])
    {
        $_GET['query'] = trim(strtolower($_GET['query'])); // strip unneeded spaces and make it lowercase
        if ($order != 'id')
            $w -> add( 'AND', $order, 'LIKE', '%' .$_GET['query']. '%');
        else
            $w -> add( 'AND', $order, '=', $_GET['query']);
    }
    
    // if does not exists in cache
    if (!isset($totalItems))
    {
        $totalItems = getGalleryItems($w, False, False, $order);
    }

    // get gallery items
    $page = $_GET['page'];
    
    $uiPager = new uiPager('adminGalleryItems', $totalItems, 'adminGalleryItems', 16);
    $uiPager -> setActive($page);
    $uiPager -> setLinkTemplates('#', 'navigateTo(\'?' .getQueryString($_GET, 'page={$page}', '_'). '\');');
    $limit = $uiPager -> getPageLimit();
    
    $items = getGalleryItems($w, $limit[1], $limit[0], $order, $direction);

    $template -> push('category_title', $category->title);
    $template -> push('category_id', $category->id);
    $template -> push('item_list', $items);
    $template -> push('category_language', $category->language);
    $template -> push('unique', $_GET['unique']);
    $template -> push('languages', $panthera->locale->getLocales());
    $template -> push('galleryObject', $category);

    if (intval($category->meta('unique')->get('all_langs')) > 0)
        $template -> push('all_langs', True);

    // get custom styles for gallery in both languages and for gallery in single language
    $header = $category->meta('unique')->get('site_header');

    if ($category->meta('id')->get('site_header') != null)
        $header = array_merge($header, $category->meta('unique')->get('site_header'));

    // add custom styles and scripts
    if (count($header) > 0)
    {
        if (count($header['scripts']) > 0)
        {
            foreach ($header['scripts'] as $key => $value)
                $panthera -> template -> addScript($value);
        }

        if (count($header['styles']) > 0)
        {
            foreach ($header['styles'] as $key => $value)
                $panthera -> template -> addStyle($value);
        }
    }

    if ($category->visibility)
        $visibility = localize("visible", 'gallery');
    else
        $visibility = localize("invisible", 'gallery');

    $titlebar = new uiTitlebar($category->title . " (".$category->language.", ".$visibility.")");
	$titlebar -> addIcon('{$PANTHERA_URL}/images/admin/menu/gallery.png', 'left');
    
    $template -> push('category_visibility', $category->visibility);
    
    $template -> display('gallery_displaycategory.tpl');
    pa_exit();
}

/**
  * Edit item form
  *
  * @author Damian Kęska
  * @author Mateusz Warzyński
  */

if ($_GET['action'] == 'edit_item_form')
{
    $panthera -> importModule('filesystem');
    $template -> push('action', 'edit_item');

    if ($_GET['subaction'] == 'edit_item')
    {
        $item = new galleryItem('id', $_GET['id']);
        $_POST['upload_id'] = intval($_POST['upload_id']);
        
        if (!$manageAll and !getUserRightAttribute($user, 'can_manage_gimage_' .$id) and !getUserRightAttribute($user, 'can_manage_gallery_' .$item->getGalleryID()))
        {
            $noAccess = new uiNoAccess;
            
            $noAccess -> addMetas(array(
                'can_manage_galleries',
                'can_manage_gimage_' .$id,
                'can_manage_gallery_' .$item->getGalleryID()
            ));
            
            $noAccess -> display();
        }

        if ($item -> exists())
        {
            $file = new uploadedFile('id', $_POST['upload_id']);

            if (!$file -> exists())
                ajax_exit(array('status' => 'failed', 'message' => localize('Selected file doesnt exists in upload list', 'gallery')));

            if ($_POST['visibility'] == '1')
                $item -> visibility = 0;
            else
                $item -> visibility = 1;

            $item -> title = filterInput($_POST['title'], 'quotehtml');
            $item -> description = filterInput($_POST['description'], 'quotehtml');
            $item -> link = pantheraUrl($file->getLink());
            $item -> thumbnail = $file->getThumbnail($panthera->config->getKey('gallery_thumbs_width', 240, 'int'), True);
            $item -> upload_id = $_POST['upload_id'];

            $category = new galleryCategory('id', $_POST['gallery_id']);

            if ($category -> exists())
                $item -> gallery_id = $_POST['gallery_id'];

            ajax_exit(array('status' => 'success', 'unique' => $item->unique));
        } else
            ajax_exit(array('status' => 'failed', 'error' => localize('Error with changing item!')));
    }

    $id = intval($_GET['itid']);
    $item = new galleryItem('id', $id);

    if ($item -> exists())
    {
        if (!$manageAll and !getUserRightAttribute($user, 'can_manage_gimage_' .$id) and !getUserRightAttribute($user, 'can_manage_gallery_' .$item->getGalleryID()))
        {
            $noAccess = new uiNoAccess;
            
            $noAccess -> addMetas(array(
                'can_manage_galleries',
                'can_manage_gimage_' .$id,
                'can_manage_gallery_' .$item->getGalleryID()
            ));
            
            $noAccess -> display();
        }
    
        $template -> push('id', $item -> id);
        $template -> push('title', $item -> title);
        $template -> push('description', $item -> description);
        $template -> push('link', pantheraUrl($item -> link));
        $template -> push('thumbnail', pantheraUrl($item -> thumbnail)); // We haven't script to make thumbnails yet. Later we'll delete this line. /M.
        $template -> push('gallery_id', $item -> gallery_id);
        $template -> push('visibility', !$item -> visibility);
        $template -> push('upload_id', $item -> upload_id);

        $c = gallery::fetch('');
        $template -> push('category_list', $c);

        $category = new galleryCategory('id', $item->gallery_id);
        $template -> push('unique', $category->unique);
        $template -> push('language_item', $category->language);

		$titlebar = new uiTitlebar(localize('Editing gallery image', 'gallery'));
		$titlebar -> addIcon('{$PANTHERA_URL}/images/admin/menu/gallery.png', 'left');
		
        $template -> display('gallery_edititem.tpl');
        pa_exit();
    } else {
        pa_exit();
    }
}

/**
  * Adding item form
  *
  * @author Mateusz Warzyński
  * @author Damian Kęska
  */

if (@$_GET['action'] == 'add_item') 
{
   if ($_GET['subaction'] == 'add') 
   {
        $panthera -> importModule('filesystem');

        if ($_POST['title'] and $_POST['gallery_id'] and $_POST['upload_id'])   
        {
            // validate input
            $_POST['title'] = filterInput($_POST['title'], 'quotehtml');
            $_POST['description'] = filterInput($_POST['description'], 'quotehtml');
            $uploadID = intval($_POST['upload_id']);
            $visibility = intval((bool)intval($_POST['visibility']));
            $galleryID = intval($_POST['gallery_id']);
            
            // check permissions
            if (!$manageAll and !getUserRightAttribute($user, 'can_manage_gallery_' .$galleryID))
            {
                $noAccess = new uiNoAccess;
                    
                $noAccess -> addMetas(array(
                    'can_manage_galleries',
                    'can_manage_gallery_' .$galleryID
                 ));
                    
                $noAccess -> display();
            }

            // validate category            
            $category = new galleryCategory('id', $galleryID);
            
            if (!$category->exists())
            {
                ajax_exit(array('status' => 'failed', 'message' => localize('Cannot find destination category you want to save image to', 'gallery')));
            }
            
            // check if uploaded file exists
            $file = new uploadedFile('id', $uploadID);

            if (!$file -> exists())
            {
                ajax_exit(array('status' => 'failed', 'message' => localize('Selected file does not exists in list of uploaded files', 'gallery')));
            }
            
            $link = pantheraUrl($file->getLink(), True);

            if (createGalleryItem($_POST['title'], $_POST['description'], $link, $galleryID, $visibility, $file))
            {
                ajax_exit(array('status' => 'success', 'ctgid' => $galleryID));
            } else {
                ajax_exit(array('status' => 'failed', 'message' => localize('Database error, please refresh this page and try again', 'messages')));
            }
            
        } else {
            ajax_exit(array('status' => 'failed', 'message' => localize('Please fill all form inputs', 'gallery')));
        }

        pa_exit();
    }
    
    $id = intval($_GET['ctgid']);
    
    if (!$manageAll and !getUserRightAttribute($user, 'can_manage_gallery_' .$id))
    {
        $noAccess = new uiNoAccess;
                    
        $noAccess -> addMetas(array(
            'can_manage_galleries',
            'can_manage_gallery_' .$id
        ));
                    
        $noAccess -> display();
    }

    $template -> push('action', 'add_item');
    $c = gallery::fetch('');

    $category = new galleryCategory('id', $id);

    if ($category -> exists())
    {
        $template -> push('category_list', $c);
        $template -> push('category_id', $_GET['ctgid']);
        $template -> push('gallery_name', $category->title);
        $template -> push('unique', $category->unique);
        $template -> push('language_item', $category->language);
			
        $titlebar = new uiTitlebar(localize('Adding gallery image', 'gallery'));
        $titlebar -> addIcon('{$PANTHERA_URL}/images/admin/menu/gallery.png', 'left');
			
        $template -> display('gallery_additem.tpl');
        pa_exit();
    }

}

/**
  * Creating new category
  *
  * @author Mateusz Warzyński
  * @author Damian Kęska
  */

if ($_GET['action'] == 'add_category')
{
    // check user rights
    if (!$manageAll)
    {
        $noAccess = new uiNoAccess;
                    
        $noAccess -> addMetas(array(
            'can_manage_galleries'
        ));
                    
        $noAccess -> display();
    }

    if ($_GET['new_title'])
    {
        gallery::createCategory($_GET['filter'].$_GET['new_title'], $user->login, $user->id, $user->language, intval($_GET['visibility']), $user->full_name, md5(rand(999, 9999)));
        ajax_exit(array('status' => 'success'));
    } else {
        ajax_exit(array('status' => 'failed', 'error' => localize('Title cannot be empty', 'gallery')));
    }
}

/**
  * Setting gallery thumbnail from gallery image
  *
  * @author Mateusz Warzyński
  * @author Damian Kęska
  */

if ($_GET['action'] == 'set_category_thumb')
{
    $id = intval($_GET['itid']);
    $ctgid = intval($_GET['ctgid']);

    // check user rights
    if (!$manageAll and !getUserRightAttribute($user, 'can_manage_gallery_' .$ctgid))
    {
        $noAccess = new uiNoAccess;
                    
        $noAccess -> addMetas(array(
            'can_manage_galleries',
            'can_manage_gallery_' .$ctgid
        ));
                    
        $noAccess -> display();
    }

    $item = new galleryItem('id', $id);
    $category = new galleryCategory('id', $ctgid);

    if ($item -> exists() and $category -> exists())
    {
         $category -> thumb_id = $item -> id;
         $category -> thumb_url = $item -> link;
         ajax_exit(array('status' => 'success'));
    } else {
         ajax_exit(array(
            'status' => 'failed',
            'error' => localize('Error with changing gallery thumbnail!', 'gallery')
         ));
    }
}

/**
  * Editing category title and visibility
  *
  * @author Mateusz Warzyński
  * @author Damian Kęska
  */

if ($_GET['action'] == 'edit_category')
{
    $id = intval($_GET['ctgid']);

    if (!$manageAll and !getUserRightAttribute($user, 'can_manage_gallery_' .$id))
    {
        $noAccess = new uiNoAccess;
                    
        $noAccess -> addMetas(array(
            'can_manage_galleries',
            'can_manage_gallery_' .$id
        ));
                    
        $noAccess -> display();
    }


    $item = new galleryCategory('id', $id);

    if ($item -> exists())
    {
        $response = array('status' => 'success');

        if (isset($_GET['new_title']) and $_GET['new_title'] != '') 
        {
            $item -> title = filterInput($_GET['new_title'], 'quotehtml');
            $response['title'] = filterInput($_GET['new_title'], 'quotehtml');
            
        } else {
            ajax_exit(array(
                'status' => 'failed',
                'error' => localize("Title can't be empty", 'gallery')
            ));
        }

        if (isset($_GET['visibility']))
        {

            if ($_GET['visibility'] == 'show')
            {
                $item -> visibility = True;
                $response['visibility'] = 'show';
            } else {
                $item -> visibility = False;
                $response['visibility'] = 'hide';
            }
        }

        ajax_exit($response);

    } else {
          ajax_exit(array('status' => 'failed', 'error' => localize('Category does not exists')));
          pa_exit();
    }
    pa_exit();
}

// here we will store query and other filter params
$filter = array();

$sBar = new uiSearchbar('uiTop');
//$sBar -> setMethod('POST');
$sBar -> setQuery($_GET['query']);
$sBar -> setAddress('?' .getQueryString('GET', '', array('_', 'page', 'query')));
$sBar -> navigate(True);
$sBar -> addIcon('{$PANTHERA_URL}/images/admin/ui/permissions.png', '#', '?display=acl&cat=admin&popup=true&name=can_manage_galleries,can_read_own_galleries,can_read_all_galleries', localize('Manage permissions'));

// only in selected language
if ($_GET['lang']) 
{
    $filter['language'] = $_GET['lang'];
    $template -> push('current_lang', $_GET['lang']);
}

// search query
if ($_GET['query'])
{
    $filter['title*LIKE*'] = '%' .trim(strtolower($_GET['query'])). '%';
}

$page = intval($_GET['page']);
$itemsCount = gallery::fetch($filter, False);

// pager
$uiPager = new uiPager('galleryCategories', $itemsCount, 'adminGalleryCategories');
$uiPager -> setActive($page);
$uiPager -> setLinkTemplates('#', 'navigateTo(\'?' .getQueryString('GET', 'page={$page}', '_'). '\');');
$limit = $uiPager -> getPageLimit();

// get categories for current page
$categories = gallery::fetch($filter, $limit[1], $limit[2]);

// with title filter
$categoriesFiltered = array();

foreach ($categories as $category)
{
    if ($_GET['filter'] != '')
    {
        if (!stristr($category->title, $_GET['filter']))
            continue;
    }

    // create an array with information about categories
    if (isset($categoriesFiltered[$category->unique])) {
        $categoriesFiltered[$category->unique]['langs'] = $categoriesFiltered[$category->unique]['langs'].', '.$category->language;
        $categoriesFiltered[$category->unique]['ids'] = $categoriesFiltered[$category->unique]['ids'].'.'.$category->id;
        
        if ($category->language == 'english')
            $categoriesFiltered[$category->unique]['language'] = 'english';
        
        if (!$categoriesFiltered[$category->unique]['visibility_all'] and (bool)$category->visibility)
            $categoriesFiltered[$category->unique]['visibility_all'] = (bool)$category->visiblity;
        
    } else {
        $categoriesFiltered[$category->unique] = $category->getData();
        $categoriesFiltered[$category->unique]['langs'] = $category->language;
        $categoriesFiltered[$category->unique]['ids'] = $category->id;
        $categoriesFiltered[$category->unique]['visibility_all'] = (bool)$category->visibility;
    }
}

if (defined('GALLERY_FILTER'))
{
    $template -> push('category_filter', $_GET['filter'].GALLERY_FILTER);
    $template -> push('category_filter_complete', $_GET['filter'].GALLERY_FILTER);
} else
    $template -> push('category_filter', $_GET['filter']);

$template -> push('category_list', $categoriesFiltered);
$template -> push('set_locale', $panthera -> locale -> getActive());

$titlebar = new uiTitlebar(localize('Gallery'));
$titlebar -> addIcon('{$PANTHERA_URL}/images/admin/menu/gallery.png', 'left');
$panthera -> template -> display('gallery.tpl');
pa_exit();
