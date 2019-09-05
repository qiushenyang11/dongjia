<?php
namespace CardApi\Controller;

use Server\Card;
use Server\Goods;
use Server\WeChatRedis;


use Think\Controller;

class CardController extends Controller
{
    /***
     * 新建礼品卡/卡包
     *
     **/
    //https://www.dservie.cn/myWeb/index.php/CardApi/Card/addCard
    public function addCard()
    {
        $name=I("post.name", '');           //卡包需要

        $price=I("post.price", '');

        $num=I("post.num", '');             //卡包份数

        $type = I("post.type",1);           //type 1 余额卡 2兑换卡

        $cardpic = I('post.cardpic','');       //兑换卡图片

        $exchangelist = $_POST['exchangelist'];  //json数据  [{"id":0,"productid":1103,"selects":["1336-1508","1336-1509"],"nums":10,"title":"12234"}]

        //'1固定日期 2领取后N天 ，卡包必传1
        $timetype=I("timetype", '');

        $desc=I("post.desc",'');

        //1白名单  2黑名单
        $usetype=I("post.usetype", 1);

        //'1所有产品 2部分产品',
        $isall=I("post.isall", 1);

        $productids=I("post.productids",'');

        $levelones=I("post.levelones", '');

        $leveltwos=I("post.leveltwos", '');

        $guanjiaids=I("post.guanjiaids", '');

        $supplierids=I("post.supplierids", '');

        $info=I("post.info", '');

        $status=I("post.status", '');

        $addtime=time();

        //卡包卡
        /********卡套**********/

        $parent=I("post.parent", 0);

        $cardList=I("post.cardlist");

        $cardurl=I("post.cardurl");//类型;URL

        if ($parent == 1) {
            if ($num <=0) response('请输入正确的份数');
            if (!$cardList) response('请绑定礼品卡');
            $cardList = explode(',', $cardList);
            $cardtemp = [];
            $tempids = [];
            foreach ($cardList as $row) {
                $temp = explode(';',$row);
                $cardtemp[]=[
                    'id'=>$temp[0],
                    'name'=>$temp[1]
                ];
                $tempids[] = $temp[0];
            }
            $restnumlist = M('cardloop')->field('id,(num-bindnum) as restnum')->where(['id'=>['in',$tempids]])->select();
            $minmsg = '';
            foreach ($restnumlist as $row) {
                if ($row['restnum'] < $num) {
                    $minmsg .= 'ID' . $row['id'] . '的礼品卡份数不足,';
                }
            }
            if ($minmsg){
                $minmsg = rtrim($minmsg,',');
                response($minmsg);
            }
            unset($cardList);
            $cardList = json_encode($cardtemp);
        }

        if($num==0)
        {
            response("参数错误");
        }

        if($num>2000)
        {
            response("发行份数最多2000份");
        }
        if($timetype==1)                //卡包需传 1
        {
            $time=I("post.time",'');        //若是卡包  传有效期
            if (Date("Y-m-d",strtotime($time)) != $time) {
                response('输入正确的日期');
            }
            if (!$time) response('请填写结束日期');
            $time = strtotime($time);
        }
        else
        {//领取后N天
            $time=I("post.time", '');

            if($time>365*2)
            {
                response("最多不超过2年");
            }

        }

        if ($type ==2) {
            $isall = 0;
        }

        if($isall==1)
        {

        }
        else//判断参数正确性，非全平台判断余下参数是否为空
        {
            if (($usetype == 1)&&($isall==1) && $type ==1)
            {
                response('黑名单禁止选择所有产品');
            }
            if(!$usetype && $type==1)
            {
                response("参数错误");
            }
            /*          if(!($productids||$levelones||$leveltwos||$guanjiaids||$supplierids))
                      {
                          response("Wrong Param productids||levelones||leveltwos||guanjiaids Cant Not be All Empty");
                      }*/
        }

        if ($type == 2) {
            if (!$cardpic) response('请上传兑换卡图片');
            if (!$exchangelist) response('请填写可兑换的产品');
            $cardClass = new Card();

            $exchangelist = $cardClass->verifyExchangeProduct($exchangelist);
        }


        $CardLoop=M("cardloop");

        $Card=M("card");

        $dataLoop=array();

        $dataLoop["name"]=$name;

        $dataLoop["price"]=$price;

        $dataLoop["num"]=$num;

        $dataLoop["timetype"]=$timetype;

        $dataLoop["time"]=$timetype == 1 ?($time+24*3600-1):$time;

        $dataLoop["desc"]=$desc;

        $dataLoop["usetype"]=$usetype;

        $dataLoop["isall"]=$isall;

        $dataLoop["productids"]=$productids;

        $dataLoop["levelones"]=$levelones;

        $dataLoop["leveltwos"]=$leveltwos;

        $dataLoop["guanjiaids"]=$guanjiaids;

        $dataLoop["supplierids"]=$supplierids;

        $dataLoop["info"]=$info;

        $dataLoop["status"]=$status;

        $dataLoop["addtime"]=$addtime;

        $dataLoop['type'] = $type;

        $dataLoop['cardpic'] = $cardpic;

        $dataLoop['exchangelist'] = $exchangelist;

        /********卡套**********/
        if($parent)
        {
            $dataLoop["parent"]=$parent;

            $dataLoop["cardlist"]=$cardList;
        }

        if($cardurl)
        {
            $dataLoop["cardurl"]= $cardurl;
        }
        /********卡套**********/


        $CardLoop->startTrans();

        $rstLoop=$CardLoop->add($dataLoop);

        $loopId=$rstLoop;

        $dataCard=array();

        /********卡套**********/

        if($cardurl)
        {
            $dataCard["cardurl"]=$cardurl;
        }
        if ($parent)
        {
            $dataCard["parent"]=$parent;
        }
        if($cardList)
        {
            $dataCard["cardlist"]=$cardList;
        }

        /********卡套**********/

        $dataCard["loopid"]=$loopId;

        $dataCard["name"]=$name;

        $dataCard["price"]=$price;

        $dataCard["nowprice"]=$price;

        $dataCard["timetype"]=$timetype;

        $dataCard["time"]=$timetype == 1 ?($time+24*3600-1):$time;;

        $dataCard["isall"]=$isall;

        $dataCard["productids"]=$productids;

        $dataCard["levelones"]=$levelones;

        $dataCard["leveltwos"]=$leveltwos;

        $dataCard["guanjiaids"]=$guanjiaids;

        $dataCard["supplierids"]=$supplierids;

        $dataCard['usetype'] = $usetype;

        $dataCard['desc'] = $desc;

        $dataCard["info"]=$info;

        $dataCard["status"]=$status;

        $dataCard["addtime"]=time();

        $dataCard['type'] = $type;

        $dataCard['cardpic'] = $cardpic;

        $dataCard['exchangelist'] = $exchangelist;

        $array=array();

        for($i=0;$i<$num;$i++)
        {
            $array[$i]=$dataCard;

            $raw=time().":".$i;

            $array[$i]["code"]=$this->zipCode($raw).$this->generateLastTwoCode();

            $array[$i]["cardid"]=$this->createCardNumber($dataCard["addtime"]);

        }

        $rstCard=$Card->addAll($array);

        if($rstCard&&$rstLoop)
        {
            $CardLoop->commit();


            response("添加成功", 1);
        }
        else
        {
            $CardLoop->rollback();

            response("添加失败");

        }

    }

