<?php

use Dompdf\Dompdf;

/*
|--------------------------------------------------------------------------
| CREATE TICKET TABLE
|--------------------------------------------------------------------------
*/

function rw_create_ticket_table()
{
    global $wpdb;

    $table = $wpdb->prefix . 'event_tickets';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        ticket_id VARCHAR(32) NOT NULL,
        event_id BIGINT NOT NULL,
        order_id BIGINT NOT NULL,
        product_id BIGINT NOT NULL,
        seat_number VARCHAR(20),
        status VARCHAR(20) DEFAULT 'valid',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    $counter_table = $wpdb->prefix . 'event_seat_counter';

    $sql2 = "CREATE TABLE IF NOT EXISTS $counter_table (
        event_id BIGINT PRIMARY KEY,
        last_seat INT DEFAULT 0
    ) $charset;";

    dbDelta($sql2);

    $checkins_table = $wpdb->prefix . 'event_checkins';

    $sql3 = "CREATE TABLE IF NOT EXISTS $checkins_table (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        ticket_id VARCHAR(32) NOT NULL,
        event_id BIGINT NOT NULL,
        scan_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        device TEXT
    ) $charset;";

    dbDelta($sql3);
}

register_activation_hook(__FILE__, 'rw_create_ticket_table');
add_action('after_switch_theme', 'rw_create_ticket_table');

/*
|--------------------------------------------------------------------------
| TICKET ID
|--------------------------------------------------------------------------
*/

function rw_generate_ticket_id()
{
    return 'RW-' . strtoupper(wp_generate_password(12, false));
}

/*
|--------------------------------------------------------------------------
| FIND EVENT FROM PRODUCT
|--------------------------------------------------------------------------
*/

function rw_get_event_by_product($product_id)
{
    $events = get_posts([
        'post_type' => 'event',
        'posts_per_page' => 1,
        'meta_query' => [
            [
                'key' => 'woo_product',
                'value' => '"' . $product_id . '"',
                'compare' => 'LIKE'
            ]
        ]
    ]);

    if (!$events) {
        return null;
    }

    return $events[0]->ID;
}

function rw_generate_ticket_hash($ticket_id)
{
    $secret = wp_salt('auth'); // WordPress secret key

    return hash_hmac('sha256', $ticket_id, $secret);
}

/*
|--------------------------------------------------------------------------
| SEAT GENERATOR
|--------------------------------------------------------------------------
*/

function rw_get_next_seat_number($event_id)
{
    global $wpdb;

    $table = $wpdb->prefix . 'event_tickets';

    $count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE event_id = %d",
            $event_id
        )
    );

    return (int)$count + 1;
}

function rw_get_next_seat($event_id)
{
    global $wpdb;

    $table = $wpdb->prefix . 'event_seat_counter';

    $wpdb->query('START TRANSACTION');

    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT last_seat FROM $table WHERE event_id=%d FOR UPDATE",
            $event_id
        )
    );

    if (!$row) {
        $seat = 1;

        $wpdb->insert($table, [
            'event_id' => $event_id,
            'last_seat' => $seat
        ]);
    } else {
        $seat = $row->last_seat + 1;

        $wpdb->update(
            $table,
            ['last_seat' => $seat],
            ['event_id' => $event_id]
        );
    }

    $wpdb->query('COMMIT');

    return $seat;
}

function rw_format_seat($seat_number)
{
    return 'A-' . str_pad($seat_number, 4, '0', STR_PAD_LEFT);
}

/*
|--------------------------------------------------------------------------
| GENERATE TICKETS
|--------------------------------------------------------------------------
*/

function rw_generate_tickets($order_id)
{
    $order = wc_get_order($order_id);

    if (!$order) {
        return;
    }

    global $wpdb;

    $table = $wpdb->prefix . 'event_tickets';

    foreach ($order->get_items() as $item) {
        /** @var WC_Order_Item_Product $item */
        $quantity   = $item->get_quantity();
        $product_id = $item->get_product_id();

        $event_id = rw_get_event_by_product($product_id);

        if (!$event_id) {
            continue;
        }

        $event_date = get_field('event_date', $event_id);
        $location   = get_field('event_location_details', $event_id);
        $event_name = get_the_title($event_id);

        for ($i = 1; $i <= $quantity; $i++) {
            $ticket_id = rw_generate_ticket_id();

            $seat_number = rw_get_next_seat($event_id);
            $seat        = rw_format_seat($seat_number);

            $wpdb->insert($table, [
                'ticket_id'   => $ticket_id,
                'event_id'    => $event_id,
                'order_id'    => $order_id,
                'product_id'  => $product_id,
                'seat_number' => $seat
            ]);

            $item->add_meta_data("_ticket_{$i}_id", $ticket_id);
            $item->save();

            rw_generate_ticket_pdf(
                $ticket_id,
                $seat,
                $event_date,
                $location,
                $event_name
            );
        }
    }
}

