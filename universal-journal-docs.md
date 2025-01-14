# Guided Journal WordPress Plugin Documentation

## Features List

- Personal journaling system integrated into WordPress
- 📝 Daily writing prompts to inspire reflection
- 📊 Progress tracking and writing statistics
- 🎨 Customizable colors and typography
- 📱 Responsive design for all devices
- ⚡ Real-time autosaving
- 🔒 Private, user-specific entries
- 📈 Writing streak tracking
- 🎯 Progress visualization
- ⌨️ Rich text editor with formatting options
- 🔄 Automatic word count tracking
- 🎉 Achievement system for consistent writing
- 🎨 Custom theme integration
- 👥 Role-based access control
- 💾 Secure data storage
- 🔍 Entry search and organization

## Getting Started Guide

### Installation

1. Download the Guided Journal plugin ZIP file
2. Log in to your WordPress dashboard
3. Navigate to Plugins > Add New
4. Click "Upload Plugin" and choose the downloaded ZIP file
5. Click "Install Now"
6. After installation completes, click "Activate"

### Initial Setup

1. **Configure Colors**
   - Go to Journal Settings > Typography & Colors
   - Customize the color scheme to match your site
   - Click "Save Settings" to apply changes

2. **Set Up Prompts**
   - Navigate to Journal Prompts in the dashboard
   - Add your first prompt using "Add New"
   - Set the title as a number (e.g., "1" for Day 1)
   - Enter the prompt text in the content area
   - Publish to make it available

3. **User Access**
   - Users need an account to access the journal
   - New users automatically get the "Journal User" role
   - Existing users can be assigned the role manually

### Basic Usage

1. **Accessing the Journal**
   - Users can access their journal via the front-end
   - Navigate to the journal grid view
   - Click on any prompt to start writing

2. **Writing Entries**
   - Use the rich text editor to write
   - Format text using the toolbar options
   - Entries auto-save every minute
   - Click "Save Entry" to save manually

3. **Tracking Progress**
   - View statistics on the dashboard
   - Track writing streaks
   - Monitor completion percentage
   - See total words written

## Technical Documentation

### Database Schema

The plugin creates three custom tables:

1. `{prefix}journal_entries`
   - id (bigint)
   - user_id (bigint)
   - day_number (int)
   - entry_text (longtext)
   - created_at (datetime)
   - updated_at (datetime)

2. `{prefix}journal_stats`
   - id (bigint)
   - user_id (bigint)
   - day_number (int)
   - word_count (int)
   - time_spent (int)
   - last_modified (datetime)

3. `{prefix}journal_streaks`
   - id (bigint)
   - user_id (bigint)
   - streak_start (date)
   - streak_end (date)
   - streak_days (int)

### Custom Post Types

- `journal_prompt`
  - Public: true
  - Hierarchical: false
  - Supports: title, editor, author, thumbnail, excerpt, revisions
  - Rewrite: slug 'journal-prompts'

### Roles and Capabilities

1. **Journal User**
   - read
   - level_0
   - view_journal

2. **Administrator**
   - All default capabilities
   - view_journal
   - manage_journal

### Hooks and Filters

```php
// Save entry action
do_action('guided_journal_save_entry', $entry_data);

// Get entry filter
apply_filters('guided_journal_entry_content', $content, $user_id, $day);

// Stats calculation filter
apply_filters('guided_journal_calculate_stats', $stats, $user_id);
```

### CSS Variables

```css
:root {
  --gj-background
  --gj-card-background
  --gj-text
  --gj-accent
  --gj-container-background
  --gj-completed
}
```

## FAQ

**Q: Can users see each other's entries?**
A: No, each user can only see their own entries. The plugin maintains strict privacy by user ID.

**Q: How does auto-saving work?**
A: Entries auto-save every 60 seconds of inactivity. Users can also manually save using Ctrl/Cmd + S or the Save button.

**Q: Can I customize the prompts?**
A: Yes, administrators can add, edit, or remove prompts through the WordPress dashboard.

**Q: Is the data secure?**
A: Yes, entries are stored in a secure custom table and are only accessible to the author and administrators.

**Q: Can I export my journal entries?**
A: Currently, entries can be exported through the WordPress database backup. A dedicated export feature is planned.

**Q: What happens if I uninstall the plugin?**
A: By default, all data is preserved. You can enable data deletion on uninstall in the settings.

**Q: Does it work with any theme?**
A: Yes, the plugin is designed to work with any WordPress theme and includes customization options.

**Q: Can I change the styling?**
A: Yes, you can customize colors and typography through the settings panel.

## User Manual

### Dashboard Overview

The journal dashboard provides:
- Total entries written
- Current writing streak
- Total words written
- Overall completion percentage
- Visual progress bar
- Prompt grid with completion status

### Writing Interface