    //https://www.dservie.cn/myWeb/index.php/CardApi/Card/searchCard
    public function searchCard()
    {
        $condition = I("get.key",'');
        $cardModel = M('cardloop');
        if (!$condition) {
            return;
        }
        $where = [];
        if (is_numeric($condition)) {
            $where['id'] = $condition;
        } else {
            $where['name'] = ['like',"%$condition%"];
        }
        $where['parent'] = 0;
        $where['status'] = 1;
        $cardlist = $cardModel->field("id,name")->where($where)->select();
        if ($cardlist)  {
            foreach ($cardlist as $row) {
                $data[]=[
                    'id'=>$row['id'].','.$row['name'],
                    'text'=>$row['name'].'('.$row['id'].')'
                ];
            }
        } else {
            $data = [];
        }
        echo json_encode($data);
    }


    /***
     *
     * 修改礼品卡
     */
    //https://www.dservie.cn/myWeb/index.php/CardApi/Card/updateCard
    public function updateCard()
    {
        $id=I("post.id");

        $name=I("post.name");

        $desc=I("post.desc");
        //1白名单  2黑名单
        $usetype=I("post.usetype");

        //'1所有产品 2部分产品',
        $isall=I("post.isall");

        $productids=I("post.productids");

        $levelones=I("post.levelones");

        $leveltwos=I("post.leveltwos");

        $guanjiaids=I("post.guanjiaids");

        $supplierids=I("post.supplierids");


        $info=I("post.info");

        $status=I("post.status");

        $addtime=time();

        $type = I("post.type",1);           //type 1 余额卡 2兑换卡

        $cardpic = I('post.cardpic','');       //兑换卡图片

        $exchangelist = $_POST['exchangelist'];  //json数据  [{"id":0,"productid":1103,"selects":["1336-1508","1336-1509"],"nums":10,"title":"12234"}]
//卡包卡
        /********卡套**********/

        $parent=I("post.parent");

        $cardList=I("post.cardlist");

        $time = I('post.time','');



        if ($parent) {
            $cardList = explode(',', $cardList);
            $cardtemp = [];
            foreach ($cardList as $row) {
                $temp = explode(';',$row);
                $cardtemp[]=[
                    'id'=>$temp[0],
                    'name'=>$temp[1]
                ];
            }
            unset($cardList);
            $cardList = json_encode($cardtemp);
        }

        $cardurl=I("post.cardurl");//类型;URL

        if ($type == 2) {
            if (!$cardpic) response('请上传兑换卡图片');
            if (!$exchangelist) response('请填写可兑换的产品');
            $cardClass = new Card();
            $exchangelist = $cardClass->verifyExchangeProduct($exchangelist);
        }

        /********卡套**********/
        /**测试数据**/
        //$id=1;$name="Fuck You!";$usetype=1;$productids="12222";$isall=2;

        $CardLoop=M("cardloop");

        $CardLoop->startTrans();

        $where=array();

        $where["id"]=$id;

        $rst=$CardLoop->where($where)->limit(1)->find();
        if ($type ==2) {
            $isall = 0;
        }

        if(!$rst)
        {
            response("Failure Update Beacuse Of No Card");
        }
        if($isall!=1 && !$parent && $type == 1)       //礼品包不考虑 不需要这个参数
        {
            if(!$usetype)
            {
                response("Wrong Param WithOut UseType");
            }
            /*            if(!($productids||$levelones||$leveltwos||$guanjiaids||$supplierids))
                        {
                            response("Wrong Param productids||levelones||leveltwos||guanjiaids Cant Not be All Empty");
                        }*/
        }



        $CardLoop=M("cardloop");

        $where=array();

        $where["id"]=$id;



        //改LOOP
        $dataLoop=array();

        $dataCard=array();

        /********卡套**********/
        if($parent)
        {

            if (Date("Y-m-d",strtotime($time)) != $time) {
                response('输入正确的日期');
            }
            $dataLoop["parent"]=$parent;

            $dataCard["parent"]=$parent;

            $dataLoop["cardlist"]=$cardList;

            $dataCard["cardlist"]=$cardList;

            $dataCard['time'] = strtotime($time);

            $dataLoop['time'] = strtotime($time);
        }

        if($cardurl)
        {
            $dataLoop["cardurl"]=$cardurl;

            $dataCard["cardurl"]=$cardurl;
        }
        /********卡套**********/


        $dataLoop["name"]=$name;


        $dataCard["name"]=$name;


        $dataLoop["desc"]=$desc;


        $dataCard["desc"]=$desc;


        $dataLoop["usetype"]=$usetype;


        $dataCard["usetype"]=$usetype;


        $dataLoop["isall"]=$isall;


        $dataCard["isall"]=$isall;


        $dataLoop["productids"]=$productids;

        $dataCard["productids"]=$productids;

        $dataLoop["levelones"]=$levelones;


        $dataCard["levelones"]=$levelones;


        $dataLoop["leveltwos"]=$leveltwos;


        $dataCard["leveltwos"]=$leveltwos;


        $dataLoop["guanjiaids"]=$guanjiaids;


        $dataCard["guanjiaids"]=$guanjiaids;


        $dataLoop["supplierids"]=$supplierids;

        $dataCard["supplierids"]=$supplierids;

        $dataLoop["info"]=$info;


        $dataCard["info"]=$info;


        $dataLoop["status"]=$status;


        $dataCard["status"]=$status;

        $dataLoop['type'] = $type;

        $dataLoop['cardpic'] = $cardpic;

        $dataLoop['exchangelist'] = $exchangelist;

        $dataCard['type'] = $type;

        $dataCard['cardpic'] = $cardpic;

        $dataCard['exchangelist'] = $exchangelist;


        $dataLoop["addtime"]=time();

        $dataLoop["id"]=$id;

        $CardLoop->startTrans();

        $Card=M("card");

        $where=array();

        $where["loopid"]=$id;

        $where["jdaccount"]=array('EXP','is NULL');

        $updateIds=$Card->where($where)->field("id")->select();

        $count=count($updateIds);
        if ($count) {
            $arrayIds=array();

            for($i=0;$i<$count;$i++)
            {
                $arrayIds[$i]=$updateIds[$i]["id"];
            }

            $arryStr=implode(',',$arrayIds);

            $map=array("id IN (".$arryStr.")");

            $rstU=$Card->where($map)->setField($dataCard);
        } else {
            $rstU = true;
        }

        $rstL=$CardLoop->save($dataLoop);

        if($rstL)
        {
            $CardLoop->commit();

            response("修改成功",1,0);
        }
        else
        {
            $CardLoop->rollback();

            response("修改失败");
        }

    }

