<?php
namespace GuidedJournal;
class GuidedJournalDemoMode
{
    private $prompts = [
        // Life & Purpose
        ['category' => 'Life', 'prompt' => 'What does living a meaningful life mean to you?'],
        ['category' => 'Life', 'prompt' => 'Describe your perfect day from start to finish.'],
        ['category' => 'Life', 'prompt' => 'What legacy would you like to leave behind?'],

        // Happiness & Joy
        ['category' => 'Happiness', 'prompt' => 'List five things that made you smile today.'],
        ['category' => 'Happiness', 'prompt' => 'What activities make you lose track of time?'],
        ['category' => 'Happiness', 'prompt' => 'Describe a moment when you felt pure joy.'],

        // Self-Esteem
        ['category' => 'Self-Esteem', 'prompt' => 'What are your three greatest strengths?'],
        ['category' => 'Self-Esteem', 'prompt' => 'Write about a challenge you overcame and what it taught you.'],
        ['category' => 'Self-Esteem', 'prompt' => 'What would you tell your younger self about self-worth?'],

        // Positive Self-Talk
        ['category' => 'Positive Self-Talk', 'prompt' => 'Transform three negative thoughts into positive ones.'],
        ['category' => 'Positive Self-Talk', 'prompt' => 'Write a letter of encouragement to yourself.'],
        ['category' => 'Positive Self-Talk', 'prompt' => 'List your achievements from the past year.'],

        // Patience
        ['category' => 'Patience', 'prompt' => 'Describe a situation where patience led to a better outcome.'],
        ['category' => 'Patience', 'prompt' => 'What helps you stay calm in challenging situations?'],
        ['category' => 'Patience', 'prompt' => 'Write about something worth waiting for.'],

        // Love
        ['category' => 'Love', 'prompt' => 'What does unconditional love mean to you?'],
        ['category' => 'Love', 'prompt' => 'Describe how you show love to others.'],
        ['category' => 'Love', 'prompt' => 'Write about someone who taught you about love.'],

        // Friendship
        ['category' => 'Friendship', 'prompt' => 'What qualities do you value most in a friend?'],
        ['category' => 'Friendship', 'prompt' => 'Write about a friendship that changed your life.'],
        ['category' => 'Friendship', 'prompt' => 'How do you maintain meaningful friendships?']
        // ... Add more prompts to complete 365 days
    ];

    public function __construct()
    {
        add_action('admin_post_install_demo_prompts', [$this, 'install_demo_prompts']);
        add_action('admin_notices', [$this, 'admin_notices']);
    }

    public function render_demo_section()
    {
        ?>
        <div class="demo-mode-section">
            <h2><?php _e('Demo Mode', 'guided-journal'); ?></h2>
            <p class="description">
                <?php _e('Install 365 pre-written journal prompts covering topics like life, happiness, self-esteem, positive self-talk, patience, love, and friendship.', 'guided-journal'); ?>
            </p>
            <div class="demo-mode-actions">
                <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                    <?php wp_nonce_field('install_demo_prompts_nonce', 'demo_prompts_nonce'); ?>
                    <input type="hidden" name="action" value="install_demo_prompts">
                    <button type="submit" class="button button-primary"
                        onclick="return confirm('<?php _e('This will add 365 demo prompts. Any existing prompts will be preserved. Continue?', 'guided-journal'); ?>');">
                        <?php _e('Install Demo Prompts', 'guided-journal'); ?>
                    </button>
                </form>
            </div>
        </div>
        <?php
    }

    public function install_demo_prompts()
    {
        if (!current_user_can('manage_options') || !check_admin_referer('install_demo_prompts_nonce', 'demo_prompts_nonce')) {
            wp_die('Unauthorized access');
        }

        $installed = 0;
        $existing_prompts = $this->get_existing_prompt_numbers();
        $next_number = $this->get_next_available_number($existing_prompts);

        foreach ($this->prompts as $prompt_data) {
            if ($installed >= 365)
                break;

            $number = str_pad($next_number, 3, '0', STR_PAD_LEFT);

            // Check if this number already exists
            if (in_array($number, $existing_prompts)) {
                $next_number++;
                continue;
            }

            $post_data = array(
                'post_title' => $number,
                'post_content' => $prompt_data['prompt'],
                'post_status' => 'publish',
                'post_type' => 'journal_prompt',
                'post_author' => get_current_user_id(),
            );

            $post_id = wp_insert_post($post_data);

            if (!is_wp_error($post_id)) {
                // Add category as term
                wp_set_object_terms($post_id, $prompt_data['category'], 'prompt_category', true);
                $installed++;
                $next_number++;
            }
        }

        // Redirect back to settings page with success message
        wp_redirect(add_query_arg(
            array('page' => 'journal-settings', 'demo_installed' => $installed),
            admin_url('admin.php')
        ));
        exit;
    }

    private function get_existing_prompt_numbers()
    {
        $existing_prompts = get_posts([
            'post_type' => 'journal_prompt',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);

        $numbers = [];
        foreach ($existing_prompts as $prompt_id) {
            $numbers[] = get_the_title($prompt_id);
        }

        return $numbers;
    }

    private function get_next_available_number($existing_prompts)
    {
        if (empty($existing_prompts)) {
            return 1;
        }

        $numbers = array_map('intval', $existing_prompts);
        return max($numbers) + 1;
    }

    public function admin_notices()
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'journal-settings') {
            return;
        }

        if (isset($_GET['demo_installed'])) {
            $installed = intval($_GET['demo_installed']);
            $message = sprintf(
                __('Successfully installed %d demo prompts.', 'guided-journal'),
                $installed
            );
            printf('<div class="notice notice-success"><p>%s</p></div>', esc_html($message));
        }
    }
}