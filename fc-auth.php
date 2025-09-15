<?php
/*
Plugin Name: FC Auth
Description: Minimalistisches Login- und Registrierungsformular im Fun-Casino-Stil.
Version: 1.0
Author: Silas
*/

if ( ! defined('ABSPATH') ) exit;

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
    if(!get_page_by_title('Casino Login')){
        $id = wp_insert_post([
            'post_title'   => 'Casino Login',
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
        $action = sanitize_text_field($_POST['fc_auth_action']);
        if($action==='register'){
            $u = sanitize_user($_POST['username']);
            $p = $_POST['password'];
            $c = $_POST['confirm'];
            if($p !== $c){
                $msg = 'Passwörter stimmen nicht überein.';
            } else {
                $placeholder_email = $u . '@example.com';
                $uid = wp_create_user($u,$p,$placeholder_email);
                if(is_wp_error($uid)){
                    $msg = $uid->get_error_message();
                } else {
                    wp_signon(['user_login'=>$u,'user_password'=>$p,'remember'=>true], false);
                    $msg = 'Registrierung erfolgreich. Du bist eingeloggt.';
                }
            }
        } elseif($action==='login') {
            $creds = [
                'user_login'    => sanitize_user($_POST['username']),
                'user_password' => $_POST['password'],
                'remember'      => true,
            ];
            $user = wp_signon($creds,false);
            if(is_wp_error($user)){
                $msg = $user->get_error_message();
            } else {
                $msg = 'Login erfolgreich.';
            }
        }
    }

    ob_start();
    ?>
    <div class="fc-auth-wrapper">
      <?php if($msg) echo '<div class="fc-auth-msg">'.esc_html($msg).'</div>'; ?>
      <h1 id="fc-auth-title">Login</h1>
      <form method="post" id="fc-auth-form">
        <input name="username" type="text" placeholder="Benutzername" required />
        <input name="password" type="password" placeholder="Passwort" required />
        <input name="confirm" type="password" placeholder="Passwort bestätigen" style="display:none;" />
        <input type="hidden" name="fc_auth_action" value="login" id="fc_auth_action" />
        <?php wp_nonce_field('fc_auth','fc_auth_nonce'); ?>
        <button type="submit">Los geht’s</button>
      </form>
      <div class="fc-auth-toggle">
        <span id="fc-auth-toggle-text">Noch keinen Account?</span>
        <a href="#" id="fc-auth-toggle-link">Registrieren</a>
      </div>
    </div>
    <style>
      :root{
        --bg:#F5F5F0;
        --card-bg:rgba(255,255,255,0.75);
        --accent:#d4af37;
        --text:#2E2E2E;
      }
      .fc-auth-wrapper{width:320px;margin:40px auto;padding:2.5rem;background:var(--card-bg);border-radius:12px;box-shadow:0 6px 18px rgba(0,0,0,0.08);backdrop-filter:blur(6px);text-align:center;font-family:'Source Sans Pro',sans-serif;}
      .fc-auth-wrapper h1{font-weight:500;font-size:1.4rem;margin-bottom:1.5rem;color:var(--text);font-family:'Playfair Display',serif;}
      .fc-auth-wrapper input{width:100%;margin-bottom:1rem;padding:0.7rem 1rem;border:1px solid rgba(0,0,0,0.1);border-radius:6px;font-size:0.95rem;background:#fff;color:var(--text);}
      .fc-auth-wrapper button{width:100%;padding:0.8rem;border:none;cursor:pointer;font-size:1rem;font-weight:500;border-radius:6px;background:linear-gradient(135deg,var(--accent),#d7b57d);color:#fff;box-shadow:0 4px 10px rgba(0,0,0,0.1);transition:filter 0.2s;}
      .fc-auth-wrapper button:hover{filter:brightness(1.05);}
      .fc-auth-toggle{margin-top:1.2rem;font-size:0.85rem;}
      .fc-auth-toggle a{color:var(--accent);text-decoration:none;font-weight:500;}
      .fc-auth-msg{margin-bottom:1rem;color:var(--accent);font-weight:500;}
    </style>
    <script>
      const fcTitle=document.getElementById('fc-auth-title');
      const fcToggleText=document.getElementById('fc-auth-toggle-text');
      const fcToggleLink=document.getElementById('fc-auth-toggle-link');
      const confirmField=document.querySelector('input[name="confirm"]');
      const actionField=document.getElementById('fc_auth_action');
      let isLogin=true;
      fcToggleLink.addEventListener('click',e=>{
        e.preventDefault();
        isLogin=!isLogin;
        fcTitle.textContent=isLogin?'Login':'Registrierung';
        fcToggleText.textContent=isLogin?'Noch keinen Account?':'Schon registriert?';
        fcToggleLink.textContent=isLogin?'Registrieren':'Login';
        confirmField.style.display=isLogin?'none':'block';
        confirmField.required=!isLogin;
        actionField.value=isLogin?'login':'register';
      });
    </script>
    <?php
    return ob_get_clean();
});