    //https://www.dservie.cn/myWeb/index.php/CardApi/Card/testNull
    public function testNull()
    {
        $nowdate=date('Y-m-d');

        echo strtotime($nowdate);

        exit;


        $Card=M("card");

        $where["loopid"]=1;

        $where["jdaccount"]=array('EXP','is NULL');

        $updateIds=$Card->where($where)->field("id")->select();

        $count=count($updateIds);

        $arrayIds=array();

        for($i=0;$i<$count;$i++)
        {
            $arrayIds[$i]=$updateIds[$i]["id"];
        }

        echo json_encode($arrayIds);

        $arryStr=implode(',',$arrayIds);


        echo $arryStr;



        $map=array("id IN (".$arryStr.")");

        $dataLoop=array();

        $dataLoop["name"]="ggg";

        $dataLoop["desc"]="yyy";

        $data=array();





        $dataLoop["usetype"]=1;

        $dataLoop["isall"]=1;

        $dataLoop["productids"]=null;

        $dataLoop["levelones"]=null;

        $dataLoop["leveltwos"]="";

        $dataLoop["guanjiaids"]="";

        $dataLoop["supplierids"]="";

        $dataLoop["info"]="ffddfd";

        $dataLoop["status"]=1;

        $dataLoop["addtime"]=time();

        $rstU=$Card->where($map)->setField($dataLoop);



        echo $Card->getLastSql();

        if($rstU)
        {
            echo "GOOG";
        }
        else
        {
            echo "wrong";
        }

    }


