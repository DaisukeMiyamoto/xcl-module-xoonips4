<?php

require_once XOOPS_TRUST_PATH . '/modules/' . $mytrustdirname . '/class/Enum.class.php';
require_once XOOPS_TRUST_PATH . '/modules/' . $mytrustdirname . '/class/datatype/DataType.class.php';

abstract class Xoonips_ViewType {
	private $id;
	private $name;
	private $preselect;
	private $module;
	private $multi;
	protected $dirname;
	protected $trustDirname;
	protected $search;
	private $xoopsTpl;
	private $template;

	abstract protected function getInputView($field, $value, $groupLoopId);
	abstract protected function getDisplayView($field, $value, $groupLoopId);
	abstract protected function getDetailDisplayView($field, $value, $display);

	public function setId($v) {
		$this->id = $v;
	}

	public function getId() {
		return $this->id;
	}

	public function setName($v) {
		$this->name = $v;
	}

	public function getName() {
		return $this->name;
	}

	public function setPreslect($v) {
		$this->preselect = $v;
	}

	public function getPreslect() {
		return $this->preselect;
	}

	public function setModule($v) {
		$this->module = $v;
	}

	public function getModule() {
		return $this->module;
	}

	public function setMulti($v) {
		$this->multi = $v;
	}

	public function getMulti() {
		return $this->multi;
	}

	public function setDirname($v) {
		$this->dirname = $v;
	}

	public function setTrustDirname($v) {
		$this->trustDirname = $v;
	}

	public function setSearch($obj) {
		$this->search = $obj;
	}

	public function setXoopsTpl($obj) {
		$this->xoopsTpl = $obj;
	}

	protected function getXoopsTpl() {
		return $this->xoopsTpl;
	}

	public function isMulti() {
		if ($this->multi == 0) {
			return false;
		}
		return true;
	}

	protected function isLayered() {
		return true;
	}

	public function hasSelectionList() {
		return false;
	}

	/**
	 * generate html name attribute value for item
	 *
	 * @param object $field
	 * @param int $groupLoopId
	 * @param string $id
	 * @return string
	 */
	protected function getFieldName($field, $groupLoopId, $id = null) {
		if ($groupLoopId == 0) {
			return '0' . Xoonips_Enum::ITEM_ID_SEPARATOR . $groupLoopId . Xoonips_Enum::ITEM_ID_SEPARATOR . $field->getId();
		}

		if ($id == null) {
			return $field->getFieldGroupId() . Xoonips_Enum::ITEM_ID_SEPARATOR . $groupLoopId . Xoonips_Enum::ITEM_ID_SEPARATOR . $field->getId();
		} else {
			return $field->getFieldGroupId() . Xoonips_Enum::ITEM_ID_SEPARATOR . $groupLoopId . Xoonips_Enum::ITEM_ID_SEPARATOR . $id;
		}
	}

	/**
	 * set template name
	 */
	public function setTemplate() {
		$this->template = $this->dirname . '_viewtype.html';
	}

	/**
	 * get edit view for moderator
	 *
	 * @param object $field
	 * @param array $data
	 * @param int $groupLoopId
	 * @return array
	 */
	public function getEditViewForModerator($field, &$data, $groupLoopId) {
		return $this->getEditView($field, $data, $groupLoopId);
	}

	/**
	 * get edit view with data for moderator
	 *
	 * @param object $field
	 * @param array $data
	 * @param int $groupLoopId
	 * @return array
	 */
	public function getEditViewWithDataForModerator($field, &$data, $groupLoopId) {
		return $this->getEditViewForModerator($field, $data, $groupLoopId);
	}

	/**
	 * item owners must
	 *
	 * @return boolean
	 */
	public function isItemOwnersMust() {
		return false;
	}

	/**
	 * item display
	 *
	 * @param int $op
	 * @return boolean
	 */
	public function isDisplay($op) {
		return true;
	}

