<?php

namespace BaoXian\Controller;

use Think\Controller;
use Org\Util\EasyWeChat;
use GuzzleHttp\Client;

class BaoxianShRenshouLicaishiController extends Controller {

    private $excel_api = 'http://101.124.73.24';

    public function index()
    {
        $this->display();
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSheet()
    {
        $shrenshou_controller = new BaoXianShRenshouController();
        $shrenshou_controller->setExcelApiAddr($this->excel_api);
        $shrenshou_controller->setRowCount(30);
        $shrenshou_controller->getSheet();
    }

    public function _empty()
    {
        $this->display('index');
    }

}