<?php

namespace BaoXian\Model;

use Common\Common\pinyinfirstchar;

class KehuModel extends BaoxianModel {

    private $licaishiJdaccount = 'axer0910';

    /**
     * 设置理财师jdaccount
     * @param $jdaccount
     * @return $this
     */
    public function setLicaishiJdAccount($jdaccount) {
        $this->licaishiJdaccount = $jdaccount;
        return $this;
    }

    /**
     * json quot反转义
     * @param $str
     * @return mixed
     */
    private function replaceQuot($str) {
        return str_replace('&quot;', '"', $str);
    }

    /**
     * 添加一个客户
     * @param $name
     * @param $phone
     * @return bool
     */
    public function addOneKehu($name, $phone, $QuizTemplateInfo) {
        if (!$name || !$phone) {
            return false;
        }

        $data['kehu_name'] = $name;
        $data['kehu_phone'] = $phone;
        $data['licaishi_jdaccount'] = $this->licaishiJdaccount;
        $data['quiz_results'] = $this->replaceQuot($QuizTemplateInfo);
        return $this->data($data)->add();

    }

    /**
     * 获取所有客户列表
     */
    public function getAllKehu() {
        $where['licaishi_jdaccount'] = $this->licaishiJdaccount;
        $kehu_list = $this->field('id,kehu_name,kehu_phone,quiz_results')->where($where)->select();

        $pinyin = new pinyinfirstchar();

        $ordered_list = [];

        foreach ($kehu_list as $kehu) {
            $char = strtoupper($pinyin->getFirstchar($kehu['kehu_name']));
            $ordered_list[$char]['title'] = $char;
            $row = [
                'userid' => $kehu['id'],
                'name' => $kehu['kehu_name'],
                'phone' => $kehu['kehu_phone'],
                'avatar' => $kehu['avatar'],
                'quiz_template_info' => $kehu['quiz_results']
            ];
            $ordered_list[$char]['items'][] = $row;
        }

        ksort($ordered_list);
        $ordered_list = array_values($ordered_list);

        response('Success', 1, $ordered_list);
    }

    /**
     * 更新问卷答案
     * @param $userid
     * @param $jsonStr
     */
    public function updateAnswer($userid, $jsonStr) {
        $this->where(['id' => $userid])->save(['quiz_results' => $this->replaceQuot($jsonStr)]);
    }

}