add_action('woocommerce_checkout_order_processed', 'rw_generate_tickets');

/*
|--------------------------------------------------------------------------
| QR GENERATOR
|--------------------------------------------------------------------------
*/

function rw_generate_ticket_qr($ticket_id)
{
    $hash = rw_generate_ticket_hash($ticket_id);

    $url = home_url("/verify-ticket/?ticket={$ticket_id}&hash={$hash}");

    return "https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=" . urlencode($url);
}

/*
|--------------------------------------------------------------------------
| PDF GENERATOR
|--------------------------------------------------------------------------
*/

require_once get_stylesheet_directory() . '/dompdf/autoload.inc.php';

function rw_generate_ticket_pdf($ticket_id, $seat, $date, $location, $event_name)
{
    $qr = rw_generate_ticket_qr($ticket_id);

    $html = "
    <h1>Renegade Urban Winery</h1>

    <h2>$event_name</h2>

    <p><strong>Date:</strong> $date</p>
    <p><strong>Location:</strong> $location</p>
    <p><strong>Seat:</strong> $seat</p>

    <p><strong>Ticket ID:</strong> $ticket_id</p>

    <img src='$qr' width='400'>";

    $options = new \Dompdf\Options();
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $output = $dompdf->output();

    $upload_dir = wp_upload_dir();
    $ticket_dir = $upload_dir['basedir'] . '/tickets';

    if (!file_exists($ticket_dir)) {
        mkdir($ticket_dir, 0755, true);
    }

    file_put_contents(
        $ticket_dir . "/ticket-$ticket_id.pdf",
        $output
    );
}

/*
|--------------------------------------------------------------------------
| EMAIL ATTACHMENT
|--------------------------------------------------------------------------
*/

add_filter('woocommerce_email_attachments', 'rw_attach_ticket_pdfs', 10, 3);

function rw_attach_ticket_pdfs($attachments, $email_id, $order)
{
    if ($email_id !== 'customer_completed_order') {
        return $attachments;
    }

    if (!$order instanceof WC_Order) {
        return $attachments;
    }

    $upload_dir = wp_upload_dir();
    $ticket_dir = $upload_dir['basedir'] . '/tickets';

    foreach ($order->get_items() as $item) {
        $quantity = $item->get_quantity();

        for ($i = 1; $i <= $quantity; $i++) {
            $ticket_id = $item->get_meta("_ticket_{$i}_id");

            if (!$ticket_id) continue;

            $file = $ticket_dir . "/ticket-$ticket_id.pdf";

            if (file_exists($file)) {
                $attachments[] = $file;
            }
        }
    }

    return $attachments;
}

/*
|--------------------------------------------------------------------------
| QR VERIFICATION ENDPOINT
|--------------------------------------------------------------------------
*/

add_action('init', function () {
    add_rewrite_rule(
        '^verify-ticket/?$',
        'index.php?verify_ticket=1',
        'top'
    );
});

add_filter('query_vars', function ($vars) {
    $vars[] = 'verify_ticket';
    return $vars;
});

add_action('template_redirect', function () {
    if (!get_query_var('verify_ticket')) {
        return;
    }
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Ticket Verification</title>

        <style>
            body {
                font-family: Arial;
                background: #111;
                color: white;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
            }

            .card {
                background: white;
                color: black;
                padding: 40px;
                border-radius: 10px;
                text-align: center;
                width: 320px;
            }

            .valid {
                color: green;
            }

            .used {
                color: orange;
            }

            .invalid {
                color: red;
            }
        </style>

    </head>

    <body>
        <div class="card">
            <h2>Checking Ticket...</h2>
            <div id="result"></div>
        </div>

        <script>
            const params = new URLSearchParams(window.location.search);

            const ticket = params.get("ticket");
            const hash = params.get("hash");

            fetch("/wp-json/rw-tickets/v1/verify?ticket=" + ticket + "&hash=" + hash)
                .then(r => r.json())
                .then(data => {
                    let html = "";

                    if (data.status === "valid") {
                        html = `
                            <div class="valid">
                            <h2>✓ VALID TICKET</h2>
                            <p>${data.event}</p>
                            <p>Seat ${data.seat}</p>
                            </div>
                            `;
                    } else if (data.status === "used") {
                        html = `
                            <div class="used">
                            <h2>⚠ TICKET ALREADY USED</h2>
                            </div>
                            `;
                    } else {
                        html = `
                            <div class="invalid">
                            <h2>✕ INVALID TICKET</h2>
                            </div>
                            `;
                    }

                    document.getElementById("result").innerHTML = html;
                });
        </script>
    </body>

    </html>
<?php
    exit;
});

