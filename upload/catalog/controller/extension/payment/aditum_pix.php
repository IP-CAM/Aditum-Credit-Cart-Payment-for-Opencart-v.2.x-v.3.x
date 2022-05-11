<?php
class ControllerExtensionPaymentAditumPix extends Controller {

	private $total;
	private $order_status_id;
	private $geo_zone_id;
	private $status;
	private $sort_order;
	private $environment;
	private $titulo_gateway;
	private $descricao_gateway;
	private $merchant_cnpj;
	private $merchant_token;
	private $campo_documento;
	private $campo_numero;
	private $campo_complemento;
	private $campo_bairro;
	private $tipo_antifraude;
	private $token_antifraude;

	public function init_config() {
		$this->total = $this->config->get('payment_aditum_pix_total');
		$this->order_status_id = $this->config->get('payment_aditum_pix_order_status_id');
		$this->geo_zone_id = $this->config->get('payment_aditum_pix_geo_zone_id');
		$this->status = $this->config->get('payment_aditum_pix_status');
		$this->sort_order = $this->config->get('payment_aditum_pix_sort_order');
		$this->environment = $this->config->get('payment_aditum_pix_modo');
		$this->titulo_gateway = $this->config->get('payment_aditum_pix_titulo_gateway');
		$this->descricao_gateway = $this->config->get('payment_aditum_pix_descricao_gateway');
		$this->merchant_cnpj = $this->config->get('payment_aditum_pix_cnpj');
		$this->merchant_token = $this->config->get('payment_aditum_pix_merchant_token');
		$this->campo_documento = $this->config->get('payment_aditum_pix_campo_documento');
		$this->campo_numero = $this->config->get('payment_aditum_pix_campo_numero');
		$this->campo_complemento = $this->config->get('payment_aditum_pix_campo_complemento');
		$this->campo_bairro = $this->config->get('payment_aditum_pix_campo_bairro');
		$this->tipo_antifraude = $this->config->get('payment_aditum_pix_tipo_antifraude');
		$this->token_antifraude = $this->config->get('payment_aditum_pix_token_antifraude');
	}

	public function index() {
		$this->init_config();
		$data['tipo_antifraude'] = $this->tipo_antifraude;
		$data['token_antifraude'] = $this->token_antifraude;
		return $this->load->view('extension/payment/aditum_pix', $data);
	}

	public function confirm() {
		if ( ! isset( $_REQUEST['aditum_checkbox'] ) ) {
			$this->response->addHeader('Content-Type: application/json');
			return $this->response->setOutput(json_encode(['error' => 'Aceite os TERMOS & CONDIÇÕES para continuar']));	
		}
		$this->init_config();
		$json = array();
		$this->load->model('checkout/order');
		
		$data['order_id'] = $this->session->data['order_id'];
		$data['order_info'] = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$data['custom_fields'] = $this->getCustomerExtras($data['order_info']);

		$this->createTransaction($data);
		
	}

	private function getCustomerExtras($order) {
		$this->load->model('account/custom_field');
		$this->load->model('account/customer');
		$customer = $this->model_account_customer->getCustomer($order['customer_id']);
		if(!isset($customer['customer_group_id'])) {
			$customer['customer_group_id'] = 1;
		}
		$custom_fields = $this->model_account_custom_field->getCustomFields($customer['customer_group_id']);
		foreach($custom_fields as $custom_field){
			if($custom_field['location'] == 'account'){
				$data[$custom_field['custom_field_id']] = $order['custom_field'][$custom_field['custom_field_id']];
			}elseif($custom_field['location'] == 'address'){
				$data[$custom_field['custom_field_id']] = $order['payment_custom_field'][$custom_field['custom_field_id']];
			}
		}

		return $data;
	}

