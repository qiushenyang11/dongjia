<?php
/**
 * Created by PhpStorm.
 * User:xiyou
 * Date: 2018/11/06
 * Time: 15:51
 * 产品管理:产品列表 添加产品
 *http://www.dservie.cn/myWeb/index.php/Operation/OperationProduct/
 */
namespace Operation\Controller;


use Think\Controller;


class OperationToExcelController extends OperationBaseController
{
    
    
    public function coupon2excel(){ //导出coupon优惠券数据
    
    
        $ds = I('ss','') ;
        $de = I('ee','') ;
        $loopid = I("loopid",59);
        $type = I('get.type',0);
        
        $ts = strtotime($ds) ; 
        
        if( !( is_int($ts) && $ts > 0) ){
              exit( '开始日期格式不正确！' ) ;
        }
        
        
        $te = strtotime($de) ; 
        
        if( !( is_int($te) && $te > 0) ){
            
          exit( '结束日期格式不正确！' ) ;
        }
       
        
        
        
        $where['p.updatetime'] =  array( array('egt',$ds) ,array('elt',$de) )  ;

        $where['p.jdaccount'] = ['neq',''];

        $where['p.loopid'] = $loopid;

        $productMdoel = M("coupon as p");

         $productMdoel->field('distinct(p.jdaccount)') ;
        //    ->join('goods g ON p.id = g.productid','left')
        //    ->join('spec s ON g.id = s.goodsid','left')
          
                 $productMdoel->where( $where ) ; 
                 $productMdoel->order('p.updatetime asc') ;
          $res = $productMdoel->select();

       //   echo $res ; exit ;


        //  echo ' getLastSql--> '.$productMdoel->getLastSql();  exit ;

        //  var_dump( $res ) ; exit ;

        if( empty( $res ) ) exit( '没有找到数据' );
        
        $excelData = $res;
        
        $arr=["jdpin"];
        
             array_unshift( $excelData , $arr );
        
        $this->excelExport( $excelData ,'优惠券数据导出('.$ds.'至'.$de.')' ); 

        echo '导出优惠券数据，完成！' ;
    }    

    public function excelExport($data=array(),$filename="默认列表"){
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
  
}