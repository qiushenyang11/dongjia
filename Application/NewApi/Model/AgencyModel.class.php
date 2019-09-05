<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/7/26
 * Time: 10:45
 */

namespace NewApi\Model;


class AgencyModel
{
    public function addProduct($data)
    {
        $data['addtime'] = time();
        $res = M('agencyinfo')->data($data)->add();
        return $res;
    }

    public function getProduct($id)
    {
        $where['id'] = $id;
        $res = M('agencyinfo')->where($where)->limit(1)->find();
        return $res;
    }
}