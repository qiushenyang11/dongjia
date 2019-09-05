<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/12/27
 * Time: 16:48
 */

namespace Server;


use WeChat\Model\WeChatUserModel;

class WeChat
{
    public function handelFirstSubscribe($openid)
    {
        $userModel = new WeChatUserModel();
        $hasUser = $userModel->getUserInfoByOpenid($openid);
        if (!$hasUser) {
            $param['openid'] = $openid;
            $param['addtime'] = time();
            $userModel->addUser($param);
        }
    }
}