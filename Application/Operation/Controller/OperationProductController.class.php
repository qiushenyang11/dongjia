<?php
/**
 * Created by PhpStorm.
 * User:xiyou
 * Date: 2017/10/30
 * Time: 9:51
 * 产品管理:产品列表 添加产品
 *http://www.dservie.cn/myWeb/index.php/Operation/OperationProduct/
 */
namespace Operation\Controller;


use Operation\Model\MessageModel;
use Server\Category;
use Server\Goods;
use Think\Controller;
use WeChat\Model\GoodsModel;
use WeChat\Model\SupplierModel;
use WeChat\Model\WeChatUserModel;
use Operation\Model\CategoryModel;

class OperationProductController extends OperationBaseController
{
    public  function productIndex()
    {
        $p = I('get.p', 1);
        $type=I('get.type','');
        $ptype = I("get.ptype",'');
        $condition=I("get.condition",'');
        $status = I("get.status", 0);
        $producttype = I('get.producttype', 0);
        $servicity = I("get.servicity",'');
        $yiji = I('get.yiji', '');
        $erji = I('get.erji', '');
        $isexcel = I('get.isexcel', 0);
        $cate = new Category();
        $yijicates = $cate->getCategorys(2);
        $yijiname = '';
        $erjiname = '';
        $areaModel = M("areanew");
        if ($yiji) {
            $cates  = $cate->getCategorynameById($yiji);
            $yijiname = $cates[0]['name'];
        }
        if ($erji) {
            $cates =$cate->getCategorynameById($erji);
            $erjiname = $cates[1]['name'];
        }
        if ($servicity == '服务城市') {
            $servicity = '';
        }
        $goodsModel = new GoodsModel();
        $info = $goodsModel->productList($p, $type, $producttype,$condition, $yijiname, $erjiname,$servicity, $status,20,$ptype,$isexcel);
        if ($isexcel) {
            $this->toexcel($info);
            die;
        }
        // dump($info);exit;
        $allcity = $areaModel->field('id,areaname as text')->where(['level'=>['in',[1,2]]])->select();
        array_unshift($allcity,['id'=>'0','text'=>'全国']);
        array_unshift($allcity,['id'=>'-1','text'=>'服务城市']);
        $result = $info['res'];
        $count = $info['count'];
        $Page = $info['Page'];
        $first=$Page->firstRow+1;
        $rest=($count-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($count-$Page->listRows*($p-1));
        $end = $first + $rest-1;
        $all=$first.'-'.$end;
        $this->assign('servicity', $servicity);
        $this->assign('allcity',$allcity);
        $this->assign("count",$count);
        $this->assign("type",$type);
        $this->assign('page',$Page->show());
        $this->assign('condition', $condition);
        $this->assign('status', $status);
        $this->assign('yiji', $yiji);
        $this->assign('erji', $erji);
        $this->assign('yijicates', $yijicates);
        $this->assign('nowPage',$p);
        $this->assign('status',$status);
        $this->assign('producttype', $ptype);
        $this->assign('totalPages',$Page->totalPages);
        $this->assign("result",$result);
//     dump($result);
        $this->assign('all',$all);
        $this->display();
    }

    public function addProductExpress()
    {
        $userModel = new WeChatUserModel();
        $guanjiainfo = $userModel->getAllGuanJia();
        $bdinfo = $userModel->getAllBD();
        $this->assign('bdinfo',$bdinfo);
        $this->assign('guanjiainfo',$guanjiainfo);
        $this->display();
    }

    public function addProduct()
    {
        $userModel = new WeChatUserModel();
        $guanjiainfo = $userModel->getAllGuanJia();
        $bdinfo = $userModel->getAllBD();
        $supplierModel = new \Operation\Model\SupplierModel();
        $suppliers = $supplierModel->getAllSupplier();
        $message = '您的"{{产品名称}}"预订成功，服务时间{{服务时间}}，服务码为{{服务码}},可直接凭此码消费,如有问题,可咨询客服{{客服电话}}';
        $zidiyimessage = $message;
        $this->assign('zidiyimessage', $zidiyimessage);
        $this->assign('message', $message);
        $this->assign('bdinfo',$bdinfo);
        $this->assign('suppliers',$suppliers);
        $this->assign('guanjiainfo',$guanjiainfo);
        $this->display();
    }

    public function addTest()
    {
        $userModel = new WeChatUserModel();
        $guanjiainfo = $userModel->getAllGuanJia();
        $bdinfo = $userModel->getAllBD();
        $this->assign('bdinfo',$bdinfo);
        $this->assign('guanjiainfo',$guanjiainfo);
        $this->display();
    }

    public function editProductExpress()
    {

        $id = I("get.id", 0);
        if (!$id) $this->error('产品参数异常');
        $userModel = new WeChatUserModel();
        $guanjiainfo = $userModel->getAllGuanJia();
        $bdinfo = $userModel->getAllBD();
        $goodsModel = new GoodsModel();
        $result = $goodsModel->getOneProduct($id);
        $this->assign('result', $result);
        $this->assign('bdinfo',$bdinfo);
        $this->assign('guanjiainfo',$guanjiainfo);
        $this->display();
    }

    public function editProduct()
    {
        $id = I("get.id", 0);
        if (!$id) $this->error('产品参数异常');
        $userModel = new WeChatUserModel();
        $guanjiainfo = $userModel->getAllGuanJia();
        $bdinfo = $userModel->getAllBD();
        $goodsModel = new GoodsModel();
        $result = $goodsModel->getOneProduct($id);
        $supplierModel = new \Operation\Model\SupplierModel();
        $suppliers = $supplierModel->getAllSupplier();
        $messageid = $result['messageid'];
        $messagetype = $result['messagetype'];
        $message = '您的"{{产品名称}}"预订成功，服务时间{{服务时间}}，服务码为{{服务码}},可直接凭此码消费,如有问题,可咨询客服{{客服电话}}';
        $zidiyimessage = $message;
        if ($messageid) {
            $messageModel = new MessageModel();
            $zidiyimessage = $messageModel->getMessageByid($messageid);
        } else {
        }
        $this->assign('message', $message);
        $this->assign('zidiyimessage', $zidiyimessage);
        $this->assign('messagetype', $messagetype);
        $this->assign('result', $result);
        $this->assign('bdinfo',$bdinfo);
        $this->assign('suppliers',$suppliers);
        $this->assign('guanjiainfo',$guanjiainfo);
        $this->display();
    }

    public function saveProduct()
    {
        $id = I("post.id",'');
        unset($_POST['id']);
        $goodsClass = new Goods();
        if (!$id) {
            $res = $goodsClass->addProduct($_POST);
            $res ? response('添加产品成功',1, $res) : response('添加产品失败');
        } else {
            $res = $goodsClass->saveProduct($id, $_POST);
            $res ? response('修改成功',1) : response('修改失败');
        }
    }


    public function excelExport($data=array(),$filename="默认列表"){
        vendor("excel.PHPExcel");
        $objPHPExcel=new \PHPExcel();
        if(empty($data)){
            return;
        }
        $pColumnIndex = 0;

        foreach ($data as $key=>$row){
            $num=$key+1;

            foreach ($row as $row1){
                if($pColumnIndex<26){
                    $charIndex=chr(65+$pColumnIndex);
                }else if($pColumnIndex<702){
                    $charIndex=chr(64 + ($pColumnIndex / 26)) . chr(65 + $pColumnIndex % 26);
                }else{
                    $charIndex=chr(64 + (($pColumnIndex - 26) / 676)) . chr(65 + ((($pColumnIndex - 26) % 676) / 26)) . chr(65 + $pColumnIndex % 26);
                }
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue($charIndex.$num," ".$row1);
                $pColumnIndex++;
            }
            $pColumnIndex=0;

        }
        $filename=$filename."(".Date("Y-m-d").")".".xls";
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.$filename.'');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        die;
    }

