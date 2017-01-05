<?php

namespace PayTabs\Express\Logger\Handler;

use Monolog\Logger;
use Magento\Framework\Logger\Handler\Base;

/**
 * @category   MagePsycho
 * @package    PayTabs_Express
 * @author     Raj KB <magepsycho@gmail.com>
 * @website    http://www.magepsycho.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Custom extends Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/paytabs_express.log';

    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;
}