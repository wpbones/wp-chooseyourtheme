#!/usr/bin/env bash
# probe.sh — WP Choose Your Theme on a real WordPress, in a local Playground (a scratch copy of the
# tree, with extra classic test themes): the visitor's cookie and redirect, what is offered, the
# widget and shortcodes, the settings page and the 0.x migration, and that the choice never reaches
# wp-admin, AJAX, REST or wp-login. The unit suite cannot show these.
#
# Usage: tests/live/probe.sh <plugin folder: source or deploy output> <WordPress folder> <classic|block|mixed>
#   <WordPress folder>  an unpacked release (e.g. 7.2-RC3). A folder and not --wp=, because Playground
#                       CLI 3.1.55 resolves --wp=beta and RC zip URLs to the latest stable; the folder
#                       is mounted with --mount-before-install and install-from-existing-files.
#   PORT=9433 (default) picks the port; KEEP=1 keeps the scratch folder.
set -uo pipefail
PLUGIN=${1:?usage: probe.sh <plugin folder> <WordPress folder> <classic|block|mixed>}
WPV=${2:?WordPress folder}; SCEN=${3:?scenario: classic|block|mixed}
HERE=$(cd "$(dirname "$0")" && pwd)
PORT=${PORT:-9433}
PG=@wp-playground/cli@3.1.55
SLUG=wp-chooseyourtheme
lsof -ti tcp:$PORT >/dev/null && { echo "port busy"; exit 1; }
T=$(mktemp -d "${TMPDIR:-/tmp}/cyt-probe.XXXXXX")
cleanup() { p=$(lsof -ti tcp:$PORT); [ -n "$p" ] && kill $p 2>/dev/null; [ -n "${KEEP:-}" ] && echo "kept $T" || rm -rf "$T"; }
trap cleanup EXIT
rsync -a --exclude node_modules --exclude .git --exclude tests "$PLUGIN/" "$T/$SLUG/"
cp -R "$WPV" "$T/wordpress"
"$HERE/make-themes.sh" "$T/wordpress/wp-content/themes"
mkdir -p "$T/logs" "$T/mu"; cp "$HERE/probe-mu.php" "$T/mu/probe.php"
[ "$SCEN" = mixed ] && touch "$T/wordpress/wp-content/mix.flag"

WPLANGCONST=""; [ "$SCEN" = locale ] && WPLANGCONST=", \"WPLANG\": \"$LOCALE\""
case $SCEN in
  classic) ACTIVE=wpcyt-classic-a; OPTS='{"wp-chooseyourtheme":{"hidden_themes":["Classic B"]}}';;
  block)   ACTIVE=twentytwentyfive; OPTS='{"wp_chooseyourtheme_settings":{"allowed_themes":["twentytwentyfour","twentytwentythree","wpcyt-classic-a","wpcyt-classic-b"],"cookie_days":30}}';;
  locale)  ACTIVE=wpcyt-classic-a; OPTS='{"wp_chooseyourtheme_settings":{"allowed_themes":["wpcyt-classic-b"],"cookie_days":30}}';;
  mixed)   ACTIVE=wpcyt-classic-a; OPTS='{"wp_chooseyourtheme_settings":{"allowed_themes":["twentytwentyfive","wpcyt-classic-b"],"cookie_days":30}}';;
esac
cat > "$T/blueprint.json" <<JSON
{ "steps": [
  { "step": "defineWpConfigConsts", "consts": { "WP_DEBUG": true, "WP_DEBUG_LOG": "/logs/debug.log", "WP_DEBUG_DISPLAY": false$WPLANGCONST } },
  { "step": "login" },
  { "step": "setSiteOptions", "options": { "permalink_structure": "/%postname%/" } },
  { "step": "setSiteOptions", "options": $OPTS },
  { "step": "runPHP", "code": "<?php require '/wordpress/wp-load.php'; wp_insert_post(['post_title'=>'Probe','post_name'=>'probe','post_status'=>'publish','post_content'=>'<p>PROBE-BODY</p>[wp_chooseyourtheme mode=\"menu\"] [wp_chooseyourtheme mode=\"roll\"] [wp_chooseyourtheme mode=\"list\"]']); update_option('theme_mods_wpcyt-classic-a',['wpcyt_marker'=>'MOD-A']); update_option('theme_mods_wpcyt-classic-b',['wpcyt_marker'=>'MOD-B']); update_option('theme_mods_wpcyt-classic-kid',['wpcyt_marker'=>'MOD-KID']); update_option('sidebars_widgets', ['sidebar-1'=>['wp_chooseyourtheme-2'],'array_version'=>3]); update_option('widget_wp_chooseyourtheme', [2=>['title'=>'Pick one','mode'=>'roll'],'_multiwidget'=>1]);" },
  { "step": "activateTheme", "themeFolderName": "$ACTIVE" },
  { "step": "activatePlugin", "pluginPath": "$SLUG/wp-chooseyourtheme.php" }
] }
JSON
( cd "$T" && exec npx -y "$PG" server --blueprint="$T/blueprint.json" --port=$PORT --mount-before-install="$T/wordpress:/wordpress" --wordpress-install-mode=install-from-existing-files \
  --mount="$T/$SLUG:/wordpress/wp-content/plugins/$SLUG" --mount="$T/logs:/logs" --mount="$T/mu/probe.php:/wordpress/wp-content/mu-plugins/probe.php" > "$T/pg.log" 2>&1 ) &