    public function toexcel($res){ //导出全部产品数据

        if (empty($res)) return false;
        $excelData = $res;
        $arr=["产品ID","产品名称","类型","服务城市","一级分类","二级分类","供应商","供应商ID","管家","管家ID","预定短信","状态","预定流程","规格","单价","剩余库存","限购","状态","市场价","起订份数","结算方式","自动下线时间"];
        array_unshift($excelData, $arr);
        $this->excelExport($excelData,'产品列表数据' );

        echo '导出全部产品数据' ;
    }


    //开普勒产品列表
    public function kplProduct(){
        $p = I('get.p', 1);
        $type = I("get.type",'');
        $condition=I("get.condition",'');
        $goodsModel = new GoodsModel();
        $info = $goodsModel->kplproductList($p, $type, $condition,50);
//       dump($info);exit;
        $result = $info['res'];
        $count = $info['count'];
        $Page = $info['Page'];
        $first=$Page->firstRow+1;
        $rest=($count-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($count-$Page->listRows*($p-1));
        $end = $first + $rest-1;
        $all=$first.'-'.$end;
        $this->assign("count",$count);
        $this->assign("type",$type);
        $this->assign('page',$Page->show());
        $this->assign('condition', $condition);
        $this->assign('nowPage',$p);
        $this->assign('totalPages',$Page->totalPages);
        $this->assign("result",$result);
//         dump($result);
        $this->assign('all',$all);
        $this->display();
    }

    //查看单个商品介绍
    public function kplGoodsInfo(){
        $sku=I("post.sku",'');
//           $sku=100597;
        $kplproductMdoel = M("keplerlist");
        $where=[];
        $where['sku']=$sku;
        $res=$kplproductMdoel->where($where)->find();
        //       dump($res);
        echo $res['introduction'];


    }





}