<?php

namespace BaoXian\Controller;

use Think\Controller;
use GuzzleHttp\Client;

class BaoXianCalculateController extends Controller {

    private $excel_api = 'http://127.0.0.1:9501';

    public function calculateBaoxian()
    {
        $baoxian_name = I('get.baoxian_name');
        if ($baoxian_name === 'chunxiang') {
            $this->calcChunxiang();
        }
        else if ($baoxian_name === 'taikang') {
            $this->calcTaikang();
        }
        else if ($baoxian_name === 'rongyao') {
            $this->calcRongyao();
        }
    }

    /**
     * 传世荣耀计算
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function calcRongyao()
    {
//        $baoe = I('get.baoe');
//        $age = I('get.age');

        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'http://httpbin.org',
            // You can set any number of default request options.
            'timeout'  => 30,
        ]);

        $res = $client->request('GET', $this->excel_api . '/rongyao/zhongsheng', [
            'query' => []
        ]);

        $res = (string)$res->getBody();
        $res = json_decode($res, true);
        $return_sheet = [];
        if (json_decode($res['state'], true) == 1) {
            $age_row_offset = 15;
            $start_col = 'B';
            $end_col = 'M';
            $yearcounts = [1, 3, 5, 10, 15, 20];
            $yearcounts_i = 0;
            foreach ($res['data'] as $index => $row) {
                for($curr_col = $start_col; $curr_col <= $end_col; $curr_col ++) {
                    $year_key = 'year' . $yearcounts[$yearcounts_i];
                    if (ord($curr_col) % 2 === 0) {
                        $return_sheet[$index + $age_row_offset][$year_key]['male'] = $row[$curr_col];
                    }
                    else {
                        $return_sheet[$index + $age_row_offset][$year_key]['female'] = $row[$curr_col];
                        $yearcounts_i ++;
                    }
                }
                $yearcounts_i = 0;
            }
            response('Success', 1, $return_sheet);
        }
        response('failed', 0, $res['msg']);
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function calcTaikang()
    {
        $baofei = I('get.baofei');
        $year_count = I('get.year_count');
        $age = I('get.age');
        $row = (int)$age + 5; // 表格的行数设定

        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'http://httpbin.org',
            // You can set any number of default request options.
            'timeout'  => 35,
        ]);

        $res = $client->request('GET', $this->excel_api . '/taikang/NianjinLiyi', [
            'query' => [
                'baofei' => (int)$baofei,
                'age' => $age,
                'year_count' => $year_count
            ]
        ]);

        $res = (string)$res->getBody();
        $res = json_decode($res, true);

        // 格式化数据，按年龄，行
        $cols = ['A', 'B', 'C', 'D', 'H', 'I', 'J'];

        $start_row = 6;
        $end_row = 105;

        $age_offset = 5;

        $output_table = [];

        $rt_startage = (int)$age + 1;
        for ($r = $start_row; $r < $end_row; $r ++) {
            foreach ($cols as $col) {
                if ($res['data'][$col . $r] === null) break;
                $output_table[$age + 1][$col] = $res['data'][$col . $r];
            }
            $age ++;
        }
        response('Success', 1, $output_table);
//        var_dump($res);

//        if (json_decode($res['state'], true) == 1) {
//            response('Success', 1, $res['data']);
//        }

    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function calcChunxiang()
    {
        $core_medical_insurance = I('get.core_medical_insurance');
        $ext_medical_insurance = I('get.ext_medical_insurance');
        $dental_care = I('get.dental_care');
        $peoples = I('get.people');

        $request_data = [];

        foreach ($peoples as $people) {

            if ($people['sex'] === 'male' || $people['sex'] === 'boy') {
                $sex = '男性';
            }
            else {
                $sex = '女性';
            }

            $row['age'] = (int)$people['age'];
            $row['sex'] = $sex;
            $request_data['insured_peoples'][] = $row;
        }

        $request_data['core_medical_insurance']['amount'] = $core_medical_insurance['value'];

        if ($ext_medical_insurance['value'] > 0) {
            $request_data['ext_medical_insurance']['amount'] = $ext_medical_insurance['value'];
            $request_data['ext_medical_insurance']['is_need'] = 'Y';
        }
        else {
            $request_data['ext_medical_insurance']['is_need'] = 'N';
            $request_data['ext_medical_insurance']['amount'] = 0;
        }

        if ($dental_care['value'] > 0) {
            $request_data['dental_care']['amount'] = $dental_care['value'];
            $request_data['dental_care']['is_need'] = 'Y';
        }
        else {
            $request_data['dental_care']['is_need'] = 'N';
            $request_data['dental_care']['amount'] = 0;
        }

        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'http://httpbin.org',
            // You can set any number of default request options.
            'timeout'  => 30,
        ]);

        $res = $client->request('GET', $this->excel_api . '/chunxiang/ww', [
            'query' => $request_data
        ]);

        $res = (string)$res->getBody();
        $res = json_decode($res, true);

        if (json_decode($res['state'], true) == 1) {
            response('Success', 1, $res['data']);
        }

        response('failed', 0, $res['msg']);

//        response()
//
//        echo $res->getBody();

//        var_dump($res->getBody());

        // 传给swoole接口

        // 醇享
        // chunxiang/ww
//        $test_data = [
//            'core_medical_insurance' => [
//                'amount' => '10000'
//            ],
//            'ext_medical_insurance' => [
//                'is_need' => 'Y',
//                'amount' => '0'
//            ],
//            'dental_care' => [
//                'is_need' => 'Y',
//                'amount' => '30000'
//            ],
//            'insured_peoples' => [
//                ['age' => '26', 'sex' => '男性']
//            ]
//        ];

    }

}