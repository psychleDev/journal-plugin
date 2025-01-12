<?php
namespace GuidedJournal;

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

    // Keep all your other existing methods with the same functionality...
}