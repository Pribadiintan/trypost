<?php

declare(strict_types=1);

return [
    'title' => 'コンテンツブリーフをインポート',
    'description' => 'コンテンツブリーフのCSVをアップロードします（最大 :max 行）。各行がブランドに合わせてAIが調整する下書きになります。最終的な投稿は後でチャットから生成できます。',
    'upload' => 'CSVをアップロード',

    'empty' => [
        'title' => 'まだインポートがありません',
        'description' => '各行が1つのコンテンツブリーフになっているCSVをアップロードしてください。',
    ],

    'status' => [
        'parsing' => '解析中',
        'preview_ready' => '確認待ち',
        'processing' => '処理中',
        'completed' => '完了',
        'failed' => '失敗',
    ],

    'row_status' => [
        'valid' => '有効',
        'invalid' => '無効',
        'needs_review' => '要確認',
        'created' => '作成済み',
        'skipped' => 'スキップ',
        'failed' => '失敗',
    ],

    'summary' => '有効 :valid 件、スキップ :invalid 件',
    'progress' => ':total 件中 :processed 件を処理',
    'process' => ':count 件の下書きを作成',
    'completed' => ':count 件の下書きを作成し、Content Brief のラベルを付けました。',
    'view_drafts' => '下書きを表示',
    'new_import' => '新しいインポート',

    'table' => [
        'content' => 'ブリーフ',
        'status' => 'ステータス',
        'no_content' => 'コンテンツなし',
    ],

    'errors' => [
        'upload' => 'このファイルをアップロードできませんでした。5 MB未満のCSVを使用してください。',
        'process' => '処理を開始できませんでした。もう一度お試しください。',
    ],
];
