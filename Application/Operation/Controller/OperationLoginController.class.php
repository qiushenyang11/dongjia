<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/9/29
 * Time: 15:28
 */
namespace Operation\Controller;
use Operation\Model\LoginModel;
use Think\Controller;
header("Content-Type: text/html;charset=utf-8");
/*
 登录
 http://www.dservie.net/myWeb/index.php/Operation/OperationLogin/work
 @author:xiyou
 created 2017/9/29


 */


class OperationLoginController  extends  Controller
{

    public function getCode() {
        $config = [
            'length'=>4,
            'useNoise'    =>    false,
        ];
        $Verify = new \Think\Verify($config);
        $Verify->entry();
    }

    public  function  work(){

        U('getcode');
        $this->display();
    }
    public  function loginOut(){
        //$this->success('退出成功',U('OperationLogin/work'));
        session('[destroy]');
        $this->redirect("OperationLogin/work");
            }

    //登录
    public  function  login(){
        $phone=I("post.phone",'');
        $code=I("post.code",'');
        $verifycode = I('post.verifycode', '');
        if (C('ISONLINE')) {
            $verify = new \Think\Verify();
            $res = $verify->check($verifycode);
            if (!$res) {
                echo "验证码错误";
                exit;
            }
        }
        $map=array();
        
        $map['phone']=$phone;
        $map['code']   =  md5Password($phone, $code);
 //       $map['_query'] = 'phone='.$phone.'&name="'.$phone.'"&_logic=or';       
        
        $login = new LoginModel();
        $result=$login->where($map)->find();  //echo $login->getLastSql() ; 

        if(!$result){
            
      /*      if(  $phone=='17621255963' && $code=='1236987' ){ //临时绕过 
                session('adminuserid',3 );
                session('isadmin'    ,1 );
                session('username'   ,'丹龙');
                $this->redirect('OperationIndex/index');
            }else{
        */    
            $message="用户名或密码错误!";  //print_r( $result ); exit ; 
            echo $message;
                
       //     }
            
            
            
        }else{
            
            if( $result['is_stop'] == 2 ) exit( '账号或密码错误!!' ) ; 
            
            session('adminuserid',$result['id']);
            session('isadmin',$result['isadmin']);
            session('username',$result['name']);
            $this->redirect('OperationIndex/index');
        }

    }



   }