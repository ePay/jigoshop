<?php
/**
 * ePay Gateway
 *  
 */

/**
 * Add the gateway to Jigoshop
 */
function add_epay_gateway( $methods ) {
	$methods[] = 'epay';

	return $methods;
}
add_filter( 'jigoshop_payment_gateways', 'add_epay_gateway', 20 );

class epay extends jigoshop_payment_gateway {

	public function __construct(){

		parent::__construct();
		
		$options = Jigoshop_Base::get_options();

		$this->id = 'epay';
		$this->icon = '';
		$this->has_fields = false;
		$this->enabled = $options->get_option('jigoshop_epay_enabled');
		$this->title = $options->get_option('jigoshop_epay_title');
		$this->merchant = $options->get_option('jigoshop_epay_merchant');
		$this->md5key = $options->get_option('jigoshop_epay_md5key');
		$this->windowid = $options->get_option('jigoshop_epay_windowid');
		$this->instantcapture = $options->get_option('jigoshop_epay_instantcapture');
		$this->group = $options->get_option('jigoshop_epay_group');
		$this->mailreceipt = $options->get_option('jigoshop_epay_mailreceipt');
		$this->ownreceipt = $options->get_option('jigoshop_epay_ownreceipt');
		$this->notify_url = jigoshop_request_api::query_request('?js-api=JS_Gateway_EPay', false);

		add_action('receipt_epay', array($this, 'receipt_page'));
		
		add_action('jigoshop_api_js_gateway_epay', array($this, 'successful_request'));
		
	}


	/**
	 * Default Option settings for WordPress Settings API using the Jigoshop_Options class
	 * These will be installed on the Jigoshop_Options 'Payment Gateways' tab by the parent class 'jigoshop_payment_gateway'
	 */
	protected function get_default_options(){

		$defaults = array();

		// Define the Section name for the Jigoshop_Options
		$defaults[] = array(
			'name' => sprintf(__('ePay Payment Solutions', 'jigoshop'), '<img style="vertical-align:middle;margin-top:-4px;margin-left:10px;" src="'.jigoshop::assets_url().'/assets/images/icons/epay.png" alt="ePay">'),
			'type' => 'title',
			'desc' => __('Accept payments by using the ePay Payment Gateway.', 'jigoshop')
		);

		// List each option in order of appearance with details
		$defaults[] = array(
			'name' => __('Enable', 'jigoshop'),
			'desc' => '',
			'tip' => '',
			'id' => 'jigoshop_epay_enabled',
			'std' => 'yes',
			'type' => 'checkbox',
			'choices' => array(
				'no' => __('No', 'jigoshop'),
				'yes' => __('Yes', 'jigoshop')
			)
		);
		
		$defaults[] = array(
			'name' => __('Method Title', 'jigoshop'),
			'desc' => '',
			'tip' => __('This controls the title which the user sees during checkout.', 'jigoshop'),
			'id' => 'jigoshop_epay_title',
			'std' => __('ePay Payment Solutions', 'jigoshop'),
			'type' => 'text'
		);

		$defaults[] = array(
			'name' => __('Description', 'jigoshop'),
			'desc' => '',
			'id' => 'jigoshop_epay_description',
			'std' => '',
			'type' => 'text'
		);
		
		$defaults[] = array(
			'name' => __('Merchantnumber', 'jigoshop'),
			'desc' => '',
			'id' => 'jigoshop_epay_merchant',
			'std' => '',
			'type' => 'text'
		);
		
		$defaults[] = array(
			'name' => __('MD5 Key', 'jigoshop'),
			'desc' => '',
			'id' => 'jigoshop_epay_md5key',
			'std' => '',
			'type' => 'text'
		);
		
		$defaults[] = array(
			'name' => __('Window ID', 'jigoshop'),
			'desc' => '',
			'id' => 'jigoshop_epay_windowid',
			'std' => '',
			'type' => 'text'
		);
		
		$defaults[] = array(
			'name' => __('Instant capture', 'jigoshop'),
			'desc' => '',
			'id' => 'jigoshop_epay_instantcapture',
			'std' => 'no',
			'type' => 'checkbox',
			'choices' => array(
				'no' => __('No', 'jigoshop'),
				'yes' => __('Yes', 'jigoshop')
			)
		);
		
		$defaults[] = array(
			'name' => __('Group', 'jigoshop'),
			'desc' => '',
			'id' => 'jigoshop_epay_group',
			'std' => '',
			'type' => 'text'
		);
		
		$defaults[] = array(
			'name' => __('Auth Mail', 'jigoshop'),
			'desc' => '',
			'id' => 'jigoshop_epay_mailreceipt',
			'std' => '',
			'type' => 'text'
		);

		$defaults[] = array(
			'name' => __('Own receipt', 'jigoshop'),
			'desc' => '',
			'id' => 'jigoshop_epay_ownreceipt',
			'std' => 'no',
			'type' => 'checkbox',
			'choices' => array(
				'no' => __('No', 'jigoshop'),
				'yes' => __('Yes', 'jigoshop')
			)
		);

		return $defaults;
	}

