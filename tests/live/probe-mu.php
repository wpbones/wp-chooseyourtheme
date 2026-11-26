<?php
// Probe: shows which theme each kind of request sees.
add_action('rest_api_init', function () {
  register_rest_route('wpcyt-probe/v1', '/theme', ['methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => fn () => ['stylesheet' => get_stylesheet(), 'template' => get_template()]]);
});
add_action('wp_ajax_nopriv_wpcyt_probe', fn () => wp_die('AJAX-STYLESHEET:' . get_stylesheet()));
add_action('wp_ajax_wpcyt_probe', fn () => wp_die('AJAX-STYLESHEET:' . get_stylesheet()));
add_action('admin_footer', fn () => print('<!--ADMIN-STYLESHEET:' . get_stylesheet() . '-->'));
add_action('login_footer', fn () => print('<!--LOGIN-STYLESHEET:' . get_stylesheet() . '-->'));
if (getenv('WPCYT_MIX') || file_exists('/wordpress/wp-content/mix.flag')) {
  add_filter('wp_chooseyourtheme_allow_mixed_kinds', '__return_true');
}
add_action('init', function () {
  if (isset($_GET['wpcyt_dump_options'])) {
    global $wpdb;
    header('Content-Type: text/plain'); echo 'locale=', get_locale(), ' WPLANG=', var_export(get_option('WPLANG'), true), ' loaded=', var_export(is_textdomain_loaded('wp-chooseyourtheme'), true), "\n";
    foreach ($wpdb->get_results("SELECT option_name, autoload FROM {$wpdb->options} WHERE option_name LIKE '%choose%' OR option_name LIKE '%wpcyt%' ORDER BY option_name") as $r) echo $r->option_name, ' (autoload=', $r->autoload, ")\n";
    foreach ($wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'widget_%'") as $r) echo 'W:', $r->option_name, "\n";
    exit;
  }
}, 0);
