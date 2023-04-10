<?php

/**
 * Users balance file
 *
 * @package SzCsCoupon
 */
class SzCsCouponWallet
{
	/**
	 * The single instance of the class.
	 *
	 * @var SzCsCouponWallet
	 * @since 1.1.11
	 */
	protected static $_instance = null;
	private static $_user_data = null;

	/**
	 * Main instance
	 *
	 * @return class object
	 */

	protected static $_message = array();

	public static function instance()
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Class constructor
	 */
	public function __construct()
	{
		// add_action('szcs_admin_menu', array($this, 'admin_menu'), 0);
		add_action('init', array($this, 'topup'));
		add_shortcode('szcs_coupon_balance_only_digits', array($this, 'get_balance'));
		add_shortcode('szcs_coupon_balance', array($this, 'get_balance_html'));
		add_shortcode('szcs_coupon_topup_form', array($this, 'topup_form'));
		add_shortcode('szcs_coupon_transactions_table', array($this, 'transactions_table'));
		add_action('szcs_coupon_add_voucher_notice', array($this, 'user_notice'));
		add_action('szcs_claim_voucher', array($this, 'claim_voucher'));
		add_action('szcs_coupon_update_balance', array($this, 'update_balance'), 10, 3);
	}


	/**
	 * Get data.
	 */
	public function get_data($user_id = '')
	{
		if ($user_id) {
			global $wpdb;
			$points_table = $wpdb->prefix . 'szcs_user_points';
			$users_query = "SELECT * FROM $points_table WHERE user_id={$user_id}";
			$results = $wpdb->get_results($users_query, OBJECT);
			return empty($results) ? false : $results[0];
		} else if (is_null(self::$_user_data) && get_current_user_id()) {
			self::$_user_data = $this->get_data(get_current_user_id());
		}
		return self::$_user_data;
	}

	public function update_balance($user_id, $amount, $action)
	{

		$args = array(
			'user_id' => $user_id,
		);

		switch ($action) {
			case 'add':
				$args['description'] = 'Coins balance increased by ' . $amount;
				$args['credit_points'] = (float) $amount;
				break;
			case 'less':
				$args['description'] = 'Coins balance decreased by ' . $amount;
				$args['debit_points'] = (float) $amount;
				break;
			case 'set':
				$args['description'] = 'Coins balance set to ' . $amount;
				$args['closing_balance'] = (float) $amount;
				break;
		}
		do_action('szcs_coupon_add_transaction', $args);
	}

	/**
	 * Show balance [shortcode].
	 */
	function get_balance($user_id = '')
	{
		$user_data = $this->get_data($user_id);
		$balance = $user_data ? $user_data->wallet_points : 0;
		return $balance;
	}

	function get_balance_html($args)
	{

		$args = wp_parse_args(
			$args,
			array(
				'href' => home_url('/voucher/'),
				'title' => 'Coins',
				'icon' => ''
			)
		);

		if (get_current_user_id()) {
			wp_enqueue_style('szcs_coupons');
			if ($args['href']) {
				$output = '<a class="szcs_coupon" href="' . $args['href'] . '" title="' . $args['title'] . '">';
			} else {
				$output = '<div class="szcs_coupon">';
			}
			$output .= '<span class="szcs_coupon_icon" style="width: 45px;">';
			if ($args['icon']) {
				$output .= '<img src="' . $args['icon'] . '" alt="' . $args['title'] . '">';
			} else {
				$output .= '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-wallet2" viewBox="0 0 16 16"><path d="M12.136.326A1.5 1.5 0 0 1 14 1.78V3h.5A1.5 1.5 0 0 1 16 4.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 13.5v-9a1.5 1.5 0 0 1 1.432-1.499L12.136.326zM5.562 3H13V1.78a.5.5 0 0 0-.621-.484L5.562 3zM1.5 4a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-13z"/></svg>';
			}
			$output .= '</span>';
			$output .= '<span class="szcs_coupon_points">';
			$output .= '<span class="szcs_coupon_digits">';
			$output .= number_format($this->get_balance(), 2);
			$output .= '</span>';
			$output .= '<span class="szcs_coupon_text">';
			$output .= $args['title'];
			$output .= '</span>';
			$output .= '</span>';
			if ($args['href']) {
				$output .= '</a>';
			} else {
				$output .= '</div>';
			}
			return $output;
		}
		return '';
	}

	/**
	 * add balance
	 */

	function claim_voucher($voucher, $id = '')
	{
		if (is_user_logged_in()) {
			//? Checks if the voucher can be claimed;
			$claim_validation = szcs_coupon_can_redeem($voucher);
			if (!$id) {
				$id = get_current_user_id();
			}

			//? If the voucher can be claimed, then add the transaction;
			if ($claim_validation[0] === 'success') {
				do_action('szcs_coupon_add_transaction', array(
					'user_id' => $id,
					'description' => "Vaucher Credited",
					'debit_points' => 0,
					'credit_points' => $voucher->voucher_amount,
					'voucher_id' => $voucher->voucher_id,
					'voucher_no' => $voucher->voucher_no,
					'status' => null,
				));
				wc_add_notice(__('Yay! Your account has been credited with ' . $voucher->voucher_amount . ' of coins!', 'szcs-coupon'), 'success');
			} else {
				wc_add_notice(__($claim_validation[2], 'szcs-coupon'), 'error');
			}
		} else {
			wc_add_notice(__('You need to logged in to claim voucher', 'szcs-coupon'), 'error');
		}
	}

