<?php

return [
    'article' => 'Article',
    'articles' => 'Articles',
    'comment' => 'Comment',
    'comments' => 'Comments & moderation',
    'former_reader' => 'Former reader',
    'navigation' => [
        'moderation_badge' => ':comments pending contributions · :reports pending reports',
    ],
    'sections' => [
        'content' => 'Bilingual article',
        'publishing' => 'Publishing',
        'discovery' => 'Discovery & source',
    ],
    'fields' => [
        'title' => 'Title', 'slug' => 'Slug', 'type' => 'Editorial label', 'read_minutes' => 'Reading time (minutes)',
        'summary' => 'Summary', 'lead' => 'Opening', 'sections' => 'Article sections', 'heading' => 'Heading',
        'paragraphs' => 'Paragraphs', 'points' => 'Key points', 'note' => 'Callout note', 'closing' => 'Closing',
        'seo_title' => 'SEO title', 'seo_description' => 'SEO description', 'key' => 'Stable article key',
        'published_at' => 'Publish date', 'modified_at' => 'Last meaningful revision', 'published' => 'Published',
        'featured' => 'Featured', 'image_path' => 'Article image', 'topics' => 'Topic keys', 'source_url' => 'Original source URL',
        'appreciations' => 'Appreciations', 'comments' => 'Comments', 'updated_at' => 'Updated', 'reader' => 'Reader',
        'comment_body' => 'Contribution', 'status' => 'Status', 'reply_to' => 'Reply to', 'reports' => 'Reports',
        'pending_reports' => 'Pending reports',
        'created_at' => 'Submitted', 'moderation_note' => 'Private moderation note', 'report_reasons' => 'Report reasons',
        'report_details' => 'Report context',
    ],
    'hints' => [
        'image_path' => 'Path inside public/, for example images/projects/atlas/example.webp.',
        'image_upload' => 'Upload a JPG, PNG, WebP, or AVIF image (maximum 8 MB). Responsive hero and card WebP versions are generated automatically.',
    ],
    'statuses' => ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'],
    'filters' => ['pending_reports' => 'Has pending reports'],
    'actions' => ['approve' => 'Approve', 'reject' => 'Reject', 'dismiss_reports' => 'Dismiss reports', 'view_article' => 'Open article'],
    'messages' => [
        'approved' => 'Contribution published.',
        'rejected' => 'Contribution rejected.',
        'reports_dismissed' => 'Pending reports dismissed. The contribution remains published.',
    ],
];
