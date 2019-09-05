<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/11/1
 * Time: 14:00
 */

namespace Server;


use WeChat\Model\JizanModel;

class Jizan
{
    public function jizan($guanjiaid = '', $openid = '')
    {
        if (!$guanjiaid) return false;
        if (!$openid) return false;
        $jizanModel = new JizanModel();
        if ($jizanModel->isJizan($openid,$guanjiaid)) response("对不起，不能重复支持哦");
        $res = $jizanModel->addJizan($guanjiaid,$openid);
        return $res;
    }
}