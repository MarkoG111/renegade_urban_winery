<?php
if (!defined('ABSPATH')) exit;

get_header();

while (have_posts()) : the_post();

    $status = get_event_status();
    $woo_product = get_field('woo_product');

    $product_id = !empty($woo_product) ? (int)$woo_product[0] : 0;
    $product = $product_id ? wc_get_product($product_id) : null;

    // $checkout_url = $product_id ? wc_get_checkout_url() . '?add-to-cart=' . $product_id : '';
    $checkout_url = add_query_arg(
        'add-to-cart',
        $product_id,
        wc_get_cart_url()
    );

    $stock_qty = ($product && $product->managing_stock()) ? $product->get_stock_quantity() : null;

    $price = $product ? $product->get_price() : null;

    $host             = get_field('event_host');
    $duration         = get_field('event_duration');
    $location_details = get_field('event_location_details');
    $highlights_raw = get_field('event_highlights');
    $highlights = $highlights_raw
        ? array_filter(array_map('trim', explode("\n", $highlights_raw)))
        : [];
?>
    <!-- HERO (FULL WIDTH, VAN CONTAINERA) -->
    <section class="event-hero">
        <?php if (has_post_thumbnail()) : ?>
            <div class="event-hero-image">
                <?php the_post_thumbnail('full'); ?>
            </div>
        <?php endif; ?>


        <div class="event-hero-overlay">

        </div>
    </section>

    <!-- CONTENT (OVO SME BITI CONTAINER) -->
    <section class="event-main">
        <div class="event-container">
            <div class="event-grid">
                <!-- LEFT COLUMN -->
                <div class="event-left">
                    <!-- Status pill -->
                    <div class="single-event-status single-badge-<?php echo esc_attr($status); ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                    </div>

                    <h2 class="single-event-categories">
                        <?php
                        $terms = get_the_terms(get_the_ID(), 'event_category');
                        if ($terms && !is_wp_error($terms)) {
                            foreach ($terms as $term) {
                                echo '<span class="single-event-category">' . esc_html($term->name) . '</span>';
                            }
                        }
                        ?>
                    </h2>

                    <!-- Title (desktop — hero covers mobile) -->
                    <h2 class="single-event-title">
                        <?php the_title(); ?>
                    </h2>

                    <!-- Date -->
                    <div class="single-event-date">
                        <?php echo esc_html(format_event_date()); ?>
                    </div>

                    <!-- Gold rule divider -->
                    <div class="event-divider"></div>

                    <!-- Event body copy -->
                    <div class="single-event-description">
                        <?php echo get_field('event_excerpt'); ?>
                    </div>

                    <!-- ── META STRIP ─────────────────────────── -->
                    <?php if ($host || $duration || $location_details) : ?>
                        <div class="event-meta-strip">

                            <?php if ($host) : ?>
                                <div class="event-meta-item">
                                    <div class="event-meta-icon">
                                        <!-- person icon -->
                                        <svg viewBox="0 0 24 24">
                                            <circle cx="12" cy="7" r="4" />
                                            <path d="M4 21v-1a8 8 0 0116 0v1" />
                                        </svg>
                                    </div>
                                    <div class="event-meta-body">
                                        <div class="event-meta-label">Hosted by</div>
                                        <div class="event-meta-value"><?php echo esc_html($host); ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($duration) : ?>
                                <div class="event-meta-item">
                                    <div class="event-meta-icon">
                                        <!-- clock icon -->
                                        <svg viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="9" />
                                            <path d="M12 7v5l3 3" />
                                        </svg>
                                    </div>
                                    <div class="event-meta-body">
                                        <div class="event-meta-label">Duration</div>
                                        <div class="event-meta-value"><?php echo esc_html($duration); ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($location_details) : ?>
                                <div class="event-meta-item">
                                    <div class="event-meta-icon">
                                        <!-- pin icon -->
                                        <svg viewBox="0 0 24 24">
                                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" />
                                            <circle cx="12" cy="9" r="2.5" />
                                        </svg>
                                    </div>
                                    <div class="event-meta-body">
                                        <div class="event-meta-label">Location</div>
                                        <div class="event-meta-value"><?php echo esc_html($location_details); ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div><!-- /.event-meta-strip -->
                    <?php endif; ?>
                </div>

                <!-- RIGHT COLUMN -->
                <div class="event-right">
                    <!-- STOCK -->
                    <?php if ($stock_qty !== null && $stock_qty > 0 && $status === 'upcoming') : ?>
                        <div class="spots-left">
                            <?php echo esc_html($stock_qty); ?> spots remaining
                        </div>
                    <?php endif; ?>

                    <div class="event-book-box">
                        <?php if ($status === 'upcoming' && $product) : ?>
                            <div class="event-price">
                                £<?php echo esc_html(number_format((float)$price, 0)); ?>
                            </div>
                            <div class="event-price-label">Per person · includes welcome glass</div>

                            <a href="<?php echo esc_url($checkout_url); ?>" class="btn primary full">
                                Reserve Your Seat
                            </a>

                            <div class="book-guarantee">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                </svg>
                                Secure checkout &nbsp;·&nbsp; Instant confirmation
                            </div>

                        <?php elseif ($status === 'sold_out') : ?>
                            <div class="event-price">Sold Out</div>
                            <div class="event-price-label">This event is fully booked</div>
                            <div class="btn disabled full">Join Waitlist</div>

                            <div class="book-guarantee">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M12 8v4M12 16h.01" />
                                </svg>
                                We'll notify you if spots open up
                            </div>

                        <?php else : ?>
                            <div class="event-price">Finished</div>
                            <div class="event-price-label">This event has taken place</div>
                            <div class="btn disabled full">Event Ended</div>

                            <div class="book-guarantee">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
                                </svg>
                                View upcoming events below
                            </div>
                        <?php endif; ?>
                    </div><!-- /.event-book-box -->

                    <!-- Highlights -->
                    <?php if (!empty($highlights)) : ?>
                        <ul class="highlights-list">
                            <?php foreach ($highlights as $i => $item) : ?>
                                <li class="highlight-item">
                                    <span class="highlight-item__bullet"><?php echo $i + 1; ?></span>
                                    <span class="highlight-item__text"><?php echo esc_html($item); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
    </section>
<?php endwhile; ?>

<?php
get_template_part('template-parts/event-gallery');
?>

<?php get_footer(); ?>
