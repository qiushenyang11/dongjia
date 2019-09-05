<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/9/29
 * Time: 10:11
 * Token 生成器
 */

namespace Server;


class TokenCreat
{

    const KEYATTACHTOKENKEY="QXQYWDJFW123";

    const KEYATTACHDONGJIAKEY="";

    public function creatToken($phone,$timeStamp)
   {
        $GJAES=new GJAES();

        $text=$phone.$timeStamp;

        $rst=$GJAES->aes_encrypt($text,self::KEYATTACHTOKENKEY);

//        echo "rst is:".$rst;
//
//        echo "\n";

        return $rst;
   }
}