@extends('layouts.email')

@section('content')
@php
    function getStatusText($status) {
        $statuses = [
            'pending' => 'В ожидании',
            'processing' => 'В обработке',
            'completed' => 'Завершён',
            'cancelled' => 'Отменён',
        ];
        return $statuses[$status] ?? $status;
    }
@endphp

<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;">
        📦 Обновление статуса заказа #{{ $order->id }}
    </h2>

    <p>Здравствуйте, @if($order->user){{ $order->user->name }}@elseклиент@endif!</p>

    <p>Статус вашего заказа был изменён:</p>

    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <table width="100%" cellpadding="5" cellspacing="0">
            <tr>
                <td width="40%" style="font-weight: bold;">Предыдущий статус:</td>
                <td width="60%" style="color: #e74c3c;">{{ getStatusText($oldStatus) }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Новый статус:</td>
                <td style="color: #27ae60; font-weight: bold;">{{ getStatusText($order->status) }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Сумма заказа:</td>
                <td>{{ number_format($order->total, 2, ',', ' ') }} ₽</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Дата создания:</td>
                <td>{{ $order->created_at->format('d.m.Y H:i') }}</td>
            </tr>
        </table>
    </div>

    @if($order->status === 'completed')
    <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <strong>✅ Ваш заказ выполнен!</strong><br>
        Спасибо за покупку. Мы надеемся, что вы останетесь довольны.
    </div>
    @elseif($order->status === 'cancelled')
    <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <strong>❌ Ваш заказ отменён.</strong><br>
        Если у вас есть вопросы, пожалуйста, свяжитесь с нами.
    </div>
    @elseif($order->status === 'processing')
    <div style="background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <strong>🔄 Ваш заказ в обработке.</strong><br>
        Мы начали готовить ваш заказ к отправке.
    </div>
    @endif

    <h3 style="color: #2c3e50; margin-top: 25px;">Состав заказа:</h3>

    <table width="100%" cellpadding="8" cellspacing="0" style="border: 1px solid #ddd; border-collapse: collapse;">
        <thead style="background: #3498db; color: white;">
            <tr>
                <th style="border: 1px solid #ddd; padding: 8px;">Товар</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Кол-во</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Цена</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Сумма</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px;">{{ $item->title }}</td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">{{ $item->qty }}</td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">{{ number_format($item->price, 2, ',', ' ') }} ₽</td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">{{ number_format($item->price * $item->qty, 2, ',', ' ') }} ₽</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px; text-align: right; font-size: 18px; font-weight: bold;">
        Итого: {{ number_format($order->total, 2, ',', ' ') }} ₽
    </div>

    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 14px; color: #666;">
        <p>Если у вас есть вопросы, ответьте на это письмо или свяжитесь с нами:</p>
        <p>📞 Телефон: +7 (XXX) XXX-XX-XX<br>
        📧 Email: support@yourshop.com</p>
    </div>
</div>
@endsection
