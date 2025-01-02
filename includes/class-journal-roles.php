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

        // Auth0 user handling
        add_action('auth0_user_login', function ($user_id, $user_data) {
            if (!$user_id) {
                $user_id = $this->create_auth0_user($user_data);
            }
        }, 10, 2);

        // Error page redirect
        add_action('template_redirect', function () {
            $current_uri = $_SERVER['REQUEST_URI'];

            // Check for Auth0 callback with error or API error
            if (
                (isset($_GET['auth0']) && isset($_GET['code'])) ||
                strpos($current_uri, 'api/v2') !== false ||
                (isset($_GET['error']) && isset($_GET['error_description']))
            ) {
                error_log('Redirecting to verification page. URI: ' . $current_uri);
                wp_redirect(get_page_link($this->create_or_get_error_page()));
                exit;
            }
        });

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
            'view_archive' => false
        ]);

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
        if (!get_role('menoffire')) {
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
                if (!current_user_can('menoffire') && !current_user_can('ignite30') && !current_user_can('administrator')) {
                    wp_redirect(home_url('/'));
                    exit;
                }
            }
        }
    }
    private function create_or_get_error_page()
    {
        $page = get_page_by_path('verify-email');

        if (!$page) {
            $page_id = wp_insert_post([
                'post_title' => 'Email Verification Required',
                'post_name' => 'verify-email',
                'post_content' => file_get_contents(get_template_directory() . '/page-verify-email.php'),
                'post_status' => 'publish',
                'post_type' => 'page',
                'comment_status' => 'closed'
            ]);
            return $page_id;
        }

        return $page->ID;
    }
    // Add this to class-journal-roles.php

    public function create_auth0_user($user_data)
    {
        try {
            // Sanitize and prepare user data
            $email = sanitize_email($user_data->email);
            $username = sanitize_user($user_data->email);

            // Check if user exists
            if (!email_exists($email)) {
                // Create WP user
                $user_id = wp_create_user(
                    $username,
                    wp_generate_password(),
                    $email
                );

                if (!is_wp_error($user_id)) {
                    // Set role to subscriber by default
                    $user = new WP_User($user_id);
                    $user->set_role('subscriber');

                    // Log success
                    error_log('Created new WordPress user from Auth0: ' . $email);
                    return $user_id;
                }
            } else {
                // User exists, just return the ID
                $existing_user = get_user_by('email', $email);
                return $existing_user->ID;
            }
        } catch (Exception $e) {
            error_log('Error creating WordPress user: ' . $e->getMessage());
            return false;
        }
    }

}

// Initialize the roles management
$journal_roles = new JournalRoles();