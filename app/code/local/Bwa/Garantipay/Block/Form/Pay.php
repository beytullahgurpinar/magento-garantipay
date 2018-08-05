<?php

    class Bwa_Garantipay_Block_Form_Pay extends Mage_Core_Block_Template
    {
        protected function _construct()
        {
            parent::_construct();

            $this->setTemplate('bwa/garantipay/form/pay.phtml');
        }
    }