function rw_event_checkin_stats(WP_REST_Request $request)
{
    global $wpdb;

    $event_id = intval($request->get_param('event_id'));

    $tickets_table  = $wpdb->prefix . 'event_tickets';
    $checkins_table = $wpdb->prefix . 'event_checkins';

    $sold = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $tickets_table WHERE event_id=%d",
            $event_id
        )
    );

    $checked = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $checkins_table WHERE event_id=%d",
            $event_id
        )
    );

    return new WP_REST_Response([
        'sold' => (int)$sold,
        'checked' => (int)$checked
    ], 200);
}

function rw_ticket_dashboard()
{
    global $wpdb;

    $table = $wpdb->prefix . 'event_tickets';

    $events = get_posts([
        'post_type' => 'event',
        'numberposts' => -1
    ]);

    echo "<h1>Event Tickets Dashboard</h1>";

    echo "<a href='admin.php?page=scan-tickets' class='button button-primary'>Open Ticket Scanner</a>";

    foreach ($events as $event) {
        $event_id = $event->ID;

        $sold = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE event_id=%d",
                $event_id
            )
        );

        $checked = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE event_id=%d AND status='used'",
                $event_id
            )
        );

        echo "<h2>{$event->post_title}</h2>";

        echo "<ul id='event-stats-$event_id'>";
        echo "<li>Tickets sold: <span class='sold'>$sold</span></li>";
        echo "<li>Check-ins: <span class='checked'>$checked</span></li>";
        echo "<li>Remaining: <span class='remaining'>" . ($sold - $checked) . "</span></li>";
        echo "</ul>";
    }

    echo "<h2>Ticket Lookup</h2>";

    echo "<form method='post'>
        <input type='text' name='ticket_id' placeholder='Enter Ticket ID'>
        <button class='button button-primary'>Lookup</button>
        </form>";
?>
    <script>
        function refreshStats(eventId) {
            fetch("/wp-json/rw-tickets/v1/stats?event_id=" + eventId)
                .then(r => r.json())
                .then(data => {
                    const box = document.getElementById("event-stats-" + eventId);

                    if (!box) return;

                    box.querySelector(".sold").innerText = data.sold;
                    box.querySelector(".checked").innerText = data.checked;
                    box.querySelector(".remaining").innerText = data.sold - data.checked;
                });
        }

        function startLiveStats() {
            document.querySelectorAll("[id^='event-stats-']")
                .forEach(el => {
                    const id = el.id.replace("event-stats-", "");

                    setInterval(() => refreshStats(id), 3000);
                });
        }

        window.addEventListener("load", startLiveStats);
    </script>
<?php
}

function rw_scan_page()
{
?>
    <script src="https://unpkg.com/vconsole/dist/vconsole.min.js"></script>
    <script>
        // Inicijalizacija vConsole
        var vConsole = new window.VConsole();
    </script>

    <div class="wrap">
        <h1>Ticket Scanner</h1>

        <div class="rw-scanner-card">
            <div id="reader"></div>

            <div id="result">
                Ready to scan ticket
            </div>

            <button onclick="restartScanner()" class="button button-primary">
                Scan next ticket
            </button>
        </div>
    </div>
<?php
}

