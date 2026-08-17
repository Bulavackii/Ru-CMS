<?php

namespace Modules\Payments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Modules\Delivery\Models\DeliveryMethod;
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;

class Order extends Model
{
    use HasFactory;


    /**
     * 🧾 Массово заполняемые поля
     */
    protected $fillable = [
        'user_id',             // 👤 Пользователь (если авторизован)
        'payment_method_id',   // 💳 Метод оплаты
        'payment_id',          // 💳 ID платежа в платежной системе
        'delivery_method_id',  // 🚚 Метод доставки
        'total',               // 💰 Общая сумма заказа (с доставкой и комиссией)
        'items_total',         // 💰 Сумма товаров
        'delivery_price',      // 💰 Стоимость доставки
        'commission',          // 💰 Комиссия платежной системы
        'status',              // 📦 Статус заказа (pending, completed и т.д.)
        'is_new',              // 🆕 Новый заказ (для админки)
        'customer_name',       // 📛 Имя клиента
        'customer_phone',      // 📞 Телефон клиента
        'customer_email',      // 📧 Email клиента
        'customer_address',    // 🏠 Адрес доставки
        'customer_city',       // 🏙️ Город — по нему служба решает, возит ли туда
        'total_weight',        // ⚖️ Вес заказа, кг (пусто = не взвешивали)
        'comment',             // 💬 Комментарий к заказу
        'stock_returned_at',   // 🔄 Когда остатки по заказу вернули на склад
        'cancel_reason',       // ❌ Почему отменён (напр. не оплачен в срок)
    ];

