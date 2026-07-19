@extends('layouts.email')

@section('content')
<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;">
        ✅ Заказ #{{ $order->id }} успешно оформлен
    </h2>

    <p>Здравствуйте, @if($order->user){{ $order->user->name }}@elseif($order->customer_name){{ $order->customer_name }}@elseклиент@endif!</p>

    <p>Благодарим вас за заказ! Мы получили вашу заявку и в ближайшее время обработаем её.</p>

    <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <strong>📦 Номер заказа: #{{ $order->id }}</strong><br>
        <strong>💰 Сумма заказа: {{ number_format($order->total, 2, ',', ' ') }} ₽</strong><br>
        <strong>📅 Дата создания: {{ $order->created_at->format('d.m.Y H:i') }}</strong>
    </div>

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

    <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
        <table width="100%" cellpadding="5" cellspacing="0">
            <tr>
                <td width="50%" style="font-weight: bold;">Сумма товаров:</td>
                <td width="50%" style="text-align: right;">{{ number_format($order->items_total, 2, ',', ' ') }} ₽</td>
            </tr>
            @if($order->delivery_price > 0)
            <tr>
                <td style="font-weight: bold;">Доставка:</td>
                <td style="text-align: right;">{{ number_format($order->delivery_price, 2, ',', ' ') }} ₽</td>
            </tr>
            @endif
            @if($order->commission > 0)
            <tr>
                <td style="font-weight: bold;">Комиссия:</td>
                <td style="text-align: right;">{{ number_format($order->commission, 2, ',', ' ') }} ₽</td>
            </tr>
            @endif
            <tr style="border-top: 2px solid #333;">
                <td style="font-weight: bold; font-size: 18px;">Итого:</td>
                <td style="text-align: right; font-size: 18px; font-weight: bold;">{{ number_format($order->total, 2, ',', ' ') }} ₽</td>
            </tr>
        </table>
    </div>

    @if($order->paymentMethod)
    <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
        <strong>💳 Способ оплаты:</strong> {{ $order->paymentMethod->title }}
    </div>
    @endif

    @if($order->deliveryMethod)
    <div style="margin-top: 10px; padding: 15px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px;">
        <strong>🚚 Способ доставки:</strong> {{ $order->deliveryMethod->title }}
    </div>
    @endif

    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 14px; color: #666;">
        <p>Мы свяжемся с вами в ближайшее время для подтверждения заказа.</p>
        <p>Если у вас есть вопросы, ответьте на это письмо или свяжитесь с нами:</p>
        <p>📞 Телефон: +7 (XXX) XXX-XX-XX<br>
        📧 Email: support@yourshop.com</p>
    </div>
</div>
@endsection

