<?php
class WC_Gateway_BankTransfer_Hexakode extends WC_Payment_Gateway {

    public function __construct() {
        $this->id = 'hexakode_banktransfer';
        $this->method_title = 'Bank Transfers (Hexakode)';
        $this->has_fields = false;
        $this->supports = ['products', 'subscriptions', 'default', 'virtual'];

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title' => 'Enable',
                'type' => 'checkbox',
                'default' => 'yes',
            ],
            'title' => [
                'title' => 'Title',
                'type' => 'text',
                'default' => 'Bank Transfer',
            ],
            'description' => [
                'title' => 'Customer Instructions',
                'type' => 'textarea',
                'default' => 'Transfer the amount and upload your receipt after checkout.',
            ],
            'bank_accounts_table' => [
            'title' => 'Bank Accounts',
            'type'  => 'bank_accounts_table',
            'description' => 'Enter multiple rows of bank account information.',
            'default' => '',
            ],
        ];

    }

    public function generate_bank_accounts_table_html($key, $data) {
    $option_value = get_option('hexakode_banktransfer_accounts', []);
    ob_start();
    ?>
    <table class="widefat" id="bank-accounts-table">
        <thead>
            <tr>
                <th>Bank Name</th>
                <th>Account Name</th>
                <th>Account Number</th>
                <th>Transit Number</th>
                <th>Branch Name</th>
                <th>Type</th>
                <th>Category</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ((array) $option_value as $i => $row): ?>
            <tr>
                <td><input name="hexakode_banktransfer_accounts[<?= $i ?>][bank_name]" value="<?= esc_attr($row['bank_name']) ?>" /></td>
                <td><input name="hexakode_banktransfer_accounts[<?= $i ?>][account_name]" value="<?= esc_attr($row['account_name']) ?>" /></td>
                <td><input name="hexakode_banktransfer_accounts[<?= $i ?>][account_number]" value="<?= esc_attr($row['account_number']) ?>" /></td>
                <td><input name="hexakode_banktransfer_accounts[<?= $i ?>][transit]" value="<?= esc_attr($row['transit']) ?>" /></td>
                <td><input name="hexakode_banktransfer_accounts[<?= $i ?>][branch]" value="<?= esc_attr($row['branch']) ?>" /></td>
                <td>
                    <select name="hexakode_banktransfer_accounts[<?= $i ?>][type]">
                        <option value="Chequings" <?= selected($row['type'], 'Chequings') ?>>Chequings</option>
                        <option value="Savings" <?= selected($row['type'], 'Savings') ?>>Savings</option>
                    </select>
                </td>
                <td>
                    <select name="hexakode_banktransfer_accounts[<?= $i ?>][category]">
                        <option value="Personal" <?= selected($row['category'], 'Personal') ?>>Personal</option>
                        <option value="Business" <?= selected($row['category'], 'Business') ?>>Business</option>
                    </select>
                </td>
                <td><button class="button remove-bank-row">Remove</button></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p><button id="add-bank-row" class="button">Add Row</button></p>

    <script>
    jQuery(function($) {
        let rowCount = $('#bank-accounts-table tbody tr').length;
        $('#add-bank-row').on('click', function(e) {
            e.preventDefault();
            const newRow = `<tr>
                <td><input name="hexakode_banktransfer_accounts[${rowCount}][bank_name]" /></td>
                <td><input name="hexakode_banktransfer_accounts[${rowCount}][account_name]" /></td>
                <td><input name="hexakode_banktransfer_accounts[${rowCount}][account_number]" /></td>
                <td><input name="hexakode_banktransfer_accounts[${rowCount}][transit]" /></td>
                <td><input name="hexakode_banktransfer_accounts[${rowCount}][branch]" /></td>
                <td>
                    <select name="hexakode_banktransfer_accounts[${rowCount}][type]">
                        <option value="Chequings">Chequings</option>
                        <option value="Savings">Savings</option>
                    </select>
                </td>
                <td>
                    <select name="hexakode_banktransfer_accounts[${rowCount}][category]">
                        <option value="Personal">Personal</option>
                        <option value="Business">Business</option>
                    </select>
                </td>
                <td><button class="button remove-bank-row">Remove</button></td>
            </tr>`;
            $('#bank-accounts-table tbody').append(newRow);
            rowCount++;
        });

        $(document).on('click', '.remove-bank-row', function(e) {
            e.preventDefault();
            $(this).closest('tr').remove();
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

    public function process_admin_options() {
    parent::process_admin_options();
    if (isset($_POST['hexakode_banktransfer_accounts'])) {
        update_option('hexakode_banktransfer_accounts', wc_clean($_POST['hexakode_banktransfer_accounts']));
    }
}

    public function is_available() {
        return 'yes' === $this->get_option('enabled');
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $order->update_status('on-hold', 'Awaiting bank transfer.');
        WC()->cart->empty_cart();
        return ['result' => 'success', 'redirect' => $this->get_return_url($order)];
    }

    public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
		}
	}
}
