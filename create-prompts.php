<?php
// Find the WordPress load path
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
require_once($wp_load_path);

// Prompts array - extend this with more prompts for more variety
$prompts = [
    "Write about a moment today when you felt strong.",
    "What's one thing you want to change in your life and why?",
    "Describe a challenge you faced and how it changed you.",
    "What do you value most in relationships?",
    "What are three things you're grateful for today?",
    "What would you do if you knew you couldn't fail?",
    "Describe a person who has greatly influenced your life.",
    "What are your top priorities right now?",
    "Write about a fear you'd like to overcome.",
    "What makes you feel most alive?",
    "Describe your perfect day.",
    "What's a goal you're working towards?",
    "Write about a time you felt proud of yourself.",
    "What does success mean to you?",
    "Describe a place where you feel at peace.",
];

echo "Starting to create journal prompts...\n";

$created = 0;
$skipped = 0;
$errors = [];

for ($i = 1; $i <= 365; $i++) {
    // Check if prompt already exists
    $existing = get_page_by_path((string) $i, OBJECT, 'journal_prompt');

    if ($existing) {
        echo "Day {$i}: Skipped (already exists)\n";
        $skipped++;
        continue;
    }

    // Get random prompt
    $prompt = $prompts[array_rand($prompts)];

    // Create post
    $post_data = array(
        'post_title' => (string) $i,
        'post_content' => $prompt,
        'post_status' => 'publish',
        'post_type' => 'journal_prompt',
        'post_name' => (string) $i
    );

    $result = wp_insert_post($post_data, true);

    if (is_wp_error($result)) {
        echo "Day {$i}: Error - " . $result->get_error_message() . "\n";
        $errors[] = $i;
    } else {
        echo "Day {$i}: Created successfully\n";
        $created++;
    }

    // Small delay to prevent overwhelming the server
    usleep(100000); // 0.1 second delay
}

echo "\n=== Summary ===\n";
echo "Created: {$created}\n";
echo "Skipped: {$skipped}\n";
echo "Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "Days with errors: " . implode(', ', $errors) . "\n";
}