for _ in $(seq 1 150); do grep -q 'Ready!' "$T/pg.log" 2>/dev/null && break; sleep 2; done
grep -q 'Ready!' "$T/pg.log" || { echo "Playground did not start"; tail "$T/pg.log"; exit 1; }
B=http://127.0.0.1:$PORT
ADM="$T/admin.jar"; V="playground_auto_login_already_happened=1"
curl -s -L -c "$ADM" -b "$ADM" -o /dev/null "$B/wp-admin/"      # the blueprint's login step autologs in

[ -n "${DEBUGSHELL:-}" ] && { echo "server on $B (T=$T)"; sleep ${DEBUGSHELL}; }
pass=0; fail=0
ok()  { pass=$((pass+1)); echo "  ok   $1"; }
bad() { fail=$((fail+1)); echo "  FAIL $1"; }
has() { grep -qi -- "$2" <<<"$3" && ok "$1" || { bad "$1 (missing: $2)"; }; }
hasnt() { grep -qi -- "$2" <<<"$3" && bad "$1 (found: $2)" || ok "$1"; }
get()  { curl -s -b "$1" "$B$2"; }                       # get <jar|cookie string> <path>
head_() { curl -s -D - -o /dev/null -b "$1" "$B$2"; }
stylesheet_of() { grep -o 'THEME:[^ ]* stylesheet=[^ ]* template=[^-]*-->' <<<"$1" | head -1; }
ck() { echo "$1"; }    # a raw cookie string

withcookie() { # withcookie <value> -> a jar: admin session + the visitor cookie
  cp "$ADM" "$T/j.jar"; printf '127.0.0.1\tFALSE\t/\tFALSE\t0\twpcyt_theme\t%s\n' "$1" >> "$T/j.jar"; echo "$T/j.jar"; }

echo "== scenario: $SCEN (active: $ACTIVE) on $(grep -o "wp_version = '[^']*'" $WPV/wp-includes/version.php)"

