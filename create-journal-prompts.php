<?php
// Ensure this script is being run in WordPress context
if (!defined('ABSPATH')) {
    require_once('wp-load.php');
}

// Verify user has permission to create posts
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

function create_journal_prompts()
{
    $created = 0;
    $skipped = 0;
    $errors = 0;

    // Sample prompts - you can customize these or add more variety
    $sample_prompts = [
        "What are you most grateful for today?",
        "What was the biggest challenge you faced recently and how did you handle it?",
        "Write about a goal you're working towards and your next steps.",
        "Reflect on a relationship that's important to you.",
        "What made you smile today?"
    ];

    for ($i = 1; $i <= 365; $i++) {
        // Check if a prompt with this number already exists
        $existing_prompt = get_page_by_path($i, OBJECT, 'journal_prompt');

        if ($existing_prompt) {
            $skipped++;
            continue;
        }

        // Get a random prompt from the sample list
        $prompt_content = $sample_prompts[array_rand($sample_prompts)];

        // Create the post data
        $post_data = array(
            'post_title' => (string) $i,
            'post_content' => $prompt_content,
            'post_status' => 'publish',
            'post_type' => 'journal_prompt',
            'post_name' => (string) $i,
            'post_author' => get_current_user_id()
        );

        // Insert the post
        $result = wp_insert_post($post_data, true);

        if (is_wp_error($result)) {
            $errors++;
            error_log("Error creating prompt {$i}: " . $result->get_error_message());
        } else {
            $created++;
        }

        // Add a small delay to prevent overwhelming the server
        usleep(100000); // 0.1 second delay
    }

    return [
        'created' => $created,
        'skipped' => $skipped,
        'errors' => $errors
    ];
}

// Run the function and get results
$results = create_journal_prompts();

// Output results
echo "Journal prompts creation completed:\n";
echo "Created: {$results['created']}\n";
echo "Skipped (already existed): {$results['skipped']}\n";
echo "Errors: {$results['errors']}\n";