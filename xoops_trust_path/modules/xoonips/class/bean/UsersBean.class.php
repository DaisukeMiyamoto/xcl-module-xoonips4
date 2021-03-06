<?php

require_once XOONIPS_TRUST_PATH . '/class/core/BeanBase.class.php';
require_once XOONIPS_TRUST_PATH . '/class/Enum.class.php';
require_once XOOPS_ROOT_PATH . '/class/xoopsuser.php';

/**
 * @brief operate users table 
 *
 */
class Xoonips_UsersBean extends Xoonips_BeanBase {
	private static $userCache = array();
	private static $userNameCache = array();
	private $linkTable = null;

	/**
	 * Constructor
	 **/
	public function __construct($dirname, $trustDirname) {
		parent::__construct($dirname, $trustDirname);
		$this->setTableName('users');
		$this->linkTable = $this->prefix('groups_users_link');
	}
	
	/**
	 * 
	 * get userBasicInfo
	 * 
	 * @param
	 * @return array
	 */
	public function getUserBasicInfo($id) {
		if (isset(self::$userCache[$id])) {
			return self::$userCache[$id];
		}
		$sql = 'SELECT * FROM ' . $this->table . ' WHERE uid=' . $id;
		$result = $this->execute($sql);
		if (!$result) {
			return false;
		}
		$row = $this->fetchArray($result);
		if (!$row) {
			return false;
		}
		$this->freeRecordSet($result);
		return $row;
	}
	
	/**
	 *
	 * get userBasicInfo by uname
	 *
	 * @param
	 * @return array
	 */
	public function getUserBasicInfoByUname($uname) {
		if (isset(self::$userNameCache[$uname])) {
			return self::$userNameCache[$uname];
		}
		$sql = 'SELECT * FROM ' . $this->table . ' WHERE uname=' . Xoonips_Utils::convertSQLStr($uname);
		$result = $this->execute($sql);
		if (!$result) {
			return false;
		}
		$row = $this->fetchArray($result);
		if (!$row) {
			return false;
		}
		$this->freeRecordSet($result);
		return $row;
	}

	/**
	 * 
	 * get userBasicInfo by name
	 * 
	 * @param  var $uname:uname
	 *          var $name:name
	 * @return array
	 */
	public function getUserBasicInfoByName($uname, $name) {
		$ret = array();
		$uname = Xoonips_Utils::convertSQLStrLike($uname);
		$name = Xoonips_Utils::convertSQLStrLike($name);
		$sql = 'SELECT * FROM ' . $this->table . " WHERE uname LIKE '%$uname%' AND name LIKE '%$name%' ORDER BY uname";
		$result = $this->execute($sql);
		if (!$result) {
			return false;
		}
		while ($row = $this->fetchArray($result)) {
			$ret[] = $row;
			self::$userCache[$row['uid']] = $row;
		}
		$this->freeRecordSet($result);
		return $ret;
	}
	
	/**
	 * 
	 * is moderator
	 * 
	 * @param  int $uid:uid
	 * @return array
	 */
	public function isModerator($uid) {
		$groupsBean = Xoonips_BeanFactory::getBean('GroupsBean', $this->dirname, $this->trustDirname);
		$moderator_gid = $groupsBean->getModeratorGroupId();
		$linkBean = Xoonips_BeanFactory::getBean('GroupsUsersLinkBean', $this->dirname, $this->trustDirname);
		$linkInfo = $linkBean->getGroupUserLinkInfo($moderator_gid, $uid);
		if ($linkInfo && count($linkInfo) > 0) {
			return true;
		}
		return false;
	}
	
	/**
	 * 
	 * is groupmanger
	 * 
	 * @param  int $groupId:groupid
	 *          int $uid:uid
	 * @return array
	 */
	public function isGroupManager($groupId, $uid) {
		$ret = false;
		$sql = 'SELECT * FROM ' . $this->linkTable . " WHERE groupid=$groupId"
			. " AND uid=${uid} AND is_admin=" . Xoonips_Enum::GRP_ADMINISTRATOR
			. ' AND activate<>' . Xoonips_Enum::GRP_US_JOIN_REQUIRED;
		if (($result = $this->execute($sql))
			&& $this->getRowsNum($result) > 0) {
			$ret = true;
		} else {
			$ret = false;
		}
		$this->freeRecordSet($result);
		return $ret;
	}
	
