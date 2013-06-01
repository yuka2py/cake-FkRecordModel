<?php
/**
 * Supports Entitiy model on CakePHP.
 *
 * @version       0.2.0
 * @copyright     Copyright 2012-2013, Foreignkey, Inc. (http://foreignkey.jp)
 * @package       FkRecordModel.Model
 * @license       GPLv3 License
 */

App::uses('Model', 'Model');
App::uses('FkRecord', 'FkRecordModel.Model');
App::uses('FkRecordCollection', 'FkRecordModel.Model');
require_once 'FkRecordExceptions.php';

class FkRecordModel extends Model
{
	protected $recordClassName;
	protected $recordCollectionClassName;

	public $verboseName = array();
	
	public $errorMessageWrapper = ":message";
	public $errorFieldWrapper = "[:name] :message\n";

	/**
	 * Throws exceptions on validation error, If set True.
	 * Default is False, means the result as boolean on the validation.
	 * @var boolean
	 */
	public $throwsValidationError = false;


	public function __construct($id=false, $table=null, $ds=null) {
		parent::__construct($id, $table, $ds);

		//Decide $recordClassName
		if (empty($this->recordClassName)) {
			$this->recordClassName = get_class($this) . 'Record';
			if (!class_exists($this->recordClassName) and !App::uses($this->recordClassName, 'Model')) {
				if (class_exists('AppRecord') or App::uses('AppRecord', 'Model')) {
					$this->recordClassName = 'AppRecord';
				} else {
					$this->recordClassName = 'FkRecord';
				}
			}
		}
		//Decide $recordCollectionClassName
		if (empty($this->recordCollectionClassName)) {
			$this->recordCollectionClassName = get_class($this) . 'RecordCollection';
			if (!class_exists($this->recordCollectionClassName) and !App::uses($this->recordCollectionClassName, 'Model')) {
				if (class_exists('AppRecordCollection') or App::uses('AppRecordCollection', 'Model')) {
					$this->recordCollectionClassName = 'AppRecordCollection';
				} else {
					$this->recordCollectionClassName = 'FkRecordCollection';
				}
			}
		}
	}


	public function getRecordClassName() {
		return $this->recordClassName;
	}


	public function getVerboseName($filedName) {
		if (isset($this->verboseName[$filedName])) {
			return $this->verboseName[$filedName];
		} else {
			return null;
		}
	}


	public function getAssociationType($associationName) {
		static $_types = array();
		if ( ! array_key_exists($associationName, $_types)) {
			if (isset($this->hasMany[$associationName])) {
				$_types[$associationName] = 'hasMany';
			} else if (isset($this->hasOne[$associationName])) {
				$_types[$associationName] = 'hasOne';
			} else if (isset($this->belongsTo[$associationName])) {
				$_types[$associationName] = 'belongsTo';
			} else if (isset($this->hasAndBelongsToMany[$associationName])) {
				$_types[$associationName] = 'hasAndBelongsToMany';
			} else {
				$_types[$associationName] = null;
			}
		}
		return $_types[$associationName];
	}


	public function getAssociationClassName($associationName) {
		$associationType = $this->getAssociationType($associationName);
		if ($associationType) {
			$associationInfoByType = $this->$associationType;
			return $associationInfoByType[$associationName]['className'];
		} else {
			return null;
		}
	}


	public function getAssociationModel($associationName) {
		static $_models = array();
		if ( ! array_key_exists($associationName, $_models)) {
			$className = $this->getAssociationClassName($associationName);
			if ($className) {
				$_models[$associationName] = new $className();
			} else {
				$_models[$associationName] = null;
			}
		}
		return $_models[$associationName];
	}


	/**
	 * Build new FkRecord object.
	 * @param  array  $data  Array as a return of the Model::find('first', ...
	 * @param  boolean  $primary[optional]  Whether this model is being queried directly (vs. being queried as an association) default is false.
	 * @return FkRecord
	 */
	public function buildRecord($data=array(), $primary=false) {
		if (false === $data) {
			return false;
		}
		if ( ! $primary) {
			$data = array($this->alias => $data);
		}
		return new $this->recordClassName($this, $data);
	}


	/**
	 * Build new FkRecordCollection 
	 * @param  array $rawdata  Array as a return of the Model::find('all', ...
	 * @return FkRecordCollection
	 */
	public function buildRecordCollection($rawdata=array(), $primary=false) {
		return new $this->recordCollectionClassName($this, $rawdata, $primary);
	}


	/**
	 * Extended find method.
	 * This method returns the FkRecord or FkRecordCollection.
	 * @param  string  $type  See Model reference
	 * @param  array  $query  See Model reference
	 * @param  boolean  $raw  When True was given, returns Original raw data.
	 * @return mixed         When $type are [all|threaded|neighbors], returns the FkRecordCollection, 
	 *                            When $type are 'first', returns the FkRecord.
	 *                            Other returns the rawdata. 
	 */
	public function find($type='first', $query=array(), $raw=false) {
		$rawdata = parent::find($type, $query);
		if ($raw) {
			return $rawdata;
		}
		switch ($type) {
			case 'all':
			case 'threaded':
			case 'neighbors':
				return $this->buildRecordCollection($rawdata, true);
			case 'first':
				return $this->buildRecord($rawdata, true);
			case 'count':
			case 'list':
			default:
				return $rawdata;
		}
	}
}






