<?php

namespace BaoXian\Model;

use Think\Model;

abstract class BaoxianModel extends Model {

    protected $connection = array(
        'db_type'  => 'mysql',
        'db_user'  => 'woshinibaba',
        'db_pwd'   => 'Jianghong8888',
//        'db_host'  => 'jddb-cn-south-1-19671acb10f341b6.jcloud.com',
//        'db_port'  => '3306',
        'db_host'  => '125.94.45.229',
        'db_port'  => '7701',
        'db_name'  => 'dbbaoxian',
        'db_charset'=>'utf8'
    );

}