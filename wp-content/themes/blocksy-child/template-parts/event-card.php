<?php
$event_id = $args['event_id'] ?? get_the_ID();
$status = get_event_status($event_id);
$raw_date    = get_field('event_date', $event_id);
$timestamp = strtotime($raw_date);

$woo_product = array_filter((array) get_field('woo_product', $event_id));

$product_id = !empty($woo_product) ? (int) $woo_product[0] : 0;
$product = $product_id ? wc_get_product($product_id) : null;

$permalink = get_permalink($event_id);
?>

<div class="event-card" onclick="window.location='<?php echo esc_url($permalink); ?>'">
    <div class="event-clickable-area">
        <div class="event-image">
            <?php if (has_post_thumbnail($event_id)) : ?>
                <img
                    src="<?php echo get_the_post_thumbnail_url($event_id, 'large'); ?>"
                    alt="<?php echo esc_attr(get_the_title($event_id)); ?>"
                    loading="lazy" />
            <?php endif; ?>

            <!-- STATUS BADGE -->
            <span class="event-badge badge-<?php echo esc_attr($status); ?>">
                <?php echo esc_html(ucfirst(str_replace('_', ' ', $status))); ?>
            </span>

            <!-- CATEGORY BADGES -->
            <div class="event-category-wrapper">
                <?php
                $terms = get_the_terms(get_the_ID(), 'event_category');
                if ($terms):
                    foreach ($terms as $term):
                ?>
                        <span class="event-category-badge">
                            <?php echo esc_html($term->name); ?>
                        </span>
                <?php
                    endforeach;
                endif;
                ?>
            </div>
        </div>

        <div class="event-content">
            <h3 class="event-title">
                <?php echo esc_html(get_the_title($event_id)); ?>
            </h3>

            <div class="event-date">
                <?php echo esc_html(format_event_date($event_id)); ?>
            </div>

            <div class="event-button-wrapper">
                <a href="<?php the_permalink(); ?>" class="btn secondary" onclick="event.stopPropagation();">
                    View Details
                </a>
            </div>
        </div>
    </div>
</div>
