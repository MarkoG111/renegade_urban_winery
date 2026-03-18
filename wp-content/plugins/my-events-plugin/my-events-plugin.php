<?php
/*
Plugin Name: My Events Plugin
Description: Custom events functionality
Version: 1.0
Author: Gacho
*/

function my_scripts()
{
    wp_enqueue_script(
        'event-filter',
        plugin_dir_url(__FILE__) . 'event-filter.js',
        [],
        null,
        true
    );

    wp_localize_script('event-filter', 'eventFilter', [
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
}

add_action('wp_enqueue_scripts', 'my_scripts');

add_action('wp_ajax_filter_events', 'filter_events');
add_action('wp_ajax_nopriv_filter_events', 'filter_events');

function filter_events()
{
    $status   = $_POST['status'] ?? '';
    $category = $_POST['category'] ?? '';
    $page     = $_POST['page'] ?? 1;

    $args = [
        'post_type'      => 'event',
        'posts_per_page' => 6,
        'paged' => $page,
    ];

    if ($status) {
        $args['meta_query'] = get_status_meta_query($status);
    }

    if ($category) {
        $args['tax_query'] = [[
            'taxonomy' => 'event_category',
            'field'    => 'slug',
            'terms'    => $category,
        ]];
    }

    $args['meta_key'] = 'event_date';
    $args['orderby'] = 'meta_value';
    $args['order'] = 'ASC';

    $query = new WP_Query($args);

    ob_start();

    if ($query->have_posts()):
        while ($query->have_posts()):
            $query->the_post();

            get_template_part('template-parts/event-card');
        endwhile;
    else:
?>
        <div class="no-events">
            <div class="no-events-icon">
                📅
            </div>
            <h3>No events found</h3>
            <p>
                There are no events matching your selected filters.<br>
                Try selecting a different category or status.
            </p>

            <a href="#" class="reset-filters-btn">
                Reset filters
            </a>
        </div>
<?php
    endif;

    $cards = ob_get_clean();

    $pagination = paginate_links([
        'total' => $query->max_num_pages,
        'current' => $page,
        'type' => 'plain',
        'prev_text' => '‹',
        'next_text' => '›',
    ]);

    wp_reset_postdata();

    wp_send_json([
        'cards' => $cards,
        'pagination' => $pagination,
    ]);
}

function get_status_meta_query($status)
{
    $now = current_time('Y-m-d H:i:s');

    switch ($status) {
        case 'upcoming':
            return [
                [
                    'key'     => 'event_date',
                    'value'   => $now,
                    'compare' => '>=',
                    'type'    => 'DATETIME',
                ],
                [
                    'key'     => 'is_sold_out',
                    'value'   => '1',
                    'compare' => '!=',
                ],
            ];

        case 'past':
            return [
                [
                    'key'     => 'event_date',
                    'value'   => $now,
                    'compare' => '<',
                    'type'    => 'DATETIME',
                ],
            ];

        case 'sold_out':
            return [
                [
                    'key'     => 'is_sold_out',
                    'value'   => '1',
                    'compare' => '=',
                ],
            ];

        default:
            return [];
    }
}

add_action('init', function () {
    register_taxonomy(
        'event_category',
        'event',
        [
            'label'             => 'Event Categories',
            'public'            => true,
            'hierarchical'      => true,
            'rewrite'           => [
                'slug' => 'event-category',
            ],
            'show_admin_column' => true,
            'show_in_rest'      => true,
        ]
    );
});
