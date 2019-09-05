<?php

namespace WeChat\Model;

use Think\Model;
header("content-type:text/html;charset=utf-8");
class SupplierModel
{
    /* protected $trueTableName = 'supplier';

     protected $autoCheckFields = true;*/
//保存管家信息
    public function saveSupplier($supplierData = '',$supplierid = 0)
    {
        if (!$supplierData) return false;
        
            $supplierModel = M("supplier");
     
        if (!$supplierid) {
            
            $res = $supplierModel->data($supplierData)->add();
            return $res;
        } else {
            $where['id'] = $supplierid;
            $supplierModel->where($where)->save($supplierData);
            return true;
        }

    }

    public function hasSupplier($name = '', $supplierid = 0)
    {
        $supplierModel = M("supplier");
        $where['supplier'] = $name;
        if ($supplierid) {
            $where['id'] = ['neq',$supplierid];
        }
        $res = $supplierModel->where($where)->limit(1)->find();
        if ($res) {
            return true;
        } else {
            return false;
        }
    }

    public function hasSupplierphone($phone = '', $id = 0)
    {
        $Model = M("supplier");
        $where['contactphone'] = $phone;
        if ($id) {
            $where['id'] = ['neq',$id];
        }
        $res = $Model->where($where)->limit(1)->find();
        if ($res) {
            return true;
        } else {
            return false;
        }
    }


    public function saveBank($bankData = '', $bankid = 0)
    {
        if (!$bankData) return false;
        $balanceInfoModel = M("balanceinfo");
        if (!$bankid) {
            $res = $balanceInfoModel->data($bankData)->add();
            return $res;
        } else {
            $where['id'] = $bankid;
            $balanceInfoModel->where($where)->save($bankData);
            return true;
        }

    }

    //管家列表
    /*    public function  guanJiaList(){
            $guanJiaModel = M("guanjia");
            return  $guanJiaModel;
        }*/
    public function getTotal($where)
    {
        $supplier = M("supplier");
        $total = $supplier->join('user on supplier.userid=user.id','left')
            ->field("supplier.id as supplierid,supplier.supplier,supplier.contactphone,supplier.userid,user.name")
            ->where($where)->count();
        return $total;
    }

    public function supplierList($where = '', $p)
    {
        $count = $this->getTotal($where);
        $Page = new \Think\Page($count, 20);
        $Page->nowPageage = $p;
        $supplier = M("supplier");
        $res = $supplier->join('user on supplier.userid=user.id','left')
            ->field("supplier.id as supplierid,supplier.supplier,supplier.contactphone,supplier.commissionrate,supplier.userid,supplier.issign,user.name")
            ->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->order('supplier.id desc')->select();
        $data['Page'] = $Page;
        $data['res']  = $res;
        $data['count']= $count;

        return $data;

    }

    public function getOneGuanJiaNameAndId($id)
    {
        $guanJia111 = M("guanjia");
        $where['g.id'] = $id;
        $res = $guanJia111
            ->alias('g')
            ->join('__USER__ u on g.userid=u.id')
            ->field("g.id as guanjiaid,g.guanjianame")
            ->where($where)->limit(1)->find();
        return $res;
    }

    public function getOneSupplier($id)
    {
        $Model = M("supplier");
        $where['id'] = $id;
        $res = $Model->where($where)->limit(1)->find();
        $res['code'] = '';
        $res['stype'] = $res['stype'] ? explode(',', $res['stype']): '';
        $res['stime'] = $res['stime'] ? explode(',',$res['stime']) : '';
        foreach ($res['stime'] as $key => $row) {
            $res['stime'][$key] = explode('-', $row);
        }
        $res['contractstarttime_d'] = date('Y-m-d'  ,$res['contractstarttime'] ) ; 
        $res['contractendtime_d']   = date('Y-m-d'  ,$res['contractendtime']   ) ; 

        return $res;
    }

    public function getBankInfo($guanjiaid)
    {
        $bankModel = M('balanceinfo');
        $where['guanjiaid'] = $guanjiaid;
        return $bankModel->where($where)->find();
    }

    /**
     * @breif 获取管家基本信息（管家id,管家姓名，管家头像,管家二级分类）
     * @param $gunajiaid
     * @return mixed
     */
    public function getSupplierInfo($gunajiaid)
    {
        $where['id'] = $gunajiaid;
        $Model = M('supplier');
        $info = $Model->where($where)
            ->limit(1)
            ->find();
        return $info;
    }

    /**
     * @breif 微信首页获取推荐管家管家
     */
    public function getGuanJiaRecommend()
    {
        $guanjiaModel = M('guanjia as g');
        $where['isdelete'] = 0;
        $res = $guanjiaModel->field('g.id as guanjiaid,g.icon,c.name as type,g.guanjianame')->join('category as c ON g.guanjialevelid=c.id')->where($where)->order('isrecommend desc,weight desc')->limit(8)->select();
        return $res;
    }

    /**
     * @param $guanjiaid
     * @return mixed
     */
    public function getGuanjiaDetail($guanjiaid)
    {
        $guanjiaModel = M('guanjia as g');
        $where['g.isdelete'] = 0;
        $where['g.id'] = $guanjiaid;
        $res = $guanjiaModel->field('g.id as guanjiaid,g.guanjiatag,g.avatarurl,c.name as type,g.guanjianame,g.info,g.guanjiadetails,g.moreinfo')->join('__CATEGORY__ as c ON g.guanjialevelid=c.id')->where($where)->limit(1)->find();
        return $res;
    }

    public function getAllGuanjia()
    {
        $model = M('guanjia');
        return $model->field('id,guanjianame')->select();
    }

    /**
     * @breif 微信全部管家页面
     * 生活 健康 海外 其它
     * id 10 11不显示
     */
    public function getGuanjiaList() {
        $ignoreList = [10, 11];
        $model = M('guanjia as g');
        $field = ['g.id','g.guanjianame','g.avatarurl','g.guanjiafenlei','c.name as type','c.id as cid','c.pid'];
        $join = 'category as c ON g.guanjialevelid=c.id';
        $where = [
            'g.id' => ['not in', $ignoreList],
            'c.type' => 1,
            'c.status' => ['neq', 2]
        ];
        $managers = $model->field($field)->join($join)->where($where)->select();
        $life = []; // id = 11
        $healthy = []; // id = 15
        $abrod = []; // id = 14
        $others = [];

        foreach($managers as $k => $v) {
            if (!strpos($v['avatarurl'], 'http')) {
                $v['avatarurl'] = 'https://file.rose52.com'.$v['avatarurl'];
            }
            switch ($v['pid']){
                case 11:
                    $life[] = $v;
                    break;
                case 14:
                    $abrod[] = $v;
                    break;
                case 15:
                    $healthy[] = $v;
                    break;
                default:
                    $others[] = $v;
            }
        }
        $result = [
            [
                'name' => '生活',
                'list' => $life
            ],
            [
                'name' => '健康',
                'list' => $healthy
            ],
            [
                'name' => '海外',
                'list' => $abrod
            ],
            [
                'name' => '其它',
                'list' => $others
            ],
        ];
        return $result;
    }
}
