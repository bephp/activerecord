<?php
class Base {
	protected $data = array();
	public function __construct($config = array()) {
		foreach($config as $key => $val) $this->$key = $val;
	}
	public function __set($var, $val) {
		$this->data[$var] = $val;
	}
	public function __get($var) {
		return isset($this->data[$var]) ? $this->data[$var] : null;
	}
}
class Expressions extends Base {
	public function __toString() {
		return $this->source. ' '. $this->operator. ' '. $this->target;
	}
}
class WrapExpressions extends Expressions {
	public function __toString() {
		return ($this->start ? : '('). implode(($this->delimiter ? : ','), $this->target). ($this->end?:')');
	}
}
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
	);
	public static $defaultSqlExpressions = array('expressions' => array(), 'wrap' => false,
		'select'=>null, 'insert'=>null, 'update'=>' ', 'delete'=>' ', 
		'from'=>null, 'values' => null, 'where'=>null, 'limit'=>null, 'order'=>null, 'group' => null);
	protected $sqlExpressions = array();
	public function __construct($config = array()) {
		parent::__construct($config);
		//$this->reset();
	}
	public function reset() {$this->sqlExpressions = self::$defaultSqlExpressions;return $this;}
	public static function setDb($db) {
		self::$db = $db;
	}
	public function find($id = null) {
		if ($id) $this->eq('id', $id);
		return self::query($this->limit(1)->_buildSql(array('select', 'from', 'where', 'group', 'order', 'limit')), $this);
	}
	public function findAll() {
		return self::queryAll($this->_buildSql(array('select', 'from', 'where', 'group', 'order', 'limit')));
	}
	public static function exec($sql) {
		return self::$db->prepare($sql)->execute();
	}
	public static function query($sql, $obj) {
		$sth = self::$db->prepare($sql);
		$sth->setFetchMode( PDO::FETCH_INTO , ($obj ? : new get_called_class()));
		$sth->execute();
		$sth->fetch( PDO::FETCH_INTO );
		return $obj;
	}
	public static function queryAll($sql, $class = null) {
		$class = $class ? : get_called_class();
		$sth = self::$db->prepare($sql);
		$sth->setFetchMode( PDO::FETCH_INTO , new $class);
		$sth->execute();
		$result = array();
		while ($obj = $sth->fetch( PDO::FETCH_INTO )) $result[] = clone $obj;
		return $result;
	}
	public function _buildSql($sqls = array()) {
		array_walk($sqls, function (&$n, $i, $o){
			if ('select' === $n && null == $o->$n) $n = strtoupper($n).' *';
			elseif ('from' === $n && null == $o->$n) $n = strtoupper($n).' '. $o->table;
			else $n = (null !== $o->$n) ? $o->$n. ' ' : '';
		}, $this);
		echo 'SQL: '. implode(' ', $sqls). "\n";
		return implode(' ', $sqls);
	}
	public function __call($name, $args) {
		if (is_callable($callback = array(self::$db,$name)))
			return call_user_func_array($callback, $args);
		if (in_array($name = strtolower($name), array_keys(self::$operators))) 
			$this->addCondition($args[0], self::$operators[$name], isset($args[1]) ? $args[1] : null, (is_string(end($args)) && 'or' === strtolower(end($args))) ? 'OR' : 'AND');
		else if (in_array($name= str_replace('by', '', $name), array_keys(self::$sqlParts)))
			$this->$name = new Expressions(array('operator'=>self::$sqlParts[$name], 'target' => implode(', ', $args)));
		return $this;
	}
	public function wrap($op = null) {
		if (1 === func_num_args()){
			$this->wrap = false;
			if (is_array($this->expressions) && count($this->expressions) > 0)
			$this->_addCondition(new WrapExpressions(array('delimiter' => ' ','target'=>$this->expressions)), 'or' === strtolower($op) ? 'OR' : 'AND');
			$this->expressions = array();
		} else $this->wrap = true;
		return $this;
	}
	protected function addCondition($field, $operator, $value, $op = 'AND') {
		if ($exp =  new Expressions(array('source'=>$field, 'operator'=>$operator, 'target'=>(is_array($value) ? 
			new WrapExpressions(array('target' => $value)) : $value)))) {
			if (!$this->wrap)
				$this->_addCondition($exp, $op);
			else
				$this->_addExpression($exp, $op);
		}
	}
	protected function _addExpression($exp, $operator) {
		if (!is_array($this->expressions) || count($this->expressions) == 0) 
			$this->expressions = array($exp);
		else 
			$this->expressions[] = new Expressions(array('operator'=>$operator, 'target'=>$exp));
	}
	protected function _addCondition($exp, $operator) {
		if (!$this->where) 
			$this->where = new Expressions(array('operator'=>'WHERE', 'target'=>$exp));
		else 
			$this->where->target = new Expressions(array('source'=>$this->where->target, 'operator'=>$operator, 'target'=>$exp));	
	}
	public function __set($var, $val) {
		if (array_key_exists($var, $this->sqlExpressions)) $this->sqlExpressions[$var] = $val;
		elseif (array_key_exists($var, self::$defaultSqlExpressions)) $this->sqlExpressions[$var] = $val;
		else parent::__set($var, $val);
	}
	public function & __get($var) {
		if (array_key_exists($var, $this->sqlExpressions)) return $this->sqlExpressions[$var];
		else $r = parent::__get($var); return $r;
	}
}
