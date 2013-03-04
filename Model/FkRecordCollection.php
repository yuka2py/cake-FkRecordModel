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
	protected $_primary;

	public function __construct(FkRecordModel $model, $data=array(), $primary=false) {
		$this->_records = empty($data) ? array() : $data;
		$this->_model = $model;
		$this->_primary = $primary;
	}

	private function _toRecord($record) {
		if ($record instanceof FkRecord) {
			return $record;
		}
		return $this->_model->buildRecord($data, $this->_primary);
	}

	public function isEmpty() {
		return 0 === $this->count();
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
	 * @return  FkRecord
	 */
	public function unshift($record) {
		$record = $this->_toRecord($record);
		array_unshift($this->_records, $record);
		return $record;
	}

	/**
	 * Shift a record off the beginning of collection.
	 * @return  mixed
	 */
	public function shift() {
		$record = array_shift($this->_records);
		$record = $this->_toRecord($record);
		return $record;
	}

	/**
	 * Push record onto the end of collection.
	 * @param  FkRecord|array  $record
	 * @return  FkRecord
	 */
	public function push($record) {
		$record = $this->_toRecord($record);
		array_push($this->_records, $record);
		return $record;
	}

	/**
	 * Pop the record off the end of collection.
	 * @return  mixed
	 */
	public function pop() {
		$record = array_pop($this->_records);
		$record = $this->_toRecord($record);
		return $record;
	}

	/**
	 * Remove the specified record from the collection.
	 * @param  FkRecord  $target
	 * @return  boolean  Removed records.
	 */
	public function remove($target) {
		$found = false;
		foreach ($this as $index => $record) {
			if ($target === $record) {
				$found = true;
				break;
			}
		}
		if ($found) {
			unset($this->_records[$index]);
			return true;
		} else {
			return false;
		}
	}

	public function clear() {
		$this->_records = array();
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

	public function all($callback) {
		foreach ($this as $record)
			if ( ! call_user_func($callback, $record))
				return false;
		return true;
	}
	
	public function any($callback) {
		foreach ($this as $record)
			if (call_user_func($callback, $record))
				return true;
		return false;
	}

	/**
	 * コレクションの中から callback が true となる全ての要素を FkRecordCollection で返す。
	 * @param  callable  callback
	 * @return  FkRecordCollection
	 */
	public function select($callback) {
		$records = $this->_model->buildRecordCollection(array(), $this->_primary);
		foreach ($this as $record) {
			if (call_user_func($callback, $record)) {
				$records->push($record);
			}
		}
		return $records;
	}

	/**
	 * コレクションの中から callback が true となる最初の要素を返す。
	 * 見つからない時は、null を返す。
	 * @param  callable  $callback  
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


	/**
	 * 
	 */
	public function __call($name, $arguments) {
		if (preg_match('/^(select|selectFirst)By(\w+)/', $name, $matches)) {
			$method = $matches[1];
			$name = Inflector::underscore($matches[2]);
			if ($this->_model->hasField($name)) {
				$value = $arguments[0];
				$callback = create_function('$r', sprintf('return \'%s\' === strval($r->%s);', $value, $name));
				return $this->$method($callback);
			}
		}
		throw new InternalErrorException('No such method: ' + $name);
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
			return $this->_records[$index] = $this->_model->buildRecord($data, $this->_primary);
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
		$this->_records[$offset] = $this->_toRecord($this->_records[$offset]);
		return $this->_records[$offset];
	}
	public function offsetSet($offset, $value) {
		$this->_records[$offset] = $value;
	}
	public function offsetUnset($offset) {
		unset($this->_records[$offset]);
	}


}






