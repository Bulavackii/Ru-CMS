<?php

return [
    // Автоматическое одобрение комментариев
    'auto_approve_users' => env('COMMENTS_AUTO_APPROVE_USERS', true),
    'auto_approve_guests' => env('COMMENTS_AUTO_APPROVE_GUESTS', false),
    
    // Слова-спам
    'spam_words' => [
        'casino', 'viagra', 'xxx', 'poker', 'loan', 'credit',
        'казино', 'виагра', 'кредит', 'займ',
    ],
    
    // Максимальная длина комментария
    'max_length' => 5000,
    'min_length' => 3,
    
    // Вложенность комментариев
    'max_depth' => 5,
    
    // Лимиты
    'rate_limit' => [
        'per_minute' => 5,
        'per_hour' => 20,
    ],
];

