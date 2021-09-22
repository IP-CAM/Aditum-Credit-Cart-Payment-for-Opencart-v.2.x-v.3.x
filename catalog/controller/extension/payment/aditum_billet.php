<?php
class ControllerExtensionPaymentAditumBillet extends Controller {

	private $total;
	private $order_status_id;
	private $geo_zone_id;
	private $status;
	private $sort_order;
	private $environment;
	private $titulo_gateway;
	private $descricao_gateway;
	private $instrucoes;
	private $expiracao;
	private $dias_multa;
	private $valor_multa;
	private $percentual_multa;
	private $merchant_cnpj;
	private $merchant_token;
	private $campo_documento;
	private $campo_numero;
	private $campo_complemento;
	private $campo_bairro;
	private $tipo_antifraude;
	private $token_antifraude;

	public function init_config() {
		$this->total = $this->config->get('payment_aditum_billet_total');
		$this->order_status_id = $this->config->get('payment_aditum_billet_order_status_id');
		$this->geo_zone_id = $this->config->get('payment_aditum_billet_geo_zone_id');
		$this->status = $this->config->get('payment_aditum_billet_status');
		$this->sort_order = $this->config->get('payment_aditum_billet_sort_order');
		$this->environment = $this->config->get('payment_aditum_billet_modo');
		$this->titulo_gateway = $this->config->get('payment_aditum_billet_titulo_gateway');
		$this->descricao_gateway = $this->config->get('payment_aditum_billet_descricao_gateway');
		$this->instrucoes = $this->config->get('payment_aditum_billet_instrucoes');
		$this->expiracao = $this->config->get('payment_aditum_billet_expiracao');
		$this->dias_multa = $this->config->get('payment_aditum_billet_dias_multa');
		$this->valor_multa = $this->config->get('payment_aditum_billet_valor_multa');
		$this->percentual_multa = $this->config->get('payment_aditum_billet_percentual_multa');
		$this->merchant_cnpj = $this->config->get('payment_aditum_billet_cnpj');
		$this->merchant_token = $this->config->get('payment_aditum_billet_merchant_token');
		$this->campo_documento = $this->config->get('payment_aditum_billet_campo_documento');
		$this->campo_numero = $this->config->get('payment_aditum_billet_campo_numero');
		$this->campo_complemento = $this->config->get('payment_aditum_billet_campo_complemento');
		$this->campo_bairro = $this->config->get('payment_aditum_billet_campo_bairro');
		$this->tipo_antifraude = $this->config->get('payment_aditum_billet_tipo_antifraude');
		$this->token_antifraude = $this->config->get('payment_aditum_billet_token_antifraude');
	}

	public function index() {
		$this->init_config();
		$data['tipo_antifraude'] = $this->tipo_antifraude;
		$data['token_antifraude'] = $this->token_antifraude;
		return $this->load->view('extension/payment/aditum_billet', $data);
	}

	public function confirm() {
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

		require DIR_SYSTEM . '../vendor/autoload.php';

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
		$boleto  = new AditumPayments\ApiSDK\Domains\Boleto();

		$deadline = $this->expiracao;

		$boleto->setDeadline( $deadline );
		$boleto->setSessionId($_REQUEST['antifraud_token']);
		$boleto->setMerchantChargeId($order['order_id']);

		// ! Customer
		$boleto->customer->setId( $order['order_id'] );
		$boleto->customer->setName( $order['payment_firstname'] . ' ' . $order['payment_lastname'] );
		$boleto->customer->setEmail( $order['email'] );

		$campo_documento = $this->campo_documento;

		$count = strlen( $data['custom_fields'][$this->campo_documento] ) ;

		if ( strlen( $data['custom_fields'][$this->campo_documento] ) > 11 ) 
		{
			$boleto->customer->setDocumentType( AditumPayments\ApiSDK\Enum\DocumentType::CNPJ );
		} 
		else 
		{
			$boleto->customer->setDocumentType( AditumPayments\ApiSDK\Enum\DocumentType::CPF );
		}

		$documento = preg_replace( '/[^\d]+/i', '', $data['custom_fields'][$this->campo_documento] );
		$boleto->customer->setDocument( $documento );

		// ! Customer->address
		$boleto->customer->address->setStreet( $order['payment_address_1'] );
		$boleto->customer->address->setNumber( $data['custom_fields'][$this->campo_numero] );
		$boleto->customer->address->setNeighborhood( $order['payment_address_2'] );
		$boleto->customer->address->setCity( $order['payment_city'] );
		$boleto->customer->address->setState( $order['payment_zone_code'] );
		$boleto->customer->address->setCountry( $order['payment_iso_code_2'] );
		$boleto->customer->address->setZipcode( $order['payment_postcode'] );
		$boleto->customer->address->setComplement( $data['custom_fields'][$this->campo_complemento] );

		// ! Customer->phone
		$boleto->customer->phone->setCountryCode( '55' );
		$boleto->customer->phone->setAreaCode( $customer_phone_area_code );
		$boleto->customer->phone->setNumber( $customer_phone );
		$boleto->customer->phone->setType( AditumPayments\ApiSDK\Enum\PhoneType::MOBILE );

		// ! Transactions
		$boleto->transactions->setAmount( $amount );
		$boleto->transactions->setInstructions( $this->instrucoes );

		// // Transactions->fine (opcional)

		if(!empty($this->dias_multa)){
			$boleto->transactions->fine->setStartDate($this->dias_multa);
			$boleto->transactions->fine->setAmount($this->valor_multa);
			$boleto->transactions->fine->setInterest($this->percentual_multa);
		}

		$res = $gateway->charge( $boleto );

		if ( isset( $res['status'] ) ) {
			if ( AditumPayments\ApiSDK\Enum\ChargeStatus::PRE_AUTHORIZED === $res['status'] ) {
				 $this->load->model('checkout/order');
				 $checkout = true;
				 $url = AditumPayments\ApiSDK\Configuration::DEV_URL;
					 $urlBoleto = str_replace('/v2/', '', $url) . "{$res['charge']->transactions[0]->bankSlipUrl}";
					 $this->session->data['url_boleto'] = $urlBoleto;
					$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_aditum_cc_order_status_id'), "Pedido realizado com sucesso <a style='background:#9c2671;color:#fff;font-size:9px;text-transform:uppercase;font-weight:bold;padding:5px 10px;border-radius:2px;' href='{$urlBoleto}' target='_blank'>clique aqui para pagar o boleto</a>", true);
					$json['success'] = true;
					$json['redirect'] = $this->url->link('checkout/success');
			}
		} else {
			$json['error'] = implode("\n", array_map(function($error){ return $error->message; }, json_decode($res['httpMsg'])->errors));
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

}
