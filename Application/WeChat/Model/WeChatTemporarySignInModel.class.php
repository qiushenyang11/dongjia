<?php
namespace WeChat\Model;
//use Think\Model;
class WeChatTemporarySignInModel
{
    public function hasUser($openid)
    {
        $where['openid'] = $openid;
        $model = M('zenge_user');
        return $model->where($where)->limit(1)->find();
    }

    public function addUser($phone,$userinfo,$openid)
    {
        $model = M('zenge_user');
        $data['phone'] = $phone;
        $data['userinfo'] = json_encode($userinfo);
        $data['openid'] = $openid;
        $data['signtime'] = time();
        return $model->data($data)->add();
    }
}