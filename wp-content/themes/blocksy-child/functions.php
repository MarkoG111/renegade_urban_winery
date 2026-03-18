<?php
function winery_styles()
{
    wp_enqueue_style('blocksy-child-style', get_stylesheet_directory_uri() . '/css/style.css', [], filemtime(get_stylesheet_directory() . '/css/style.css'));
    wp_enqueue_style('archive-events', get_stylesheet_directory_uri() . '/css/events/archive-events.css', [], filemtime(get_stylesheet_directory() . '/css/events/archive-events.css'));
    wp_enqueue_style('event-card', get_stylesheet_directory_uri() . '/css/events/event-card.css', [], filemtime(get_stylesheet_directory() . '/css/events/event-card.css'));
    wp_enqueue_style('filters', get_stylesheet_directory_uri() . '/css/events/filters.css', [], filemtime(get_stylesheet_directory() . '/css/events/filters.css'));
    wp_enqueue_style('single-event', get_stylesheet_directory_uri() . '/css/events/single-event.css', [], filemtime(get_stylesheet_directory() . '/css/events/single-event.css'));
    wp_enqueue_style('event-gallery', get_stylesheet_directory_uri() . '/css/events/event-gallery.css', [], filemtime(get_stylesheet_directory() . '/css/events/event-gallery.css'));
    wp_enqueue_style('pagination', get_stylesheet_directory_uri() . '/css/events/pagination.css', [], filemtime(get_stylesheet_directory() . '/css/events/pagination.css'));
}

add_action('wp_enqueue_scripts', function () {
    if (! class_exists('WooCommerce')) return;

    $css_path = get_stylesheet_directory() . '/css/woocommerce/';
    $css_uri  = get_stylesheet_directory_uri() . '/css/woocommerce/';

    if (is_cart()) {
        wp_enqueue_style(
            'winery-cart',
            $css_uri . 'cart.css',
            [],
            filemtime($css_path . 'cart.css')
        );
    }
});

add_action('wp_enqueue_scripts', 'winery_styles');

function renegade_winery_woo_styles()
{
    if (is_cart() || is_checkout() || is_wc_endpoint_url('order-received')) {
        wp_enqueue_style(
            'renegade-woo-custom',
            get_stylesheet_directory_uri() . '/css/woocommerce/renegade-woo.css',
            array(),
            '1.0.0'
        );
    }
}
add_action('wp_enqueue_scripts', 'renegade_winery_woo_styles');

wp_enqueue_script('event-gallery', get_stylesheet_directory_uri() . '/event-gallery.js', [], '1.0', true);

add_filter('woocommerce_checkout_fields', function ($fields) {
    unset($fields['billing']['billing_company']);
    unset($fields['billing']['billing_address_2']);
    unset($fields['billing']['billing_state']);
    unset($fields['billing']['billing_postcode']);
    unset($fields['billing']['billing_city']);
    unset($fields['billing']['billing_address_1']);

    return $fields;
});

add_action('woocommerce_review_order_after_payment', function () {
    echo '<div class="secure-checkout" style="margin-top:20px;font-size:14px;">🔒 Secure payment powered by Stripe</div>';
});

add_action('init', function () {
    add_post_type_support('tribe_events', 'excerpt');
});


add_action('pre_get_posts', function ($query) {
    if (! is_admin() && $query->is_main_query() && is_post_type_archive('event')) {
        $status   = $_GET['status'] ?? '';
        $category = $_GET['category'] ?? '';

        $query->set('posts_per_page', 6);

        $meta_query = [];
        $tax_query  = [];

        if ($status) {
            $meta_query = get_status_meta_query($status);
        }

        if ($category) {
            $tax_query[] = [
                'taxonomy' => 'event_category',
                'field'    => 'slug',
                'terms'    => $category,
            ];
        }

        if ($meta_query) {
            $query->set('meta_query', $meta_query);
        }

        if ($tax_query) {
            $query->set('tax_query', $tax_query);
        }

        $query->set('meta_key', 'event_date');
        $query->set('orderby', 'meta_value');
        $query->set('order', 'ASC');
    }
}, 999);

function get_event_status($event_id = null)
{
    if (!$event_id) {
        $event_id = get_the_ID();
    }

    $event_date = get_field('event_date', $event_id);

    if (!$event_date) {
        return 'unknown';
    }

    $event_timestamp = strtotime($event_date);
    $now_timestamp   = current_time('timestamp');

    if ($event_timestamp < $now_timestamp) {
        return 'past';
    }

    // ACF returns array of IDs
    $woo_product = array_filter((array) get_field('woo_product', $event_id));

    // No product = free event, just check date
    if (empty($woo_product)) {
        return 'upcoming';
    }

    $product_id = (int) $woo_product[0];
    $product    = wc_get_product($product_id);

    if (!$product) {
        return 'unknown';
    }

    if (!$product->is_in_stock()) {
        return 'sold_out';
    }

    return 'upcoming';
}

function format_event_date($event_id = null)
{
    if (! $event_id) {
        $event_id = get_the_ID();
    }

    $raw_date = get_field('event_date', $event_id);

    if (! $raw_date) {
        return '';
    }

    $timestamp = strtotime($raw_date);

    return date_i18n('F j, g:i A', $timestamp);
}

require_once WP_PLUGIN_DIR . '/rw-tickets/tickets.php';

add_action('admin_menu', 'rw_ticket_menu');

function rw_ticket_menu()
{
    add_menu_page(
        'Tickets',
        'Tickets',
        'manage_options',
        'rw-tickets',
        'rw_ticket_dashboard',
        'dashicons-tickets-alt',
        6
    );

    add_submenu_page(
        'rw-tickets',
        'All Tickets',
        'All Tickets',
        'manage_options',
        'rw-all-tickets',
        'rw_all_tickets_page'
    );

    add_submenu_page(
        'rw-tickets',
        'Scanner',
        'Scanner',
        'manage_options',
        'scan-tickets',
        'rw_scan_page'
    );
}

add_filter('woocommerce_payment_complete_order_status', function ($status, $order_id) {
    $order = wc_get_order($order_id);

    // Samo za naše event ordere (nema shipping = nema fizičkih proizvoda)
    if ($order && ! $order->needs_shipping()) {
        return 'completed';
    }

    return $status;
}, 10, 2);

add_filter('gettext', function ($translated, $text, $domain) {
    if ($domain === 'woocommerce') {
        if ($text === 'Product') {
            return 'Ticket';
        }
    }

    return $translated;
}, 20, 3);

add_filter('woocommerce_email_order_items_args', function ($args) {
    $args['show_image'] = false;
    return $args;
});

add_filter('woocommerce_valid_order_statuses_for_order_again', '__return_empty_array');

add_action('init', function () {
    if (isset($_GET['add_to_calendar'])) {
        $event_name = "Renegade Winery Event";
        $event_start = "20260401T190000";
        $event_end = "20260401T210000";

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename=event.ics');

        echo "BEGIN:VCALENDAR
        VERSION:2.0
        BEGIN:VEVENT
        SUMMARY:$event_name
        DTSTART:$event_start
        DTEND:$event_end
        LOCATION:Renegade Urban Winery
        END:VEVENT
        END:VCALENDAR";

        exit;
    }
});