	private function createTransaction($data) {

		require DIR_SYSTEM . 'library/vendor/autoload.php';

		$this->load->model('extension/payment/aditum');

		$order = $data['order_info'];	
		
		$amount = number_format($order['total'], 2, '', '');

		AditumPayments\ApiSDK\Configuration::initialize();

		$environment = $this->environment;

		if ( 'sandbox' == $this->environment ) {
			AditumPayments\ApiSDK\Configuration::setUrl( AditumPayments\ApiSDK\Configuration::DEV_URL );
		}

		$config = get_object_vars($this);

		AditumPayments\ApiSDK\Configuration::setCnpj( $this->merchant_cnpj );
		AditumPayments\ApiSDK\Configuration::setMerchantToken( $this->merchant_token );
		AditumPayments\ApiSDK\Configuration::setlog( false );
		$login = AditumPayments\ApiSDK\Configuration::login();

		$telephone = preg_replace('/[^\d]+/i', '', $order['telephone']);

		$customer_phone_area_code = substr( $telephone, 0, 2 );
		$customer_phone           = substr( $telephone, 2 );

		$gateway = new AditumPayments\ApiSDK\Gateway();
		$pix  = new AditumPayments\ApiSDK\Domains\Pix();

        
		$items = $this->cart->getProducts();
		$this->load->model('catalog/product');		
		foreach($items as $item) {
			$product_info = $this->model_catalog_product->getProduct($item['product_id']);
			$pix->products->add(
				$item['name'], 
				$product_info['sku'],
				str_replace('.', '', number_format($item['price'], 2)),
				$item['quantity']
			);
		}

		$pix->setMerchantChargeId($order['order_id']);

		// ! Customer
		$pix->customer->setId( $order['order_id'] );
		$pix->customer->setName( $order['payment_firstname'] . ' ' . $order['payment_lastname'] );
		$pix->customer->setEmail( $order['email'] );

		$campo_documento = $this->campo_documento;

		$count = strlen( $data['custom_fields'][$this->campo_documento] ) ;

		if ( strlen( $data['custom_fields'][$this->campo_documento] ) > 11 ) 
		{
			$pix->customer->setDocumentType( AditumPayments\ApiSDK\Enum\DocumentType::CNPJ );
		} 
		else 
		{
			$pix->customer->setDocumentType( AditumPayments\ApiSDK\Enum\DocumentType::CPF );
		}

		$documento = preg_replace( '/[^\d]+/i', '', $data['custom_fields'][$this->campo_documento] );
		$pix->customer->setDocument( $documento );

		// ! Customer->address
		$pix->customer->address->setStreet( $order['payment_address_1'] );
		$pix->customer->address->setNumber( $data['custom_fields'][$this->campo_numero] );
		$pix->customer->address->setNeighborhood( $order['payment_address_2'] );
		$pix->customer->address->setCity( $order['payment_city'] );
		$pix->customer->address->setState( $order['payment_zone_code'] );
		$pix->customer->address->setCountry( $order['payment_iso_code_2'] );
		$pix->customer->address->setZipcode( $order['payment_postcode'] );
		$pix->customer->address->setComplement( $data['custom_fields'][$this->campo_complemento] );

		// ! Customer->phone
		$pix->customer->phone->setCountryCode( '55' );
		$pix->customer->phone->setAreaCode( $customer_phone_area_code );
		$pix->customer->phone->setNumber( $customer_phone );
		$pix->customer->phone->setType( AditumPayments\ApiSDK\Enum\PhoneType::MOBILE );

		// ! Transactions
		$pix->transactions->setAmount( $amount );
		$res = $gateway->charge( $pix );


		if ( isset( $res['status'] ) ) {
			if ( AditumPayments\ApiSDK\Enum\ChargeStatus::PRE_AUTHORIZED === $res['status'] ) {
				$this->model_extension_payment_aditum->save_data($this->session->data['order_id'], json_encode($res));
				$this->load->model('checkout/order');
				$checkout = true;
				if ( 'sandbox' == $this->environment ) {
					$url = AditumPayments\ApiSDK\Configuration::DEV_URL;
				}
				else {
					$url = AditumPayments\ApiSDK\Configuration::PROD_URL;
				}
					
				$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_aditum_pix_order_status_id'), "Pedido realizado com sucesso", true);
				
				$json['success'] = true;
				$json['redirect'] = $this->url->link('checkout/success') . '&order_id=' . $this->session->data['order_id'];
			}
		} else {
			$message = json_decode($res['httpMsg']);
			if($message && isset($message->errors) && is_array($message->errors) && count($message->errors)) {
				$json['error'] = implode("\n", array_map(function($error){ return $error->message; }, $message->errors));
			}
			else {
				$json['error'] = 'Houve uma falha ao finalizar. Tente novamente.';
			}
		}
		// $json = get_defined_vars();
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));	
	}
	
	/**
	 * Webhook para pegar as notificações enviadas pela Aditum
	 *
	 * @return void
	 */
	public function webhook() {
		$input = file_get_contents('php://input');
		if(empty($input)){ 
			$input = $_POST;
		}
		else {
			$input = json_decode($input, true);
		}
		$order_id = $input['Transactions'][0]['MerchantOrderId'];
		$this->load->model('checkout/order');
		$order = $this->model_checkout_order->getOrder($order_id);
		if( $order ){
			if( 1 == $input['ChargeStatus'] ) {
				$this->model_checkout_order->addOrderHistory($order_id, 2, "Pagamento confirmado com sucesso.", true);
			}
			else if( 2 == $input['ChargeStatus'] ) {
				$this->model_checkout_order->addOrderHistory($order_id, 1, "Pagamento pendente.", true);
			}
			else { 
				$this->model_checkout_order->addOrderHistory($order_id, 7, "Pagamento cancelado.", true);
			}
		}
		else{
		}
		// file_put_contents(__DIR__ . '/log-aditum-cc.txt', json_encode($input), FILE_APPEND);
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode([]));		
	}

}
