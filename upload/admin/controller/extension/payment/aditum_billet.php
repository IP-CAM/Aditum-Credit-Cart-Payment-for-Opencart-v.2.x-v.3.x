<?php
class ControllerExtensionPaymentAditumBillet extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/aditum_billet');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_aditum_billet', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$this->load->model('customer/custom_field');
		$data['custom_fields'] = $this->model_customer_custom_field->getCustomFields();

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/aditum_billet', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/aditum_billet', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_aditum_billet_total'])) {
			$data['payment_aditum_billet_total'] = $this->request->post['payment_aditum_billet_total'];
		} else {
			$data['payment_aditum_billet_total'] = ($c=$this->config->get('payment_aditum_billet_total')) ? $c : 0;
		}

		if (isset($this->request->post['payment_aditum_billet_order_status_id'])) {
			$data['payment_aditum_billet_order_status_id'] = $this->request->post['payment_aditum_billet_order_status_id'];
		} else {
			$data['payment_aditum_billet_order_status_id'] = $this->config->get('payment_aditum_billet_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_aditum_billet_geo_zone_id'])) {
			$data['payment_aditum_billet_geo_zone_id'] = $this->request->post['payment_aditum_billet_geo_zone_id'];
		} else {
			$data['payment_aditum_billet_geo_zone_id'] = $this->config->get('payment_aditum_billet_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_aditum_billet_status'])) {
			$data['payment_aditum_billet_status'] = $this->request->post['payment_aditum_billet_status'];
		} else {
			$data['payment_aditum_billet_status'] = $this->config->get('payment_aditum_billet_status');
		}

		if (isset($this->request->post['payment_aditum_billet_sort_order'])) {
			$data['payment_aditum_billet_sort_order'] = $this->request->post['payment_aditum_billet_sort_order'];
		} else {
			$data['payment_aditum_billet_sort_order'] = $this->config->get('payment_aditum_billet_sort_order');
		}

		if (isset($this->request->post['payment_aditum_billet_modo'])) {
			$data['payment_aditum_billet_modo'] = $this->request->post['payment_aditum_billet_modo'];
		} else {
			$data['payment_aditum_billet_modo'] = $this->config->get('payment_aditum_billet_modo');
		}

		if (isset($this->request->post['payment_aditum_billet_titulo_gateway'])) {
			$data['payment_aditum_billet_titulo_gateway'] = $this->request->post['payment_aditum_billet_titulo_gateway'];
		} else {
			$data['payment_aditum_billet_titulo_gateway'] = ($c=$this->config->get('payment_aditum_billet_titulo_gateway')) ? $c : 'Aditum Boleto Gateway';
		}

		if (isset($this->request->post['payment_aditum_billet_descricao_gateway'])) {
			$data['payment_aditum_billet_descricao_gateway'] = $this->request->post['payment_aditum_billet_descricao_gateway'];
		} else {
			$data['payment_aditum_billet_descricao_gateway'] = ($c=$this->config->get('payment_aditum_billet_descricao_gateway')) ? $c : 'Pague com total segurança através de boleto bancário';
		}

		if (isset($this->request->post['payment_aditum_billet_instrucoes'])) {
			$data['payment_aditum_billet_instrucoes'] = $this->request->post['payment_aditum_billet_instrucoes'];
		} else {
			$data['payment_aditum_billet_instrucoes'] = $this->config->get('payment_aditum_billet_instrucoes');
		}

		if (isset($this->request->post['aditum_billet_expiracao'])) {
			$data['payment_aditum_billet_expiracao'] = $this->request->post['aditum_billet_expiracao'];
		} else {
			$data['payment_aditum_billet_expiracao'] = ($c=$this->config->get('aditum_billet_expiracao')) ? $c : 5;
		}

		if (isset($this->request->post['aditum_billet_dias_multa'])) {
			$data['payment_aditum_billet_dias_multa'] = $this->request->post['aditum_billet_dias_multa'];
		} else {
			$data['payment_aditum_billet_dias_multa'] = $this->config->get('aditum_billet_dias_multa');
		}

		if (isset($this->request->post['aditum_billet_valor_multa'])) {
			$data['payment_aditum_billet_valor_multa'] = $this->request->post['aditum_billet_valor_multa'];
		} else {
			$data['payment_aditum_billet_valor_multa'] = $this->config->get('aditum_billet_valor_multa');
		}

		if (isset($this->request->post['aditum_billet_percentual_multa'])) {
			$data['payment_aditum_billet_percentual_multa'] = $this->request->post['aditum_billet_percentual_multa'];
		} else {
			$data['payment_aditum_billet_percentual_multa'] = $this->config->get('aditum_billet_percentual_multa');
		}

		if (isset($this->request->post['payment_aditum_billet_cnpj'])) {
			$data['payment_aditum_billet_cnpj'] = $this->request->post['payment_aditum_billet_cnpj'];
		} else {
			$data['payment_aditum_billet_cnpj'] = $this->config->get('payment_aditum_billet_cnpj');
		}

		if (isset($this->request->post['payment_aditum_billet_merchant_token'])) {
			$data['payment_aditum_billet_merchant_token'] = $this->request->post['payment_aditum_billet_merchant_token'];
		} else {
			$data['payment_aditum_billet_merchant_token'] = $this->config->get('payment_aditum_billet_merchant_token');
		}

		if (isset($this->request->post['payment_aditum_billet_campo_documento'])) {
			$data['payment_aditum_billet_campo_documento'] = $this->request->post['payment_aditum_billet_campo_documento'];
		} else {
			$data['payment_aditum_billet_campo_documento'] = $this->config->get('payment_aditum_billet_campo_documento');
		}

		if (isset($this->request->post['payment_aditum_billet_campo_numero'])) {
			$data['payment_aditum_billet_campo_numero'] = $this->request->post['payment_aditum_billet_campo_numero'];
		} else {
			$data['payment_aditum_billet_campo_numero'] = $this->config->get('payment_aditum_billet_campo_numero');
		}

		if (isset($this->request->post['payment_aditum_billet_campo_complemento'])) {
			$data['payment_aditum_billet_campo_complemento'] = $this->request->post['payment_aditum_billet_campo_complemento'];
		} else {
			$data['payment_aditum_billet_campo_complemento'] = $this->config->get('payment_aditum_billet_campo_complemento');
		}

		if (isset($this->request->post['payment_aditum_billet_tipo_antifraude'])) {
			$data['payment_aditum_billet_tipo_antifraude'] = $this->request->post['payment_aditum_billet_tipo_antifraude'];
		} else {
			$data['payment_aditum_billet_tipo_antifraude'] = $this->config->get('payment_aditum_billet_tipo_antifraude');
		}

		if (isset($this->request->post['payment_aditum_billet_token_antifraude'])) {
			$data['payment_aditum_billet_token_antifraude'] = $this->request->post['payment_aditum_billet_token_antifraude'];
		} else {
			$data['payment_aditum_billet_token_antifraude'] = $this->config->get('payment_aditum_billet_token_antifraude');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/aditum_billet', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/aditum_billet')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}