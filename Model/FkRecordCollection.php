<?php

/**
 * 
 */
class FkRecordCollection implements Iterator, Countable
{
	protected $_data;
	protected $_model;
	protected $_bracket;

	public function __construct(FkRecordModel $model, $data=array(), $bracket=false) {
		$this->_data = empty($data) ? array() : $data;
		$this->_model = $model;
		$this->_bracket = $bracket;
	}
	public function isEmpty() {
		return 0 === $this->count();
	}
	public function count() {
		return count($this->_data);
	}
	public function current() {
		$index = key($this->_data);
		$data = current($this->_data);
		if ($data instanceof FkRecord) {
			return $data;
		} else {
			return $this->_data[$index] 
				= $this->_model->buildRecord($data, $this->_bracket);
		}
	}
	public function key() {
		return key($this->_data);
	}
	public function next() {
		next($this->_data);
	}
	public function rewind() {
		reset($this->_data);
	}
	public function valid() {
		return key($this->_data) !== null;
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
		return usort($this->_data, $comparator);
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


	public function push($data) {
		array_push($this->_data, $data);
	}
	public function pop() {
		return array_pop($this->_data);
	}


	public function getData() {
		return $this->_data;
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
}