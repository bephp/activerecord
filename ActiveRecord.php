<?php
/*
 * Created on Nov 26, 2013
 * Simple implement of active record in PHP.
 * Using magic function to implement more smarty functions.
 * Can using chain method calls, to build concise and compactness program.
 * @author Lloyd Zhou
 * @email lloydzhou@qq.com
 */
?>
<?php
/**
 * base function, to stord attributes in one array.
 */
class Base {
	public $data = array();
	public function __construct($config = array()) {
		foreach($config as $key => $val) $this->$key = $val;
	}
	public function __set($var, $val) {
		$this->data[$var] = $val;
	}
	public function & __get($var) {
        $result = isset($this->data[$var]) ? $this->data[$var] : null;
		return $result;
	}
}
/**
 * Class Expressions, part of SQL.
 * Every SQL can be split into multiple expressions.
 * Each expression contains three parts: 
 * @property string|Expressions $source of this expression, (option)
 * @property string $operator (required)
 * @property string|Expressions $target of this expression (required)
 * Just implement one function __toString.
 */
class Expressions extends Base {
	public function __toString() {
		return $this->source. ' '. $this->operator. ' '. $this->target;
	}
}
/**
 * Class WrapExpressions 
 */
class WrapExpressions extends Expressions {
	public function __toString() {
		return ($this->start ? : '('). implode(($this->delimiter ? : ','), $this->target). ($this->end?:')');
	}
}
/**
 * @abstract base class of ActiveRecords.
 * @property PDO $db static property.
 * @property array $operators maping the function name and the operator, to build Expressions in WHERE condition.
 * user can call it like this: 
 *      $user->isnotnull()->eq('id', 1); 
 * will create Expressions can explain to SQL: 
 *      WHERE user.id IS NOT NULL AND user.id = :ph1
 * @property array $sqlPart Part of SQL, maping the function name and the operator to build SQL Part.
 * call function like this: 
 *      $user->order('id desc', 'name asc')->limit(2,1);
 *  can explain to SQL:
 *      ORDER BY id desc, name asc limit 2,1
 * @property array $sqlExpressions stored the Expressions of the SQL. 
 * @property string $table The table name in database.
 * @property string $primaryKey  The primary key of this ActiveRecord, just suport single primary key.
 * @property array $drity Stored the drity data of this object, when call "insert" or "update" function, will write this data into database. 
 * @property array $params Stored the params will bind to SQL when call PDOStatement::execute(), 
 * @property int $count The count of bind params, using this count and const "PREFIX" (:ph) to generate place holder in SQL. 
 */
abstract class ActiveRecord extends Base {
	public static $db;
	public static $operators = array(
		'equal' => '=', 'eq' => '=',
		'notequal' => '<>', 'ne' => '<>',
		'greaterthan' => '>', 'gt' => '>',
		'lessthan' => '', 'lt' => '<',
		'greaterthanorequal' => '>=', 'ge' => '>=','gte' => '>=',
		'lessthanorequal' => '<=', 'le' => '<=','lte' => '<=',
		'between' => 'BETWEEN',
		'like' => 'LIKE',
		'in' => 'IN',
		'notin' => 'NOT IN',
		'isnull' => 'IS NULL',
		'isnotnull' => 'IS NOT NULL', 'notnull' => 'IS NOT NULL', 
	);
	public static $sqlParts = array(
		'select' => 'SELECT',
		'from' => 'FROM',
		'group' => 'GROUP BY','groupby' => 'GROUP BY',
		'order' => 'ORDER BY','orderby' => 'ORDER BY',
		'limit' => 'limit',
		'top' => 'TOP',
		'where' => 'WHERE',
	);
	public static $defaultSqlExpressions = array('expressions' => array(), 'wrap' => false,
		'select'=>null, 'insert'=>null, 'update'=>null, 'set' => null, 'delete'=>'DELETE ', 
		'from'=>null, 'values' => null, 'where'=>null, 'limit'=>null, 'order'=>null, 'group' => null);
	protected $sqlExpressions = array();
	
	public $table;
	public $primaryKey = 'id';
	public $dirty = array();
	public $params = array();
	const BELONGS_TO = 'belongs_to';
	const HAS_MANY = 'has_many';
	const HAS_ONE = 'has_one';
	public $relations = array();
	public static $count = 0;
	const PREFIX = ':ph';

