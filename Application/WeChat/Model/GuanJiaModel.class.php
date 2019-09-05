<?php

namespace WeChat\Model;

use Think\Model;
header("content-type:text/html;charset=utf-8");
class GuanJiaModel
{
    /* protected $trueTableName = 'guanjia';

     protected $autoCheckFields = true;*/
//保存管家信息
    public function saveGuanJia($guanJiaData = '',$guanjiaid = 0)
    {
        if (!$guanJiaData) return false;
        $guanJiaModel = M("guanjia");
        if (!$guanjiaid) {
            $res = $guanJiaModel->data($guanJiaData)->add();
            return $res;
        } else {
            $where['id'] = $guanjiaid;
            $guanJiaModel->where($where)->save($guanJiaData);
            return true;
        }

    }

    public function hasGanjia($name = '', $guanjiaid = 0)
    {
        $guanJiaModel = M("guanjia");
        $where['guanjianame'] = $name;
        if ($guanjiaid) {
            $where['id'] = ['neq',$guanjiaid];
        }
        $res = $guanJiaModel->where($where)->limit(1)->find();
        if ($res) {
            return true;
        } else {
            return false;
        }
    }

    public function hasGanjiaphone($phone = '', $guanjiaid = 0)
    {
        $guanJiaModel = M("guanjia");
        $where['guanjiaphone'] = $phone;
        if ($guanjiaid) {
            $where['id'] = ['neq',$guanjiaid];
        }
        $res = $guanJiaModel->where($where)->limit(1)->find();
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
        $guanJia111 = M("guanjia");
        $total = $guanJia111->join('user on guanjia.userid=user.id')->field("guanjia.id as guanjiaid,guanjia.guanjianame,guanjia.guanjiaphone,guanjia.guanjiafenlei,guanjia.userid,user.name")
            ->where($where)->order('guanjia.id desc')->count();
        return $total;
    }

