	public function install() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "aditum` (
		  `order_id` int(11),
		  `date_added` datetime NOT NULL DEFAULT current_timestamp,
		  `data` longtext NOT NULL
		)");
	}