    /**
     * 🧠 Преобразования типов
     */
    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'items_total' => 'decimal:2',
            'delivery_price' => 'decimal:2',
            'commission' => 'decimal:2',
            'is_new' => 'boolean',
            'stock_returned_at' => 'datetime',
        ];
    }

    /**
     * Вернуть на склад товары этого заказа. Ровно ОДИН раз.
     *
     * Возврат жил только в удалении заказа: отменённый заказ списывал товар
     * навсегда, и склад молча худел с каждой отменой. Теперь его зовут и
     * отмена, и удаление — а отметка `stock_returned_at` не даёт вернуть
     * дважды, когда заказ сначала отменили, а потом удалили.
     *
     * ⚠️ Остаток NULL — это «не считаем», а не «ноль»: у услуг и материалов
     * без склада поле пустое, и наращивать там нечего.
     *
     * @return bool Возврат выполнен именно сейчас
     */
    public function returnStockOnce(): bool
    {
        if ($this->stock_returned_at !== null) {
            return false;
        }

        // ⚠️ Строки заказа берутся ЗАПРОСОМ, а не из `$this->items`.
        //
        // Связь могла быть подгружена раньше, чем появились строки: событие
        // `created` делает `load(['items', …])` для писем, и на этой модели
        // `items` навсегда остаётся пустой коллекцией. Заказ, созданный и
        // удалённый в одном запросе, не возвращал на склад НИЧЕГО — молча и
        // без ошибки.
        foreach ($this->items()->get() as $item) {
            $product = \Modules\News\Models\News::find($item->product_id);

            if ($product && $product->stock !== null) {
                $product->increment('stock', $item->qty);
            }
        }

        // Отметка ставится даже когда возвращать было нечего: заказ обработан,
        // и повторный проход по нему ничего изменить не должен.
        //
        // ⚠️ Тихо (`saveQuietly`), без событий. Возврат зовётся ИЗ обработчика
        // `updated`, а `syncOriginal()` выполняется уже после него — значит,
        // внутри обработчика `wasChanged('status')` ещё истинно. Обычный
        // `save()` поднимал `updated` второй раз, событие смены статуса
        // уходило дважды, и покупатель получал два письма об одной отмене.
        $this->forceFill(['stock_returned_at' => now()])->saveQuietly();

        return true;
    }

    /**
     * Снова списать товар со склада, если заказ ожил после отмены.
     *
     * 🔴 Зачем это нужно. Неоплаченный заказ отменяется по сроку (10 минут) и
     * возвращает товар в продажу. Но деньги за него могут прийти ПОЗЖЕ:
     * покупатель дооплатил на одиннадцатой минуте, банк прислал уведомление с
     * задержкой, владелец вернул заказ в работу руками. Без этого метода
     * получалось так: заказ снова живой, товар за ним числится — а на складе
     * он лежит как свободный, и его продают второй раз.
     *
     * Отметка `stock_returned_at` читается как «остаток СЕЙЧАС возвращён»:
     * пока она стоит, товар свободен; сняли — товар снова за заказом. Метод
     * зеркален returnStockOnce(), поэтому пара «отменить → вернуть в работу»
     * не сдвигает склад ни на единицу, сколько раз её ни повторяй.
     *
     * ⚠️ Остаток может уйти в минус — и это правильно. Значит, товар за это
     * время успели продать, и владелец обязан об этом узнать: минус виден в
     * панели и в журнале, а тихое обнуление скрыло бы недостачу.
     *
     * @return bool Списание выполнено именно сейчас
     */
    public function takeStockBackOnce(): bool
    {
        if ($this->stock_returned_at === null) {
            return false;   // товар и не возвращали — забирать нечего
        }

        // Запросом, а не из кеша связи — по той же причине, что в returnStockOnce().
        foreach ($this->items()->get() as $item) {
            $product = \Modules\News\Models\News::find($item->product_id);

            if ($product && $product->stock !== null) {
                $product->decrement('stock', $item->qty);

                if ($product->fresh()->stock < 0) {
                    \Illuminate\Support\Facades\Log::warning(
                        'Остаток ушёл в минус: заказ ожил после отмены, а товар уже продан',
                        ['order_id' => $this->id, 'product_id' => $product->id]
                    );
                }
            }
        }

        // Тихо, по той же причине, что и в returnStockOnce().
        $this->forceFill(['stock_returned_at' => null])->saveQuietly();

        return true;
    }

    /**
     * 📦 Элементы заказа (товары)
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * 💳 Метод оплаты
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * 👤 Пользователь, оформивший заказ
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 🚚 Метод доставки
     */
    public function deliveryMethod(): BelongsTo
    {
        return $this->belongsTo(DeliveryMethod::class);
    }

    /**
     * 📊 Форматирование общей суммы
     */
    public function getFormattedTotalAttribute()
    {
        return number_format($this->total, 2, ',', ' ') . ' ₽';
    }

    /**
     * 📊 Форматирование суммы товаров
     */
    public function getFormattedItemsTotalAttribute()
    {
        return number_format($this->items_total, 2, ',', ' ') . ' ₽';
    }

    /**
     * 📊 Форматирование стоимости доставки
     */
    public function getFormattedDeliveryPriceAttribute()
    {
        return number_format($this->delivery_price, 2, ',', ' ') . ' ₽';
    }

    /**
     * 📊 Форматирование комиссии
     */
    public function getFormattedCommissionAttribute()
    {
        return number_format($this->commission, 2, ',', ' ') . ' ₽';
    }

    /**
     * 🎨 Статус заказа с цветом
     */
    public function getStatusBadgeAttribute()
    {
        $statuses = [
            'pending' => '<span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded">⏳ В ожидании</span>',
            'processing' => '<span class="bg-blue-100 text-blue-800 px-2 py-1 rounded">🔄 В обработке</span>',
            'completed' => '<span class="bg-green-100 text-green-800 px-2 py-1 rounded">✅ Завершен</span>',
            'cancelled' => '<span class="bg-red-100 text-red-800 px-2 py-1 rounded">❌ Отменен</span>',
        ];
        return $statuses[$this->status] ?? '<span class="bg-gray-100 text-gray-800 px-2 py-1 rounded">❓ Неизвестно</span>';
    }

    /**
     * Boot метод для событий модели
     */
    /**
     * Статусы заказа — единственный источник на весь модуль.
     *
     * Раньше набор был описан трижды (обе вьюхи и правило валидации) и
     * успел разойтись: панель предлагала «Оплачен», а валидация его не
     * принимала — сохранить такой статус было невозможно.
     *
     * Значения — идентификаторы в базе, переводить их нельзя; подписи
     * лежат в admin.orders.st_<статус>.
     */
    /**
     * Набор статусов заказа — ЕДИНСТВЕННЫЙ источник.
     *
     * ⚠️ `new` — это заказ, заведённый в панели руками (например, по
     * телефону). Он ДОЛЖЕН быть в списке: без него подпись показывалась
     * сырым кодом «new», а отборы по статусу такой заказ не находили вовсе.
     *
     * ⚠️ И он НЕ равен `pending`. `pending` ставит корзина, и именно его
     * забирает автоотмена по сроку: неоплаченный заказ из магазина живёт
     * десять минут. Заказ, о котором владелец договорился с покупателем
     * лично, отменять по таймеру нельзя — поэтому статус отдельный.
     */
    public const STATUSES = ['new', 'pending', 'paid', 'processing', 'completed', 'cancelled'];

    /**
     * Подписи статусов для выпадающих списков и плашек.
     *
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        $labels = [];

        foreach (self::STATUSES as $status) {
            $labels[$status] = __('admin.orders.st_' . $status);
        }

        return $labels;
    }

    protected static function boot(): void
    {
        parent::boot();

        // Событие создания заказа
        static::created(function ($order) {
            // Загружаем связи для уведомлений
            $order->load(['items', 'user', 'paymentMethod', 'deliveryMethod']);
            event(new OrderCreated($order));
        });

        // Удаление заказа возвращает товар в продажу — по тому же принципу,
        // что и отмена: возврат привязан к ФАКТУ, а не к пути в коде. Раньше
        // это жило только в карточке заказа, и удаление из любого другого
        // места (массовое действие, чистка, своя интеграция) оставляло товар
        // списанным навсегда.
        //
        // ⚠️ Именно `deleting`, а не `deleted`: строки заказа нужны, чтобы
        // знать, что и сколько возвращать, а каскад их уже унесёт.
        static::deleting(function ($order) {
            $order->returnStockOnce();
        });

        // Событие изменения статуса.
        //
        // ⚠️ Прежний вариант складывал старый статус в `$order->old_status`.
        // У модели нет такого свойства, поэтому присваивание заводило
        // АТРИБУТ, и Eloquent добавлял его в UPDATE: «столбец old_status
        // не существует», 500 на КАЖДОЙ смене статуса из панели.
        //
        // Ничего запоминать и не нужно: syncOriginal() выполняется уже
        // после события updated, так что getOriginal() здесь ещё отдаёт
        // значение до сохранения, а wasChanged() — факт изменения.
        static::updated(function ($order) {
            if (! $order->wasChanged('status')) {
                return;
            }

            // ⚠️ Прежний статус снимаем ПЕРВЫМ ДЕЛОМ.
            //
            // Ниже идёт возврат товара, а он записывает отметку — и любая
            // запись синхронизирует «исходные» значения модели. После неё
            // getOriginal('status') отдаёт уже новый статус, и письмо ушло бы
            // с текстом «было отменён → стало отменён».
            $прежнийСтатус = (string) $order->getOriginal('status');

            // Загружаем связи для уведомлений
            $order->load(['items', 'user', 'paymentMethod', 'deliveryMethod']);

            // ⚠️ Возврат товара привязан к ФАКТУ отмены, а не к пути в коде.
            //
            // Раньше его звали только карточка заказа в панели и команда
            // отмены по сроку. Любой другой путь — массовое действие,
            // уведомление платёжной системы, правка из своей интеграции —
            // оставлял товар списанным навсегда, и склад молча худел.
            //
            // Повторный вызов безопасен: `returnStockOnce()` смотрит на
            // отметку `stock_returned_at` и второй раз ничего не делает.
            // Рекурсии тоже нет: внутренняя запись отметки статус не меняет,
            // и обработчик выходит на первой же проверке.
            if ($order->status === 'cancelled') {
                $order->returnStockOnce();
            } else {
                // 🔴 И обратно: заказ ожил после отмены — товар снова за ним.
                //
                // Так бывает чаще, чем кажется: покупатель дооплатил после
                // автоотмены по сроку, банк прислал уведомление с задержкой,
                // владелец вернул заказ в работу руками. Без этого товар
                // числился бы за заказом и одновременно лежал на складе
                // свободным — и его продавали второй раз.
                $order->takeStockBackOnce();
            }

            event(new OrderStatusChanged(
                $order,
                $прежнийСтатус,
                (string) $order->status,
            ));
        });
    }
}