    /*****
    绑定礼品卡
     ***/
    //https://www.dservie.cn/myWeb/index.php/CardApi/Card/bindCard
    public function bindCard()
    {
        //前端test json , success, 2 cards, 110 CNY
//        echo '{"state":1,"msg":"SuccessMulti全部兑换成功","data":{"successAll":1,"message":"全部兑换成功","failureNum":0,"failureArray":[],"successNum":2,"successArray":[{"code":"8AC22FG1C1LZ7RNB"},{"card":{"id":"545","loopid":"73","jdaccount":"18367826195_p","name":"测试东家卡宝宝SYF03","cardid":"501847604264","code":"8AC22FG1C1LZ7RNB","price":"50.00","nowprice":"50.00","timetype":"1","time":"1535731199","starttime":"1535018725","endtime":"1535731199","desc":"&lt;p&gt;测试东家卡宝宝SYF03&lt;/p&gt;","usetype":"1","isall":"1","productids":"","levelones":"","leveltwos":"","guanjiaids":"","supplierids":"","info":"测试东家卡宝宝SYF03","status":"1","addtime":"1535018476","cardurl":"1;","parent":"0","cardlist":null}},{"code":"O1M0UEK423GWJ8Z5"},{"card":{"id":"546","loopid":"74","jdaccount":"18367826195_p","name":"测试东家卡宝宝SYF04","cardid":"501851804584","code":"O1M0UEK423GWJ8Z5","price":"60.00","nowprice":"60.00","timetype":"1","time":"1535731199","starttime":"1535018725","endtime":"1535731199","desc":"&lt;p&gt;测试东家卡宝宝SYF04&lt;/p&gt;","usetype":"1","isall":"1","productids":"","levelones":"","leveltwos":"","guanjiaids":"","supplierids":"","info":"测试东家卡宝宝SYF04","status":"1","addtime":"1535018518","cardurl":"3;18","parent":"0","cardlist":null}}],"successPrice":110}}';die;
        needAuth('jdLogin');
        $jdAccount=session('jdaccount');

        $cardCode=I("post.cardcode");

        //测试数据
        //$cardCode="9G43PUNHOD7XLB1K";
        /******判断是不是有这张卡******/
        $where=array();

        $where["code"]=$cardCode;

        $Card=M("card");

        $CardLoop=M("cardloop");

        $rst=$Card->where($where)->limit(1)->find();

        $R=new WeChatRedis();

        $rstR=$R->getBindWrong($jdAccount);

        if($rstR>=10)
        {
            response("Too Much Bind!");
        }
        if(!$rst)
        {
            $R->bindCardWrong($jdAccount);

//            response("Code Is Unvalidate!");
            response("该礼品卡密码不存在");
        }
        else if($rst["jdaccount"]||($rst["jdaccount"]==$jdAccount))
        {
//            response("This Card Has Been Binded!");
            response("该礼品卡已经被兑换");
        }
        else if($rst["status"]==0)
        {
//             response("This Card Has been Stopped Binded!");
            response("礼品卡不可用");
        }
        else if(($rst["timetype"]==1)&&(time()>$rst["time"]))
        {
//            response("This Card has expired!!!");
            response("礼品卡已过期");
        }
        else
        {
            //判定是否为卡套，里面含有多张卡
            /********卡套**********/

            $parent=$rst["parent"];

            if($parent==1)
            {
                //获取cardList  $cardCodeList;
                $whereLoopL["id"]=$rst["loopid"];

                $rstBBL=$CardLoop->where($whereLoopL)->limit(1)->find();

                if($rstBBL["num"]==$rstBBL["bindnum"])
                {
                    response("该礼品卡卡包已经被兑换",1,array());
//                response("All Card Has Been Picked UP");
                }


                $cardChildString=$rst["cardlist"];

                $cardChildArray=json_decode($cardChildString,true);

                $cardCodeList=array();

                $childCount=count($cardChildArray);

                for($i=0;$i<$childCount;$i++)
                {
                    $cardCodeList[$i]["id"]=$cardChildArray[$i]["id"];
                }

                $rstCardAll=$this->bindCardListInner($jdAccount,$cardCodeList);

                $whereLoopMutli=array();

                $whereLoopMutli["id"]=$rst["loopid"];

                $CardLoop->where($whereLoopMutli)->setInc("bindnum");

                $Card->where($where)->setField("jdaccount",$jdAccount);

                if($rstCardAll["successAll"]==1)
                {
                    response("SuccessMulti".$rstCardAll["message"],1, $rstCardAll);
                }
                else
                {
                    response("FailureMulti".$rstCardAll["message"],1,array());
                }
            }

            /********卡套**********/

            //绑定过程
            $data=array();

            $data["jdaccount"]=$jdAccount;

            $whereNew=array();

            $whereNew["id"]=$rst["id"];

            if($rst["timetype"]==1)
            {
                $data["starttime"]=time();

                $data["endtime"]=$rst["time"];
            }

            if($rst["timetype"]==2)
            {

                $nowdate=date('Y-m-d');

                $nowdateTimeStamp=strtotime($nowdate);

                $data["starttime"]=$nowdateTimeStamp;

                $data["endtime"]=strtotime($this->dayto($rst["time"],"",true));
            }

            $loopid=$rst["loopid"];

            $whereLoop=array();

            $whereLoop["id"]=$loopid;

            $rstBB=$CardLoop->where($whereLoop)->limit(1)->find();

            if($rstBB["num"]==$rstBB["bindnum"])
            {
                response("该礼品卡已经被兑换");
//                response("All Card Has Been Picked UP");
            }

            $Card->startTrans();

            $rstL=$CardLoop->where($whereLoop)->setInc("bindnum");

            $rstB=$Card->where($whereNew)->save($data);

            $rstC = $this->addOneCardHistory($jdAccount,$rst['name'],$rst['id'], $rst['price'],'', 1);

            if($rstB&&$rstL&&$rstC)
            {
                $Card->commit();

                $rstHas=$Card->where($whereNew)->limit(1)->find();

                response("SuccessOne",1,$rstHas);
            }
            else
            {
                $Card->rollback();

//                response("Failure To bind Beacuse Of DataBase");
                response("该礼品卡密码不存在");
            }

        }


    }




