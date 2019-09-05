<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/10/26
 * Time: 12:04
 */
namespace Server;
class ExcelOperation
{
    /**require Extension: php-mbstring, php-bcmath
     * @param string $excelFile
     * @param string $type
     * @return array
     */
    public function readExcel($excelFile = '', $type = '')
    {
        if ($excelFile) {
            if ($type == 'xlsx') {
                $reader = \PHPExcel_IOFactory::createReader('Excel2007'); // 读取 excel 文档
            } else if ($type == 'xls') {
                $reader = \PHPExcel_IOFactory::createReader('Excel5');
            } else if ($type == 'csv') {
                $reader = new \PHPExcel_Reader_CSV();
                $reader->setInputEncoding('GBK');
            } else {
                return false;
            }

            $PHPExcel = $reader->load($excelFile); // 文档名称
            $objWorksheet = $PHPExcel->getActiveSheet();
            $highestRow = $objWorksheet->getHighestRow(); // 取得总行数
            $highestColumn = $objWorksheet->getHighestColumn(); // 取得总列数
            $end_index = \PHPExcel_Cell::columnIndexFromString($highestColumn);
            //echo $highestRow.$highestColumn;
            // 一次读取一列
            $titles = [];
            $content = [];
            for ($row = 1; $row <= $highestRow; $row++) {
                for ($column = 0; $column < $end_index; $column++) {
                    $col_name = \PHPExcel_Cell::stringFromColumnIndex($column);//由列数反转列名(0->'A')
                    $value    = $objWorksheet->getCell($col_name . $row)->getValue();//转码
                    if ($row == 1) {
                        //获取表头
                        $titles[] = preg_replace('/\s/', '', $value);
                    } else {
                        if ($objWorksheet->getCell($col_name . $row)->getDataType() == \PHPExcel_Cell_DataType::TYPE_NUMERIC) {
                            //数字类型时,三位精度标准来比较该数是否在0,1之间,转换为百分数,保留一位小数
                            if (bccomp(floatval($value), floatval('1'), 3) == -1 && bccomp(floatval($value), floatval('0'), 3) == 1) {
                                $value = sprintf("%01.1f", $value * 100) . '%';
                            }
                        }
                        $content[$row-2][$column] = preg_replace('/\s/', '', $value);
                    }
                }
            }
            return ['title'=>$titles, 'content'=>$content];


        }
    }

    public function exportExcel($name = '', $data = '')
    {
        if (!$data) return false;
        if ($name) {
            $filename = $name.'.xls';
        } else {
            $filename = Date("YmdGis")+mt_rand(10000,99999);
        }
        $objPhpExcel = new \PHPExcel();
        for ($row = 0; $row < count($data); $row++) {
            for ($column = 0; $column < count($data[$row]); $column++) {
                $col_name = \PHPExcel_Cell::stringFromColumnIndex($column);//由列数反转列名(0->'A')
                $objPhpExcel->setActiveSheetIndex(0)->setCellValue($col_name . ($row + 1), $data[$row][$column].'');
            }
        }

        $objPhpExcel->setActiveSheetIndex(0);

        // Redirect output to a client’s web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.$filename.'');
        header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        $objWriter = \PHPExcel_IOFactory::createWriter($objPhpExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public function isExcelExt($type = '', $ext = '')
    {
        if ($type == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
            return 'xlsx';
        } elseif ($type == 'application/vnd.ms-excel' && $ext == 'csv') {
            return 'csv';
        } elseif ($type == 'application/vnd.ms-excel') {
            return 'xls';
        } else {
            return false;
        }
    }
}