    public function guanJiaList($where = '', $p)
    {
        $count = $this->getTotal($where);
        $Page = new \Think\Page($count, 20);
        $Page->nowPageage = $p;
        $guanJia111 = M("guanjia");
        $res = $guanJia111->join('user on guanjia.userid=user.id','left')->field("guanjia.id as guanjiaid,guanjia.guanjianame,guanjia.guanjiaphone,guanjia.guanjiafenlei,guanjia.userid,user.name")
            ->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->order('guanjia.id desc')->select();
        $data['Page'] = $Page;
        $data['res'] = $res;
        $data['count'] = $count;

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


    public function getOneGuanJiaNameAndId1($id)
    {
        $guanJia111 = M("guanjia");
        if(is_numeric($id)){
            $where['g.id'] = $id;
        }else{
            $where['g.guanjianame'] = ['like','%'.$id.'%'];
        }
        $res = $guanJia111
            ->alias('g')
            ->join('__USER__ u on g.userid=u.id')
            ->field("g.id as guanjiaid,g.guanjianame")
            ->where($where)->limit(1)->find();
        return $res;
    }

    public function getOneGuanJia($id)
    {
        $guanJia111 = M("guanjia");
        $where['g.id'] = $id;
        $res = $guanJia111
            ->alias('g')
            ->join('__USER__ u on g.userid=u.id')
            ->field("g.id as guanjiaid,g.guanjianame,g.avatarurl,g.guanjiatag,g.guanjiadetails,g.guanjiaphone,g.guanjiafenlei,g.userid,u.name,g.guanjialevelid,g.extra,g.info,g.supplier,g.suppliershort,g.customertype,g.customerphone,g.moreinfo,g.title")
            ->where($where)->limit(1)->find();
        $res['avatarurl']=$res['avatarurl'];
        return $res;
    }

    public function getBankInfo($guanjiaid)
    {
        $bankModel = M('balanceinfo');
        $where['guanjiaid'] = $guanjiaid;
        return $bankModel->where($where)->find();
    }

    public function getGuanJiaBaseInfo($guanjiaid)
    {
        $where['id'] = $guanjiaid;
        $info = M('guanjia')->field('id as guanjiaid,guanjianame,avatarurl,guanjiaphone,title')->where($where)->find();
        return $info;
    }

    /**
     * @breif 获取管家基本信息（管家id,管家姓名，管家头像,管家二级分类）
     * @param $gunajiaid
     * @return mixed
     */
    public function getGuanjiaInfo($gunajiaid, $productid = 0)
    {
        $where['id'] = $gunajiaid;
        $info = M('guanjia')->field('id as guanjiaid, guanjianame,title as name,avatarurl,guanjiaphone')->where($where)->limit(1)->find();
        $info1 = M('product as p')->join('__SUPPLIER__ as s ON p.supplierid = s.id')->field('s.suppliershort,s.customertype,s.customerphone')->where(['p.id'=>$productid])->limit(1)->find();
        if (!$info1) {
            $info1['customertype'] = 1;
            $info1['customerphone'] = null;
        }
         $info = array_merge($info,$info1);
        return $info;
    }


    /**
     * @param $guanjiaid
     * @return mixed
     */
    public function getGuanjiaDetail($guanjiaid)
    {
        $where['isdelete'] = 0;
        $where['id'] = $guanjiaid;
        $res = M('guanjia')->field('id as guanjiaid,guanjiatag,avatarurl,title as type,guanjianame,info,guanjiadetails,moreinfo')->where($where)->limit(1)->find();
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
        $ignoreList = [10, 11, 13, 19, 22];
        $list = '[
                    {"name":"生活",
                        "list":[
                            {"id":"7","guanjianame":"杨涛","avatarurl":"https://file.rose52.com/2018/02/09/upload_oc6k8rxlrhcrhu0r.png","guanjiafenlei":"生活-清洁","type":"清洁","cid":"23","pid":"11"},
                            {"id":"9","guanjianame":"张媛","avatarurl":"https://file.rose52.com/2018/03/02/upload_iasmsr0fvxm2o1kj.png","guanjiafenlei":"生活-礼仪","type":"礼仪","cid":"39","pid":"11"},
                            {"id":"25","guanjianame":"父母邦","avatarurl":"https://file.rose52.com/2018/09/11/upload_x9n07tmle4qr1jzf.jpg","guanjiafenlei":"生活-亲子游","type":"亲子游","cid":"53","pid":"11"},
                            {"id":"16","guanjianame":"纳兰正秀","avatarurl":"https://file.rose52.com/2018/05/22/upload_mcxzvetmyxq8a3ww.jpg","guanjiafenlei":"生活-奢品","type":"奢品","cid":"57","pid":"11"},
                            {"id":"20","guanjianame":"潘晓娜","avatarurl":"https://file.rose52.com/2018/06/26/upload_7uzesxdaot37lqzl.jpg","guanjiafenlei":"生活-卫浴","type":"卫浴","cid":"61","pid":"11"},
                            {"id":"24","guanjianame":"花+","avatarurl":"https://file.rose52.com/2018/09/06/upload_x0ovu7z74471iojo.jpg","guanjiafenlei":"生活-鲜花","type":"鲜花","cid":"61","pid":"11"},
                            {"id":"23","guanjianame":"刘洋","avatarurl":"https://file.rose52.com/2018/09/04/upload_gbb21et3fvspoiqm.jpg","guanjiafenlei":"生活-清洁","type":"清洁","cid":"61","pid":"11"}
                        ]},
                    {"name":"健康",
                        "list":[
                            {"id":"5","guanjianame":"高杰","avatarurl":"https://file.rose52.com/2018/02/09/upload_1vci1kt6awyrv88d.jpeg","guanjiafenlei":"健康-齿科","type":"齿科","cid":"25","pid":"15"},
                            {"id":"8","guanjianame":"韩志毅","avatarurl":"https://file.rose52.com/2018/02/09/upload_26ho0dahzf1ciuom.png","guanjiafenlei":"健康-体检","type":"体检","cid":"35","pid":"15"},
                            {"id":"14","guanjianame":"刘安妮","avatarurl":"https://file.rose52.com/2018/05/14/upload_yl74h0st44l79pkw.jpg","guanjiafenlei":"健康-美孕","type":"美孕","cid":"49","pid":"15"},
                            {"id":"17","guanjianame":"张杰","avatarurl":"https://file.rose52.com/2018/05/24/upload_78hi815cjvm891a6.jpg","guanjiafenlei":"健康-陪诊","type":"陪诊","cid":"63","pid":"15"},
                            {"id":"18","guanjianame":"宜生到家","avatarurl":"https://file.rose52.com/2018/05/25/upload_jpsgx4kyl008ha60.jpg","guanjiafenlei":"健康-推拿","type":"推拿","cid":"59","pid":"15"}
                        ]},
                    {"name":"海外",
                        "list":[
                            {"id":"1","guanjianame":"Andy Zhong","avatarurl":"https://file.rose52.com/2018/02/09/upload_zsqv9w5a1s0d3ir4.jpg","guanjiafenlei":"海外-投资","type":"投资","cid":"31","pid":"14"},
                            {"id":"2","guanjianame":"马宁","avatarurl":"https://file.rose52.com/2018/02/09/upload_t0fml4i12ew6zvwk.png","guanjiafenlei":"海外-移民","type":"移民","cid":"33","pid":"14"},
                            {"id":"4","guanjianame":"王蓓蓓","avatarurl":"https://file.rose52.com/2018/02/09/upload_hlmepwguebvstpre.png","guanjiafenlei":"海外-留学","type":"留学","cid":"29","pid":"14"}
                        ]},
                    {"name":"其它","list":[
                        {"id":"3","guanjianame":"刘常科","avatarurl":"https://file.rose52.com/2018/02/09/upload_zu1u8lpwvhogzxe4.jpg","guanjiafenlei":"教育-家教","type":"家教","cid":"37","pid":"16"},
                        {"id":"6","guanjianame":"Linda","avatarurl":"https://file.rose52.com/2018/02/09/upload_3pwvcpe1wttq0gxg.png","guanjiafenlei":"出行-签证","type":"签证","cid":"27","pid":"13"},
                        {"id":"12","guanjianame":"梁维维","avatarurl":"https://file.rose52.com/2018/04/16/upload_zi001yu6ekunplfr.jpg","guanjiafenlei":"法律-法律","type":"法律","cid":"47","pid":"45"}
                        ]}
                ]';

        return json_decode($list, true);

    }
}
