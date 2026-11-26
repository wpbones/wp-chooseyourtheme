#!/usr/bin/env bash
# make-themes.sh <themes dir>: four classic test themes, a child and an orphan child.
set -e
T=$1
mk() { # slug name template
  mkdir -p "$T/$1"
  { echo "/*"; echo "Theme Name: $2"; echo "Version: 1.0"; [ -n "$3" ] && echo "Template: $3"; echo "*/"; } > "$T/$1/style.css"
}
mk wpcyt-classic-a "Classic A" ""
mk wpcyt-classic-b "Classic B" ""
mk wpcyt-classic-kid "Classic Kid" wpcyt-classic-a
mk wpcyt-orphan "Orphan Kid" no-such-parent
for s in wpcyt-classic-a wpcyt-classic-b; do
cat > "$T/$s/functions.php" <<PHP
<?php
add_action('widgets_init', function () { register_sidebar(['id' => 'sidebar-1', 'name' => 'Side', 'before_widget' => '<div class="w">', 'after_widget' => '</div>']); });
PHP
cat > "$T/$s/index.php" <<PHP
<!doctype html><html><head><?php wp_head(); ?></head><body>
<!--MOD:<?php echo get_theme_mod('wpcyt_marker'); ?>-->
<!--THEME:$s stylesheet=<?php echo get_stylesheet(); ?> template=<?php echo get_template(); ?>-->
<main><?php while (have_posts()) { the_post(); the_content(); } ?></main>
<?php if (empty(\$_GET['nosw'])) : ?><aside><?php wp_chooseyourtheme('mode=list'); dynamic_sidebar('sidebar-1'); ?></aside><?php endif; ?>
<?php wp_footer(); ?></body></html>
PHP
done
# the child has no index.php of its own: it must use its parent's, with get_stylesheet() = the child
