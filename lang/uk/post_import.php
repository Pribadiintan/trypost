<?php

declare(strict_types=1);

return [
    'title' => 'Імпорт брифів контенту',
    'description' => 'Завантажте CSV із брифами контенту (до :max рядків). Кожен рядок стає чернеткою, яку ШІ адаптує під ваш бренд. Згенеруйте фінальні дописи пізніше з чату.',
    'upload' => 'Завантажити CSV',

    'empty' => [
        'title' => 'Імпорту ще немає',
        'description' => 'Завантажте CSV, де кожен рядок — це один бриф контенту.',
    ],

    'status' => [
        'parsing' => 'Розбір',
        'preview_ready' => 'Готово до перегляду',
        'processing' => 'Обробка',
        'completed' => 'Завершено',
        'failed' => 'Помилка',
    ],

    'row_status' => [
        'valid' => 'Дійсний',
        'invalid' => 'Недійсний',
        'needs_review' => 'Потрібен перегляд',
        'created' => 'Створено',
        'skipped' => 'Пропущено',
        'failed' => 'Помилка',
    ],

    'summary' => ':valid дійсних, :invalid пропущено',
    'progress' => 'Оброблено :processed з :total',
    'process' => 'Створити :count чернеток',
    'completed' => 'Створено :count чернеток із міткою Content Brief.',
    'view_drafts' => 'Переглянути чернетки',
    'new_import' => 'Новий імпорт',

    'table' => [
        'content' => 'Бриф',
        'status' => 'Статус',
        'no_content' => 'Немає вмісту',
    ],

    'errors' => [
        'upload' => 'Не вдалося завантажити файл. Використайте CSV до 5 МБ.',
        'process' => 'Не вдалося почати обробку. Спробуйте ще раз.',
    ],
];