	/**
	 * 
	 * is groupmember
	 * 
	 * @param  int $groupId:groupid
	 *          int $uid:uid
	 * @return array
	**/
	public function isGroupMember($groupId, $uid) {
		$ret = false;
		$sql = 'SELECT * FROM ' . $this->linkTable
			. " WHERE groupid=$groupId"
			. " AND uid=${uid} AND is_admin=" . Xoonips_Enum::GRP_USER
			. ' AND activate<>' . Xoonips_Enum::GRP_US_JOIN_REQUIRED;
		if (($result = $this->execute($sql))
			&& $this->getRowsNum($result) > 0) {
			$ret = true;
		} else {
			$ret = false;
		}
		$this->freeRecordSet($result);
		return $ret;
	}

	/**
	 * 
	 * get all users
	 * 
	 * @param
	 * @return boolean
	 */
	public function getAllUsers() {
		$ret = array();
		$sql = "SELECT * FROM $this->table";
		$result = $this->execute($sql);
		if (!$result) {
			return false;
		}
		while ($row = $this->fetchArray($result)) {
			$ret[] = $row;
			self::$userCache[$row['uid']] = $row;
		}
		$this->freeRecordSet($result);
		return $ret;
	}

	public function insert($columns, $values, &$newId, $actkey, $userType) {
		//get system time
		$regtime = time();
		if ($userType == Xoonips_Enum::USER_TYPE_MODERATOR) {
			$columns = $columns . 'user_regdate';
			$values = $values . "'$regtime'";
		} else {
			$columns = $columns . 'actkey,user_regdate';
			$values = $values . "'$actkey','$regtime'";
			$myxoopsConfigUser = Xoonips_Utils::getXoopsConfigs(XOOPS_CONF_USER);
			if ($myxoopsConfigUser['activation_type'] != 1) {
				$columns = $columns . ',level';
				$values = $values . ',0';
			}
		}
		//execute sql
		$sql = "INSERT INTO $this->table ($columns) VALUES ($values)";
		if (!$this->execute($sql)) {
			return false;
		} else {
			$newId = $this->getInsertId();
			return true;
		}
	}

	/**
	 * 
	 * activateuser
	 * 
	 * @param  $user
	 * @return boolean
	 */
	public function activateUser(&$user) {
		$uid = $user['uid'];
		if ($user['level'] != Xoonips_Enum::USER_NOT_ACTIVATE) {
			return true;
		}

		$sql = 'UPDATE ' . $this->table
		    	. ' SET level = ' . Xoonips_Enum::USER_NOT_CERTIFIED
		        . " WHERE uid = $uid";
	            
		$result = $this->execute($sql);
		if (!$result) {
			return false;
		}

		return true;
	}

	/**FIXME inactivateUser methods not use
	 *
	 * activateuser
	 * 
	 * @param  $user
	 * @return boolean
	 */
	public function inactivateUser(&$user) {
		$uid = $user['uid'];
		if ($user['level'] != Xoonips_Enum::USER_NOT_ACTIVATE) {
			return true;
		}

		$sql = 'UPDATE ' . $this->table
			. ' SET level = ' . Xoonips_Enum::USER_NOT_ACTIVATE
			. " WHERE uid = $uid";

		$result = $this->execute($sql);
		if (!$result) {
			return false;
		}

		return true;
	}

	/**FIXME admin level check
	 *
	 * count activated users
	 *
	 * @param  $isActivate
	 * @return int count
	*/
	public function getCountActivateUsers($isActivate) {
		$sql = 'SELECT COUNT(uid) AS cnt FROM ' . $this->table . ' WHERE level';
		if ($isActivate) {
			$sql .= '>=' . Xoonips_Enum::USER_CERTIFIED;
		} else {
			$sql .= '<' . Xoonips_Enum::USER_CERTIFIED;
		}

		$result = $this->execute($sql);
		if (!$result) {
			return false;
		}
		$ret = $this->fetchArray($result);

		$this->freeRecordSet($result);
		return $ret['cnt'];
	}