	/**
	 * field name display
	 *
	 * @return boolean
	 */
	public function isDisplayFieldName() {
		return true;
	}

	/**
	 * get registry view
	 *
	 * @param object $field
	 * @return array
	 */
	public function getRegistryView($field) {
		return $this->getInputView($field, $field->getDefault(), 1);
	}

	/**
	 * get registry view with data
	 *
	 * @param object $field
	 * @param array $data
	 * @param int $groupLoopId
	 * @return array
	 */
	function getRegistryViewWithData($field, $value, $groupLoopId) {
		return $this->getInputView($field, $value, $groupLoopId);
	}

	/**
	 * get edit view
	 *
	 * @param object $field
	 * @param array $data
	 * @param int $groupLoopId
	 * @return array
	 */
	public function getEditView($field, $value, $groupLoopId) {
		return $this->getInputView($field, $value, $groupLoopId);
	}

	/**
	 * get edit view with data
	 *
	 * @param object $field
	 * @param array $data
	 * @param int $groupLoopId
	 * @return array
	 */
	function getEditViewWithData($field, $value, $groupLoopId) {
		return $this->getEditView($field, $value, $groupLoopId);
	}

	/**
	 * get search input view
	 *
	 * @param object $field
	 * @param array $data
	 * @param int $groupLoopId
	 * @return array
	 */
	function getSearchInputView($field, $value, $groupLoopId) {
		return $this->getInputView($field, $value, $groupLoopId);
	}

	/**
	 * registry input check
	 *
	 * @param object $errors
	 * @param object $field
	 * @param string $value
	 * @param string $fieldName
	 */
	public function inputCheck(&$errors, $field, $value, $fieldName) {
		// dataCheck
		$field->getDataType()->inputCheck($errors, $field, $value, $fieldName);
	}

	/**
	 * must input check
	 *
	 * @param object $errors
	 * @param object $field
	 * @param string $value
	 * @param string $fieldName
	 */
	public function mustCheck(&$errors, $field, $value, $fieldName) {
		if ($field->getEssential() == 1 && trim($value) == '') {
			$parameters = array();
			$parameters[] = $field->getName();
			$errors->addError('_MD_' . strtoupper($this->trustDirname) . '_ERROR_REQUIRED', $fieldName, $parameters);
		}
	}

	/**
	 * edit input check
	 *
	 * @param object $errors
	 * @param object $field
	 * @param string $value
	 * @param string $fieldName
	 * @param int $uid
	 */
	public function editCheck(&$errors, $field, $value, $fieldName, $uid) {
		// dataCheck
		$field->getDataType()->inputCheck($errors, $field, $value, $fieldName);
	}

	/**
	 * must input check
	 *
	 * @param object $errors
	 * @param object $field
	 * @param string $value
	 * @param string $fieldName
	 * @return boolean
	 */
	public function ownersEditCheck(&$errors, $field, $value, $fieldName) {
		return true;
	}

	/**
	 * search input check
	 *
	 * @param object $errors
	 * @param object $field
	 * @param string $value
	 * @param string $fieldName
	 */
	public function searchCheck(&$errors, $field, $value, $fieldName) {
		// dataCheck
		$field->getDataType()->inputCheck($errors, $field, $value, $fieldName);
	}

