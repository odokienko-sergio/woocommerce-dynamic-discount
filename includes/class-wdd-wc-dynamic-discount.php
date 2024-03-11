<?php

class WDD_WC_Dynamic_Discount {
	public function run() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		load_plugin_textdomain( 'woocommerce-dynamic-discount', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		if ( class_exists( 'WooCommerce' ) ) {
			$this->wdd_define_admin_hooks();
			$this->wdd_define_public_hooks();
		}
	}

	private function wdd_define_admin_hooks() {
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'wdd_add_dynamic_discount_main_settings_tab' ), 999 );
		add_action( 'woocommerce_settings_tabs_dynamic_discount', array( $this, 'wdd_dynamic_discount_settings_page' ) );
		add_action( 'woocommerce_update_options_dynamic_discount', array( $this, 'wdd_update_dynamic_discount_settings' ) );
	}

	private function wdd_define_public_hooks() {
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'wdd_apply_dynamic_discount' ), 20 );
	}

	public function wdd_add_dynamic_discount_main_settings_tab( $settings_tabs ) {
		$settings_tabs['dynamic_discount'] = __( 'Dynamic Discount', 'woocommerce-dynamic-discount' );
		return $settings_tabs;
	}

	public function wdd_dynamic_discount_settings_page() {
		woocommerce_admin_fields( $this->wdd_get_dynamic_discount_settings() );
	}

	public function wdd_update_dynamic_discount_settings() {
		woocommerce_update_options( $this->wdd_get_dynamic_discount_settings() );
	}

	private function wdd_create_setting_field( $title, $desc, $id, $default, $type, $desc_tip ): array {
		return array(
			'title'    => $title,
			'desc'     => $desc,
			'id'       => $id,
			'default'  => $default,
			'type'     => $type,
			'desc_tip' => $desc_tip,
		);
	}

	public function wdd_get_dynamic_discount_settings(): array {
		return array(
			$this->wdd_create_setting_field(
				__( 'Dynamic Discount Settings', 'woocommerce-dynamic-discount' ),
				__( 'Configure dynamic discounts based on cart quantity.', 'woocommerce-dynamic-discount' ),
				'dynamic_discount_options',
				'',
				'title',
				false
			),
			$this->wdd_create_setting_field(
				__( 'Enable Dynamic Discounts', 'woocommerce-dynamic-discount' ),
				__( 'Enable/disable the functionality of dynamic discounts.', 'woocommerce-dynamic-discount' ),
				'dynamic_discount_enabled',
				'no',
				'checkbox',
				true
			),
			$this->wdd_create_setting_field(
				__( 'Level 1 Product Count', 'woocommerce-dynamic-discount' ),
				__( 'Number of products for the 1st level discount to apply.', 'woocommerce-dynamic-discount' ),
				'dynamic_discount_level1_count',
				'5',
				'number',
				true
			),
			$this->wdd_create_setting_field(
				__( 'Level 1 Discount (%)', 'woocommerce-dynamic-discount' ),
				__( 'Discount percentage for the 1st level.', 'woocommerce-dynamic-discount' ),
				'dynamic_discount_level1_discount',
				'5',
				'number',
				true
			),
			$this->wdd_create_setting_field(
				__( 'Level 2 Product Count', 'woocommerce-dynamic-discount' ),
				__( 'Number of products for the 2nd level discount to apply.', 'woocommerce-dynamic-discount' ),
				'dynamic_discount_level2_count',
				'10',
				'number',
				true
			),
			$this->wdd_create_setting_field(
				__( 'Level 2 Discount (%)', 'woocommerce-dynamic-discount' ),
				__( 'Discount percentage for the 2nd level.', 'woocommerce-dynamic-discount' ),
				'dynamic_discount_level2_discount',
				'10',
				'number',
				true
			),
			array(
				'type' => 'sectionend',
				'id'   => 'dynamic_discount_options',
			)
		);
	}

	public function wdd_apply_dynamic_discount() {
		if ('yes' !== get_option('wdd_dynamic_discount_enabled')) { // Prefixed option name
			return;
		}

		$discount_details = $this->wdd_calculate_discount_details();
		if ($discount_details['apply_discount']) {
			$this->wdd_apply_discount_fee($discount_details['discount_percentage'], $discount_details['discount_amount']);
		}
	}

	private function wdd_calculate_discount_details() {
		$level1_count = get_option('wdd_dynamic_discount_level1_count'); // Prefixed option name
		$level1_discount = get_option('wdd_dynamic_discount_level1_discount'); // Prefixed option name
		$level2_count = get_option('wdd_dynamic_discount_level2_count'); // Prefixed option name
		$level2_discount = get_option('wdd_dynamic_discount_level2_discount'); // Prefixed option name

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

	private function wdd_apply_discount_fee($discount_percentage, $discount_amount) {
		WC()->cart->add_fee(sprintf(__('%s%% Discount', 'woocommerce-dynamic-discount'), $discount_percentage), -$discount_amount, false);
	}
}
