<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/10/25
 * Time: 16:01
 */

namespace Operation\Model;
use Think\Model;


class AuctionAllowUserModel extends  Model
{
    protected $trueTableName = 'auction_allowuser';

    protected $autoCheckFields = true;
}