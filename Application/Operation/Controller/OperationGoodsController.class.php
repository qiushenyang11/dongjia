<?php
/**
 * Created by PhpStorm.
 * User: xiyou
 * Date: 2017/10/11
 * Time: 15:51
 * 商品管理 商品列表，商品添加，商品编辑
 * http://www.dservie.cn/myWeb/index.php/Operation/OperationGoods/index
 */
namespace Operation\Controller;
use Operation\Model\GoodsModel;
use Server\Goods;
use Server\Order;
use  Think\Controller;

class OperationGoodsController  extends  OperationBaseController
{
  public  function  index(){
      $p=(String)$_GET["p"]==0?1:$_GET["p"];
      $goods = new \Operation\Model\GoodsModel();
      $goodname=I('post.goodname','');
      $count=$goods->count();
      $Page=new \Think\Page($count,3);
      $map=array();
      if($goodname){
         // $map["goodname"]=$goodname;
          $map["goodname"]=array('like',"%$goodname%");
          $Page=new \Think\Page($count,3);
          $show = $Page->show();
          $Page->nowPageage=$p;
          $result=$goods->where($map)->limit($Page->firstRow.','.$Page->listRows)->select();

      }else{
          $show = $Page->show();
          $Page->nowPageage=$p;
          $result=$goods->limit($Page->firstRow.','.$Page->listRows)->select();
      }

      $this->assign('page',$show);
      $this->assign('totalPages',$Page->totalPages);
      $this->assign('nowPage',$Page->nowPage);
      $this->assign('result',$result);
      $this->display();
  }

