<?php
/*
Plugin Name: Fun Casino
Description: Virtuelles Casino mit Coins (kein Echtgeld). V4: Neues Spiel Roulette, Animationen & Clean UI.
Version: 4.0
Author: Silas
Text Domain: fun-casino
*/

if ( ! defined('ABSPATH') ) exit;

add_action('plugins_loaded', function(){
    load_plugin_textdomain('fun-casino', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

add_action('wp_enqueue_scripts', function(){
    $base = plugin_dir_url(__FILE__);
    wp_enqueue_style('fun-casino-style', $base . 'assets/css/fc-style.css', [], '1.0');
    wp_enqueue_script('fun-casino-script', $base . 'assets/js/fc-script.js', [], '1.0', true);
});

register_uninstall_hook(__FILE__, 'fc_uninstall');
function fc_uninstall(){
    require_once plugin_dir_path(__FILE__) . 'uninstall.php';
}

// --------------------------------
// Lade fc-template.php aus Plugin-Verzeichnis
// --------------------------------
add_filter('template_include', function($template){
    if (is_page()) {
        $slug = get_page_template_slug(get_queried_object_id());
        if ($slug === 'fc-template.php') {
            $file = plugin_dir_path(__FILE__) . 'fc-template.php';
            if (file_exists($file)) return $file;
        }
    }
    return $template;
});

// ------------------------------
// Activation: create tables & pages
// ------------------------------
register_activation_hook(__FILE__, function(){
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $table1 = $wpdb->prefix . "fc_users";
    $sql1 = "CREATE TABLE $table1 (
        user_id BIGINT(20) UNSIGNED NOT NULL,
        coins BIGINT(20) NOT NULL DEFAULT 1000,
        last_bonus DATE DEFAULT NULL,
        PRIMARY KEY (user_id)
    ) $charset_collate;";

    $table2 = $wpdb->prefix . "fc_logs";
    $sql2 = "CREATE TABLE $table2 (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        game VARCHAR(50) NOT NULL,
        change_amount INT NOT NULL,
        new_balance INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql1);
    dbDelta($sql2);

    $pages = [
        __('Casino Home', 'fun-casino') => '[fc_nav]\n<h2>' . esc_html__('Fun Casino', 'fun-casino') . '</h2><p>' . esc_html__('Wähle ein Spiel:', 'fun-casino') . '</p>',
        __('Casino Coinflip', 'fun-casino') => '[fc_nav]\n[fc_coinflip]',
        __('Casino Slot', 'fun-casino') => '[fc_nav]\n[fc_slot]',
        __('Casino Roulette', 'fun-casino') => '[fc_nav]\n[fc_roulette]',
        __('Casino Profile', 'fun-casino') => '[fc_nav]\n[fc_profile]',
        __('Casino Leaderboard', 'fun-casino') => '[fc_nav]\n[fc_leaderboard]',
    ];
    foreach($pages as $title=>$content){
        if(!get_page_by_title($title)){
            $id = wp_insert_post([
                'post_title'   => $title,
                'post_content' => $content,
                'post_status'  => 'publish',
                'post_author'  => 1,
                'post_type'    => 'page',
            ]);
            if($id){
                update_post_meta($id, '_wp_page_template', 'fc-template.php');
            }
        }
    }
});

// ------------------------------
// Admin Menu
// ------------------------------
add_action('admin_menu', function(){
    add_menu_page(
        __('Fun Casino', 'fun-casino'),
        __('Fun Casino', 'fun-casino'),
        'manage_options',
        'fun-casino',
        function(){
            echo '<div class="wrap"><h1>' . esc_html__('Fun Casino', 'fun-casino') . '</h1><p>' . esc_html__('Verwalte das Plugin.', 'fun-casino') . '</p></div>';
        },
        'dashicons-games',
        6
    );
});

// ------------------------------
// Helpers
// ------------------------------
function fc_get_coins($uid){
    global $wpdb;
    $table = $wpdb->prefix . "fc_users";
    $coins = $wpdb->get_var($wpdb->prepare("SELECT coins FROM $table WHERE user_id=%d", $uid));
    if ($coins === null) {
        $wpdb->insert($table, [
            'user_id' => $uid,
            'coins'   => 1000,
        ]);
        $coins = 1000;
    }
    return intval($coins);
}
function fc_update_coins($uid,$new){
    global $wpdb;
    $table = $wpdb->prefix . "fc_users";
    $wpdb->update($table, ['coins'=>$new], ['user_id'=>$uid]);
}
function fc_add_log($uid,$game,$change,$new_balance){
    global $wpdb;
    $table = $wpdb->prefix . "fc_logs";
    $wpdb->insert($table,[
        'user_id'=>$uid,
        'game'=>$game,
        'change_amount'=>$change,
        'new_balance'=>$new_balance
    ]);
}

// ------------------------------
// Navigation
// ------------------------------
add_shortcode('fc_nav', function(){
    $home = site_url('/casino-home');
    $coinflip = site_url('/casino-coinflip');
    $slot = site_url('/casino-slot');
    $roulette = site_url('/casino-roulette');
    $profile = site_url('/casino-profile');
    $leader = site_url('/casino-leaderboard');
    return "<nav class='fc-nav'>
        <a href='{$home}'>🏠 " . esc_html__('Home', 'fun-casino') . "</a>
        <a href='{$coinflip}'>🎲 " . esc_html__('Coinflip', 'fun-casino') . "</a>
        <a href='{$slot}'>🎰 " . esc_html__('Slot', 'fun-casino') . "</a>
        <a href='{$roulette}'>🎡 " . esc_html__('Roulette', 'fun-casino') . "</a>
        <a href='{$profile}'>👤 " . esc_html__('Profil', 'fun-casino') . "</a>
        <a href='{$leader}'>🏆 " . esc_html__('Leaderboard', 'fun-casino') . "</a>
    </nav>";
});

// ------------------------------
// Coinflip with animation
// ------------------------------
add_shortcode('fc_coinflip', function(){
    if (!is_user_logged_in()) return esc_html__('Bitte einloggen.', 'fun-casino');
    $uid = get_current_user_id();
    $coins = fc_get_coins($uid);
    $result = '';
    if(isset($_POST['fc_coinflip'])){
        if(isset($_POST['fc_nonce']) && wp_verify_nonce($_POST['fc_nonce'], 'fc_play')){
            if($coins>=50){
                $coins -= 50;
                $change = -50;
                if(wp_rand(0,1)==1){
                    $coins += 100;
                    $change = +50;
                    $result = "<div class='fc-result win'>🎉 " . esc_html__('Gewonnen! +100 Coins', 'fun-casino') . "</div>";
                } else {
                    $result = "<div class='fc-result lose'>😢 " . esc_html__('Verloren! -50 Coins', 'fun-casino') . "</div>";
                }
                fc_update_coins($uid,$coins);
                fc_add_log($uid, __('Coinflip', 'fun-casino'), $change, $coins);
            } else {
                $result="<div class='fc-result lose'>" . esc_html__('Nicht genug Coins!', 'fun-casino') . "</div>";
            }
        } else {
            $result = "<div class='fc-result lose'>" . esc_html__('Ungültige Anfrage.', 'fun-casino') . "</div>";
        }
    }
    $nonce = wp_nonce_field('fc_play','fc_nonce',true,false);
    return "<div class='fc-game'>
      <h2>" . esc_html__('Coinflip', 'fun-casino') . "</h2>
      <form method='post' id='fc-coinflip-form'>
        <button class='fc-btn' name='fc_coinflip'>" . esc_html__('Münzwurf (50 Einsatz)', 'fun-casino') . "</button>
        {$nonce}
      </form>
      <div class='fc-balance'>" . esc_html__('Kontostand:', 'fun-casino') . " {$coins}</div>
      <div id='flip-anim' class='fc-coin-anim fc-hide'>🪙</div>
      {$result}
    </div>";
});

// ------------------------------
// Slot with animation
// ------------------------------
add_shortcode('fc_slot', function(){
    if (!is_user_logged_in()) return esc_html__('Bitte einloggen.', 'fun-casino');
    $uid = get_current_user_id();
    $coins = fc_get_coins($uid);
    $symbols = ['🍒','⭐','🍋','💎'];
    $result = '';
    if(isset($_POST['fc_slot'])){
        if(isset($_POST['fc_nonce']) && wp_verify_nonce($_POST['fc_nonce'], 'fc_play')){
            if($coins>=100){
                $coins -= 100;
                $change = -100;
                $s1 = $symbols[wp_rand(0, count($symbols)-1)];
                $s2 = $symbols[wp_rand(0, count($symbols)-1)];
                $s3 = $symbols[wp_rand(0, count($symbols)-1)];
                $msg = "{$s1} | {$s2} | {$s3}";
                $win = 0;
                if($s1==$s2 || $s2==$s3 || $s1==$s3) $win = 200;
                if($s1==$s2 && $s2==$s3) $win = 500;
                if($win>0){
                    $coins += $win;
                    $change += $win;
                    $result = "<div class='fc-result win'>{$msg} 🎉 " . esc_html__('Gewinn:', 'fun-casino') . " +{$win}</div>";
                } else {
                    $result = "<div class='fc-result lose'>{$msg} 😢 " . esc_html__('Kein Gewinn', 'fun-casino') . "</div>";
                }
                fc_update_coins($uid,$coins);
                fc_add_log($uid, __('Slot', 'fun-casino'), $change, $coins);
            } else {
                $result="<div class='fc-result lose'>" . esc_html__('Nicht genug Coins!', 'fun-casino') . "</div>";
            }
        } else {
            $result = "<div class='fc-result lose'>" . esc_html__('Ungültige Anfrage.', 'fun-casino') . "</div>";
        }
    }
    $nonce = wp_nonce_field('fc_play','fc_nonce',true,false);
    return "<div class='fc-game'>
      <h2>" . esc_html__('Slot Machine', 'fun-casino') . "</h2>
      <form method='post' id='fc-slot-form'>
        <button class='fc-btn' name='fc_slot'>" . esc_html__('Slot spielen (100 Einsatz)', 'fun-casino') . "</button>
        {$nonce}
      </form>
      <div class='fc-balance'>" . esc_html__('Kontostand:', 'fun-casino') . " {$coins}</div>
      <div id='slot-anim' class='fc-slot-anim fc-hide'>🍒⭐🍋💎</div>
      {$result}
    </div>";
});

// ------------------------------
// Roulette with animation
// ------------------------------
add_shortcode('fc_roulette', function(){
    if (!is_user_logged_in()) return esc_html__('Bitte einloggen.', 'fun-casino');
    $uid = get_current_user_id();
    $coins = fc_get_coins($uid);
    $result = '';
    if(isset($_POST['fc_roulette']) && isset($_POST['color'])){
        if(isset($_POST['fc_nonce']) && wp_verify_nonce($_POST['fc_nonce'], 'fc_play')){
            $color = sanitize_text_field(wp_unslash($_POST['color']));
            if($coins>=100){
                $coins -= 100;
                $change = -100;
                $num = wp_rand(0,36);
                $colorLabel = ($color=='red') ? esc_html__('rot', 'fun-casino') : esc_html__('schwarz', 'fun-casino');
                $hitColorRaw = ($num % 2 == 0) ? 'red' : 'black';
                $hitColor = ($hitColorRaw=='red') ? esc_html__('rot', 'fun-casino') : esc_html__('schwarz', 'fun-casino');
                $msg = esc_html__('Zahl', 'fun-casino') . " {$num} ({$hitColor})";
                if($hitColorRaw==$color){
                    $coins += 200;
                    $change = +100;
                    $result = "<div class='fc-result win'>🎉 " . esc_html__('Gewinn!', 'fun-casino') . " {$msg}</div>";
                } else {
                    $result = "<div class='fc-result lose'>😢 " . esc_html__('Verloren!', 'fun-casino') . " {$msg}</div>";
                }
                fc_update_coins($uid,$coins);
                fc_add_log($uid, __('Roulette', 'fun-casino'), $change, $coins);
            } else {
                $result="<div class='fc-result lose'>" . esc_html__('Nicht genug Coins!', 'fun-casino') . "</div>";
            }
        } else {
            $result = "<div class='fc-result lose'>" . esc_html__('Ungültige Anfrage.', 'fun-casino') . "</div>";
        }
    }
    $nonce = wp_nonce_field('fc_play','fc_nonce',true,false);
    return "<div class='fc-game'>
      <h2>" . esc_html__('Roulette', 'fun-casino') . "</h2>
      <form method='post' id='fc-roulette-form'>
        <select name='color'><option value='red'>" . esc_html__('Rot', 'fun-casino') . "</option><option value='black'>" . esc_html__('Schwarz', 'fun-casino') . "</option></select>
        <button class='fc-btn' name='fc_roulette' value='1'>" . esc_html__('Spielen (100 Einsatz)', 'fun-casino') . "</button>
        {$nonce}
      </form>
      <div class='fc-balance'>" . esc_html__('Kontostand:', 'fun-casino') . " {$coins}</div>
      <div id='roulette-anim' class='fc-roulette-anim fc-hide'>0 1 2 3 4 5 ...</div>
      {$result}
    </div>";
});

// ------------------------------
// Leaderboard & Profile
// ------------------------------
add_shortcode('fc_leaderboard', function(){
    global $wpdb;
    $table = $wpdb->prefix . "fc_users";
    $rows = $wpdb->get_results("SELECT user_id,coins FROM $table ORDER BY coins DESC LIMIT 20");
    $out = "<h2>" . esc_html__('Leaderboard', 'fun-casino') . "</h2><ol>";
    foreach($rows as $r){
        $u = get_userdata($r->user_id);
        $name = $u ? $u->user_login : sprintf(esc_html__('User %d', 'fun-casino'), $r->user_id);
        $out .= "<li>{$name} – {$r->coins} " . esc_html__('Coins', 'fun-casino') . "</li>";
    }
    $out .= "</ol>";
    return $out;
});

add_shortcode('fc_profile', function(){
    if (!is_user_logged_in()) return esc_html__('Bitte einloggen.', 'fun-casino');
    $uid = get_current_user_id();
    $coins = fc_get_coins($uid);
    global $wpdb;
    $table = $wpdb->prefix . "fc_logs";
    $logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE user_id=%d ORDER BY created_at DESC LIMIT 10",$uid));
    $out = "<h2>" . esc_html__('Mein Profil', 'fun-casino') . "</h2><p>" . esc_html__('Coins:', 'fun-casino') . " {$coins}</p><h4>" . esc_html__('Letzte Aktionen', 'fun-casino') . "</h4><ul>";
    foreach($logs as $l){
        $out .= "<li>{$l->created_at} – {$l->game} ({$l->change_amount}) → {$l->new_balance}</li>";
    }
    $out .= "</ul>";
    return $out;
});
?>
