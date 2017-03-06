<?php

use Xoonips\Core\CacheUtils;
use Xoonips\Core\ImageUtils;

require_once dirname(dirname(__FILE__)) . '/class/core/BeanFactory.class.php';

/**
 * preview action
 */
class Xoonips_PreviewAction extends Xoonips_AbstractAction
{

    const THUMBNAIL_MAX_WIDTH = 300;
    const THUMBNAIL_MAX_HEIGHT = 300;

    /**
     * image file path.
     * @var string
     */
    protected $mImageFilePath = '';

    /**
     * image file name.
     * @var string
     */
    protected $mImageFileName = '';

    /**
     * flag for thumbnail
     * @var bool
     */
    protected $mIsThumbnail = false;

    /**
     * item object.
     *
     * @var object
     */
    protected $mItemObj = 0;

    /**
     * item file object.
     *
     * @var object
     */
    protected $mItemFileObj = 0;

    /**
     * download file path.
     *
     * @var string
     */
    protected $mFilePath = '';

    /**
     * download file mime type.
     *
     * @var string
     */
    protected $mFileMimeType = '';

    /**
     * download file name.
     *
     * @var string
     */
    protected $mFileName = '';

    /**
     * error code.
     *
     * @var int
     */
    protected $mErrorCode = 0;


    /**
     * get default view
     *
     * @return Enum
     */
    public function getDefaultView()
    {
        $dirname = $this->mAsset->mDirname;
        $trustDirname = $this->mAsset->mTrustDirname;

        $params = $this->_fetchRequest();
        if ($params === false) {
            $this->mErrorCode = 404;

            return $this->_getFrameViewStatus('ERROR');
        }
        if ($this->_getObjectsByParams($params) === false) {
            $this->mErrorCode = 404;

            return $this->_getFrameViewStatus('ERROR');
        }

        // delegate
        $itemtypeBean = Xoonips_BeanFactory::getBean('ItemTypeBean', $dirname, $trustDirname);
        $itemtypeName = $itemtypeBean->getItemTypeName($this->mItemObj->get('item_type_id'));
        XCube_DelegateUtils::call('Module.Xoonips.FileDownload.Prepare', $this->mItemObj->get('item_id'), $itemtypeName, $this->mItemFileObj->gets(), new XCube_Ref($this->mFilePath));

        return $this->_getFrameViewStatus('INDEX');

    }


    /**
     * fetch request from url patterns:
     * - XOOPS_URL/modules/xoonips/preview.php?file_id=${FILE_ID}
     * - XOOPS_URL/modules/xoonips/preview.php?${IDNAME}=${ID}
     * - XOOPS_URL/modules/xoonips/preview.php/${FILE_NAME}?file_id=${FILE_ID}
     * - XOOPS_URL/modules/xoonips/preview.php/${ITEM_ID}/${FILE_NAME}
     * - XOOPS_URL/modules/xoonips/preview.php/${ITEM_ID}/${FILE_ID}/${FILE_NAME}
     * - XOOPS_URL/modules/xoonips/preview.php/${IDNAME}:${ID}/${FILE_NAME}
     * - XOOPS_URL/modules/xoonips/preview.php/${IDNAME}:${ID}/${FILE_ID}/${FILE_NAME}.
     *
     * @return array/bool false if failure
     */
    protected function _fetchRequest()
    {
        $ret = array(
            'item_id' => 0,
            'item_doi' => false,
            'file_id' => 0,
            'file_name' => false,
        );
        if (array_key_exists('PATH_INFO', $_SERVER)) {
            $pathinfo = explode('/', $_SERVER['PATH_INFO']);
            if (!is_array($pathinfo) || count($pathinfo) < 2) {
                return false;
            }
            array_shift($pathinfo);
            $ret['file_name'] = array_pop($pathinfo);
            if (!empty($pathinfo)) {
                $item_str = array_shift($pathinfo);
                $item_arr = explode(':', $item_str);
                if (!is_array($item_arr)) {
                    return false;
                }
                switch (count($item_arr)) {
                    case 1:
                        $ret['item_id'] = intval($item_arr[0]);
                        break;
                    case 2:
                        if ($item_arr[0] != XOONIPS_CONFIG_DOI_FIELD_PARAM_NAME) {
                            return false;
                        }
                        $ret['item_doi'] = $item_arr[1];
                        break;
                    default:
                        return false;
                }
                if (!empty($pathinfo)) {
                    $ret['file_id'] = intval(array_shift($pathinfo));
                }
            }
        }
        $request = $this->mRoot->mContext->mRequest;
        $file_id = $request->getRequest('file_id');
        if (!empty($file_id)) {
            $ret['file_id'] = $file_id;
        }
        $item_doi = $request->getRequest(XOONIPS_CONFIG_DOI_FIELD_PARAM_NAME);
        if (!empty($item_doi)) {
            $ret['item_doi'] = $item_doi;
        }

        return $ret;
    }

