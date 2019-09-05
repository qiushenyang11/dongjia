<?php
/**
 * Created by PhpStorm.
 * User: wdl
 * Date: 2018/07/03
 * Time: 15:23
 * 供应商管理:供应商列表，搜索查询,新建供应商...
 * http://www.dservie.cn/myWeb/index.php/Operation/OperationGuanJia/
 *
 */

namespace Operation\Controller;
use Common\Model\AreaModel;
use Server\Area;
use Server\Category;
use Think\Controller;
use WeChat\Model\SupplierModel;
use WeChat\Model\WeChatUserModel;

use AjaxApi\Model\LogModel;

class OperationSupplierController extends  OperationBaseController
{

    public function  index(){
        $p=(string)$_GET["p"]==0?1:$_GET["p"];
        $type=I("get.type",'');
        $condition=I("get.condition",'');
        $issign=I("get.issign",'');
        $temp = $type;
        if($issign==0){
            $where['issign']=0;
        } elseif( $issign==1){
            $where['issign']=1;
        }
        if($type==1){
            $where['supplier']=['like',"%$condition%"];
            $type="供应商名称";
        }
        if($type==2){
            $where['supplier.id']=$condition;
            $type="供应商D";
        }
        if($type==3){
            $where['contactphone']=$condition;
            $type="联系方式";

        }
        if($type==4){
            $where['name']=['like',"%$condition%"];
            $type="负责BD";
        }
        if(!$type){
            $where='';
        }
               $supplier = new SupplierModel();
        $data= $supplier->supplierList($where, $p);
        //dump($data);exit;
        $result= $data['res'];
        $Page  = $data['Page'];
        $count = $data['count'];
        $first = $Page->firstRow+1;
        $rest  =($count-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($count-$Page->listRows*($p-1));
        $end = $first + $rest-1;
        $all = $first.'-'.$end;
        $this->assign("count",$count);
        $this->assign("type",$temp);
        $this->assign('page',$Page->show());
        $this->assign('condition', $condition);
        $this->assign('nowPage',$p);
        $this->assign('all',$all);
        $this->assign('totalPages',$Page->totalPages);
        $this->assign("result",$result);
        $this->assign("issign",$issign);
//        dump($result);
        $this->display();
    }

    public function supplieredit()
    {
             $id = I("get.id", 0);
        
        if (!$id) $this->error('参数错误');
        
        
               $Model = new SupplierModel();
        $res = $Model->getOneSupplier($id);
        
        if( $res['contractstarttime']==0 ) $res['contractstarttime_d']='' ; 
        if( $res['contractendtime']  ==0 ) $res['contractendtime_d']='' ; 
        
        $areaid = $res['areaid'];
        $areaClass = new Area();
        $areas = $areaClass->getIdsByAreaid($areaid);
        
        $this->assign('areas', $areas);
        $this->assign('res', $res);
//        dump($res);
        $this->display( 'OperationSupplier/addSupplier' );
    }

    public function addsupplier()
    {
        $res['customertype'] = 1 ; 

        $this->assign('res', $res);
        $this->display( 'OperationSupplier/addSupplier' );
    }

    /**
     * @breif 根据areaid获取所有省|市|区  areaid =0 为所有省
     */
    public function getAreas()
    {
        $areaid = I("get.areaid", 0);
        $areaClass = new Area();
        $province = $areaClass->getAreaListByAreaId($areaid);
        response("获取成功", 1, $province);
    }

    public function getCategory()
    {
        $id = I("get.id", 0);
        $type = I("get.type", 1);
        $cateClass = new Category();
        $res = $cateClass->getCategorys($type, $id);
        response("获取成功", 1, $res);

    }

    public  function  getBD(){
        $BDModel = new WeChatUserModel();
        $res = $BDModel->BDList();
        response("获取成功",1,$res);
    }

    public function getAllBD()
    {
        $BDModel = new WeChatUserModel();
        $res = $BDModel->getAllBD();
        response("获取成功",1,$res);
    }

    //录入管家信息
    public function save()
    {
       
         $sv = array() ;  
         $supplierid = I("post.supplierid", 0);   //修改供应商时候用到
        
         $sv['contracturl'] = I("post.contracturl", '');    

         $sv['supplier']      = I("post.supplier", '');      
         $sv['suppliershort'] = I("post.suppliershort", ''); 
        
         $sv['contactname']   = I("post.contactname", '');   
         $sv['contactphone']  = I("post.contactphone", ''); 
        
         $sv['userid'] = I("post.userid", '');        
        
         $sv['customertype']  = I("post.customertype" , '1');  
         $sv['customerphone'] = I("post.customerphone", ''); 
        
         $sv['issign'] = I("post.issign", '0'); 
         $sv['contractid']        = I("post.contractid", '');        
         $sv['contractstarttime'] = strtotime( I("post.contractstarttime", '') ); 
         $sv['contractendtime']   = strtotime( I("post.contractendtime"  , '') );   
        
  
         $sv['areaid']      = I("post.areaid", '');     
         $sv['bankbrance']  = I("post.bankbrance", ''); 
         $sv['bank']        = I("post.bank", '');       
         $sv['bankaccount'] = I("post.bankaccount", '');
         $sv['bankid'] = I("post.bankid", '');     
         
         $sv['commissionrate'] = I("post.commissionrate", '');     
         $sv['settletime']     = I("post.settletime", '');     
         
         $sv['extra']  = I("post.extra", '');

         //管家账号
        $sv['phone'] = I('post.phone', '');
        $sv['code'] = I('post.code', '');

        //提供服务类型
        $sv['stype'] = I('post.stype', '');
        $sv['stime'] = I('post.stime', '');
        $sv['sinterval'] = I('post.sinterval', 15);
        $sv['waytime'] = I('post.waytime', 0);
        

        if (!( $sv['supplier'] && $sv['suppliershort'] && $sv['contactname'] && $sv['contactphone'] && $sv['userid']  ) )
            response("参数错误");

        $supplierModel = new \Operation\Model\SupplierModel();
        if (!$supplierid) {
            if ($sv['phone']) {
                if (!$sv['code']) response('请输入密码');
                $sv['code'] = md5SupplierPassword($sv['phone'], $sv['code']);
                if ($supplierModel->hasSupplierAccount($sv['phone'])) response('供应商账号不能重复');
            }

        } else {
            if ($sv['code'] && $sv['phone']) {
                $sv['code'] = md5SupplierPassword($sv['phone'], $sv['code']);
                if ($supplierModel->hasSupplierAccount($sv['phone'], $supplierid)) response('供应商账号不能重复');
            } else {
                unset($sv['code']);
            }
        }

        if ($sv['stype']) {
            $temp = explode(',', $sv['stype']);
            if (in_array(3,$temp)) {
                if (!($sv['stime'] && $sv['sinterval'] && $sv['waytime'])) response('请输入上门服务的必要字段');
            }
        }




        if( $supplierid == 0 )  $sv['addtime']  = time() ; //新增记录时间   

            $SupplierModel = new SupplierModel();
            
            $msg  = '' ; 
            $isok = 0  ; 
        
        if ( ! $supplierid ) { //新增
            
         //     if ($SupplierModel->hasSupplier( $sv['supplier'] )) response("供应商已存在__".$supplierid);
              if ($SupplierModel->hasSupplierphone(  $sv['contactphone'] ,$supplierid )) response("供应商联系手机号不能重复");

            $Id = $SupplierModel->saveSupplier( $sv );
            //添加默认组
            $data['name'] = '默认组';
            $data['supplierid'] = $Id;
            $res = M('svr_group')->data($data)->add();
            if( $Id && $res ){
                $msg  = '添加成功' ; 
                $isok = 1  ; 
            }else{
                $msg  = '添加失败' ; 
                $isok = 0  ; 
            }
            
        } else { //修改 
         
            
          
            if ($SupplierModel->hasSupplierphone( $sv['contactphone'] ,$supplierid  )) response("供应商联系手机号不能重复!");
          
            
            
            $SupplierModel->saveSupplier( $sv, $supplierid );

            $where['supplierid'] = $supplierid;
            $where['name'] = '默认组';
            if (!M('svr_group')->where($where)->limit(1)->find()) {
                $data['name'] = '默认组';
                $data['supplierid'] = $supplierid;
                $res = M('svr_group')->data($data)->add();
            }
            
                $msg  = '修改成功' ; 
                $isok = 1  ; 

        }
        
        if( $isok==1 ){
            $xdata = json_encode( $sv ); 
             
                   $logModel = new LogModel();
            $res = $logModel->addLog($xdata, time(), 'supplier');
        }
 
         response($msg ,$isok );

    }

    public function getAllCity()
    {
        $param = I("get.param",'');
        $areaModel = new AreaModel();
        $res = $areaModel->getAllCity($param);
        echo json_encode($res);

    }

    public  function  sss(){

        $this->display();
    }
    
    
    public function download(){
        
            $furl = I("get.furl"  , '');    
            $farr = explode( '||' , $furl );
        
        if( count( $farr)==2 ){
            $filename     = 'https://file.rose52.com'.$farr[0] ; 
            $out_filename = $farr[1] ; 
        }else{
             exit ; 
        }
   
            $fdata = file_get_contents($filename) ; 
            $flen  = strlen( $fdata ) ;     //    echo $flen ; exit ; 
   
            header('Accept-Ranges: bytes');
            header('Accept-Length: ' . $flen ); 
            header('Content-Transfer-Encoding: binary'); 
            header('Content-type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . $out_filename);
            header('Content-Type: application/octet-stream; name=' . $out_filename);
               echo $fdata ;
               exit;
    }

}