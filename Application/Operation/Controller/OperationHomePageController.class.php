<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/9/18
 * Time: 18:59
 */

namespace Operation\Controller;
use Think\Controller;
use Think\Page;

use Server\Goods;

class OperationHomePageController extends Controller
{

    //首页数据   https://test.dongrich.cn/myWeb/Operation/OperationHomePage/home_menu?cityname=北京
    public function home_menu( $cityname='' ){

        if( ! $cityname ) $cityname = trim(  I('get.cityname','') );   //echo ' id--->'.$id ;
        if( empty($cityname )) $cityname = session('address') ;

        if( self::citylist('find',$cityname) === false )  $cityname = '上海' ;

        $pid = 0;   //echo ' id--->'.$id ;

        //--------菜单---------------
        $res =  self::getdata( $cityname , 'menu' ,$pid ) ;

        $hp = array() ;

        foreach( $res as $key=>$val ){

            $id  = $val['id'] ;
            $tit = $val['title'] ;

            $two =  self::getdata( $cityname , 'menu' ,$id ) ;

            foreach( $two as $kk=>$vv){
                $hp[ $tit ][] = array(
                    'id'=>$vv['id'] ,
                    'title'=>$vv['title']  ,
                    'a_img'=>$vv['a_img']  ,
                    'a_type'=>$vv['a_type'] ,
                    'a_id'  =>$vv['a_id']   ,
                    'a_txt' =>$vv['a_txt']  ,
                ) ;
            }

        }

        // print_r( $hp) ;

        response('成功_'.$cityname ,1, $hp ) ;

    }

    //首页数据   https://www.dservie.cn/myWeb/Operation/OperationHomePage/homepagedata?cityname=北京
    public function homepagedata( $cityname='' ){

        if( ! $cityname ) $cityname = trim(  I('get.cityname','') );   //echo ' id--->'.$id ;
        if( empty($cityname )) $cityname = session('address') ;
        //-------------------------------------------------
        //   $cy = M("homepage")->where('city="'.$cityname.'"')->select() ;


        if( self::citylist('find',$cityname) === false )  $cityname = '上海' ; // 非开放城市 就默认为上海  echo ' $cityname = '.$cityname ; exit ;


        $hp = array() ;
        $hp['cityname'] = $cityname ;
        $nowtime = date('Y-m-d H:i:s') ; //当前时间

        //--------顶部---------------
        $res =  self::getdata( $cityname , 'top' ,12 ) ;

        foreach( $res as $key=>$val ){
            $hp['top'][] = array(
                'id'=>$val['id'] ,
                'title'=>$val['title']  ,
                'a_img'=>$val['a_img']  ,
                'a_type'=>$val['a_type'] ,
                'a_id'  =>$val['a_id']   ,
                'a_txt' =>$val['a_txt']  ,
            ) ;
        }
        //--------banner---------------
        $res =  self::getdata( $cityname , 'banner' ,6 ) ;

        foreach( $res as $key=>$val ){

            $a_id  = 0 ;
            $a_txt ='' ;

            if( $val['urltype']==3){
                $a_txt = $val['urltvalue'] ;
            }else{
                $a_id  = $val['urltvalue'] ;
            }

            $hp['banner'][] = array(
                'id'=>$val['id'] ,
                'title'=>$val['title']  ,
                'a_img'=>$val['pic']  ,
                'a_type'=>$val['urltype'] ,

                'a_id'  =>$a_id  ,
                'a_txt' =>$a_txt ,
            ) ;
        }
        //--------定制---------------
        $res =  self::getdata( $cityname , 'diy' ,1 ) ;

        foreach( $res as $key=>$val ){
            $hp['diy'][] = array(
                'id'=>$val['id'] ,
                'title'=>$val['title']  ,

                'a_img'=>$val['a_img']  ,
                'a_type'=>$val['a_type'] ,
                'a_id'  =>$val['a_id']   ,
                'a_txt' =>$val['a_txt']  ,

                'b_img' =>$val['b_img']  ,
                'b_type'=>$val['b_type'] ,
                'b_id'  =>$val['b_id']   ,
                'b_txt' =>$val['b_txt']  ,

                'c_img' =>$val['c_img']  ,
                'c_type'=>$val['c_type'] ,
                'c_id'  =>$val['c_id']   ,
                'c_txt' =>$val['c_txt']  ,

            ) ;
        }

        //--------热门---------------
        $res =  self::getdata( $cityname , 'hot' ,4 ,'all' ) ; //  var_dump( $res ) ; exit ;

        $ids = array() ;
        foreach( $res as $key=>$val ){
            $ids[] = $val['a_id'] ;
        }
        $hp['hot'] = array() ;
        if( $ids && count($ids)>=4 ){ //

            $where = array('p.id' => array('in' , $ids ) );

            $productList = self::getproduct(0, 4,1,false, false,$where,false); // print_r( $where ) ;  echo ' $goodsClass->getProduct --> ' ; var_dump($productList);die;

            foreach( $productList as $key=>$val ){
                // var_dump($val);die;

                $hp['hot'][] = array(

                    'id'=> $val['id'] ,
                    'title'=> $val['guanjianame']  ,
                    'a_img'=> $val['facepic']  ,
                    'a_type'=>  4 ,
                    'a_id'  => $val['id']   ,
                    'a_txt' => $val['name']  ,
                    'price' => $val['price']  ,

                ) ;
            }

        }
        //--------管家优选---------------
        $res =  self::getdata( $cityname , 'ganjia' ,6 ) ;  // print_r( $res ) ;

        $hp['ganjia'] = array() ;

        if( count($res)>=0 ){ //&

            foreach( $res as $key=>$val ){
                //    var_dump($val);die;
                $hp['ganjia'][] = array(
                    'id'  =>$val['id'] ,
                    'join_id'  =>$val['join_id']    ,
                    'join_name'=>$val['join_name']  ,
                    'title'=>$val['title']  ,
                    'a_img'=>$val['a_img']  ,
                    'a_type'=>  4 ,
                    'a_id'  =>$val['a_id']   ,
                    'a_txt' =>$val['a_txt']  ,
                    'avatarurl'=>$val['avatarurl']
                ) ;
            }
        }

        //--------分类推荐---------------
        //    $res =  self::getdata( $cityname , 'recommend' ,6 ,'all' ) ;
        $savetype = 'recommend' ;

        $citywhere = ' AND  ((city_type=1 AND (city_rang     LIKE "%,'.$cityname.'%"      OR  city_rang LIKE "%,全国%" ) ) 
                     OR    (city_type=2 AND (city_rang NOT LIKE "%,'.$cityname.'%"  AND NOT city_rang LIKE "%,全国%" ) ) ) ' ;



        $where = ' savetype="'.$savetype.'" AND is_show=1' ;
        $where = $where.$citywhere ;

        $where = $where.' AND show_start <="'.$nowtime.'" AND show_end >="'.$nowtime.'" ' ;    //  echo '$where-->'.$where ;

