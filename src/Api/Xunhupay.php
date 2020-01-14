<?php
namespace PhalApi\Xunhupay\Api;

require_once dirname(__FILE__) . '/../sdk/api.php';

use PhalApi\Exception\BadRequestException;
use PhalApi\Exception\InternalServerErrorException;

/**
 * 虎皮椒支付
 */
class Xunhupay extends \PhalApi\Api {

    public function getRules() {
        return array(
            'paymentDo' => array(
                'trade_order_id' => array('name' => 'trade_order_id', 'regex' => '/[a-zA-Z\d\-_]{1,}/', 'desc' => '商户订单号,必填。请确保在当前网站内是唯一订单号。为空时由系统自动生成。'),
                'total_fee' => array('name' => 'total_fee', 'require' => true, 'desc' => '订单金额(元),必填。单位为人民币，精确到分',), 
                'payment' => array('name' => 'payment', 'type' => 'enum', 'range' => array('wechat', 'alipay'), 'default' => 'wechat', 'desc' => '支付接口标识：wechat(微信接口)|alipay(支付宝接口)'),
                'title' => array('name' => 'title', 'require' => true, 'desc' => '订单标题,必填。商户订单标题'),
                'return_url' => array('name' => 'return_url', 'desc' => '跳转网址,可选。用户支付成功后，我们会让用户浏览器自动跳转到这个网址'),
                'callback_url' => array('name' => 'callback_url', 'desc' => '商品网址,可选。用户取消支付后，我们可能引导用户跳转到这个网址上重新进行支付'),
                'plugins' => array('name' => 'plugins', 'desc' => '备注,可选。备注字段，可以传入一些备注数据，回调时原样返回'),
                'isJump' => array('name' => 'is_jump', 'type' => 'boolean', 'default' => true, 'desc' => '是否直接跳转到支付页面'),
            ),
            'orderQuery' => array(
                'trade_order_id' => array('name' => 'trade_order_id', 'regex' => '/[a-zA-Z\d\-_]{1,}/', 'desc' => '商户订单号', 'require' => true),
            ),
        );
    }

    /**
     * 发起支付接口
     * @desc 发起支付接口，实现微信、支付宝支付的接口
     * @return string pay_url 客户端待跳转的支付链接，当指定由接口跳转时接口则直接跳转
     * @return string trade_order_id 未跳转时返回订单号
     */
    public function paymentDo() {
		$trade_order_id = time();//商户网站内部ID，此处time()是演示数据

        $di = \PhalApi\DI();
        $cfg = $di->config->get('app.xunhupay');
        $appid              = $cfg['appid'];
        $appsecret          = $cfg['appsecret'];

        if (empty($this->trade_order_id)) {
            $this->trade_order_id = date('YmdHis') . rand(10000, 99999);
        }
		
		$data=array(
			'trade_order_id'=> $this->trade_order_id, //必须的，网站订单ID，唯一的，匹配[a-zA-Z\d\-_]+
			'payment'   => $this->payment,//必须的，支付接口标识：wechat(微信接口)|alipay(支付宝接口)
			'total_fee' => $this->total_fee,//人民币，单位精确到分(测试账户只支持0.1元内付款)
			'title'     => $this->title, //必须的，订单标题，长度32或以内

			'version'   => '1.1',//固定值，api 版本，目前暂时是1.1
			'lang'       => 'zh-cn', //必须的，zh-cn或en-us 或其他，根据语言显示页面
			'appid'     => $appid, //必须的，APPID
			'time'      => time(),//必须的，当前时间戳，根据此字段判断订单请求是否已超时，防止第三方攻击服务器
			'notify_url'=> $cfg['notify_url'], //必须的，支付成功异步回调接口
			'modal'=>null, //可空，支付模式 ，可选值( full:返回完整的支付网页; qrcode:返回二维码; 空值:返回支付跳转链接)
			'nonce_str' => str_shuffle(time())//必须的，随机字符串，作用：1.避免服务器缓存，2.防止安全密钥被猜测出来
		);

        if (!empty($this->return_url))
			$data['return_url']=$this->return_url;//必须的，支付成功后的跳转地址
        if (!empty($this->callback_url))
			$data['callback_url']= $this->callback_url;//必须的，支付发起地址（未支付或支付失败，系统会会跳到这个地址让用户修改支付信息）
        if (!empty($this->plugins))
			$data['plugins']   =$this->plugins;//根据自己需要自定义插件ID，唯一的，匹配[a-zA-Z\d\-_]+

        // 订单入库
        $orderData = array(
            'trade_order_id' => $data['trade_order_id'],
            'payment' => $data['payment'],
            'total_fee' => $data['total_fee'],
            'title' => $data['title'],
            'add_time' => time(),
            'nonce_str' => $data['nonce_str'],
            'plugins' => strval($this->plugins),
            'order_status' => 0,
        );
        $model = new \PhalApi\Xunhupay\Model\XunhupayOrder();
        try {
            $model->insert($orderData);
        } catch (\PDOException $ex) {
            // 重复下单
        }
		
		$hashkey =$appsecret;
		$data['hash']     = \XH_Payment_Api::generate_xh_hash($data,$hashkey);
		/**
		 * 个人支付宝/微信官方支付，支付网关：https://api.xunhupay.com
		 * 微信支付宝代收款，需提现，支付网关：https://pay.wordpressopen.com
		 */
        $url              = $cfg['api']['do'];
		
		try {
			$response     = \XH_Payment_Api::http_post($url, json_encode($data));
			/**
			 * 支付回调数据
			 * @var array(
			 *      order_id,//支付系统订单ID
			 *      url//支付跳转地址
			 *  )
			 */
			$result       = $response?json_decode($response,true):null;
			if(!$result){
                throw new InternalServerErrorException('发起支付失败');
			}
		
			$hash         = \XH_Payment_Api::generate_xh_hash($result,$hashkey);
			if(!isset( $result['hash'])|| $hash!=$result['hash']){
                throw new BadRequestException('签名错误');
			}
		
			if($result['errcode']!=0){
                throw new BadRequestException($result['errmsg'] . '，错误码：' . $result['errcode']);
			}
		
		
			$pay_url =$result['url'];
            // 直接跳转
            if ($this->isJump) {
                header("Location: $pay_url");
                exit;
            }
            return array('pay_url' => $pay_url, 'trade_order_id' => $this->trade_order_id);
		} catch (\Exception $e) {
			//echo "errcode:{$e->getCode()},errmsg:{$e->getMessage()}";
            \PhalApi\DI()->logger->error('虎皮椒发起支付失败，错误信息：' . $e->getMessage());
			//TODO:处理支付调用异常的情况
            throw new InternalServerErrorException('发起支付失败，错误信息请查看文件日志');
		}

    }

