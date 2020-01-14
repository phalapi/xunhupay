<?php
/**
 * 订单批量查询
 *
 * @date 2017年3月13日
 * @copyright 重庆迅虎网络有限公司
 */
require_once 'api.php';
$out_trade_order = '20180921023800';//商户网站订单号
$appid              = '2147483647';//测试账户，
$appsecret          = '160130736b1ac0d54ed7abe51e44840b';//测试账户，

$request=array(
    'appid'     => $appid, //必须的，APPID
    'skip'		=>0,//跳过订单条数
	'take'		=>50,//每页取订单条数（最大1000）
	'status'	=>'',//可选，取值OD已支付， WP待支付, CD已取消
	'start'		=>'',//可选 ，订单开始时间  格式：2018-12-20 12：00
	'end'		=>'',//可选 ，订单结束时间  格式：2018-12-20 12：00
    'time'      => time(),//必须的，当前时间戳，根据此字段判断订单请求是否已超时，防止第三方攻击服务器
    'nonce_str' => str_shuffle(time())//必须的，随机字符串，作用：1.避免服务器缓存，2.防止安全密钥被猜测出来
);

$request['hash'] =  XH_Payment_Api::generate_xh_hash($request,$appsecret);

$url              = 'https://api.xunhupay.com/payment/querylist.html';

try {
    $response     = XH_Payment_Api::http_post($url, http_build_query($request));
    /**
     * 支付回调数据
     * @var array(
     *      status,//OD：已支付  WP:未支付  CD 已取消
     *  )
     */
    $result       = $response?json_decode($response,true):null;
    if(!$result){
        throw new Exception('Internal server error:'.$response,500);
    }

    print_r($result);exit;
} catch (Exception $e) {
    echo "errcode:{$e->getCode()},errmsg:{$e->getMessage()}";
    //TODO:处理支付调用异常的情况
}
?>
