<?php
/**
 * Plugin Name: Multistep Checkout
 * Description: A plugin to implement a multi-step checkout process in WooCommerce.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: multistep-checkout
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Multistep_Checkout {

    public function __construct() {
        // Disable payment options on checkout page
        add_filter('woocommerce_cart_needs_payment', '__return_false');

        // Set order status based on total at checkout
        add_action('woocommerce_checkout_order_processed', [$this, 'set_order_status_based_on_total'], 10, 3);

        // Redirect to appropriate page after checkout
        add_action('woocommerce_thankyou', [$this, 'redirect_after_checkout'], 10);

        // Ensure completed orders remain completed
        add_filter('woocommerce_payment_complete_order_status', [$this, 'ensure_completed_orders_remain_completed'], 10, 3);

        // Enqueue Bootstrap CSS and JS
        add_action('wp_enqueue_scripts', [$this, 'enqueue_bootstrap']);

        add_filter('woocommerce_checkout_fields', [$this, 'remove_unnecessary_checkout_fields']);

        add_filter('woocommerce_checkout_fields', [$this, 'reorder_checkout_fields']);


        // Render custom billing form
        add_action('woocommerce_before_checkout_billing_form', [$this, 'render_custom_billing_form']);
    }

    /**
     * Enqueue Bootstrap CSS and JS
     */
    public function enqueue_bootstrap() {
        wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css', [], '5.3.0-alpha3');
        wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js', ['jquery'], '5.3.0-alpha3', true);
    }

    public function remove_unnecessary_checkout_fields($fields) {
        // Hapus field yang tidak diperlukan
        unset($fields['billing']['billing_company']); // Hapus field perusahaan
        unset($fields['billing']['billing_address_2']); // Hapus field alamat tambahan
        unset($fields['billing']['billing_state']); // Hapus field provinsi jika tidak diperlukan
        unset($fields['billing']['billing_postcode']); // Hapus field kode pos
        unset($fields['billing']['billing_country']); // Hapus field negara (jika tetap ingin default)
        unset($fields['billing']['billing_city']); // Hapus field kota

        return $fields;
    }

    public function reorder_checkout_fields($fields) {
        $fields['billing']['billing_first_name']['priority'] = 10;
        $fields['billing']['billing_last_name']['priority'] = 20;
        $fields['billing']['billing_email']['priority'] = 30;
        $fields['billing']['billing_phone']['priority'] = 40;
        $fields['billing']['billing_address_1']['priority'] = 50;

        return $fields;
    }


    /**
     * Render custom billing form using Bootstrap.
     */
    public function render_custom_billing_form() {
        // Contoh data (seharusnya berasal dari WooCommerce atau custom logic)
        $countries = WC()->countries->get_countries();
        $form_data = [
            'first_name'  => '',
            'last_name'   => '',
            'email'       => '',
            'phone'       => '',
            'address'     => '',
            'country'     => '',
            'city'        => '',
            'postal_code' => '',
        ];
        ?>
        <h3 class="mb-3"><?php esc_html_e('Billing Information', 'multistep-checkout'); ?></h3>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="billing_first_name" class="form-label"><?php esc_html_e('First Name', 'multistep-checkout'); ?></label>
                <input type="text" name="billing_first_name" id="billing_first_name" class="form-control" value="<?php echo esc_attr($form_data['first_name']); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="billing_last_name" class="form-label"><?php esc_html_e('Last Name', 'multistep-checkout'); ?></label>
                <input type="text" name="billing_last_name" id="billing_last_name" class="form-control" value="<?php echo esc_attr($form_data['last_name']); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="billing_email" class="form-label"><?php esc_html_e('Email', 'multistep-checkout'); ?></label>
                <input type="email" name="billing_email" id="billing_email" class="form-control" value="<?php echo esc_attr($form_data['email']); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="billing_phone" class="form-label"><?php esc_html_e('Phone Number', 'multistep-checkout'); ?></label>
                <input type="text" name="billing_phone" id="billing_phone" class="form-control" value="<?php echo esc_attr($form_data['phone']); ?>" required>
            </div>
            <div class="col-md-12">
                <label for="billing_address_1" class="form-label"><?php esc_html_e('Address', 'multistep-checkout'); ?></label>
                <input type="text" name="billing_address_1" id="billing_address_1" class="form-control" value="<?php echo esc_attr($form_data['address']); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="billing_country" class="form-label"><?php esc_html_e('Country', 'multistep-checkout'); ?></label>
                <select name="billing_country" id="billing_country" class="form-select" required>
                    <option value=""><?php esc_html_e('Select Country', 'multistep-checkout'); ?></option>
                    <?php foreach ($countries as $code => $name) : ?>
                        <option value="<?php echo esc_attr($code); ?>">
                            <?php echo esc_html($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="billing_city" class="form-label"><?php esc_html_e('City', 'multistep-checkout'); ?></label>
                <input type="text" name="billing_city" id="billing_city" class="form-control" value="<?php echo esc_attr($form_data['city']); ?>" required>
            </div>
        </div>
        <?php
    }

    /**
     * Set order status based on total at checkout
     *
     * @param int $order_id
     * @param array $posted_data
     * @param WC_Order $order
     */
    public function set_order_status_based_on_total($order_id, $posted_data, $order) {
        if ($order->get_total() == 0) {
            $order->update_status('completed');
        } else {
            $order->add_order_note( sprintf( __( 'Order Created. Order ID: #%d', 'multistep-checkout' ), $order->get_id() ) );
            $order->update_status('pending');
        }
    }

    /**
     * Redirect to appropriate page after checkout
     *
     * @param int $order_id
     */
    public function redirect_after_checkout($order_id) {
        $order = wc_get_order($order_id);
        if ($order->get_total() == 0) {
            return;
        }
        if ($order->get_status() === 'pending') {
            wp_safe_redirect($order->get_checkout_payment_url());
            exit;
        }
    }

    /**
     * Ensure completed orders remain completed
     *
     * @param string $status
     * @param int $order_id
     * @param WC_Order $order
     * @return string
     */
    public function ensure_completed_orders_remain_completed($status, $order_id, $order) {
        if ($order->get_status() === 'pending') {
            return 'pending';
        }
        return $status;
    }
}

// Initialize the plugin
new Multistep_Checkout();