	/**
	 * 
	 * count activated users
	 * 
	 * @param  $isActivate
	 * @return int count
	 */
	public function getCountUsedFieldValue($table, $column, $value) {
		$sql = 'SELECT COUNT(uid) AS cnt FROM ' . $this->prefix($table) . ' WHERE value=' . Xoonips_Utils::convertSQLStr($value);

		$result = $this->execute($sql);
		if (!$result) {
			return false;
		}
		$ret = $this->fetchArray($result);

		$this->freeRecordSet($result);
		return $ret['cnt'];
	}

	//TODO DELETE Check
	public function getUserExtend($tableName, $uid) {
		$ret = array();
		$table = $this->prefix($tableName);
	  	$sql = "SELECT * FROM $table WHERE uid=$uid ORDER BY occurrence_number";
		$result = $this->execute($sql);
		if (!$result) {
			return false;
		}
		while ($row = $this->fetchArray($result)) {
			$ret[] = $row;
		}
		$this->freeRecordSet($result);
		return $ret;
	}

	/**
	 * 
	 * get usertype
	 * 
	 * @param  int $uid:uid
	 * @return array
	 */
	public function getUserType($uid) {
		$ret = Xoonips_Enum::USER_TYPE_USER;
		if ($uid == 0) {
			$ret = Xoonips_Enum::USER_TYPE_GUEST;
		}
		return $ret;
	}

	/**
	 * 
	 * is groupadmin
	 * 
	 * @param  int $uid:uid
	 * @return boolean
	 */
	public function isGroupAdmin($uid) {
		$ret = false;
		$sql = 'SELECT COUNT(a.uid) AS count '
			. "FROM $this->linkTable a "
			. "WHERE a.uid=$uid "
			. 'AND a.is_admin=' . Xoonips_Enum::GRP_ADMINISTRATOR;
		$result = $this->execute($sql);	
		if (!$result) {
			return false;
		}
		$row = $this->fetchArray($result);
		if ($row && $row['count'] != 0) {
			$ret = true;
		}
		$this->freeRecordSet($result);
		return $ret;
	}

	/**
	 * 
	 * get groupsuser
	 * 
	 * @param  int $uid:uid
	 *          $activate
	 * @return array
	 */
	public function getGroupsUsers($uid, $activate) {
		$ret = array();
		$sql = "SELECT groupid FROM $this->linkTable "
		    	. "WHERE uid=$uid AND activate=$activate";
		$result = $this->execute($sql);
		if (!$result) {
			return false;
		}
		while ($row = $this->fetchArray($result)) {
			$ret[] = $row['groupid'];
		}
		$this->freeRecordSet($result);
		return $ret;
	}

