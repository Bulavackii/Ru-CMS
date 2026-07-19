<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Статус вашего отзыва</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #4a5568;">Статус вашего отзыва</h2>
        
        <p>Здравствуйте!</p>
        
        <p>
            @if($action === 'approve')
                Ваш отзыв был <strong style="color: #48bb78;">одобрен</strong> и опубликован на сайте.
            @else
                К сожалению, ваш отзыв был <strong style="color: #f56565;">отклонен</strong> модератором.
            @endif
        </p>
        
        @if(isset($review))
        <div style="background: #f7fafc; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p><strong>Ваш отзыв:</strong></p>
            @if($review->title)
                <p><strong>{{ $review->title }}</strong></p>
            @endif
            <p>{{ Str::limit($review->content, 200) }}</p>
            <p>Оценка: {{ $review->rating }}⭐</p>
        </div>
        @endif
        
        <p>Спасибо за ваш отзыв!</p>
        
        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;">
        <p style="font-size: 12px; color: #718096;">
            Это автоматическое уведомление. Пожалуйста, не отвечайте на это письмо.
        </p>
    </div>
</body>
</html>




