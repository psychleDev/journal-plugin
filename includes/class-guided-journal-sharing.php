<?php
namespace GuidedJournal;

class GuidedJournalSharing
{
    private $wpdb;
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'journal_share_tokens';

        // Initialize sharing functionality
        add_action('init', [$this, 'init']);
        add_action('wp_ajax_generate_share_token', [$this, 'generate_share_token']);
        add_filter('template_include', [$this, 'handle_shared_entry'], 100);

        // Enqueue scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_share_assets']);
    }

    public function init()
    {
        // Register share endpoint
        add_rewrite_rule(
            'shared-entry/([^/]+)/?$',
            'index.php?share_token=$matches[1]',
            'top'
        );
        add_rewrite_tag('%share_token%', '([^/]+)');
    }

    public function activate()
    {
        // Create share tokens table
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            entry_day int(11) NOT NULL,
            token varchar(64) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NOT NULL,
            views int(11) DEFAULT 0,
            max_views int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY token (token)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    public function enqueue_share_assets()
    {
        if (is_singular('journal_prompt') || $this->is_shared_entry()) {
            wp_enqueue_script(
                'guided-journal-sharing',
                GUIDED_JOURNAL_PLUGIN_URL . 'assets/js/sharing.js',
                ['jquery', 'wp-element'],
                GUIDED_JOURNAL_VERSION,
                true
            );

            wp_localize_script('guided-journal-sharing', 'journalSharing', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('journal_share_nonce')
            ]);
        }
    }

    public function generate_share_token()
    {
        check_ajax_referer('journal_share_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not authorized', 'guided-journal')]);
        }

        $entry_day = intval($_POST['entry_day']);
        $expiry_hours = isset($_POST['expiry_hours']) ? intval($_POST['expiry_hours']) : 24;
        $max_views = isset($_POST['max_views']) ? intval($_POST['max_views']) : 3;

        // Generate unique token
        $token = wp_generate_password(32, false);

        // Calculate expiry
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_hours} hours"));

        $result = $this->wpdb->insert(
            $this->table_name,
            [
                'user_id' => get_current_user_id(),
                'entry_day' => $entry_day,
                'token' => $token,
                'expires_at' => $expires_at,
                'max_views' => $max_views
            ],
            ['%d', '%d', '%s', '%s', '%d']
        );

        if ($result === false) {
            wp_send_json_error(['message' => __('Failed to generate share link', 'guided-journal')]);
        }

        wp_send_json_success(['token' => $token]);
    }

    public function handle_shared_entry($template)
    {
        if (!$this->is_shared_entry()) {
            return $template;
        }

        $token = get_query_var('share_token');
        $share_data = $this->validate_share_token($token);

        if (is_wp_error($share_data)) {
            return $this->get_error_template($share_data);
        }

        // Update view count
        $this->wpdb->update(
            $this->table_name,
            ['views' => $share_data->views + 1],
            ['token' => $token],
            ['%d'],
            ['%s']
        );

        // Load shared entry template
        $shared_template = GUIDED_JOURNAL_PLUGIN_DIR . 'templates/shared-entry.php';
        if (file_exists($shared_template)) {
            return $shared_template;
        }

        return $template;
    }

    private function validate_share_token($token)
    {
        $share_data = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE token = %s",
            $token
        ));

        if (!$share_data) {
            return new \WP_Error('invalid_token', __('Invalid or expired share link', 'guided-journal'));
        }

        if (strtotime($share_data->expires_at) < time()) {
            return new \WP_Error('expired_token', __('This share link has expired', 'guided-journal'));
        }

        if ($share_data->max_views && $share_data->views >= $share_data->max_views) {
            return new \WP_Error('max_views', __('This share link has reached its maximum view limit', 'guided-journal'));
        }

        return $share_data;
    }

    private function is_shared_entry()
    {
        return get_query_var('share_token') !== '';
    }

    private function get_error_template($error)
    {
        set_query_var('share_error', $error);
        $error_template = GUIDED_JOURNAL_PLUGIN_DIR . 'templates/share-error.php';
        if (file_exists($error_template)) {
            return $error_template;
        }
        return get_404_template();
    }
}