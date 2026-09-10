<?php

declare(strict_types=1);

return [
    'title' => '导入内容简报',
    'description' => '上传内容简报 CSV（最多 :max 行）。每一行都会成为一条草稿，由 AI 按你的品牌进行润色。稍后可在聊天中生成最终帖子。',
    'upload' => '上传 CSV',

    'empty' => [
        'title' => '暂无导入',
        'description' => '上传每行为一条内容简报的 CSV。',
    ],

    'status' => [
        'parsing' => '解析中',
        'preview_ready' => '待审阅',
        'processing' => '处理中',
        'completed' => '已完成',
        'failed' => '失败',
    ],

    'row_status' => [
        'valid' => '有效',
        'invalid' => '无效',
        'needs_review' => '需审阅',
        'created' => '已创建',
        'skipped' => '已跳过',
        'failed' => '失败',
    ],

    'summary' => '有效 :valid 条，跳过 :invalid 条',
    'progress' => '已处理 :processed / :total 条',
    'process' => '创建 :count 条草稿',
    'completed' => '已创建 :count 条草稿并标记为 Content Brief。',
    'view_drafts' => '查看草稿',
    'new_import' => '新建导入',

    'table' => [
        'content' => '简报',
        'status' => '状态',
        'no_content' => '无内容',
    ],

    'errors' => [
        'upload' => '无法上传该文件。请使用小于 5 MB 的 CSV。',
        'process' => '无法开始处理。请重试。',
    ],
];
