<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/10/31
 * Time: 15:29
 */

namespace WeChat\Model;


class Scene
{
    public function getAllSceneId()
    {
        $sceneModel  = M("scene");
        $ids = $sceneModel->getField('sceneid',true);
        return $ids;
    }

    public function addSceneId($sceneid,$param)
    {
        $param['sceneid'] = $sceneid;
        $param['addtime'] = time();
        $sceneModel  = M("scene");
        $res = $sceneModel->data($param)->add();
        return $res;
    }

    public function getEventBySceneid($sceneid)
    {
        $where['sceneid'] = $sceneid;
        $sceneModel  = M("scene");
        return $sceneModel->field('method,class,uniqueid,extrdata')->limit(1)->find();
    }

    public function getQrCode($type, $uniqueid)
    {
        $where['type'] = $type;
        $where['uniqueid'] = $uniqueid;
        $sceneModel  = M("scene");
        return $sceneModel->limit(1)->getField('qrcode');
    }

}