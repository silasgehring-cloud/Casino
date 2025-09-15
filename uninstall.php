<?php
if ( ! defined('WP_UNINSTALL_PLUGIN') ) exit;

global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fc_users");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fc_logs");

$pages = [
    __('Casino Home', 'fun-casino'),
    __('Casino Coinflip', 'fun-casino'),
    __('Casino Slot', 'fun-casino'),
    __('Casino Roulette', 'fun-casino'),
    __('Casino Blackjack', 'fun-casino'),
    __('Casino Profile', 'fun-casino'),
    __('Casino Leaderboard', 'fun-casino'),
];
foreach($pages as $title){
    $page = get_page_by_title($title);
    if($page){
        wp_delete_post($page->ID, true);
    }
}
