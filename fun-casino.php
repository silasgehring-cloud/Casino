<?php
/*
Plugin Name: Fun Casino
Description: Virtuelles Casino mit Coins (kein Echtgeld). V4: Neues Spiel Roulette, Animationen & Clean UI.
Version: 4.0
Author: Silas
*/

if ( ! defined('ABSPATH') ) exit;

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
        'Casino Home' => '[fc_nav]\n<h2>Fun Casino</h2><p>Wähle ein Spiel:</p>',
        'Casino Coinflip' => '[fc_nav]\n[fc_coinflip]',
        'Casino Slot' => '[fc_nav]\n[fc_slot]',
        'Casino Roulette' => '[fc_nav]\n[fc_roulette]',
        'Casino Profile' => '[fc_nav]\n[fc_profile]',
        'Casino Leaderboard' => '[fc_nav]\n[fc_leaderboard]',
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
        'Fun Casino',
        'Fun Casino',
        'manage_options',
        'fun-casino',
        function(){
            echo '<div class="wrap"><h1>Fun Casino</h1><p>Verwalte das Plugin.</p></div>';
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
        <a href='{$home}'>🏠 Home</a>
        <a href='{$coinflip}'>🎲 Coinflip</a>
        <a href='{$slot}'>🎰 Slot</a>
        <a href='{$roulette}'>🎡 Roulette</a>
        <a href='{$profile}'>👤 Profil</a>
        <a href='{$leader}'>🏆 Leaderboard</a>
    </nav>";
});

// ------------------------------
// Coinflip with animation
// ------------------------------
add_shortcode('fc_coinflip', function(){
    if (!is_user_logged_in()) return "Bitte einloggen.";
    $uid = get_current_user_id();
    $coins = fc_get_coins($uid);
    $result = "";
    if(isset($_POST['fc_coinflip'])){
        if($coins>=50){
            $coins -= 50;
            $change = -50;
            if(rand(0,1)==1){
                $coins += 100;
                $change = +50;
                $result = "<div class='fc-result win'>🎉 Gewonnen! +100 Coins</div>";
            } else {
                $result = "<div class='fc-result lose'>😢 Verloren! -50 Coins</div>";
            }
            fc_update_coins($uid,$coins);
            fc_add_log($uid,'Coinflip',$change,$coins);
        } else $result="<div class='fc-result lose'>Nicht genug Coins!</div>";
    }
    return <<<HTML
    <div class='fc-game'>
      <h2>Coinflip</h2>
      <form method='post' onsubmit="document.getElementById('flip-anim').style.display='block'">
        <button class='fc-btn' name='fc_coinflip'>Münzwurf (50 Einsatz)</button>
      </form>
      <div class='fc-balance'>Kontostand: {$coins}</div>
      <div id='flip-anim' class='fc-coin-anim' style='display:none;'>🪙</div>
      <style>@keyframes flip{0%{transform:rotateY(0);}100%{transform:rotateY(360deg);}} .fc-coin-anim{font-size:40px;animation:flip 1s linear infinite;}</style>
      {$result}
    </div>
HTML;
});

// ------------------------------
// Slot with animation
// ------------------------------
add_shortcode('fc_slot', function(){
    if (!is_user_logged_in()) return "Bitte einloggen.";
    $uid = get_current_user_id();
    $coins = fc_get_coins($uid);
    $symbols = ['🍒','⭐','🍋','💎'];
    $result = "";
    if(isset($_POST['fc_slot'])){
        if($coins>=100){
            $coins -= 100;
            $change = -100;
            $s1 = $symbols[array_rand($symbols)];
            $s2 = $symbols[array_rand($symbols)];
            $s3 = $symbols[array_rand($symbols)];
            $msg = "{$s1} | {$s2} | {$s3}";
            $win = 0;
            if($s1==$s2 || $s2==$s3 || $s1==$s3) $win = 200;
            if($s1==$s2 && $s2==$s3) $win = 500;
            if($win>0){
                $coins += $win;
                $change += $win;
                $result = "<div class='fc-result win'>{$msg} 🎉 Gewinn: +{$win}</div>";
            } else {
                $result = "<div class='fc-result lose'>{$msg} 😢 Kein Gewinn</div>";
            }
            fc_update_coins($uid,$coins);
            fc_add_log($uid,'Slot',$change,$coins);
        } else $result="<div class='fc-result lose'>Nicht genug Coins!</div>";
    }
    return <<<HTML
    <div class='fc-game'>
      <h2>Slot Machine</h2>
      <form method='post' onsubmit="document.getElementById('slot-anim').style.display='block'">
        <button class='fc-btn' name='fc_slot'>Slot spielen (100 Einsatz)</button>
      </form>
      <div class='fc-balance'>Kontostand: {$coins}</div>
      <div id='slot-anim' class='fc-slot-anim' style='display:none;'>🍒⭐🍋💎</div>
      <style>@keyframes spin{0%{letter-spacing:5px;}100%{letter-spacing:-5px;}} .fc-slot-anim{font-size:40px;animation:spin 0.2s linear infinite;}</style>
      {$result}
    </div>
HTML;
});

