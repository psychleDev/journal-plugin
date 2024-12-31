<?php
class JournalRoles
{
    public function __construct()
    {
        // Register activation and deactivation hooks
        register_activation_hook(GUIDED_JOURNAL_PLUGIN_DIR . 'guided-journal.php', [$this, 'activate']);
        register_deactivation_hook(GUIDED_JOURNAL_PLUGIN_DIR . 'guided-journal.php', [$this, 'deactivate']);

        // Add init hooks
        add_action('init', [$this, 'init_role']);
        add_action('admin_init', [$this, 'ensure_role_exists']);

        // Add filters for role management
        add_filter('editable_roles', [$this, 'add_editable_role']);

        // Add new user hook
        // add_action('user_register', [$this, 'set_default_role']);

        // Add page restriction functionality
        add_action('add_meta_boxes', [$this, 'add_restriction_meta_box']);
        add_action('save_post', [$this, 'save_restriction']);
        add_action('template_redirect', [$this, 'check_page_access']);

        // Hide admin bar for journal members
        add_action('after_setup_theme', function () {
            if (current_user_can('menoffire')) {
                show_admin_bar(false);
            }
        });

        // Redirect journal members from wp-admin to home page
        // add_action('admin_init', function() {
        //     $user = wp_get_current_user();

        //     // Check if user is a journal member and not an administrator
        //     if (in_array('menoffire', (array) $user->roles) && !in_array('administrator', (array) $user->roles)) {
        //         wp_redirect(home_url());
        //         exit;
        //     }
        // });
    }

    public function activate()
    {
        $this->create_journal_role();
        flush_rewrite_rules();
    }

    public function deactivate()
    {
        remove_role('menoffire');
        flush_rewrite_rules();
    }

    public function create_journal_role()
    {
        remove_role('menoffire');

        add_role('menoffire', 'Men of Fire', [
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'publish_posts' => false,
            'upload_files' => false,
            'level_0' => true,
            'view_journal' => true,
            'view_archive' => true
        ]);

        // Ensure subscriber role has journal access
        $subscriber = get_role('subscriber');
        if ($subscriber) {
            $subscriber->add_cap('read', true);
            $subscriber->add_cap('level_0', true);
            $subscriber->add_cap('view_journal', true);
        }
    }

    public function init_role()
    {
        $role = get_role('menoffire');
        if ($role) {
            $role->add_cap('read', true);
            $role->add_cap('level_0', true);
            $role->add_cap('view_journal', true);
        }
    }

    public function ensure_role_exists()
    {
        if (!get_role('menoffire')) {
            $this->create_journal_role();
        }
    }

    public function add_editable_role($roles)
    {
        $journal_role = get_role('menoffire');
        if ($journal_role) {
            $roles['menoffire'] = [
                'name' => 'Men of Fire',
                'capabilities' => $journal_role->capabilities
            ];
        }
        return $roles;
    }

    public function set_default_role($user_id)
    {
        $user = new WP_User($user_id);
        $user->set_role('menoffire');
    }

    public function add_restriction_meta_box()
    {
        add_meta_box(
            'journal_access_restriction',
            'Journal Access',
            [$this, 'render_restriction_meta_box'],
            'page'
        );
    }

    public function render_restriction_meta_box($post)
    {
        $restricted = get_post_meta($post->ID, '_journal_restricted', true);
        ?>
        <label>
            <input type="checkbox" name="journal_restricted" value="1" <?php checked($restricted, '1'); ?>>
            Restrict to Men of Fire Only
        </label>
        <?php
        wp_nonce_field('journal_access_nonce', 'journal_access_nonce');
    }

    public function save_restriction($post_id)
    {
        if (
            !isset($_POST['journal_access_nonce']) ||
            !wp_verify_nonce($_POST['journal_access_nonce'], 'journal_access_nonce')
        ) {
            return;
        }

        $restricted = isset($_POST['journal_restricted']) ? '1' : '0';
        update_post_meta($post_id, '_journal_restricted', $restricted);
    }

    public function check_page_access()
    {
        if (is_page()) {
            $restricted = get_post_meta(get_the_ID(), '_journal_restricted', true);
            if ($restricted == '1') {
                $user = wp_get_current_user();
                if (!in_array('menoffire', (array) $user->roles) && !current_user_can('administrator')) {
                    wp_redirect(home_url('/'));
                    exit;
                }
            }
        }
    }
}

// Initialize the roles management
$journal_roles = new JournalRoles();