<?php

namespace BaoXian\Model;

use Think\Model;

class BaoxianyuyueModel extends Model
{
    public function __construct()
    {
        $this->trueTableName = 'baoxianyuyue';
        parent::__construct();
    }

    /**
     * 检查是否预约过
     */
    public function checkIsReserved($jdAccount, $product)
    {
        $where = array();
        $where["jdaccount"] = array('eq', $jdAccount);
        $where['product'] = $product;
        $where['fromby'] = ['neq', 'CANCEL'];
        $rst=$this->where($where)->limit(1)->find();

        if($rst)
        {
            return true;
        }
        return false;
    }

    /**
     * 检查是否是软删除的数据
     */
    public function checkIsMarkedCancel($jdAccount, $product)
    {
        $where = array();
        $where["jdaccount"] = array('eq', $jdAccount);
        $where['product'] = $product;
        $where['fromby'] = 'CANCEL';
        $rst=$this->where($where)->limit(1)->find();

        if($rst)
        {
            return true;
        }
        return false;
    }
}
