<?php

namespace PayTabs\Express\Block\Standard;

/**
 * @category   MagePsycho
 * @package    PayTabs_Express
 * @author     Raj KB <magepsycho@gmail.com>
 * @website    http://www.magepsycho.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Redirect extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \PayTabs\Express\Model\Payment\Standard
     */
    protected $_paymentMethod;

    /**
     * @var \Magento\Framework\Data\FormFactory
     */
    protected $_formFactory;

    /**
     * PayTabs constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \PayTabs\Express\Model\Payment\Standard $paymentMethod
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \PayTabs\Express\Model\Payment\Standard $paymentMethod,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_paymentMethod   = $paymentMethod;
        $this->_formFactory     = $formFactory;
    }

    protected function _toHtml()
    {
        $order = $this->getOrder();
        
        $form = $this->_formFactory->create();
        $form->setAction($this->_paymentMethod->getGatewayUrl())
             ->setId('paytabs_payment_checkout')
             ->setName('paytabs_payment_checkout')
             ->setMethod('POST')
             ->setUseContainer(true)
        ;

        $formFields = $this->_paymentMethod->buildRequest($order);
        foreach ($formFields as $field => $value) {
            $form->addField(
                $field,
                'hidden',
                [
                    'name'  => $field, 
                    'value' => $value
                ]
            );
        }

        $html  = '<html><body>';
        $html .= __('You will be redirected to PayTabs page in a few seconds.');
        $html .= $form->toHtml();
        #die($form->toHtml());
        $html .= '<script type="text/javascript">document.getElementById("paytabs_payment_checkout").submit();</script>';
        $html .= '</body></html>';

        return $html;
    }
}