<?php

return [
    'article' => 'مقال',
    'articles' => 'المقالات',
    'comment' => 'تعليق',
    'comments' => 'التعليقات والمراجعة',
    'former_reader' => 'قارئ سابق',
    'navigation' => [
        'moderation_badge' => ':comments مشاركات معلقة · :reports بلاغات معلقة',
    ],
    'sections' => [
        'content' => 'المقال باللغتين',
        'publishing' => 'النشر',
        'discovery' => 'الاكتشاف والمصدر',
    ],
    'fields' => [
        'title' => 'العنوان', 'slug' => 'الرابط', 'type' => 'التصنيف التحريري', 'read_minutes' => 'وقت القراءة بالدقائق',
        'summary' => 'الملخص', 'body' => 'نص المقال', 'lead' => 'المقدمة', 'sections' => 'أقسام المقال', 'heading' => 'العنوان الفرعي',
        'paragraphs' => 'الفقرات', 'points' => 'النقاط الرئيسية', 'note' => 'ملاحظة بارزة', 'closing' => 'الخاتمة',
        'seo_title' => 'عنوان محركات البحث', 'seo_description' => 'وصف محركات البحث', 'key' => 'المعرّف الثابت للمقال',
        'published_at' => 'تاريخ النشر', 'modified_at' => 'آخر مراجعة جوهرية', 'published' => 'منشور',
        'featured' => 'مميّز', 'image_path' => 'صورة المقال', 'image_alt' => 'النص البديل لصورة المقال', 'image_caption' => 'تعليق صورة المقال', 'topics' => 'مفاتيح الموضوعات', 'source_url' => 'رابط المصدر الأصلي',
        'appreciations' => 'التقديرات', 'comments' => 'التعليقات', 'updated_at' => 'آخر تحديث', 'reader' => 'القارئ',
        'comment_body' => 'المشاركة', 'status' => 'الحالة', 'reply_to' => 'رد على', 'reports' => 'البلاغات',
        'pending_reports' => 'البلاغات المعلقة',
        'created_at' => 'تاريخ الإرسال', 'moderation_note' => 'ملاحظة مراجعة خاصة', 'report_reasons' => 'أسباب البلاغ',
        'report_details' => 'تفاصيل البلاغ',
    ],
    'hints' => [
        'image_path' => 'المسار داخل public/، مثال: images/projects/atlas/example.webp.',
        'image_upload' => 'ارفع صورة JPG أو PNG أو WebP أو AVIF (بحد أقصى 8 م.ب). تُنشأ تلقائياً نسخ WebP متجاوبة للعرض الرئيسي والبطاقات.',
        'body' => 'استخدم عناوين H2 وH3 واضحة، وفقرات قصيرة، وقوائم حين تجعل الفكرة أسهل للمسح البصري. كل صورة مرفوعة تحتاج نصاً بديلاً مفيداً.',
        'image_alt' => 'صِف الصورة ووظيفتها، ولا تكرر عنوان المقال إلا إذا كان يصف محتواها فعلاً.',
        'read_minutes' => 'يُحسب تلقائياً من نسخة هذه اللغة عند حفظ المقال.',
        'published' => 'استخدم إجراء النشر حتى لا يمكن تجاوز فحوص المحتوى الثنائي والوسائط وإتاحة الوصول.',
    ],
    'statuses' => ['pending' => 'قيد المراجعة', 'approved' => 'منشور', 'rejected' => 'مرفوض'],
    'filters' => ['pending_reports' => 'توجد بلاغات معلقة'],
    'actions' => ['approve' => 'نشر', 'reject' => 'رفض', 'dismiss_reports' => 'رفض البلاغات', 'view_article' => 'فتح المقال'],
    'messages' => [
        'approved' => 'نُشرت المشاركة.',
        'rejected' => 'رُفضت المشاركة.',
        'reports_dismissed' => 'رُفضت البلاغات المعلقة وبقيت المشاركة منشورة.',
    ],
];
