<?php
/*
Plugin Name: WooCommerce - ThePay
Plugin URL: https://cleverstart.cz
Description: Přidá možnost platby přes ThePay
Version: 1.0.56
Author: Pavel Janíček
Author URI: https://cleverstart.cz
*/

require __DIR__ . '/vendor/autoload.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://plugins.cleverstart.cz/?action=get_metadata&slug=woothepay',
	__FILE__, //Full path to the main plugin file or functions.php.
	'woothepay'
);

add_action('plugins_loaded', 'woocommerce_cleverstart_thepay_init', 0);
function woocommerce_cleverstart_thepay_init(){
	if(!class_exists('WC_Payment_Gateway')) return;

  class WC_Cleverstart_Thepay extends WC_Payment_Gateway{
		public function __construct(){
      $this->id = 'thepay';
      $this->medthod_title = 'ThePay';
      $this->has_fields = false;

      $this->init_form_fields();
      $this->init_settings();

      $this->title = $this->settings['title'];
      $this->description = $this->settings['description'];
      $this->merchantId = $this->settings['merchantId'];
      $this->accountId = $this->settings['accountId'];
			$this->password = $this->settings['password'];
			$this->test_mode = $this->settings['test_mode'];


      add_action('check_thepay', array(&$this, 'check_thepay_response'));

      if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
             } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }

   }

	 function init_form_fields(){

       $this -> form_fields = array(
                'enabled' => array(
                    'title' => __('Povolit / Zakázat', 'woothepay'),
                    'type' => 'checkbox',
                    'label' => __('Povolit platby přes ThePay', 'woothepay'),
                    'default' => 'no'),
                'title' => array(
                    'title' => __('Název:', 'mrova'),
                    'type'=> 'text',
                    'description' => __('Zde můžete změnit název brány zobrazovaný během nákupu', 'woothepay'),
                    'default' => __('ThePay', 'woothepay')),
                'description' => array(
                    'title' => __('Popis:', 'woothepay'),
                    'type' => 'textarea',
                    'description' => __('Zobrazí popis platební brány během nákupu', 'woothepay'),
                    'default' => __('Zaplaťte rychle a snadno platební kartou.', 'woothepay')),
                'merchantId' => array(
                    'title' => __('ID Obchodníka', 'woothepay'),
                    'type' => 'text',
                    'description' => __('ID Obchodníka:')),
                'accountId' => array(
                    'title' => __('ID Účtu', 'woothepay'),
                    'type' => 'text',
                    'description' =>  __('ID Účtu:', 'woothepay'),
                ),
								'password' => array(
                    'title' => __('Heslo', 'woothepay'),
                    'type' => 'text',
                    'description' =>  __('Heslo:', 'woothepay'),
                ),
								'test_mode' => array(
									'title' => __('Testovací mód?', 'woothepay'),
									'type' => 'checkbox',
									'label' => __('Je brána v testovacím módu?', 'woothepay'),
									'default' => 'no'
								)
            );
    }

       public function admin_options(){
        echo '<h3>'.__('Platební brána ThePay', 'woothepay').'</h3>';
        echo '<p>'.__('ThePay je rychlá a spolehlivá platební brána pro příjem plateb kartou.').'</p>';
        echo '<table class="form-table">';
        // Generate the HTML For the settings form.
        $this -> generate_settings_html();
        echo '</table>';

    }

		function payment_fields(){
        if($this -> description) echo wpautop(wptexturize($this -> description));

    }

    /**
     * Process the payment
     **/
    public function process_payment($order_id){
        global $woocommerce;
				require_once "payConfig.php";
  			require_once (plugin_dir_path(__FILE__) . 'component/classes/helpers/TpUrlHelper.php');

				$order = new WC_Order( $order_id );
				$redirect_url = $order->get_checkout_order_received_url(). "&listener=woothepay";
				$order->update_status('on-hold', __( 'Očekáváme platbu', 'woothepay' ));
				if ( 'no' == $this->settings['test_mode'] ) {
						$isProd = true;
				}else {
					$isProd = false;
				}
				$thepayobject = new TpPayment(new PayConfig($isProd,$this->settings['merchantId'],$this->settings['accountId'],$this->settings['password']));
				$thepayobject->setValue($order->get_total());
				$thepayobject->setCurrency($order->get_currency());
    		$thepayobject->setMerchantData($order_id);
    		$thepayobject->setReturnUrl($redirect_url);
				$tpHelper = new TpUrlHelper($thepayobject);
    		$location = $tpHelper->getRedirectUrl();
				$order->reduce_order_stock();
				$woocommerce -> cart -> empty_cart();
				return array(
        'result' => 'success',
        'redirect' => $tpHelper->getRedirectUrl()
    );


    }



		public function check_thepay_response(){

				///Require TpReturnedPayment
				require_once(plugin_dir_path(__FILE__) . 'component/classes/TpReturnedPayment.php');
				///Require our configuration of thepay
				require_once "payConfig.php";
				$order_id = $_GET['merchantData'];
				$order = new WC_Order($order_id);
				if ( 'no' == $this->settings['test_mode'] ) {
						$isProd = true;
				}else {
					$isProd = false;
				}
				$returnedPayment = new TpReturnedPayment(new PayConfig($isProd,$this->settings['merchantId'],$this->settings['accountId'],$this->settings['password']));

				try{
				/// Verify the payment signature

				$returnedPayment->verifySignature();
				if($returnedPayment->getStatus() == TpReturnedPayment::STATUS_OK){
					$order -> payment_complete();
					$order -> add_order_note('Objednávka úspěšně zaplacena');
				}elseif ($returnedPayment->getStatus() ==TpReturnedPayment::STATUS_CANCELED ) {
					$order -> update_status('failed');
					$order -> add_order_note('Objednávka Stornována');
				}elseif ($returnedPayment->getStatus() == TpReturnedPayment::STATUS_ERROR) {
					$order -> update_status('error');
					$order -> add_order_note('Chyba objednávky');
				}elseif ($returnedPayment->getStatus() == TpReturnedPayment::STATUS_UNDERPAID) {
					$order -> update_status('failed');
					$order -> add_order_note('Objednávka Nezaplacena');
				}elseif ($returnedPayment->getStatus() == TpReturnedPayment::STATUS_WAITING) {
					$order -> update_status('pending');
					$order -> add_order_note('Čekáme na platbu');
				}else{
					$order -> update_status('failed');
					$order -> add_order_note('Chyba objednávky');
				}

			}catch (Exception $e){
				$order -> update_status('failed');
				$order -> add_order_note('Chyba objednávky');
			} catch(TpMissingParameterException $e){
				$order -> update_status('failed');
				$order -> add_order_note('Objednávka Stornována, nedostatek parametrů');
			}


		}



}

}

/**
	* Add the Gateway to WooCommerce
	**/
 function woocommerce_add_cleverstart_thepay_gateway($methods) {
		 $methods[] = 'WC_Cleverstart_Thepay';
		 return $methods;
 }

 add_filter('woocommerce_payment_gateways', 'woocommerce_add_cleverstart_thepay_gateway' );

add_action('init', 'woocommerce_thepay_gateway_pingback');



function woocommerce_thepay_gateway_pingback(){

	if ( isset( $_GET['listener'] ) && $_GET['listener'] == 'woothepay' ) {

	 WC()->payment_gateways();
 	 do_action('check_thepay');
  }
}
