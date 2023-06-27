<?php

///Require TpMerchantConfig
require_once(plugin_dir_path(__FILE__) . 'component/classes/TpMerchantConfig.php');



///Class with our thepay configuration, it extends TpMerchantConfig
class PayConfig extends TpMerchantConfig {

	///Our id
	public $merchantId;

	///Our account id
	public $accountId;

	///Our password
	public $password;

	///Url of thepay gate
	public $gateUrl;

	public function __construct($isProd,$sentmerchantId='',$sentaccountId='',$sentpassword=''){
		if($isProd){
			if(!empty($sentmerchantId)){
        $this->merchantId = $sentmerchantId;
			}
			if(!empty($sentaccountId)){
				$this->accountId = $sentaccountId;
			}
			if(!empty($sentpassword)){
				$this->password = $sentpassword;
			}
			$this->gateUrl = 'https://www.thepay.cz/gate/';
		}else{
			 $this->merchantId = 1;
			 $this->accountId = 3;
			 $this->password = 'my$up3rsecr3tp4$$word';
			 $this->gateUrl = "https://www.thepay.cz/demo-gate/";
		}
	}

}