    /*****
     * 绑定卡内部方法
    ****/
    public function bindCardListInner($jdAccount,$cardCodeList)
    {
        $back=array();

        if(!is_array($cardCodeList))
        {
            $back["successAll"]=0;//失败

            $back["message"]="不是数组";

            return $back;
        }

        $cardInnerCount=count($cardCodeList);

        if($cardInnerCount==0)
        {
            $back["successAll"]=0;//失败

            $back["message"]="不是父卡";

            return $back;
        }

        $successPick=0;

        $successArray=array();

        $failurePick=0;

        $failureArray=array();

        $successPrice=0;

        $Card=M("card");

        $CardLoop=M("cardloop");

        $Card->startTrans();

        for($i=0;$i<$cardInnerCount;$i++)
        {
            $cardLoopId=$cardCodeList[$i]["id"];

            $whereXXX=array();

            $whereXXX["loopid"]=$cardLoopId;

            $whereXXX["jdaccount"]=array("EXP", "is null");

            $rstXXX=$Card->where($whereXXX)->limit(1)->find();

            $cardCode=$rstXXX["code"];
            /******判断是不是有这张卡******/
            $where=array();

            $where["code"]=$cardCode;

            $rst=$Card->where($where)->limit(1)->find();

            if(!$rst)
            {
//            response("Code Is Unvalidate!");
                //response("该礼品卡密码不存在");
                $failurePick=$failurePick+1;

                $failureArray[]["code"]=$cardCode;

                $failureArray[]["reason"]="该礼品卡密码不存在0.LoopID is:".$cardLoopId;

                continue;

            }
            else if($rst["jdaccount"]||($rst["jdaccount"]==$jdAccount))
            {
//            response("This Card Has Been Binded!");
                //response("该礼品卡已经被兑换");
                $failurePick=$failurePick+1;

                $failureArray[]["code"]=$cardCode;

                $failureArray[]["reason"]="该礼品卡已经被兑换";

                continue;

            }
            else if($rst["status"]==0)
            {
//             response("This Card Has been Stopped Binded!");
                //response("礼品卡不可用");
                $failurePick=$failurePick+1;

                $failureArray[]["code"]=$cardCode;

                $failureArray[]["reason"]="礼品卡不可用";

                continue;



            }
            else if(($rst["timetype"]==1)&&(time()>$rst["time"]))
            {
//            response("This Card has expired!!!");
                //response("礼品卡已过期");
                $failurePick=$failurePick+1;

                $failureArray[]["code"]=$cardCode;

                $failureArray[]["reason"]="礼品卡已过期";

                continue;

            }
            else
            {
                //绑定过程
                $data=array();

                $data["jdaccount"]=$jdAccount;

                $whereNew=array();

                $whereNew["id"]=$rst["id"];

                if($rst["timetype"]==1)
                {
                    $data["starttime"]=time();

                    $data["endtime"]=$rst["time"];
                }

                if($rst["timetype"]==2)
                {

                    $nowdate=date('Y-m-d');

                    $nowdateTimeStamp=strtotime($nowdate);

                    $data["starttime"]=$nowdateTimeStamp;

                    $data["endtime"]=strtotime($this->dayto($rst["time"],"",true));
                }

                $loopid=$rst["loopid"];

                $whereLoop=array();

                $whereLoop["id"]=$loopid;

                $rstBB=$CardLoop->where($whereLoop)->limit(1)->find();

                if($rstBB["num"]==$rstBB["bindnum"])
                {
                    //response("该礼品卡已经被兑换");
//                response("All Card Has Been Picked UP");
                    $failurePick=$failurePick+1;

                    $failureArray[]["code"]=$cardCode;

                    $failureArray[]["reason"]="该礼品卡已经被兑换";

                    continue;

                }
                //$Card->startTrans();

                $rstL=$CardLoop->where($whereLoop)->setInc("bindnum");

                $rstB=$Card->where($whereNew)->save($data);

                $rstC = $this->addOneCardHistory($jdAccount,$rst['name'],$rst['id'], $rst['price'],'', 1);

                if($rstB&&$rstL&&$rstC)
                {
                    //$Card->commit();
                    $successPick=$successPick+1;

                    $successPrice=$successPrice+$rst["price"];

                    $successArray[]["code"]=$cardCode;

                    $rstHas=$Card->where($whereNew)->limit(1)->find();

                    $successArray[]["card"]=$rstHas;


                    //response("Success",1,$rstHas);
                }
                else
                {
//                    $Card->rollback();
//
////                response("Failure To bind Beacuse Of DataBase");
//                    response("该礼品卡密码不存在");
                    $failurePick=$failurePick+1;

                    $failureArray[]["code"]=$cardCode;

                    $failureArray[]["reason"]="该礼品卡密码不存在1";

                    continue;


                }

            }
        }


        if($successPick==$cardInnerCount)
        {
            $back["successAll"]=1;//成功

            $back["message"]="全部兑换成功";

            $Card->commit();
        }
        else
        {
            $back["successAll"]=1;//失败

            $back["message"]="本次兑换成功:".$successPick."失败兑换为:".$failurePick;

            $Card->commit();
        }

        $back["failureNum"]=$failurePick;

        $back["failureArray"]=$failureArray;

        $back["successNum"]=$successPick;

        $back["successArray"]=$successArray;

        $back["successPrice"]=$successPrice;

        return $back;

    }


