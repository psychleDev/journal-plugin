# Guided Journal - Developer Documentation

## Plugin Architecture

### Core Components
- `class-guided-journal.php`: Main plugin functionality
- `class-guided-journal-settings.php`: Settings management
- `class-guided-journal-sharing.php`: Entry sharing capabilities
- `class-journal-roles.php`: User role management
- `class-journal-stats.php`: Progress tracking

## Database Schema

### Journal Entries Table
```sql
CREATE TABLE wp_journal_entries (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    day_number int(11) NOT NULL,
    entry_text longtext NOT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_day (user_id, day_number)
)
```

### Journal Stats Table
```sql
CREATE TABLE wp_journal_stats (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    day_number int(11) NOT NULL,
    word_count int(11) NOT NULL DEFAULT 0,
    time_spent int(11) NOT NULL DEFAULT 0,
    last_modified datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY user_day (user_id, day_number)
)
```

### Share Tokens Table
```sql
CREATE TABLE wp_journal_share_tokens (
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
)
```

## Hooks and Filters

### Action Hooks
- `guided_journal_init`: Plugin initialization
- `guided_journal_daily_maintenance`: Daily cleanup tasks
- `wp_ajax_save_journal_entry`: AJAX entry saving
- `wp_ajax_export_journal_entries`: Entry export functionality

### Filter Hooks
- `guided_journal_entry_content`: Modify entry content
- `guided_journal_prompt_query`: Customize prompt retrieval
- `guided_journal_export_data`: Modify export data

## Extending the Plugin

### Custom Prompt Categories
```php
// Register custom prompt category
function register_journal_prompt_category() {
    register_taxonomy('prompt_category', 'journal_prompt', [
        'hierarchical' => true,
        'labels' => [
            'name' => _x('Prompt Categories', 'taxonomy general name'),
            'singular_name' => _x('Prompt Category', 'taxonomy singular name'),
        ],
        'public' => true,
        'show_ui' => true,
    ]);
}
add_action('init', 'register_journal_prompt_category');
```

### Adding Custom Entry Validation
```php
// Example: Add minimum word count validation
function validate_journal_entry($entry_data) {
    $min_words = get_option('guided_journal_min_words', 0);
    $word_count = str_word_count(strip_tags($entry_data['text']));
    
    if ($word_count < $min_words) {
        wp_send_json_error([
            'message' => sprintf(
                __('Entries must be at least %d words long.', 'guided-journal'), 
                $min_words
            )
        ]);
    }
    
    return $entry_data;
}
add_filter('guided_journal_before_save_entry', 'validate_journal_entry');
```

## Performance Optimization

### Caching Strategies
- Use WordPress object caching
- Implement transient caching for user stats
- Minimize database queries

### Example Caching
```php
function get_cached_user_stats($user_id) {
    $cache_key = 'journal_user_stats_' . $user_id;
    $stats = wp_cache_get($cache_key);
    
    if (false === $stats) {
        $stats_class = new GuidedJournal\JournalStats();
        $stats = $stats_class->get_user_stats($user_id);
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $stats, 'guided_journal', 3600);
    }
    
    return $stats;
}
```

## Security Considerations

### Best Practices
- Always use `wp_verify_nonce()`
- Sanitize and validate all input
- Use prepared statements for database queries
- Implement proper user role checks

### Sample Security Implementation
```php
function secure_journal_access() {
    // Ensure only authenticated users can access journal
    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url());
        exit;
    }
    
    // Check user capabilities
    if (!current_user_can('view_journal')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'guided-journal'));
    }
}
```

## Theming and Customization

### Template Hierarchy
1. Check theme directory for template
2. Fall back to plugin templates
3. Allow complete template overriding

### Custom CSS Integration
```php
function enqueue_custom_journal_styles() {
    // Allow theme to override default styles
    $custom_css = locate_template('guided-journal-custom.css');
    if ($custom_css) {
        wp_enqueue_style('guided-journal-custom', $custom_css);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_custom_journal_styles');
```

## Internationalization

### Translation Ready
- Use `__()` and `_e()` for translatable strings
- Text domain: `guided-journal`
- POT files provided for translation

## Troubleshooting and Logging

### Error Logging
```php
function log_journal_error($message, $context = []) {
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log('[Guided Journal] ' . $message . ' ' . print_r($context, true));
    }
}
```

## Version Compatibility

### WordPress Compatibility
- Minimum WordPress Version: 5.6
- Tested up to: 6.3
- PHP Minimum Version: 7.4

## Contributing

### Development Workflow
1. Fork the repository
2. Create feature branch
3. Write tests
4. Implement feature
5. Run tests
6. Submit pull request

### Coding Standards
- Follow WordPress Coding Standards
- Use PHPDoc comments
- Maintain consistent indentation
- Write clear, readable code

---

**Developer Version**: 2.0.0
**Last Updated**: January 2025
