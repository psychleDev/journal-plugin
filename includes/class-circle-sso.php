<?php
namespace GuidedJournal;

class CircleSSO {
    private $api_key;
    private $circle_domain;
    private $token_cookie = 'circle_sso_token';
    
    public function __construct() {
        $this->api_key = get_option('guided_journal_circle_sso_key');
        $this->circle_domain = get_option('guided_journal_circle_domain');
    }
    
    public function verify_user_access() {
        $token = $this->get_circle_token();
        if (!$token) {
            return false;
        }
        
        return $this->verify_token_with_circle($token);
    }
    
    private function get_circle_token() {
        if (isset($_COOKIE[$this->token_cookie])) {
            return sanitize_text_field($_COOKIE[$this->token_cookie]);
        }
        
        if (isset($_GET['circle_token'])) {
            $token = sanitize_text_field($_GET['circle_token']);
            $this->set_token_cookie($token);
            return $token;
        }
        
        return false;
    }
    
    private function set_token_cookie($token) {
        setcookie(
            $this->token_cookie,
            $token,
            time() + (86400 * 30),
            '/',
            COOKIE_DOMAIN,
            true,
            true
        );
    }
    
    private function verify_token_with_circle($token) {
        $response = wp_remote_post("https://api.circle.so/api/v1/verify_sso", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode(['token' => $token]),
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            error_log('Circle SSO verification failed: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['valid']) && $data['valid']) {
            if (isset($data['user'])) {
                $this->update_user_data($data['user']);
            }
            return true;
        }
        
        return false;
    }
    
    private function update_user_data($circle_user) {
        $wp_user = get_user_by('email', $circle_user['email']);
        
        if (!$wp_user) {
            $user_id = wp_create_user(
                $circle_user['username'],
                wp_generate_password(),
                $circle_user['email']
            );
            
            if (!is_wp_error($user_id)) {
                $wp_user = get_user_by('ID', $user_id);
            }
        }
        
        if ($wp_user) {
            update_user_meta($wp_user->ID, 'circle_user_id', $circle_user['id']);
            update_user_meta($wp_user->ID, 'circle_username', $circle_user['username']);
            
            wp_set_current_user($wp_user->ID);
            wp_set_auth_cookie($wp_user->ID);
        }
    }
    
    public function get_login_url() {
        $redirect_url = home_url('journal');
        $encoded_redirect = urlencode($redirect_url);
        
        return sprintf(
            'https://%s/api/v1/sso/authorize?redirect_url=%s',
            $this->circle_domain,
            $encoded_redirect
        );
    }
    
    public function render_login_button() {
        return sprintf(
            '<a href="%s" class="circle-login-button">%s</a>',
            esc_url($this->get_login_url()),
            esc_html__('Login with Circle', 'guided-journal')
        );
    }
}