function rw_all_tickets_page()
{
    global $wpdb;

    $table = $wpdb->prefix . 'event_tickets';

    $notice_map = [
        'cancel' => ['Ticket has been cancelled.', 'success'],
        'reset'  => ['Ticket has been reset to "valid".', 'success'],
        'delete' => ['Ticket has been deleted.', 'warning'],
    ];

    if (isset($_GET['rw_notice']) && isset($notice_map[$_GET['rw_notice']])) {
        [$msg, $type] = $notice_map[$_GET['rw_notice']];
        echo "<div class='notice notice-{$type} is-dismissible'><p>{$msg}</p></div>";
    }

    if (isset($_GET['rw_error'])) {
        echo "<div class='notice notice-error is-dismissible'><p>Invalid action.</p></div>";
    }

    // Filter po eventu
    $event_filter = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
    $status_filter = sanitize_text_field($_GET['status'] ?? '');

    $where = 'WHERE 1=1';
    $args  = [];

    if ($event_filter) {
        $where .= ' AND event_id = %d';
        $args[] = $event_filter;
    }

    if ($status_filter) {
        $where .= ' AND status = %s';
        $args[] = $status_filter;
    }

    $query = "SELECT * FROM $table $where ORDER BY created_at DESC";

    $tickets = $args
        ? $wpdb->get_results($wpdb->prepare($query, ...$args))
        : $wpdb->get_results($query);

    // Filter forma
    $events = get_posts(['post_type' => 'event', 'numberposts' => -1]);

    echo "<div class='wrap'><h1>All Tickets</h1>";

    echo "<form method='get' style='margin-bottom:16px;'>
        <input type='hidden' name='page' value='rw-all-tickets'>
        <select name='event_id'>
            <option value=''>— All events —</option>";

    foreach ($events as $ev) {
        $sel = ($event_filter === $ev->ID) ? 'selected' : '';
        echo "<option value='{$ev->ID}' {$sel}>" . esc_html($ev->post_title) . "</option>";
    }

    echo "</select>
        <select name='status'>
            <option value=''>— All statuses —</option>
            <option value='valid'"     . ($status_filter === 'valid'     ? ' selected' : '') . ">Valid</option>
            <option value='used'"      . ($status_filter === 'used'      ? ' selected' : '') . ">Used</option>
            <option value='cancelled'" . ($status_filter === 'cancelled' ? ' selected' : '') . ">Cancelled</option>
        </select>
        <button class='button'>Filter</button>
    </form>";

    // Tabela
    $status_labels = [
        'valid'     => '<span style="color:#1a6b3c;font-weight:600;">✓ Valid</span>',
        'used'      => '<span style="color:#8a5a00;font-weight:600;">● Used</span>',
        'cancelled' => '<span style="color:#7b1c1c;font-weight:600;">✕ Cancelled</span>',
    ];

    echo "<table class='widefat striped'>
        <thead><tr>
            <th>Ticket ID</th>
            <th>Event</th>
            <th>Seat</th>
            <th>Order</th>
            <th>Status</th>
            <th>Created</th>
            <th>Action</th>
        </tr></thead><tbody>";

    foreach ($tickets as $ticket) {
        $event  = esc_html(get_the_title($ticket->event_id));
        $status = $status_labels[$ticket->status] ?? esc_html($ticket->status);

        echo "<tr>
            <td><code>{$ticket->ticket_id}</code></td>
            <td>{$event}</td>
            <td>{$ticket->seat_number}</td>
            <td><a href='" . admin_url("post.php?post={$ticket->order_id}&action=edit") . "'>#{$ticket->order_id}</a></td>
            <td>{$status}</td>
            <td>{$ticket->created_at}</td>
            <td>" . rw_ticket_action_buttons($ticket) . "</td>
        </tr>";
    }

    echo "</tbody></table></div>";
}


function rw_get_order_tickets($order_id)
{
    global $wpdb;

    $table = $wpdb->prefix . 'event_tickets';

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table WHERE order_id = %d",
            $order_id
        )
    );
}

function rw_ticket_assets()
{
    wp_enqueue_style(
        'rw-tickets',
        get_stylesheet_directory_uri() . '/css/tickets.css',
        [],
        '1.0'
    );
}

add_action('wp_enqueue_scripts', 'rw_ticket_assets');

function rw_ticket_scripts()
{
    wp_enqueue_script('html5-qrcode', 'https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js', [], '2.3.8', true);
    wp_enqueue_script(
        'rw-scanner',
        get_stylesheet_directory_uri() . '/scanner.js',
        ['html5-qrcode'],
        time(), // Umesto '1.0' stavljamo trenutno vreme
        true
    );
    // Dodaj ovo: Dinamičko prosleđivanje tačnog REST URL-a u JS fajl
    wp_localize_script('rw-scanner', 'rwTickets', [
        'restUrl' => esc_url_raw(rest_url('rw-tickets/v1/verify'))
    ]);
}

add_action('admin_enqueue_scripts', 'rw_ticket_scripts');

