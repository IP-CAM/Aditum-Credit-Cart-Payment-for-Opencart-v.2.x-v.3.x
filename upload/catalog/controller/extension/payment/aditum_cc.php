<?php
class ControllerExtensionPaymentAditumCC extends Controller {

	private $total;
	private $order_status_id;
	private $geo_zone_id;
	private $status;
	private $sort_order;
	private $environment;
	private $titulo_gateway;
	private $descricao_gateway;
	private $parcela_minima;
	private $maximo_parcelas;
	private $merchant_cnpj;
	private $merchant_token;
	private $campo_documento;
	private $campo_numero;
	private $campo_complemento;
	private $campo_bairro;
	private $tipo_antifraude;
	private $token_antifraude;

	public function init_config() {
		$this->total = $this->config->get('payment_aditum_cc_total');
		$this->order_status_id = $this->config->get('payment_aditum_cc_order_status_id');
		$this->geo_zone_id = $this->config->get('payment_aditum_cc_geo_zone_id');
		$this->status = $this->config->get('payment_aditum_cc_status');
		$this->sort_order = $this->config->get('payment_aditum_cc_sort_order');
		$this->environment = $this->config->get('payment_aditum_cc_modo');
		$this->titulo_gateway = $this->config->get('payment_aditum_cc_titulo_gateway');
		$this->descricao_gateway = $this->config->get('payment_aditum_cc_descricao_gateway');
		$this->parcela_minima = $this->config->get('payment_aditum_cc_parcela_minima');
		$this->maximo_parcelas = $this->config->get('payment_aditum_cc_maximo_parcelas');
		$this->merchant_cnpj = $this->config->get('payment_aditum_cc_cnpj');
		$this->merchant_token = $this->config->get('payment_aditum_cc_merchant_token');
		$this->campo_documento = $this->config->get('payment_aditum_cc_campo_documento');
		$this->campo_numero = $this->config->get('payment_aditum_cc_campo_numero');
		$this->campo_complemento = $this->config->get('payment_aditum_cc_campo_complemento');
		$this->campo_bairro = $this->config->get('payment_aditum_cc_campo_bairro');
		$this->tipo_antifraude = $this->config->get('payment_aditum_cc_tipo_antifraude');
		$this->token_antifraude = $this->config->get('payment_aditum_cc_token_antifraude');
		$this->debug = $this->config->get('payment_aditum_cc_debug');
	}

	public function index() {
		$this->init_config();
		$this->load->model('checkout/order');
		$data['order_info'] = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		$data['custom_fields'] = $this->getCustomerExtras($data['order_info']);
		$data['parcela_minima'] = $this->parcela_minima;
		$data['maximo_parcelas'] = $this->maximo_parcelas;
		$data['tipo_antifraude'] = $this->tipo_antifraude;
		$data['token_antifraude'] = $this->token_antifraude;
		$data['documento'] = $data['custom_fields'][$this->campo_documento];
		$data['nome_completo'] = $data['order_info']['payment_firstname'] . ' ' . $data['order_info']['payment_lastname'];
		return $this->load->view('extension/payment/aditum_cc', $data);
	}

