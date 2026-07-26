<?php

return [
    'sections' => [
        'relationships' => 'Related content',
    ],
    'fields' => [
        'fit_signals' => 'Good-fit signals',
        'engagement_note' => 'Starting engagement',
        'seo_title' => 'SEO title',
        'seo_description' => 'SEO description',
        'related_projects' => 'Related projects',
        'related_articles' => 'Related articles',
        'publication_status' => 'Publication status',
    ],
    'hints' => [
        'fit_signals' => 'Add two to four clear signals before publishing.',
        'related_content' => 'Choose related content in the order it should appear on the public Service page.',
        'related_projects' => 'Choose related projects in the order they should appear on the public Service page.',
        'related_articles' => 'Choose related articles in the order they should appear on the public Service page.',
    ],
    'actions' => [
        'preview' => 'Preview',
        'publish' => 'Publish Service',
        'publish_description' => 'The Service will publish only after its Arabic and English content passes the complete publication check.',
        'unpublish' => 'Unpublish Service',
        'unpublish_description' => 'The Service will no longer be available on the public website.',
    ],
    'messages' => [
        'published' => 'The Service is now published.',
        'unpublished' => 'The Service is no longer public.',
    ],
    'statuses' => [
        'published' => 'Published',
        'draft' => 'Draft',
        'inactive' => 'Inactive',
    ],
    'locales' => [
        'ar' => 'Arabic',
        'en' => 'English',
    ],
    'publication' => [
        'status' => 'The Service must be active and no longer a draft before it can be published.',
        'deleted' => 'A deleted Service cannot be published.',
        'key' => 'Add a stable Service key before publishing.',
        'slug_required' => 'Complete the URL slug in :locale before publishing.',
        'slug_unique' => 'The URL slug in :locale is already used by another Service.',
        'required_field' => 'Complete :field in :locale before publishing.',
        'prohibited_content' => 'Remove placeholder text or the prohibited consultation phrase from :field in :locale.',
        'fit_signals' => 'Add between two and four complete good-fit signals in :locale.',
        'deliverables' => 'Add between one and five complete deliverables in :locale.',
        'related_project' => 'Every related Project must be public, publishable, and safe to name before this Service can publish.',
        'related_article' => 'Every related Article must be publicly available before this Service can publish.',
    ],
];