	function topup()
	{
		if (isset($_POST['add_voucher'])) {
			if (is_user_logged_in()) {
				global $szcs_coupon_voucher;
				$voucher = $szcs_coupon_voucher->validate_voucher($_POST['voucher'], '', true);
				if ($voucher[0] === 'valid') {
					do_action('szcs_claim_voucher', $voucher[1]);
				} else {
					//self::$_message = $voucher;
					wc_add_notice(__($voucher[2], 'szcs-coupon'), $voucher[0]);
				}
			} else {
				wc_add_notice(__('You need to logged in to claim voucher', 'szcs-coupon'), 'error');
			}
		}
	}

	function user_notice()
	{
		if (!empty(self::$_message)) {
			wc_add_notice(self::$_message[2], self::$_message[0]);
		}
	}

	/**
	 * add balance form [shortcode]
	 */
	function topup_form($args)
	{
		$args = wp_parse_args($args, [
			'placeholder-text' => __('Add voucher code', 'szcs-coupon'),
			'button-text' => __("Add Voucher", 'szcs-coupon')
		]);

		wp_enqueue_style('szcs_coupons');
		$output =  '<div class="woocommerce szcs_coupon_topup_form">';
		do_action('szcs_coupon_add_voucher_notice'); // for backward compatibility
		if (function_exists('wc_print_notices')) {
			$output .=  wc_print_notices(true);
		}
		$output .=  '<h2 class="title">' . __("Have Freebucks?", "szcs-coupon") . '</h2>';
		$output .=  '<p class="subtitle">' . __("Add your coins here", "szcs-coupon") . '</p>';
		$output .=  '<form method="post" class="woocommerce-form woocommerce-form-login login" action="">';
		$output .=  '<p class="form-row form-row-wide">';
		// 		$output .=  '<label for="voucher">' . __("Add voucher code", "szcs-coupon") . '</label>';
		$output .=  '<input type="text" class="input-text" name="voucher" id="voucher" value="" placeholder="' . $args['placeholder-text'] . '">';
		$output .=  '</p>';
		// 		$output .= '<img src="https://www.myfreebucks.cubosquare.com/wp-content/uploads/2023/03/pngfind.com-straight-arrow-png-5931747.png" />';
		$output .=  '<p class="form-row">';
		$output .=  '<input type="hidden" name="action" value="add-voucher" >';
		$output .=  '<!-- <input type="hidden" id="woocommerce-login-nonce" name="woocommerce-login-nonce" value="485bff4379"> -->';
		$output .=  '<input type="submit" class="button" name="add_voucher" value="' . $args['button-text'] . '">';
		$output .=  '</p>';
		$output .=  '</form>';
		$output .=  '</div>';
		return $output;
	}

	/**
	 * transactons table [shortcode]
	 */
	function transactions_table()
	{
		if (get_current_user_id()) {
			$tableColumns = array(
				array(
					'label' => __("Description", "szcs-coupon"),
					'id'    => 'description',
				),
				array(
					'label' => __("Debit Points", "szcs-coupon"),
					'id'    => 'debit_points',
				),
				array(
					'label' => __("Credit Points", "szcs-coupon"),
					'id'    => 'credit_points',
				),
				array(
					'label' => __("Closing Balance", "szcs-coupon"),
					'id'    => 'closing_balance',
				),
				array(
					'label' => __("Voucher No", "szcs-coupon"),
					'id'    => 'voucher_no',
				),
				array(
					'label' => __("Order Datetime", "szcs-coupon"),
					'id'    => 'order_dateTime',
				),
			);

			$output = '<div class="szcs_transactions" id="szcs_transactions">';
			$output .= '<table>';
			$output .= '<thead>';
			$output .= '<tr>';
			foreach ($tableColumns as $tableColumn) {
				$output .= '<th>';
				$output .= $tableColumn['label'];
				$output .= '</th>';
			}
			$output .= '</tr>';
			$output .= '</thead>';
			$output .= '<tbody>';
			global $szcs_coupon_transaction;
			$transactions = $szcs_coupon_transaction->get_transactions_by_user_id(get_current_user_id());
			foreach ($transactions as $transaction) {
				$transaction = (array) $transaction;
				$output .= '<tr>';
				foreach ($tableColumns as $tableColumn) {

					$output .= '<td>';
					if ($tableColumn['id'] === 'order_dateTime') {
						$time = strtotime($transaction[$tableColumn['id']]);
						$formattedDate = date('Y-m-d h:i:s A', $time);
						$output .= $formattedDate;
					} else {
						$output .= $transaction[$tableColumn['id']];
					}
					$output .= '</td>';
				}
				$output .= '</tr>';
			}
			$output .= '</tbody>';
			$output .= '</table>';
			$output .= '</div>';
			wp_enqueue_style('jquery-datatable');
			wp_enqueue_script('jquery-datatable');
			wp_enqueue_script('szcs-jquery-datatable');
			wp_enqueue_style('szcs_coupons');


			return $output;
		}
		return '';
	}
}

function szcs_coupon_wallet()
{
	return SzCsCouponWallet::instance();
}

$GLOBALS['szcs_coupon_wallet'] = szcs_coupon_wallet();
