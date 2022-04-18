<?php
class ModelExtensionPaymentAditum extends Model {

	public function save_data($order_id, $data) {
		if(is_array($data)) {
			$data = json_encode($data);
		}
		$query = $this->db->query("INSERT INTO " . DB_PREFIX . "aditum (`order_id`, `data`) VALUES ('{$order_id}', '{$data}')");
		return $query;
	}

	public function get_data($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "aditum WHERE order_id = '{$order_id}'");
		if(count($query->rows)) {
			return $query->rows[0]['data'];
		}
		return false;
	}

}
