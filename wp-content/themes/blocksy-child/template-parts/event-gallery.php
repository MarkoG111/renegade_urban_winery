<?php

/**
 * Event Gallery Section – single-event.php
 * 
 * Zamijeniti postojeći event-gallery blok ovim kodom.
 * Koristi WordPress galерiju (wp:gallery) ili ACF image repeater.
 * 
 * Zahtijeva: event-gallery.css (poseban fajl)
 */

$gallery_images = [];
$content = get_the_content();

// Rekurzivno prolazi kroz sve blokove i inner blokove
function extract_gallery_blocks($blocks)
{
    foreach ($blocks as $block) {
        if ($block['blockName'] === 'core/gallery') {
            return $block;
        }
        if (!empty($block['innerBlocks'])) {
            $found = extract_gallery_blocks($block['innerBlocks']);
            if ($found) return $found;
        }
    }
    return null;
}

$blocks = parse_blocks($content);
$gallery_block = extract_gallery_blocks($blocks);

if ($gallery_block) {
    if (!empty($gallery_block['innerBlocks'])) {
        foreach ($gallery_block['innerBlocks'] as $img_block) {
            $id = $img_block['attrs']['id'] ?? 0;
            if (!$id) continue;
            $meta = wp_get_attachment_metadata($id);
            $gallery_images[] = [
                'url'    => wp_get_attachment_url($id),
                'sizes'  => [
                    'large'     => wp_get_attachment_image_url($id, 'large'),
                    'thumbnail' => wp_get_attachment_image_url($id, 'thumbnail'),
                ],
                'alt'    => get_post_meta($id, '_wp_attachment_image_alt', true),
                'width'  => $meta['width']  ?? 800,
                'height' => $meta['height'] ?? 600,
            ];
        }
    }

    // Fallback: izvuci ID-eve iz raw innerHTML ako innerBlocks prazan
    if (empty($gallery_images)) {
        preg_match_all('/"id":(\d+)/', $gallery_block['innerHTML'] . wp_json_encode($gallery_block['attrs']), $m);
        // Bolje: skeniraj innerContent
        $raw = implode('', $gallery_block['innerContent']);
        preg_match_all('/class="wp-image-(\d+)"/', $raw, $matches);
        foreach (array_unique($matches[1]) as $id) {
            $id = (int)$id;
            if (!$id) continue;
            $meta = wp_get_attachment_metadata($id);
            $gallery_images[] = [
                'url'    => wp_get_attachment_url($id),
                'sizes'  => [
                    'large'     => wp_get_attachment_image_url($id, 'large'),
                    'thumbnail' => wp_get_attachment_image_url($id, 'thumbnail'),
                ],
                'alt'    => get_post_meta($id, '_wp_attachment_image_alt', true),
                'width'  => $meta['width']  ?? 800,
                'height' => $meta['height'] ?? 600,
            ];
        }
    }
}
?>

<?php if (!empty($gallery_images)) : ?>
    <section class="eg-section">

        <div class="eg-header">
            <div class="eg-header__rule"></div>
            <span class="eg-header__label">Gallery</span>
            <div class="eg-header__rule"></div>
        </div>

        <div class="eg-grid" id="egGrid">
            <?php foreach ($gallery_images as $i => $img) :
                $src_full  = $img['url'];
                $src_thumb = isset($img['sizes']['large']) ? $img['sizes']['large'] : $src_full;
                $alt       = esc_attr($img['alt'] ?: get_the_title());
                $portrait  = ($img['height'] > $img['width']);
            ?>
                <button
                    class="eg-item <?php echo $portrait ? 'eg-item--tall' : ''; ?>"
                    data-index="<?php echo $i; ?>"
                    data-full="<?php echo esc_url($src_full); ?>"
                    aria-label="Open image <?php echo $i + 1; ?>"
                    style="--delay: <?php echo $i * 60; ?>ms">
                    <img
                        src="<?php echo esc_url($src_thumb); ?>"
                        alt="<?php echo $alt; ?>"
                        loading="lazy"
                        decoding="async" />
                    <div class="eg-item__overlay">
                        <svg class="eg-item__zoom" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="11" cy="11" r="7" />
                            <path d="M21 21l-4.35-4.35M11 8v6M8 11h6" />
                        </svg>
                    </div>
                </button>
            <?php endforeach; ?>
        </div>

    </section>

    <!-- LIGHTBOX -->
    <div class="eg-lb" id="egLb" role="dialog" aria-modal="true" aria-label="Image lightbox" hidden>
        <div class="eg-lb__backdrop" id="egBackdrop"></div>

        <button class="eg-lb__close" id="egClose" aria-label="Close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M18 6L6 18M6 6l12 12" />
            </svg>
        </button>

        <button class="eg-lb__nav eg-lb__nav--prev" id="egPrev" aria-label="Previous">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M15 18l-6-6 6-6" />
            </svg>
        </button>

        <div class="eg-lb__stage" id="egStage">
            <img class="eg-lb__img" id="egImg" src="" alt="" />
            <div class="eg-lb__loader" id="egLoader"></div>
        </div>

        <button class="eg-lb__nav eg-lb__nav--next" id="egNext" aria-label="Next">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M9 18l6-6-6-6" />
            </svg>
        </button>

        <div class="eg-lb__counter" id="egCounter"></div>

        <!-- Filmstrip -->
        <div class="eg-lb__strip" id="egStrip">
            <?php foreach ($gallery_images as $i => $img) :
                $thumb = isset($img['sizes']['thumbnail']) ? $img['sizes']['thumbnail'] : $img['url'];
            ?>
                <button class="eg-strip__thumb" data-index="<?php echo $i; ?>" aria-label="Go to image <?php echo $i + 1; ?>">
                    <img src="<?php echo esc_url($thumb); ?>" alt="" loading="lazy" />
                </button>
            <?php endforeach; ?>
        </div>
    </div>

<?php endif; ?>
