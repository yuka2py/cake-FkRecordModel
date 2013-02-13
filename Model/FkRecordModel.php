<?php


App::uses('Model', 'Model');
App::uses('FkRecord', 'FkRecordModel.Model');
App::uses('FkRecordCollection', 'FkRecordModel.Model');

/**
 * 
 */
class FkRecordModel extends Model
{
	protected $recordClassName;

	public $verboseName = array();
	
	public $errorMessageWrapper = ":message";
	public $errorFieldWrapper = "[:name] :message\n";


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
	}


	public function getVerboseName($filedName) {
		if (isset($this->verboseName[$filedName])) {
			return $this->verboseName[$filedName];
		} else {
			return null;
		}
	}

	public function getAssociationType($associationName) {
		static $_map = array();
		if (!array_key_exists($associationName, $_map)) {
			if (isset($this->hasMany[$associationName])) {
				$_map[$associationName] = 'hasMany';
			} else if (isset($this->hasOne[$associationName])) {
				$_map[$associationName] = 'hasOne';
			} else if (isset($this->belongsTo[$associationName])) {
				$_map[$associationName] = 'belongsTo';
			} else if (isset($this->hasAndBelongsToMany[$associationName])) {
				$_map[$associationName] = 'hasAndBelongsToMany';
			} else {
				$_map[$associationName] = null;
			}
		}
		return $_map[$associationName];
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
		$className = $this->getAssociationClassName($associationName);
		if ($className) {
			$_models[$associationName] = new $className();
		} else {
			$_models[$associationName] = null;
		}
		return $_models[$associationName];
	}


	/**
	 * Build new FkRecord object.
	 * @param  array  $rawdata  Array as a return of the Model::find('first', ...
	 * @return FkRecord
	 */
	public function buildRecord($rawdata=array(), $bracket=false) {
		if ($bracket) {
			$rawdata = array($this->name => $rawdata);
		}
		return false === $rawdata 
			? false : new $this->recordClassName($this, $rawdata);
	}

	/**
	 * Build new FkRecordCollection 
	 * @param  array $rawdata  Array as a return of the Model::find('all', ...
	 * @return FkRecordCollection
	 */
	public function buildRecordCollection($rawdata=array(), $bracket=false) {
		return new FkRecordCollection($this, $rawdata, $bracket);
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
				return $this->buildRecordCollection($rawdata);
			case 'first':
				return $this->buildRecord($rawdata);
			case 'count':
			case 'list':
			default:
				return $rawdata;
		}
	}
}