	public function confirm() {
		if ( empty( $_REQUEST['aditum_card_installment'] ) ) {
			$this->response->addHeader('Content-Type: application/json');
			return $this->response->setOutput(json_encode(['error' => 'Selecione a <strong>Quantidade de parcelas</strong>']));	
		}
		if ( empty( $_REQUEST['aditum_checkbox'] ) ) {
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
		$authorization  = new AditumPayments\ApiSDK\Domains\Authorization();
		
		$items = $this->cart->getProducts();
		$this->load->model('catalog/product');		
		foreach($items as $item) {
			$product_info = $this->model_catalog_product->getProduct($item['product_id']);
			$authorization->products->add(
				$item['name'], 
				$product_info['sku'],
				str_replace('.', '', number_format($item['price'], 2)),
				$item['quantity']
			);
		}

		$deadline = $this->expiracao;

		$authorization->setSessionId($_REQUEST['antifraud_token']);
		$authorization->setMerchantChargeId($order['order_id']);

		// ! Customer
		$authorization->customer->setId( $order['order_id'] );
		$authorization->customer->setName( $order['payment_firstname'] . ' ' . $order['payment_lastname'] );
		$authorization->customer->setEmail( $order['email'] );

		$campo_documento = $this->campo_documento;

		$count = strlen( $data['custom_fields'][$this->campo_documento] ) ;

		if ( strlen( $data['custom_fields'][$this->campo_documento] ) > 11 ) 
		{
			$authorization->customer->setDocumentType( AditumPayments\ApiSDK\Enum\DocumentType::CNPJ );
		} 
		else 
		{
			$authorization->customer->setDocumentType( AditumPayments\ApiSDK\Enum\DocumentType::CPF );
		}

		$documento = preg_replace( '/[^\d]+/i', '', $data['custom_fields'][$this->campo_documento] );
		$authorization->customer->setDocument( $documento );

		// ! Customer->address
		$authorization->customer->address->setStreet( $order['payment_address_1'] );
		$authorization->customer->address->setNumber( $data['custom_fields'][$this->campo_numero] );
		$authorization->customer->address->setNeighborhood( $order['payment_address_2'] );
		$authorization->customer->address->setCity( $order['payment_city'] );
		$authorization->customer->address->setState( $order['payment_zone_code'] );
		$authorization->customer->address->setCountry( $order['payment_iso_code_2'] );
		$authorization->customer->address->setZipcode( $order['payment_postcode'] );
		$authorization->customer->address->setComplement( $data['custom_fields'][$this->campo_complemento] );

		// ! Customer->phone
		$authorization->customer->phone->setCountryCode( '55' );
		$authorization->customer->phone->setAreaCode( $customer_phone_area_code );
		$authorization->customer->phone->setNumber( $customer_phone );
		$authorization->customer->phone->setType( AditumPayments\ApiSDK\Enum\PhoneType::MOBILE );

		// ! Transactions
		$authorization->transactions->setAmount( $amount );
		$authorization->transactions->setPaymentType( AditumPayments\ApiSDK\Enum\PaymentType::CREDIT );
		$authorization->transactions->setInstallmentNumber( isset($_POST['aditum_card_installment']) ? $_POST['aditum_card_installment'] : 1 ); // Só pode ser maior que 1 se o tipo de transação for crédito.
		
		if($_POST['aditum_card_installment']>1) {
			$authorization->transactions->setInstallmentType(AditumPayments\ApiSDK\Enum\InstallmentType::MERCHANT);
		}
		else {
			$authorization->transactions->setInstallmentType(AditumPayments\ApiSDK\Enum\InstallmentType::NONE);
		}

		$authorization->transactions->card->setCardNumber( preg_replace( '/[^\d]+/', '', $_POST['aditum_card_number'] ) );
		$authorization->transactions->card->setCVV( $_POST['aditum_card_cvv'] );
		$authorization->transactions->card->setCardHolderName( $_POST['card_holder_name'] );
		$authorization->transactions->card->setCardHolderDocument( $_POST['card_holder_document'] );
		$authorization->transactions->card->setExpirationMonth( $_POST['aditum_card_expiration_month'] );
		$authorization->transactions->card->setExpirationYear( 20 . $_POST['aditum_card_expiration_year'] );
		
		$authorization->transactions->card->billingAddress->setStreet( $order['payment_address_1'] );
		$authorization->transactions->card->billingAddress->setNumber( $data['custom_fields'][$this->campo_numero] );
		$authorization->transactions->card->billingAddress->setNeighborhood($order['payment_address_2']);
		$authorization->transactions->card->billingAddress->setCity($order['payment_city']);
		$authorization->transactions->card->billingAddress->setState($order['payment_zone_code']);
		$authorization->transactions->card->billingAddress->setCountry($order['payment_iso_code_2']);
		$authorization->transactions->card->billingAddress->setZipcode($order['payment_postcode']);
		$authorization->transactions->card->billingAddress->setComplement( $data['custom_fields'][$this->campo_complemento] );
		
		$res = $gateway->charge( $authorization );
		
		$json = [];
		if ( isset( $res['status'] ) ) {
			if ( AditumPayments\ApiSDK\Enum\ChargeStatus::NOT_AUTHORIZED === $res['status'] ) {
				$json['error'] = 'Transação não autorizada.';
			}
			else if ( AditumPayments\ApiSDK\Enum\ChargeStatus::AUTHORIZED === $res['status'] ) {
				$this->model_extension_payment_aditum->save_data($this->session->data['order_id'], json_encode($res));
				$checkout = true;
				$this->load->model('checkout/order');
				$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_aditum_cc_order_status_id'), "Pedido realizado com sucesso.", true);
				$json['success'] = true;
				$json['redirect'] = $this->url->link('checkout/success') . '&order_id=' . $this->session->data['order_id'];
			}
			else {
				if($res['charge']->transactions[0]->transactionStatus === "Denied") {
					$json['error'] = 'Transação negada pela operadora do cartão.';
					$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_aditum_cc_order_status_id'), 'Transação negada pela operadora do cartão.', true);
				} 
				else if($res['charge']->transactions[0]->transactionStatus === "PreAuthorized"){
					$this->load->model('checkout/order');
					$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_aditum_cc_order_status_id'), "O pagamento está sendo processado.", true);
					$json['success'] = true;
					$json['redirect'] = $this->url->link('checkout/success') . '&order_id=' . $this->session->data['order_id'];
				}
			}
		} else {
			$message = json_decode($res['httpMsg']);
			if($message && isset($message->errors) && is_array($message->errors) && count($message->errors)) {
				$json['error'] = implode("\n", array_map(function($error){ return $error->message; }, $message->errors));
			}
			else {
				$json['error'] = 'Houve uma falha ao finalizar a campo. Tente novamente.';
			}
		}
		if(isset($json['error']) && $this->debug == 'yes') {
			$json['error'] = json_encode($res);
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
		file_put_contents(__DIR__ . '/log-aditum-cc.txt', json_encode($input), FILE_APPEND);
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode([]));	
	}

	public function card_brand() {
		$this->init_config();
		require DIR_SYSTEM . '../vendor/autoload.php';
		AditumPayments\ApiSDK\Configuration::initialize();
		if ( 'sandbox' === $this->environment ) {
			AditumPayments\ApiSDK\Configuration::setUrl( AditumPayments\ApiSDK\Configuration::DEV_URL );
		}
		AditumPayments\ApiSDK\Configuration::setCnpj( $this->merchant_cnpj );
		AditumPayments\ApiSDK\Configuration::setMerchantToken( $this->merchant_token );
		AditumPayments\ApiSDK\Configuration::setlog( false );
		AditumPayments\ApiSDK\Configuration::login();
		$brand_name = AditumPayments\ApiSDK\Helper\Utils::getBrandCardBin( preg_replace( '/[^\d]+/i', '', $_POST['bin'] ) );
		if ( $brand_name === null ) {
			$array_result = array(
				'status' => 'error',
				'brand'  => 'null',
			);
		} else {
			if ( true === $brand_name['status'] ) {
				$array_result = array(
					'status' => 'success',
					'brand'  => $brand_name['brand'],
				);
			} else {
				$array_result = array(
					'status' => 'error',
					'brand'  => 'null',
				);
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(strtolower(json_encode($array_result)));	
	}

}
