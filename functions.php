<?php
/**
* Enqueues child theme stylesheet, loading first the parent theme stylesheet.
*/
function hpc_custom_enqueue_child_theme_styles() {
    wp_enqueue_style( 'parent-theme-css', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'hpc_custom_enqueue_child_theme_styles' );

/**
*  Register widgetized areas.
*/
function hps_widgets_init() {
	register_sidebar( array(
		'name'          => 'HPS Widget Area',
		'id'            => 'hps_widget_area',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h2 class="hps-widget-area">',
		'after_title'   => '</h2>'
	) );
}
add_action( 'widgets_init', 'hps_widgets_init' );

function restricted_prescription_widgets_init() {
	register_sidebar( array(
		'name'          => 'Restricted Prescription',
		'id'            => 'restricted_prescription_widget_area',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h2 class="restricted-prescription-widget-area">',
		'after_title'   => '</h2>'
	) );
}
add_action( 'widgets_init', 'restricted_prescription_widgets_init' );

function restricted_order_widgets_init() {
	register_sidebar( array(
		'name'          => 'Restricted Order',
		'id'            => 'restricted_order_widget_area',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h2 class="restricted-order-widget-area">',
		'after_title'   => '</h2>'
	) );
}
add_action( 'widgets_init', 'restricted_order_widgets_init' );

function mobile_shop_filter_widgets_init() {
	register_sidebar( array(
		'name'          => 'Mobile Shop Filter Bar',
		'id'            => 'mobile_shop_filter_bar_widget_area',
		'class'			=> 'mobile-shop-filter-bar-widget-area',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h2 class="mobile-shop-filter-bar-widget-area-heading">',
		'after_title'   => '</h2>'
	) );
}
add_action( 'widgets_init', 'mobile_shop_filter_widgets_init' );

/**
 * Display restricted product warning on the product page.
*/
function restricted_product_content(){
  
	if (function_exists('get_field')){

		$restricted_product = get_field('restricted_prescription_required');

		if ($restricted_product && $restricted_product[0] == 'required') {

			if ( is_active_sidebar( 'restricted_prescription_widget_area' ) ) {
					dynamic_sidebar( 'restricted_prescription_widget_area' );
			}
		}
	}
	
}
add_action( 'woocommerce_before_add_to_cart_button', "restricted_product_content", 10 );

  
/**
 * @snippet       Add restricted product custom field to Woocommerce checkout
 * @source        Inspired by https://businessbloomer.com/?p=73583
 * @author        Damian Davila inspired by work of Rodolfo Melogli
 * @compatible    Woo 3.3.2
 */
 
// -------------------------------
// Display restricted product pre-approval code field if order contains a restricted product

function hpc_restricted_product_in_cart( $checkout ) {
    
	// Ensure ACF and this custom field exists
	if ( ! function_exists('get_field') ) {
		return false;
	}

	// Check each item in cart for ACF custom field
	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {        
		$product_id   = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );          

		$restricted_product = get_field('restricted_prescription_required', $product_id);

		if ( $restricted_product && $restricted_product[0] == 'required' ) {
			return true;
		}

	}
	// end of loop, return false
	return false;

}

function hpc_display_restricted_product_approval_field( $checkout ) {
    
	if ( hpc_restricted_product_in_cart( $checkout ) ) {

		if ( is_active_sidebar( 'restricted_order_widget_area' ) ) {
			dynamic_sidebar( 'restricted_order_widget_area' );
		}

		echo '<div id="restricted-product-in-cart"><h3>Restricted Product</h3>';
			
		woocommerce_form_field( 'restricted_product_approval_code', array(
					'type'          => 'text',
					'class'         => array('form-row-wide'),
					'id'            => 'restricted-product-approval-code',
					'required'      => true,
					'label'         => __('Approval code'),
					'placeholder'       => __('Enter the code provided by Healing Paws Center'),
					// ), $checkout->get_value( 'restricted_product_approval_code' ));
				));
			
		echo '</div>';
	}
    
}
add_action( 'woocommerce_review_order_before_submit', 'hpc_display_restricted_product_approval_field' );

// -------------------------------
// Save & show restricted product pre-approval code as order meta
 
function hpc_save_restricted_product_approval_code( $order_id ) {
    
    global $woocommerce;
    
    if ( $_POST['restricted_product_approval_code'] ) update_post_meta( $order_id, '_restricted_product_approval_code', esc_attr( $_POST['restricted_product_approval_code'] ) );
    
}
add_action( 'woocommerce_checkout_update_order_meta', 'hpc_save_restricted_product_approval_code' );
 
