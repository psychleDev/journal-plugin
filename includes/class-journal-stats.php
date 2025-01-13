<?php
namespace GuidedJournal;

class JournalStats
{
    private $wpdb;
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'journal_entries';

        // Create stats table on activation
        add_action('admin_init', [$this, 'create_stats_table']);

        // Register AJAX handlers
        add_action('wp_ajax_get_journal_progress', [$this, 'get_progress']);
        add_action('wp_ajax_save_journal_entry', [$this, 'save_entry'], 1);
    }

    public function create_stats_table()
    {
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}journal_stats (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            day_number int(11) NOT NULL,
            word_count int(11) NOT NULL DEFAULT 0,
            time_spent int(11) NOT NULL DEFAULT 0,
            last_modified datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_day (user_id, day_number)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function get_progress()
    {
        check_ajax_referer('journal_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in to view progress');
        }

        $user_id = get_current_user_id();

        // Get total number of prompts
        $total_prompts = wp_count_posts('journal_prompt')->publish;

        // Get completed entries count
        $completed_count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT day_number) FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ));

        // Calculate completion rate
        $completion_rate = $total_prompts > 0 ? round(($completed_count / $total_prompts) * 100) : 0;

        // Calculate streak
        $streak = $this->calculate_streak($user_id);

        wp_send_json_success([
            'completion_rate' => $completion_rate,
            'streak' => $streak
        ]);
    }

    private function calculate_streak($user_id)
    {
        $entries = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT DATE(created_at) as entry_date 
            FROM {$this->table_name} 
            WHERE user_id = %d 
            ORDER BY created_at DESC",
            $user_id
        ));

        if (empty($entries)) {
            return 0;
        }

        $streak = 1;
        $last_date = strtotime($entries[0]->entry_date);
        $today = strtotime('today');

        // Break streak if no entry today or yesterday
        if ($last_date < strtotime('yesterday')) {
            return 0;
        }

        // Calculate consecutive days
        for ($i = 1; $i < count($entries); $i++) {
            $current_date = strtotime($entries[$i]->entry_date);
            $date_diff = round(($last_date - $current_date) / (60 * 60 * 24));

            if ($date_diff == 1) {
                $streak++;
                $last_date = $current_date;
            } else {
                break;
            }
        }

        return $streak;
    }

    public function save_entry($entry_data)
    {
        // This method should be called by the main save_entry method
        if (!isset($entry_data['word_count']) || !isset($entry_data['time_spent'])) {
            return;
        }

        $user_id = get_current_user_id();
        $day_number = $entry_data['day'];

        // Update stats table
        $this->wpdb->replace(
            $this->wpdb->prefix . 'journal_stats',
            [
                'user_id' => $user_id,
                'day_number' => $day_number,
                'word_count' => intval($entry_data['word_count']),
                'time_spent' => intval($entry_data['time_spent']),
                'last_modified' => current_time('mysql')
            ],
            ['%d', '%d', '%d', '%d', '%s']
        );
    }

    public function get_user_stats($user_id)
    {
        // Get total word count
        $total_words = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT SUM(word_count) FROM {$this->wpdb->prefix}journal_stats WHERE user_id = %d",
            $user_id
        ));

        // Get total time spent
        $total_time = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT SUM(time_spent) FROM {$this->wpdb->prefix}journal_stats WHERE user_id = %d",
            $user_id
        ));

        // Get average words per entry
        $avg_words = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT AVG(word_count) FROM {$this->wpdb->prefix}journal_stats WHERE user_id = %d",
            $user_id
        ));

        return [
            'total_words' => intval($total_words),
            'total_time' => intval($total_time),
            'avg_words' => round($avg_words),
            'streak' => $this->calculate_streak($user_id)
        ];
    }
}