    /**
    获取我所有的卡
     ***/
    //https://www.dservie.cn/myWeb/index.php/CardApi/Card/getAllMyCard
    public function getAllMyCard()
    {
        needAuth('jdLogin');
        $jdAccount=session('jdaccount');

        $Card=M("card");

        $where=array();

        $where["jdaccount"]=$jdAccount;

        $now=time();

        $where['UNIX_TIMESTAMP(endtime)'] = array("lt",$now);

        $where["parent"]=0;

     //   $where["nowprice"]=array("gt",0);

        $where['isuseall'] = 0;

        $rst=$Card->where($where)->order('id desc')->select();


        if($rst)
        {
            foreach ($rst as $k => $v) {
                $rst[$k]['desc'] = htmlspecialchars_decode($v['desc']);
            }
            response("Success",1,$rst);
        }
        else
        {
            response("No Card", 1, array());
        }

    }

    /**
    获取我所有的卡数量
     ***/
    //https://www.dservie.cn/myWeb/index.php/CardApi/Card/getAllMyCard
    public function getAllMyCardCount()
    {
        needAuth('jdLogin');
        $jdAccount=session('jdaccount');


        $Card=M("card");

        $where=array();

        $now=time();

        $where["jdaccount"]=$jdAccount;

        $where['UNIX_TIMESTAMP(endtime)'] = array("lt",$now);

      /*  $where["nowprice"]=array("gt",0);*/

        /*    $where["nowprice"]=array("gt",0);*/

        $where['isuseall'] = 0;

        $rst=$Card->where($where)->count();


        if($rst)
        {
            response("Success",1,$rst);
        }
        else
        {
            $data=array();

            response("Data Base Wrong",1, 0);
        }

    }


    /****添加一条卡的交易记录***/
    //https://www.dservie.cn/myWeb/index.php/CardApi/Card/addOneCardHistory
    private function addOneCardHistory($jdAccount, $cardname,$cardid,$price,$ordersn,$type)
    {
        $CardHistory = M('cardlog');

        $data["jdaccount"]=$jdAccount;

        $data["cardname"]=$cardname;

        $data["cardid"]=$cardid;

        $data["price"]=$price;

        $data["ordersn"]=$ordersn;

        $data["type"]=$type;

        $data['addtime'] = time();

        $rst=$CardHistory->add($data);

        if($rst)
        {
            return true;//response("Success Add One History",1,$data);
        }
        else
        {
            return false;//response("Failure Add One History");
        }
    }

