<?php
namespace PayTabs\Express\Helper;

class Notification extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_NOTIFICATION_ENABLED         = 'payment/paytabs_standard/notification_enabled';
    const XML_PATH_NOTIFICATION_RECIPIENT_NAME  = 'general/store_information/name';
    const XML_PATH_NOTIFICATION_RECIPIENT_EMAIL = 'payment/paytabs_standard/recipient_email';
    const XML_PATH_NOTIFICATION_SENDER_NAME     = 'trans_email/ident_general/name';
    const XML_PATH_NOTIFICATION_SENDER_EMAIL    = 'trans_email/ident_general/email';

    /**
     * @var \PayTabs\Express\Helper\Data
     */
    protected $paytabsHelper;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \PayTabs\Express\Helper\Data $paytabsHelper
    ) {
        $this->paytabsHelper = $paytabsHelper;
        parent::__construct($context);
    }

    public function getSenderName()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_NOTIFICATION_SENDER_NAME,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSenderEmail()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_NOTIFICATION_SENDER_EMAIL,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getRecipientName()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_NOTIFICATION_RECIPIENT_NAME,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getRecipientEmail()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_NOTIFICATION_RECIPIENT_EMAIL,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function sendEmail(
        $fromEmail,
        $fromName,
        $toEmail,
        $toName,
        $subject,
        $body
    ) {
        try {

            $mail = new \Zend_Mail();
            $mail->setFrom($fromEmail, $fromName);
            $mail->addTo($toEmail, $toName);
            $mail->setSubject($subject);
            $mail->setBodyHtml($body);
            $mail->send();

        } catch(\Exception $e) {
            $exceptionMessage = 'An unexpected error occurred when trying to send email : ' . $e->getMassage();
            $exceptionMessage .= PHP_EOL . $e->__toString();
            $this->paytabsHelper->log($exceptionMessage);
        }
    }

    public function sendNotificationEmail($subject, $body)
    {
        $this->paytabsHelper->log('$subject::' . $subject);
        $this->paytabsHelper->log('$body::' . $body);
        $emails = $this->getRecipientEmail();
        if (empty($emails)) {
            return false;
        }

        $emails = preg_replace('/\s+/', '', $emails);
        $emails = explode(',', $emails);
        foreach ($emails as $email) {
            $this->sendEmail(
                $this->getSenderEmail(),
                $this->getSenderName(),
                $this->getRecipientEmail(),
                $email,
                $subject,
                $body
            );
        }

        return true;
    }
}