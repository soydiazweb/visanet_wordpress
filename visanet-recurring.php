<?php
/*
 * Plugin Name: WooCommerce Visanet Recurring Payment
 * Plugin URI: https://github.com/soydiazweb/visanet_wordpress
 * Description: Take credit card payments on your store.
 * Author: SoyDiaz
 * Version: 1.0.1
 */


/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'visanet_recurring_add_gateway_class' );
function visanet_recurring_add_gateway_class( $gateways ) {
    $gateways[] = 'WC_Visanet_Recurring_Gateway'; // your class name is here
    return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'visanet_recurring_init_gateway_class' );

function visanet_recurring_init_gateway_class() {

    class WC_Visanet_Recurring_Gateway extends WC_Payment_Gateway {

        /**
         * Class constructor, more about it in Step 3
         */
        public function __construct() {

            $this->id = 'visa_recurring'; // payment gateway plugin ID
            $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = 'Visa Recurring';
            $this->method_description = 'Visa Recurring payment gateway'; // will be displayed on the options page

            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this tutorial we begin with simple payments
            $this->supports = array(
                'products'
            );

            // Method with all the options fields
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->enabled = $this->get_option( 'enabled' );
            $this->testmode = 'yes' === $this->get_option( 'testmode' );
            $this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
            $this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );

            // This action hook saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            // We need custom JavaScript to obtain a token
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
        }

        /**
         * Plugin options, we deal with it in Step 3 too
         */
        public function init_form_fields(){

            $this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Enable/Disable',
                    'label'       => 'Enable Misha Gateway',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default'     => 'Credit Card',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default'     => 'Pay with your credit card via our super-cool payment gateway.',
                ),
            );
    
        }

        /**
         * You will need it if you want your custom credit card form, Step 4 is about it
         */
        public function payment_fields() {

            // ok, let's display some description before the payment form
            if ( $this->description ) {
                // you can instructions for test mode, I mean test card numbers etc.
                if ( $this->testmode ) {
                    $this->description .= ' TEST MODE ENABLED.';
                    $this->description  = trim( $this->description );
                }
                // display the description with <p> tags etc.
                echo wpautop( wp_kses_post( $this->description ) );
            }
         
            // I will echo() the form, but you can close PHP tags and print it directly in HTML
            echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
         
            // Add this action hook if you want your custom payment gateway to support it
            do_action( 'woocommerce_credit_card_form_start', $this->id );
         
            // I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
            echo '
                <div class="form-row form-row-wide">
                    <label>Tipo de donación <span class="required">*</span></label>
                    <select name="visa_donacion" id="visa_donacion">
                        <option value="monthly">Mensual</option>
                        <option value="semi-annually">2 veces al año</option>
                        <option value="annually">Anual</option>
                    </select> 
                </div>
                <div class="form-row form-row-wide" style="display: none;">
                    <label>Device Finger Print <span class="required">*</span></label>
                    <input name="visa_device" id="visa_device" type="text" autocomplete="off">
                </div>
                <div class="form-row form-row-wide">
                    <label>Card Number <span class="required">*</span></label>
                    <input name="visa_ccNo" id="visa_ccNo" type="text" autocomplete="off">
                </div>
                <div class="form-row form-row-first">
                    <label>Expiry Date <span class="required">*</span></label>
                    <select name="visa_expireMM" id="visa_expireMM">
                        <option value="">Month</option>
                        <option value="01">January</option>
                        <option value="02">February</option>
                        <option value="03">March</option>
                        <option value="04">April</option>
                        <option value="05">May</option>
                        <option value="06">June</option>
                        <option value="07">July</option>
                        <option value="08">August</option>
                        <option value="09">September</option>
                        <option value="10">October</option>
                        <option value="11">November</option>
                        <option value="12">December</option>
                    </select> 
                    <select name="visa_expireYY" id="visa_expireYY">
                        <option value="">Year</option>
                        <option value="22">2022</option>
                        <option value="23">2023</option>
                        <option value="24">2024</option>
                        <option value="25">2025</option>
                        <option value="26">2026</option>
                        <option value="27">2027</option>
                        <option value="28">2028</option>
                        <option value="29">2029</option>
                        <option value="30">2030</option>
                        <option value="31">2031</option>
                        <option value="32">2032</option>
                        <option value="33">2033</option>
                        <option value="34">2034</option>
                        <option value="35">2035</option>
                    </select>
                </div>
                <div class="form-row form-row-last">
                    <label>Card Code (CVC) <span class="required">*</span></label>
                    <input id="visa_cvv" name="visa_cvv" type="password" autocomplete="off" placeholder="CVC">
                </div>
                <div class="clear"></div>';
         
            do_action( 'woocommerce_credit_card_form_end', $this->id );
         
            echo '<div class="clear"></div></fieldset>';
                 
        }

        /*
         * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
         */
        public function payment_scripts() {
            wp_enqueue_script( 'woocommerce_visa_recurring', plugins_url( 'visa_recurring.js', __FILE__ ), array( 'jquery' ),'',true );
        }

        /*
         * Fields validation, more in Step 5
         */
        public function validate_fields() {
            
            $card_number = str_replace( array(' ', '-' ), '', $_POST['visa_ccNo'] ); 
            $card_cvv=(isset($_POST['visa_cvv'])) ? $_POST ['visa_cvv'] : ''; 
            $card_exp_month =  $_POST['visa_expireMM'];
            $card_exp_year =  $_POST['visa_expireYY'];
         
            // Check card number 
            if(empty($card_number) || !ctype_digit($card_number)) { 
                wc_add_notice('Card number is required'.' '.$card_type , 'error'); 
                return false; 
            } 
             
            // Check card security code 
             
            if(!ctype_digit($card_cvv)) { 
                wc_add_notice('Card security code is invalid (only digits are allowed)', 'error'); 
                return false; 
            } 
            if(strlen($card_cvv) <3) { 
                wc_add_notice('Card security code, invalid length', 'error'); 
                return false; 
            } 
         
            if(empty($card_exp_year)) { 
                wc_add_notice('Card expiration year is required', 'error'); 
                return false; 
            }else{ 
                if(strlen($card_exp_year)==1 ||strlen($card_exp_year)==3||strlen($card_exp_year)>4) { 
                    wc_add_notice('Card expiration year is invalid', 'error'); 
                    return false; 
                } 
         
                if(strlen($card_exp_year)==2) { 
                    if((int)$card_exp_year < (int)substr(date('Y'), -2)) { 
                        wc_add_notice('Card expiration year is invalid 1', 'error'); 
                        return false; 
                    } 
                } 
         
                if(strlen($card_exp_year)==4) { 
                    if((int)$card_exp_year < (int)date('Y')) { 
                        wc_add_notice('Card expiration year is invalid', 'error'); 
                        return false; 
                    } 
                } 
            } 
            if(empty($card_exp_month)) { 
                wc_add_notice('Card expiration mont is required', 'error'); 
                return false; 
            }else{ 
                if((int)$card_exp_month>12 || (int)$card_exp_month<1) { 
                    wc_add_notice('Card expiration month is invalid', 'error'); 
                    return false; 
                } 
            } 
         
             
         
            //wc_add_notice('Card number is invalid', 'error'); 
            return true; 
        }

        /*
         * We're processing the payments here, everything about it is in Step 5
         */
        public function process_payment( $order_id ) {

            global $woocommerce;
 
            // we need it to get any order detailes
            $order = wc_get_order( $order_id );
            $order_data = $order->get_data(); // The Order data

            /*
             * Array with parameters for API interaction
             */
            $args = array();
         
            #Payment Information
            $args['payment']['id'] = $order_data['id'];
            $args['payment']['total'] = $order_data['total'];
            $args['payment']['customer_id'] = $order_data['total'];
            $args['payment']["tarjeta"] = $_POST["visa_ccNo"];
            $args['payment']["mes"] = $_POST["visa_expireMM"];
            $args['payment']["anio"] = $_POST["visa_expireYY"];
            $args['payment']['cvv'] = $_POST['visa_cvv'];
            $args['payment']['donacion'] = $_POST['visa_donacion'];
            $args['payment']['device'] = $_POST['visa_device'];

            ## BILLING INFORMATION:
            $args['billing']['first_name'] = $order_data['billing']['first_name'];
            $args['billing']['last_name'] = $order_data['billing']['last_name'];
            $args['billing']['address_1'] = $order_data['billing']['address_1'];
            $args['billing']['email'] = $order_data['billing']['email'];
            $args['billing']['phone'] = $order_data['billing']['phone'];
         
            /*
             * Your API interaction could be built with wp_remote_post()
             */
            //$response = wp_remote_post( plugin_dir_url( __FILE__ ) . 'transaccion.php', array(
            $response = wp_remote_post( 'https://www.tusitio.com/wp-content/plugins/visanet-recurring/transaccion.php', array(
                    'method'      => 'POST',
                    'body'        => $args,
                )
            );

            if( !is_wp_error( $response ) ) {
                if ( $response['body'] == 'APPROVED' ) {
                    // we received the payment
                    $order->payment_complete();
                    $order->reduce_order_stock();
         
                    // some notes to customer (replace true with false to make it private)
                    $order->add_order_note( 'Donación realizada con éxito', true );
         
                    // Empty cart
                    $woocommerce->cart->empty_cart();
         
                    // Redirect to the thank you page
                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url( $order )
                    );
                 } else {
                    wc_add_notice(  'Please try again.', 'error' );
                    return;
                }
         
            } else {
                wc_add_notice(  'Connection error.', 'error' );
                return;
            }
 
                    
        }

    }
}