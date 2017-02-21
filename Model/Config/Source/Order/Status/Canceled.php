<?php

namespace PayTabs\Express\Model\Config\Source\Order\Status;

/**
 * Order Statuses source model
 */
class Canceled extends \Magento\Sales\Model\Config\Source\Order\Status
{
    /**
     * @var string
     */
    protected $_stateStatuses = [
        \Magento\Sales\Model\Order::STATE_NEW,
        \Magento\Sales\Model\Order::STATE_CANCELED,
    ];
}