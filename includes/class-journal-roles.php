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
        // Remove and recreate menoffire role
        remove_role('menoffire');
        add_role('menoffire', 'Men of Fire', [
            'read' => true,
            'level_0' => true,
            'view_journal' => true,
            'view_archive' => true
        ]);

        remove_role('ignite30');
        add_role('ignite30', 'Ignite 30', [
            'read' => true,
            'level_0' => true,
            'view_journal' => true,
            'view_archive' => true
        ]);

        // Update subscriber capabilities
        $ignite30 = get_role('ignite30');
        if ($ignite30) {
            $ignite30->remove_cap('view_archive');
            $ignite30->add_cap('read');
            $ignite30->add_cap('level_0');
            $ignite30->add_cap('view_journal');
        }

        // Add capability to admin role
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('view_journal');
            $admin->add_cap('view_archive');
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
        $irole = get_role('ignite30');
        if ($irole) {
            $irole->add_cap('read', true);
            $irole->add_cap('level_0', true);
            $irole->add_cap('view_journal', true);
        }

    }

    public function ensure_role_exists()
    {
        if (!get_role('menoffire') || !get_role('ignite30')) {
            $this->create_journal_role();
        }
    }

    public function add_editable_role($roles)
    {
        $journal_role = get_role('menoffire');
        $ignite_role = get_role('ignite30');
        if ($journal_role) {
            $roles['menoffire'] = [
                'name' => 'Men of Fire',
                'capabilities' => $journal_role->capabilities
            ];
        }

        if ($ignite_role) {
            $roles['ignite30'] = [
                'name' => 'Ignite 30',
                'capabilities' => $ignite_role->capabilities
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
                if (!in_array('menoffire', (array) $user->roles) && !in_array('ignite30', (array) $user->roles) && !current_user_can('administrator')) {
                    wp_redirect(home_url('/'));
                    exit;
                }
            }
        }
    }
}

// Initialize the roles management
$journal_roles = new JournalRoles();