        $sql = ' SELECT categoryid ,categoryname FROM  product 
              WHERE  categoryid IN ( 
                 SELECT a_id FROM homepage_set 
                  WHERE  '.$where.'  GROUP BY a_id   ORDER BY frame ASC, utime DESC
                                  )
                  AND  `status` =1  AND  (servicecity LIKE "%,'.$cityname.'%"  OR  servicecity LIKE "%,全国%" ) 
              GROUP BY categoryid HAVING COUNT( categoryid)>=2  '  ;

//   echo ' sql --> ' . $sql ;

        $Model = new \Think\Model() ; // 实例化一个model对象 没有对应任何数据表
        $res = $Model->query( $sql );

//    print_r( $res ) ;   exit ;


        $hp['recommend'] = array() ;


        $prd = M("product") ;
        $goodsClass = new Goods();


        foreach( $res as $key=>$cate ){

            $pdata = $prd->field('id')->where( 'categoryid='.$cate['categoryid'].' AND `status`=1 AND (servicecity LIKE "%,'.$cityname.'%"  OR  servicecity LIKE "%,全国%" ) ' )->order('sortweight desc , modified_date desc')->limit('0,40')->select();
            if( $pdata ) {
                $ids = array() ;
                foreach( $pdata as $pp ){
                    $ids[] = $pp['id'] ;
                }
                $product = array() ;
                $where = array() ;
                $where['p.id'] = array('in' , $ids );  //print_r( $where ) ; exit ;
                $productList = $goodsClass->getProduct(0, 4,1,false, false,$where); // echo ' $goodsClass->getProduct --> ' ; var_dump($productList);die;

                foreach( $productList as $key=>$val ){

                    $product[] = array(

                        'id'=> $val['id'] ,
                        'title'=> $val['guanjianame']  ,
                        'a_img'=> $val['facepic']  ,
                        'a_type'=>  4 ,
                        'a_id'  => $val['id']   ,
                        'a_txt' => $val['name']  ,
                        'price' => $val['price']  ,

                    ) ;
                }


            }


            $hp['recommend'][ $cate['categoryid'] ] = array( 'cid'=>$cate['categoryid'] , 'cname'=>$cate['categoryname'] ,'product'=> $product  ) ;


        }
        //   print_r( $hp ) ;

        response('成功',1, $hp ) ;
    }


    //提取数据
    public function getdata( $cityname , $savetype ,$num  ){

        $nowtime = date('Y-m-d H:i:s') ; //当前时间

        $citywhere = ' AND  ((city_type=1 AND (city_rang     LIKE "%,'.$cityname.'%"      OR  city_rang LIKE "%,全国%" ) ) 
                     OR    (city_type=2 AND (city_rang NOT LIKE "%,'.$cityname.'%"  AND NOT city_rang LIKE "%,全国%" ) ) ) ' ;

        if( $savetype=='menu'){

            $where = 'join_id='.$num.' AND savetype="'.$savetype.'" AND is_show=1 AND del_flg=0 ' ;
            $where = $where.$citywhere ;
            //  $where = $where.' AND show_start <="'.$nowtime.'" AND show_end >="'.$nowtime.'" ' ;    //  echo '$where-->'.$where ;
            $order = 'frame asc, utime desc' ;
            $home = M("homepage_set");
            $res = $home->field('*')->table('homepage_set')->where($where)->order($order)->select(  ); //  false   //   echo ' sql--> '.$res ;

            //        $new = array() ;
            //        foreach( $res as $key=>$val ){
            //           $new[ $val['frame']  ] = $val ;
            //        }

            return $res ;
        }


        if( $savetype=='banner'){

            $nowtime = time() ;
            $where = ' ishow=1 AND isdelete=0 ' ;
            $where = $where.' AND  ((city_type=1 AND (servicecity LIKE "%,'.$cityname.'%"      OR servicecity LIKE "%,全国%"   )  ) 
                          OR    (city_type=2 AND (servicecity NOT LIKE "%,'.$cityname.'%"  AND NOT servicecity LIKE "%,全国%"   ) ) ) ' ;
            $where = $where.' AND showstarttime <="'.$nowtime.'" AND showendtime >="'.$nowtime.'" ' ;    //  echo '$where-->'.$where ;
            $order = 'frame asc, modified_date desc' ;
            $home = M("banner");

            $res = $home->field('*')->table('banner')->where($where)->order($order)->limit('0,'.$num)->select(); // ->group('frame')false    // echo ' sql--> '.$res ;

            return $res ;
        }


        if( $savetype=='diy' ){
            $where = ' savetype="'.$savetype.'" AND is_show=1 AND del_flg=0 ' ;
            $where = $where.$citywhere ;
            $where = $where.' AND show_start <="'.$nowtime.'" AND show_end >="'.$nowtime.'" ' ;    //  echo '$where-->'.$where ;
            $order = 'frame asc, utime desc' ;
            $home = M("homepage_set");
            $res = $home->field('*')->where($where)->order($order)->limit('0,'.$num)->select(); // false    // echo ' sql--> '.$res ;
            return $res ;
        }


        if( $savetype=='hot' ){
            $where = ' savetype="'.$savetype.'" AND is_show=1 AND del_flg=0 ' ;
            $where = $where.$citywhere ;
            $where = $where.' AND show_start <="'.$nowtime.'" AND show_end >="'.$nowtime.'" ' ;    //  echo '$where-->'.$where ;
            $order = 'frame asc, utime desc' ; //
            $home = M("homepage_set");
            $res = $home->field('*')->where($where)->order($order)->limit('0,'.$num)->select(); //false echo ' hot sql--> '.$res ;
            return $res ;
        }


        if( $savetype=='recommend' ){
            //   SELECT sex,COUNT(sex) FROM employee GROUP BY sex HAVING COUNT(sex)>=3;
            //   $order = 'frame asc, utime desc' ;
            /*
               $sql = 'SELECT * FROM `homepage_set`
                       WHERE (  savetype="recommend" AND is_show=1 AND show_start <="'.$nowtime.'" AND show_end >="'.$nowtime.'"  )
                       GROUP BY a_id ,frame  HAVING COUNT(a_id)>=2
                       ORDER BY a_id ,frame ASC, utime DESC' ;
             */
            $where = ' savetype="'.$savetype.'" AND is_show=1 AND del_flg=0 ' ;
            $where = $where.$citywhere ;
            $where = $where.' AND show_start <="'.$nowtime.'" AND show_end >="'.$nowtime.'" ' ;    //  echo '$where-->'.$where ;

            $sql = ' SELECT categoryid ,categoryname FROM  product 
              WHERE  categoryid IN ( SELECT a_id FROM homepage_set 
              WHERE  '.$where.'  GROUP BY a_id
               )  AND  `status` =1  
              GROUP BY categoryid   HAVING COUNT( categoryid)>=2  '  ;

            $Model = new \Think\Model() ; // 实例化一个model对象 没有对应任何数据表
            $res = $Model->query( $sql );

            //     $home = M("homepage_set");
            //      $res = $home->field('*')->table('homepage_set')->group('frame')->where($where)->order($order)->limit('0,'.$num)->select(false);   echo ' sql--> '.$res ;
            return $res ;
        }

        if( $savetype=='ganjia' ){ //管家优选

            $where = ' savetype="'.$savetype.'" AND is_show=1 AND del_flg=0 ' ;
            $where = $where.$citywhere ;
            $where = $where.' AND show_start <="'.$nowtime.'" AND show_end >="'.$nowtime.'" ' ;    //  echo '$where-->'.$where ;
            $order = 'frame asc, utime desc' ;
            $home = M("homepage_set");


            $sql = 'select homepage_set.*,gj.avatarurl from homepage_set inner join guanjia gj on homepage_set.join_id = gj.id 
         where '.$where.' order by '.$order.' limit 0,'.$num ;

            $res = $home->query( $sql ) ;

            return $res ;

        }

        $where = ' savetype="'.$savetype.'" AND is_show=1  AND del_flg=0 ' ;
        $where = $where.$citywhere ;
        $where = $where.' AND show_start <="'.$nowtime.'" AND show_end >="'.$nowtime.'" ' ;    //  echo '$where-->'.$where ;
        $order = 'frame asc, utime desc' ;
        $home = M("homepage_set");
        $res = $home->field('*')->table('homepage_set')->where($where)->order($order)->limit('0,'.$num)->select(); //->group('frame') false    // echo ' sql--> '.$res ;
        return $res ;
    }


    //顶部
    public function addTopBanner(){
        $res = self::editdata( );
        $this->assign('res', $res);
//      dump($res);
        $this->display();
    }

    //顶部列表
    public function top(){
        self::getlist( 'top' ) ;
    }
