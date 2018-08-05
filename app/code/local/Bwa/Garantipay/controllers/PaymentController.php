<?php
    /*
    Garantipay Payment Controller
    By: Junaid Bhura
    www.junaidbhura.com
    */

    class Bwa_Garantipay_PaymentController extends Mage_Core_Controller_Front_Action
    {

        //Buraya pos bilgileri girilecek(example.php'de buralar set ediliyor) >>>>>
        public $debugMode = false;
        public $debugUrlUse = false;
        public $version = "v0.01";
        public $mode = "TEST"; //Test ortamı "TEST", gerçek ortam için "PROD"
        public $terminalMerchantID = "7000679"; //Üye işyeri numarası
        public $terminalID = "30691297"; //Terminal numarası
        public $provUserID = "PROVOOS"; //Terminal prov kullanıcı adı
        public $provUserPassword = "123qweASD"; //Terminal prov kullanıcı şifresi
        public $garantiPayProvUserID = "PROVOOS"; //GarantiPay için prov kullanıcı adı
        public $garantiPayProvUserPassword = "123qweASD"; //GarantiPay için prov kullanıcı şifresi
        public $storeKey = "123qweASD"; //24byte hex 3D secure anahtarı
        public $successUrl = "?action=success"; //3D başarıyla sonuçlandığında yönlenecek sayfa
        public $errorUrl = "?action=error"; //3D başarısız olduğunda yönlenecek sayfa

        public $terminalID_;
        public $paymentUrl = "https://sanalposprovtest.garanti.com.tr/servlet/gt3dengine";
        public $debugPaymentUrl = "https://sanalposprovtest.garanti.com.tr/VPServlet";
        public $provisionUrl = "https://sanalposprov.garanti.com.tr/VPServlet"; //Provizyon için xml'in post edileceği adres
        public $currencyCode = "949"; //TRY=949, USD=840, EUR=978, GBP=826, JPY=392
        public $lang = "tr";
        public $paymentRefreshTime = "0"; //Ödeme alındıktan bekletilecek süre
        public $timeOutPeriod = "60";
        public $addCampaignInstallment = "N";
        public $totalInstallamentCount = "";
        public $installmentOnlyForCommercialCard = "N";

        //GarantiPay tanımlamalar
        public $useGarantipay = "Y"; //GarantiPay kullanımı: Y/N
        public $useBnsuseflag = "Y"; //Bonus kullanımı: Y/N
        public $useFbbuseflag = "Y"; //Fbb kullanımı: Y/N
        public $useChequeuseflag = "N"; //Çek kullanımı: Y/N
        public $useMileuseflag = "N"; //Mile kullanımı: Y/N
        public $companyName = 'BWA Digital';
        public $orderNo;
        public $amount;
        public $installmentCount = "0";
        public $cardName;
        public $cardNumber;
        public $cardExpiredMonth;
        public $cardExpiredYear;
        public $cardCVV;
        public $customerIP;
        public $customerEmail;
        public $orderAddress;

        public $mdStatuses = [
            0 => "Doğrulama başarısız, 3-D Secure imzası geçersiz",
            1 => "Tam doğrulama",
            2 => "Kart sahibi banka veya kart 3D-Secure üyesi değil",
            3 => "Kartın bankası 3D-Secure üyesi değil",
            4 => "Kart sahibi banka sisteme daha sonra kayıt olmayı seçmiş",
            5 => "Doğrulama yapılamıyor",
            7 => "Sistem hatası",
            8 => "Bilinmeyen kart numarası",
            9 => "Üye işyeri 3D-Secure üyesi değil",
        ];

        public function loadData()
        {
            $_order = new Mage_Sales_Model_Order();
            $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
            $_order->loadByIncrementId($orderId);

            $this->debugMode = $_order->getId();

            $this->companyName = '';
            $this->orderNo = $_order->getIncrementId(); //Her işlemde yeni sipariş numarası gönderilmeli
            $this->amount = number_format($_order->getGrandTotal(), '2');  //İşlem Tutarı 1 TL için 1.00 gönderilmeli
            $this->installmentCount = '';
            $this->storeKey = Mage::getStoreConfig('payment/garantipay/store_key');
            $this->garantiPayProvUserPassword = Mage::getStoreConfig('payment/garantipay/prov_user_password');
            $this->customerIP = $_SERVER['REMOTE_ADDR'];
            $this->customerEmail = $_order->getCustomerEmail();
            $this->cardName = '';
            $this->cardNumber = '';
            $this->terminalID_ = '0' . Mage::getStoreConfig('payment/garantipay/terminal_id');
            $this->cardExpiredMonth = '';
            $this->cardExpiredYear = '';
            $this->cardCVV = '';
            $this->successUrl = Mage::getUrl('garantipay/payment/response/', array('_secure' => true));
            $this->errorUrl = Mage::getUrl('garantipay/payment/response/', array('_secure' => true));


        }

        public function pay()
        {

            $this->provUserID = $this->garantiPayProvUserID;
            $this->provUserPassword = $this->garantiPayProvUserPassword;
            $params = [
                "secure3dsecuritylevel" => "CUSTOM_PAY",
                "txntype" => "gpdatarequest",
                "txnsubtype" => "sales",
                "garantipay" => $this->useGarantipay,
                "bnsuseflag" => $this->useBnsuseflag,
                "fbbuseflag" => $this->useFbbuseflag,
                "chequeuseflag" => $this->useChequeuseflag,
                "mileuseflag" => $this->useMileuseflag,
                "refreshtime" => $this->paymentRefreshTime,
            ];

            $params['companyname'] = $this->companyName;
            $params['apiversion'] = $this->version;
            $params['mode'] = $this->mode;
            $params['terminalprovuserid'] = Mage::getStoreConfig('payment/garantipay/prov_user_id');
            $params['terminaluserid'] = Mage::getStoreConfig('payment/garantipay/terminal_id');
            $params['terminalid'] = Mage::getStoreConfig('payment/garantipay/terminal_id');
            $params['terminalmerchantid'] = Mage::getStoreConfig('payment/garantipay/merchant_id');
            $params['orderid'] = $this->orderNo;
            $params['customeremailaddress'] = $this->customerEmail;
            $params['customeripaddress'] = $this->customerIP;
            $params['txnamount'] = $this->amount;
            $params['txncurrencycode'] = $this->currencyCode;
            $params['txninstallmentcount'] = "0";
            $params['successurl'] = $this->successUrl;
            $params['errorurl'] = $this->errorUrl;
            $params['lang'] = $this->lang;
            $params['txntimestamp'] = time();
            $params['txntimeoutperiod'] = $this->timeOutPeriod;
            $params['addcampaigninstallment'] = $this->addCampaignInstallment;
            $params['totallinstallmentcount'] = $this->totalInstallamentCount;
            $params['installmentonlyforcommercialcard'] = $this->installmentOnlyForCommercialCard;
            $SecurityData = strtoupper(sha1( Mage::getStoreConfig('payment/garantipay/prov_user_password') . $this->terminalID_));

            $HashData = strtoupper(sha1($this->terminalID . $params['orderid'] . $params['txnamount'] . $params['successurl'] . $params['errorurl'] . $params['txntype'] . $params['txninstallmentcount'] . $this->storeKey . $SecurityData));
            $params['secure3dhash'] = $HashData;

            return $params;

        }


        public function redirectAction()
        {

            $this->loadData();

            $this->loadLayout();
            $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'garantipay', array('template' => 'bwa/garantipay/redirect.phtml'));;

            $block->assign('params', $this->pay());
            $block->assign('data', $this);
            $this->getLayout()->getBlock('content')->append($block);
            $this->renderLayout();
        }

        public function getOnepage()
        {
            return Mage::getSingleton('checkout/type_onepage');
        }

        // The response action is triggered when your gateway sends back a response after processing the customer's payment
        public function responseAction()
        {
            if ($this->getRequest()->isPost()) {


                $_order = new Mage_Sales_Model_Order();
                $_order->loadByIncrementId($_POST['oid']);

                $params = $_POST;

                $orderId = $_POST['oid'];

                if ($params['mdstatus'] == 0 && $params['response'] == 'approved') {
                    $validated = true;
                } else {
                    $validated = false;
                }

                if ($validated) {
                    // Payment was successful, so update the order's state, send order email and move to the success page
                    $order = Mage::getModel('sales/order');
                    $order->loadByIncrementId($orderId);
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Ödeme işlemi başarıyla tamamlandı.');

                    $order->sendNewOrderEmail();
                    $order->setEmailSent(true);

                    $order->save();

                    Mage::getSingleton('checkout/session')->unsQuoteId();

                    Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure' => true));
                } else {
                    // There is a problem in the response we got
                    $this->cancelAction($params['mdstatus'] . ', ' . $params['errmsg']);

                    if (Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
                        if ($lastQuoteId = Mage::getSingleton('checkout/session')->getLastQuoteId()) {
                            $quote = Mage::getModel('sales/quote')->load($lastQuoteId);
                            $quote->setIsActive(true)->save();
                        }
                        Mage::getSingleton('core/session')->addError(Mage::helper('garantipay')->__('Ödeme işlemi sırasında bir hata oluştu. Hata mesajı : ' . $params['mdstatus'] . ', ' . $params['errmsg']));

                    }


                    Mage_Core_Controller_Varien_Action::_redirect('checkout/cart', array('_secure' => true));
                }
            } else
                Mage_Core_Controller_Varien_Action::_redirect('');
        }

        // The cancel action is triggered when an order is to be cancelled
        public function cancelAction($message = '')
        {
            if (Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
                $order = Mage::getModel('sales/order')->loadByIncrementId(Mage::getSingleton('checkout/session')->getLastRealOrderId());
                if ($order->getId()) {
                    // Flag the order as 'cancelled' and save it
                    $order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'Ödeme işlem sırasında hata oluştu. ' . $message)->save();
                }
            }


        }
    }