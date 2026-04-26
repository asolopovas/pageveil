<?php
/**
 * Plugin Name:       Pageveil
 * Plugin URI:        https://github.com/asolopovas/pageveil
 * Description:       Veil the site with any chosen Gutenberg page — a chrome-free under-construction screen.
 * Version:           0.0.1
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Andrius Solopovas
 * Author URI:        https://github.com/asolopovas
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       pageveil
 * Domain Path:       /languages
 * Update URI:        false
 * Network:           false
 */

declare(strict_types=1);

namespace Pageveil;

if (!defined('ABSPATH')) exit;

const OPTION = 'pageveil';
const CAP    = 'manage_options';

final class Settings
{
    public function defaults(): array { return ['enabled' => false, 'page_id' => 0]; }

    public function get(): array
    {
        $stored = get_option(OPTION, []);
        $merged = array_merge($this->defaults(), is_array($stored) ? $stored : []);
        return ['enabled' => (bool) $merged['enabled'], 'page_id' => (int) $merged['page_id']];
    }

    public function sanitize(array $input): array
    {
        return ['enabled' => !empty($input['enabled']), 'page_id' => max(0, (int) ($input['page_id'] ?? 0))];
    }

    public function save(array $input): array
    {
        $clean = $this->sanitize($input);
        update_option(OPTION, $clean);
        return $clean;
    }

    public function active(): bool { $o = $this->get(); return $o['enabled'] && $o['page_id'] > 0; }
    public function pageId(): int  { return $this->get()['page_id']; }
}

final class Renderer
{
    public function bypass(): bool
    {
        if (defined('WP_CLI') && WP_CLI) return true;
        if (function_exists('wp_doing_cron') && wp_doing_cron()) return true;
        if (function_exists('wp_doing_ajax') && wp_doing_ajax()) return true;
        if (defined('REST_REQUEST') && REST_REQUEST) return true;
        if (function_exists('is_admin') && is_admin()) return true;
        if (function_exists('is_user_logged_in') && is_user_logged_in()
            && function_exists('current_user_can') && current_user_can(CAP)) return true;
        if (function_exists('is_login') && is_login()) return true;
        $script = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '';
        return $script !== '' && str_ends_with($script, 'wp-login.php');
    }

    public function render(int $pageId): void
    {
        $post = get_post($pageId);
        if (!$post || $post->post_status !== 'publish') {
            status_header(503); nocache_headers();
            echo '<!doctype html><meta charset="utf-8"><title>Under construction</title><p>Site is under construction.</p>';
            return;
        }
        global $wp_query;
        if (isset($wp_query) && is_object($wp_query)) $wp_query->is_404 = false;
        status_header(503); nocache_headers();
        header('Content-Type: text/html; charset=' . get_bloginfo('charset'));

        $title   = get_the_title($post);
        $content = apply_filters('the_content', $post->post_content);
        $lang    = get_bloginfo('language');
        $charset = get_bloginfo('charset');

        echo '<!doctype html><html lang="' . esc_attr($lang) . '"><head><meta charset="' . esc_attr($charset) . '">'
           . '<meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">'
           . '<title>' . esc_html($title) . '</title>';
        wp_head();
        echo '</head><body class="pageveil"><main class="wp-site-blocks"><article class="entry-content">'
           . $content . '</article></main>';
        wp_footer();
        echo '</body></html>';
    }
}

final class Plugin
{
    public function __construct(private Settings $s, private Renderer $r) {}

    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'setting']);
        add_action('template_redirect', [$this, 'intercept'], 0);
        add_action('admin_bar_menu', [$this, 'bar'], 100);
    }

    public function menu(): void
    {
        add_management_page(__('Pageveil', 'pageveil'), __('Pageveil', 'pageveil'), CAP, 'pageveil', [$this, 'page']);
    }

    public function setting(): void
    {
        register_setting('pageveil_group', OPTION, [
            'type' => 'array',
            'sanitize_callback' => fn ($i) => $this->s->sanitize(is_array($i) ? $i : []),
            'default' => $this->s->defaults(),
            'show_in_rest' => false,
        ]);
    }

    public function page(): void
    {
        if (!current_user_can(CAP)) wp_die(__('Insufficient permissions.', 'pageveil'));
        $o = $this->s->get();
        $pages = get_posts([
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
            'numberposts'    => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'suppress_filters' => true,
        ]);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Pageveil', 'pageveil'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('pageveil_group'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="pv-on"><?php esc_html_e('Enable', 'pageveil'); ?></label></th>
                        <td><input type="checkbox" id="pv-on" name="<?php echo esc_attr(OPTION); ?>[enabled]" value="1" <?php checked($o['enabled']); ?>>
                            <p class="description"><?php esc_html_e('Administrators always see the live site.', 'pageveil'); ?></p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pv-page"><?php esc_html_e('Page', 'pageveil'); ?></label></th>
                        <td><select id="pv-page" name="<?php echo esc_attr(OPTION); ?>[page_id]">
                            <option value="0"><?php esc_html_e('— Select —', 'pageveil'); ?></option>
                            <?php foreach ($pages as $pid): ?>
                                <option value="<?php echo (int) $pid; ?>" <?php selected($o['page_id'], (int) $pid); ?>><?php echo esc_html(get_the_title((int) $pid)); ?></option>
                            <?php endforeach; ?>
                        </select></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function intercept(): void
    {
        if (!$this->s->active() || $this->r->bypass()) return;
        $this->r->render($this->s->pageId());
        exit;
    }

    public function bar($bar): void
    {
        if (!is_object($bar) || !$this->s->active() || !current_user_can(CAP)) return;
        $bar->add_node([
            'id' => 'pageveil',
            'title' => esc_html__('Pageveil: ON', 'pageveil'),
            'href' => admin_url('tools.php?page=pageveil'),
            'meta' => ['class' => 'pageveil-active'],
        ]);
    }
}

if (!defined('PAGEVEIL_TESTS')) {
    add_action('plugins_loaded', static fn () => (new Plugin(new Settings(), new Renderer()))->register());
}
