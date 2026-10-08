<?php
namespace SHORT\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Core {
	public function run() {
		\SHORT\Core\CPT\Video_CPT::init();
		\SHORT\Core\API\REST_Controller::init();
		\SHORT\Core\Admin\License::init();
		if ( is_admin() ) {
			\SHORT\Core\Admin\Dashboard::init();
		}
	}
}