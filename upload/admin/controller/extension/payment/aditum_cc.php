<?php
class ControllerExtensionPaymentAditumCC extends Controller {
	private $error = array();
	
	public function install() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "aditum` (
		  `order_id` int(11),
		  `date_added` datetime NOT NULL DEFAULT current_timestamp,
		  `data` longtext NOT NULL
		)");
	}

	public function index() {
		$this->load->language('extension/payment/aditum_cc');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_aditum_cc', $this->request->post);

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
			'href' => $this->url->link('extension/payment/aditum_cc', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/aditum_cc', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_aditum_cc_total'])) {
			$data['payment_aditum_cc_total'] = $this->request->post['payment_aditum_cc_total'];
		} else {
			$data['payment_aditum_cc_total'] = ($c=$this->config->get('payment_aditum_cc_total')) ? $c : 0;
		}

		if (isset($this->request->post['payment_aditum_cc_order_status_id'])) {
			$data['payment_aditum_cc_order_status_id'] = $this->request->post['payment_aditum_cc_order_status_id'];
		} else {
			$data['payment_aditum_cc_order_status_id'] = $this->config->get('payment_aditum_cc_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_aditum_cc_geo_zone_id'])) {
			$data['payment_aditum_cc_geo_zone_id'] = $this->request->post['payment_aditum_cc_geo_zone_id'];
		} else {
			$data['payment_aditum_cc_geo_zone_id'] = $this->config->get('payment_aditum_cc_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_aditum_cc_status'])) {
			$data['payment_aditum_cc_status'] = $this->request->post['payment_aditum_cc_status'];
		} else {
			$data['payment_aditum_cc_status'] = $this->config->get('payment_aditum_cc_status');
		}

		if (isset($this->request->post['payment_aditum_cc_sort_order'])) {
			$data['payment_aditum_cc_sort_order'] = $this->request->post['payment_aditum_cc_sort_order'];
		} else {
			$data['payment_aditum_cc_sort_order'] = $this->config->get('payment_aditum_cc_sort_order');
		}

		if (isset($this->request->post['payment_aditum_cc_modo'])) {
			$data['payment_aditum_cc_modo'] = $this->request->post['payment_aditum_cc_modo'];
		} else {
			$data['payment_aditum_cc_modo'] = $this->config->get('payment_aditum_cc_modo');
		}

		if (isset($this->request->post['payment_aditum_cc_titulo_gateway'])) {
			$data['payment_aditum_cc_titulo_gateway'] = $this->request->post['payment_aditum_cc_titulo_gateway'];
		} else {
			$data['payment_aditum_cc_titulo_gateway'] = ($c=$this->config->get('payment_aditum_cc_titulo_gateway')) ? $c : 'Aditum Cartão de Crédito';
		}

		if (isset($this->request->post['payment_aditum_cc_descricao_gateway'])) {
			$data['payment_aditum_cc_descricao_gateway'] = $this->request->post['payment_aditum_cc_descricao_gateway'];
		} else {
			$data['payment_aditum_cc_descricao_gateway'] = ($c=$this->config->get('payment_aditum_cc_descricao_gateway')) ? $c : 'Pague com total segurança através do seu cartão de crédito';
		}

		if (isset($this->request->post['payment_aditum_cc_instrucoes'])) {
			$data['payment_aditum_cc_instrucoes'] = $this->request->post['payment_aditum_cc_instrucoes'];
		} else {
			$data['payment_aditum_cc_instrucoes'] = $this->config->get('payment_aditum_cc_instrucoes');
		}

		if (isset($this->request->post['payment_aditum_cc_parcela_minima'])) {
			$data['payment_aditum_cc_parcela_minima'] = $this->request->post['payment_aditum_cc_parcela_minima'];
		} else {
			$data['payment_aditum_cc_parcela_minima'] = ($c=$this->config->get('payment_aditum_cc_parcela_minima')) ? $c : 5;
		}

		if (isset($this->request->post['payment_aditum_cc_maximo_parcelas'])) {
			$data['payment_aditum_cc_maximo_parcelas'] = $this->request->post['payment_aditum_cc_maximo_parcelas'];
		} else {
			$data['payment_aditum_cc_maximo_parcelas'] = ($c=$this->config->get('payment_aditum_cc_maximo_parcelas')) ? $c : 12;
		}

		if (isset($this->request->post['payment_aditum_cc_cnpj'])) {
			$data['payment_aditum_cc_cnpj'] = $this->request->post['payment_aditum_cc_cnpj'];
		} else {
			$data['payment_aditum_cc_cnpj'] = $this->config->get('payment_aditum_cc_cnpj');
		}

		if (isset($this->request->post['payment_aditum_cc_merchant_token'])) {
			$data['payment_aditum_cc_merchant_token'] = $this->request->post['payment_aditum_cc_merchant_token'];
		} else {
			$data['payment_aditum_cc_merchant_token'] = $this->config->get('payment_aditum_cc_merchant_token');
		}

		if (isset($this->request->post['payment_aditum_cc_campo_documento'])) {
			$data['payment_aditum_cc_campo_documento'] = $this->request->post['payment_aditum_cc_campo_documento'];
		} else {
			$data['payment_aditum_cc_campo_documento'] = $this->config->get('payment_aditum_cc_campo_documento');
		}

		if (isset($this->request->post['payment_aditum_cc_campo_numero'])) {
			$data['payment_aditum_cc_campo_numero'] = $this->request->post['payment_aditum_cc_campo_numero'];
		} else {
			$data['payment_aditum_cc_campo_numero'] = $this->config->get('payment_aditum_cc_campo_numero');
		}

		if (isset($this->request->post['payment_aditum_cc_campo_complemento'])) {
			$data['payment_aditum_cc_campo_complemento'] = $this->request->post['payment_aditum_cc_campo_complemento'];
		} else {
			$data['payment_aditum_cc_campo_complemento'] = $this->config->get('payment_aditum_cc_campo_complemento');
		}

		if (isset($this->request->post['payment_aditum_cc_tipo_antifraude'])) {
			$data['payment_aditum_cc_tipo_antifraude'] = $this->request->post['payment_aditum_cc_tipo_antifraude'];
		} else {
			$data['payment_aditum_cc_tipo_antifraude'] = $this->config->get('payment_aditum_cc_tipo_antifraude');
		}

		if (isset($this->request->post['payment_aditum_cc_token_antifraude'])) {
			$data['payment_aditum_cc_token_antifraude'] = $this->request->post['payment_aditum_cc_token_antifraude'];
		} else {
			$data['payment_aditum_cc_token_antifraude'] = $this->config->get('payment_aditum_cc_token_antifraude');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/aditum_cc', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/aditum_cc')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}