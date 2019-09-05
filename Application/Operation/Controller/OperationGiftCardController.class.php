<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/7/30
 * Time: 14:32
 * Url: http://www.dservie.net/myWeb/index.php/Operation/OperationGiftCard/index
 */

namespace Operation\Controller;


use Operation\Model\CategoryModel;
use Org\Util\Date;
use Server\Goods;
use WeChat\Model\GoodsModel;
use WeChat\Model\GuanJiaModel;


class OperationGiftCardController  extends OperationBaseController
{

    //礼品卡列表
    public function index()
    {
        $p = I('get.p', 1);
        $type=I("get.type",'');
        $condition=I("get.condition",'');
        $status = I('get.status', '');
        $cardtype = I('get.cardtype',0);
//        echo $status;
        $where = [];
        if ($condition) {
            if ($type == 1) {
                $where['id'] = $condition;
            } elseif ($type == 2) {
                $where['name'] = ['like','%'.$condition.'%'];
            }
        }
        if ($status !== '') {
            $where['status'] = $status;
        }
        if ($cardtype) {
            $where['type'] = $cardtype;
        }
        $where['parent'] = 0;
        $model  = M('cardloop');
        $count = $model->where($where)->count();
        $Page = new \Think\Page($count, 20);
        $Page->nowPageage = $p;
        $res = $model
            ->field('id,name,price,num,bindnum,info,status,type')
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('id desc')
            ->select();
        //dump($data);exit;
        foreach ($res as $key => $row) {
            $res[$key]['price'] = floatval($row['price']);
        }
        $result = $res;
        $Page = $Page;
        $count = $count;
        $first=$Page->firstRow+1;
        $rest=($count-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($count-$Page->listRows*($p-1));
        $end = $first + $rest-1;
        $all=$first.'-'.$end;
        $this->assign('cardtype',$cardtype);
        $this->assign("count",$count);
        $this->assign("type",$type);
        $this->assign('page',$Page->show());
        $this->assign('condition', $condition);
        $this->assign('all',$all);
        $this->assign('nowPage',$p);
        $this->assign('totalPages',$Page->totalPages);
        $this->assign("result",$result);
        $this->assign('status', $status);
        $this->assign('condition',$condition);
        $this->assign('type',$type);
        $this->assign('status',$status);
        $this->display();
    }

    //卡包
    public function packsIndex()
    {
        $p = I('get.p', 1);
        $type=I("get.type",'');
        $condition=I("get.condition",'');
//        echo $status;
        $where = [];
        if ($condition) {
            if ($type == 1) {
                $where['id'] = $condition;
            } elseif ($type == 2) {
                $where['name'] = ['like','%'.$condition.'%'];
            }
        }

        $where['parent'] = 1;
        $model  = M('cardloop');
        $count = $model->where($where)->count();
        $Page = new \Think\Page($count, 20);
        $Page->nowPageage = $p;
        $res = $model
            ->field('id,name,cardlist,num,status')
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('id desc')
            ->select();
        //dump($data);exit;

        foreach ($res as $key => $row) {
            $cardlist = $row['cardlist'];
            $temp = [];
            if ($cardlist) {
                $cardlist = json_decode($cardlist, true);
                foreach ($cardlist as $row1) {
                    $temp[] = $row1['id'].':'.$row1['name'];
                }
            } else {
                $temp[] = '';
            }
            $res[$key]['cardlist'] = $temp;

        }
        $result = $res;
        $Page = $Page;
        $count = $count;
        $first=$Page->firstRow+1;
        $rest=($count-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($count-$Page->listRows*($p-1));
        $end = $first + $rest-1;
        $all=$first.'-'.$end;
        $this->assign("count",$count);
        $this->assign("type",$type);
        $this->assign('page',$Page->show());
        $this->assign('condition', $condition);
        $this->assign('all',$all);
        $this->assign('nowPage',$p);
        $this->assign('totalPages',$Page->totalPages);
        $this->assign("result",$result);
        $this->assign('condition',$condition);
        $this->assign('type',$type);
        $this->display();
    }

    //获取卡包剩余数量
    public function getPackesNum()
    {
        $id = I("get.id",0);
        $where['id'] = $id;
        $res = M('cardloop')->field('num,bindnum')->where($where)->limit(1)->find();
        $restnum = $res['num'] - $res['bindnum'];
        $data['id'] = $id;
        $data['restnum'] = $restnum;
        response('获取成功', 1, $data);
    }

    public function editPacks()
    {
        $id = I('get.id', 0);
        $where['id'] = $id;
        $res = M('cardloop')->where($where)->limit(1)->find();
        $res['cardlist'] = json_decode($res['cardlist'], true);
        $res['time'] = Date("Y-m-d", $res['time']);
        $this->assign('res', $res);
        $this->display();
    }

    public function excelData()
    {
        $id = I('get.id', 0);
        $where['loopid'] = $id;
        $res = M('card')->field('loopid,type,cardid,code')->where($where)->select();

        $arr=["批次ID","礼品卡类型","礼品卡编号","礼品卡密码"];
        foreach ($res as $key => $row)
        {
            if ($row['type'] == 2) {
                $res[$key]['type'] = '兑换卡';
            } else {
                $res[$key]['type'] = '充值卡';
            }
        }
        array_unshift($res, $arr);
        A('OperationProduct')->excelExport($res,'礼品卡批次('.$id.')信息');
    }

    public function excelPicksData()
    {
        $id = I('get.id', 0);
        $where['loopid'] = $id;
        $res = M('card')->field('loopid,name,cardid,code,status')->where($where)->select();
        $arr=["卡包ID","卡包名称","卡ID","卡包密码","卡包状态"];
        foreach($res as $key=>$row){
            $res[$key]['status']=$row['status'] == 1 ?"可以绑定":"停止绑定";
        }
        array_unshift($res, $arr);
        A('OperationProduct')->excelExport($res,'卡包批次('.$id.')信息');
    }


    public function editGiftCard()
    {
        $id = I('get.id', 0);
        $where['id'] = $id;
        $res = M('cardloop')->where($where)->limit(1)->find();
        if ($res['timetype'] == 1) $res['time'] = Date('Y-m-d', $res['time']);
        $res['price'] = floatval($res['price']);
        $res['desc'] = htmlspecialchars_decode($res['desc']);
        $list = [];
        $cateModel = new CategoryModel();
        if ($res['levelones']) {
            $res['levelones'] = substr($res['levelones'], 1,strlen($res['levelones'])-2);
            $listtmp = explode('-', $res['levelones']);
            foreach ($listtmp as $row) {
                $list[] = [
                    'type'=>1,
                    'id'=>$row,
                    'name'=>'一级分类:'.$cateModel->getCategory($row)['name']
                ];
            }
        }
        if ($res['leveltwos']) {
            $res['leveltwos'] = substr($res['leveltwos'], 1,strlen($res['leveltwos'])-2);
            $listtmp = explode('-', $res['leveltwos']);
            foreach ($listtmp as $row) {
                $list[] = [
                    'type'=>2,
                    'id'=>$row,
                    'name'=>'二级分类:'.$cateModel->getCategory($row)['name']
                ];
            }
        }
        if ($res['guanjiaids']) {
            $res['guanjiaids'] = substr($res['guanjiaids'], 1,strlen($res['guanjiaids'])-2);
            $listtmp = explode('-', $res['guanjiaids']);
            foreach ($listtmp as $row) {
                $list[] = [
                    'type'=>3,
                    'id'=>$row,
                    'name'=>'管家:'.M('guanjia')->where(['id'=>$row])->getField('guanjianame as name')
                ];
            }
        }
        if ($res['productids']) {
            $res['productids'] = substr($res['productids'], 1,strlen($res['productids'])-2);
            $listtmp = explode('-', $res['productids']);
            foreach ($listtmp as $row) {
                $list[] = [
                    'type'=>4,
                    'id'=>$row,
                    'name'=>'产品:'.M('product')->where(['id'=>$row])->getField('name')
                ];
            }
        }
        if ($res['supplierids']) {
            $res['supplierids'] = substr($res['supplierids'], 1,strlen($res['supplierids'])-2);
            $listtmp = explode('-', $res['supplierids']);
            foreach ($listtmp as $row) {
                $list[] = [
                    'type'=>5,
                    'id'=>$row,
                    'name'=>'供应商:'.M('supplier')->where(['id'=>$row])->getField('supplier as name')
                ];
            }

        }
        $cardurl = $res['cardurl'];
        $temp = [];
        if ($cardurl) {
            $cardurl = explode(';', $cardurl);
            $cardtype = $cardurl[0];            //1无 2 首页 3 管家页 4产品页 5连接
            $cardval = $cardurl[1];
            $temp['type'] = $cardtype;
            if ($cardtype == 2 || $cardtype == 5) {
                $temp['value'] = $cardval;
            } elseif ($cardtype == 3) {
                $temp['value'] = (M('guanjia')->where(['id'=>$cardval])->limit(1)->getField('guanjianame')).'('.$cardval.')';
            } elseif ($cardtype == 4) {
                $temp['value'] = (M('product')->where(['id'=>$cardval])->limit(1)->getField('name')).'('.$cardval.')';
            }
            $temp['id'] = $cardval;
        } else {
            $temp['type'] = 1;
            $temp['value'] = '';
            $temp['id'] = '';
        }
        if ($res['type'] == 2) {
            $exchangelist = json_decode($res['exchangelist'],true);
            $goodClass = new Goods();
            $goodsModel = new GoodsModel();
            foreach ($exchangelist as $key=>$row) {
                $exchangelist[$key]['selectlist'] = $goodClass->getAllgoodsAndSpec($row['productid']);
                $exchangelist[$key]['productname'] = $goodsModel->getOneProduct($row['productid'])['name'];
            }
            $res['exchangelist'] = $exchangelist;
        }
        $res['exchangelist'] = json_encode($exchangelist);
        $this->assign('cardurl', $temp);
        $this->assign('list', $list);
        $this->assign('res', $res);
//        dump($res);
        $this->display();
    }



    public function addGiftCard(){
        $this->display();
    }

    /*搜索跳转链接*/
    public function chooseAll()
    {
        $type=$_GET["type"];//$obj->type;

        $key=$_GET["key"];

        if($key&&$type)
        {
            if($type==4)
            {
                $Product=new GoodsModel();
                $res=$Product->getOneProductIdAndName1($key);
                if($res)
                {
                    $data[0]=[
                        'id'=>$res['id'],
                        'text'=>$res['name'].'('.$res['id'].')'
                    ];
                    echo json_encode($data);
                } else {
                    echo json_encode([]);
                }

            }
            else if($type==3)
            {
                $GuanJia=new GuanJiaModel();

                $res=$GuanJia->getOneGuanJiaNameAndId1($key);

                if($res)
                {
                    $data[0]=[
                        'id' =>$res['guanjiaid'],
                        'text'=>$res['guanjianame'].'('.$res['guanjiaid'].')'
                    ];
                    echo json_encode($data);
                } else {
                    echo json_encode([]);
                }

            }
            else
            {

            }
        }
        else
        {
            response("参数不能为空",0);
        }

    }




}