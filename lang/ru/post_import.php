<?php

declare(strict_types=1);

return [
    'title' => 'Импорт брифов контента',
    'description' => 'Загрузите CSV с брифами контента (до :max строк). Каждая строка станет черновиком, который ИИ адаптирует под ваш бренд. Финальные посты сгенерируйте позже из чата.',
    'upload' => 'Загрузить CSV',

    'empty' => [
        'title' => 'Импорта пока нет',
        'description' => 'Загрузите CSV, где каждая строка — это один бриф контента.',
    ],

    'status' => [
        'parsing' => 'Разбор',
        'preview_ready' => 'Готово к проверке',
        'processing' => 'Обработка',
        'completed' => 'Завершено',
        'failed' => 'Ошибка',
    ],

    'row_status' => [
        'valid' => 'Верно',
        'invalid' => 'Неверно',
        'needs_review' => 'Требует проверки',
        'created' => 'Создано',
        'skipped' => 'Пропущено',
        'failed' => 'Ошибка',
    ],

    'summary' => ':valid верных, :invalid пропущено',
    'progress' => 'Обработано :processed из :total',
    'process' => 'Создать :count черновиков',
    'completed' => 'Создано :count черновиков с меткой Content Brief.',
    'view_drafts' => 'Посмотреть черновики',
    'new_import' => 'Новый импорт',

    'table' => [
        'content' => 'Бриф',
        'status' => 'Статус',
        'no_content' => 'Нет содержимого',
    ],

    'errors' => [
        'upload' => 'Не удалось загрузить файл. Используйте CSV до 5 МБ.',
        'process' => 'Не удалось начать обработку. Попробуйте снова.',
    ],
];
