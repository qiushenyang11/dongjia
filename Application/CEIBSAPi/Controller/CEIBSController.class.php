<?php
namespace CeibsApi\Controller;

use Think\Controller;
use Think\Exception;

class CEIBSController extends Controller
{

/*

测试地址：intest.eceibs.com  
Corpid: yxt
tid为：2084
secret_key : sddsintestsdasd

*/

    public  $zourl ='intest.eceibs.com' ; 
    public  $Corpid='yxt' ; 
 
    public  $secret_key='sddsintestsdasd' ; 

  //我的课程
    public function  my_course(  )
    {
        
//  https://test.dongrich.cn/myWeb/index.php/CEIBSAPi/CEIBS/my_course?uid=2    //播放

       
                needAuth('jdLogin');
//                var_dump(session('userid'));die;
                $uid = session('userid') ;

             //   $uid = I('uid','2'); //

                if( empty( $uid ) || intval( $uid)==0  ) response('无uid',0 ,array() );
                 

                 $db = M( 'order_info' ); // 实例化对象
                   
                 $where['order_info.userid']= $uid ;  
                 $where['goods.zo_tid']= array('neq',''); 
             //     , DATEDIFF(  NOW() ,FROM_UNIXTIME(order_info.addtime)) as syday 
      $list = $db->field( ' DATE_ADD(FROM_UNIXTIME(order_info.addtime),INTERVAL goods.zo_sday DAY) AS maxdate  ,   goods.zo_sday ,goods.zo_tid ,order_good.productname ,order_good.productpic ,order_info.addtime ,order_info.userid,order_info.jdaccount,order_info.username' )
                 ->join('order_good  on  order_info.id=order_good.orderid ')
                 ->join('goods  on  order_good.goodid=goods.id ')
                 ->where($where)->select( ) ;  // false print_r( $list ) ; exit ;

                 
                 $nowdate = date('Y-m-d H:i:s') ; 
                 
              foreach( $list as $key=>$val ){
                
                       $list[$key]['overdue']=0 ; 
                   if( $val['maxdate'] < $nowdate ){
                       $list[$key]['overdue']=1 ; //过期标记
                   }
                   
                   $tid = $val['zo_tid'] ;
                   $loginname = $val['jdaccount'] ; 
                   $username  = $val['jdaccount'] ; 
                
                   $time = $this->course_list( $tid ,1 ) ;

                   $list[$key]['complete'] = $time['complete'];
                
              }    
       
           //    print_r( $list ) ; 

           response('获取成功',1 ,$list);

        //    echo  json_encode( $list ) ;  

    }


  //课程列表
    public function  course_list( $tid='' , $return=0 )
    { // https://test.dongrich.cn/myWeb/index.php/CEIBSAPi/CEIBS/course_list?tid=xxx&uid=xx&loginname=xxxxx&username=xxxxx

      if( $return==0 ){
          $tid = intval(I('tid',''));
      }
      if( empty( $tid ) )   response('无tid',0 ,array() );
      
      needAuth('jdLogin');  //var_dump($_SESSION);
      $uid       = session('userid') ;
      $loginname = session('jdaccount') ; //用户唯一
      $username  = session('jdaccount') ; //
      
    //  $uid       = I('uid','1'); // 
    //  $loginname = I('loginname','jdgjtest'); //用户唯一    
    //  $username  = I('username','jdgjtestname'); //   
    //  $username  = urlencode($username) ; 

      $s1   = md5( $this->Corpid.$tid  ) ; 
      $sig  = md5( $this->secret_key.$s1  ) ; 
      
      $send = 'http://'.$this->zourl.'/index.php?r=front/scorm/shareScormListWithTrainplan&corpid='.$this->Corpid.'&tid='.$tid.'&timestamp='.time().'&sig='.$sig ;  //echo $send .'<hr/>';
            $res  = $this->send( $send  ) ;
            $list = json_decode( $res ,true ) ;
        $cszs = 0 ; 
        $csok = 0 ; 
        
      foreach( $list['courses'] as $key=>$val ){
        
             $time = $this->course_time( $tid ,$val['cid'] ,$uid ,$loginname ,$username ) ;
          $timelen =  json_decode( $time ,true ) ; 
        if( is_array( $timelen )){
            
            
            $zj = $timelen['data']['lesson_location'] ; 
            
            $zjarr = explode('|',$zj) ;
                        
            $jsf = $zjarr[0] ; 
            
            $j = strlen($jsf);  
            
               $okii = 0 ;
           for($i=0;$i<$j;$i++){
             if( $jsf[$i] == '1'){
                 $okii++ ;
             }      
           }     
            
             $cszs = $cszs + $j    ; 
             $csok = $csok + $okii ;    
             
            $timelen['data']['complete'] = round( ($okii/$j)* 100  ) ;   
            $list['courses'][$key]['timeinfo'] = $timelen ; 
            
        }
      }     
            $list['complete'] = round( ($csok/$cszs)* 100  ) ;           // print_r( $list ) ; 
      
         if(  $return == 1 ) return $list ; 
      
          // echo  json_encode( $list ) ;  
           
             response('获取成功',1 ,$list);
           
    }
    
  
  
