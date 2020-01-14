<?php
namespace PhalApi\Xunhupay\Model;

class XunhupayOrder extends \PhalApi\Model\NotORMModel {

    public function getTableName($id) {
        return 'xunhupay_order';
    }
}
