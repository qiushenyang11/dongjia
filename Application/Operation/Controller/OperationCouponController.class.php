<?php
/**
 * Created by PhpStorm.
 * User: xiyou
 * Date: 2018/5/28
 * Time: 15:30
 * http://www.dservie.cn/myWeb/index.php/Operation/OperationCouPon/index
 * 优惠券管理:  添加 编辑优惠卷  优惠卷列表
 */

namespace Operation\Controller;
use  Think\Controller;
use WeChat\Model\GuanJiaModel;
use WeChat\Model\GoodsModel;


class OperationCouponController extends OperationBaseController
{

    //https://www.dservie.cn/myWeb/index.php/Operation/OperationCoupon/index
    public function index()
    {
        $CouponLoop=M("couponloop");
        $selecttype=I("get.selecttype",'');
        $condition=I("get.condition",'');
              $where=[];
        if($selecttype==1){
           $where['id']=$condition;
        }else{
            $where['couponname']=['like',"%$condition%"];
        }
        $p = I('get.p',1);

        $count=$CouponLoop->where($where)->count();

        $Page=new \Think\Page($count,20);

        $show = $Page->show();

        $result=$CouponLoop->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();

        $countR=count($result);

        for($i=0;$i<$countR;$i++)
        {
            $data=$this->indexCombineInfo($result[$i]["id"]);

            $result[$i]=array_merge($result[$i],$data);

        }

        $Page->nowPageage=$p;

        $first=$Page->firstRow+1;
        $rest=($count-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($count-$Page->listRows*($p-1));
        $end = $first + $rest-1;
        $all=$first.'-'.$end;

        //dump($result);die;
        $this->assign('page',$show);

        $this->assign('all',$all);

        $this->assign('count',$count);

        $this->assign('all',$all);

        $this->assign('totalPages',$Page->totalPages);

        $this->assign('nowPage',$Page->nowPage);


        $this->assign('result',$result);

        $this->display();

    }

    /*初始化编辑信息*/
    public function editCoupon(){
        $id=I("get.id",'');
        $couPonLoop=M("couponloop as cp");
        $where['cp.id']=$id;
        $res=$couPonLoop
            ->join('__COUPON__ as c ON cp.id=c.loopid')
            ->join('__COUPONEXCHANGECODE__ as ch ON cp.id=ch.loopid')
            ->field('cp.isall,cp.category,cp.guanjiaid,cp.productid,cp.code,cp.limit,cp.couponname,cp.couponinfo,cp.count,c.codetype,c.couponuseage,c.coupontype,c.status,c.discountrate,c.discountprice,c.usecondition,c.usertype,c.canuserduring,c.canuserbegin,c.canuserend,ch.scence,ch.code as codeexchange,cp.couponurl ')
            ->where($where)->limit(1)->find();
        $whereR=array();
        $resultdata = [];
            if($res['category']){
                unset($whereR);
                $whereR['id']=array('in',$res['category']);
                $result = M('category')->field('level,id,name')->where($whereR)->select();
                foreach($result as $key => $row) {
                    if ($row['level'] == 1) {
                        $resultdata[] = [
                          'id'=>$row['id'],
                          'name'=>"一级分类:".$row['name'],
                            'type'=>1
                        ];
                    } else {
                        $resultdata[] = [
                            'id'=>$row['id'],
                            'name'=>"二级分类:".$row['name'],
                            'type'=>2
                        ];
                    }
                }
            }

        if($res['guanjiaid']){
            unset($whereR);
                $whereR['id']=array('in',$res['guanjiaid']);
                $result = M('guanjia')->field('id,guanjianame')->where($whereR)->select();
            foreach($result as $key => $row) {
                    $resultdata[] = [
                        'id'=>$row['id'],
                        'name'=>"管家:".$row['guanjianame'],
                        'type'=>3
                    ];
            }

        }
        if ($res['productid']){
                unset($whereR);
                $whereR['id']=array('in', $res['productid']);
                $result = M('product')->field('id,name')->where($whereR)->select();
            foreach($result as $key => $row) {
                $resultdata[] = [
                    'id'=>$row['id'],
                    'name'=>"产品:".$row['name'],
                    'type'=>4
                ];
            }

        }

        $couponurl = $res['couponurl'];
        $temp = [];
        if ($couponurl) {
            $couponurl = explode(';', $couponurl);
            $coupontype = $couponurl[0];            //1无 2 首页 3 管家页 4产品页 5连接
            $couponval = $couponurl[1];
            $temp['type'] = $coupontype;
            if ($coupontype == 2 || $coupontype == 5) {
                $temp['value'] = $couponval;
            } elseif ($coupontype == 3) {
                $temp['value'] = (M('guanjia')->where(['id'=>$couponval])->limit(1)->getField('guanjianame')).'('.$couponval.')';
            } elseif ($coupontype == 4) {
                $temp['value'] = (M('product')->where(['id'=>$couponval])->limit(1)->getField('name')).'('.$couponval.')';
            }
            $temp['id'] = $couponval;
        } else {
            $temp['type'] = 1;
            $temp['value'] = '';
            $temp['id'] = '';
        }

        $res['canuserend'] = Date("Y-m-d",strtotime($res['canuserend']));
        $res['canuserbegin'] = Date("Y-m-d",strtotime($res['canuserbegin']));
        $res['discountrate'] = floatval($res['discountrate']/100);
//         dump($res);
//        dump($resultdata);
        $this->assign('couponurl',$temp);
        $this->assign('result',$resultdata);
        $this->assign('res',$res);
        $this->display();

    }

    private function indexCombineInfo($loopid)
    {
        $Coupon=M("coupon");

        $where["loopid"]=$loopid;

        $rst=$Coupon->where($where)->limit(1)->find();

        $data=array();

        if($rst["coupontype"]==1)
        {
            $data["jinger"]="立减".$rst["discountprice"]."(满".$rst["usecondition"]."可用)";
        }
        else
        {
            $data["jinger"]="折扣:".($rst["discountrate"]*0.01)."(满".$rst["usecondition"]."可用)";
        }

        $where["status"]=0;

        $staticRst=$Coupon->where($where)->limit(1)->find();

        if($staticRst)
        {
            $data["status"]="停止领取";
        }
        else
        {
            $data["status"]="可以领取";
        }

        $where["status"]=2;

        $countRst=$Coupon->where($where)->count();

        if($countRst)
        {
            $data["hasPicked"]=$countRst;
        }
        else
        {
            $data["hasPicked"]=0;
        }

        $where["status"]=4;

        $countRstUse=$Coupon->where($where)->count();

        if($countRstUse)
        {
            $data["hasUsed"]=$countRstUse;
        }
        else
        {
            $data["hasUsed"]=0;
        }

        return $data;

    }

   public function excelCode(){
        $id=I('get.id','');
//        if(!$id)response('参数错误');
        $couponexchangecode=M("couponexchangecode as ch");
        $where=[];
        $where['ch.loopid']=$id;
        $res=$couponexchangecode->join('__COUPON__ as c ON ch.couponcode=c.couponcode')
            ->field('ch.loopid,ch.code,c.status')->where($where)->select();
        //dump($res);die;
        if($res){
            $exceldata = [];
            foreach($res as $key=>$row){
                $exceldata[$key]['loopid']=$row['loopid'];
                $exceldata[$key]['code']=$row['code'];
                $status = '';
                if($row['status'] == 0){
                    $status='已禁用';
                }elseif($row['status'] == 1){
                    $status='未领取';
                }elseif($row['status'] == 2){
                    $status='已领取';
                }elseif($row['status'] == 3){
                    $status='已锁定';
                }elseif($row['status'] == 4){
                    $status='已使用';
                }else{
                    $status='已过期';
                }
                $exceldata[$key]['ststus']=$status;
            }
        }
       // dump($exceldata);die;
       $this->excel($exceldata);
   }

    public function excelExport($data=[],$filename="默认列表"){
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

    /*优惠码批次excel导出*/
    private function excel($res){
        if (empty($res)) return false;
        $excelData = $res;
        $arr=["批次ID","优惠码","优惠码状态"];
        array_unshift($excelData, $arr);
        $this->excelExport($excelData,'优惠码批次数据');
    }



    public function addCoupon(){
      $this->display();
  }




}