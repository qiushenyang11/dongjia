<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/12/15
 * Time: 11:03
 */

namespace Operation\Controller;
use Think\Model;
use Think\Controller;

class OperationBaseController extends Controller
{
    protected $isToken = true;
    private $beforeActionList = ['saveProduct', 'saveGuanjia', 'saveGoods', 'saveExpressGoods', 'addAccount'];
    protected $beforeList = [];
    public function _initialize()
    {
        $userid = session('adminuserid');
        $host = $_SERVER['HTTP_HOST'];
        if (!$userid) {
            if (IS_AJAX) {
                response('登入已过期,请退出重登');
            } else {
                $url = 'https://'.$host.'/myWeb/Operation/OperationLogin/work.html';
                echo "<script>top.location.href='".$url."'</script>";
            }
            exit;
        }
       /* if ($this->beforeList && count($this->beforeList)) {
            $this->beforeActionList = array_merge($this->beforeActionList, $this->beforeList);
        }
        if (in_array(ACTION_NAME, $this->beforeActionList) && !$this->checkToken()) {
            response('令牌失效，请刷新后重新提交');
        }*/
    }

    public function checkToken(){
        $token = [
            '__hash__'  => I('post.token', '')
        ];

        $model = new Model();
        return $model->autoCheckToken($token);
    }
}