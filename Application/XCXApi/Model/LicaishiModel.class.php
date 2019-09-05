<?php

namespace XCXApi\Model;
use Think\Model;

class LicaishiModel extends Model {


    /**
     * 获取理财师下属所有客户
     * @param $licaishi_jdpin
     */
    public function getLicaishiKehu($licaishi_jdpin) {
        $kehuModel = M('licaishikehu');
        return $kehuModel->where(['licaishiaccount' => $licaishi_jdpin])->select();
    }

}