//-----------------------------
    //定制化
    public function addDiy(){
        $res = self::editdata( );
        $this->assign('res', $res);
//      dump($res);
        $this->display();

    }
    //----------------------------
    //定制化列表
    public function diy()
    {
        self::getlist( 'diy' ) ;

    }

//-----------------前台菜单列表
    public function menu(){
        self::getlist( 'menu' ) ;
    }


    //热门
    public function addHot(){
        $res = self::editdata( );
        $this->assign('res', $res);
//      dump($res);
        $this->display();
    }

    //热门列表
    public function hot(){
        self::getlist( 'hot' ) ;
    }
//-----------------------------
    //管家
    public function addGuanjia(){
        $res = self::editdata( );
        $this->assign('res', $res);
//      dump($res);
        $this->display();
    }

    //管家列表
    public function ganjia(){
        self::getlist( 'ganjia' ) ;
    }
//-----------------------------
    //推荐
    public function addRecommend(){
        $res = self::editdata( );
        $this->assign('res', $res);
        $this->display();
    }


    //------------------------
    //前台菜单
    public function addMenu(){
        $id = I('get.id',0);
        $data = $this->editMenuData($id);
        $childsinfo = $data['childsinfo'];
        if ($childsinfo) {
            foreach ($childsinfo as $key =>$row) {
                $city_range = $row['city_rang'];
                $city_range = explode('|',$city_range);
                $cityname = '';
                foreach ($city_range as $row1) {
                    $temp = explode(',',$row1);
                    $cityname .= $temp[1].',';
                }
                $cityname = substr($cityname,0,strlen($cityname)-1);

                if ($row['city_type'] == 1) {
                    $city_typename = '白名单';
                } else {
                    $city_typename = '黑名单';
                }
                $childsinfo[$key]['city_typename'] = $city_typename;
                $childsinfo[$key]['city_name'] = $city_typename.':'.$cityname;
                $childsinfo[$key]['cityname'] = $cityname;
                if ($row['a_type'] == 4) {
                    $childsinfo[$key]['a_name'] = '产品';
                }  elseif ($row['a_type'] == 3) {
                    $childsinfo[$key]['a_name'] = '管家';
                } elseif ($row['a_type'] == 2) {
                    $childsinfo[$key]['a_name'] = '二级分类';
                } else {
                    $childsinfo[$key]['a_name'] = '链接';
                }
            }
        }
//        dump($data['res']);
        $this->assign('res',$data['res']);
        $this->assign('childsinfo',$childsinfo);
        $this->display();
    }

    private function editMenuData($id)
    {
        $where['id'] = $id;
        $home = M("homepage_set");
        $res  = $home->where($where)->find();
        $childsinfo = '';
        if ($res) {
            $childsinfo = $home->where(['join_id'=>$id,'del_flg'=>0,'savetype'=>'menu'])->order('frame asc,id desc')->select();

            if( $res ){
                $xres = array(  '0'=>$res ) ;
                $xres = self::upname( 'menu' ,$xres ) ;  //  根据id更新name ( 二级分类  管家  产口)
                $res = $xres[0] ;
                $childsinfo = self::upname( 'menu' ,$childsinfo ) ;
            }
        }
        $res['res'] = $res;
        $res['childsinfo'] = $childsinfo;
        return $res ;
    }

    //管家列表
    public function recommend(){
        self::getlist( 'recommend' ) ;
    }
