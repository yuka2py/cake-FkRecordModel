<?php
/**
 * Supports Entitiy model on CakePHP.
 *
 * @version       0.1.0
 * @copyright     Copyright 2012-2013, Foreignkey, Inc. (http://foreignkey.jp)
 * @package       FkRecordModel.Model
 * @license       GPLv3 License
 */

class FkRecordCollection implements Iterator, Countable, ArrayAccess
{
	protected $_records;
	protected $_model;
	protected $_bracket;

	public function __construct(FkRecordModel $model, $data=array(), $bracket=false) {
		$this->_records = empty($data) ? array() : $data;
		$this->_model = $model;
		$this->_bracket = $bracket;
	}

	public function isEmpty() {
		return 0 === $this->count();
	}

	public function count() {
		return count($this->_records);
	}
	public function current() {
		$index = key($this->_records);
		$data = current($this->_records);
		if ($data instanceof FkRecord) {
			return $data;
		} else {
			return $this->_records[$index] 
				= $this->_model->buildRecord($data, $this->_bracket);
		}
	}
	public function key() {
		return key($this->_records);
	}
	public function next() {
		next($this->_records);
	}
	public function rewind() {
		reset($this->_records);
	}
	public function valid() {
		return key($this->_records) !== null;
	}
	public function offsetExists($offset) {
		return isset($this->_records[$offset]);
	}
	public function offsetGet($offset) {
		return $this->_records[$offset];
	}
	public function offsetSet($offset, $value) {
		$this->_records[$offset] = $value;
	}
	public function offsetUnset($offset) {
		unset($this->_records[$offset]);
	}




	/**
	 * Get extracted data of collection.
	 * @param  string  $field  field name
	 * @return  array
	 */
	public function extract($field) {
		$list = array();
		foreach ($this as $d) {
			$list[] = $d->$field;
		}
		return $list;
	}

	/**
	 * Prepend record to the beginning of collection.
	 * @param  FkRecord|array  $record
	 * @return  void
	 */
	public function unshift($record) {
		array_unshift($this->_records, $record);
	}

	/**
	 * Shift a record off the beginning of collection.
	 * @return  mixed
	 */
	public function shift() {
		return array_shift($this->_records);
	}

	/**
	 * Push record onto the end of collection.
	 * @param  FkRecord|array  $record
	 */
	public function push($record) {
		array_push($this->_records, $record);
	}

	/**
	 * Pop the record off the end of collection.
	 * @return  mixed
	 */
	public function pop() {
		return array_pop($this->_records);
	}

	/**
	 * Remove the specified record from the collection.
	 * @param  FkRecord  $target
	 * @param  callable  $callback[optional]  Records that match this will be removed. Default expression is $a === $b.
	 */
	public function remove(FkRecord $target, $callback=null) {
		$records = array();
		if (empty($callback)) {
			$callback = create_function('$a, $b', 'return $a === $b;');
		}
		foreach ($this as $record) {
			if (! $callback($target, $record)) {
				$records[] = $record;
			}
		}
		$this->_records = $records;
	}

	public function getData() {
		return $this->_records;
	}
	public function toArray($fields=array(), $onlyPrimaryData=true) {
		$data = array();
		foreach ($this as $record) {
			$data[] = $record->toArray($fields, $onlyPrimaryData);
		}
		return $data;
	}
	public function toJSON($fields=array(), $onlyPrimaryData=true) {
		$data = array();
		foreach ($this as $record) {
			$data[] = $record->toJSON($fields, $onlyPrimaryData);
		}
		return '[' . implode(',', $data) . ']';
	}

	/**
	 * コレクションの中から callback が true となる全ての要素を FkRecordCollection で返す。
	 * @return  FkRecordCollection
	 */
	public function select($callback) {
		$records = new FkRecordCollection($this->_model, array(), $this->_bracket);
		foreach ($this as $record) {
			if (call_user_func($callback, $record)) {
				$records.push($record);
			}
		}
		return $records;
	}

	/**
	 * コレクションの中から callback が true となる最初の要素を返す。
	 * 見つからない時は、null を返す。
	 * @param  $callback  
	 * @return  null|FkRecord
	 */
	public function selectFirst($callback) {
		foreach ($this as $record) {
			if (call_user_func($callback, $record)) {
				return $record;
			}
		}
		return null;
	}
	/**
	 * Sort collection with a an comparator.
	 * @param  callable  comparator  Default comparator to sort by the primary key asc.
	 * @return  boolean  On success, return true.
	 */
	public function sort($comparator=null) {
		foreach ($this as $d) {} /* Notice: All data convert to FkRecord */
		if (empty($comparator)) {
			$comparator = create_function('$a, $b', 'return $a->getID() < $b->getID() ? -1 : 1;');
		}
		return usort($this->_records, $comparator);
	}

	/**
	 * コレクションの全ての要素に $callback の処理を適用する。
	 * @param  callable  $callback
	 * @return  void
	 */
	public function each($callback) {
		foreach ($this as $record) {
			call_user_func($callback, $record);
		}
	}




	public function __call($name, $arguments) {
		if (preg_match('/^(select|selectFirst)By(\w+)/', $name, $matches)) {
			$method = $matches[1];
			$name = Inflector::underscore($matches[2]);
			if ($this->_model->hasField($name)) {
				$value = $arguments[0];
				$callback = create_function('$record', sprintf('return \'%s\' === strval($record->%s);', $value, $name));
				return $this->$method($callback);
			}
		}
		throw new InternalErrorException('No such method: ' + $name);
	}

}






