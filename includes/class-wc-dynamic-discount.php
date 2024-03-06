<?php

class WC_Dynamic_Discount {
	public function run() {
		add_action( 'init', array(
			$this,
			'init',
		) );
	}

	public function init() {
		load_plugin_textdomain( 'woocommerce-dynamic-discount', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		if ( class_exists( 'WooCommerce' ) ) {
			$this->define_admin_hooks();
			$this->define_public_hooks();
		}
	}

	private function define_admin_hooks() {
		add_filter( 'woocommerce_settings_tabs_array', array(
			$this,
			'add_dynamic_discount_main_settings_tab',
		), 999 );
		add_action( 'woocommerce_settings_tabs_dynamic_discount', array(
			$this,
			'dynamic_discount_settings_page',
		) );
		add_action( 'woocommerce_update_options_dynamic_discount', array(
			$this,
			'update_dynamic_discount_settings',
		) );
	}

	private function define_public_hooks() {
		add_action( 'woocommerce_cart_calculate_fees', array(
			$this,
			'apply_dynamic_discount',
		), 20 );
	}

	public function add_dynamic_discount_main_settings_tab( $settings_tabs ) {
		$settings_tabs['dynamic_discount'] = __( 'Dynamic Discount', 'woocommerce-dynamic-discount' );

		return $settings_tabs;
	}

	public function dynamic_discount_settings_page() {
		woocommerce_admin_fields( $this->get_dynamic_discount_settings() );
	}

	public function update_dynamic_discount_settings() {
		woocommerce_update_options( $this->get_dynamic_discount_settings() );
	}

	private function create_setting_field( $title, $desc, $id, $default, $type, $desc_tip ): array {
		return array(
			'title'    => __( $title, 'woocommerce-dynamic-discount' ),
			'desc'     => __( $desc, 'woocommerce-dynamic-discount' ),
			'id'       => $id,
			'default'  => $default,
			'type'     => $type,
			'desc_tip' => $desc_tip,
		);
	}

	public function get_dynamic_discount_settings(): array {
		return array(
			$this->create_setting_field(
				'Dynamic Discount Settings',
				'Configure dynamic discounts based on cart quantity.',
				'dynamic_discount_options',
				'',
				'title',
				false
			),
			$this->create_setting_field(
				'Enable Dynamic Discounts',
				'Enable/disable the functionality of dynamic discounts.',
				'dynamic_discount_enabled',
				'no',
				'checkbox',
				true
			),
			$this->create_setting_field(
				'Level 1 Product Count',
				'Number of products for the 1st level discount to apply.',
				'dynamic_discount_level1_count',
				'5',
				'number',
				true
			),
			$this->create_setting_field(
				'Level 1 Discount (%)',
				'Discount percentage for the 1st level.',
				'dynamic_discount_level1_discount',
				'5',
				'number',
				true
			),
			$this->create_setting_field(
				'Level 2 Product Count',
				'Number of products for the 2nd level discount to apply.',
				'dynamic_discount_level2_count',
				'10',
				'number',
				true
			),
			$this->create_setting_field(
				'Level 2 Discount (%)',
				'Discount percentage for the 2nd level.',
				'dynamic_discount_level2_discount',
				'10',
				'number',
				true
			),
			array(
				'type' => 'sectionend',
				'id'   => 'dynamic_discount_options',
			),
		);
	}

	public function apply_dynamic_discount() {
		if ('yes' !== get_option('dynamic_discount_enabled')) {
			return;
		}

		$discount_details = $this->calculate_discount_details();
		if ($discount_details['apply_discount']) {
			$this->apply_discount_fee($discount_details['discount_percentage'], $discount_details['discount_amount']);
		}
	}

	private function calculate_discount_details() {
		$level1_count = get_option('dynamic_discount_level1_count');
		$level1_discount = get_option('dynamic_discount_level1_discount');
		$level2_count = get_option('dynamic_discount_level2_count');
		$level2_discount = get_option('dynamic_discount_level2_discount');

		$total_quantity = WC()->cart->get_cart_contents_count();
		$discount_percentage = 0;
		$apply_discount = false;

		if (!empty($level2_count) && !empty($level2_discount) && $total_quantity >= $level2_count) {
			$discount_percentage = $level2_discount;
			$apply_discount = true;
		} elseif (!empty($level1_count) && !empty($level1_discount) && $total_quantity >= $level1_count) {
			$discount_percentage = $level1_discount;
			$apply_discount = true;
		}

		if ($apply_discount) {
			$cart_total = WC()->cart->get_subtotal();
			$discount_amount = ($cart_total * $discount_percentage) / 100;
		} else {
			$discount_amount = 0;
		}

		return [
			'apply_discount' => $apply_discount,
			'discount_percentage' => $discount_percentage,
			'discount_amount' => $discount_amount,
		];
	}

	private function apply_discount_fee($discount_percentage, $discount_amount) {
		WC()->cart->add_fee(sprintf(__('%s%% Discount', 'woocommerce-dynamic-discount'), $discount_percentage), -$discount_amount, false);
	}
}
