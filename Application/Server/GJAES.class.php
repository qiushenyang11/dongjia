<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/9/29
 * Time: 10:11
 * Token 生成器
 */

namespace Server;


class GJAES
{
    const SALT_LEN = 32;
    const ITERATIONS = 10000;
    const KEY_LENGTH = 16;
    const KEY_ALGO = "sha512";

    //AES，CBC加密
    function aes_encrypt($data, $password)
    {
        $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);//这个值tmd就是16
        $pad = $block - (strlen($data) % $block);
        $data .= str_repeat(chr($pad), $pad);
        //以上三行在Java中会由final函数自动处理

        $salt =$this->rand_str(self::SALT_LEN);
        $encrypted = mcrypt_encrypt(
            MCRYPT_RIJNDAEL_128,//128位，Java中为默认
            hash_pbkdf2(self::KEY_ALGO, $password, $salt, self::ITERATIONS, self::KEY_LENGTH, true),//处理key
            $data,
            MCRYPT_MODE_CBC,//模式
            str_repeat(chr(0), 16) //iv偏移量
        );
        return $salt.base64_encode($encrypted);
    }

    //解密
    function aes_decrypt($data, $password)
    {
        //分离salt和密文
        $salt = substr($data, 0, self::SALT_LEN);
        $data = substr($data, self::SALT_LEN);

        $decrypted=mcrypt_decrypt(MCRYPT_RIJNDAEL_128,
            hash_pbkdf2(self::KEY_ALGO, $password, $salt, self::ITERATIONS, self::KEY_LENGTH, true),
            base64_decode($data),
            MCRYPT_MODE_CBC,
            str_repeat(chr(0), 16));
        //以下三行在Java中final函数自动完成
        $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $pad = ord($decrypted[strlen($decrypted) - 1]);
        return substr($decrypted, 0, strlen($decrypted) - $pad);
    }

    //生成随机字符串
    function rand_str($length)
    {
        $s = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $ret = '';
        $count = strlen($s);
        for ($i=0; $i<$length; $i++) {
            $ret .= $s[mt_rand(0,$count - 1)];
        }
        return $ret;
    }

//
// 	function myPbkdf2 ($algorithm, $password, $salt, $count, $key_length, $raw_output)
// 	{
// 		$algorithm = strtolower($algorithm);
// 		//echo "algorithm is".$algorithm;
// 		//echo "<br/>";
// 		if(!in_array($algorithm, hash_algos(), true))
// 		{	//echo "aaaa";
// 			trigger_error('PBKDF2 ERROR: Invalid hash algorithm.', E_USER_ERROR);
// 			if($count <= 0 || $key_length <= 0)
// 				trigger_error('PBKDF2 ERROR: Invalid parameters.', E_USER_ERROR);
// 				$hash_length = strlen(hash($algorithm, "", true));
// 				$block_count = ceil($key_length / $hash_length);

// 				$output = "";
// 				for($i = 1; $i <= $block_count; $i++) {
// 					// $i encoded as 4 bytes, big endian.
// 					$last = $salt . pack("N", $i);
// 					// first iteration
// 					$last = $xorsum = hash_hmac($algorithm, $last, $password, true);
// 					// perform the other $count - 1 iterations
// 					for ($j = 1; $j < $count; $j++) {
// 						$xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
// 					}
// 					$output .= $xorsum;
// 				}

// 				if($raw_output)
// 					return substr($output, 0, $key_length);
// 		}
// 	    else
// 	    {

// 	    	return bin2hex(substr($output, 0, $key_length));
// 	    }
// 	}

}
