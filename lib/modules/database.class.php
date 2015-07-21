<?php
namespace Panthera\database;

class driver extends \Panthera\baseClass
{
    public static $object = null;

    public $functions = array(

    );

    /**
     * Get database driver singleton instance
     *
     * @param string $databaseType Database handler name
     * @param bool|false $force Force create new object, or use existing
     *
     * @throws \Panthera\FileNotFoundException
     * @throws \Panthera\PantheraFrameworkException
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return object
     */
    public static function getDatabaseInstance($databaseType, $force = false)
    {
        if (is_object(self::$object) && !$force)
        {
            return self::$object;
        }

        $framework = \Panthera\framework::getInstance();
        $path = $framework->getPath('modules/databaseHandlers/' .$databaseType. 'DatabaseHandler.class.php');
        $className = '\\Panthera\\database\\' .$databaseType. 'DatabaseHandler';

        require_once $path;

        if (!in_array('Panthera\database\databaseHandlerInterface', class_implements($className)))
        {
            throw new \Panthera\PantheraFrameworkException('Database handler "' .$className. '" have to implement "databaseHandlerInterface" interface', 'FW_INVALID_DRIVER');
        }

        self::$object = new $className;
        self::$object->connect();

        return self::$object;
    }

    /**
     * Parse "WHERE" condition block - from PHP array to SQL string
     *
     * Example of input:
     * array(
     *   array(
     *      '|=|name' => 'test',
     *      '|AND|=|title' => 'aaa',
     *   ),
     *
     *   '|OR|.test' => array(
     *      array(
     *          '|=|a' => 'other test',
     *          '|OR|b' => 'other title',
     *      ),
     *
     *      '|AND|.' => array(
     *          '|[]|unique' => array(
     *              'first', 'second', 'third',
     *          )
     *      ),
     *   ),
     * );
     *
     * @param array $whereCondition The condition
     * @param string|null $columnNamePrefix Optional prefix to add to every column name (in case column don't have any)
     *
     * @throws PantheraFrameworkException
     * @return array
     */

    public function parseWhereConditionBlock($whereCondition, $columnNamePrefix = null)
    {
        $output = '';
        $values = array();
        $logicOperatorAllowed = false;

        foreach ($whereCondition as $condition => $value)
        {
            $splitted = explode('|', $condition);
            $len = count($splitted);

            if ($len > 4 && !is_numeric($condition))
            {
                throw new PantheraFrameworkException('Where conditions could have only maximum 3 blocks, given ' .count($splitted). ', details: "' .$condition. '"', 'FW_SQL_CONDITIONS_INVALID');
            }

            /**
             * Step 1: Logic operators - AND/OR
             */
            if ($logicOperatorAllowed)
            {
                $logicOperator = 'AND';

                if (!is_numeric($condition))
                {
                    for ($i = 1; $i <= 2; $i++)
                    {
                        if ($splitted[$i] == 'OR' || $splitted[$i] == 'AND' || $splitted[$i] == 'XOR')
                        {
                            $logicOperator = $splitted[1];
                            break;
                        }
                    }
                }

                $output .= ' ' .$logicOperator;
            }

            // inherited conditions
            if ($len === 3 && substr($splitted[2], 0, 1) === '.' || is_numeric($condition))
            {
                $subCondition = $this->parseWhereConditionBlock($value);
                $output .= ' ' .$subCondition['sql']. ' ';
                $values = array_merge($values, $subCondition['data']);
                $logicOperatorAllowed = true;
                continue;
            }

            $columnName = ($len === 4 ? $splitted[3] : $splitted[2]);
            $columnId = $columnName . '_' . substr(hash('md4', rand(0, 9) . microtime(true)), 0, 8);

            // append a column name prefix on columns that don't have any
            if ($columnNamePrefix && strpos($columnName, '.') === false)
            {
                $columnName = $columnNamePrefix. '.' .$columnName;
            }

            /**
             * Step 2: Comparison operators
             */
            $comparisonOperator = '=';

            for ($i = 1; $i <= 2; $i++)
            {
                if (isset($this->comparisonOperators[$splitted[$i]]))
                {
                    $comparisonOperator = $this->comparisonOperators[$splitted[$i]];
                    break;
                }
            }

            if ($comparisonOperator == 'in' || $comparisonOperator == 'not in')
            {
                $output .= ' ' .$columnName . ' ' .$comparisonOperator. ' (';

                foreach ($value as $i => $k)
                {
                    $output .= ':' .$columnId. '_' .$i. ', ';
                    $values[$columnId. '_' .$i] = $k;
                }

                $output = rtrim($output, ', '). ')';


            } else {
                $output .= ' ' .$columnName . ' ' .$comparisonOperator. ' :' .$columnId. ' ';
                $values[$columnId] = $value;
            }

            $logicOperatorAllowed = true;
        }

        return array(
            'sql' => '(' .$output. ')',
            'data' => $values,
        );
    }