function rw_verify_ticket_json(WP_REST_Request $request)
{
    global $wpdb;

    $ticket = sanitize_text_field($request->get_param('ticket'));
    $hash   = sanitize_text_field($request->get_param('hash'));

    if (!$ticket || !$hash) {
        return new WP_REST_Response([
            'status' => 'invalid',
            'reason' => 'missing_parameters'
        ], 200);
    }

    if (!hash_equals(rw_generate_ticket_hash($ticket), $hash)) {
        return new WP_REST_Response([
            'status' => 'invalid',
            'reason' => 'hash_failed'
        ], 200);
    }

    $table = $wpdb->prefix . 'event_tickets';

    $ticket_data = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table WHERE ticket_id = %s",
            $ticket
        )
    );

    if (!$ticket_data) {
        return new WP_REST_Response([
            'status' => 'invalid',
            'reason' => 'ticket_not_found'
        ], 200);
    }

    if ($ticket_data->status === 'used') {
        return new WP_REST_Response([
            'status' => 'used',
            'ticket' => $ticket
        ], 200);
    }

    if ($ticket_data->status === 'cancelled') {
        return new WP_REST_Response([
            'status' => 'invalid',
            'reason' => 'cancelled'
        ], 200);
    }

    $updated = $wpdb->query(
        $wpdb->prepare(
            "UPDATE $table 
             SET status = 'used' 
             WHERE ticket_id = %s AND status = 'valid'",
            $ticket
        )
    );

    if (!$updated) {
        return new WP_REST_Response([
            'status' => 'used',
            'ticket' => $ticket
        ], 200);
    }

    $checkins_table = $wpdb->prefix . 'event_checkins';

    $wpdb->insert(
        $checkins_table,
        [
            'ticket_id' => $ticket,
            'event_id'  => $ticket_data->event_id,
            'scan_time' => current_time('mysql'),
            'device'    => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]
    );

    return new WP_REST_Response([
        'status' => 'valid',
        'ticket' => $ticket,
        'event'  => get_the_title($ticket_data->event_id),
        'seat'   => $ticket_data->seat_number
    ], 200);
}

add_action('rest_api_init', function () {
    register_rest_route('rw-tickets/v1', '/verify', [
        'methods'             => 'GET',
        'callback'            => 'rw_verify_ticket_json',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('rw-tickets/v1', '/stats', [
        'methods' => 'GET',
        'callback' => 'rw_event_checkin_stats',
        'permission_callback' => '__return_true'
    ]);
});

add_action('admin_post_rw_ticket_action', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Nemate dozvolu.');
    }

    check_admin_referer('rw_ticket_action');

    $ticket_id = sanitize_text_field($_REQUEST['ticket_id'] ?? '');
    $action    = sanitize_text_field($_REQUEST['ticket_action'] ?? '');

    if (!$ticket_id || !in_array($action, ['cancel', 'reset', 'delete'])) {
        wp_redirect(add_query_arg('rw_error', 'invalid', admin_url('admin.php?page=rw-all-tickets')));
        exit;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'event_tickets';

    switch ($action) {
        case 'cancel':
            $wpdb->update($table, ['status' => 'cancelled'], ['ticket_id' => $ticket_id]);
            break;
        case 'reset':
            $wpdb->update($table, ['status' => 'valid'], ['ticket_id' => $ticket_id]);
            break;
        case 'delete':
            $wpdb->delete($table, ['ticket_id' => $ticket_id]);
            break;
    }

    wp_redirect(add_query_arg('rw_notice', $action, admin_url('admin.php?page=rw-all-tickets')));
    exit;
});

function rw_ticket_action_buttons($ticket)
{
    $base = [
        'action'    => 'rw_ticket_action',
        'ticket_id' => $ticket->ticket_id,
        '_wpnonce'  => wp_create_nonce('rw_ticket_action'),
    ];

    $buttons = '';

    if (in_array($ticket->status, ['used', 'cancelled'])) {
        $url = admin_url('admin-post.php?' . http_build_query(array_merge($base, ['ticket_action' => 'reset'])));
        $buttons .= "<a href='{$url}' class='button button-small'>↩ Reset to Valid</a> ";
    }

    if ($ticket->status === 'valid') {
        $url = admin_url('admin-post.php?' . http_build_query(array_merge($base, ['ticket_action' => 'cancel'])));
        $buttons .= "<a href='{$url}' class='button button-small' style='color:#c0392b;' 
                       onclick=\"return confirm('Cancel ticket {$ticket->ticket_id}?')\">✕ Cancel</a> ";
    }

    $url = admin_url('admin-post.php?' . http_build_query(array_merge($base, ['ticket_action' => 'delete'])));
    $buttons .= "<a href='{$url}' class='button button-small' style='color:#888;'
                   onclick=\"return confirm('Delete ticket permanently?')\">🗑</a>";

    return $buttons;
}