	/**
	 * set registry data into array
	 *
	 * @param object $field
	 * @param array $data
	 * @param array $sqlStrings
	 * @param int $groupLoopId
	 */
	public function doRegistry($field, &$data, &$sqlStrings, $groupLoopId) {
		$tableName = $field->getTableName();
		$columnName = $field->getColumnName();

		// get data
		$value = $this->getData($field, $data, $groupLoopId);
		$tableData;
		$groupData;
		$columnData;

		if (isset($sqlStrings[$tableName])) {
			$tableData = &$sqlStrings[$tableName];
		} else {
			$tableData = array();
			$sqlStrings[$tableName] = &$tableData;
		}

		if (strPos($tableName, '_extend') !== false) {
			$groupid = $field->getFieldGroupId();
			if (isset($tableData[$groupid])) {
				$groupData = &$tableData[$groupid];
			} else {
				$groupData = array();
				$tableData[$groupid] = &$groupData;
			}

			if (isset($groupData[$columnName])) {
				$columnData = &$groupData[$columnName];
			} else {
				$columnData = array();
				$groupData[$columnName] = &$columnData;
			}

		} else {
			if (isset($tableData[$columnName])) {
				$columnData = &$tableData[$columnName];
			} else {
				$columnData = array();
				$tableData[$columnName] = &$columnData;
			}
		}

		// set value into array
		$columnData[] = $field->getDataType()->convertSQLStr($value);
	}

	/**
	 * set edit data into array
	 *
	 * @param object $field
	 * @param array $data
	 * @param array $sqlStrings
	 * @param int $groupLoopId
	 */
	public function doEdit($field, &$data, &$sqlStrings, $groupLoopId) {
		$this->doRegistry($field, $data, $sqlStrings, $groupLoopId);
	}

	/**
	 * get data
	 *
	 * @param object $field
	 * @param array $data
	 * @param int $groupLoopId
	 * @return string
	 */
	protected function getData($field, &$data, $groupLoopId) {
		$ret = array();
		foreach ($data as $key => $v) {
			if (stristr($key, Xoonips_Enum::ITEM_ID_SEPARATOR) !== FALSE) {
				$idArray = explode(Xoonips_Enum::ITEM_ID_SEPARATOR, $key);
				if ($idArray[2] == $field->getId() && $idArray[0] == $field->getFieldGroupId()) {

					$ret[] = trim($v);
				}
			}
		}
		return $ret[$groupLoopId - 1];
	}

	/**
	 * set search data into array
	 *
	 * @param object $field
	 * @param array $data
	 * @param array $sqlStrings
	 * @param int $groupLoopId
	 * @param boolean $scopeSearchFlg
	 * @param boolean $isExact
	 */
	public function doSearch($field, &$data, &$sqlStrings, $groupLoopId, $scopeSearchFlg, $isExact) {
		$tableName = $field->getTableName();
		$columnName = $field->getColumnName();
		if (!isset($data[$this->getFieldName($field, $groupLoopId)])) {
			return;
		}
		$value = $data[$this->getFieldName($field, $groupLoopId)];

		if (isset($sqlStrings[$tableName])) {
			$tableData = &$sqlStrings[$tableName];
		} else {
			$tableData = array();
			$sqlStrings[$tableName] = &$tableData;
		}

		if ($value != '') {
			if ($field->getScopeSearch() == 1 && $scopeSearchFlg) {
				if ($value[0] != '') {
					$v = $field->getDataType()->convertSQLStr($value[0]);
					$tableData[] = sprintf('"t1".%s>=%s', $columnName, $v);
				}
				if ($value[1] != '') {
					$v = $field->getDataType()->convertSQLStr($value[1]);
					$tableData[] = sprintf('"t1".%s<=%s', $columnName, $v);
				}
				// scope search
			} else {
				$tableData[] = $this->search->getSearchSql($columnName, $value, _CHARSET, $field->getDataType(), $isExact);
			}
		}
	}

	/**
	 * get search view
	 *
	 * @param object $field
	 * @param int $groupLoopId
	 * @return string
	 */
	public function getSearchView($field, $groupLoopId) {
		$ret = $this->getSearchInputView($field, '', $groupLoopId);
		// scope search
		if ($field->getScopeSearch() == 1) {
			$fieldName = $field->getFieldGroupId() . Xoonips_Enum::ITEM_ID_SEPARATOR . $groupLoopId . Xoonips_Enum::ITEM_ID_SEPARATOR . $field->getId();
			$ret = str_replace("name=\"$fieldName\"", "name=\"$fieldName" . "[]\"", $ret);
		}
		$this->getXoopsTpl()->assign('viewType', 'search');
		$this->getXoopsTpl()->assign('from', $ret);
		if ($field->getScopeSearch() == 1) {
			$this->getXoopsTpl()->assign('to', $ret);
		} else {
			$this->getXoopsTpl()->assign('to', null);
		}
		self::setTemplate();
		return $this->getXoopsTpl()->fetch('db:'. $this->template);
	}

