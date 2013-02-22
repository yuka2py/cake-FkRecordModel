<?php
/**
 * Supports Entitiy model on CakePHP.
 *
 * @version       0.1.0
 * @copyright     Copyright 2012-2013, Foreignkey, Inc. (http://foreignkey.jp)
 * @package       FkRecordModel.Model
 * @license       GPLv3 License
 */

class FkRecord extends ArrayObject
{
	const ALL_FILED = null;

	protected $_model;
	protected $_errors;
	protected $_cache;

	public $Form;


	public function __construct(FkRecordModel $model, $rawdata) {
		parent::__construct($rawdata);
		$this->_model = $model;
		$this->_errors = array();
		$this->_cache = array();
	}


	public function bindFormHelper(FormHelper $helper) {
		$helper->validationErrors = $this->getError();
		$helper->request->data = $this->getData(); //FormHelper の出力値をこのレコードの値にする為。もっと良い方法ありますか？
		$this->Form = $helper;
		return $helper;
	}


	/**
	 * Removes this record. Returns true on success.
	 * @param  boolean  $cascade  Set to true to delete records that depend on this record.
	 * @return boolean  boolean True on success.
	 */
	public function delete($cascade=true) {
		return $this->_model->delete($this->getID(), $cascade);
	}


	/**
	 * Returns the this record's ID.
	 * If given $default then
	 * @param  mixed  $default
	 * @return integer
	 */
	public function getID($default=null) {
		$alias = $this->_model->alias;
		$key = $this->_model->primaryKey;
		if (empty($this[$alias]) or empty($this[$alias][$key])) {
			if (0 === func_num_args()) {
				throw new ErrorException(sprintf('Record id is not set yet. %s[%s][%s]', 
					get_class($this), $alias, $key));
			} else {
				return $default;
			}
		}
		return $this[$alias][$key];
	}


	/**
	 * Get data as array.
	 * @return  array
	 */
	public function getData() {
		return $this->getArrayCopy();
	}


	public function getVerboseName($fieldName) {
		return $this->_model->getVerboseName($fieldName);
	}


	public function hasID() {
		$alias = $this->_model->alias;
		$key = $this->_model->primaryKey;
		return isset($this[$alias]) and isset($this[$alias][$key]) and !empty($this[$alias][$key]);
	}


	public function model() {
		return $this->_model;
	}


	public function get($name, $default=null) {
		$alias = $this->_model->alias;
		if (isset($this[$alias]) and isset($this[$alias][$name])) {
			return $this[$alias][$name];
		} else {
			return $default;
		}
	}




	public function addError($fieldName, $message) {
		$this->_errors[$fieldName][] = $message;
	}

	public function hasError($fieldName=self::ALL_FILED) {
		if (self::ALL_FILED === $fieldName) {
			return !empty($this->_errors);
		} else if (is_array($fieldName)) {
			$errorFieldNames = array_keys($this->_errors);
			return 0 < count(array_intersect($errorFieldNames, $fieldName));
		} else {
			return isset($this->_errors[$fieldName]);
		}
	}
	public function hasNotError($fieldName=self::ALL_FILED) {
		return !$this->hasError($fieldName);
	}

	public function getError($fieldName=self::ALL_FILED) {
		if (self::ALL_FILED === $fieldName) {
			return $this->_errors;
		} else if (is_array($fieldName)) {
			return array_intersect_key($this->_errors, array_flip($fieldName));
		} else if (isset($this->_errors[$fieldName])) {
			return $this->_errors[$fieldName];
		} else {
			return null;
		}

	}

	public function getErrorMessage($fieldName=self::ALL_FILED, $messageWrapper=null, $fieldWrapper=null) {

		is_null($messageWrapper) && $messageWrapper = $this->_model->errorMessageWrapper;
		is_null($fieldWrapper) && $fieldWrapper = $this->_model->errorFieldWrapper;

		if (self::ALL_FILED === $fieldName or is_array($fieldName)) {
			$errors = $this->getError($fieldName);
			foreach ($errors as $fieldName => &$error) {
				$verboseName = $this->_model->getVerboseName($fieldName)
				or $verboseName = $fieldName;
				$error = str_replace(':name', $verboseName, $fieldWrapper);
				$error = str_replace(':message', $this->getErrorMessage($fieldName, $messageWrapper), $error);
			}
			return implode('', $errors);
		} else if (isset($this->_errors[$fieldName])) {
			$errors = $this->_errors[$fieldName];
			foreach ($errors as &$error) {
				$error = str_replace(':message', $error, $messageWrapper);
			}
			return implode('', $errors);
		} else {
			return null;
		}
	}


	public function reload() {
		$data = $this->_model->read(null, $this->getID());
		$this->resetData($data);
		$this->_cache = null;
	}


