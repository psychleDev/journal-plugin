<?php
namespace GuidedJournal;

class JournalRoles
{
    public function __construct()
    {
        // Initialize the roles
        add_action('init', [$this, 'initialize_roles']);
        add_action('admin_init', [$this, 'ensure_roles_exist']);

        // Hide admin bar for journal users
        add_action('after_setup_theme', function () {
            if (!current_user_can('edit_posts')) {
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
        if (!get_role('journal_user')) {
            $this->create_journal_roles();
        }
    }

    /**
     * Create the journal roles
     */
    public function create_journal_roles()
    {
        // Remove and recreate journal_user role
        remove_role('journal_user');
        add_role('journal_user', 'Journal User', [
            'read' => true,
            'level_0' => true,
            'view_journal' => true
        ]);

        // Add capabilities to admin role
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('view_journal');
            $admin->add_cap('manage_journal');
        }

        // Add capabilities to editor role
        $editor = get_role('editor');
        if ($editor) {
            $editor->add_cap('view_journal');
        }
    }

    /**
     * Set default role for new users
     */
    public function set_default_role($user_id)
    {
        $user = new \WP_User($user_id);
        $user->add_role('journal_user');
    }
}