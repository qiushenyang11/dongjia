<?php
/**
 * Created by PhpStorm.
 * User: xiyou
 * Date: 2017/10/9
 * Time: 12:19
 * 权限管理
 *
 */
namespace Operation\Controller;
use Think\Controller;
use Think\Auth;

class OperationAuthController extends Controller
{
  protected  function  _initialize(){
      $sess_auth=session('user');
      if(!$sess_auth){
          $this->error('还没有登录，正在跳转到登录页',U('OperationLogin/work'));
      }
      //超级管理员
      if($sess_auth['id']==1){
          return true;

      }
      $auth = new Auth();
      if(!$auth->check(MODULE_NAME.'/'.CONTROLLER_NAME, $sess_auth['id'])){
          $this->error('没有权限');
      }

  }



}