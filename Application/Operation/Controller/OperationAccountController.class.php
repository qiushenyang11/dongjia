<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/12/14
 * Time: 18:06
 */

namespace Operation\Controller;


use Operation\Model\AccountModel;
use Think\Controller;

class OperationAccountController extends OperationBaseController
{
    
        
    public function menu_set() //菜单列表
    {

        $adminModel = M('admin_menu');

        $data = $adminModel->where($where)->order('pid','row_sort desc')->select();

        $this->assign("result",$data);
        $this->display();
    }

    public function add_menu()
    {
        $adminModel = M('admin_menu');
        $levelone = $adminModel->where(['pid'=>0,'is_del'=>0])->field('id,menu_name')->select();
        if (IS_POST) {
            $menu_name = I('post.menu_name','');
            $pid = I('post.pid','');
            $url = I('post.url','');
            if (!$menu_name) response('参数错误');
            $data['pid'] = $pid;
            $data['menu_name'] = $menu_name;
            $data['layer'] = $pid ? 2: 1;
            $data['url'] = $url;
            $res = $adminModel->add($data);
            $res ? response('添加成功', 1) : response('添加失败');

        }
        $this->assign('levelone', $levelone);
        $this->display();
    }

    
    public function role_list() //角色列表
    {
        $p = I('get.p', 1);
        $where = 'is_del=0' ; 

        $accountModel = new AccountModel();
        $data = $accountModel->getRoleList($p,$where);
        $Page = $data['Page'];
        $first=$Page->firstRow+1;
        $rest=($data['count']-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($data['count']-$Page->listRows*($p-1));
        $end = $first + $rest-1;
        $all=$first.'-'.$end;
 

        $this->assign("count",$data['count']);
        $this->assign('page',$Page->show());
        $this->assign('nowPage',$p);
        $this->assign('totalPages',$Page->totalPages);
        $this->assign("result",$data['res']);
        $this->assign('all',$all);
        $this->display();
    }
    
    public function role_edit() //角色编辑
    {
   
        $id = I('get.id', 0);
        
        if ($id > 0) {
            $where['id'] = $id;
                     
            $adminModel = M('admin_role');
            $data = $adminModel->where($where)->limit(1)->find();
            
        } else {
            $data = array('id'=>0  ,
                    'rolename'=>'' ,
                   'menu_list'=>'' ,
                        ) ; 
        }


        $qx = ','.$data['menu_list'].',' ; 
        
        $xzcode='' ;
        
            $menu = M('admin_menu');
            $me = $menu->where('is_del=0')->order('pid')->select();
            
            $zz = $me ; 
            
          foreach( $me as $mk=>$mv  ){
            if($mv['pid']>0) continue;
            
               $xzcode = $xzcode .'
               <div class="col-sm-3" style="margin-bottom: 30px;">
               
               <ul class="list-unstyled">
               
               <li><b> '.$mv['menu_name'].'</b> </li>
               
               '   ; 
            
          foreach( $zz as $zk=>$zv  ){
            
              if( $zv['pid']==$mv['id'] ){
                
                
                $pos = strpos( $qx ,','.$zv['id'].',') ;
                
                
                if( $pos === false ){
                    $chk = '' ;    
                }else{
                    
                      $chk = 'checked' ;    
                }
                
                
                $xzcode = $xzcode .'
      <li>  <input type="checkbox" class="qxid" name="qxxz" value="'.$zv['id'].'" '.$chk.' >'.$zv['menu_name'].'  </li>
                    ' ; 
              }
          }   
            
         $xzcode = $xzcode .'  </ul></div>'   ; 
            
          }   
            
           
        
          $xzcode =  '
  <div class="container-fluid">
  <div class="row">
    '.$xzcode.'
  </div>
  </div>'   ; 
       
        
        
        
        $this->assign(  'data',$data  );
        $this->assign('xzcode',$xzcode);
        
        $this->display();
    
    }
    
    public function role_submit() //角色提交
    {
    
        $id        = I('post.id'        ,0  );
        $rolename  = I('post.rolename'  ,'' );
        $menu_list = I('post.menu_list' ,'' );

        if ( ! $rolename ) response('角色名称不能为空');
        if ( ! $menu_list) response('角色权限不能为空，请选择！');
        
        
        $data = array(
                'rolename' => $rolename  , 
               'menu_list' => $menu_list ,
                     ) ; 
        
        
            $adminModel = M('admin_role');
        
        if( $id > 0 ){
            $res = $adminModel->where('id='.$id)->save($data);
            
            if( $res==0 ) $res=1 ; 
            
        }else{
            $res = $adminModel->data($data)->add();
        }
            $res ? response('添加成功', 1): response('添加失败'.$res);    
    }
    
    public function role_del() //删除角色
    {
    
             $id = I('post.id' ,0  );
             
           if( $id > 0 ){
            
             $adminModel = M('admin_role');
             
             $data = array( 'is_del'=>1 );
   
             $res = $adminModel->where('id='.$id)->save($data);
             
             response('删除成功', 1);
            
           }else{
             response('id不能为空');
           }  
    
   
    
    
    }
    
    
    public function root()
    {
    
      $data = array(
                 'phone' => '138' , 
                 'name'   =>'root' , 
                 'isadmin'=> 1 , 
                 'is_stop'=> 0 , 
                    'role'=>'' , 
                   ) ; 
                   
       $data['code'] = md5Password($data['phone'],'root@qaz') ;       
                   

      $adminModel = M('admin'); 
      
           $res = $adminModel->where('name="root"')->find() ; 
       if( $res ){
                $rst = $adminModel->where('name="root"')->save($data);
            if( $rst==0 ) $rst=1 ; 
        
       }else{
                $rst = $adminModel->data($data)->add();
       }       
       
       $rst ? exit('添加成功'): exit('添加失败'.$rst);
    }
    
    
    
    public function index()
    {
        $p = I('get.p', 1);
        $type = I('get.type');
        $typename = I('get.typename');
        $where = [];
        if ($typename) {
            if ($type == 1) {
                $where['name'] = ['like',"%$typename%"];
            } elseif ($type == 2) {
                $where['phone'] = $typename;
            }
        }
        $where['name'] = array( 'neq','root' );

        $accountModel = new AccountModel();
        $data = $accountModel->getList($p, $where);
        $Page = $data['Page'];
        $first=$Page->firstRow+1;
        $rest=($data['count']-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($data['count']-$Page->listRows*($p-1));
        $end = $first + $rest-1;
        $all=$first.'-'.$end;
 
        
        $this->assign('type', $type);
        $this->assign('typename', $typename);
        $this->assign("count",$data['count']);
        $this->assign('page',$Page->show());
        $this->assign('nowPage',$p);
        $this->assign('totalPages',$Page->totalPages);
        $this->assign("result",$data['res']);
        $this->assign('all',$all);
        $this->display();
    }

    public function addAccount()
    {
        $id       = I('post.id'      ,0  );
        $phone    = I('post.phone'   ,'' );
        $name     = I('post.name'    ,'' );
        $password =trim( I('post.password','' ) );
        $is_stop  = I('post.is_stop' ,1  );
        
      $role_list  = I('post.role_list' , 0 );
        
        if (!$phone)     response('手机号不能为空');
        if (!$name)      response('用户名不能为空');
        if (!$role_list) response('角色不能为空,请选择！');

        if ( strtolower( trim($name) )=='root'){
            response('用户名不能为root ,已被系统占用！');
        }
        
        if (!$password){
           if( $id==0) response('密码不能为空');
        } 
        
        if (!$is_stop)  response('请选择状态');
       
        
        if (!preg_match('/^1\d{10}$/',$phone)) response('请输入正确的手机号');
       
        $accountModel = new AccountModel();
        
            $hasAccount = $accountModel->hasAccount($phone,$id);
        if ($hasAccount) response('该手机号已存在');
        
        $res = $accountModel->addAccount( $id ,$phone,$name,$password, $is_stop ,$role_list ); 
        
        $res ? response('添加成功', 1): response('添加失败'.$res);

    }
    
    
     public function addAccounts()
    {
   
        $id = I('get.id', 0);
        
        if ($id > 0) {
            $where['id'] = $id;
            $adminModel = M('admin');
            $data = $adminModel->where($where)->limit(1)->find();
            $data['password'] = '' ;
            
        } else {
            $data = array('id'=>0  ,
                       'phone'=>'' ,
                        'name'=>'' ,
                    'password'=>'' ,
                     'is_stop'=> 1 ,
                        'role'=>''  
                        ) ; 
        }
        
        
            $qx = ','.$data['role'].',' ; 
        
        $xzcode ='
               <ul class="list-inline">
                ' ;
        
            $menu = M('admin_role');
            $me = $menu->where('is_del=0')->order('id')->select();
            
         foreach( $me as $zk=>$zv  ){
                
                  $pos = strpos( $qx ,','.$zv['id'].',') ;
              if( $pos === false ){
                  $chk = '' ;    
              }else{
                  $chk = 'checked' ;    
              }
                $xzcode = $xzcode .'
      <li>  <input type="checkbox" class="qxid" name="qxxz" value="'.$zv['id'].'" '.$chk.' >'.$zv['rolename'].'  </li>
                    ' ; 
            
         }   
            
        $xzcode = $xzcode .'  </ul>'   ; 
        
        $this->assign('xzcode',$xzcode);
        $this->assign('data'  ,$data);
        $this->display();
    }
    
    public function queryInitdata()
    {
        $str = '[{"id":"1","pid":"0","layer":"1","menu_name":"BD\u7ba1\u7406","is_bottom":"0","url":"","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"2","pid":"1","layer":"2","menu_name":"BD\u5217\u8868","is_bottom":"1","url":"OperationBD\/index","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"3","pid":"0","layer":"1","menu_name":"\u7ba1\u5bb6\u7ba1\u7406","is_bottom":"0","url":"","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"4","pid":"3","layer":"2","menu_name":"\u7ba1\u5bb6\u5217\u8868","is_bottom":"1","url":"OperationGuanJia\/index","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"5","pid":"0","layer":"1","menu_name":"\u4f9b\u5e94\u5546\u7ba1\u7406","is_bottom":"0","url":"","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"6","pid":"5","layer":"2","menu_name":"\u4f9b\u5e94\u5546\u5217\u8868","is_bottom":"1","url":"OperationSupplier\/index","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"7","pid":"0","layer":"1","menu_name":"\u4ea7\u54c1\u7ba1\u7406","is_bottom":"0","url":"","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"8","pid":"7","layer":"2","menu_name":"\u4ea7\u54c1\u5217\u8868","is_bottom":"1","url":"OperationProduct\/productIndex","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"9","pid":"7","layer":"2","menu_name":"\u5f00\u666e\u52d2\u4ea7\u54c1","is_bottom":"1","url":"OperationProduct\/kplProduct","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"10","pid":"0","layer":"1","menu_name":"\u5206\u7c7b\u7ba1\u7406","is_bottom":"0","url":"","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"11","pid":"10","layer":"2","menu_name":"\u5206\u7c7b\u5217\u8868","is_bottom":"1","url":"OperationCategory\/index","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"12","pid":"0","layer":"1","menu_name":"\u6587\u7ae0\u7ba1\u7406","is_bottom":"0","url":"","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"13","pid":"12","layer":"2","menu_name":"\u6587\u7ae0\u5217\u8868","is_bottom":"1","url":"OperationArticle\/articleList","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"14","pid":"0","layer":"1","menu_name":"\u8ba2\u5355\u7ba1\u7406","is_bottom":"0","url":"","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"15","pid":"14","layer":"2","menu_name":"\u8ba2\u5355\u5217\u8868","is_bottom":"1","url":"OperationOrder\/index","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"16","pid":"0","layer":"1","menu_name":"\u8bc4\u4ef7\u7ba1\u7406","is_bottom":"0","url":"","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"17","pid":"16","layer":"2","menu_name":"\u8ba2\u5355\u8bc4\u4ef7","is_bottom":"1","url":"OperationComment\/index","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"18","pid":"0","layer":"1","menu_name":"\u8fd0\u8425\u4f4d\u7ba1\u7406","is_bottom":"0","url":"","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"19","pid":"18","layer":"2","menu_name":"\u9876\u90e8\u5206\u7c7b\u8fd0\u8425\u4f4d","is_bottom":"1","url":"OperationHomePage\/top","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"20","pid":"18","layer":"2","menu_name":"\u9996\u9875banner\u4f4d","is_bottom":"1","url":"OperationBanner\/index","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"21","pid":"18","layer":"2","menu_name":"\u5b9a\u5236\u5316\u8fd0\u8425\u4f4d","is_bottom":"1","url":"OperationHomePage\/diy","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"22","pid":"18","layer":"2","menu_name":"\u70ed\u95e8\u4ea7\u54c1","is_bottom":"1","url":"OperationHomePage\/hot","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"23","pid":"18","layer":"2","menu_name":"\u7ba1\u5bb6\u4f18\u9009","is_bottom":"1","url":"OperationHomePage\/ganjia","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"24","pid":"18","layer":"2","menu_name":"\u4ea7\u54c1\u63a8\u8350","is_bottom":"1","url":"OperationHomePage\/recommend","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"25","pid":"0","layer":"1","menu_name":"\u4f18\u60e0\u52b5\u7ba1\u7406","is_bottom":"0","url":"","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"26","pid":"25","layer":"2","menu_name":"\u4f18\u60e0\u52b5\u5217\u8868","is_bottom":"1","url":"OperationCoupon\/index","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"27","pid":"0","layer":"1","menu_name":"\u793c\u54c1\u5361\u7ba1\u7406","is_bottom":"0","url":"","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"28","pid":"27","layer":"2","menu_name":"\u793c\u54c1\u5361\u7ba1\u7406","is_bottom":"1","url":"OperationGiftCard\/index","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"29","pid":"27","layer":"2","menu_name":"\u793c\u54c1\u5361\u5305\u7ba1\u7406","is_bottom":"1","url":"OperationGiftCard\/packsIndex","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"30","pid":"0","layer":"1","menu_name":"\u8d22\u5bcc\u7528\u6237\u7b49\u7ea7","is_bottom":"0","url":"","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"31","pid":"30","layer":"2","menu_name":"\u7528\u6237\u5217\u8868","is_bottom":"1","url":"OperationUserLevel\/index","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"32","pid":"0","layer":"1","menu_name":"\u8d26\u53f7\u7ba1\u7406","is_bottom":"0","url":"","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"33","pid":"32","layer":"2","menu_name":"\u8d26\u53f7\u5217\u8868","is_bottom":"1","url":"OperationAccount\/index","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"34","pid":"32","layer":"2","menu_name":"\u89d2\u8272\u5217\u8868","is_bottom":"1","url":"OperationAccount\/role_list","url_parm":"","is_del":"0","row_sort":"0","created_date":null,"modified_date":null},{"id":"35","pid":"32","layer":"2","menu_name":"\u83dc\u5355\u8bbe\u7f6e","is_bottom":"1","url":"OperationAccount\/menu_set","url_parm":"","is_del":"1","row_sort":"0","created_date":null,"modified_date":null}]';
        $str = json_decode($str,true);
        M('admin_menu')->addAll($str);
    }
}