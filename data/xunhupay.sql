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