if [ "$SCEN" = classic ]; then
  echo "-- settings migrated from 0.x (hidden: Classic B)"
  P=$(get "$V" "/probe/")
  has "no cookie: the active theme renders" "THEME:wpcyt-classic-a stylesheet=wpcyt-classic-a" "$P"
  has "the list is offered" 'class="wpcyt_list"' "$P"
  has "Classic Kid is offered (not hidden)" 'wpcyt_theme=wpcyt-classic-kid' "$P"
  hasnt "Classic B (hidden in 0.x) is not offered" 'wpcyt_theme=wpcyt-classic-b' "$P"
  hasnt "a block theme is not offered on a classic site" 'wpcyt_theme=twenty' "$P"
  hasnt "the orphan child (missing parent) is not offered" 'wpcyt_theme=wpcyt-orphan' "$P"
  has "shortcode menu: a GET form" '<form class="wpcyt_form" method="get" action="/probe/">' "$P"
  has "shortcode menu: a no-JS submit button" 'class="wpcyt_submit"' "$P"
  has "shortcode roll" 'class="wpcyt_roll"' "$P"
  has "the menu script is enqueued" 'wp-chooseyourtheme.js' "$P"
  has "widget (roll mode) with its title" 'Pick one' "$P"
  H=$(head_ "$V" "/probe/"); hasnt "no cookie: page is not marked uncacheable" 'Vary: Cookie' "$H"

  echo "-- choosing a theme"
  H=$(curl -s -D - -o /dev/null -b "$V" "$B/probe/?foo=bar&wpcyt_theme=wpcyt-classic-kid")
  has "302" 'HTTP/1.1 302' "$H"
  has "redirects back, minus the argument, keeping the rest" 'ocation: /probe/?foo=bar' "$H"
  has "cookie holds the slug" 'wpcyt_theme=wpcyt-classic-kid' "$H"
  has "SameSite=Lax" 'SameSite=Lax' "$H"
  has "HttpOnly" 'HttpOnly' "$H"
  has "default lifetime 30 days (Max-Age or expires)" 'xpires=' "$H"
  has "the redirect is uncacheable" 'no-cache' "$H"
  has "no cookie: the active theme's own theme mods" 'MOD:MOD-A' "$(get "$V" /probe/)"
  K=$(withcookie wpcyt-classic-kid)
  P=$(get "$K" "/probe/")
  has "the chosen theme's own theme mods (Customizer settings)" 'MOD:MOD-KID' "$P"; hasnt "…and not the active theme's" 'MOD:MOD-A' "$P"
  has "child theme: stylesheet is the child, template the parent" "stylesheet=wpcyt-classic-kid template=wpcyt-classic-a" "$P"
  has "the list marks the current theme" 'class="classic-kid selected"' "$P"
  H=$(head_ "$K" "/probe/"); has "cookie holder: Vary: Cookie" 'Vary: Cookie' "$H"; has "cookie holder: nocache" 'Cache-Control: no-cache' "$H"

  echo "-- refusals"
  for v in wpcyt-classic-b twentytwentyfive wpcyt-orphan no-such-theme '../etc/passwd'; do
    P=$(get "$(withcookie "$v")" "/probe/"); has "forged cookie '$v' shows the active theme" "stylesheet=wpcyt-classic-a template" "$P"; done
  cp "$ADM" "$T/old.jar"; printf '127.0.0.1\tFALSE\t/\tFALSE\t0\twpcyt_name\tClassic B\n' >> "$T/old.jar"
  P=$(get "$T/old.jar" "/probe/"); has "the 0.x name cookie is ignored" "stylesheet=wpcyt-classic-a template" "$P"
  H=$(curl -s -D - -o /dev/null -b "$V" "$B/probe/?wpcyt_theme=wpcyt-classic-b"); hasnt "a hidden theme sets no cookie" 'Set-Cookie: wpcyt_theme' "$H"; has "…but the argument is still stripped" 'ocation: /probe/' "$H"
  H=$(curl -s -D - -o /dev/null -b "$V" "$B/probe/?wpcyt.theme=wpcyt-classic-kid"); has "wpcyt.theme (PHP reads it as wpcyt_theme) does not loop" 'ocation: /probe/$' "$(tr -d '\r' <<<"$H")"
  H=$(curl -s -D - -o /dev/null -b "$V" "$B/probe/?wpcyt_theme[]=x"); has "array argument is dropped" 'ocation: /probe/' "$H"
  H=$(curl -s -D - -o /dev/null -b "$K" "$B/probe/?wpcyt_theme=wpcyt-classic-a"); has "choosing the active theme clears the cookie" 'Max-Age=0' "$H"

  echo "-- never outside the front end (cookie = wpcyt-classic-kid)"
  A=$(get "$K" "/wp-admin/themes.php?page=wp-chooseyourtheme"); has "settings page is served" 'Themes visitors may choose' "$A"; has "wp-admin sees the active theme" 'ADMIN-STYLESHEET:wpcyt-classic-a' "$A"
  has "Classic Kid is ticked (migrated), Classic B is not" 'value="wpcyt-classic-kid"  *checked' "$(tr -s ' \n' ' ' <<<"$A")"
  hasnt "…Classic B is not ticked" 'value="wpcyt-classic-b"[^>]*checked' "$(tr -s ' \n' ' ' <<<"$A")"
  has "block themes are shown as not offered" 'Not offered: the active theme is a classic theme' "$A"
  X=$(get "$K" "/wp-admin/admin-ajax.php?action=wpcyt_probe"); has "admin-ajax" 'AJAX-STYLESHEET:wpcyt-classic-a' "$X"
  X=$(curl -s -b "$V; wpcyt_theme=wpcyt-classic-kid" "$B/wp-admin/admin-ajax.php?action=wpcyt_probe"); has "admin-ajax, logged out" 'AJAX-STYLESHEET:wpcyt-classic-a' "$X"
  X=$(curl -s -b "$V; wpcyt_theme=wpcyt-classic-kid" "$B/wp-json/wpcyt-probe/v1/theme"); has "REST (pretty URL)" '"stylesheet":"wpcyt-classic-a"' "$X"
  X=$(curl -s -b "$V; wpcyt_theme=wpcyt-classic-kid" "$B/?rest_route=/wpcyt-probe/v1/theme"); has "REST (?rest_route=)" '"stylesheet":"wpcyt-classic-a"' "$X"
  X=$(curl -s -b "$V; wpcyt_theme=wpcyt-classic-kid" "$B/wp-login.php"); has "wp-login.php" 'LOGIN-STYLESHEET:wpcyt-classic-a' "$X"
  H=$(curl -s -D - -o /dev/null -b "$K" "$B/wp-admin/themes.php?page=wp-chooseyourtheme&wpcyt_theme=wpcyt-classic-kid"); hasnt "the argument does nothing in wp-admin" 'ocation: /wp-admin/themes.php?page=wp-chooseyourtheme$' "$H"

  echo "-- widgets screen and the script"
  W=$(curl -s -w '\n%{http_code}' -b "$ADM" "$B/wp-admin/widgets.php"); has "Appearance > Widgets answers 200 (classic theme)" '^200$' "$(tail -1 <<<"$W")"
  has "the widget is offered" 'Choose Your Theme' "$W"
  RN=$(curl -s -b "$ADM" "$B/wp-admin/admin-ajax.php?action=rest-nonce")
  E=$(curl -s -b "$ADM" -H "X-WP-Nonce: $RN" -H 'Content-Type: application/json' -d '{"instance":{}}' "$B/wp-json/wp/v2/widget-types/wp_chooseyourtheme/encode")
  has "widget form renders (title, mode)" 'Show as:' "$E"; has "widget preview renders the switcher" 'wpcyt_form' "$E"
  JS=$(grep -o 'src=.[^"'"'"' ]*wp-chooseyourtheme.js[^"'"'"' ]*' <<<"$(get "$V" /probe/)" | head -1 | sed "s/src=.//;s/&#038;/\&/g;s/&amp;/\&/g"); echo "  script: $JS"
  case "$JS" in http*) U="$JS";; /*) U="$B$JS";; esac
  [ "$(curl -s -o /dev/null -w '%{http_code}' "$U")" = 200 ] && ok "the menu script URL answers 200" || bad "script URL does not answer 200: $U"
  echo "-- saving the settings (Settings API)"
  A=$(get "$ADM" "/wp-admin/themes.php?page=wp-chooseyourtheme"); N=$(grep -o 'name="_wpnonce" value="[^"]*"' <<<"$A" | head -1 | sed 's/.*value="//;s/"//')
  [ -n "$N" ] && ok "form carries a nonce" || bad "no nonce"
  C=$(curl -s -o /dev/null -w '%{http_code}' -b "$ADM" -d 'option_page=wp_chooseyourtheme&action=update&wp_chooseyourtheme_settings[cookie_days]=7' "$B/wp-admin/options.php"); [ "$C" = 403 ] && ok "a save without the nonce is refused (403)" || bad "save without nonce answered $C"
  curl -s -L -o /dev/null -b "$ADM" -c "$ADM" --data-urlencode "_wpnonce=$N" -d 'option_page=wp_chooseyourtheme&action=update&_wp_http_referer=%2Fwp-admin%2Fthemes.php%3Fpage%3Dwp-chooseyourtheme' \
     -d 'wp_chooseyourtheme_settings[allowed_themes][]=wpcyt-classic-b' -d 'wp_chooseyourtheme_settings[allowed_themes][]=ghost-theme' -d 'wp_chooseyourtheme_settings[cookie_days]=99999' "$B/wp-admin/options.php"
  A=$(get "$ADM" "/wp-admin/themes.php?page=wp-chooseyourtheme" | tr -s ' \n' ' ')
  has "saved: Classic B is now ticked" 'value="wpcyt-classic-b"[^>]*checked' "$A"; hasnt "saved: Classic Kid is no longer ticked" 'value="wpcyt-classic-kid"[^>]*checked' "$A"
  hasnt "saved: an unknown theme is dropped" 'ghost-theme' "$A"; has "saved: a lifetime of 99999 is clamped to 365" 'value="365"' "$A"
  H=$(curl -s -D - -o /dev/null -b "$V" "$B/probe/?wpcyt_theme=wpcyt-classic-b"); has "the new setting applies at once" 'wpcyt_theme=wpcyt-classic-b' "$H"
  P=$(get "$(withcookie wpcyt-classic-b)" "/probe/"); has "theme B chosen: only B's theme mods" 'MOD:MOD-B' "$P"; hasnt "…not A's" 'MOD:MOD-A' "$P"
  A=$(get "$(withcookie wpcyt-classic-b)" "/wp-admin/themes.php?page=wp-chooseyourtheme"); has "wp-admin still reads the site's theme option" 'ADMIN-STYLESHEET:wpcyt-classic-a' "$A"
fi

if [ "$SCEN" = locale ]; then
  echo "-- locale $LOCALE"
  A=$(get "$ADM" "/wp-admin/themes.php?page=wp-chooseyourtheme"); P=$(get "$V" "/probe/")
  echo "$A" | grep -o '<h2 class="title">[^<]*' | sed 's/<h2 class="title">/  admin: /'
  echo "$P" | grep -o '<button type="submit" class="wpcyt_submit">[^<]*' | head -1 | sed 's/.*">/  front menu button: /'
  echo "$A" | grep -o 'Not offered[^<]*\|Non offert[^<]*\|No se ofrece[^<]*\|Non propos[^<]*\|Nicht im Angebot[^<]*\|Não oferecido[^<]*' | head -1 | sed 's/^/  status: /'
fi

if [ "$SCEN" = block ]; then
  echo "-- block site: only block themes are offered"
  P=$(get "$V" "/probe/")
  has "twentytwentyfive renders" 'themes/twentytwentyfive/' "$P"; has "the post body is there" 'PROBE-BODY' "$P"
  has "shortcode list offers twentytwentyfour" 'wpcyt_theme=twentytwentyfour' "$P"; hasnt "no classic theme offered" 'wpcyt_theme=wpcyt-classic' "$P"
  H=$(curl -s -D - -o /dev/null -b "$V" "$B/probe/?wpcyt_theme=twentytwentyfour"); has "set" 'wpcyt_theme=twentytwentyfour' "$H"
  P=$(get "$(withcookie twentytwentyfour)" "/probe/")
  has "a block visitor gets twentytwentyfour" 'themes/twentytwentyfour/' "$P"; hasnt "…and not twentytwentyfive" 'themes/twentytwentyfive/' "$P"; has "…body still there" 'PROBE-BODY' "$P"
  H=$(head_ "$(withcookie twentytwentyfour)" "/probe/"); has "Vary: Cookie" 'Vary: Cookie' "$H"
  P=$(get "$(withcookie wpcyt-classic-a)" "/probe/"); has "a classic theme is refused on a block site" 'themes/twentytwentyfive/' "$P"
  A=$(get "$(withcookie twentytwentyfour)" "/wp-admin/themes.php?page=wp-chooseyourtheme"); has "settings page" 'Themes visitors may choose' "$A"; has "admin sees the active theme" 'ADMIN-STYLESHEET:twentytwentyfive' "$A"
  C=$(curl -s -o /dev/null -w '%{http_code}' -b "$(withcookie twentytwentyfour)" "$B/wp-admin/site-editor.php"); [ "$C" = 200 ] && ok "Site Editor (200) with the cookie set" || bad "site editor $C"
  X=$(curl -s -b "$V; wpcyt_theme=twentytwentyfour" "$B/wp-json/wpcyt-probe/v1/theme"); has "REST sees the active theme" '"stylesheet":"twentytwentyfive"' "$X"
fi

if [ "$SCEN" = mixed ]; then
  echo "-- classic site with the 'allow mixed kinds' filter on, a block theme chosen"
  P=$(get "$V" "/probe/"); has "block theme offered now" 'wpcyt_theme=twentytwentyfive' "$P"
  K=$(withcookie twentytwentyfive)
  C=$(curl -s -o "$T/mixed.html" -w '%{http_code}' -b "$K" "$B/probe/"); echo "  HTTP $C, $(wc -c < "$T/mixed.html") bytes"
  grep -q 'themes/twentytwentyfive/' "$T/mixed.html" && echo "  the block theme's styles are loaded" || echo "  the block theme's styles are NOT loaded"
  grep -q 'PROBE-BODY' "$T/mixed.html" && echo "  the post content is rendered" || echo "  the post content is NOT rendered"
  grep -q 'wpcyt_list\|wpcyt_form\|wpcyt_roll' "$T/mixed.html" && echo "  the switcher shortcodes still render" || echo "  switcher not rendered (block templates have no template tag or sidebar)"
  grep -q 'Pick one' "$T/mixed.html" && echo "  the classic sidebar widget renders" || echo "  the classic sidebar widget does NOT render"
  grep -c '<nav\|wp-block-navigation' "$T/mixed.html" | sed 's/^/  navigation blocks: /'
fi

[ -n "${DUMP:-}" ] && { echo "-- options"; curl -s -b "$V" "$B/?wpcyt_dump_options=1"; }
echo "-- debug.log"
if [ -s "$T/logs/debug.log" ]; then echo "  FAIL debug.log:"; head -30 "$T/logs/debug.log" | sed 's/^/    /'; fail=$((fail+1)); else echo "  ok   debug.log is empty"; pass=$((pass+1)); fi
echo "== $SCEN: $pass ok, $fail failed"
[ "$fail" = 0 ]