	/**
	 * There are no payment fields for ePay, but we want to show the description if set.
	 **/
	function payment_fields(){
		if($this->description){
			echo wpautop(wptexturize($this->description));
		}
	}
	
	function yes_no_to_int($string)
	{
		if($string == "yes")
			return 1;
		
		return 0;
	}

	/**
	 * Generate the epay button link
	 **/
	public function generate_epay_form($order_id){
		$order = new jigoshop_order($order_id);
		
		// filter redirect page
		$checkout_redirect = apply_filters('jigoshop_get_checkout_redirect_page_id', jigoshop_get_page_id('thanks'));
		
		$epay_args = array
		(
			'merchantnumber' => $this->merchant,
			'windowstate' => 3,
			'instantcallback' => 1,
			'windowid' => $this->windowid,
			'instantcapture' => $this->yes_no_to_int($this->instantcapture),
			'group' => $this->group,
			'mailreceipt' => $this->mailreceipt,
			'ownreceipt' => $this->yes_no_to_int($this->ownreceipt),
			'amount' => $order->order_total * 100,
			'orderid' => $order_id,
			'currency' => Jigoshop_Base::get_options()->get_option('jigoshop_currency'),
			'callbackurl' => $this->notify_url,
			'accepturl' => add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink($checkout_redirect))),
			'cancelurl' => $order->get_cancel_order_url()
		);
		
		if(strlen($this->md5key) > 0)
			$epay_args["hash"] = md5(implode("", array_values($epay_args)) . $this->md5key);
		
		$url = "https://ssl.ditonlinebetalingssystem.dk/integration/ewindow/Default.aspx";
		
		return jigoshop_render_result('gateways/epay', array(
			'url' => $url,
			'fields' => $epay_args,
		));
	}

	/**
	 * Process the payment and return the result
	 */
	function process_payment($order_id){
		$order = new jigoshop_order($order_id);

		return array(
			'result' => 'success',
			'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(jigoshop_get_page_id('pay'))))
		);
	}

	/**
	 * Receipt_page
	 */
	function receipt_page($order){
		echo '<p>'.__('Thank you for your order, please click the button below to pay with ePay.', 'jigoshop').'</p>';
		echo $this->generate_epay_form($order);
	}

	/**
	 * Successful payment processing
	 */
	function successful_request(){
		
		$posted = $_GET;
		
		$posted = stripslashes_deep($posted);
		
		$order = new jigoshop_order((int)$posted["orderid"]);
		if(strlen($this->md5key) > 0)
		{
			foreach($posted as $key => $value)
			{
				if($key != "hash")
					$var .= $value;
			}
			
			$genstamp = md5($var . $this->md5key);
			if($genstamp != $posted["hash"])
			{
				echo "MD5 error";
				error_log('MD5 check failed for ePay callback with order_id:' . $posted["wooorderid"]);
				status_header(500);
				exit;
			}
		}
		
		if($order->status !== 'completed')
		{
			$order->add_order_note(__('Callback payment completed', 'jigoshop'));
			$order->payment_complete();
		}
		
		echo "OK";
	}

	public function process_gateway($subtotal, $shipping_total, $discount = 0){

		return true;
	}
}
