<?php

namespace PayTabs\Express\Model\Config\Source;

/**
 * @category   PayTabs Payment
 * @package    PayTabs_Expressmagento2
 * @author     Support <support@paytabs.com>
 * @website    https://www.paytabs.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Domaintype implements \Magento\Framework\Option\ArrayInterface
{
    const DOMAIN_TYPE_PRODUCTION  = 1;
    const DOMAIN_TYPE_DEVELOPMENT = 2;

    protected $_options;

    public function getAllOptions($withEmpty = false)
    {
        if (is_null($this->_options)) {
            $this->_options = array(
                array(
                    'value' => self::DOMAIN_TYPE_PRODUCTION,
                    'label' => __('Production'),
                ),
                array(
                    'value' => self::DOMAIN_TYPE_DEVELOPMENT,
                    'label' => __('Development'),
                ),
            );

        }
        $options = $this->_options;
        if ($withEmpty) {
            array_unshift($options, array('value' => '', 'label' => ''));
        }
        return $options;
    }

    public function getOptionsArray($withEmpty = true)
    {
        $options = array();
        foreach ($this->getAllOptions($withEmpty) as $option) {
            $options[$option['value']] = $option['label'];
        }
        return $options;
    }

    public function getOptionText($value)
    {
        $options = $this->getAllOptions(false);
        foreach ($options as $item) {
            if ($item['value'] == $value) {
                return $item['label'];
            }
        }
        return false;
    }

    public function toOptionArray()
    {
        return $this->getAllOptions();
    }

    public function toOptionHash($withEmpty = true)
    {
        return $this->getOptionsArray($withEmpty);
    }
}