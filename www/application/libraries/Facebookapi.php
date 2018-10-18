<?php
/**
 * @author Nilesh PS
 */
use Facebook\Facebook;

class Facebookapi extends \Facebook\Facebook {

	private $data = null;

	public function __construct() {
		parent::__construct([
			'app_id'                 => '505574966576531',
			'app_secret'                => '1bdcaf2f657ee8b5928ea96df741f3fa',
			'default_graph_version' => 'v2.5',
			'persistent_data_handler' => 'session'
		]);
	}
	
	public function cacheUserData($data) {
		$this->data = $data;
	}

	public function getCachedUserData() {
		return $this->data;
	}
} 