//-----------------------------

    //编辑数据
    public function editdata( $id=0 ){

        if( $id==0 ) $id = intval(  I('get.id',0) );   //echo ' id--->'.$id ;

        if( $id == 0 ){
            return array( 'id'=>0) ;
        }

        $where = 'id='.$id ;
        $home = M("homepage_set");
        $res  = $home->where($where)->find();


        if( $res ){
            $xres = array(  '0'=>$res ) ;
            $xres = self::upname( $res['savetype'] ,$xres ) ;  //  根据id更新name ( 二级分类  管家  产口)
            $res = $xres[0] ;
        }
        return $res ;
    }

    //提取列表数据
    public function getlist( $savetype ){

        $limit = 20 ;

        //   $savetype = I('get.savetype','') ;
        $typearr  = array( 'top' ,'banner' ,'diy' ,'hot' ,'ganjia' ,'recommend','menu') ;

        if( ! in_array( $savetype ,$typearr) ){
            response("存储类型错误！");
        }
        $where  = 'savetype="'.$savetype.'"'  ;

        $p = I('get.p',1) ;

        $findtype = I('get.findtype','') ;
        $keyw = trim( I('get.keyw'    ,'') );
        $is_show = I('get.is_show' ,'') ;
        $time_s = I('get.time_s'  ,'') ;



        if( ! empty($findtype) && $keyw !='' ){


            if( $savetype=='top' ){
                if( $findtype==1){ //运营位名称
                    $where = $where.' AND title like "%'.$keyw.'%" ' ;
                }
                if( $findtype==2){ //运营位id
                    $where = $where.' AND id='.intval($keyw).' ' ;
                }
            }
            if( $savetype=='diy' ){
                if( $findtype==1){ //名称
                    $where = $where.' AND title LIKE "%'.$keyw.'%" ' ;
                }
                if( $findtype==2){ //ID
                    $where = $where.' AND id='.intval($keyw) .' ' ;
                }
                if( $findtype==3){ //城市
                    $where = $where.' AND  ((city_type=1 AND city_rang LIKE "%'.$keyw.'%" ) OR ( city_type=2 AND city_rang NOT LIKE "%'.$keyw.'%" )) ' ;
                }
            }
            if( $savetype=='hot' ){
                if( $findtype==1){ //产品名称
                    $where = $where.' AND a_txt like "%'.$keyw.'%" ' ;
                }
                if( $findtype==2){ //产品id
                    $where = $where.' AND a_id='.intval($keyw).' ' ;
                }
            }
            if( $savetype=='ganjia' ){
                if( $findtype==1){ //城市
                    $where = $where.' AND  ((city_type=1 AND city_rang LIKE "%'.$keyw.'%" ) OR ( city_type=2 AND city_rang NOT LIKE "%'.$keyw.'%" )) ' ;
                }
                if( $findtype==2){ //id
                    $where = $where.' AND id='.intval($keyw).' ' ;
                }
                if( $findtype==3){ //管家
                    $where = $where.' AND join_name like "%'.$keyw.'%" ' ;
                }
                if( $findtype==4){ //活动
                    $where = $where.' AND title like "%'.$keyw.'%" ' ;
                }
            }

            if( $savetype=='recommend' ){
                if( $findtype==1){ //城市
                    $where = $where.' AND  ((city_type=1 AND city_rang LIKE "%'.$keyw.'%" ) OR ( city_type=2 AND city_rang NOT LIKE "%'.$keyw.'%" )) ' ;
                }
                if( $findtype==2){ //名称
                    $where = $where.' AND title like "%'.$keyw.'%" ' ;
                }
                if( $findtype==3 ){ //id
                    $where = $where.' AND id='.intval($keyw).' ' ;
                }
            }
        }

        if($savetype=='menu'){
            $where = $where.' AND join_id=0 AND del_flg=0 ';
        }
        if( ! empty($is_show) ){
            $where = $where.' AND is_show='.intval($is_show).' ' ;
        }
        if( ! empty($time_s) ){
            $where = $where.' AND show_start <="'.$time_s.'" AND show_end >="'.$time_s.'" ' ;
        }
//   echo ' $where --> '. $where ;

        $home  = M("homepage_set");
        $count = $home->where($where)->count();
        $Page = new Page($count, $limit);
        $Page->nowPageage = $p;
        //================
        $home = M("homepage_set as h");
        $home->field('h.*') ;
        $home->where($where);
        $home->order('h.id desc');
        $home->limit($Page->firstRow . ',' . $Page->listRows);
        $res = $home->select();

        $res = self::upname( $savetype ,$res ) ; //根据id更新name ( 二级分类  管家  产口)
        //--------------------------------
        $first= $Page->firstRow+1;
        $rest =($count-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($count-$Page->listRows*($p-1));
        $end  = $first + $rest-1;
        $all  = $first.'-'.$end;

        if ($savetype == 'menu') {
            foreach ($res as $key=>$row) {
                $id= $row['id'];
                $childtitle = M('homepage_set')->where(['join_id'=>$id,'savetype'=>$savetype,'del_flg'=>0])->getField('title',true);
                $childtitle = implode('、',$childtitle);
                $res[$key]['twolevel'] = $childtitle;
            }
        }

        $this->assign("result"  ,$res);//列表数据
        //分页信息
        $this->assign("count"   ,$count);
        $this->assign("type"    ,$type );
        $this->assign('page'    ,$Page->show());
        $this->assign('nowPage' ,$p    );
        $this->assign('totalPages',$Page->totalPages);
        $this->assign('all'       ,$all);

        //查询参数
        $this->assign('findtype', $findtype);
        $this->assign('keyw'    , $keyw    );
        $this->assign('is_show' , $is_show);
        $this->assign('time_s'  , $time_s);
//     dump($res);
        $this->display( $savetype );

    }


    //根据id更新name ( 二级分类  管家  产口)
    public function upname( $savetype ,$res  )
    {

        if( $savetype=='top' ){
            foreach( $res as $k=>$v ){
                $res[$k]['a_txt'] = self::get_ldy( $v['a_type'] ,$v['a_id'] ,$v['a_txt'] ) ;
            }
        }

        if( $savetype=='diy' ){
            foreach( $res as $k=>$v ){
                $res[$k]['a_txt'] = self::get_ldy( $v['a_type'] ,$v['a_id'] ,$v['a_txt'] ) ;
                $res[$k]['b_txt'] = self::get_ldy( $v['b_type'] ,$v['b_id'] ,$v['b_txt'] ) ;
                $res[$k]['c_txt'] = self::get_ldy( $v['c_type'] ,$v['c_id'] ,$v['c_txt'] ) ;
            }
        }

        if( $savetype=='hot' ){
            foreach( $res as $k=>$v ){
                $res[$k]['a_txt'] = self::get_ldy( 4 ,$v['a_id'] ,$v['a_txt']  ) ;
            }
        }

        if( $savetype=='ganjia' ){
            foreach( $res as $k=>$v ){
                $res[$k]['join_name'] = self::get_ldy( 3 ,$v['join_id'] ) ;
                $res[$k]['a_txt']     = self::get_ldy( 4 ,$v['a_id']    ) ;
            }
        }

        if( $savetype=='recommend' ){
            foreach( $res as $k=>$v ){
                $res[$k]['a_txt'] = self::get_ldy( $v['a_type'] ,$v['a_id'] ,$v['a_txt'] ) ;
            }
        }

        if ($savetype == 'menu') {
            foreach ($res as $k=>$v) {
                $res[$k]['a_txt'] = self::get_ldy( $v['a_type'] ,$v['a_id'] ,$v['a_txt'] ) ;
            }
        }
        return  $res  ;
    }


    //落地页 类型
    public function get_ldy( $type ,$id ,$txt='' ,$opt='')
    {
        if( $type==1 ){ //链接
            return $txt ;
        }

        if( $type==2 ){ //二级分类
            $db = M("category");
            $get = $db->field('name')->where('id='.$id)->find() ;
            if( $get ){
                if( $opt=='all' ) return  $get ;
                return $get['name'] ;
            }else{
                return '' ;
            }
        }
        if( $type==3 ){ //管家
            $db = M("guanjia");
            $get = $db->field('guanjianame')->where('id='.$id)->find() ;
            if( $get ){
                if( $opt=='all' ) return  $get ;
                return $get['guanjianame'] ;
            }else{
                return '' ;
            }
        }
        if( $type==4 ){ //产品
            $db = M("product");
            $get = $db->field('name')->where('id='.$id)->find() ;
            if( $get ){
                if( $opt=='all' ) return  $get ;
                return $get['name'] ;
            }else{
                return '' ;
            }
        }

    }



    //提交存储
    public function save_submit()
    {

        $savetype = I('post.savetype','') ; // '保存类型：top ,banner ,diy ,hot ,ganjia ,recommend',
        $typearr = array( 'top' ,'banner' ,'diy' ,'hot' ,'ganjia' ,'recommend','menu') ;

        if( ! in_array( $savetype ,$typearr) ){
            response("存储类型错误！");
        }

        $id= I('post.id' , 0 ) ;
        if( empty($id) ) $id=0 ;

        $title     = I('post.title'     ,'') ; // '标题',
        $city_rang = I('post.city_rang' ,'') ; //  '城市范围',
        $city_type = I('post.city_type' ,'') ; // 城市类型：白/黑名单  1-白名单 2-黑名单',
//       echo $city_type;die;
        $show_start= I('post.show_start','') ; // 显示开始时间',
        $show_end  = I('post.show_end'  ,'') ; // 显示结束时间',
        $frame     = I('post.frame'    , '') ; // 帧数',
        $is_show   = I('post.is_show'  , '') ; // 是否显示 1-显示  2-不显示',

        $join_id   = I('post.join_id'  , '') ; // 关联id  （管家优先页面  ）（热门产品）',
        $join_name = I('post.join_name', '') ; // 关联名称（管家优先页面  ）（热门产品）',

        $a_type = I('post.a_type', '') ; //a类型（落地页）', 4产品  3管家  2二级分类  1 链接
        $a_img  = I('post.a_img' , '') ; //a图片url',
        $a_id   = I('post.a_id'  , '') ; //a关联id(落地页)产品或管家或二级分类',
        $a_txt  = I('post.a_txt' , '') ; //a关联文字(落地页)产品或管家或二级分类 名称',

        $b_type = I('post.b_type', '') ; //
        $b_img  = I('post.b_img' , '') ; //
        $b_id   = I('post.b_id'  , '') ; //
        $b_txt  = I('post.b_txt' , '') ; //

        $c_type = I('post.c_type', '') ; //
        $c_img  = I('post.c_img' , '') ; //
        $c_id   = I('post.c_id'  , '') ; //
        $c_txt  = I('post.c_txt' , '') ; //

        $list = $_POST['list'];  //二级菜单  json

        //--------------------------------------
        $sv = array() ;
        $sv['savetype'] = $savetype ; //

        if( $savetype=='top' ){   //顶部

            $sv['title']  = $title ; //名称',

            $sv['a_type'] = $a_type ; //a类型（落地页）',
            $sv['a_img']  = $a_img  ; // icon
            $sv['a_id']   = $a_id   ; // 'a关联id(落地页)产品或管家或二级分类',
            $sv['a_txt']  = $a_txt  ; //'a关联文字(落地页)产品或管家或二级分类 名称',

            $sv['city_rang'] = $city_rang ; //城市范围',
            $sv['city_type'] = $city_type ; //城市类型：白/黑名单  1-白名单 2-黑名单',

            $sv['show_start']= $show_start ; //显示开始时间',
            $sv['show_end']  = $show_end ; //显示结束时间',
            $sv['frame']     = $frame ; //帧数',
            $sv['is_show']   = $is_show ; //是否显示 1-显示 2-不显示',
        }

        if( $savetype=='diy' ){  //定制化

            $sv['title']     = $title ; //标题',
            $sv['city_rang'] = $city_rang ; //城市范围',
            $sv['city_type'] = $city_type ; //城市类型：白/黑名单  1-白名单 2-黑名单',
            $sv['show_start']= $show_start ; //显示开始时间',
            $sv['show_end']  = $show_end ; //显示结束时间',
            //    $sv['frame']     = $frame ; //帧数',
            $sv['is_show']   = $is_show ; //是否显示 1-显示 2-不显示',

            $sv['a_type'] = $a_type ; //a类型（落地页）',
            $sv['a_img']  = $a_img  ; //'a图片url',
            $sv['a_id']   = $a_id   ; // 'a关联id(落地页)产品或管家或二级分类',
            $sv['a_txt']  = $a_txt  ; //'a关联文字(落地页)产品或管家或二级分类 名称',

            $sv['b_type'] = $b_type ; //a类型（落地页）',
            $sv['b_img']  = $b_img  ; //'a图片url',
            $sv['b_id']   = $b_id   ; //'a关联id(落地页)产品或管家或二级分类',
            $sv['b_txt']  = $b_txt  ; //'a关联文字(落地页)产品或管家或二级分类 名称',

            $sv['c_type'] = $c_type ; //a类型（落地页）',
            $sv['c_img']  = $c_img  ; //'a图片url',
            $sv['c_id']   = $c_id   ; // 'a关联id(落地页)产品或管家或二级分类',
            $sv['c_txt']  = $c_txt  ; //'a关联文字(落地页)产品或管家或二级分类 名称',
        }

        if( $savetype=='hot' ){ //热门产品

            $sv['a_id']  =   $a_id   ; // 关联id  （管家优先页面  ）（热门产品）',
            $sv['a_txt'] =   $a_txt  ; // 关联名称（管家优先页面  ）（热门产品）',
            $sv['city_rang'] = $city_rang ; //城市范围',
            $sv['city_type'] = $city_type ; //城市类型：白/黑名单  1-白名单 2-黑名单',
            $sv['a_type']= 4 ; // 类型为产品,

            $sv['show_start']= $show_start ; //显示开始时间',
            $sv['show_end']  = $show_end ; //显示结束时间',

            $sv['frame']     = $frame ; //帧数',
            $sv['is_show']   = $is_show ; //是否显示 1-显示 2-不显示',
        }


        if( $savetype=='ganjia' ){ //管家优先

            $sv['join_id']   =   $join_id    ; // 关联id  （管家优先页面  ）（热门产品）',
            $sv['join_name'] =   $join_name  ; // 关联名称（管家优先页面  ）（热门产品）',

            $sv['title']     = $title ; //推荐理由',

            $sv['a_img']  = $a_img  ; //'a图片url',
            $sv['a_id']   = $a_id   ; // 'a关联id(落地页)产品或管家或二级分类',
            $sv['a_txt']  = $a_txt  ; //'a关联文字(落地页)产品或管家或二级分类 名称',

            $sv['city_rang'] = $city_rang ; //城市范围',
            $sv['city_type'] = $city_type ; //城市类型：白/黑名单  1-白名单 2-黑名单',
            $sv['show_start']= $show_start ; //显示开始时间',
            $sv['show_end']  = $show_end ; //显示结束时间',
            $sv['frame']     = $frame ; //帧数',
            $sv['is_show']   = $is_show ; //是否显示 1-显示 2-不显示',
        }

        if( $savetype=='recommend' ){  //推荐产品

            $sv['title']  = $title ; //名称',

            $sv['a_type'] = $a_type ; //a类型（落地页）',

            $sv['a_id']   = $a_id   ; // 'a关联id(落地页)产品或管家或二级分类',
            $sv['a_txt']  = $a_txt  ; //'a关联文字(落地页)产品或管家或二级分类 名称',

            $sv['city_rang'] = $city_rang ; //城市范围',
            $sv['city_type'] = $city_type ; //城市类型：白/黑名单  1-白名单 2-黑名单',
            $sv['show_start']= $show_start ; //显示开始时间',
            $sv['show_end']  = $show_end ; //显示结束时间',
            $sv['frame']     = $frame ; //帧数',
            $sv['is_show']   = $is_show ; //是否显示 1-显示 2-不显示',
        }

        if ($savetype == 'menu') {

            $sv['title']  = $title ; //名称',

            $sv['city_rang'] = $city_rang ; //城市范围',
            $sv['city_type'] = $city_type ; //城市类型：白/黑名单  1-白名单 2-黑名单',

            $sv['frame']     = $frame ; //帧数',
            $sv['is_show']   = $is_show ; //是否显示 1-显示 2-不显示',
            $sv['utime'] = date('Y-m-d H:i:s') ;
            $sv['join_id'] = 0;
            $db = M( 'homepage_set' ); // 实例化对象
            $list = json_decode($list, true);
            if ($id) {
                $childsids = $db->where(['join_id'=>$id,'savetype'=>'menu'])->getField('id',true);
                $hasids = array_column($list,'id');
                $delids = array_diff($childsids,$hasids);
                if (count($delids)) {
                    $db->where(['id'=>['in',$delids]])->save(['del_flg'=>1,'utime'=>date('Y-m-d H:i:s')]);
                }
                $addData = [];
                foreach ($list as $key=>$row) {
                    $tempid = $row['id'];
                    unset($row['id']);
                    $row['utime'] = date('Y-m-d H:i:s');
                    $row['join_id'] = $id;
                    $row['savetype'] = $savetype;
                    if ($tempid) {
                        $db->where(['id'=>$tempid])->save($row);
                    } else {
                        $addData[] = $row;
                    }
                }
                if ($addData) {
                    $db->addAll($addData);
                }
                // response("添加成功xx ",1 );
            } else {
                if (!$list) response('二级菜单不能为空');
                $pid = $db->add($sv);
                foreach ($list as $key =>$row) {
                    $list[$key]['savetype'] = $savetype;
                    $list[$key]['join_id'] = $pid;
                    $list[$key]['utime'] = date('Y-m-d H:i:s');
                    unset($list[$key]['id']);
                }
                $db->addAll($list);
                response("添加成功 ",1,$pid );
            }
        } else {


        }
        //---------------------------------------
        foreach( $sv as  $key=>$val){
            if( empty( $val ) && ( $key!='join_id' &&  $key!='b_id' && $key!='c_id' ) ){

                if(  ($key=='a_id' && $sv['a_type']==1)  || ($sv['a_type']==5 && ($key=='a_id' || $key=='a_txt' ) )  ){

                }else{
                    response("空值，请检查！".$key.'  val==>'.$val  );   exit;
                }

            }
        }
        //---------------------------------------
        $sv['utime'] = date('Y-m-d H:i:s') ;


        $where = 'id='.$id;
        $db = M( 'homepage_set' ); // 实例化对象
        $read = $db->where($where)->find() ;  //print_r( $read ) ; exit ;
        if( $read ){
            $db->where($where)->save($sv); // 根据条件保存修改的数据
            $newid = $read['id'] ;
        }else{
            $newid =  $db->add($sv);
        }

        response("保存成功",1 ,$newid );
    }

    public function delMenu()
    {
        $id = I('post.id',0);
        if (!$id) response('删除失败');
        $res = M('homepage_set')->where(['id'=>$id])->save(['del_flg'=>1]);
        $res ? response('删除成功',1) :response('删除失败');
    }

    //添加城市
    public function addcity(){
        /*
                         $city= I('get.city' ,'' ) ;
              if( empty( $city )) exit( 'city is empty!' ) ;

              $sv = array(
                  'city'=> $city ,
                         ) ;
                   $db = M( 'homepage');
              $newid =  $db->add($sv);

              if(  $newid  ) {
                  exit( '添加成功！' );
              }else{
                  exit( '添加失败！' );
              }
         */
    }
    /**
     * @breif 微信端获取管家产品列表
     * @param $guanjiaid
     */
    public function getproduct($guanjiaid = 0, $limit = 0, $nowpage =1, $isfiter = false, $ishome = false, $where = [] ,$where_status=true )
    {
        $productModel = M('product as p');
        if ($guanjiaid) {
            $where['p.guanjiaid'] = $guanjiaid;
        }
        $unshowProduct = C('UNSHOWPRODUCT');
        if (is_array($unshowProduct) && count($unshowProduct) && $isfiter) {
            $where['p.id'] = ['not in',$unshowProduct];
        }

        if( $where_status ) $where['p.status'] = 1 ;


        $where['s.isdelete'] = 0;

        if ($ishome) {                                            //首页列表查询
            $where['p.isrecommend'] = 1;
            $time = time();
            $where['p.showstarttime'] = ['elt',$time];
            $where['p.showendtime'] = ['egt',$time];
            if ($limit) {
                $productList = $productModel
                    ->field('p.id,j.guanjianame,j.avatarurl,j.title as guanjiafenlei,p.name,p.type,p.guanjiaid,p.facepic,serviceinfo,count(p.id) as onenum')
                    ->join('__GOODS__ as g ON p.id = g.productid')
                    ->join('__GUANJIA__ as j ON p.guanjiaid = j.id')
                    ->join('__SPEC__ as s ON g.id = s.goodsid')
                    ->where($where)
                    ->group('p.id')
                    ->having('sum(s.nums)>0')
                    ->order('p.weight desc,p.id desc')
                    ->page($nowpage)
                    ->limit($limit)
                    ->select();
            } else {
                $productList = $productModel
                    ->field('p.id,j.guanjianame,j.avatarurl,j.title as guanjiafenlei,p.name,p.type,p.guanjiaid,p.facepic,serviceinfo,count(p.id) as onenum')
                    ->join('__GOODS__ as g ON p.id = g.productid')
                    ->join('__GUANJIA__ as j ON p.guanjiaid = j.id')
                    ->join('__SPEC__ as s ON g.id = s.goodsid')
                    ->where($where)
                    ->group('p.id')
                    ->having('sum(s.nums)>0')
                    ->order('p.weight desc,p.id desc')
                    ->select();

            }

        } else {
            if ($limit) {
                $productList = $productModel
                    ->field('p.id,j.guanjianame,j.avatarurl,j.title as guanjiafenlei,p.name,p.type,p.guanjiaid,p.facepic,serviceinfo,count(p.id) as onenum')
                    ->join('__GOODS__ as g ON p.id = g.productid')
                    ->join('__GUANJIA__ as j ON p.guanjiaid = j.id')
                    ->join('__SPEC__ as s ON g.id = s.goodsid')
                    ->where($where)
                    ->group('p.id')
                    ->order('p.sortweight desc,p.id desc')
                    ->page($nowpage)
                    ->limit($limit)
                    ->select();
            } else {
                $productList = $productModel
                    ->field('p.id,j.guanjianame,j.avatarurl,j.title as guanjiafenlei,p.name,p.type,p.guanjiaid,p.facepic,serviceinfo,count(p.id) as onenum')
                    ->join('__GOODS__ as g ON p.id = g.productid')
                    ->join('__GUANJIA__ as j ON p.guanjiaid = j.id')
                    ->join('__SPEC__ as s ON g.id = s.goodsid')
                    ->where($where)
                    ->group('p.id')
                    ->order('p.sortweight desc,p.id desc')
                    ->select();
            }
        }
        $productids = array_column($productList,'id');
        if (!count($productids)) {
            return [];
        }
        $goodsModel = M('goods as g');
        unset($where1);
        $pricelist = $goodsModel
            ->field('g.productid,s.price,s.nums,s.status,s.orginprice')
            ->join('__SPEC__ as s ON g.id = s.goodsid')
            ->where(['s.isdelete'=>0])
            ->select();
        $tempList = [];
        foreach ($pricelist as $key => $row) {
            $productid = $row['productid'];
            $price = $row['price'];
            $orginprice = $row['orginprice'];
            if (!isset($tempList[$productid]['minorginprice'])) {
                $tempList[$productid]['minorginprice'] = $orginprice;
                $tempList[$productid]['orginunqiue'] = 1;
            } else {
                if ($orginprice >0) {
                    if ($tempList[$productid]['minorginprice'] != $orginprice && $tempList[$productid]['minorginprice'] >0) {
                        $tempList[$productid]['orginunqiue'] = 0;
                    }
                    if ($tempList[$productid]['minorginprice'] == 0 || $tempList[$productid]['minorginprice'] > $orginprice) {
                        $tempList[$productid]['minorginprice'] = $orginprice;
                    }
                }
            }
            if (!isset($tempList[$productid]['minprice']) ) {
                $tempList[$productid]['minprice'] = $price;
                $tempList[$productid]['unqiue'] = 1;
            } else {
                if ($tempList[$productid]['minprice'] != $price) {
                    $tempList[$productid]['unqiue'] = 0;
                }
                if ($tempList[$productid]['minprice'] > $price) {
                    $tempList[$productid]['minprice'] = $price;
                }
            }

            if ($row['nums'] > 0 && $row['status'] == 1) {
                if (!isset($tempList[$productid]['orginprice'])) {
                    $tempList[$productid]['orginprice'] = $orginprice;
                    $tempList[$productid]['isorginunqiue'] = 1;
                } else {
                    if ($orginprice >0) {
                        if ($tempList[$productid]['orginprice'] != $orginprice && $tempList[$productid]['orginprice'] >0) {
                            $tempList[$productid]['isorginunqiue'] = 0;
                        }
                        if ($tempList[$productid]['orginprice'] == 0 ||$tempList[$productid]['orginprice'] > $orginprice) {

                            $tempList[$productid]['orginprice'] = $orginprice;
                        }
                    }
                }
                if (!isset($tempList[$productid]['price'])) {
                    $tempList[$productid]['price'] = $price;
                    $tempList[$productid]['ispriceunqiue'] = 1;
                } else {
                    if ($price != $tempList[$productid]['price']) {
                        $tempList[$productid]['ispriceunqiue'] = 0;
                    }
                    if ($price < $tempList[$productid]['price']) {
                        $tempList[$productid]['price'] = $price;
                    }
                }

            }

        };
        foreach ($productList as $key=> $row) {
            $id = $row['id'];
            if (isset($tempList[$id]['price'])) {
                $productList[$key]['price'] = $tempList[$id]['price'];
                $productList[$key]['ispriceunqiue'] = $tempList[$id]['ispriceunqiue'];
            } else {
                $productList[$key]['price'] = $tempList[$id]['minprice'];
                $productList[$key]['ispriceunqiue'] = $tempList[$id]['unqiue'];
            }
            if (isset($tempList[$id]['orginprice'])) {
                $productList[$key]['orginprice'] = $tempList[$id]['orginprice'];
                $productList[$key]['isorginunqiue'] = $tempList[$id]['isorginunqiue'];
            } else {
                $productList[$key]['orginprice'] = $tempList[$id]['minorginprice'];
                $productList[$key]['isorginunqiue'] = $tempList[$id]['orginunqiue'];
            }
        }
        return $productList;
    }


    /**
     * 全国城市列表
     *      https://test.dongrich.cn/myWeb/Operation/OperationHomePage/citylist
     */
    public function citylist( $type='json' ,$kw='' )
    {
        $allcity = array(
            'A'=>array(
                "1" =>'鞍山', "2" =>'安庆', "3" =>'安阳', "4" =>'安康', "5" =>'安顺', "6" =>'阿克苏', "7" =>'阿坝州',"8" =>'阿拉善',
                "9" =>'阿里',"10" =>'阿拉尔',
                "11" =>'阿勒泰',"12" =>'澳门',),

            'B'=>array(
                "13" =>'北京',"14" =>'保定',"15" =>'本溪',"16" =>'包头',"17" =>'宝鸡',"18" =>'滨州',"19" =>'蚌埠',"20" =>'白城',
                "21" =>'北海',"22" =>'亳州',"23" =>'百色',"24" =>'白山',"25" =>'毕节',"26" =>'巴中',"27" =>'保山',
                "28" =>'巴彦淖尔',"29" =>'白银',"30" =>'巴音郭楞',"31" =>'博尔塔拉',"32" =>'白沙',"33" =>'保亭',),
            'C'=>array(
                "34" =>'重庆',"35" =>'成都',"36" =>'长沙',"37" =>'长春',"38" =>'常州',"39" =>'沧州',"40" =>'常德',"41" =>'承德',"42" =>'郴州',"43" =>'潮州',"44" =>'巢湖',"45" =>'赤峰',"46" =>'长治',"47" =>'滁州',"48" =>'朝阳',"49" =>'池州',"50" =>'楚雄',"51" =>'崇左',"52" =>'昌吉',"53" =>'澄迈',"54" =>'昌都',"55" =>'昌江',),
            'D'=>array(
                "56" =>'东莞',"57" =>'大连',"58" =>'大庆',"59" =>'东营',"60" =>'德州',"61" =>'大同',"62" =>'丹东',"63" =>'大理',"64" =>'德阳',"65" =>'达州',"66" =>'东方',"67" =>'儋州',"68" =>'大兴',"69" =>'安岭',"70" =>'定安',"71" =>'定西',"72" =>'德宏',"73" =>'迪庆',),
            'E'=>array(
                "74" =>'鄂尔多斯',"75" =>'鄂州',"76" =>'恩施州',),
            'F'=>array(
                "77" =>'福州',"78" =>'佛山',"79" =>'抚顺',"80" =>'阜阳',"81" =>'阜新',"82" =>'抚州',"83" =>'防城港',),
            'G'=>array(
                "84" =>'广州',"85" =>'贵阳',"86" =>'桂林',"87" =>'赣州',"88" =>'广安',"89" =>'贵港',"90" =>'广元',"91" =>'甘孜州',"92" =>'固原',"93" =>'甘南',"94" =>'果洛',),
            'H'=>array(
                "95" =>'杭州',"96" =>'合肥',"97" =>'哈尔滨',"98" =>'海口',"99" =>'惠州',"100" =>'呼和浩特',"101" =>'邯郸',"102" =>'淮安',"103" =>'荷泽',"104" =>'衡阳',"105" =>'湖州',"106" =>'衡水',"107" =>'淮南',"108" =>'黄石',"109" =>'汉中',"110" =>'淮北',"111" =>'怀化',"112" =>'鹤壁',"113" =>'河源',"114" =>'黄山',"115" =>'黄冈',"116" =>'葫芦岛',"117" =>'呼伦贝尔',"118" =>'河池',"119" =>'贺州',"120" =>'鹤岗',"121" =>'红河',"122" =>'黑河',"123" =>'海南',"124" =>'哈密',"125" =>'海东',"126" =>'海北',"127" =>'和田',"128" =>'海西',"129" =>'黄南',),
            'J'=>array(
                "130" =>'济南',"131" =>'金华',"132" =>'嘉兴',"133" =>'济宁',"134" =>'九江',"135" =>'江门',"136" =>'吉林',"137" =>'荆州',"138" =>'焦作',"139" =>'吉安',"140" =>'锦州',"141" =>'晋城',"142" =>'景德镇',"143" =>'晋中',"144" =>'揭阳',"145" =>'佳木斯',"146" =>'鸡西',"147" =>'荆门',"148" =>'济源',"149" =>'嘉峪关',"150" =>'酒泉',"151" =>'金昌',),
            'K'=>array(
                "152" =>'昆明',"153" =>'开封',"154" =>'喀什',"155" =>'克拉玛依',"156" =>'克孜勒苏柯尔克孜',),
            'L'=>array(
                "157" =>'洛阳',"158" =>'兰州',"159" =>'临沂',"160" =>'柳州',"161" =>'廊坊',"162" =>'聊城',"163" =>'连云港',"164" =>'临汾',"165" =>'莱芜',"166" =>'辽阳',"167" =>'娄底',"168" =>'泸州',"169" =>'丽水',"170" =>'龙岩',"171" =>'辽源',"172" =>'乐山',"173" =>'六安',"174" =>'漯河',"175" =>'丽江',"176" =>'六盘水',"177" =>'拉萨',"178" =>'吕梁',"179" =>'来宾',"180" =>'凉山州',"181" =>'临沧',"182" =>'陇南',"183" =>'临高县',"184" =>'临夏',"185" =>'林芝',"186" =>'陵水',"187" =>'乐东',),
            'M'=>array(
                "188" =>'绵阳',"189" =>'马鞍山',"190" =>'茂名',"191" =>'牡丹江',"192" =>'梅州',"193" =>'眉山',),
            'N'=>array(
                "194" =>'南京',"195" =>'宁波',"196" =>'南昌',"197" =>'南宁',"198" =>'南通',"199" =>'南充',"200" =>'南阳',"201" =>'南平',"202" =>'宁德',"203" =>'内江',"204" =>'怒江',"205" =>'那曲',),
            'P'=>array(
                "206" =>'莆田',"207" =>'平顶山',"208" =>'濮阳',"209" =>'盘锦',"210" =>'萍乡',"211" =>'攀枝花',"212" =>'普洱',"213" =>'平凉',),
            'Q'=>array(
                "214" =>'青岛',"215" =>'泉州',"216" =>'秦皇岛',"217" =>'齐齐哈尔',"218" =>'清远',"219" =>'衢州',"220" =>'曲靖',"221" =>'钦州',"222" =>'琼海',"223" =>'黔南',"224" =>'黔东南',"225" =>'潜江',"226" =>'七台河',"227" =>'庆阳',"228" =>'黔西南',"229" =>'琼中',),
            'R'=>array(
                "230" =>'日照',"231" =>'日喀则',),
            'S'=>array(
                "232" =>'上海',"233" =>'深圳',"234" =>'苏州',"235" =>'石家庄',"236" =>'沈阳',"237" =>'绍兴',"238" =>'汕头',"239" =>'三亚',"240" =>'十堰',"241" =>'商丘',"242" =>'四平',"243" =>'朔州',"244" =>'宿迁',"245" =>'上饶',"246" =>'韶关',"247" =>'邵阳',"248" =>'宿州',"249" =>'三门峡',"250" =>'三明',"251" =>'随州',"252" =>'松原',"253" =>'遂宁',"254" =>'绥化',"255" =>'汕尾',"256" =>'商洛',"257" =>'双鸭山',"258" =>'石河子',"259" =>'神农架',"260" =>'石嘴山',"261" =>'山南',"262" =>'三沙市',),
            'T'=>array(
                "263" =>'天津',"264" =>'太原',"265" =>'唐山',"266" =>'泰安',"267" =>'台州',"268" =>'泰州',"269" =>'铁岭',"270" =>'铜陵',"271" =>'通辽',"272" =>'通化',"273" =>'天水',"274" =>'铜川',"275" =>'铜仁',"276" =>'天门',"277" =>'屯昌',"278" =>'吐鲁番',"279" =>'塔城',"280" =>'图木舒克',),
            'W'=>array(
                "281" =>'武汉',"282" =>'无锡',"283" =>'温州',"284" =>'潍坊',"285" =>'威海',"286" =>'乌鲁木齐',"287" =>'芜湖',"288" =>'渭南',"289" =>'梧州',"290" =>'五家渠',"291" =>'文昌',"292" =>'乌兰察布',"293" =>'乌海',"294" =>'万宁',"295" =>'五指山',"296" =>'文山',"297" =>'武威',"298" =>'吴忠',),
            'X'=>array(
                "299" =>'西安',"300" =>'厦门',"301" =>'徐州',"302" =>'襄樊',"303" =>'邢台',"304" =>'湘潭',"305" =>'新乡',"306" =>'信阳',"307" =>'西宁',"308" =>'咸阳',"309" =>'许昌',"310" =>'忻州',"311" =>'新余',"312" =>'孝感',"313" =>'咸宁',"314" =>'宣城',"315" =>'仙桃',"316" =>'湘西',"317" =>'西双版纳',"318" =>'兴安',"319" =>'锡林郭勒',"320" =>'香港',),
            'Y'=>array(
                "321" =>'烟台',"322" =>'扬州',"323" =>'盐城',"324" =>'宜昌',"325" =>'银川',"326" =>'岳阳',"327" =>'宜宾',"328" =>'营口',"329" =>'延边',"330" =>'榆林',"331" =>'永州',"332" =>'益阳',"333" =>'玉林',"334" =>'运城',"335" =>'延安',"336" =>'阳泉',"337" =>'宜春',"338" =>'阳江',"339" =>'玉溪',"340" =>'云浮',"341" =>'鹰潭',"342" =>'伊犁',"343" =>'雅安',"344" =>'伊春',"345" =>'阳朔',"346" =>'玉树',),
            'Z'=>array(
                "347" =>'郑州',"348" =>'珠海',"349" =>'淄博',"350" =>'中山',"351" =>'镇江',"352" =>'张家口',"353" =>'株洲',"354" =>'湛江',"355" =>'漳州',"356" =>'枣庄',"357" =>'肇庆',"358" =>'舟山',"359" =>'驻马店',"360" =>'周口',"361" =>'遵义',"362" =>'张家界',"363" =>'自贡',"364" =>'资阳',"365" =>'昭通',"366" =>'中卫',"367" =>'张掖',)

        );


        $hotcity = array( '北京','上海','深圳','杭州' ) ;


        foreach ($allcity as $letter => $letterGroup) {
            $allcity[$letter] = array_values($allcity[$letter]);
        }
        $citylist = array( 'all'=>$allcity ,'hot'=>$hotcity  )  ;

        if( $type=='json' ) {
            response('成功',1, $citylist ) ;
        }else{

            if( $type=='find'  ){

                foreach( $allcity as $val ){

                    $key = array_search( $kw ,$val );

                    if( $key!==false ) return true ;

                }


                return false  ;
            }



            return $citylist ;
        }

    }



}