The writing interface includes:
1. Daily prompt display
2. Rich text editor
3. Formatting tools
4. Auto-save indicator
5. Navigation buttons
6. Save button

### Navigation

- Grid View: Shows all prompts and completion status
- Entry View: Writing interface for individual prompts
- Settings: Color and typography customization
- Progress tracking: Stats and achievements

### Text Editor Features

- Bold, italic, underline formatting
- Bullet and numbered lists
- Link insertion
- Undo/redo
- Copy/paste support
- Media embedding

### Statistics and Tracking

The plugin tracks:
1. Words written per entry
2. Total words written
3. Writing streaks
4. Completion percentage
5. Time spent writing
6. Entry dates and times

### Settings and Customization

Administrators can customize:
1. Color scheme
   - Background colors
   - Text colors
   - Accent colors
   - Progress indicators

2. Typography
   - Heading font
   - Body font
   - Font weights
   - Available Google Fonts

3. System Settings
   - Auto-save interval
   - Minimum word count
   - Stats display options
   - Data handling preferences

### Best Practices

1. **Regular Backups**
   - Keep regular database backups
   - Export prompts periodically
   - Document customizations

2. **Performance**
   - Optimize media uploads
   - Keep prompt count manageable
   - Monitor database size

3. **Security**
   - Use strong passwords
   - Keep WordPress updated
   - Limit admin access
   - Regular security audits

4. **Content Management**
   - Plan prompts in advance
   - Test prompts before publishing
   - Maintain consistent numbering
   - Review user feedback

### Troubleshooting

Common issues and solutions:

1. **Saving Issues**
   - Check user permissions
   - Verify database connection
   - Clear browser cache
   - Check for JavaScript errors

2. **Display Problems**
   - Review theme compatibility
   - Check for CSS conflicts
   - Verify responsive settings
   - Clear cache

3. **Performance Issues**
   - Optimize database
   - Check server resources
   - Monitor entry sizes
   - Review active plugins

4. **Access Problems**
   - Verify user roles
   - Check login status
   - Review permission settings
   - Clear browser cookies

# Export Feature Documentation

## Overview
The Guided Journal plugin includes a robust export feature that allows users to download their journal entries in CSV format. This feature maintains privacy by only allowing users to export their own entries.

## Features
- Export all journal entries to CSV
- UTF-8 encoding with BOM for Excel compatibility
- Includes prompts, entries, word counts, and timestamps
- Sanitized filenames and content
- Progress indicator during export
- Error handling and user feedback
- Browser compatibility (including IE11 and Edge)

## Technical Implementation

### Frontend
The export feature is implemented using:
- AJAX for asynchronous file download
- Blob handling for large files
- Progress indicators
- Error notifications
- Cross-browser compatibility measures

### Backend
The export process:
1. Validates user authentication and nonce
2. Retrieves entries with associated prompts and stats
3. Generates CSV with proper encoding
4. Handles memory efficiently for large exports
5. Provides appropriate headers for file download

### Data Format
The exported CSV includes:
- Day number
- Original prompt
- Entry text (stripped of HTML)
- Word count
- Creation date and time

### Security Measures
- Nonce verification
- User authentication
- Content sanitization
- Safe file handling
- Privacy protection

## Usage

### For Users
1. Navigate to the journal grid or entry view
2. Click the "Export Entries" button
3. Choose save location when prompted
4. Open the CSV file with a spreadsheet application

### For Developers
The export functionality can be extended using these filters:

```php
// Modify export data
add_filter('guided_journal_export_data', function($entries, $user_id) {
    // Modify $entries array
    return $entries;
}, 10, 2);

// Customize CSV headers
add_filter('guided_journal_export_headers', function($headers) {
    // Modify $headers array
    return $headers;
});
```

## Troubleshooting

### Common Issues
1. **Download doesn't start**
   - Check browser download settings
   - Verify user permissions
   - Clear browser cache

2. **Corrupted CSV**
   - Ensure proper encoding (UTF-8)
   - Check for special characters
   - Verify Excel compatibility

3. **Missing Data**
   - Confirm entries exist
   - Check database connectivity
   - Verify query permissions

### Error Messages
- "No entries found to export": User has no journal entries
- "Invalid security token": Session expired or invalid nonce
- "Please log in to export entries": User authentication required

## Best Practices
1. Regular exports for backup
2. Check exported data integrity
3. Monitor large exports
4. Handle sensitive information appropriately

## Future Enhancements
- Multiple export formats (PDF, DOC)
- Selective entry export
- Progress tracking for large exports
- Advanced filtering options
- Custom export templates

### Support and Resources

- Plugin Homepage: [URL]
- Documentation: [URL]
- Support Forum: [URL]
- Bug Reports: [URL]
- Feature Requests: [URL]

For direct support, contact: support@[your-domain].com

---

© 2025 Your Company Name. All rights reserved.
