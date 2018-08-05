<?php
class Bwa_Garantipay_Model_Standard extends Mage_Payment_Model_Method_Abstract {
	protected $_code = 'garantipay';
	
	protected $_isInitializeNeeded      = true;
	protected $_canUseInternal          = true;
	protected $_canUseForMultishipping  = false;

    protected $_formBlockType = 'garantipay/form_pay';
	
	public function getOrderPlaceRedirectUrl() {
		return Mage::getUrl('garantipay/payment/redirect', array('_secure' => true));
	}
}
?>