	/**
	 *
	 * get search view with data
	 *
	 * @param object $field
	 * @param string $value
	 * @param int $groupLoopId
	 * @return string
	 */
	public function getSearchViewWithData($field, $value, $groupLoopId) {
		if ($field->getScopeSearch() == 1) {
			$fieldName = $field->getFieldGroupId() . Xoonips_Enum::ITEM_ID_SEPARATOR . $groupLoopId . Xoonips_Enum::ITEM_ID_SEPARATOR . $field->getId();
			if (is_array($value)) {
				$from = $this->getSearchInputView($field, $value[0], $groupLoopId);
				$to = $this->getSearchInputView($field, $value[1], $groupLoopId);
				$from = str_replace("name=\"$fieldName\"", "name=\"$fieldName" . "[]\"", $from);
				$to = str_replace("name=\"$fieldName\"", "name=\"$fieldName" . "[]\"", $to);
			} else {
				$ret = $this->getSearchInputView($field, $value, $groupLoopId);
				$from = $ret;
			}
		} else {
			$ret = $this->getSearchInputView($field, $value, $groupLoopId);
			$from = $ret;
		}
		$this->getXoopsTpl()->assign('viewType', 'search');
		$this->getXoopsTpl()->assign('from', $from);
		if ($field->getScopeSearch() == 1 && is_array($value)) {
			$this->getXoopsTpl()->assign('to', $to);
		} else {
			$this->getXoopsTpl()->assign('to', null);
		}
		self::setTemplate();
		return $this->getXoopsTpl()->fetch('db:'. $this->template);
	}

	/**
	 * get confirm view
	 *
	 * @param object $field
	 * @param string $value
	 * @param int $groupLoopId
	 * @return string
	 */
	public function getConfirmView($field, $value, $groupLoopId) {
		return $this->getDisplayView($field, $value, $groupLoopId);
	}

	/**
	 * get detail view
	 *
	 * @param object $field
	 * @param string $value
	 * @param int $groupLoopId
	 * @return string
	 */
	public function getDetailView($field, $value, $groupLoopId, $display) {
		return $this->getDetailDisplayView($field, $value, $display);
	}

	/**
	 * get detail view
	 *
	 * @param object $field
	 * @param string $value
	 * @param int $groupLoopId
	 * @return string
	 */
	public function getDetailViewForModerator($field, $value, $groupLoopId) {
		return $this->getDetailDisplayView($field, $value, true);
	}

	/**
	 * get detail view for certify
	 *
	 * @param object $field
	 * @param string $value
	 * @param int $groupLoopId
	 * @return string
	 */
	public function getDetailViewForCertify($field, $value, $groupLoopId) {
		return $this->getDetailDisplayView($field, $value, true);
	}

	/**
	 * get meta info
	 *
	 * @param object $field
	 * @param string $value
	 * @return string
	 */
	public function getMetaInfo($field, $value) {
		return $value;
	}

	/**
	 * get item owners edit view
	 *
	 * @param object $field
	 * @param string $value
	 * @param int $groupLoopId
	 * @return string
	 */
	public function getItemOwnersEditView($field, $value, $groupLoopId) {
		return $this->getDisplayView($field, $value, $groupLoopId);
	}

	/**
	 * get item owners edit view with data
	 *
	 * @param object $field
	 * @param string $value
	 * @param int $groupLoopId
	 * @return string
	 */
	public function getItemOwnersEditViewWithData($field, $value, $groupLoopId) {
		return $this->getItemOwnersEditView($field, $value, $groupLoopId);
	}