    /**
    获取我的某张卡交易详情
     ***/
    //https://www.dservie.cn/myWeb/index.php/CardApi/Card/getCardHistory
    public function getCardHistory()
    {
        needAuth('jdLogin');
        $jdAccount=session('jdaccount');

        $cardid=I("post.cardid");

        $CardHistory=M("cardlog as c");

        $where=array();

        $where["c.cardid"]=$cardid;

        $where["c.jdaccount"]=$jdAccount;

        $where['c.type'] = ['in',[1,2,3]];

        $rst=$CardHistory
                ->field('c.*,og.productname')
                ->join('__ORDER_INFO__ as o ON c.ordersn = o.ordersn','left')
                ->join('__ORDER_GOOD__ as og ON o.id = og.orderid','left')
                ->where($where)
                ->order('c.id asc')
                ->select();
        if($rst)
        {
            response("Success",1,$rst);
        }
        else
        {
            response("Failure No Find", 1, array());
        }

    }


    /***
    获取当前商品可用的卡
     ***/
    //https://www.dservie.cn/myWeb/index.php/CardApi/Card/findNowWhichCardCanUse
    public function findNowWhichCardCanUse()
    {
        needAuth('jdLogin');
        $jdAccount=session('jdaccount');

        $productid=I("post.productid");

        $where["jdaccount"]=$jdAccount;

        $now=time();

        $Card=M("card");

        $where['endtime'] = array("gt",$now);

       $where["nowprice"]=array("gt",0);

        //兑换卡不显示
        $where['type'] = 1;
        /******卡包*******/
        $where["parent"]=0;
        /******卡包*******/
        $rst=$Card->where($where)->order('id desc')->select();


        if(!$rst)
        {
            $data=array();

            response("No Card Can Be Use",1,$data);
        }

        $canUse=array();

        /******发现白名单*********/
        $count=count($rst);

        for($i=0;$i<$count;$i++)
        {
            if($rst[$i]["usetype"]==1)          //白名单
            {
                //全品类
                if ($rst[$i]["isall"] == 1){
                    $canUse[]=$rst[$i];
                    continue;
                } else {
                    if($rst[$i]["productids"])
                    {
                        $tempProductIds=$rst[$i]["productids"];

                        if(strpos($tempProductIds,"-".$productid."-") !== false)
                        {
                            //包含
                            $canUse[]=$rst[$i];

                            continue;
                        }

                    }

                    $productLevelAll=$this->getProductLevelAllAndGuanJiaAndSupplierIds($productid);

                    if($rst[$i]["levelones"])
                    {
                        $tempLevelones=$rst[$i]["levelones"];

                        if(strpos($tempLevelones,"-".$productLevelAll["classOne"]."-")!== false)
                        {
                            //包含
                            $canUse[]=$rst[$i];

                            continue;
                        }
                    }
                    if($rst[$i]["leveltwos"])
                    {
                        $tempLeveltwos=$rst[$i]["leveltwos"];

                        if(strpos($tempLeveltwos,"-".$productLevelAll["classTwo"]."-")!== false)
                        {
                            //包含
                            $canUse[]=$rst[$i];

                            continue;
                        }
                    }
                    if($rst[$i]["guanjiaids"])
                    {
                        $tempGuanjiaIds=$rst[$i]["guanjiaids"];

                        if(strpos($tempGuanjiaIds,"-".$productLevelAll["product"]["guanjiaid"]."-")!== false)
                        {
                            //包含
                            $canUse[]=$rst[$i];

                            continue;
                        }
                    }
                    if($rst[$i]["supplierids"])
                    {
                        $tempSuppliersIds=$rst[$i]["supplierids"];

                        if(strpos($tempSuppliersIds,"-".$productLevelAll["product"]["supplierid"]."-")!== false)
                        {
                            //包含
                            $canUse[]=$rst[$i];

                            continue;
                        }
                    }
                }

            }
            else
            {
                //黑名单
                if($rst[$i]["productids"])
                {
                    $tempProductIds=$rst[$i]["productids"];

                    if(strpos($tempProductIds,"-".$productid."-") !== false)
                    {
                        //包含
                        continue;
                    }

                }

                $productLevelAll=$this->getProductLevelAllAndGuanJiaAndSupplierIds($productid);

                if($rst[$i]["levelones"])
                {
                    $tempLevelones=$rst[$i]["levelones"];

                    if(strpos($tempLevelones,"-".$productLevelAll["classOne"]."-")!== false)
                    {
                        //包含
                        continue;
                    }
                }
                if($rst[$i]["leveltwos"])
                {
                    $tempLeveltwos=$rst[$i]["leveltwos"];

                    if(strpos($tempLeveltwos,"-".$productLevelAll["classTwo"]."-")!== false)
                    {
                        //包含
                        continue;
                    }
                }
                if($rst[$i]["guanjiaids"])
                {
                    $tempGuanjiaIds=$rst[$i]["guanjiaids"];

                    if(strpos($tempGuanjiaIds,"-".$productLevelAll["product"]["guanjiaid"]."-")!== false)
                    {
                        //包含
                        continue;
                    }
                }
                if($rst[$i]["supplierids"])
                {
                    $tempSuppliersIds=$rst[$i]["supplierids"];

                    if(strpos($tempSuppliersIds,"-".$productLevelAll["product"]["supplierid"]."-")!== false)
                    {
                        //包含

                        continue;
                    }
                }
                $canUse[]=$rst[$i];
            }

        }

        $count=count($canUse);

        $back=array();

        $back["count"]=$count;

        $back["cardAll"]=$canUse;

        if($count==0)
        {
            response("Failure No Card Can Use", 1, $back);
        }
        else
        {
            response("Success",1,$back);
        }


    }


