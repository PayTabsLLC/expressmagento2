<?php
namespace PayTabs\Express\Observer\Adminhtml;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * @category   MagePsycho
 * @package    PayTabs_Express
 * @author     Raj KB <magepsycho@gmail.com>
 * @website    http://www.magepsycho.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ControllerActionPredispatch implements ObserverInterface
{
    /**
     * @var \PayTabs\Express\Helper\Data
     */
    protected $_paytabsHelper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * ControllerActionPredispatch constructor.
     *
     * @param \PayTabs\Express\Helper\Data $paytabsHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \PayTabs\Express\Helper\Data $paytabsHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_paytabsHelper   = $paytabsHelper;
        $this->_messageManager       = $messageManager;
    }

    public function execute(Observer $observer)
    {
        $isValid          = $this->_paytabsHelper->isValid();
        $isActive         = $this->_paytabsHelper->isActive();
        $request          = $observer->getRequest();
        $fullActionName   = $request->getFullActionName();
        if ($isActive
            && !$isValid
            && 'adminhtml_system_config_edit' == $fullActionName
            && 'magepsycho_paytabs' == $request->getParam('section')
        ) {
            $this->_messageManager->addError($this->_paytabsHelper->getMessage());
        }
        return $this;

    }
}