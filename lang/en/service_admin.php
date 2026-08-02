<?php

return [
    'fields' => [
        'name' => 'Name',
        'summary' => 'Summary',
        'problem' => 'Problem',
        'approach' => 'Approach',
        'deliverables' => 'Deliverables',
        'result' => 'Result',
        'fit_signals' => 'Good-fit signals',
        'engagement_note' => 'Starting the engagement',
    ],
    'locales' => [
        'ar' => 'Arabic',
        'en' => 'English',
    ],
    'hints' => [
        'fit_signals' => 'One clear signal per line. Add two to four before publishing.',
    ],
    'status' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'unpublished' => 'Unpublished',
    ],
    'actions' => [
        'publish' => 'Publish service',
        'unpublish' => 'Unpublish service',
    ],
    'publication' => [
        'status' => 'The service must be active and out of draft before it can be published.',
        'deleted' => 'A deleted service cannot be published.',
        'key' => 'A stable service key is required before publishing.',
        'required_field' => ':field is required in :locale.',
        'prohibited_content' => ':field in :locale contains placeholder or prohibited public content.',
        'fit_signals' => 'Provide between two and four complete good-fit signals in :locale.',
        'deliverables' => 'Provide between one and five complete deliverables in :locale.',
    ],
];