    /**
     * get objects by parameters.
     *
     * @param array $params
     *
     * @return bool false if failure
     */
    protected function _getObjectsByParams($params)
    {
        $itemObj = null;
        $itemFileObj = null;
        $dirname = $this->mAsset->mDirname;
        $itemHandler = Xoonips_Utils::getModuleHandler('item', $dirname);
        $itemFileHandler = Xoonips_Utils::getModuleHandler('itemFile', $dirname);
        if (!empty($params['item_id'])) {
            $itemObj = &$itemHandler->get($params['item_id']);
            if (!is_object($itemObj)) {
                return false;
            }
        } elseif (!empty($params['item_doi'])) {
            $itemObj = &$itemHandler->getByDoi($params['item_doi']);
            if (!is_object($itemObj)) {
                return false;
            }
        }
        if (!empty($params['file_id'])) {
            $itemFileObj = &$itemFileHandler->get($params['file_id']);
            if (!is_object($itemFileObj)) {
                return false;
            }
        } else {
            if (!is_object($itemObj)) {
                return false;
            }
            $objs = &$itemFileHandler->getObjectsForDownload($itemObj->get('item_id'), $params['file_name']);
            if (count($objs) != 1) {
                return false;
            }
            $itemFileObj = &$objs[0];
        }
        if (!is_object($itemObj)) {
            $itemObj = &$itemHandler->get($itemFileObj->get('item_id'));
            if (!is_object($itemObj)) {
                return false;
            }
        }
        if ($itemObj->get('item_id') != $itemFileObj->get('item_id')) {
            return false;
        }
        $this->mItemObj = $itemObj;
        $this->mItemFileObj = $itemFileObj;
        $this->mFilePath = $itemFileObj->getFilePath();
        $this->mFileMimeType = $itemFileObj->get('mime_type');
        $this->mFileName = empty($params['file_name']) ? $itemFileObj->get('original_file_name') : $params['file_name'];
        if (!file_exists($this->mFilePath)) {
            return false;
        }

        return true;
    }


    /**
     * execute
     *
     * @return Enum
     */
    public function execute()
    {
        return $this->getDefaultView();
    }

    /**
     * execute view index.
     *
     * @param XCube_RenderTarget &$render
     */
    public function executeViewIndex(&$render)
    {
        $dirname = $this->mAsset->mDirname;
        $trustDirname = $this->mAsset->mTrustDirname;
        $itemHandler = Xoonips_Utils::getModuleHandler('item', $dirname);
        $itemFileHandler = Xoonips_Utils::getModuleHandler('itemFile', $dirname);
        // record download file event log
        $eventLogBean = Xoonips_BeanFactory::getBean('EventLogBean', $dirname, $trustDirname);
        $eventLogBean->recordDownloadFileEvent($this->mItemObj->get('item_id'), $this->mItemFileObj->get('file_id'));
        // increment dowonload count
        $download_count = $this->mItemFileObj->get('download_count') + 1;
        $this->mItemFileObj->set('download_count', $download_count);
        $itemFileHandler->insert($this->mItemFileObj, true);
        // download
        $mtime = filemtime($this->mFilePath);
        $etag = md5($this->mFilePath.filesize($this->mFilePath).$mtime);
        CacheUtils::downloadFile($mtime, $etag, $this->mFileMimeType, $this->mFilePath, $this->mFileName, _CHARSET);
    }

    /**
     * execute view error.
     *
     * @param XCube_RenderTarget &$render
     */
    public function executeViewError(&$render)
    {
        $constpref = '_MD_'.strtoupper($this->mAsset->mDirname);
        switch ($this->mErrorCode) {
            case 403:
                $this->mRoot->mController->executeRedirect(XOOPS_URL.'/', 3, constant($constpref.'_ITEM_CANNOT_ACCESS_ITEM'));
                break;
            case 404:
                CacheUtils::errorExit(404);
                break;
            case 500:
                CacheUtils::errorExit(500);
                break;
        }
        exit();
    }
}