  //学习时长
    public function  course_time( $tid ,$cid ,$uid ,$loginname ,$username ) 
    {
   
      $s1   = md5( $this->Corpid. $uid.$loginname.$username.$tid.$cid  ) ; 
      $sig  = md5( $this->secret_key.$s1  ) ; 
       
      $send = 'http://'.$this->zourl.'/apps/controller/html5/partner/trainplan_course.php?method=show_progress&corpid='.$this->Corpid.'&format=json&uid='.$uid.'&loginname='.$loginname.'&username='.$username.'&tid='.$tid.'&cid='.$cid.'&timestamp='.time().'&sig='.$sig;
      
      return   $this->send(  $send  ) ;
        
    }
  
    
    
//播放课程
    public function  course_play()
    {
// https://test.dongrich.cn/myWeb/index.php/CEIBSAPi/CEIBS/course_play?tid=2084&cid=54a0e3884468f&r_url=https://test.dongrich.cn/    //播放
    
   
    $r_url = I('r_url',''); // 
      $tid = I('tid'  ,''); //课  
      $cid = I('cid'  ,''); //子  //   md5($secret_key + md5($corpid + $uid + $loginname + $username + $tid  + $cid));

      if( empty( $tid ) )   response('无tid',0 ,array() );
      if( empty( $cid ) )   response('无cid',0 ,array() );
      if( empty( $r_url ) ) response('无r_url',0 ,array() );
      
      
      needAuth('jdLogin');  //var_dump($_SESSION);
      $uid       = session('userid') ;
      $loginname = session('jdaccount') ; //用户唯一
      $username  = session('jdaccount') ; //
        
  //    $uid       = I('uid','1'); // 
  //    $loginname = I('loginname','jdgjtest'); //用户唯一    
  //    $username  = I('username' ,'jdgjtestname'); //   
  //    $username = urlencode($username) ; //转义汉字

      $s1   = md5( $this->Corpid. $uid.$loginname.$username.$tid.$cid  ) ; 
      $sig  = md5( $this->secret_key.$s1  ) ; 
      
      $send = 'http://'.$this->zourl.'/apps/controller/html5/partner/trainplan_course.php?corpid='.$this->Corpid.'&uid='.$uid.'&loginname='.$loginname.'&username='.$username.'&tid='.$tid.'&cid='.$cid.'&timestamp='.time().'&sig='.$sig.'&return_url='.urlencode($r_url) ;

      $url = array( 'url'=>$send ) ;  
      
     // echo  json_encode( $url ) ;  
      
      response('获取成功',1 ,$url);

      // header('Location: '.$send); exit ; 
       
    }
	

    //发送参数 
    public function send( $url  )  //
    {
        $curl = curl_init(); // 启动一个CURL会话
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_HEADER, 0);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION,1);
                
          //      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // 跳过证书检查
         //       curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
              curl_setopt($curl, CURLOPT_TIMEOUT,10);
              curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        $data = curl_exec( $curl ) ;      //返回api的json对象
                curl_close($curl); //关闭URL请求
       // echo $url ;         
        return $data ; 
    }



}