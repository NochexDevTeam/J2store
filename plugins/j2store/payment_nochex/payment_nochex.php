<?php
/*------------------------------------------------------------------------
# com_j2store - J2Store
# ------------------------------------------------------------------------
# author    Ramesh Elamathi - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2014 - 19 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://j2store.org
# Technical Support:  Forum - http://j2store.org/forum/index.html
-------------------------------------------------------------------------*/

/** ensure this file is being included by a parent file */
defined('_JEXEC') or die('Restricted access');

require_once (JPATH_ADMINISTRATOR.'/components/com_j2store/library/plugins/payment.php');

class plgJ2StorePayment_nochex extends J2StorePaymentPlugin
{
	/**
	 * @var $_element  string  Should always correspond with the plugin's filename,
	 *                         forcing it to be unique
	 */
    var $_element    = 'payment_nochex';

	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @param 	array  $config  An array that holds the plugin configuration
	 * @since 2.5
	 */
	function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage( 'com_j2store', JPATH_ADMINISTRATOR );
	}
	

    /**
     * Prepares the payment form
     * and returns HTML Form to be displayed to the user
     * generally will have a message saying, 'confirm entries, then click complete order'
     *
     * @param $data     array       form post data
     * @return string   HTML to display
     */
    function _prePayment( $data )
    {
        // prepare the payment form
  
  $app = JFactory::getApplication();
  $session = JFactory::getSession();
  
// Gather Order Ids, amount, and variables  

  $order_id = $data['order_id'];
  $orderpayment_id = $data['orderpayment_id'];
  $amount = $data['orderpayment_amount'];
  
  $merchant_id = $this->params->get("merchant_id");
  $test_mode = $this->params->get("sandbox");
  $xmlCollection = $this->params->get("xmlcollection");

// Check Test Mode has been enabled by the Merchant
if ($test_mode == 1){
	$test_transaction = "100";
}else{
	$test_transaction = "";
}
  
		F0FTable::addIncludePath ( JPATH_ADMINISTRATOR . '/components/com_j2store/tables' );
		$order = F0FTable::getInstance ( 'OrderInfo', 'J2StoreTable' );
	
// Get Product Details from the Shopping Cart
		$cart = F0FTable::getInstance('Cart', 'J2StoreTable')->getClone();
		
				$cartitem_model = F0FModel::getTmpInstance('Cartitems', 'J2StoreModel');
				$cartitem_model->setState('filter_cart', $data['orderpayment_id']);
				$items = $cartitem_model->getList();
				
$products = array_unique($items, SORT_REGULAR);
$xmlcollect = "<items>";
foreach($products as &$item) {
	
	$description = "Product ID: " . $item->product_id . ", Product Name: " . $item->sku . ", Product Quantity: " . number_format($item->product_qty,0) . ", Product Price: ". number_format($item->price,2);
	$xmlcollect .= "<item><id>".$item->product_id."</id><name>".$item->sku."</name><description>".$item->sku."</description><quantity>".number_format($item->product_qty,0)."</quantity><price>".number_format($item->price,2)."</price></item>";
}
$xmlcollect .= "</items>";

// Check XML collection has been enabled by the Merchant
if ($xmlCollection ==1){

$description = "Order created for: " . $order_id;

}else{

$xmlcollect = "";

}

// Billing Address Details
$billing_fullname = $order->billing_first_name . ", " . $order->billing_last_name;
$billing_address = $order->billing_address_1 . ", " . $order->billing_address_2;
$billing_city = $order->billing_city;
$billing_postcode = $order->billing_zip;

$delivery_fullname = $order->shipping_first_name . ", " . $order->shipping_last_name;
$delivery_address = $order->shipping_address_1 . ", " . $order->shipping_address_2;
$delivery_city = $order->shipping_city;
$delivery_postcode = $order->shipping_zip;

// Phone Number
$customer_phone_number = $order->billing_phone_1;

// Email Address
$email_address =  str_replace("{","",$order->all_billing);
$email_address =  str_replace("}","",$email_address);
$email_address =  str_replace(":","",$email_address);
$email_address =  str_replace(",","",$email_address);
$email_address =  str_replace('"','',$email_address);
$email_address =  str_replace("emaillabelJ2STORE_EMAILvalue",'',$email_address);

$callback_url = JROUTE::_(JURI::root() . 'index.php?option=com_j2store&view=checkout&task=confirmPayment&orderpayment_type=payment_nochex&paction=display&id='.$order_id);  
$success_url = JROUTE::_(JURI::root() . 'index.php?option=com_j2store&view=checkout&task=confirmPayment&orderpayment_type=payment_nochex&paction=display&id='.$order_id);  
$cancel_url = JROUTE::_(JURI::root() . 'index.php?option=com_j2store&view=carts&orderpayment_type=payment_nochex&paction=display&id='.$order_id);  

$html = "<form action=\"https://secure.nochex.com/default.aspx\" method=\"post\" name=\"nochexForm\">
    <input type=\"hidden\" name=\"order_id\" value=\"".$order_id."\">    
    <input type=\"hidden\" name=\"merchant_id\" value=\"".$merchant_id."\">    
    <input type=\"hidden\" name=\"amount\" value=\"".$amount."\">    
    <input type=\"hidden\" name=\"description\" value=\"".$description."\">    
    <input type=\"hidden\" name=\"xml_item_collection\" value=\"".$xmlcollect."\">    
    <input type=\"hidden\" name=\"billing_fullname\" value=\"".$billing_fullname."\">    
    <input type=\"hidden\" name=\"billing_address\" value=\"".$billing_address."\">    
    <input type=\"hidden\" name=\"billing_city\" value=\"".$billing_city."\">    
    <input type=\"hidden\" name=\"billing_postcode\" value=\"".$billing_postcode."\">    
    <input type=\"hidden\" name=\"delivery_fullname\" value=\"".$delivery_fullname."\">    
    <input type=\"hidden\" name=\"delivery_address\" value=\"".$delivery_address."\">    
    <input type=\"hidden\" name=\"delivery_city\" value=\"".$delivery_city."\">    
    <input type=\"hidden\" name=\"delivery_postcode\" value=\"".$delivery_postcode."\">    
    <input type=\"hidden\" name=\"customer_phone_number\" value=\"".$customer_phone_number."\">    
    <input type=\"hidden\" name=\"email_address\" value=\"".$email_address."\">    
    <input type=\"hidden\" name=\"callback_url\" value=\"".$callback_url."\">    
    <input type=\"hidden\" name=\"test_success_url\" value=\"". $success_url."\" >    
    <input type=\"hidden\" name=\"success_url\" value=\"". $success_url."\" >    
    <input type=\"hidden\" name=\"cancel_url\" value=\"". $cancel_url."\" >    
    <input type=\"hidden\" name=\"test_transaction\" value=\"" .$test_transaction."\">    
    <input type=\"submit\" class=\"j2store_cart_button btn btn-primary\" value=\"Make a Payment\" />   
</form>";

        return $html;
    }

	/**
	 * Processes the payment form
	 * and returns HTML to be displayed to the user
	 * generally with a success/failed message
	 *
	 * @param $data array
	 *        	form post data
	 * @return string HTML to display
	 */
	function _postPayment($data) {
		// Process the payment
		$app = JFactory::getApplication ();
		$vars = new JObject ();
		$html = '';
		$order_id = $_POST["order_id"];
		
		/* APC Code */
if ($_POST["order_id"] != ""){
// Payment confirmation from http post 
ini_set("SMTP","mail.nochex.com" ); 
$header = "From: apc@nochex.com";

$your_email = $_POST["from_email"];  // your merchant account email address
  
// uncomment below to force a DECLINED response 
//$_POST['order_id'] = "1"; 

$url = "https://www.nochex.com/apcnet/apc.aspx";
$postvars = http_build_query($_POST);

$ch = curl_init ();
curl_setopt ($ch, CURLOPT_URL, $url);
curl_setopt ($ch, CURLOPT_POST, true);   
curl_setopt ($ch, CURLOPT_POSTFIELDS, $postvars);
curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
//curl_setopt ($ch, CURLOPT_SSLVERSION, 0);
curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
$response = curl_exec ($ch);
curl_close ($ch);

// stores the response from the Nochex server 
$debug = "IP -> " . $_SERVER['REMOTE_ADDR'] ."\r\n\r\nPOST DATA:\r\n"; 
foreach($_POST as $Index => $Value) 
$debug .= "$Index -> $Value\r\n"; 
$debug .= "\r\nRESPONSE:\r\n$response";
// Retrieves the order_id and save it as a variable which can be used in the update query to find a particular record in a database or datatable.	
	 $order_ID = $_POST['order_id']; 
// An email to check the order_ID
	$order = F0FTable::getInstance ( 'Order', 'J2StoreTable' )->getClone ();
		if ($order->load ( array (
				'order_id' => $order_id
		) )) {
		
	$order->transaction_id = $_POST["transaction_id"];
	$order->transaction_status = $response;
					
if (!strstr($response, "AUTHORISED")) {  // searches response to see if AUTHORISED is present if it isnt a failure message is displayed
    $msg = "APC was not AUTHORISED.\r\n\r\n$debug";  // displays debug message	
	
	$order->transaction_details = $msg . ", and this was a " . $_POST["status"] . " transaction";
	$order_state_id = $this->params->get ( 'payment_status', 4 );
	$order->update_status ( $order_state_id, true );		
	
} else { 
	$msg = "APC was AUTHORISED";

	$order->transaction_details = $msg . ", and this was a " . $_POST["status"] . " transaction";
	
	$order_state_id = $this->params->get ( 'payment_status', 4 );
	$order->update_status ( $order_state_id, true );		
	
	$order->reduce_order_stock();
	$order->payment_complete();
	$order->empty_cart();
	}
}
}else{

$html = "<div style=\"padding:20px;box-shadow:1px 1px 1px #666;border:1px solid #666;margin:20px;\"><i class=\"icon-star\" style=\"color:gold;float:right\"></i><h3>Congratulation's</h3><p>Your order: " . $data['order_id'] . " has been successful</p></div>";

return $html;
	 
	}
}
    /**
     * Prepares variables and
     * Renders the form for collecting payment info
     *
     * @return unknown_type
     */
    function _renderForm( $data )
    {
    	$user = JFactory::getUser();
        $vars = new JObject();
        $vars->onselection_text = $this->params->get('onselection', '');
        $html = $this->_getLayout('form', $vars);
        return $html;
    }
}
