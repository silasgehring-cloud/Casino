<?php
/*
Plugin Name: FC Auth
Description: Minimalistisches Login- und Registrierungsformular im Fun-Casino-Stil.
Version: 1.0
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

// --------------------------------
// Aktivierung: Login-Seite anlegen
// --------------------------------
register_activation_hook(__FILE__, function(){
    $title = __('Casino Login', 'fun-casino');
    if(!get_page_by_title($title)){
        $id = wp_insert_post([
            'post_title'   => $title,
            'post_content' => '[fc_auth]',
            'post_status'  => 'publish',
            'post_author'  => 1,
            'post_type'    => 'page',
        ]);
        if($id){
            update_post_meta($id, '_wp_page_template', 'fc-template.php');
        }
    }
});

// --------------------------------
// Shortcode [fc_auth]
// --------------------------------
add_shortcode('fc_auth', function(){
    $msg = '';
    if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['fc_auth_action']) && isset($_POST['fc_auth_nonce']) && wp_verify_nonce($_POST['fc_auth_nonce'],'fc_auth')){
        $action = sanitize_text_field(wp_unslash($_POST['fc_auth_action']));
        if($action==='register'){
            $u = sanitize_user(wp_unslash($_POST['username']));
            $p = wp_unslash($_POST['password']);
            $c = wp_unslash($_POST['confirm']);
            if($p !== $c){
                $msg = __('Passwörter stimmen nicht überein.', 'fun-casino');
            } else {
                $placeholder_email = $u . '@example.com';
                $uid = wp_create_user($u,$p,$placeholder_email);
                if(is_wp_error($uid)){
                    $msg = $uid->get_error_message();
                } else {
                    wp_signon(['user_login'=>$u,'user_password'=>$p,'remember'=>true], false);
                    $msg = __('Registrierung erfolgreich. Du bist eingeloggt.', 'fun-casino');
                }
            }
        } elseif($action==='login') {
            $creds = [
                'user_login'    => sanitize_user(wp_unslash($_POST['username'])),
                'user_password' => wp_unslash($_POST['password']),
                'remember'      => true,
            ];
            $user = wp_signon($creds,false);
            if(is_wp_error($user)){
                $msg = $user->get_error_message();
            } else {
                $msg = __('Login erfolgreich.', 'fun-casino');
            }
        }
    }

    ob_start();
    ?>
    <div class="fc-auth-wrapper">
      <?php if($msg) echo '<div class="fc-auth-msg">'.esc_html($msg).'</div>'; ?>
      <h1 id="fc-auth-title" data-login="<?php echo esc_attr__('Login', 'fun-casino'); ?>" data-register="<?php echo esc_attr__('Registrierung', 'fun-casino'); ?>"><?php esc_html_e('Login', 'fun-casino'); ?></h1>
      <form method="post" id="fc-auth-form">
        <input name="username" type="text" placeholder="<?php echo esc_attr__('Benutzername', 'fun-casino'); ?>" required />
        <input name="password" type="password" placeholder="<?php echo esc_attr__('Passwort', 'fun-casino'); ?>" required />
        <input name="confirm" type="password" class="fc-auth-confirm" placeholder="<?php echo esc_attr__('Passwort bestätigen', 'fun-casino'); ?>" />
        <input type="hidden" name="fc_auth_action" value="login" id="fc_auth_action" />
        <?php wp_nonce_field('fc_auth','fc_auth_nonce'); ?>
        <button type="submit"><?php esc_html_e('Los geht’s', 'fun-casino'); ?></button>
      </form>
      <div class="fc-auth-toggle">
        <span id="fc-auth-toggle-text" data-login="<?php echo esc_attr__('Noch keinen Account?', 'fun-casino'); ?>" data-register="<?php echo esc_attr__('Schon registriert?', 'fun-casino'); ?>"><?php esc_html_e('Noch keinen Account?', 'fun-casino'); ?></span>
        <a href="#" id="fc-auth-toggle-link" data-login="<?php echo esc_attr__('Registrieren', 'fun-casino'); ?>" data-register="<?php echo esc_attr__('Login', 'fun-casino'); ?>"><?php esc_html_e('Registrieren', 'fun-casino'); ?></a>
      </div>
    </div>
    <?php
    return ob_get_clean();
});