function hpc_restricted_product_approval_code_display_admin_order_meta( $order ) {
    
   echo '<p><strong>Restricted product approval:</strong> ' . get_post_meta( $order->get_id(), '_restricted_product_approval_code', true ) . '</p>';
    
} 
add_action( 'woocommerce_admin_order_data_after_billing_address', 'hpc_restricted_product_approval_code_display_admin_order_meta' );


// -------------------------------
// Validate the restricted product approval field

function hpc_validate_new_checkout_fields() {   
    
	if ( isset( $_POST['restricted_product_approval_code'] ) ) {

		if ( empty( $_POST['restricted_product_approval_code'] ) || ! hpc_valid_approval_code($_POST['restricted_product_approval_code']) ) {

        wc_add_notice( __( 'Please enter a valid Restricted Product approval code' ), 'error' );
		}

	}
}

// After some false starts, decision is to use one unchanging approval code for all customers <sigh>
function hpc_valid_approval_code($approval_code) {

    $approval_code = strtolower( trim($approval_code) );
    
    if ( $approval_code == 'morelife!' ) {
			return true;
	} else {
			return false;
	}

}
add_action( 'woocommerce_checkout_process', 'hpc_validate_new_checkout_fields' );

/**
 * @snippet       Add Content to the Customer Processing Order Emails - WooCommerce
 * @author        Damian Davila adapted from Rodolfo Melogli
 * @testedwith    Woo 3.5.1
 */
 
add_action( 'woocommerce_email_before_order_table', 'hpc_store_add_content_woo_email', 20, 4 );
 
function hpc_store_add_content_woo_email( $order, $sent_to_admin, $plain_text, $email ) {
    $my_acct_page = site_url( '/my-account/', 'https' );
    echo '<p class="email-myacct-msg">You can manage your account details including Autoship subscriptions on the <a href="' . $my_acct_page . '">My Account page</a></p>';
}

/**
 * @snippet       Remove weight and tracking number from shipping label - WebToffee Woocommerce label plugin
 * @author        from plugin support pages on Wordpress.com
 */

add_filter('wf_pklist_add_custom_css','wt_pklist_add_custom_css_shippinglabel', 10, 2);

function wt_pklist_add_custom_css_shippinglabel($custom_css, $template_type)
{
	if($template_type=='shippinglabel')
	{
		$custom_css.='.wfte_weight{ display:none !important;}.wfte_invoice_number{display:none !important;}';
	}
	return $custom_css;
}

/**
 * @snippet       Remove phone number and email address from the shipping label - WebToffee WooCommerce label plugin
 * @author        from Webtoffee support site
 */

/**
 * updated 15Jun2020
 *
 */ 
add_filter('wf_pklist_alter_additional_fields', 'wt_pklist_alter_additional_fields_fn', 10, 3);

function wt_pklist_alter_additional_fields_fn($extra_fields, $template_type, $order)
{
	if($template_type == 'shippinglabel')
	{
		unset($extra_fields['contact_number']);
		unset($extra_fields['email']);
	}
	return $extra_fields;
}

/** 
 * end update; original follows -vv-
 *


add_filter('wf_alter_billing_phone_number', 'wt_alter_billing_phone_number', 10,2);

function wt_alter_billing_phone_number($number, $order_id){

    $number = '';
    return $number;
}

//Hide phone number text
add_filter('wf_alter_billing_phone_text', 'wt_alter_billing_phone_text', 10,1);

function wt_alter_billing_phone_text($text){

    $text = '';
    return $text;
}

//Hide email

add_filter('wf_alter_billing_email', 'wt_alter_billing_email', 10,2);

function wt_alter_billing_email($email, $order_id){

    $email = '';
    return $email;
}

//Hide email text

add_filter('wf_alter_billing_email_text', 'wt_alter_billing_email_text', 10,1);

function wt_alter_billing_email_text($text){

    $text = '';
    return $text;
}

** End original **/

/**
 * @snippet       Add search and filter bar at top of shop page on mobile devices
 * @author        Damian Davila
 * @testedwith    Woo 3.6.3
 */

// priority = 15, so after notices but before result count on shop page
add_action( 'woocommerce_before_shop_loop', 'hpc_store_add_mobile_shop_filter_bar', 15 );
 
function hpc_store_add_mobile_shop_filter_bar() {
	if ( is_active_sidebar( 'mobile_shop_filter_bar_widget_area' ) ) {
		dynamic_sidebar( 'mobile_shop_filter_bar_widget_area' );
	}
}

/**
 * @snippet		Fix sticky positioning on Android devices
 * @author		Damian Davila
 * @testedwith	Woo 3.6.3
 */

add_action( 'wp_head', 'hpc_store_add_viewport_meta_tag' , '1' );

function hpc_store_add_viewport_meta_tag() {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">';
}
