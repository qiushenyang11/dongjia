<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/12/15
 * Time: 9:57
 */

namespace Operation\Model;


class AccountModel
{
    public function getTotal($where)
    {
        $adminModel = M('admin');
        return $adminModel->where($where)->count();
    }

    public function getList($p, $where = '')
    {
        $adminModel = M('admin');
        $count = $this->getTotal($where);
        $Page = new \Think\Page($count, 20);
        $Page->nowPageage = $p;
        $res = $adminModel->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        
        
        
     //--------角色名称替换---------   
        $roleModel = M('admin_role');
        $role = $roleModel->where('is_del=0')->select() ;
        
        $roletxt = array() ; 
        
        foreach( $role as  $kk=>$vv ){

           $roletxt[ $vv['id'] ] = $vv['rolename'] ; 
        } 
        
        foreach( $res as $key=>$val ){
        
          if( $val['role'] ){
            
                 $ids = explode( ',' , $val['role'] ) ;
                 
                 $jgf = '' ; 
              $role_txt ='' ; 
              foreach( $ids as $idv ){
                
                if( $idv > 0 ){
                    
                    if( isset( $roletxt[ $idv ]  ) ){
                        
                       $role_txt = $role_txt.$jgf.$roletxt[ $idv ]  ;  
                        $jgf = '、' ; 
                    }
                }
                
              }
        
          $res[$key]['role_txt'] = $role_txt ; 
        
          }
        }
        
      /*  
        $idlist = array() ; 
        $nmlist = array() ; 
        
        foreach( $role as  $kk=>$vv ){
            $idlist[] = ','.$vv['id'].',' ; 
            $nmlist[] = ','.$vv['rolename'].',' ; 
        } 
        foreach( $res as $key=>$val ){
            
          if( $val['role'] ){
           $res[$key]['role_txt'] = str_replace( $idlist ,$nmlist,','.$val['role'].',' ) ;  
           $res[$key]['role_txt'] = str_replace( ',,' ,'' ,','.$res[$key]['role_txt'] ) ;
          }  
            
        }
      */  
    //   print_r( $res ) ; exit ;  
       
       
       $data['count'] = $count;
        $data['Page'] = $Page;
        $data['res'] = $res;
        return $data;
    }


    public function getRoleList($p, $where = '')
    {
        $adminModel = M('admin_role');
        $count = $adminModel->where($where)->count();;
        $Page = new \Think\Page($count, 20);
        $Page->nowPageage = $p;
        $res = $adminModel->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
    //   print_r( $res ) ; exit ;  
       
       $data['count'] = $count;
        $data['Page'] = $Page;
        $data['res'] = $res;
        return $data;
    }


    public function addAccount( $id ,$phone,$name,$password, $is_stop ,$role)
    {
        $data['phone']   = $phone;
        $data['name']    = $name;
        $data['is_stop'] = $is_stop;
        $data['role']    = $role;
        
        if( $id>0 ){
            
            if( $password ){
                $data['code'] = md5Password($phone,$password) ; 
            }
            
        }else{
              $data['code'] = md5Password($phone,$password) ; 
        }
        
        $adminModel = M('admin');
        
        if( $id > 0 ){
            $res = $adminModel->where('id='.$id)->save($data);
            
            if( $res==0 ) $res=1 ; 
            
        }else{
            $res = $adminModel->data($data)->add();
        }
        
        return $res;
    }

    public function getOneAccountInfo($userid)          //供应商userid 为负数，后台工作人员为正数
    {
        if ($userid >= 0) {
            $where['id'] = $userid;
            $adminModel = M('admin');
            return $adminModel->where($where)->limit(1)->find();
        } else {
            $where['id'] = -$userid;
            return M('supplier')->field('id,suppliershort as name')->where($where)->limit(1)->find();
        }

    }

    public function hasAccount($phone,$id=0)
    {
        $adminModel = M('admin');
        
        $where['phone'] = $phone;
        $where['id'] = array('neq',$id);
   
        return $adminModel->where($where)->limit(1)->find();
    }
}