<?php


namespace app\service;


class RandomStringService
{
    /**
     * @param $len 随机字符长度
     * @param null $chars
     * @return string
     */
    public function getRandomString($len, $chars=null)
    {
        if (is_null($chars)) {
            $chars = "abcdopqDEFGMWXYZ01HIJKL2NOPQRV3rstuvwx4efklmn5ghij6STU789yzABC";
        }
        mt_srand(10000000*(double)microtime());
        for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $lc)];
        }
        return $str;
    }
}