	/**
	 * Saves record data (based on white-list, if supplied) to the database. 
	 * By default, validation occurs before save.
	 * On failed, and set error message to this.
	 *
	 * @param boolean|array $validate Either a boolean, or an array.
	 *                                If a boolean, indicates whether or not to validate before saving.
	 *                                If an array, allows control of validate, callbacks, and fieldList
	 * @param array $fieldList List of fields to allow to be written
	 * @return boolean Returns true on success.
	 */
 	public function save($validate = true, $fieldList = array()) {
		$this->_errors = array();
		$result = $this->_model->save($this->getData(), $validate, $fieldList);

		if (false === $result) {
			$this->_errors = $this->_model->validationErrors;
		}
		else if (true === $result) {
			$id = $this->_model->getId();
			$data = $this->_model->read(null, $id);
			$this->resetData($data);
		}
		else if (is_array($result)) {
			$this->resetData($result);
		}
		
		return false !== $result;
	}

	/**
	 * Validates this record. and set error message to this.
	 * @param  array  $options  An optional array of custom options to be made available in the beforeValidate callback
	 * @return  boolean  True  if there are no errors.
	 */
	public function validates($options = array()) {
		$this->_errors = array();
		$this->_model->set($this->getData());
		$result = $this->_model->validates($options);
		if (!$result) {
			$this->_errors = $this->_model->validationErrors;
		}
		return $result;
	}


	/**
	 * Returns data as array.
	 * @param  array  $fields  Optional. Specify, if require extracted fields.
	 * @param  boolean  $onlyPrimaryData  Optional. default is true. 
	 * @return  array
	 */
	public function toArray($fields=array(), $onlyPrimaryData=true) {
		$data = $this->getData();
		if ($onlyPrimaryData) {
			$data = isset($data[$this->_model->alias]) ? $data[$this->_model->alias] : array();
		}
		if ($fields) {
			$extracted = array();
			foreach ($fields as $alias => $field) {
				if (is_numeric($alias)) {
					$alias = $field;
				}
				$extracted[$alias] = isset($data[$field])
					? $data[$field] : null;
			}
			$data = $extracted;
		}
		return $data;
	}

	/**
	 * Returns json string of this record
	 * @param  array  $fields  Optional. Specify, if require extracted fields.
	 * @param  boolean  $onlyPrimaryData  Optional. default is true. 
	 * @return string
	 */
	public function toJSON($fields=array(), $onlyPrimaryData=true) {
		return json_encode($this->toArray($fields, $onlyPrimaryData));
	}



	public function set($name, $value=null) {
		if (1 === func_num_args() and is_array($name)) {
			$data = $name;
			foreach ($data as $name => $value) {
				$this->set($name, $value);
			}
		} else {
			$this[$this->_model->alias][$name] = $value;
		}
	}


	public function setData($data) {
		$_ = $this->getData();
		foreach ($data as $modelAlias => $entityData) {
			foreach ($entityData as $field => $value) {
				$_[$modelAlias][$field] = $value;
			}
		}
		$this->exchangeArray($_);
	}


	public function resetData($data) {
		$this->exchangeArray($data);
	}



	private static function _arrayGetByKeyRecursive($keys, $array, $default=null) {
		$value = $array;
		while ($key = array_shift($keys)) {
			if (is_array($value) and isset($value[$key])) {
				$value = $value[$key];
			} else {
				$value = $default;
				break;
			}
		}
		return $value;
	}



	public function __get($name) {
		if (isset($this->_cache[$name])) {
			return $this->_cache[$name];
		}

		//Association
		$AssocModel = $this->_model->getAssociationModel($name);
		if ($AssocModel) {
			$type = $this->_model->getAssociationType($name);
			switch ($type) {
				case 'hasMany':
				case 'hasAndBelongsToMany':
					$records = isset($this[$name]) ? $this[$name] : array();
					return $this->_cache[$name] 
						= $AssocModel->buildRecordCollection($records, true);
				case 'hasOne':
				case 'belongsTo':
					if (isset($this[$name])) {
						$data = (array) $this[$name];
						foreach ($data as $v) {
							if ($v != null) {
								return $this->_cache[$name] 
									= $AssocModel->buildRecord($data, true);
							}
						}
					}
					return $this->_cache[$name] = null;
			}
		} else if ($this->_model->hasField($name)) {
			return $this->get($name);
		}

		throw new ErrorException("\"$name\" property not found on " . get_class($this));
	}

	public function __set($name, $value) {
		$this->set($name, $value);
	}

	public function __isset($name) {
		if ($this->_model->getAssociationType($name)) {
			$assoc = $this->$name;
			if ($assoc instanceof FkRecordCollection) {
				return !$assoc->isEmpty();
			} else {
				return !empty($assoc);
			}
		}
		else if (isset($this->_cache[$name])) {
			return true;
		}
		else if ($this->_model->hasField($name)) {
			$alias = $this->_model->alias;
			return isset($this[$alias]) 
				and isset($this[$alias][$name]);
		}
		return false;
	}




}
