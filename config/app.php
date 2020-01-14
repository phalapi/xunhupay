<?php

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
        'notify_url' => 'http://api.demo.com/xunhupay_notify.php', // TODO 成功支付后的回调地址
    ),
);
