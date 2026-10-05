<?php
/**
 * Plugin Name: Blumoo Video Chapters
 * Description: Chapter navigation for direct HTML5 video, with optional VidoRev metadata support.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: blumoo-video-chapters
 */
namespace Blumoo\VideoChapters;
if (!defined('ABSPATH')) { exit; }

/** Parse the entire batch before saving; invalid lines never partially overwrite data. */
function parse_chapters($text) {
    $chapters = [];
    foreach (preg_split('/\r\n|\r|\n/', trim($text)) as $line) {
        if (trim($line) === '') { continue; }
        if (!preg_match('/^\s*(\d{1,3}):([0-5]\d)(?::([0-5]\d))?\s*\|\s*(.+)$/u', $line, $m)) {
            return new \WP_Error('chapters', __('Use MM:SS | Label or HH:MM:SS | Label on each line.', 'blumoo-video-chapters'));
        }
        $seconds = isset($m[3]) && $m[3] !== '' ? (int)$m[1] * 3600 + (int)$m[2] * 60 + (int)$m[3] : (int)$m[1] * 60 + (int)$m[2];
        $label = sanitize_text_field($m[4]);
        if ($label === '' || strlen($label) > 240 || isset($chapters[$seconds]) || count($chapters) >= 100) {
            return new \WP_Error('chapters', __('Use unique timestamps, nonempty labels (maximum 240 bytes), and at most 100 chapters.', 'blumoo-video-chapters'));
        }
        $chapters[$seconds] = ['start' => $seconds, 'label' => $label];
    }
    ksort($chapters, SORT_NUMERIC);
    return array_values($chapters);
}
function direct_url($url) {
    $url = esc_url_raw(trim($url), ['https', 'http']);
    $parts = wp_parse_url($url);
    return $parts && !empty($parts['host']) && !empty($parts['scheme']) && preg_match('/\.(mp4|webm|ogv)$/i', $parts['path'] ?? '') ? $url : '';
}
function notice_key($id) { return 'blumoo_chapters_' . get_current_user_id() . '_' . $id; }
add_action('add_meta_boxes', function () {
    foreach (['post', 'page'] as $type) {
        add_meta_box('blumoo-chapters', __('Video chapters', 'blumoo-video-chapters'), __NAMESPACE__ . '\\editor', $type);
    }
});
function editor($post) {
    wp_nonce_field('blumoo_chapters_save', 'blumoo_chapters_nonce');
    $draft = get_transient(notice_key($post->ID));
    $rows = get_post_meta($post->ID, '_blumoo_chapters', true);
    $text = '';
    foreach (is_array($rows) ? $rows : [] as $row) {
        $text .= sprintf("%02d:%02d:%02d | %s\n", floor($row['start']/3600), floor($row['start']/60)%60, $row['start']%60, $row['label']);
    }
    $url = get_post_meta($post->ID, '_blumoo_video_url', true);
    if (is_array($draft)) {
        echo '<p role="alert">' . esc_html($draft['error']) . '</p>';
        $text = $draft['text']; $url = $draft['url'];
    }
    echo '<p><label for="blumoo-video-url">' . esc_html__('Direct video URL (optional; otherwise uses VidoRev vm_video_url)', 'blumoo-video-chapters') . '</label></p>';
    echo '<input class="widefat" id="blumoo-video-url" name="blumoo_video_url" type="url" value="' . esc_attr($url) . '">';
    echo '<p><label for="blumoo-chapter-lines">' . esc_html__('One chapter per line: 00:00 | Introduction', 'blumoo-video-chapters') . '</label></p>';
    echo '<textarea class="widefat" rows="8" id="blumoo-chapter-lines" name="blumoo_chapter_lines">' . esc_textarea($text) . '</textarea>';
    echo '<p>' . esc_html__('Insert [blumoo_video_chapters] in the content. Direct MP4, WebM or OGV files only.', 'blumoo-video-chapters') . '</p>';
}
add_action('save_post', function ($id) {
    if (wp_is_post_autosave($id) || wp_is_post_revision($id) || !current_user_can('edit_post', $id)) { return; }
    if (!isset($_POST['blumoo_chapters_nonce']) || !is_string($_POST['blumoo_chapters_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['blumoo_chapters_nonce'])), 'blumoo_chapters_save')) { return; }
    if (!isset($_POST['blumoo_chapter_lines'], $_POST['blumoo_video_url']) || !is_string($_POST['blumoo_chapter_lines']) || !is_string($_POST['blumoo_video_url'])) { return; }
    $text = wp_unslash($_POST['blumoo_chapter_lines']);
    $raw_url = trim(wp_unslash($_POST['blumoo_video_url']));
    $rows = parse_chapters($text); $url = direct_url($raw_url);
    if (is_wp_error($rows) || ($raw_url !== '' && $url === '')) {
        set_transient(notice_key($id), ['text' => $text, 'url' => $raw_url, 'error' => is_wp_error($rows) ? $rows->get_error_message() : __('Use a direct HTTP(S) MP4, WebM or OGV URL. Existing chapter settings were preserved.', 'blumoo-video-chapters')], HOUR_IN_SECONDS);
        return;
    }
    delete_transient(notice_key($id));
    if ($rows) { update_post_meta($id, '_blumoo_chapters', $rows); } else { delete_post_meta($id, '_blumoo_chapters'); }
    if ($url) { update_post_meta($id, '_blumoo_video_url', $url); } else { delete_post_meta($id, '_blumoo_video_url'); }
});
add_action('wp_enqueue_scripts', function () {
    wp_register_style('blumoo-chapters', plugins_url('assets/chapters.css', __FILE__), [], '1.0.0');
    wp_register_script('blumoo-chapter-state', plugins_url('assets/chapter-state.js', __FILE__), [], '1.0.0', true);
    wp_register_script('blumoo-chapters', plugins_url('assets/chapters.js', __FILE__), ['blumoo-chapter-state'], '1.0.0', true);
});
add_shortcode('blumoo_video_chapters', function () {
    $id = get_the_ID();
    if (!$id || post_password_required($id)) { return ''; }
    $post = get_post($id);
    if (!$post || (!is_post_publicly_viewable($post) && !current_user_can('read_post', $id))) { return ''; }
    $rows = get_post_meta($id, '_blumoo_chapters', true);
    $url = direct_url((string)get_post_meta($id, '_blumoo_video_url', true));
    if (!$url) { $url = direct_url((string)get_post_meta($id, 'vm_video_url', true)); }
    if (!$url || !is_array($rows) || !$rows) { return ''; }
    wp_enqueue_style('blumoo-chapters'); wp_enqueue_script('blumoo-chapters');
    $uid = wp_unique_id('blumoo-video-');
    ob_start(); ?>
    <section class="blumoo-chapters" aria-label="<?php esc_attr_e('Video with chapters', 'blumoo-video-chapters'); ?>">
        <video id="<?php echo esc_attr($uid); ?>" controls playsinline preload="metadata" src="<?php echo esc_url($url); ?>"></video>
        <nav aria-label="<?php esc_attr_e('Video chapters', 'blumoo-video-chapters'); ?>"><ol>
        <?php foreach ($rows as $row) : ?>
            <li><button type="button" disabled aria-controls="<?php echo esc_attr($uid); ?>" data-start="<?php echo esc_attr($row['start']); ?>"><span><?php echo esc_html(sprintf('%02d:%02d:%02d', floor($row['start']/3600), floor($row['start']/60)%60, $row['start']%60)); ?></span> <?php echo esc_html($row['label']); ?></button></li>
        <?php endforeach; ?>
        </ol></nav>
        <p class="blumoo-status" role="status" aria-live="polite"></p>
        <noscript><p><?php esc_html_e('Use the video controls to seek to the chapter times listed above.', 'blumoo-video-chapters'); ?></p></noscript>
    </section>
    <?php return ob_get_clean();
});
