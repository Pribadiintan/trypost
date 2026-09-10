<?php

declare(strict_types=1);

return [
    'title' => 'استيراد موجزات المحتوى',
    'description' => 'ارفع ملف CSV يحتوي على موجزات المحتوى (حتى :max صف). يصبح كل صف مسودة يحسّنها الذكاء الاصطناعي وفق علامتك التجارية. أنشئ المنشورات النهائية لاحقًا من الدردشة.',
    'upload' => 'رفع CSV',

    'empty' => [
        'title' => 'لا يوجد استيراد بعد',
        'description' => 'ارفع ملف CSV حيث يمثّل كل صف موجز محتوى واحدًا.',
    ],

    'status' => [
        'parsing' => 'جارٍ التحليل',
        'preview_ready' => 'جاهز للمراجعة',
        'processing' => 'قيد المعالجة',
        'completed' => 'اكتمل',
        'failed' => 'فشل',
    ],

    'row_status' => [
        'valid' => 'صالح',
        'invalid' => 'غير صالح',
        'needs_review' => 'يحتاج مراجعة',
        'created' => 'تم الإنشاء',
        'skipped' => 'تم التخطي',
        'failed' => 'فشل',
    ],

    'summary' => ':valid صالح، :invalid تم تخطيه',
    'progress' => 'تمت معالجة :processed من :total',
    'process' => 'إنشاء :count مسودة',
    'completed' => 'تم إنشاء :count مسودة ووسمها بـ Content Brief.',
    'view_drafts' => 'عرض المسودات',
    'new_import' => 'استيراد جديد',

    'table' => [
        'content' => 'الموجز',
        'status' => 'الحالة',
        'no_content' => 'لا يوجد محتوى',
    ],

    'errors' => [
        'upload' => 'تعذّر رفع هذا الملف. استخدم ملف CSV أقل من 5 ميغابايت.',
        'process' => 'تعذّر بدء المعالجة. حاول مرة أخرى.',
    ],
];
