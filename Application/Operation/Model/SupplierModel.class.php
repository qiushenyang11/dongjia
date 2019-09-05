<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/7/5
 * Time: 13:30
 */

namespace Operation\Model;


class SupplierModel
{
    public function getAllSupplier()
    {
        $res = M('supplier')->field('id,suppliershort as supplier')->select();
        return $res;
    }

    /**
     * @breif 管家助手账号是否存在
     * @param $phone
     * @param int $filterid
     */
    public function hasSupplierAccount($phone, $filterid = 0)
    {
        $where['phone'] = $phone;
        if ($filterid) $where['id'] = ['neq', $filterid];
        return M('supplier')->where($where)->limit(1)->find();
    }

    public function getOneSupplier($supplierid)
    {
        $where['id'] = $supplierid;
        return M('supplier')->where($where)->limit(1)->find();
    }
}