    /**
     * 订单查询接口
     * @desc 订单查询接口
     */
    public function orderQuery() {
        $di = \PhalApi\DI();
        $cfg = $di->config->get('app.xunhupay');
        $appid              = $cfg['appid'];
        $appsecret          = $cfg['appsecret'];

        $out_trade_order = $this->trade_order_id;//商户网站订单号

        // 获取订单
        $model = new \PhalApi\Xunhupay\Model\XunhupayOrder();
        $orderInfo = $model->getOrderInfo($out_trade_order);
        if (empty($orderInfo)) {
            throw new BadRequestException('订单不存在');
        }
        
        //out_trade_order，open_order_id 二选一
        $request=array(
            'appid'     => $appid, //必须的，APPID
        
            'out_trade_order'=> $out_trade_order, //网站订单号(out_trade_order，open_order_id 二选一)
            //'open_order_id'=> $open_order_id, //虎皮椒内部订单号，在下单时会返回，或支付后会异步回调(out_trade_order，open_order_id 二选一)
        
            'time'      => time(),//必须的，当前时间戳，根据此字段判断订单请求是否已超时，防止第三方攻击服务器
            'nonce_str' => str_shuffle(time())//必须的，随机字符串，作用：1.避免服务器缓存，2.防止安全密钥被猜测出来
        );
        
        $request['hash'] =  \XH_Payment_Api::generate_xh_hash($request,$appsecret);
        
        $url              = $cfg['api']['query'];
        
        try {
            $response     = \XH_Payment_Api::http_post($url, http_build_query($request));
            /**
             * 支付回调数据
             * @var array(
             *      status,//OD：已支付  WP:未支付  CD 已取消
             *  )
             */
            $result       = $response?json_decode($response,true):null;
            if(!$result){
                throw new InternalServerErrorException('Internal server error:'.$response);
            }

            // 更新订单为已支付
            if (!empty($result['data']['status']) && $result['data']['status'] == 'OD' && $orderInfo['order_status'] == 0) {
                $model->update($orderInfo['id'], array('order_status' => 1));
            }
        
            return $result;
        } catch (\Exception $e) {
            //echo "errcode:{$e->getCode()},errmsg:{$e->getMessage()}";
            //TODO:处理支付调用异常的情况
            \PhalApi\DI()->logger->error('虎皮椒发起支付失败，错误信息：' . $e->getMessage());
			//TODO:处理支付调用异常的情况
            throw new InternalServerErrorException('查询失败，错误信息请查看文件日志');
        }
    }
}
