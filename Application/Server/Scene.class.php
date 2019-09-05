<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/10/31
 * Time: 15:27
 */

namespace Server;


use Org\Util\EasyWeChat;

class Scene
{
    public function createQrcode($method = '',$type = 0,$uniqueid= 0,  $desc = '',$extrdata = [], $class = '')
    {

        $sceneid = $this->getSceneId();
        $easyWeChat = new EasyWeChat();
        $param['method'] = $method;
        $param['type'] = $type;
        $param['uniqueid'] = $uniqueid;
        if (count($extrdata)) {
            $param['extrdata'] = json_encode($extrdata);
        }
        $sceneModel = new \WeChat\Model\Scene();
        $url  = $sceneModel ->getQrCode($type, $uniqueid);
        if ($url) return $url;
        $param['desc'] = $desc;
        $param['class'] = $class;
        $model = M();
        $model->startTrans();
        $res = $easyWeChat->getQRCode($sceneid, 1);
        $url = $easyWeChat->getQRUrl($res['ticket']);
        $param['qrcode'] = $url;
        $res1 = $this->bindSceneIdToEvent($sceneid,$param);
        if ($res['ticket'] && $res1) {
            $model->commit();
            return $url;
        } else {
            $model->rollback();
            return false;
        }

    }

    public function getEventBySceneid($sceneid)
    {
        if (!$sceneid) return false;
        $sceneModel = new \WeChat\Model\Scene();
        return $sceneModel->getEventBySceneid($sceneid);
    }

    private function bindSceneIdToEvent($sceneid, $param)
    {
        if (!$sceneid) return false;
        if (!isset($param['method']) || !$param['method']) return false;
        $sceneModel = new \WeChat\Model\Scene();
        return $sceneModel ->addSceneId($sceneid, $param);
    }

    private function getSceneId()
    {
        $sceneModel = new \WeChat\Model\Scene();
        $hasIds = $sceneModel ->getAllSceneId();
        if (!$hasIds) $hasIds = [];
        for ($i = 1; $i<=100000; $i++) {
            $arr[$i-1] = $i;
        }
        $ids = array_diff($arr,$hasIds);
        $minId = min($ids);
        return $minId;
    }
}