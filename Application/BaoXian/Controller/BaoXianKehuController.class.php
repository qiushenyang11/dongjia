<?php

namespace BaoXian\Controller;

use Think\Controller;

use BaoXian\Model\KehuModel;

class BaoXianKehuController extends Controller {

    private $kehu_model;

    public function __construct()
    {
        parent::__construct();
        $this->kehu_model = new KehuModel();
    }

    /**
     * 新加一个客户
     */
    public function addOneKehu() {
        $phone = I('post.phone');
        $name = I('post.name');
        $QuizTemplateInfo = I('post.quiz_template_info');
        if ($userId = $this->kehu_model->addOneKehu($name, $phone, $QuizTemplateInfo)) {
            response('Success', 1, $userId);
        }
        response('Failed, Check phone and name');
    }

    /**
     * 获取当前理财师所有客户列表
     */
    public function getKehuList() {
        return $this->kehu_model->getAllKehu();
    }

    /**
     * 更新问卷答案
     */
    public function updateAnswer() {
        $answer = I('post.answer');
        $userid = I('post.userid');
        $this->kehu_model->updateAnswer($userid, $answer);
        response('Success', 1);
    }

}