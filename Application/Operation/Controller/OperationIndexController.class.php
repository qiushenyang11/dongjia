<?php
/**
 * Created by PhpStorm.
 * User: xiyou
 * Date: 2017/9/29
 * Time: 16:12
 * 后台首页
 * http://www.dservie.net/myWeb/index.php/Operation/OperationIndex/index
 */
namespace Operation\Controller;
use Think\Controller;


class OperationIndexController extends Controller
{

    public  function  index(){
        $name    = session('username');
        $isadmin = session('isadmin');
   //----------根据角色显示菜单-----------     
        
        $uid = session('adminuserid') ; 
        
        $idlist = '' ; 
        
        if( empty( $uid ) ) exit( ' 用户id为空！' ) ; 
        
        $admindb = M('admin');
        $admin   = $admindb->field('role')->where('id='.$uid)->find() ; //print_r( $admin ) ; 
        
        if( $admin ){
            
            if( $admin['role'] ){
                
             $where = ' is_del=0 AND id IN ('.$admin['role'].')' ; 
            
            $roledb = M('admin_role'); 
                $role = $roledb->field('menu_list')->where($where)->select() ;  //echo $roledb->getLastSql() ; 
            if( $role ){
                 $jgf='';
                foreach( $role as $vv ){
                     $idlist = $idlist.$jgf.$vv['menu_list'] ; 
                      $jgf=',';
                 }   
            } 
                
            }
            
        }
           
        $mcode = '' ;    
        
        if( $idlist || $name=='root' )  {
            
            $idlist = ','.$idlist.',';
            
              $menudb = M('admin_menu');
              
                $sql  ='SELECT * FROM admin_menu where is_del=0 ORDER BY row_sort desc ,id asc' ; 
              
                $menu = $menudb->query($sql) ;
            if( $menu ){
                
                $pmenu = $menu ; 
                
              foreach( $pmenu as $pp ){
                
                if( $pp['pid'] >0 ) continue ; 
                   
                    $sub = '' ; 
                  foreach( $menu as $mm ){
                    
                    if( $mm['pid']== $pp['id'] ){
                        $pos = strpos( $idlist ,','.$mm['id'].',') ;
                    if( $pos !== false || $name=='root' ){
                        $url =  U($mm['url']).$mm['url_parm'] ; 
                        $sub = $sub.'
							<li>
								<a _href="'.$url.'" data-title="'.$mm['menu_name'].'" href="javascript:void(0)" style="color:#333333">'.$mm['menu_name'].'</a>
							</li>
                          ' ;    
                    }
                    }    
                       
                 }      
                   
                 if( $sub<>'' ){
                  $mcode = $mcode .'
				<!------------------------------------------'.$pp['menu_name'].'----------------------------------->
				<dl>
					<dt><i class="Hui-iconfont unique_attributes">&#xe613;　</i>'.$pp['menu_name'].'<i class="Hui-iconfont menu_dropdown-arrow"></i></dt>
					<dd>
						<ul>
                       '.$sub.' 
						</ul>
					</dd>
				</dl>
                                   '  ; 
                 }  
                
              }
   
            } 

        }  
        
        
            
            
            
           
            
    
        
        
         
     
        $this->assign('menu_code',$mcode);
        $this->assign('isadmin',$isadmin);
        $this->assign('name',$name);
        $this->assign('uid',$uid);
        $this->display();
    }
}