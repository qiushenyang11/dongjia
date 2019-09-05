<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/6/25
 * Time: 15:25
 * url: http://www.dservie.cn/myWeb/index.php/Operation/OperationLCS/index
 */

namespace Operation\Controller;
use Think\Controllelr;
use Operation\Model\LCSModel;
use Server\ExcelOperation;


class OperationLCSController  extends OperationBaseController
{
  public function index(){
      $type = I('get.type', '');
      $condition = I('get.condition', '');
      $p = I('get.p',1);
      $where=array();
      if($condition){
          if($type==1){
              $where['phone']=$condition;
          } elseif ($type==2){
              $where['name']=['like','%'.$condition.'%'];
          }
      }
      $lcsModel = new LCSModel();
      $data = $lcsModel->lcsList($where, $p);
      $result = $data['res'];
//        dump($result);exit;
      $Page = $data['Page'];
      $count = $data['count'];
      $first=$Page->firstRow+1;
      $rest=($count-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($count-$Page->listRows*($p-1));
      $end = $first + $rest-1;
      $all=$first.'-'.$end;
      $this->assign("count",$count);
      $this->assign('page',$Page->show());
      $this->assign('nowPage',$p);
      $this->assign('totalPages',$Page->totalPages);
      $this->assign("result",$result);
      $this->assign("all", $all);
      $this->assign('type', $type);
      $this->assign('condition',$condition);
      $this->display();
  }

  /*添加理财师*/
  public function addLCSInfo(){
      $name=I("post.name",'');
      $phone=I("post.phone",'');
      $jdaccount=I("post.jdaccount",'');
      $status=I("post.status",'');
      if(!($name && $phone && $jdaccount && $status )) response("参数错误");
      $dataLCS=array();
      $dataLCS['name']=$name;
      $dataLCS['phone']=$phone;
      $dataLCS['jdaccount']=$jdaccount;
      $dataLCS['status']=$status;
      $LCSModel = new LCSModel();
      $res=$LCSModel->addLCSInfo($dataLCS);
      $res ? response("添加成功", 1) : response("添加失败");
  }

  //理财师编辑初始信息
    public function editLCS(){
        $id=I("get.id",'');
        $LCSModel = new LCSModel();
        $res=$LCSModel->getOneLCS($id);
        $this->assign('res',$res);
        $this->display();
    }

    //编辑理财师信息
    public function saveLCS(){
        $id=I("post.id",'');
        $name=I("post.name",'');
        $phone=I("post.phone",'');
        $jdaccount=I("post.jdaccount",'');
        $status=I("post.status",'');
        if(!($name && $phone && $jdaccount &&$status && $id)) response("参数错误");
        $LCSModel = new LCSModel();
        $data=array();
        $data['name']=$name;
        $data['phone']=$phone;
        $data['jdaccount']=$jdaccount;
        $data['status']=$status;
        $res=$LCSModel->saveLCS($id,$data);
        response("修改成功",1);
    }

    /*理财师客户列表*/
    public function keHuIndex(){
        $jdaccount=I('get.jdaccount','');
        $type = I('get.type', '');
        $condition = I('get.condition', '');
        $p = I('get.p',1);
        $where=array();
        if($condition){
            if($type==1){
                $where['name']=['like','%'.$condition.'%'];
            } elseif ($type==2){
                $where['jdaccount']=['like','%'.$condition.'%'];
            }
        }
        $where['licashiaccount']=$jdaccount;
        $lcsModel = new LCSModel();
        $data = $lcsModel->lcsKeHuList($where, $p);
        $result = $data['res'];
        $Page = $data['Page'];
        $count = $data['count'];
        $first=$Page->firstRow+1;
        $rest=($count-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($count-$Page->listRows*($p-1));
        $end = $first + $rest-1;
        $all=$first.'-'.$end;
        $this->assign("count",$count);
        $this->assign("jdaccount",$jdaccount);
        $this->assign('page',$Page->show());
        $this->assign('nowPage',$p);
        $this->assign('totalPages',$Page->totalPages);
        $this->assign("result",$result);
        $this->assign("all", $all);
        $this->assign('type', $type);
        $this->assign('condition',$condition);
        $this->display();
    }

   /*添加理财师客户*/
   public function  addLCSKeHu(){
       $name=I("post.name",'');
       $licashiaccount=I("post.licashiaccount",'');
       $jdaccount=I("post.jdaccount",'');
       if(!($name && $licashiaccount && $jdaccount)) response("参数错误");
       $dataLCS=array();
       $dataLCS['name']=$name;
       $dataLCS['licashiaccount']=$licashiaccount;
       $dataLCS['jdaccount']=$jdaccount;
       $LCSModel = new LCSModel();
       $res=$LCSModel->addLCSKeHuInfo($dataLCS);
       $res ? response("添加成功", 1) : response("添加失败");
   }

   //客户编辑初始信息
    public function editKeHu(){
        $id=I("get.id",'');
        $LCSModel = new LCSModel();
        $res=$LCSModel->getOneKeHu($id);
        $this->assign('res',$res);
        $this->display();
    }

  //编辑客户信息
    public function saveKeHu(){
        $id=I("post.id",'');
        $name=I("post.name",'');
        $jdaccount=I("post.jdaccount",'');
        $licashiaccount=I("post.licashiaccount",'');
        if(!($name && $jdaccount &&$licashiaccount && $id)) response("参数错误");
        $LCSModel = new LCSModel();
        $data=array();
        $data['name']=$name;
        $data['phone']=$phone;
        $data['jdaccount']=$jdaccount;
        $data['status']=$status;
        $res=$LCSModel->saveLCS($id,$data);
        response("修改成功",1);
    }


    /*导入理财师*/
    public function LCSdb(){ //导入xls文件数据到 userLevel 表 [wdl]

        //   phpinfo() ; exit ;

        // $objname  表单中文件选择的 ID NAME
        // $dir      存放文件目录
        // $msize    文件大小限制 兆字节为单位  1M=1048576
        // $timedir  时间目录, 按月\天\小时\分目录存放  =1 有效 0-无效

        //  var_dump($_FILES) ;


        $objname = 'xls_file' ;
        $dir = '/tmp' ;
        $msize = 2 ;
        $timedir = 0 ;

        $size = $msize * 1048576 ; //限制字节

        $img_type = '' ;
        if( isset( $_FILES[$objname]['type'] ) )   $img_type = $_FILES[$objname]['type'] ;  //  application/octet-stream

        $img_name = '' ;
        if( isset( $_FILES[$objname]['name'] ) )   $img_name = $_FILES[$objname]['name'] ;  //  header.png

        $img_err = '' ;
        if( isset( $_FILES[$objname]['error']) )   $img_err  = $_FILES[$objname]['error'] ;  //  0

        $img_size = 0 ;
        if( isset( $_FILES[$objname]['size'] ) )   $img_size = $_FILES[$objname]['size'] ;  //  66399   // echo " \n\r --up--- $objname ". $objname  ;   print_r( $_FILES[$objname] ) ;

        $tmpName = $_FILES[$objname]['tmp_name'];

        $showvar = ' $img_type='.$img_type.'  $img_name='.$img_name.'  $img_err='.$img_err.'  $img_size='.$img_size .' $tmpName='.$tmpName ;

        if( empty( $img_name ) ){ //没有选择图片
            self::alert( ' 没有选择导入文件!' . $showvar )  ; exit ;
        }

        if(  $img_type == 'application/vnd.ms-excel' || $img_type == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ){

        }else{
            self::alert( '请注意文件类型：仅支持xls文件！'.$img_type ) ; exit ;
        }

        if ( $img_size > $size) {
            self::alert( '图片大小超过'.$msize.'M 限制.' .$showvar ) ; exit ;
        }

        if ( $img_err > 0) {
            self::alert( " 上传文件 错误代码: ". $img_err .' '. $showvar )  ; exit ;
        } else {
            $filename = $_FILES[$objname]["name"] ; //待上传文件名
            $ext = strtolower ( substr ( $filename, strrpos ( $filename, '.' ) + 1 ) ); //文件尾缀

            if( $timedir == 1 ){
                $newpath = $dir."/".date("Ym")."/".date("d")."/".date("H"); //目录
            }else{
                $newpath = $dir ;
            }

            if( ! file_exists($newpath) ) {
                if( ! mkdir($newpath ,0777 ,true ) ){ //如果没这个目录，就新建一个
                    self::alert(  "创建目录:".$newpath."失效！".$showvar ) ; exit ;
                }
            }

            $newfile =  $newpath."/".time()."_".mt_rand().".".$ext; //新的唯一文件名
            if( ! move_uploaded_file( $tmpName , $newfile ) ) {
                self::alert( "向服务器转移文件失效！".$showvar.' newfile-->'.$newfile ) ; exit ;
            }

            //文件前的相对路径 . 符号消除
            $newfile = trim( $newfile ) ;
            $savefile = $newfile ;
            if( strpos( './' , $newfile ) == 0 ){
                $savefile = str_replace ( './' ,'/' , $newfile ) ;
            }

        }

        //   echo  '成功' . ' filename'. $newfile .' savefile'.$savefile.' showvar-->'.$showvar  ;

//==================================================

        $excel = new ExcelOperation();
        $xdata = $excel->readExcel($newfile, $ext ) ;
        $arr=['姓名','手机号','头像','京东Pin'];

        foreach ($arr as $key =>$row){

            if($row!=trim($xdata['title'][$key])){
                self::alert( ' xls文件格式不正确，请按照( 姓名 , 手机号 ,头像， 京东Pin ) 四个栏目顺序进行导出！请保留标题栏！' ) ; exit ;
            }
        }




//        $nowtime = date('Y-m-d H:i:s') ;

        $addData=[];
        array_shift($data);


        foreach ($xdata['content'] as $key=>$row){
            if(empty($row)) continue;
            $addData[$key]['name']=    trim($row[0]);
            $addData[$key]['phone']     =    intval($row[1]);
            $addData[$key]['avatar']  = trim($row[2]);
            $addData[$key]['jdaccount']    =  trim($row[3]);
        }

        $ul = M("licaishi");

        $ul->addAll($addData);

        self::alert('导入成功','window.parent.LCSIndex()');



    }
    public function alert( $msg ,$addjs=''){

        echo '
      <script> 
      
          alert( "'. $msg. '" ) ; 
          
          '.$addjs.'
      
      </script> 
            ' ;

    }



    /*导入理财师客户*/
    public function LCSKeHudb(){
        //导入xls文件数据到 userLevel 表 [wdl]

        //   phpinfo() ; exit ;

        // $objname  表单中文件选择的 ID NAME
        // $dir      存放文件目录
        // $msize    文件大小限制 兆字节为单位  1M=1048576
        // $timedir  时间目录, 按月\天\小时\分目录存放  =1 有效 0-无效

        //  var_dump($_FILES) ;

        $licaishijdaccount = I('get.jdaccount','');
        $objname = 'xls_file' ;
        $dir = '/tmp' ;
        $msize = 2 ;
        $timedir = 0 ;

        $size = $msize * 1048576 ; //限制字节

        $img_type = '' ;
        if( isset( $_FILES[$objname]['type'] ) )   $img_type = $_FILES[$objname]['type'] ;  //  application/octet-stream

        $img_name = '' ;
        if( isset( $_FILES[$objname]['name'] ) )   $img_name = $_FILES[$objname]['name'] ;  //  header.png

        $img_err = '' ;
        if( isset( $_FILES[$objname]['error']) )   $img_err  = $_FILES[$objname]['error'] ;  //  0

        $img_size = 0 ;
        if( isset( $_FILES[$objname]['size'] ) )   $img_size = $_FILES[$objname]['size'] ;  //  66399   // echo " \n\r --up--- $objname ". $objname  ;   print_r( $_FILES[$objname] ) ;

        $tmpName = $_FILES[$objname]['tmp_name'];

        $showvar = ' $img_type='.$img_type.'  $img_name='.$img_name.'  $img_err='.$img_err.'  $img_size='.$img_size .' $tmpName='.$tmpName ;

        if( empty( $img_name ) ){ //没有选择图片
            self::alert( ' 没有选择导入文件!' . $showvar )  ; exit ;
        }

        if(  $img_type == 'application/vnd.ms-excel' || $img_type == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ){

        }else{
            self::alert( '请注意文件类型：仅支持xls文件！'.$img_type ) ; exit ;
        }

        if ( $img_size > $size) {
            self::alert( '图片大小超过'.$msize.'M 限制.' .$showvar ) ; exit ;
        }

        if ( $img_err > 0) {
            self::alert( " 上传文件 错误代码: ". $img_err .' '. $showvar )  ; exit ;
        } else {
            $filename = $_FILES[$objname]["name"] ; //待上传文件名
            $ext = strtolower ( substr ( $filename, strrpos ( $filename, '.' ) + 1 ) ); //文件尾缀

            if( $timedir == 1 ){
                $newpath = $dir."/".date("Ym")."/".date("d")."/".date("H"); //目录
            }else{
                $newpath = $dir ;
            }

            if( ! file_exists($newpath) ) {
                if( ! mkdir($newpath ,0777 ,true ) ){ //如果没这个目录，就新建一个
                    self::alert(  "创建目录:".$newpath."失效！".$showvar ) ; exit ;
                }
            }

            $newfile =  $newpath."/".time()."_".mt_rand().".".$ext; //新的唯一文件名
            if( ! move_uploaded_file( $tmpName , $newfile ) ) {
                self::alert( "向服务器转移文件失效！".$showvar.' newfile-->'.$newfile ) ; exit ;
            }

            //文件前的相对路径 . 符号消除
            $newfile = trim( $newfile ) ;
            $savefile = $newfile ;
            if( strpos( './' , $newfile ) == 0 ){
                $savefile = str_replace ( './' ,'/' , $newfile ) ;
            }

        }

        $excel = new ExcelOperation();
        $xdata = $excel->readExcel($newfile, $ext ) ;

        // var_dump( $xdata ) ;

        $arr=['客户姓名','头像','京东Pin'];

        foreach ($arr as $key =>$row){

            if($row!=trim($xdata['title'][$key])){

                //  echo json_encode(["state"=>0,'msg'=>"字段顺序不正确"]);return;


                self::alert( ' xls文件格式不正确，请按照( 客户姓名 ,头像, 京东Pin) 三个栏目顺序进行导入！请保留标题栏！' ) ; exit ;
            }
        }
        $addData=[];
        array_shift($data);


        foreach ($xdata['content'] as $key=>$row){
            if(empty($row)) continue;
            $addData[$key]['name']=    trim($row[0]);
            $addData[$key]['avatar']=    trim($row[1]);
            $addData[$key]['jdaccount']     =    trim($row[2]);
            $addData[$key]['licashiaccount']    =  $licaishijdaccount;
        }

        $ul = M("licaishikehu"); //

        $ul->addAll($addData);

        self::alert('导入成功','window.parent.LCSKeHuIndex()') ;

    }




}