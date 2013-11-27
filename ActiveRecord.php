<?php
class Base {
	public $data = array();
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
	);
	public static $defaultSqlExpressions = array('expressions' => array(), 'wrap' => false,
		'select'=>null, 'insert'=>null, 'update'=>null, 'set' => null, 'delete'=>'DELETE ', 
		'from'=>null, 'values' => null, 'where'=>null, 'limit'=>null, 'order'=>null, 'group' => null);
	protected $sqlExpressions = array();
	
	public $table;
	public $primaryKey = 'id';
	public $dirty = array();
	public $params = array();
	public static $count = 0;
	const PREFIX = ':ph';

	public function __construct($config = array()) {
		parent::__construct($config);
	}
	public function reset() {$this->params = array();$this->sqlExpressions = array();return $this;}
	public function dirty($dirty = array()){$this->data = array_merge($this->data, $this->dirty = $dirty);return $this;}
	public static function setDb($db) {
		self::$db = $db;
	}
	public function find($id = null) {
		if ($id) $this->eq($this->primaryKey, $id);
		if(self::query($this->limit(1)->_buildSql(array('select', 'from', 'where', 'group', 'order', 'limit')), $this->params, $this->reset())) return $this;
		return false;
	}
	public function findAll() {
		return self::queryAll($this->_buildSql(array('select', 'from', 'where', 'group', 'order', 'limit')), $this->params, $this->reset());
	}
	public function delete() {
		return self::execute($this->eq($this->primaryKey, $this->{$this->primaryKey})->_buildSql(array('delete', 'from', 'where')), $this->params, $this->reset());
	}
	public function update() {
		foreach($this->dirty as $field => $value) $this->addCondition($field, '=', $value, '' , 'set');
		if(self::execute($this->eq($this->primaryKey, $this->{$this->primaryKey})->_buildSql(array('update', 'set', 'where')), $this->params)) 
			return $this->dirty()->reset();
		return false;
	}
	public function insert() {
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
	public static function execute($sql, $param = array()) {
		return (($sth = self::$db->prepare($sql)) && $sth->execute($param));
	}
	public static function query($sql, $param = array(), $obj = null) {
		return self::_query(function ($sth, $obj){ $sth->fetch( PDO::FETCH_INTO ); return $obj->dirty();}, $sql, $param, $obj);
	}
	protected static function _query($cb, $sql, $param = array(), $obj = null) {
		if ($sth = self::$db->prepare($sql)) {
			$sth->setFetchMode( PDO::FETCH_INTO , ($obj ? : new get_called_class()));
			$sth->execute($param);
			return call_user_func($cb, $sth, $obj);
		}
		return false;
	}
	public static function queryAll($sql, $param = array(), $obj = null) {
		return self::_query(function ($sth, $obj){ 
			$result = array();
			while ($obj = $sth->fetch( PDO::FETCH_INTO )) $result[] = clone $obj->dirty();
			return $result;
		}, $sql, $param, $obj);
	}
	public function _buildSql($sqls = array()) {
		array_walk($sqls, function (&$n, $i, $o){
			if ('select' === $n && null == $o->$n) $n = strtoupper($n). ' '.$o->table.'.*';
			elseif (('update' === $n||'from' === $n) && null == $o->$n) $n = strtoupper($n).' '. $o->table;
			else $n = (null !== $o->$n) ? $o->$n. ' ' : '';
		}, $this);
		echo 'SQL: ', implode(' ', $sqls), "\n", "PARAMS: ", implode(', ', $this->params), "\n";
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

	protected function _filterParam(&$value) {
		if (is_array($value)) foreach((array)$value as $key => $val) $this->params[$value[$key] = self::PREFIX. ++self::$count] = $val;
		else {
			$this->params[$ph = self::PREFIX. ++self::$count] = $value;
			$value = $ph;
		}
		return $value;
	}
	protected function addCondition($field, $operator, $value, $op = 'AND', $name = 'where') {
		$value = $this->_filterParam($value);
		if ($exp =  new Expressions(array('source'=>('where' == $name? $this->table.'.' : '' ) .$field, 'operator'=>$operator, 'target'=>(is_array($value) ? 
			new WrapExpressions(array('target' => $value)) : $value)))) {
			if (!$this->wrap)
				$this->_addCondition($exp, $op, $name);
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
	protected function _addCondition($exp, $operator, $name ='where' ) {
		if (!$this->$name) 
			$this->$name = new Expressions(array('operator'=>strtoupper($name) , 'target'=>$exp));
		else 
			$this->$name->target = new Expressions(array('source'=>$this->$name->target, 'operator'=>$operator, 'target'=>$exp));
	}
	public function __set($var, $val) {
		if (array_key_exists($var, $this->sqlExpressions)) $this->sqlExpressions[$var] = $val;
		elseif (array_key_exists($var, self::$defaultSqlExpressions)) $this->sqlExpressions[$var] = $val;
		else $this->dirty[$var] = $this->data[$var] = $val;
	}
	public function __unset($var) { 
		if (array_key_exists($var, $this->sqlExpressions)) unset($this->sqlExpressions[$var]);
		if(isset($this->data[$var])) unset($this->data[$var]);
		if(isset($this->dirty[$var])) unset($this->dirty[$var]);
	}
	public function & __get($var) {
		if (array_key_exists($var, $this->sqlExpressions)) return  $this->sqlExpressions[$var];
		else //if(isset($this->data[$var])) { $var = $this->data[$var]; return $var;}
		{$r = parent::__get($var); return $r;}
	}
}
