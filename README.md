# PhalApi 2.x 虎皮椒支付扩展

## 安装
使用composer命令进行安装：  
```
$ composer require phalapi/xunhupay
```

安装好后，还需要在根目录的composer.json文件的psr-4中添加配置，以便在线接口文档可以加载扩展里面的接口。
```
{
    "autoload": {
        "psr-4": {
            "PhalApi\\Xunhupay\\":"vendor/phalapi/xunhupay/src",
            "App\\": "src/app"
        }
    }

}
```

此外，还需要把./vendor/phalapi/xunhupay/public/xunhupay_notify.php文件复制到./public目录下，以便作为支付回调入口提供给虎皮椒调用。出于安全性考虑，文件名可以自行修改，但在配置时要同步修改。

在数据库中以下创建虎皮椒订单表：
```sql
CREATE TABLE `xunhupay_order` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `trade_order_id` varchar(32) DEFAULT '' COMMENT '商户订单号',
      `payment` varchar(20) DEFAULT NULL COMMENT '支付接口标识',
      `total_fee` decimal(18,2) DEFAULT NULL COMMENT '订单金额(元)。单位为人民币，精确到分',
      `title` varchar(128) DEFAULT NULL COMMENT '订单标题',
      `add_time` int(11) DEFAULT NULL COMMENT '当前时间戳',
      `nonce_str` varchar(32) DEFAULT NULL COMMENT '随机值',
      `plugins` varchar(128) DEFAULT NULL COMMENT '备注',
      `order_status` int(11) DEFAULT '0' COMMENT '支付状态，0待支付1已支付',
      PRIMARY KEY (`id`),
      UNIQUE KEY `trade_order_id` (`trade_order_id`)
) ENGINE=InnoDB;

```

## 配置
在./config/app.php配置中，添加以下虎皮椒支付扩展配置，并根据自己的情况修改里面的appid、appsecret、notify_url。  
```php
return array(
    // 虎皮椒支付扩展配置
    'xunhupay' => array(
        // 接口地址，一般不需要修改
        'api' => array(
            'do' => 'https://api.xunhupay.com/payment/do.html',
            'query' => 'https://api.xunhupay.com/payment/query.html',
        ),
        'appid' => '2147483647', // TODO 修改成你的APPID
        'appsecret' => '160130736b1ac0d54ed7abe51e44840b', // TODO 修改成你的密钥
        'notify_url' => 'http://你的接口域名/xunhupay_notify.php', // TODO 成功支付后的回调地址
    ),
);

```

## 接口

 + 发起支付接口：PhalApi\Xunhupay.Xunhupay.PaymentDo
 + 查询支付接口：PhalApi\Xunhupay.Xunhupay.OrderQuery
 + 回调入口：http://你的接口域名/xunhupay_notify.php

## 示例效果

1、调用发起支付接口，可以选择直接接口跳转到支付页面，也可以先返回接口json结果然后客户端再跳转。例如返回：
```
{
    "ret": 200,
    "data": {
        "pay_url": "https://api.xunhupay.com/payments/wechat/index?id=20201381209&nonce_str=8976995811&time=1578991869&appid=2147483647&hash=51de5fa1a6cc9d4a0f6182d07970f927",
        "trade_order_id": "11888888898"
    },
    "msg": ""
}
```

打开pay_url链接，进入在线支付页面，如：  
![](http://cdn7.okayapi.com/yesyesapi_20200114164919_6b1a132621eefbb892458860c185eff9.png)


2、支付成功后，会回调到xunhupay_notify.php  

手机扫码后，  
![](http://cdn7.okayapi.com/yesyesapi_20200114165406_0ae1fbbdbadd6c96d31190c2caec35d6.png)
  
3、最后进行订单查询，当未支付时返回：
```
{
    "ret": 200,
    "data": {
        "errcode": 0,
        "errmsg": "",
        "data": {
            "open_order_id": "20201381200",
            "total_amount": "0.01",
            "title": "test",
            "status": "WP",
            "payment_method": "wechat",
            "transaction_id": null,
            "order_date": "2020-01-14 16:48:28",
            "paid_date": null,
            "pay_url": "weixin://wxpay/bizpayurl?pr=8R4Z3LI",
            "out_trade_order": "11888888898"
        }
    },
    "msg": ""
}
```
已支付后，返回：  
```
{
    "ret": 200,
    "data": {
        "errcode": 0,
        "errmsg": "",
        "data": {
            "open_order_id": "20201381209",
            "total_amount": "0.01",
            "title": "test",
            "status": "OD",
            "payment_method": "wechat",
            "transaction_id": "4200000504202001141029026607",
            "paid_date": "2020-01-14 16:52:01",
            "pay_url": "weixin://wxpay/bizpayurl?pr=6Mprr2S",
            "out_trade_order": "11888888898"
        }
    },
    "msg": ""
}
```

4、数据库支付订单纪录

![](http://cdn7.okayapi.com/yesyesapi_20200114165529_dd26de603414f21531797cafecef94ba.png)

## 参考
 + [虎皮椒开发文档](https://www.xunhupay.com/doc/api/search.html)
 

