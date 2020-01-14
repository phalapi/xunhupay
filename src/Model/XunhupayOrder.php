<?php
namespace PhalApi\Xunhupay\Model;

class XunhupayOrder extends \PhalApi\Model\NotORMModel {

    public function getTableName($id) {
        return 'xunhupay_order';
    }

    public function getOrderInfo($trade_order_id) {
        return $this->getORM()
            ->where('trade_order_id', $trade_order_id)
            ->fetchOne();
    }
}
