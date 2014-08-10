<?php
/**
 * Generic category module
 * Can be used by any module. Use "categoryType" field to identify modules.
 * 
 * @package Panthera\core\modules\category
 * @author Damian Kęska
 * @license LGPLv3
 */
 
/**
 * Generic category class
 * 
 * @package Panthera\core\modules\category
 * @author Damian Kęska
 * @license LGPLv3
 */

class category extends pantheraFetchDB
{
    protected $_tableName = 'categories';
    protected $_idColumn = 'categoryid';
    protected $_constructBy = array(
        'id', 'categoryid', 'array',
    );
    
    protected $treeID = 'categoryid';
    protected $treeParent = 'parentid';
    
    /**
     * Get categories from specified module ordered by priority
     * 
     * @param string $moduleName Module name
     * @see pantheraFetchDb::fetchAll() for more arguments
     * @author Damian Kęska
     * @return array
     */
    
    public static function getCategories($moduleName)
    {
        $args = func_get_args();
        
        if (!is_array($moduleName))
            $args[0] = array(
                'categoryType' => $moduleName,
            );
            
        if (!$args[3])
            $args[3] = 'priority';
        
        if (!$args[4])
            $args[4] = 'DESC';
        
        return call_user_func_array('self::fetchAll', $args);
    }
    
    public static function categoriesSelectBox($array=null, $depth=0, $result, $lastID=null)
    {
        if (!$array)
        {
            $depth = 0;
            $first = True;
            $array = static::fetchTree();
        }
        
        foreach ($array as $key => $value)
        {
            $str = '';

            if (isset($first))
                $depth = 0;

            if ($lastID)
            {
                if ($lastID -> type_name != $value['item'] -> parent)
                    $depth = 0;
            }

            $depth++;

            if ($depth > 1)
                $str = str_repeat('--', $depth). ' ';
            
            /*if ($this->checkPermissions('can_update_menu_' .$value['item']->type_name, TRUE))
                $result[$value['item']->type_name] = $str.$value['item'] -> title;*/

            if ($value['subcategories'])
                $result = static::categoriesSelectBox($value['subcategories'], $depth, $result, $value['item']);

            $lastID = $value['item'];
        }

        return $result;
    }
}