<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

$status_filter = $_GET['status'] ?? '';

$current_category = $_GET['category'] ?? '';

$categories = get_terms([
    'taxonomy'   => 'event_category',
    'hide_empty' => true,
]);
?>

<!-- HERO -->
<div class="events-hero">
    <div class="events-hero-inner">
        <h1>Upcoming Events</h1>

        <p>
            Tastings, live music & curated experiences at Renegade Urban Winery
        </p>
    </div>
</div>

<!-- INTRO -->
<div class="events-intro">
    <div class="events-container">
        <h2>
            More than events.<br>
            Experiences worth showing up for.
        </h2>

        <p>
            Join us for tastings, live music, and thoughtfully crafted gatherings
            that celebrate wine, culture, and connection in the heart of the city.
        </p>
    </div>
</div>

<!-- MAIN -->
<div class="events-page">
    <div class="events-container">
        <div class="events-filter-panel">
            <!-- STATUS FILTERS -->
            <div class="filter-label">Status</div>
            <div class="events-filters status-filter">
                <span class="filter-indicator"></span>
                <button class="filter-btn <?php echo empty($status_filter) ? 'active' : ''; ?>" data-status="" data-category="">All</button>
                <button class="filter-btn <?php echo $status_filter === 'upcoming' ? 'active' : ''; ?>" data-status="upcoming" data-category="">Upcoming</button>
                <button class="filter-btn <?php echo $status_filter === 'sold_out' ? 'active' : ''; ?>" data-status="sold_out" data-category="">Sold Out</button>
                <button class="filter-btn <?php echo $status_filter === 'past' ? 'active' : ''; ?>" data-status="past" data-category="">Past</button>
            </div>

            <!-- CATEGORY FILTERS -->
            <div class="filter-label">Category</div>
            <div class="events-filters category-filter">
                <span class="filter-indicator"></span>
                <button class="filter-btn active" data-status="" data-category="">All Types</button>

                <?php foreach ($categories as $cat): ?>
                    <button class="filter-btn" data-status="" data-category="<?php echo esc_attr($cat->slug); ?>">
                        <?php echo esc_html($cat->name); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (have_posts()): ?>
            <div class="events-grid">
                <?php while (have_posts()): the_post();
                    $status    = get_event_status();
                    $raw_date  = get_field('event_date');
                    $timestamp = strtotime($raw_date);

                    get_template_part(
                        'template-parts/event-card',
                        null,
                        ['event_id' => get_the_ID()]
                    );
                ?>
                <?php endwhile; ?>
            </div>

            <!-- PAGINATION -->
            <div class="pagination" id="pagination">
                <?php
                echo paginate_links([
                    'total'     => $wp_query->max_num_pages,
                    'mid_size'  => 2,
                    'prev_text' => '‹',
                    'next_text' => '›',
                ]);
                ?>
            </div>

        <?php else: ?>
            <p style="text-align:center">
                No events found.
            </p>
        <?php endif; ?>

    </div> <!-- events-container -->
</div> <!-- events-page -->

<?php get_footer(); ?>
