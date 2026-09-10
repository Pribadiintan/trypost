<?php

declare(strict_types=1);

return [
    'title' => '콘텐츠 브리프 가져오기',
    'description' => '콘텐츠 브리프 CSV를 업로드하세요(최대 :max행). 각 행은 AI가 브랜드에 맞게 다듬는 초안이 됩니다. 최종 게시물은 나중에 채팅에서 생성하세요.',
    'upload' => 'CSV 업로드',

    'empty' => [
        'title' => '아직 가져오기가 없습니다',
        'description' => '각 행이 하나의 콘텐츠 브리프인 CSV를 업로드하세요.',
    ],

    'status' => [
        'parsing' => '분석 중',
        'preview_ready' => '검토 준비됨',
        'processing' => '처리 중',
        'completed' => '완료',
        'failed' => '실패',
    ],

    'row_status' => [
        'valid' => '유효',
        'invalid' => '유효하지 않음',
        'needs_review' => '검토 필요',
        'created' => '생성됨',
        'skipped' => '건너뜀',
        'failed' => '실패',
    ],

    'summary' => '유효 :valid개, 건너뜀 :invalid개',
    'progress' => ':total개 중 :processed개 처리됨',
    'process' => '초안 :count개 만들기',
    'completed' => '초안 :count개를 만들고 Content Brief 라벨을 지정했습니다.',
    'view_drafts' => '초안 보기',
    'new_import' => '새 가져오기',

    'table' => [
        'content' => '브리프',
        'status' => '상태',
        'no_content' => '콘텐츠 없음',
    ],

    'errors' => [
        'upload' => '이 파일을 업로드할 수 없습니다. 5MB 미만의 CSV를 사용하세요.',
        'process' => '처리를 시작할 수 없습니다. 다시 시도하세요.',
    ],
];
