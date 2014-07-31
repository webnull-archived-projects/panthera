<?php
/**
  * Simple menu module allows generating lists of menus, storing them in databases
  *
  * @package Panthera\core\modules\simplemenu
  * @author Damian Kęska
  * @author Mateusz Warzyński
  * @license LGPLv3
  */

if (!defined('IN_PANTHERA'))
    exit;

/**
  * Simple menu module allows generating lists of menus, storing them in databases
  *
  * @package Panthera\core\modules\simplemenu
  * @author Damian Kęska
  * @author Mateusz Warzyński
  */

class simpleMenu extends pantheraClass
{
    protected $_menu = array();
    protected $active;
    protected $cache = 0;

    /**
     * Constructor
     *
     * @param string $category Category name to load from database
     */

    public function __construct($category=null)
    {
        parent::__construct();

        if ($category)
            $this -> loadFromDB($category);

        // configure caching
        if ($this -> panthera->cacheType('cache') == 'memory' and $this -> panthera->db->cache > 0)
            $this->cache = $this -> panthera->db->cache;
    }

    /**
      * Load records from database
      *
      * @param string $category Category name
      * @return bool
      * @author Damian Kęska
      */

    public function loadFromDB($category)
    {
        $rows = -1;

        // get from cache
        if ($this->cache > 0)
        {
            if ($this->panthera->cache->exists('menu.' .$category))
            {
                $this->panthera->logging->output('Loaded menu from cache id=menu.' .$category, 'simpleMenu');
                $rows = $this->panthera->cache->get('menu.'.$category);
            }
        }

        // if the cache was empty
        if ($rows === -1)
        {
            $SQL = $this->panthera->db->query('SELECT * FROM `{$db_prefix}menus` WHERE `type`= :type ORDER BY `order` DESC;', array('type' => $category));
            $count = $SQL -> rowCount();
        }


        if ($rows == -1)
        {
            $rows = $SQL -> fetchAll();

            if ($this->cache > 0)
            {
                $this->panthera->logging->output('Saving menu to cache id=menu'.$category, 'simpleMenu');
                $this->panthera->cache->set('menu.'.$category, $rows, $this->cache);
            }
        }
        
        if (count($rows) > 0)
        {
            foreach ($rows as $row)
            {
                if (!is_array($attr))
                    $attr = array('active' => False);

                // set link as active
                if ($this->active == $row['url_id'])
                    $attr['active'] = True;
                else
                    $attr['active'] = False;

                $row['link'] = pantheraUrl($row['link'], false, 'frontend');

                $this -> add($row['url_id'], $row['title'], $row['link'], $row['attributes'], $row['icon'], $row['tooltip'], $row['route'], $row['routedata'], $row['routeget'], $row['enabled']);
            }

            return True;
        }
    }

    /**
      * Set item as active
      *
      * @param string $item Index of item (id)
      * @return bool
      * @author Damian Kęska
      */

    public function setActive($item)
    {
        $this->active = (string)$item;
        return True;
    }

    /**
      * Add new item to menu
      *
      * @param string $item Index of item (id)
      * @param string $title Title
      * @param string $link URL Address
      * @param array $attributes Array of attributes (optional)
      * @param string $icon Link to icon (optional)
      * @param string $tooltip Tooltip text (optional)
      * @return void
      * @author Damian Kęska
      */

    public function add($item, $title, $link, $attributes='', $icon='', $tooltip='', $route='', $routeData='', $routeGET='', $enabled=true)
    {
        if (!intval($enabled))
            return False;

        if ($route)
        {
            try {
                if (!is_array($routeData))
                    $routeData = unserialize($routeData);

                $link = $this -> panthera -> routing -> generate($route, $routeData, $routeGET);
            } catch (Exception $e) {
                $this -> panthera -> logging -> output('Cannot add menu item, routing generate failed: ' .$e->getMessage(), 'simpleMenu');
            }
        }

        $this->_menu[(string)$item] = array(
            'link' => $link,
            'name' => $item,
            'title' => $title,
            'attributes' => $attributes,
            'icon' => $icon,
            'tooltip' => $tooltip,
            'route' => $route,
            'routedata' => $routeData,
            'routeget' => $routeGET,
            'enabled' => $enabled,
        );

        return True;
    }

    /**
      * Get item by index
      *
      * @param string $item Index of item (id)
      * @return array
      * @author Damian Kęska
      */

    public function getItem($item)
    {
        if (array_key_exists($item, $this->_menu))
            return $this->_menu[$item];
    }

    /**
      * Show generated menu
      *
      * @return array
      * @author Damian Kęska
      */

    public function show()
    {
        return $this->_menu;
    }

    /**
      * Clear the menu, remove all elements
      *
      * @return void
      * @author Damian Kęska
      */

    public function clear()
    {
        $this->_menu = array();
    }

    // ==== STATIC FUNCTIONS ====

    /**
     * Get all menu items by `type_name`, $limitTo, $limitFrom, $orderBy = 'order', $order = 'DESC' (descending)
     *
     * @return array
     * @author Damian Kęska
     */

