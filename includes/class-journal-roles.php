<?php
namespace GuidedJournal;

class JournalRoles
{

    public function __construct()
    {
        // Initialize the roles
        add_action('init', [$this, 'initialize_roles']);
        add_action('admin_init', [$this, 'ensure_roles_exist']);

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

    /**
     * Initialize roles on plugin activation
     */
    public function initialize_roles()
    {
        $this->create_journal_roles();
    }

    /**
     * Ensure roles exist on admin init
     */
    public function ensure_roles_exist()
    {
        if (!get_role('menoffire') || !get_role('ignite30')) {
            $this->create_journal_roles();
        }
    }

    /**
     * Create the journal roles
     */
    public function create_journal_roles()
    {
        // Remove and recreate menoffire role
        remove_role('menoffire');
        add_role('menoffire', 'Men of Fire', [
            'read' => true,
            'level_0' => true,
            'view_journal' => true,
            'view_archive' => true
        ]);

        // Remove and recreate ignite30 role
        remove_role('ignite30');
        add_role('ignite30', 'Ignite 30', [
            'read' => true,
            'level_0' => true,
            'view_journal' => true,
            'view_archive' => false
        ]);

        // Add capabilities to admin role
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('view_journal');
            $admin->add_cap('view_archive');
        }
    }

    /**
     * Add restriction meta box to pages
     */
    public function add_restriction_meta_box()
    {
        add_meta_box(
            'journal_access_restriction',
            'Journal Access',
            [$this, 'render_restriction_meta_box'],
            'page',
            'side',
            'high'
        );
    }

    /**
     * Render the restriction meta box
     */
    public function render_restriction_meta_box($post)
    {
        $restricted = get_post_meta($post->ID, '_journal_restricted', true);
        wp_nonce_field('journal_access_nonce', 'journal_access_nonce');
        ?>
        <label>
            <input type="checkbox" name="journal_restricted" value="1" <?php checked($restricted, '1'); ?>>
            Restrict to Journal Members Only
        </label>
        <?php
    }

    /**
     * Save the restriction meta box data
     */
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

    /**
     * Check page access restrictions
     */
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

    /**
     * Add roles to the editable roles list
     */
    public function add_editable_roles($roles)
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

    /**
     * Set default role for new users
     */
    public function set_default_role($user_id)
    {
        $user = new \WP_User($user_id);
        $user->set_role('menoffire');
    }
}