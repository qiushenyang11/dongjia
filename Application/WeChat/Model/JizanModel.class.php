<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/11/1
 * Time: 14:01
 */

namespace WeChat\Model;


class JizanModel
{
    public function isJizan($openid,$guanjiaid)
    {
        $jizanModel = M("jizan");
        $where['voteopenid'] = $openid;
        $where['guanjiaid'] = $guanjiaid;
        $res = $jizanModel->where($where)->limit(1)->find();
        return $res;
    }

    public function addJizan($guanjiaid,$openid)
    {
        $jizanModel = M("jizan");
        $data['voteopenid'] = $openid;
        $data['guanjiaid'] = $guanjiaid;
        $data['addtime'] = time();
        $res = $jizanModel->data($data)->add();
        return $res;
    }
}