// ------------------------------
// Roulette with animation
// ------------------------------
add_shortcode('fc_roulette', function(){
    if (!is_user_logged_in()) return "Bitte einloggen.";
    $uid = get_current_user_id();
    $coins = fc_get_coins($uid);
    $result = "";
    if(isset($_POST['fc_roulette']) && isset($_POST['color'])){
        if($coins>=100){
            $coins -= 100;
            $change = -100;
            $num = rand(0,36);
            $color = ($_POST['color']=='red')?'rot':'schwarz';
            $hitColor = ($num % 2 == 0)?'rot':'schwarz';
            $msg = "Zahl {$num} ({$hitColor})";
            if($hitColor==$color){
                $coins += 200;
                $change = +100;
                $result = "<div class='fc-result win'>🎉 Gewinn! {$msg}</div>";
            } else {
                $result = "<div class='fc-result lose'>😢 Verloren! {$msg}</div>";
            }
            fc_update_coins($uid,$coins);
            fc_add_log($uid,'Roulette',$change,$coins);
        } else $result="<div class='fc-result lose'>Nicht genug Coins!</div>";
    }
    return <<<HTML
    <div class='fc-game'>
      <h2>Roulette</h2>
      <form method='post' onsubmit="document.getElementById('roulette-anim').style.display='block'">
        <select name='color'><option value='red'>Rot</option><option value='black'>Schwarz</option></select>
        <button class='fc-btn' name='fc_roulette' value='1'>Spielen (100 Einsatz)</button>
      </form>
      <div class='fc-balance'>Kontostand: {$coins}</div>
      <div id='roulette-anim' class='fc-roulette-anim' style='display:none;'>0 1 2 3 4 5 ...</div>
      <style>@keyframes roll{0%{opacity:0.2;}100%{opacity:1;}} .fc-roulette-anim{font-size:20px;animation:roll 0.1s linear infinite;}</style>
      {$result}
    </div>
HTML;
});

// ------------------------------
// Leaderboard & Profile
// ------------------------------
add_shortcode('fc_leaderboard', function(){
    global $wpdb;
    $table = $wpdb->prefix . "fc_users";
    $rows = $wpdb->get_results("SELECT user_id,coins FROM $table ORDER BY coins DESC LIMIT 20");
    $out = "<h2>Leaderboard</h2><ol>";
    foreach($rows as $r){
        $u = get_userdata($r->user_id);
        $name = $u ? $u->user_login : "User ".$r->user_id;
        $out .= "<li>{$name} – {$r->coins} Coins</li>";
    }
    $out .= "</ol>";
    return $out;
});

add_shortcode('fc_profile', function(){
    if (!is_user_logged_in()) return "Bitte einloggen.";
    $uid = get_current_user_id();
    $coins = fc_get_coins($uid);
    global $wpdb;
    $table = $wpdb->prefix . "fc_logs";
    $logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE user_id=%d ORDER BY created_at DESC LIMIT 10",$uid));
    $out = "<h2>Mein Profil</h2><p>Coins: {$coins}</p><h4>Letzte Aktionen</h4><ul>";
    foreach($logs as $l){
        $out .= "<li>{$l->created_at} – {$l->game} ({$l->change_amount}) → {$l->new_balance}</li>";
    }
    $out .= "</ul>";
    return $out;
});
?>