  //商品列表
    public  function  goodsIndex(){
        $p=(string)$_GET["p"]==0?1:$_GET["p"];
        $productid=I("get.productid",''); //$name=I("get.name",'');
        $goodsModel = new GoodsModel();
        $data=$goodsModel->goodsinfo($p,$productid);
        $res=$data['res'];
        $Page=$data['Page'];
        $count=$data['count'];
        $first=$Page->firstRow+1;
        $rest=($count-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($count-$Page->listRows*($p-1));
        $end = $first + $rest-1;
        $all=$first.'-'.$end;
        $name=$data['productname'];
        $this->assign("productname",$name);
        $this->assign("result",$res);
        $this->assign('page',$Page->show());
        $this->assign('nowPage',$p);
        $this->assign('totalPages',$Page->totalPages);
        $this->assign("count",$count);
        $this->assign('all',$all);
        $this->display();
}

    public function addEditGoods()
    {
        $id = I('get.productid', 0);
        $productModel = new GoodsModel();
        $data = $productModel->getOneProduct($id);
        $supplierid = $data['supplierid'];
        $type = $data['type'];
        $groupinfo = M('svr_group')->field('id,name')->where(['supplierid'=>$supplierid,'isdelete'=>0])->select();
        if (!$groupinfo && $type == 1) {
            //添加默认组兼容之前
            $data1['supplierid'] = $supplierid;
            $data1['name'] = '默认组';
            $gid = M('svr_group')->data($data1)->add();
            $groupinfo[] = [
                'id'=>$gid,
                'name'=>$data1['name']
            ];
        }
        //获取供应商下所有的可用组
        $this->assign('groupinfo', $groupinfo);
        $this->display();
    }

    /**
     * @breif 新建添加商品
     */
    public function saveGoods()
    {
        $param['name'] = $_POST['name'];
        $param['type'] = I('post.type', 1);
        $param['spec'] = I('post.spec', 0);
        $param['status'] = I('post.status', 1);
        $param['productid'] = I('post.productid', '');
        $param['paystyle'] = I('post.paystyle', 1);
        $param['specinfo'] = $_POST['specinfo'];
        $param['orderformat'] = I('post.orderformat', '');
        $param['isselecttime'] = I('post.isselecttime', '');            //是否选择服务时间
        $param['caltype'] = I('post.caltype', 0);                       //提前预约条件
        $param['advancetime'] = I('post.advancetime', '');              //固定日期为小时，自然人计算: 1,23:00 1天23点之前
        $param['booktime'] = I('post.booktime','');
        $param['noservicetime'] = I('post.noservicetime', '');          //|2;3;4;5,2018.11-2018.12|2,3,4,2019.10-2019-20|
        $param['isselectstaff'] = I('post.isselectstaff', '');
        $param['staffgroup'] = I('post.staffgroup', '');
        $goodid = I('post.goodid', 0);
        $goodClass = new Goods();
        if (!$goodid) {
            $res = $goodClass->addGoods($param);
            $res ? response('商品添加成功', 1) : response('商品添加失败');
        } else {
            //TODO 编辑商品添加
            $param['delids'] = I("post.delids", '');
            $res = $goodClass->saveGoods($goodid, $param);
            $res ? response('商品修改成功',1) : response('商品修改失败');

        }
    }


    /*
     * 编辑商品
     * */

    public  function  editGood(){
        $id=I("get.goodid",'');
        if(!$id){
            $this->error("编辑商品参数异常");
        }
        $goodsModel = new GoodsModel();
        $data=$goodsModel->getOneGoods($id);
        foreach ($data['specinfo'] as $key=>$row) {
            if (empty($row['orginprice']) || $row['orginprice'] == '0.00') {
                $data['specinfo'][$key]['orginprice'] = '';
            }
        }
        $noservicetime = $data['res']['noservicetime'];
        if ($noservicetime) {
            $noservicetime = json_decode($noservicetime, true);
            $week =[
                0=>'周日',
                1=>'周一',
                2=>'周二',
                3=>'周三',
                4=>'周四',
                5=>'周五',
                6=>'周六',
            ];
            foreach ($noservicetime as $key=> $row) {

                if ($row['weekends']) {
                    $noservicetime[$key]['weekends'] =implode(';',$row['weekends']);
                    $str = '';
                    foreach ($row['weekends'] as $row1) {
                        $str.=$week[$row1].',';
                    }
                    $str = rtrim($str,',');
                    $noservicetime[$key]['weekname'] = $str;
                }

                if ($row['date']) {
                    $noservicetime[$key]['date'] =$row['date'][0].'-'.$row['date'][1];
                }
            }
        }
        $data['res']['noservicetime'] = $noservicetime;
        $caltype = $data['res']['caltype'];
        $advancetime = $data['res']['advancetime'];
        $staffgroup = $data['res']['staffgroup'];
        if ($staffgroup) {
            $staffgroup = explode(',',$staffgroup);
            $data['res']['staffgroup'] = $staffgroup;
        }
        if ($caltype == 2) {
            list($day, $time) = explode(',',$advancetime);
            $caldata['type'] = $caltype;
            $caldata['day'] = $day;
            $caldata['time'] = $time;
        } elseif ($caltype == 1) {
            $caldata['type'] = $caltype;
            $caldata['day'] = '';
            $caldata['time'] = $advancetime;
        } else {
            $caldata = '';
        }
        $data['res']['caldata'] = $caldata;
        $orderFormat = $goodsModel->getOrderFormat($id);
        if (!count($orderFormat)) {
            $orderFormat[]=[
                'name'=>'姓名',
                'type'=>1,
                'require'=>1,
                'numtype'=>1,
                'sort'=>1,
                'extra'=>'',
                'id'=>0
            ];
            $orderFormat[]=[
                'name'=>'手机号',
                'type'=>1,
                'require'=>1,
                'numtype'=>1,
                'sort'=>2,
                'extra'=>'',
                'id'=>0
            ];
        }
        $orderClass = new Order();
        $orderformat = $orderClass->setOrderFormat($orderFormat);

        $productModel = new GoodsModel();
        $tempdata = $productModel->getOneProduct($data['res']['productid']);
        $supplierid = $tempdata['supplierid'];
        $type = $tempdata['type'];
        $groupinfo = M('svr_group')->field('id,name')->where(['supplierid'=>$supplierid,'isdelete'=>0])->select();
        if (!$groupinfo && $type == 1) {
            //添加默认组兼容之前
            $tempdata1['supplierid'] = $supplierid;
            $tempdata1['name'] = '默认组';
            $gid = M('svr_group')->data($tempdata1)->add();
            $groupinfo[] = [
                'id'=>$gid,
                'name'=>$tempdata1['name']
            ];
        }
        //获取供应商下所有的可用组
        $this->assign('groupinfo', $groupinfo);

        $this->assign('orderformat', $orderformat);
        $this->assign('res',$data['res']);
        $this->assign('specinfo',$data['specinfo']);
//        dump($data['res']);
//        dump($data['res']);
//        dump($orderformat);
//         dump($data['specinfo']);
        $this->display();

    }

    public function saveExpressGoods()
    {
        $param['name'] = $_POST['name'];
        $param['pic'] = I('post.pic', '');
        $param['price'] = I('post.price', 0);
        $param['nums'] = I('post.nums', 0);
        $param['limitnum'] = I('post.limitnum', 0);
        $param['limittype'] = I('post.limittype', 1);
        $param['status'] = I('post.status', 1);
        $param['productid'] = I('post.productid', 0);
        $param['id'] = I('post.id', 0);
        $param['orginprice'] = I('post.orginprice', 0);
        $goodsClass = new Goods();
        if (!$param['id']) {
           $res = $goodsClass->addExpressGoods($param);
           $res ? response('商品添加成功', 1) : response('商品添加失败');
        } else {
            $res = $goodsClass->saveExpressGoods($param);
            $res ? response('商品修改成功', 1) : response('商品修改失败');
        }
    }

    public  function  goodsExpressIndex(){
        $p=(string)$_GET["p"]==0?1:$_GET["p"];
        $productid=I("get.productid",''); //$name=I("get.name",'');
        $goodsModel = new GoodsModel();
        $data=$goodsModel->goodsExpressInfo($p,$productid);
        $res=$data['res'];
        $Page=$data['Page'];
        $count=$data['count'];
        $first=$Page->firstRow+1;
        $rest=($count-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($count-$Page->listRows*($p-1));
        $end = $first + $rest-1;
        $all=$first.'-'.$end;
        $name=$data['productname'];
        $this->assign("productname",$name);
        $this->assign("result",$res);
        $this->assign('page',$Page->show());
        $this->assign('nowPage',$p);
        $this->assign('totalPages',$Page->totalPages);
        $this->assign("count",$count);
        $this->assign('all',$all);
        $this->display();
    }

    public function editExpressGoods()
    {
        $id=I("get.goodid",'');
        if(!$id){
            $this->error("编辑商品参数异常");
        }
        $goodsModel = new GoodsModel();
        $data=$goodsModel->getOneExpressGoods($id);
        if ($data['orginprice'] == '0.00' || empty($data['orginprice'])) {
            $data['orginprice'] = '';
        }
        $this->assign('res',$data);
        //dump($data['res']);
        // dump($data['specinfo']);die;
        $this->display();
    }

}