    /**
     * List of columns to order by
     *
     * eg. array('userName DESC', 'userId ASC')
     *
     * @param array $orderBy List of columns and sorting directions
     * @param string|null $columnNamePrefix Optional column name prefix
     *
     * @throws PantheraFrameworkException
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return string
     */
    public function parseOrderByBlock($orderBy, $columnNamePrefix = null)
    {
        $result = '';

        // wrap it to support like array
        if (is_string($orderBy))
        {
            $orderBy = array($orderBy);
        }

        foreach ($orderBy as $orderColumn)
        {
            $exp = explode(' ', $orderColumn);

            if (count($exp) > 2)
            {
                throw new PantheraFrameworkException('Cannot parse orderBy block "' .$orderColumn. '", invalid syntax. Syntax example: "userName DESC"', 'FW_SQL_CONDITIONS_ORDERBY');
            }

            // append default sorting
            if (!isset($exp[1]))
            {
                $exp[1] = 'ASC';
            }

            if ($exp[1] !== 'ASC' && $exp[1] !== 'DESC')
            {
                throw new PantheraFrameworkException('Order by block has invalid sorting direction value, possible values: ASC, DESC or empty', 'FW_SQL_CONDITIONS_ORDERBY_DIRECTION');
            }

            // append an optional column prefix if not any found in column name
            if ($columnNamePrefix && strpos($exp[0], '.') === false)
            {
                $exp[0] = $columnNamePrefix. '.' .$exp[0];
            }

            $result .= $exp[0]. ' ' .$exp[1]. ', ';
        }

        return rtrim($result, ', ');
    }

    /**
     * Parse array into "GROUP BY" statement (common for SQLite3 and MySQL database types)
     *
     * Example:
     *   array(
     *      'userName',
     *      'count(userId)',
     *   );
     *
     * @param array|string $columnsList List of columns, see example
     * @param string|null $columnNamePrefix Optional column name prefix
     *
     * @throws \Panthera\PantheraFrameworkException
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return string
     */
    public function parseGroupByBlock($columnsList, $columnNamePrefix = null)
    {
        $result = '';

        if (is_string($columnsList))
        {
            $columnsList = array($columnsList);
        }

        foreach ($columnsList as $column)
        {
            // GROUP BY count(name)
            if (strpos($column, '(') !== false)
            {
                $exp = explode('(', $column);

                if (!isset($this->functions[strtolower($exp[0])]))
                {
                    throw new \Panthera\PantheraFrameworkException('Parser error: Unrecognized SQL function "' .$exp[0]. '" for used database handler', 'FW_SQL_UNRECOGNIZED_FUNCTION');
                }

                if ($columnNamePrefix && strpos($exp[1], '.') === false)
                {
                    $exp[1] = $columnNamePrefix. '.' .$exp[1];
                }

                $column = implode('(', $exp);
            } else {
                if ($columnNamePrefix && strpos($column, '.') === false)
                {
                    $column = $columnNamePrefix. '.' .$column;
                }
            }

            $result .= $column. ', ';
        }

        return rtrim($result, ', ');
    }
}

/**
 * A wrapper for creating SELECT database queries
 *
 * @package Panthera\database
 * @author Damian Kęska <damian@pantheraframework.org>
 */
class select
{
    public $what = null;

    public $table = null;

    public $where = null;

    public $joins = array();

    public $order = null;

    public $group = null;

    public $limit = null;

    public $values = array();

    /**
     * Constructor
     *
     * @param string $table Table name
     * @param string|array $what List of columns, or null to insert '*'
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function __construct($table, $what = null)
    {
        $this->what = $what;
        $this->table = $table;
    }

    /**
     * Execute prepared query
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return string
     */
    public function execute()
    {
        $fw = \Panthera\framework::getInstance();
        return $fw->database->select(
            $this->table,
            $this->what,
            $this->where,
            $this->order,
            $this->group,
            $this->limit,
            $this->values,
            $this->joins
        );
    }
}

/**
 * Pagination
 *
 * Calculating SQL limit and offset, page items count.
 *
 * @author Damian Kęska <damian@pantheraframework.org>
 * @package Panthera\database
 */
class Pagination
{
    public $perPage = null;
    public $page    = null;

    /**
     * Constructor
     *
     * @param int $perPage Items per page
     * @param int $page Page number (1...infinity)
     *
     * @throws \Panthera\PantheraFrameworkException
     * @author Damian Kęska <damian@pantheraframework.org>
     */

    public function __construct($perPage, $page = 1)
    {
        if (!is_numeric($perPage) || !is_numeric($page))
        {
            throw new \Panthera\PantheraFrameworkException('$perPage and $page should be of integer type', 'FW_SQL_PAGINATION_NOT_INT');
        }

        $this->perPage = intval($perPage);
        $this->page = intval($page);
    }

    /**
     * Get SQL offset and limit
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return array
     */
    public function getSQLData()
    {
        return [($this->perPage * ($this->page - 1)), $this->perPage];
    }

    /**
     * Get offset from to eg. [5, 10] or [10, 15] if perPage = 5
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return array
     */
    public function getFromTo()
    {
        return [($this->perPage * ($this->page - 1)), ($this->perPage * $this->page)];
    }
}

/**
 * Interface databaseHandlerInterface
 * Every database handler must implement this interface and keep the standards
 *
 * @author Damian Kęska <damian@pantheraframework.org>
 * @package Panthera\database
 */
interface databaseHandlerInterface
{
    public function connect();
    //public function select();
    //public function insert();
    //public function update();
}