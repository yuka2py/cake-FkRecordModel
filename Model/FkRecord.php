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

	public $Form;

	private $_model;
	private $_errors;
	private $_assocobjects = array();


	public function __construct(FkRecordModel $model, $rawdata) {
		parent::__construct($rawdata);
		$this->_model = $model;
		$this->_errors = array();
		$this->_assocobjects = array();
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
		return $this->model()->delete($this->getID(), $cascade);
	}


	/**
	 * Returns the this record's ID.
	 * If given $default then
	 * @param  mixed  $default
	 * @return integer
	 */
	public function getID($default=null) {
		$alias = $this->model()->alias;
		$key = $this->model()->primaryKey;
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

	public function setID($val) {
		$alias = $this->model()->alias;
		$key = $this->model()->primaryKey;
		$this[$alias][$key] = $val;
	}

	/**
	 * Get data as array.
	 * @return  array
	 */
	public function getData() {
		return $this->getArrayCopy();
	}


	public function getVerboseName($fieldName) {
		return $this->model()->getVerboseName($fieldName);
	}


	public function hasID() {
		$alias = $this->model()->alias;
		$key = $this->model()->primaryKey;
		return isset($this[$alias]) and isset($this[$alias][$key]) and !empty($this[$alias][$key]);
	}


	public function model() {
		return $this->_model;
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

		is_null($messageWrapper) && $messageWrapper = $this->model()->errorMessageWrapper;
		is_null($fieldWrapper) && $fieldWrapper = $this->model()->errorFieldWrapper;

		if (self::ALL_FILED === $fieldName or is_array($fieldName)) {
			$errors = $this->getError($fieldName);
			foreach ($errors as $fieldName => &$error) {
				$verboseName = $this->model()->getVerboseName($fieldName)
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
		$data = $this->model()->read(null, $this->getID());
		$this->resetData($data);
		$this->_assocobjects = array();
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
		$data = $this->getData();
		$this->model()->create();
		$result = $this->model()->save($data, $validate, $fieldList);

		if (false === $result) {
			$this->_errors = $this->model()->validationErrors;
		}
		else if (true === $result) {
			$id= $this->model()->getId();
			// $this->setID($id);
			$data = $this->model()->read(null, $id);
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
		$this->model()->set($this->getData());
		$result = $this->model()->validates($options);
		if (!$result) {
			$this->_errors = $this->model()->validationErrors;
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
			$data = isset($data[$this->model()->alias]) ? $data[$this->model()->alias] : array();
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


	public function setData($data) {
		$_ = $this->getData();
		foreach ($data as $modelAlias => $entityData) {
			if (is_array($entityData)) {
				foreach ($entityData as $field => $value) {
					$_[$modelAlias][$field] = $value;
				}
			}
		}
		$this->exchangeArray($_);
	}


	public function resetData($data) {
		$this->exchangeArray($data);
	}



	/**
	 * set でデータがセットされる前に、データを加工して返す。
	 * @param  string  $name
	 * @param  mixed  $value
	 * @return  mixed  加工した値
	 */
	protected function beforeSet($name, $value) {
		return $value;
	}


	/**
	 * $array の $key の値を ! empty で検査する。
	 * @param  string  $key
	 * @param  array  $array  
	 * @return  boolean
	 */
	private function arrayValueNotEmpty($key, $array) {
		return ! empty($array[$key]);
	}
	/**
	 * $array の $key の値を array_key_exists で検査する。
	 * @param  string  $key
	 * @param  array  $array  
	 * @return  boolean
	 */
	private function arrayKeyExists($key, $array) {
		return array_key_exists($key, $array);
	}

	/**
	 * フィールドの値を取得する。
	 * 関連はオブジェクトに展開されます。
	 * @param  mixed  $name  Field name or data array.
	 * @param  mixed  $value[optional]  値が無いと判断された時に返す値。Default is null。
	 * @param  string  $strict[optional]  値が無いと判断する基準。true はキーの有無で判定。false は empty で判定。Default is true。
	 * @param  boolean  $throws[optional]  true を指定すると、自身のフィールド、またはアソシエーションの以外で、フィールドがみつからない時には例外をスローする。Default is false.
	 */
	public function get($name, $default=null, $strict=true, $throws=false) {
		$notEmpty = $strict ? 'arrayKeyExists' : 'arrayValueNotEmpty';

		//Own fields
		$alias = $this->model()->alias;
		if ($this->model()->hasField($name) and isset($this[$alias])) {
			if ($this->$notEmpty($name, $this[$alias])) {
				return $this[$alias][$name];
			} else {
				return $default;
			}
			
		}

		//Associations
		if ($this->hasAssocObject($name)) {
			return $this->getAssocObject($name);
		}
		if ($AssocModel = $this->model()->getAssociationModel($name)) {
			switch ($this->model()->getAssociationType($name)) {
			case 'hasMany':
			case 'hasAndBelongsToMany':
				$records = isset($this[$name]) ? $this[$name] : array();
				$records = $AssocModel->buildRecordCollection($records, false);
				$records->setAssocObject($this->model()->alias, $this);
				$this->setAssocObject($name, $records);
				return $records;
			case 'hasOne':
			case 'belongsTo':
				if (isset($this[$name])) {
					$record = (array) $this[$name];
					foreach ($record as $v) { //？この処理の意味は？後日見直す。
						if ($v != null) {
							$record = $AssocModel->buildRecord($record, false);
							$record->setAssocObject($this->model()->alias, $this);
							$this->setAssocObject($name, $record);
							return $record;
						}
					}
				}
				return $this->setAssocObject($name, null);
			}
		}

		//Other fileds
		if ($this->$notEmpty($name, $this)) {
			return $this[$name];
		}

		if ($throws) {
			ob_start();
			debug($this->getData(), false);
			$vardump = ob_get_contents();
			ob_end_clean();
			throw new InternalErrorException("\"$name\" field not found on " . get_class($this) . "\n" . $vardump);
		}

		return $default;
	}

	public function setAssocObject($name, $assoc) {
		$this->_assocobjects[$name] = $assoc;
	}
	public function hasAssocObject($name) {
		return array_key_exists($name, $this->_assocobjects);
	}
	public function getAssocObject($name) {
		return $this->_assocobjects[$name];
	}


	/**
	 * フィールドに値をセットする。
	 * @param  mixed  $one  Field name or data array.
	 * @param  mixed  $two  Field value or pass field names.
	 */
	public function set($one, $two=null) {
		if (is_array($one)) {
			$pass = (array) $two;
			foreach ($one as $name => $value) {
				if (empty($pass) or in_array($name, $pass)) {
					$this->set($name, $value);
				}
			}
		} else if ($this->model()->hasField($one)) {
			$this[$this->model()->alias][$one] = $this->beforeSet($one, $two);
		} else {
			$this[$one] = $this->beforeSet($one, $two);
		}
	}

	public function __set($name, $value) {
		$this->set($name, $value);
	}

	public function __get($name) {
		return $this->get($name, null, true);
	}

	public function __isset($name) {
		//Own fields
		if ($this->model()->hasField($name)) {
			$alias = $this->model()->alias;
			return isset($this[$alias]) and isset($this[$alias][$name]);
		}

		//Associations
		if ($this->hasAssocObject($name) or $this->model()->getAssociationType($name)) {
			$assoc = $this->get($name);
			if ($assoc instanceof FkRecordCollection) {
				return ! $assoc->isEmpty();
			} else {
				return ! empty($assoc);
			}
		}

		//Others
		return $this->offsetExists($name);
	}




}