    /**
     * function to reset the $params and $sqlExpressions.
     * @return ActiveRecord return $this, can using chain method calls.
     */
	public function reset() {
        $this->params = array();
        $this->sqlExpressions = array();
        return $this;
    }
    /**
     * function to SET or RESET the dirty data.
     * @return ActiveRecord return $this, can using chain method calls.
     */
	public function dirty($dirty = array()){
        $this->data = array_merge($this->data, $this->dirty = array_merge($this->dirty, $dirty));
        return $this;
    }
    /**
     * set the DB connection.
     * @param PDO $db
     */
	public static function setDb($db) {
		self::$db = $db;
	}
    /**
     * function to find one record and assign in to current object.
     * @param int $id If call this function using this param, will find record by using this id. If not set, just find the first record in database.
     * @return bool | ActiveRecord if find record, assign in to current object and return it, other wise return "false".
     */
	public function find($id = null) {
		if ($id) $this->eq($this->primaryKey, $id);
		if(self::query($this->limit(1)->_buildSql(array('select', 'from', 'where', 'group', 'order', 'limit')), $this->params, $this->reset())) 	
            return $this;
		return false;
	}
    /**
     * function to find all records in database.
     * @return array return array of ActiveRecord
     */
	public function findAll() {
		return self::queryAll($this->_buildSql(array('select', 'from', 'where', 'group', 'order', 'limit')), $this->params, $this->reset());
	}
    /**
     * function to delete current record in database.
     * @return bool 
     */
	public function delete() {
		return self::execute($this->eq($this->primaryKey, $this->{$this->primaryKey})->_buildSql(array('delete', 'from', 'where')), $this->params, $this->reset());
	}
    /**
     * function to build update SQL, and update current record in database, just write the dirty data into database.
     * @return bool | ActiveRecord if update success return current object, other wise return false.
     */
	public function update() {
        if (count($this->dirty) == 0) return true;
		foreach($this->dirty as $field => $value) $this->addCondition($field, '=', $value, '' , 'set');
		if(self::execute($this->eq($this->primaryKey, $this->{$this->primaryKey})->_buildSql(array('update', 'set', 'where')), $this->params)) 
			return $this->dirty()->reset();
		return false;
	}
    /**
     * function to build insert SQL, and insert current record into database.
     * @return bool | ActiveRecord if insert success return current object, other wise return false.
     */
	public function insert() {
        if (count($this->dirty) == 0) return true;
		$value = $this->_filterParam($this->dirty);
		$this->insert = new Expressions(array('operator'=> 'INSERT INTO '. $this->table, 
			'target' => new WrapExpressions(array('target' => array_keys($this->dirty)))));
		$this->values = new Expressions(array('operator'=> 'VALUES', 'target' => new WrapExpressions(array('target' => $value))));
		if (self::execute($this->_buildSql(array('insert', 'values')), $this->params)) {
			$this->id = self::$db->lastInsertId();
			return $this->dirty()->reset();
		}
		return false;
	}
    /**
     * helper function to exec sql.
     */
	public static function execute($sql, $param = array()) {
		return (($sth = self::$db->prepare($sql)) && $sth->execute($param));
	}
    /**
     * helper function to query one record by sql and params.
     * @return bool | ActiveRecord 
     */
	public static function query($sql, $param = array(), $obj = null) {
		return self::callbackQuery(function ($sth, $obj){ $sth->fetch( PDO::FETCH_INTO ); return $obj->dirty();}, $sql, $param, $obj);
	}
    /**
     * helper function to execute sql with callback, can using this call back to fetch data.
     * @return mixed if success to exec SQL, return the return value of callback, other wise return false.
     */
	public static function callbackQuery($cb, $sql, $param = array(), $obj = null) {
		if ($sth = self::$db->prepare($sql)) {
			$sth->setFetchMode( PDO::FETCH_INTO , ($obj ? : new get_called_class()));
			$sth->execute($param);
			return call_user_func($cb, $sth, $obj);
		}
		return false;
	}
    /**
     * helper function to find all records by SQL.
     */
	public static function queryAll($sql, $param = array(), $obj = null) {
		return self::callbackQuery(function ($sth, $obj){ 
			$result = array();
			while ($obj = $sth->fetch( PDO::FETCH_INTO )) $result[] = clone $obj->dirty();
			return $result;
		}, $sql, $param, $obj);
	}
    /**
     * helper function to get relation of this object.
     * There was three types of relations: {BELONGS_TO, HAS_ONE, HAS_MANY}
     * @return mixed 
     */
	protected function & getRelation($name) {
		if ((!$this->relations[$name] instanceof self) && self::HAS_ONE == $this->relations[$name][0])
			$this->relations[$name] = (new $this->relations[$name][1])->eq($this->relations[$name][2], $this->{$this->primaryKey})->find();
		elseif (is_array($this->relations[$name]) && self::HAS_MANY == $this->relations[$name][0])
			$this->relations[$name] = (new $this->relations[$name][1])->eq($this->relations[$name][2], $this->{$this->primaryKey})->findAll();
		elseif ((!$this->relations[$name] instanceof self) && self::BELONGS_TO == $this->relations[$name][0])
			$this->relations[$name] = (new $this->relations[$name][1])->find($this->{$this->relations[$name][2]});
		else throw new Exception("Relation $name not found.");
		return $this->relations[$name];
	}
    /**
     * helper function to build SQL with sql parts.
     * @return string 
     */
	public function _buildSql($sqls = array()) {
		array_walk($sqls, function (&$n, $i, $o){
			if ('select' === $n && null == $o->$n) $n = strtoupper($n). ' '.$o->table.'.*';
			elseif (('update' === $n||'from' === $n) && null == $o->$n) $n = strtoupper($n).' '. $o->table;
			else $n = (null !== $o->$n) ? $o->$n. ' ' : '';
		}, $this);
        //this code to debug info.
		//echo 'SQL: ', implode(' ', $sqls), "\n", "PARAMS: ", implode(', ', $this->params), "\n";
		return implode(' ', $sqls);
	}
    /**
     * magic function to make calls witch in function mapping stored in $operators and $sqlPart.
     * also can call function of PDO object.
     * @return mixed Return the result of callback or the current object to make chain method calls.
     */
	public function __call($name, $args) {
		if (is_callable($callback = array(self::$db,$name)))
			return call_user_func_array($callback, $args);
		if (in_array($name = strtolower($name), array_keys(self::$operators))) 
			$this->addCondition($args[0], self::$operators[$name], isset($args[1]) ? $args[1] : null, (is_string(end($args)) && 'or' === strtolower(end($args))) ? 'OR' : 'AND');
		else if (in_array($name= str_replace('by', '', $name), array_keys(self::$sqlParts)))
			$this->$name = new Expressions(array('operator'=>self::$sqlParts[$name], 'target' => implode(', ', $args)));
		else throw new Exception("Method $name not exist.");
		return $this;
	}
    /**
     * make wrap when build the SQL expressions of WHWRE.
     */
	public function wrap($op = null) {
		if (1 === func_num_args()){
			$this->wrap = false;
			if (is_array($this->expressions) && count($this->expressions) > 0)
			$this->_addCondition(new WrapExpressions(array('delimiter' => ' ','target'=>$this->expressions)), 'or' === strtolower($op) ? 'OR' : 'AND');
			$this->expressions = array();
		} else $this->wrap = true;
		return $this;
	}
    /**
     * helper function to build place holder when make SQL expressions.
     */
	protected function _filterParam($value) {
		if (is_array($value)) foreach($value as $key => $val) $this->params[$value[$key] = self::PREFIX. ++self::$count] = $val;
		else if (is_string($value)){
			$this->params[$ph = self::PREFIX. ++self::$count] = $value;
			$value = $ph;
		}
		return $value;
	}
    /**
     * helper function to add condition into WHERE. 
     * create the SQL Expressions.
     */
	public function addCondition($field, $operator, $value, $op = 'AND', $name = 'where') {
		$value = $this->_filterParam($value);
		if ($exp =  new Expressions(array('source'=>('where' == $name? $this->table.'.' : '' ) .$field, 'operator'=>$operator, 'target'=>(is_array($value) ? 
			new WrapExpressions(array('target' => $value)) : $value)))) {
			if (!$this->wrap)
				$this->_addCondition($exp, $op, $name);
			else
				$this->_addExpression($exp, $op);
		}
	}
    /**
     * helper function to make wrapper. 
     */
	protected function _addExpression($exp, $operator) {
		if (!is_array($this->expressions) || count($this->expressions) == 0) 
			$this->expressions = array($exp);
		else 
			$this->expressions[] = new Expressions(array('operator'=>$operator, 'target'=>$exp));
	}
    /**
     * helper function to add condition into WHERE. 
     */
	protected function _addCondition($exp, $operator, $name ='where' ) {
		if (!$this->$name) 
			$this->$name = new Expressions(array('operator'=>strtoupper($name) , 'target'=>$exp));
		else 
			$this->$name->target = new Expressions(array('source'=>$this->$name->target, 'operator'=>$operator, 'target'=>$exp));
	}
    /**
     * magic function to SET values of the current object.
     */
	public function __set($var, $val) {
		if (array_key_exists($var, $this->sqlExpressions) || array_key_exists($var, self::$defaultSqlExpressions)) $this->sqlExpressions[$var] = $val;
		else $this->dirty[$var] = $this->data[$var] = $val;
	}
    /**
     * magic function to UNSET values of the current object.
     */
	public function __unset($var) { 
		if (array_key_exists($var, $this->sqlExpressions)) unset($this->sqlExpressions[$var]);
		if(isset($this->data[$var])) unset($this->data[$var]);
		if(isset($this->dirty[$var])) unset($this->dirty[$var]);
	}
    /**
     * magic function to GET the values of current object.
     */
	public function & __get($var) {
		if (array_key_exists($var, $this->sqlExpressions)) return  $this->sqlExpressions[$var];
		else if (array_key_exists($var, $this->relations)) return $this->getRelation($var);
		else return  parent::__get($var);
	}
}