    public static function getItems($menu, $limit=0, $limitFrom=0, $orderBy='order', $order='DESC')
    {
        $panthera = pantheraCore::getInstance();
        return $panthera->db->getRows('menus', array('type' => $menu), $limit, $limitFrom, '', $orderBy, $order);
    }

    /**
     * Create menu item
     *
     * @return pantheraUser
     * @author Damian Kęska, Mateusz Warzyński
     */

    public static function createItem($type, $title, $attributes, $link, $language='all', $url_id='', $order=1, $icon='', $tooltip='', $route='', $routeData='', $routeGET='', $enabled=False)
    {
        $panthera = pantheraCore::getInstance();

        if (!$language)
            $language = 'all';

        if (!$panthera -> locale -> exists($language) and $language != 'all' and $language)
            throw new Exception('Cannot create item in unknown language', 1339);

        if ($routeData and !is_array($routeData))
            throw new Exception('Invalid route data: non empty but not an array', 1340);
        
        if (is_array($routeGET))
            parse_str($routeGET, $routeGET);

        $SQL = $panthera -> db -> insert('menus', array(
            'type' => $type,
            'order' => $order,
            'title' => $title,
            'attributes' => $attributes,
            'link' => $link,
            'language' => $language,
            'url_id' => $url_id,
            'icon' => $icon,
            'tooltip' => $tooltip,
            'route' => $route,
            'routedata' => serialize($routeData),
            'routeget' => $routeGET,
            'enabled' => (bool)$enabled,
        ));

        // clear menu cache
        if ($panthera->cache)
            $panthera->cache->remove('menu.'.$type);

        return $SQL;
    }

    /**
     * Remove menu item
     *
     * @return bool
     * @author Mateusz Warzyński
     */

    public static function removeItem($id)
    {
        $panthera = pantheraCore::getInstance();

        // reload menu cache
        if ($panthera->cache)
        {
            $SQL = $panthera -> db -> query('SELECT `type` FROM `{$db_prefix}menus` WHERE `id` = :id', array('id' => $id));
            $fetch = $SQL -> fetch(PDO::FETCH_ASSOC);

            if ($fetch)
            {
                $panthera->cache->remove('menu.'.$fetch['type']);
            }
        }

        $SQL = $panthera -> db -> query('DELETE FROM `{$db_prefix}menus` WHERE `id` = :id', array('id' => $id));
        return (bool)$SQL->rowCount();
    }

    /**
     * Create menu category
     *
     * @return bool
     * @author Mateusz Warzyński
     */

    public static function createCategory($type_name, $title, $description, $parent, $elements)
    {
        $panthera = pantheraCore::getInstance();
        $SQL = $panthera->db->query('INSERT INTO `{$db_prefix}menu_categories` (`id`, `type_name`, `title`, `description`, `parent`, `elements`) VALUES (NULL, :type_name, :title, :description, :parent, :elements);', array('type_name' => $type_name, 'title' => $title, 'description' => $description, 'parent' => $parent, 'elements' => $elements));
        return (bool)$SQL->rowCount();
    }


    /**
     * Remove menu category
     *
     * @return bool
     * @author Mateusz Warzyński
     */

    public static function removeCategory($id)
    {
        $panthera = pantheraCore::getInstance();
        $SQL = $panthera -> db -> query('DELETE FROM `{$db_prefix}menu_categories` WHERE `id` = :id', array('id' => $id));
        return (bool)$SQL->rowCount();
    }

    /**
      * Update category elements counter
      *
      * @param string $categoryName eg. admin
      * @return void
      * @author Damian Kęska
      */

    public static function updateItemsCount($categoryName)
    {
        $panthera = pantheraCore::getInstance();
        $SQL = $panthera -> db -> query ('SELECT count(*) FROM `{$db_prefix}menus` WHERE `type` = :type', array('type' => $categoryName));
        $fetch = $SQL -> fetch(PDO::FETCH_ASSOC);
        $panthera -> db -> query('UPDATE `{$db_prefix}menu_categories` SET `elements` = :elements WHERE `type_name` = :categoryName', array('elements' => $fetch['count(*)'], 'categoryName' => $categoryName));
    }
}

/**
 * Simple menu category class
 *
 * @package Panthera\modules\core\simplemenu
 * @author Damian Kęska
 * @author Mateusz Warzyński
 */

class menuCategory extends pantheraFetchDB
{
    protected $_tableName = 'menu_categories';
    protected $_idColumn = 'id';
    protected $_constructBy = array(
        'id', 'type_name', 'array',
    );
    protected $treeID = 'type_name';
    protected $treeParent = 'parent';
}

/**
 * Simple menu item class
 *
 * @package Panthera\modules\core\simplemenu
 * @author Damian Kęska
 * @author Mateusz Warzyński
 */

class menuItem extends pantheraFetchDB
{
    protected $_tableName = 'menus';
    protected $_idColumn = 'id';
    protected $_constructBy = array(
        'id', 'url_id', 'title',
    );
}