	/**
	 * 
	 * delete groupsuser
	 * 
	 * @param  int $uid:uid
	 *          $activate
	 * @return boolean
	 */
	public function deleteGroupsUsers($uid, $activate) {
		$sql = "DELETE FROM $this->linkTable WHERE uid=$uid AND activate=$activate";
		$result = $this->execute($sql);
		if (!$result) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * 
	 * delete groupsuser
	 * 
	 * @param  int $uid:uid
	 *         
	 * @return boolean
	 */
	public function deleteGroupsUsersByUid($uid) {
		$sql = "DELETE FROM $this->linkTable WHERE uid=$uid";
		$result = $this->execute($sql);
		if (!$result) {
			return false;
		}
		
		return true;
	}

	/**
	 * 
	 * get user
	 * 
	 * @param  int $uid:uid
	 * @return boolean
	*/
	public function deleteUsers($uid) {
		$sql = "DELETE FROM $this->table WHERE uid=$uid";
		$result = $this->execute($sql);
		if (!$result) {
			return false;
		}
		return true;
	}
	
	public function getGroupsUsersLinkByUid($uid) {
		$ret = array();
		$sql = "SELECT * FROM $this->linkTable WHERE uid=$uid";
		$result = $this->execute($sql);
		if (!$result) {
			return false;
		}
		while ($row = $this->fetchArray($result)) {
			$ret[] = $row;
		}
		$this->freeRecordSet($result);
		return $ret;
	}
	
	 public function getUsersGroups($groupId, $is_admin = false) {
		$ret = array();
		$tblUser = $this->prefix('users');
		if ($is_admin) {
			$sql = "SELECT b.* FROM $this->linkTable a INNER JOIN $tblUser b ON a.uid=b.uid WHERE a.groupid=$groupId AND a.is_admin=" . Xoonips_Enum::GRP_ADMINISTRATOR;
		} else {
			$sql = "SELECT b.* FROM $this->linkTable a INNER JOIN $tblUser b ON a.uid=b.uid WHERE a.groupid=$groupId AND a.is_admin=" . Xoonips_Enum::GRP_USER;
		}
		$result = $this->execute($sql);
		if (!$result) {
			return false;
		}
		while ($row = $this->fetchArray($result)) {
			$ret[] = $row;
		}
		$this->freeRecordSet($result);
		return $ret;
	}
	
	public function getUsersInfoByGroupId($groupId) {
		$ret = array();
		$tblUser = $this->prefix('users');
		$sql = "SELECT b.* FROM $this->linkTable a INNER JOIN $tblUser b ON a.uid=b.uid WHERE a.groupid=$groupId";
		$result = $this->execute($sql);
		if (!$result) {
			return false;
		}
		while ($row = $this->fetchArray($result)) {
			$ret[] = $row;
		}
		$this->freeRecordSet($result);
		return $ret;
	}

	/**
	 * get used resources
	 *
	 * @param int $uid
	 * @return array('itemNum', 'indexNum', 'fileSize')
	 */
	public function getUsersUsedResources($uid) {
		$itemBean = Xoonips_BeanFactory::getBean('ItemVirtualBean', $this->dirname, $this->trustDirname);
		$indexBean = Xoonips_BeanFactory::getBean('IndexBean', $this->dirname, $this->trustDirname);
		$fileBean = Xoonips_BeanFactory::getBean('ItemFileBean', $this->dirname, $this->trustDirname);
		return array(
			'itemNum' => $itemBean->countUserItems($uid),
			'indexNum' => $indexBean->countUserIndexes($uid),
			'fileSize' => $fileBean->countUserFileSizes($uid)
		);
	}

	/**
	 *
	 * is user Certified
	 *
	 * @param  int $uid:uid
	 * @return bool true:yes,false:no
	**/
	public function isCertified($uid) {
		$user = $this->getUserBasicInfo($uid);
		if ($user != false && $user['level'] >= Xoonips_Enum::USER_CERTIFIED) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 *
	 * certify user
	 * 
	 * @param  $user
	 * @return bool true:yes,false:no
	**/
	public function certifyUser(&$user) {
		$uid = $user['uid'];
		if ($user['level'] == Xoonips_Enum::USER_CERTIFIED) {
			return true;
		}

		$sql = 'UPDATE ' . $this->table	. ' SET level=' . Xoonips_Enum::USER_CERTIFIED . " WHERE uid = $uid";
		$result = $this->execute($sql);
		if (!$result) {
			return false;
		}
		return true;
	}
	
	/**
	 * 
	 * recountPost
	 * 
	 * @param  int $posts
	 * @param  obj $xoopsUser
	 * @return bool true:yes,false:no
	 */
	public function recountPost($posts, $xoopsUser) {
		$uid = $xoopsUser->get('uid');
		$sql = 'UPDATE ' . $this->table
		    	. " SET posts = $posts"
		    	. " WHERE uid = $uid";
		$result = $this->execute($sql);
		if (!$result) {
			return false;
		}
		return true;
	}

	/**
	 * 
	 * addPost
	 * 
	 * @param  int $uid:uid
	 * @return bool true:yes,false:no
	 */
	public function addPost($uid) {
		$sql = 'UPDATE ' . $this->table
	    		. " SET posts = posts + 1"
	    		. " WHERE uid = $uid";
		$result = $this->execute($sql);
		if (!$result) {
			return false;
		}
		return true;
	}

	/**
	 * 
	 * subtractPost
	 * 
	 * @param  int $uid:uid
	 * @return bool true:yes,false:no
	 */
	public function subtractPost($uid) {
		$sql = 'UPDATE ' . $this->table
	    		. " SET posts = posts - 1"
	  	  	. " WHERE uid = $uid";
		$result = $this->execute($sql);
		if (!$result) {
			return false;
		}
		return true;
	}
}

