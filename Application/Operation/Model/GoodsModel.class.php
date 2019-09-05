<?php
/**
 * Created by PhpStorm.
 * User: xiyou
 * Date: 2017/10/11
 * Time: 16:18
 */

namespace Operation\Model;
use Think\Model;

class GoodsModel
{

    public  function  getCount($productid){
        $goodsModel = M("goods");
        $where['productid']=$productid;
        $goodsCount=$goodsModel->where($where)->count();
        return $goodsCount;
    }

    public function getExpressCount($productid)
    {
        $specModel = M('spec');
        $where['productid'] = $productid;
        return $specModel->where($where)->count();
    }

    public function getProductMessagetype($productid)
    {
        $productModel=M("product");
        $where['id'] = $productid;
        $res = $productModel->where($where)->find();
        return $res['messagetype'];
    }

    public  function  productname($productid){
        $productModel=M("product");
        $where['id']=$productid;
        $productname=$productModel->where($where)->field('name')->find();
        $name=$productname['name'];
        return $name;
    }

    public function goodsExpressInfo($p, $productid)
    {
        $count=$this->getExpressCount($productid);
        $Page = new \Think\Page($count,20);
        $Page->nowPageage = $p;
        $productname=$this->productname($productid);
        $goodsModel = M("spec");
        $where['productid']=$productid;
        $res=$goodsModel->field("id,specname as name,status,nums")->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $data['productname'] = $productname;
        $data['Page'] = $Page;
        $data['res']=$res;
        $data['count']=$count;
        return $data;
    }

    /*商品列表*/
   public function  goodsinfo($p,$productid){
       $count=$this->getCount($productid);
       $productname=$this->productname($productid);
       $Page = new \Think\Page($count,5);
       $Page->nowPageage = $p;
       $goodsModel = M("goods");
       $where['productid']=$productid;
       $res=$goodsModel->field("id,name,type")->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
       $specModel = M("spec");

       foreach ($res as $key=>$row){
           $map['goodsid']=$row['id'];
           $map['isdelete']=0;
           $specs = $specModel->where($map)->field('specname,nums,status')->select();
            $specstr = '';
            foreach ($specs as $vo) {
                $specstr.=$vo['specname'].'('.($vo['status'] == 1 ?'上线':'下线').',剩余库存'.$vo['nums'].')<br>';
            }
            $res[$key]['specs'] = $specstr;
            $res[$key]['name'] = $row['name'];
       }
       $data['productname']=$productname;
       $data['Page'] = $Page;
       $data['res']=$res;
       $data['count']=$count;
       return $data;


   }

   public function hasGoodsSpec($goodsname, $productid = 0)
   {
       $specModel = M("spec");
       $where['specname'] = $goodsname;
       $where['productid'] = ['gt', 0];
       if ($productid) $where['productid'] = $productid;
       return $specModel->where($where)->limit(1)->find();
   }

    public function getOneProductIdAndName($id)
    {
        $where['id'] = $id;
        $productMdoel = M('product');
        $info = $productMdoel->field('id,name')->where($where)->limit(1)->find();
        return $info;
    }


    public function hasGoods($goodsname)
    {
        $goodsModel = M("goods");
        $where['name'] = $goodsname;
        return $goodsModel->where($where)->limit(1)->find();
    }

    public function addGoodsSpec($parm)
    {
        $goodsModel = M("spec");
        return $goodsModel->data($parm)->add();
    }

    public function addGoods($param)
    {
        $goodsModel = M("goods");
        return $goodsModel->data($param)->add();
    }

    public function saveGoods($goodid, $param)
    {
        $goodsModel = M("goods");
        $where['id'] = $goodid;
        return $goodsModel->where($where)->save($param);
    }

    public function saveExpressGoods($id, $param)
    {
        $goodsModel = M("spec");
        $where['id'] = $id;
        return $goodsModel->where($where)->save($param);
    }

    public function getGoodsName($goodid)
    {
        $goodsModel = M("goods");
        $where['id'] = $goodid;
        return $goodsModel->where($where)->getField("name");
    }

    public function getGoodsSpecName($id)
    {
        $goodsModel = M("spec");
        $where['id'] = $id;
        return $goodsModel->field('specname,productid')->where($where)->limit(1)->find();
    }

    public function addSpec($data)
    {
        $specModel = M('spec');

        $num = $specModel->addAll($data);
        return $num;
    }

    public function saveSpec($specid, $data)
    {
        $specModel = M('spec');
        $where['id'] = $specid;
        return $specModel->where($where)->save($data);
    }

    public function delSpec($specid)
    {
        if (is_array($specid)) {
            $where['id'] = ['in',$specid];
        } else {
            $where['id'] = ['in',[$specid]];
        }
        $save['isdelete'] = 1;
        $specModel = M('spec');
        return $specModel->where($where)->save($save);
    }


    public function  getOneGoods($id='')
    {
        $goodinfo = M("goods");
        $where['id']=$id;
        $data['res']=$goodinfo->where($where)->limit(1)->find();
        $data['res']['name'] = htmlspecialchars_decode($data['res']['name']);
        $specModel = M("spec");
        $map['goodsid']=$id;
        $map['isdelete'] = 0;
        $specinfo = $specModel->where($map)->select();
        foreach ($specinfo as $key=>$row) {
            if ($row['endtime']) {
                $specinfo[$key]['endtime'] = Date("Y-m-d G:i:s", $row['endtime']);
            }
        }
        $data['specinfo']=$specinfo;
        return $data;
    }

    public function getOneExpressGoods($id='')
    {
        $goodinfo = M("spec");
        $where['id']=$id;
        $res = $goodinfo->field('id,specname as name,price,pic,nums,limitype as limittype,limitnum,status')->where($where)->limit(1)->find();
        return $res;

    }

    //添加库存
    public function addGoodNum( $goodid, $num, $specid = '')
    {
        if ($specid) {
            $specModel = M('spec');
            $where['id'] = $specid;
            $res = $specModel->where($where)->setInc('nums', $num);
        } else {
            $goodModel = M('spec');
            $where['id'] = $goodid;
            $res = $goodModel->where($where)->setInc('nums', $num);
        }
        return $res;
    }

    public function addOrderFormat($data)
    {
        return M('orderformat')->addAll($data);
    }

    public function getOrderFormat($goodid)
    {
        $where['goodid'] = $goodid;
        $where['isdelete'] = 0;
        return M('orderformat')->where($where)->order('sort asc')->select();
    }

    public function getAllFormatIds($goodid)
    {
        $where['goodid'] = $goodid;
        $where['isdelete'] = 0;
        return M('orderformat')->where($where)->getField('id',true);
    }

    public function editOrderFormat($id,$data)
    {
        $where['id']= $id;
        $where['isdelete'] = 0;
        return M('orderformat')->where($where)->save($data);
    }

    public function delOrderFormat($ids = [])
    {
        if (in_array($ids) || count($ids)){
            $where['id'] = ['in',$ids];
        } else {
            $where['id']= $ids;
        }
        $saveData['isdelete'] = 1;
        return M('orderformat')->where($where)->save($saveData);
    }

    public function getOneProduct($id)
    {
        $where['id'] = $id;
        return M('product')->where($where)->limit(1)->find();
    }

    public function getAllgoodsAndSpec($productid)
    {
        $where['g.productid'] = $productid;
        return M('goods as g')
            ->join('__SPEC__ as s ON g.id = s.goodsid')
            ->where($where)
            ->field('g.id as goodid,g.name as goodname,s.id as specid,s.specname')
            ->select();
    }
}