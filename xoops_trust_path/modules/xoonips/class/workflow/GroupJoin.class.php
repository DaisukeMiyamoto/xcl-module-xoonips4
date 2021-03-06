<?php

require_once XOONIPS_TRUST_PATH . '/class/core/Workflow.class.php';
require_once XOONIPS_TRUST_PATH . '/class/core/WorkflowClientBase.class.php';
require_once XOONIPS_TRUST_PATH . '/class/core/BeanFactory.class.php';
require_once dirname(dirname(__FILE__)) . '/core/User.class.php';

class Xoonips_WorkflowClientGroupJoin extends Xoonips_WorkflowClientBase {

	var $dataname = Xoonips_Enum::WORKFLOW_GROUP_JOIN;

	/**
	 *
	 *
	**/
	public function doCertify($linkId, $comment) {
		$groupsUsersLinkBean = Xoonips_BeanFactory::getBean('GroupsUsersLinkBean', $this->dirname, $this->trustDirname);
		if (($info = $groupsUsersLinkBean->getGroupUserLinkInfoByLinkId($linkId)) === false || empty($info))
			return false;
		$gid = $info['groupid'];
		$uid = $info['uid'];
		if (!$groupsUsersLinkBean->certify($gid, $uid))
			return false;
		$group_handler =& xoops_gethandler('group');
		$xoopsGroup = $group_handler->get($gid);
		//event log
		$user = Xoonips_User::getInstance();
		$user->doGroupJoined($gid, $uid, $xoopsGroup, false, $comment, $linkId);
	}

	/**
	 *
	 *
	**/
	public function doProgress($linkId) {
		$groupsUsersLinkBean = Xoonips_BeanFactory::getBean('GroupsUsersLinkBean', $this->dirname, $this->trustDirname);
		if (($info = $groupsUsersLinkBean->getGroupUserLinkInfoByLinkId($linkId)) === false || empty($info))
			return false;
		$gid = $info['groupid'];
		$uid = $info['uid'];
		//send to group admins and certifyUsers
		$sendToUsers = array();
		$sendToUsers[] = $uid;
		foreach ($groupsUsersLinkBean->getAdminUserIds($gid) as $id)
			$sendToUsers[] = $id; 
		$sendToUsers = array_merge($sendToUsers, Xoonips_Workflow::getCurrentApproverUserIds($this->dirname, $this->dataname, $linkId));
		$sendToUsers = array_unique($sendToUsers);
		$this->notification->groupJoinRequest($gid, $uid, $sendToUsers);
	}

	/**
	 *
	 *
	**/
	public function doRefuse($linkId, $comment) {
		$groupsUsersLinkBean = Xoonips_BeanFactory::getBean('GroupsUsersLinkBean', $this->dirname, $this->trustDirname);
		if (($info = $groupsUsersLinkBean->getGroupUserLinkInfoByLinkId($linkId)) === false || empty($info))
			return false;
		$gid = $info['groupid'];
		$uid = $info['uid'];
		if (!$groupsUsersLinkBean->delete($gid, $uid))
			return false;
		$group_handler =& xoops_gethandler('group');
		$xoopsGroup = $group_handler->get($gid);
		$xoopsUser = new XoopsUser($uid);
		//event log
		XCube_DelegateUtils::call('Module.Xoonips.Event.Group.Member.JoinReject', $xoopsUser, $xoopsGroup);
		//send to group admins and certifyUsers
		$sendToUsers = array();
		$sendToUsers[] = $uid;
		foreach ($groupsUsersLinkBean->getAdminUserIds($gid) as $id) {
			$sendToUsers[] = $id; 
		}
		$sendToUsers = array_merge($sendToUsers, Xoonips_Workflow::getAllApproverUserIds($this->dirname, $this->dataname, $linkId));
		$sendToUsers = array_unique($sendToUsers);
		$this->notification->groupJoinRejected($gid, $uid, $sendToUsers, $comment);
	}

}