	/**
	 * get meta data
	 *
	 * @param object $field
	 * @param array $data
	 * @return mixed
	 */
	public function getMetadata($field, &$data) {
		$table = $field->getTableName();
		$column = $field->getColumnName();
		$detail_id = $field->getId();
		if ($table == $this->dirname . '_item_title') {
			foreach($data[$table] as $value){
				if($value['item_field_detail_id'] == $detail_id){
					return $value[$column];
				}
			}
		} else if ($table == $this->dirname . '_item') {
			return $data[$table][$column];
		} else {
			$objs = $data[$table];
			$ret = array();
			foreach ($objs as $obj) {
				$ret[] = $obj[$column];
			}
			// return implode(',', $ret);
			return $ret;
		}
	}

	/**
	 * get entity data
	 *
	 * @param object $field
	 * @param array $data
	 * @return mixed
	 */
	public function getEntitydata($field, &$data) {
		return $this->getMetadata($field, $data);
	}

	/**
	 * get itemtype value set
	 *
	 * @return array
	 */
	public function getItemtypeValueSet() {
		$valueSetBean = Xoonips_BeanFactory::getBean('ItemFieldValueSetBean', $this->dirname, $this->trustDirname);
		return $valueSetBean->getSelectNames();
	}

	/**
	 * get itemtype value detail
	 *
	 * @param $list list value
	 * @return string
	 */
	public function getItemtypeValueDetail($list) {
		$valueSetBean = Xoonips_BeanFactory::getBean('ItemFieldValueSetBean', $this->dirname, $this->trustDirname);
		return $valueSetBean->getValueDetail($list);
	}

	/**
	 * get list block view
	 *
	 * @param mixed $value
	 * @param boolean $disabled
	 * @return string
	 */
	public function getListBlockView($value, $disabled = '') {
		$this->getXoopsTpl()->assign('viewType', 'list');
		$this->getXoopsTpl()->assign('value', $value);
		self::setTemplate();
		return $this->getXoopsTpl()->fetch('db:'. $this->template);
	}

	/**
	 * get default value block view
	 *
	 * @param string $list
	 * @param mixed $value
	 * @param boolean $disabled
	 * @return string
	 */
	public function getDefaultValueBlockView($list, $value, $disabled = '') {
		$this->getXoopsTpl()->assign('viewType', 'default');
		$this->getXoopsTpl()->assign('value', $value);
		$this->getXoopsTpl()->assign('disabled', $disabled);
		self::setTemplate();
		return $this->getXoopsTpl()->fetch('db:'. $this->template);
	}

	/**
	 * get simple search block view
	 *
	 * @param string $field
	 * @param string $value
	 * @param int $itemtypeId
	 * @return string
	 */
	public function getSimpleSearchView($field, $value, $itemtypeId) {
		$fieldName = $this->getFieldName($field, $itemtypeId);
		$this->getXoopsTpl()->assign('viewType', 'simpleSearch');
		$this->getXoopsTpl()->assign('fieldName', $fieldName);
		$this->getXoopsTpl()->assign('value', $value);
		self::setTemplate();
		return $this->getXoopsTpl()->fetch('db:'. $this->template);
	}

	/**
	 * must Create item_extend table
	 *
	 * @return boolean
	 */
	public function mustCreateItemExtendTable() {
		return true;
	}

	/**
	 * must create user_extend table
	 *
	 * @return boolean
	 */
	public function mustCreateUserExtendTable() {
		return true;
	}

	/**
	 * is index
	 *
	 * @param
	 * @return boolean
	 */
	public function isIndex() {
		return false;
	}

	/**
	 * is create user
	 *
	 * @return boolean
	 */
	public function isCreateUser() {
		return false;
	}

	/**
	 * is date
	 *
	 * @return boolean
	 */
	public function isDate() {
		return false;
	}
}

