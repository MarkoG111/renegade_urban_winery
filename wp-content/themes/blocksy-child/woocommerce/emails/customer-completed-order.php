<?php

if (!defined('ABSPATH')) {
    exit;
}

do_action('woocommerce_email_header', $email_heading, $email);

$order_id = $order->get_id();
$tickets = rw_get_order_tickets($order_id);
?>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0">

    <tr>
        <td>

            <p>
                <?php
                if (!empty($order->get_billing_first_name())) {
                    printf('Hi %s,', esc_html($order->get_billing_first_name()));
                } else {
                    echo 'Hi,';
                }
                ?>
            </p>

            <p>
                Your order has been successfully processed and your tickets are ready.
            </p>

        </td>
    </tr>

</table>

<!-- EVENT DETAILS -->

<?php
if (!empty($tickets)) {
    $event_id = $tickets[0]->event_id;

    $event_name = get_the_title($event_id);
    $event_date = get_field('event_date', $event_id);
    $location = get_field('event_location_details', $event_id);
?>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:25px 0;">
        <tr>
            <td style="background:#f6f6f6;padding:20px;border-radius:6px;">

                <h2 style="margin-top:0;">Event Details</h2>

                <p style="margin:5px 0;">
                    <strong>Event:</strong> <?php echo esc_html($event_name); ?>
                </p>

                <p style="margin:5px 0;">
                    <strong>Date:</strong> <?php echo esc_html($event_date); ?>
                </p>

                <p style="margin:5px 0;">
                    <strong>Location:</strong> <?php echo esc_html($location); ?>
                </p>

            </td>
        </tr>
    </table>

<?php } ?>

<!-- TICKETS -->

<?php if ($tickets) : ?>

    <h2>Your Tickets</h2>

    <?php foreach ($tickets as $ticket) :
        $ticket_id = $ticket->ticket_id;
        $seat = $ticket->seat_number;
        $qr = rw_generate_ticket_qr($ticket_id);
    ?>

        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:25px;border:1px solid #eee;border-radius:6px;">
            <tr>

                <td style="padding:20px;width:60%;">

                    <p style="margin:0 0 8px 0;">
                        <strong>Ticket ID:</strong> <?php echo esc_html($ticket_id); ?>
                    </p>

                    <p style="margin:0 0 8px 0;">
                        <strong>Seat:</strong> <?php echo esc_html($seat); ?>
                    </p>

                    <p style="margin:0;">
                        Please present this QR code at the entrance.
                    </p>

                </td>

                <td style="padding:20px;text-align:center;width:40%;">

                    <img src="<?php echo esc_url($qr); ?>" width="140">

                </td>

            </tr>
        </table>

    <?php endforeach; ?>

<?php endif; ?>

<!-- PDF INFO -->

<table role="presentation" width="100%" cellspacing="0" cellpadding="0">
    <tr>
        <td>

            <p>
                Your tickets are also attached as <strong>PDF files</strong> for download or printing.
            </p>

        </td>
    </tr>
</table>

<!-- ORDER DETAILS -->

<?php
do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);
?>

<!-- HELP -->

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:20px;">
    <tr>
        <td>

            <p>
                If you have any questions regarding your tickets, please contact us.
            </p>

            <p>
                We look forward to seeing you at the event.
            </p>

        </td>
    </tr>
</table>

<?php
do_action('woocommerce_email_footer', $email);
?>