    //https://www.dservie.cn/myWeb/index.php/CardApi/Card/searchBackToFindAll
    private function getProductLevelAllAndGuanJiaAndSupplierIds($productid)
    {
        //$productid=I("post.productid");


        $Product=M("product");

        $where=array();

        $where["id"]=$productid;

        $rst=$Product->where($where)->field("id,categoryid,categoryname,guanjiaid,supplierid")->limit(1)->find();

        /*产品名获取产品分类*/
        $categoryid=$rst["categoryid"];

        $whereC=array();

        $whereC["id"]=$categoryid;

        $Category=M("category");

        $rstC=$Category->where($whereC)->field("id,pid")->limit(1)->find();

//        $whereC["id"]=$rstC["pid"];
//
//        $rstCP=$Category->where($whereC)->field("id")->limit(1)->find();

        $data=array();

        $data["classOne"]=$rstC["pid"];

        $data["classTwo"]=$rstC["id"];

        $data["product"]=$rst;

        //echo json_encode($data,256);

        return $data;


    }



    // https://www.dservie.cn/myWeb/index.php/CouponApi/Coupon/dayto
    private function dayto( $ts ,$nowdate='',$istrue = false ){


        if(empty($nowdate)) $nowdate=date('Y-m-d') ;

//        $wdldata = self::dbload( "SELECT DATE_ADD('$nowdate',INTERVAL $ts DAY) AS xdate" ,'one') ;
        $wdldata = M()->query("SELECT DATE_ADD('$nowdate',INTERVAL $ts DAY) AS xdate");



        if($wdldata)
        {
            //return
            if ($istrue) {
                $temp=strtotime($wdldata[0]['xdate'].' 23:59:59') ;
            } else {
                $temp=strtotime($wdldata[0]['xdate'].' 00:00:00') ;
            }


            $date=date("Y-m-d H:i:s",$temp);

            return $date;
        }
        else
        {
            return '' ;
        }
    }



    /***新建卡号**礼品卡由12位卡号和16位卡密组成；*/
    //https://www.dservie.cn/myWeb/index.php/CardApi/Card/createCardNumber
    private function createCardNumber($raw, $parent = 0)
    {
        $chars="0123456789";

        $password=substr($raw,-7);;

        $length=5;

        for($i=0;$i<$length;$i++)
        {
            if ($i == 0) {
                if ($parent == 1) {
                    $password.='8';
                } else {
                    $str = '012345679';
                    $password.= $str[mt_rand(0,strlen($str) - 1)];
                }

            } else {
                $password.= $chars[mt_rand(0,strlen($chars) - 1)];
            }

        }

        return $password;

    }

    /***新建卡密***/
    //https://www.dservie.cn/myWeb/index.php/CardApi/Card/createCardCode


    //最后两位补码
    private function generateLastTwoCode( $length = 8 )
    {
        $chars="ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

        $password="";
        for($i=0;$i<$length;$i++)
        {
            $password.= $chars[mt_rand(0,strlen($chars) - 1)];
        }

        return $password;
    }


    //生成八位码
    private function zipCode($raw)
    {
        for($a = md5( $raw, true ),
            $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV',
            $d = '',
            $f = 0;
            $f < 8;
            $g = ord( $a[ $f ] ),
            $d .= $s[ ( $g ^ ord( $a[ $f + 8 ] ) ) - $g & 0x1F ],
            $f++
        );



        return $d;
    }

    public function getAllgoodsAndSpec()
    {
        $productid = I('post.productid', 0);
        if (!$productid) response('参数错误');
        $goodClass = new Goods();
        $info = $goodClass->getAllgoodsAndSpec($productid);
        response('获取成功',1,$info);
    }

    public function refreshCard()
    {
        $cardModel = M('card');
        $where['type'] = 1;
        $where['parent'] = 0;
        $where['nowprice'] = 0;
        $savedata['isuseall'] = 1;
        $res = $cardModel->where($where)->save($savedata);
        var_dump($res);
    }

    public function getCardInfo()
    {
        needAuth('jdLogin');
        $cardid = I('post.cardid',0);
        if (!$cardid) response('参数错误');
        $jdaccount = session('jdaccount');
        $cardClass = new Card();
        $rst = $cardClass->getExchangeProductInfo($cardid,$jdaccount);
        response('获取成功',1,$rst);
    }

}
