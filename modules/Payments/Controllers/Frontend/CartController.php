<?php

namespace Modules\Payments\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Payments\Models\PaymentMethod;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\OrderItem;
use Modules\Delivery\Models\DeliveryMethod;
use Modules\News\Models\News;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = $this->освежитьКорзину(session('cart', []));

        $paymentMethods = PaymentMethod::where('active', true)->orderBy('sort_order')->orderBy('id')->get();
        $deliveryMethods = DeliveryMethod::where('active', true)->orderBy('sort_order')->orderBy('id')->get();

        // 🔴 Доставка нужна не всякому заказу.
        //
        // Раньше шаг доставки показывался всегда, и услугу — детский приём в
        // клинике — предлагалось отправить Почтой России за 350 ₽ с указанием
        // адреса. Теперь шаг появляется, только если в корзине есть то, что
        // физически везут; заказ из одних услуг оформляется без доставки,
        // адреса и города.
        $нуженаДоставка = $this->нуженаДоставка(array_keys($cart));

        return view('Payments::public.cart', compact(
            'cart', 'paymentMethods', 'deliveryMethods', 'нуженаДоставка'
        ));
    }

    /**
     * Есть ли в наборе хоть один пересылаемый товар.
     *
     * ⚠️ Признак берётся ИЗ БАЗЫ по идентификаторам, а не из корзины и не из
     * запроса: в сессии лежит только `id`, `qty`, название и цена, а браузеру
     * доверять здесь нельзя — иначе достаточно было бы прислать «доставка не
     * нужна» и получить бесплатную пересылку настоящего товара.
     *
     * Смешанный набор (товар + услуга) считается требующим доставки: везти
     * надо товар, услуга просто едет в том же заказе строкой.
     */
    private function нуженаДоставка(array $идентификаторы): bool
    {
        if ($идентификаторы === []) {
            return false;
        }

        return News::whereIn('id', $идентификаторы)
            ->whereIn('template', \Modules\News\Controllers\Admin\NewsController::SHIPPABLE_TEMPLATES)
            ->exists();
    }

    /**
     * Сверить содержимое корзины с базой перед показом.
     *
     * Корзина живёт в сессии два часа, а за это время цена меняется, товар
     * снимают с публикации или удаляют совсем. Раньше покупатель видел то, что
     * положил, а на оформлении получал другую сумму либо ошибку «товар больше
     * не продаётся» — без объяснения, какой именно.
     *
     * Здесь корзина приводится к правде: цена и название обновляются, исчезнувшее
     * убирается, количество подрезается по остатку. Оформление всё равно
     * перечитывает цены само (форме верить нельзя), но покупатель теперь видит
     * ровно ту сумму, которую с него спросят.
     */
    private function освежитьКорзину(array $cart): array
    {
        if ($cart === []) {
            return $cart;
        }

        $товары = News::whereIn('id', array_keys($cart))->get()->keyBy('id');
        $свежая = [];
        $изменилось = false;

        foreach ($cart as $id => $строка) {
            $товар = $товары->get((int) $id);

            if (! $товар || ! $товар->published || $товар->price === null) {
                $изменилось = true;
                continue;
            }

            $количество = max(1, (int) ($строка['qty'] ?? 1));

            if ($товар->stock !== null && $количество > $товар->stock) {
                $количество = (int) $товар->stock;
                $изменилось = true;
            }

            if ($количество < 1) {          // остаток кончился совсем
                $изменилось = true;
                continue;
            }

            $изменилось = $изменилось
                || (float) ($строка['price'] ?? 0) !== (float) $товар->price
                || ($строка['title'] ?? null) !== $товар->title;

            $свежая[$id] = [
                'id' => (int) $id,
                'title' => $товар->title,
                'price' => (float) $товар->price,
                'qty' => $количество,
            ];
        }

        if ($изменилось) {
            session(['cart' => $свежая]);
            session()->flash('info', __('frontend.cart.contents_refreshed'));
        }

        return $свежая;
    }

    public function add(Request $request)
    {
        // Количество раньше не проверялось вовсе: `intval(null)` давал ноль, а
        // отрицательное число УМЕНЬШАЛО уже лежащее в корзине.
        $request->validate([
            'id'  => 'required|integer|exists:news,id',
            'qty' => 'nullable|integer|min:1|max:1000',
        ]);

        $id = (int) $request->input('id');
        $qty = max(1, (int) $request->input('qty', 1));

        $product = News::find($id);

        // ⚠️ В корзину кладётся только то, что продаётся.
        //
        // Раньше туда уходил ЛЮБОЙ материал по идентификатору — статья,
        // страница урока, что угодно. Цена у него пустая, значит `(float) null`
        // давал ноль, и заказ оформлялся на 0 ₽: мусор в панели, письма
        // покупателю и списание доставки за ничто.
        if (! $product || ! $product->published || $product->price === null) {
            return response()->json(['message' => 'Этот товар недоступен для заказа'], 400);
        }

        if (!is_null($product->stock) && $product->stock < $qty) {
            return response()->json([
                'message' => 'Недостаточно товара на складе. Доступно: ' . $product->stock
            ], 400);
        }

        $cart = session('cart', []);

        if (isset($cart[$id])) {
            $cart[$id]['qty'] += $qty;

            if (!is_null($product->stock) && $cart[$id]['qty'] > $product->stock) {
                return response()->json([
                    'message' => 'Вы не можете добавить больше товаров, чем есть на складе. Доступно: ' . $product->stock
                ], 400);
            }
        } else {
            $cart[$id] = ['id' => $id, 'qty' => $qty];
        }

        // ⚠️ Название и цена берутся ИЗ БАЗЫ, а не из запроса.
        //
        // Прежде они приходили полями формы и оседали в сессии. Подделать
        // сумму заказа этим уже нельзя (оформление перечитывает цены), но
        // корзина показывала присланное число — то есть покупателю можно было
        // подсунуть чужую цену, а после правки цены в панели он до конца
        // сеанса видел старую и удивлялся счёту.
        $cart[$id]['title'] = $product->title;
        $cart[$id]['price'] = (float) $product->price;

        session(['cart' => $cart]);

        return response()->json(['message' => 'Добавлено в корзину']);
    }

    public function remove(Request $request)
    {
        $cart = session('cart', []);
        $id = $request->input('id');

        unset($cart[$id]);

        session(['cart' => $cart]);

        return redirect()->route('cart.index')->with('success', 'Товар удалён из корзины');
    }

    /**
     * 🔄 Обновление количества товара в корзине
     */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:news,id',
            'qty' => 'required|integer|min:1',
        ]);

        $id = $request->input('id');
        $qty = $request->input('qty');

        $cart = session('cart', []);

        if (!isset($cart[$id])) {
            return response()->json(['message' => 'Товар не найден в корзине'], 404);
        }

        $product = News::findOrFail($id);

        // Проверка остатка
        if (!is_null($product->stock) && $qty > $product->stock) {
            return response()->json([
                'message' => 'Недостаточно товара на складе. Доступно: ' . $product->stock
            ], 400);
        }

        $cart[$id]['qty'] = $qty;
        session(['cart' => $cart]);

        return response()->json([
            'message' => 'Количество обновлено',
            'subtotal' => $qty * $cart[$id]['price'],
            'total' => array_sum(array_map(fn($item) => $item['qty'] * $item['price'], $cart))
        ]);
    }

    /**
     * 📦 Проверка остатка товара (AJAX)
     */
    public function checkStock(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:news,id',
        ]);

        $product = News::findOrFail($request->input('id'));

        return response()->json([
            'stock' => $product->stock ?? 0,
            'available' => is_null($product->stock) || $product->stock > 0
        ]);
    }

    public function checkout(Request $request)
    {
        // Согласие проверяется НА СЕРВЕРЕ. Отметка в браузере — только
        // подсказка покупателю: атрибут required снимается из инструментов
        // разработчика за секунду, а хранить нужно подтверждённую волю.
        // 🔴 Покупателя спрашиваем ОБЯЗАТЕЛЬНО.
        //
        // Раньше форма собирала только способ оплаты, способ доставки и
        // согласие — и заказ приходил владельцу без имени, телефона и адреса.
        // Магазин принимал заказы, которые физически некому доставить и не с
        // кем уточнить; письмо покупателю тоже уходило в никуда.
        //
        // Адрес требуется, только если доставка его подразумевает: у
        // самовывоза его нет по смыслу. Проверяется это на сервере, а не
        // доверием к скрытому в браузере полю.
        // 🔴 Нужна ли вообще доставка — решает СОСТАВ ЗАКАЗА, а не форма.
        //
        // Заказ из одних услуг (приём в клинике, работа, консультация) никуда
        // не едет: у него нет ни способа доставки, ни адреса, ни города, и
        // денег за доставку с него не берут. Раньше доставка требовалась
        // всегда, поэтому услугу нельзя было купить, не выбрав службу и не
        // заплатив ей.
        //
        // Признак считается по базе, до разбора заявки: прислать «доставка не
        // нужна» вместе с настоящим товаром не выйдет.
        $составЗаявки = collect((array) $request->input('items', []))
            ->map(fn ($с) => (int) ($с['id'] ?? 0))
            ->filter()
            ->all();

        $нуженаДоставка = $this->нуженаДоставка($составЗаявки);

        $самовывоз = DeliveryMethod::find($request->input('delivery_method_id'))?->type === 'pickup';

        // Адрес и город спрашиваем, только когда есть что везти и это не
        // самовывоз. У самовывоза адреса нет по смыслу, у услуги — тем более.
        $нуженАдрес = $нуженаДоставка && ! $самовывоз;

        $проверено = $request->validate([
            'payment_method_id'  => 'required|exists:payment_methods,id',
            'delivery_method_id' => ($нуженаДоставка ? 'required' : 'nullable') . '|exists:delivery_methods,id',
            'terms_agree'        => 'accepted',
            'customer_name'      => 'required|string|max:255',
            'customer_phone'     => 'required|string|max:64',
            'customer_email'     => 'nullable|email|max:255',
            'customer_address'   => ($нуженАдрес ? 'required' : 'nullable') . '|string|max:500',
            // Город отдельной строкой, а не внутри адреса. Из свободного текста
            // регион не выделить, а по нему служба решает, возит ли она туда:
            // до этого ограничение `regions` при оформлении не применялось
            // вовсе, хотя в панели его можно было задать.
            'customer_city'      => ($нуженАдрес ? 'required' : 'nullable') . '|string|max:190',
            'comment'            => 'nullable|string|max:2000',
        ], [
            'terms_agree.accepted' => __('frontend.cart.consent_required'),
        ]);

        // ⚠️ ИЗ ЗАПРОСА БЕРЁМ ТОЛЬКО ТОВАР И КОЛИЧЕСТВО.
        //
        // Раньше сюда приходили ещё название и ЦЕНА, и они же уходили в
        // заказ, в сумму и в платёжную систему. То есть покупателю
        // достаточно было отправить `items[0][price]=1`, чтобы купить что
        // угодно за рубль: браузерная форма — не источник правды, её
        // содержимое подменяется из инструментов разработчика за секунду.
        //
        // Цена и название берутся из базы по идентификатору. Заодно это
        // защищает карточку заказа в панели от чужой разметки в названии.
        $заявка = (array) $request->input('items', []);

        if (empty($заявка)) {
            return redirect()->route('cart.index')->with('error', 'Корзина пуста');
        }

        $items = [];

        foreach ($заявка as $строка) {
            $идентификатор = (int) ($строка['id'] ?? 0);
            $количество    = (int) ($строка['qty'] ?? 0);

            if ($идентификатор <= 0 || $количество <= 0) {
                continue;
            }

            $товар = News::find($идентификатор);

            // Снятый с публикации или удалённый товар в заказ не попадает.
            if (! $товар || ! $товар->published) {
                return redirect()->route('cart.index')
                    ->with('error', 'Товар из корзины больше не продаётся');
            }

            $items[] = [
                'id'     => $товар->id,
                'title'  => $товар->title,
                'price'  => (float) $товар->price,
                'qty'    => $количество,
                // Вес нужен ограничению службы доставки. Пустой — «не
                // взвешиваем» (услуга), и в сумму он не идёт вовсе.
                'weight' => $товар->weight === null ? null : (float) $товар->weight,
            ];
        }

        if (empty($items)) {
            return redirect()->route('cart.index')->with('error', 'Корзина пуста');
        }

        // Получаем методы оплаты и доставки для проверок
        $paymentMethod = PaymentMethod::find($request->payment_method_id);
        $deliveryMethod = DeliveryMethod::find($request->delivery_method_id);

        if (!$paymentMethod || !$paymentMethod->active) {
            return redirect()->route('cart.index')->with('error', 'Выбранный метод оплаты недоступен');
        }

        // Служба сверяется, только если заказ вообще везут. Заказ из одних
        // услуг проходит без неё — и присланную «на всякий случай» службу мы
        // тоже отбрасываем, иначе покупатель заплатил бы за доставку приёма.
        if ($нуженаДоставка) {
            if (!$deliveryMethod || !$deliveryMethod->active) {
                return redirect()->route('cart.index')->with('error', 'Выбранный метод доставки недоступен');
            }
        } else {
            $deliveryMethod = null;
        }

        // Расчет общей суммы товаров
        $itemsTotal = collect($items)->sum(fn($item) => $item['qty'] * $item['price']);

        // Вес заказа. Складываем ТОЛЬКО известные веса: если ни у одного
        // товара веса нет, вес заказа остаётся пустым — «не взвешиваем», и
        // ограничение по весу к такому заказу не применяется.
        $весЗаказа = null;

        foreach ($items as $строка) {
            if ($строка['weight'] !== null) {
                $весЗаказа = ($весЗаказа ?? 0.0) + $строка['weight'] * $строка['qty'];
            }
        }

        // 🔴 Правила доставки считает РАСЧЁТЧИК, а не корзина.
        //
        // Он умеет всё сразу: порог бесплатной доставки, ограничение по весу и
        // список регионов. Раньше корзина брала `$deliveryMethod->price` как
        // есть, поэтому:
        //   • порог бесплатной доставки применялся только в браузере —
        //     покупатель видел «к оплате 5000», а заказ создавался на 5300;
        //   • вес и регион не проверялись при оформлении ВООБЩЕ, хотя в панели
        //     их можно задать: служба, которая не возит в этот город, спокойно
        //     принимала заказ.
        //
        // ⚠️ `skip_api` — наружу при оформлении не ходим: расчёт через API
        // службы имеет таймаут 15–20 секунд, и недоступная служба рвала бы
        // покупку. Правила проверяются до этой ветки и работают всегда.
        $deliveryPrice = 0.0;

        if ($deliveryMethod) {
            $расчёт = app(\Modules\Delivery\Services\DeliveryCalculatorService::class)->calculate($deliveryMethod, [
                'city'        => $проверено['customer_city'] ?? null,
                'region'      => $проверено['customer_city'] ?? null,
                'address'     => $проверено['customer_address'] ?? null,
                'weight'      => $весЗаказа,
                'order_total' => $itemsTotal,
                'skip_api'    => true,
            ]);

            if (! empty($расчёт['error'])) {
                return redirect()->route('cart.index')->with('error', $расчёт['error']);
            }

            $deliveryPrice = (float) ($расчёт['price'] ?? $deliveryMethod->price);
        }

        // Расчет комиссии
        $commissionAmount = 0;
        if ($paymentMethod->commission) {
            $commissionAmount = $itemsTotal * ($paymentMethod->commission / 100);
        }

        // Итоговая сумма с доставкой и комиссией
        $total = $itemsTotal + $deliveryPrice + $commissionAmount;

        // ⚠️ Пределы платёжной системы проверяются по СПИСЫВАЕМОЙ сумме, а не
        // по сумме товаров: система увидит именно её, вместе с доставкой и
        // комиссией. Раньше заказ на 950 ₽ товаров с доставкой 300 уходил в
        // систему с потолком 1000 ₽ и отбивался уже на её стороне —
        // покупатель получал невнятную ошибку вместо понятной.
        if ($paymentMethod->min_amount && $total < $paymentMethod->min_amount) {
            return redirect()->route('cart.index')->with('error',
                "Минимальная сумма заказа для {$paymentMethod->title}: {$paymentMethod->min_amount} ₽");
        }

        if ($paymentMethod->max_amount && $total > $paymentMethod->max_amount) {
            return redirect()->route('cart.index')->with('error',
                "Максимальная сумма заказа для {$paymentMethod->title}: {$paymentMethod->max_amount} ₽");
        }

        try {
            $order = null;

            // Создаем заказ вне транзакции, чтобы избежать проблем с областью видимости
            DB::transaction(function () use ($request, $items, $paymentMethod, $deliveryMethod, $itemsTotal, $commissionAmount, $total, $deliveryPrice, $проверено, $весЗаказа) {
                $order = Order::create([
                    'user_id'            => Auth::check() ? Auth::id() : null,
                    'payment_method_id'  => $request->payment_method_id,
                    // Берём службу из ПРОВЕРЕННОЙ переменной, а не из запроса:
                    // у заказа из одних услуг она снята выше, и присланное
                    // «на всякий случай» значение не должно попасть в заказ.
                    'delivery_method_id' => $deliveryMethod?->id,
                    'total'              => $total,
                    'items_total'        => $itemsTotal,
                    'delivery_price'     => $deliveryPrice,
                    'commission'         => $commissionAmount,
                    'status'             => 'pending',
                    'is_new'             => true,

                    // Контакты покупателя. Почта запасным путём берётся из
                    // учётной записи: вошедший покупатель мог оставить поле
                    // пустым, а письмо о заказе ему всё равно нужно.
                    'customer_name'      => $проверено['customer_name'],
                    'customer_phone'     => $проверено['customer_phone'],
                    'customer_email'     => $проверено['customer_email'] ?? (Auth::user()->email ?? null),
                    'customer_address'   => $проверено['customer_address'] ?? null,
                    'customer_city'      => $проверено['customer_city'] ?? null,
                    'comment'            => $проверено['comment'] ?? null,

                    // Вес сохраняем, а не пересчитываем задним числом: товар
                    // могли изменить после заказа, а по этому числу владелец
                    // объясняется со службой доставки.
                    'total_weight'       => $весЗаказа,
                ]);

                foreach ($items as $item) {
                    // 🔴 Строка товара БЛОКИРУЕТСЯ до конца транзакции.
                    //
                    // Без блокировки двое покупателей на последний экземпляр
                    // проходили проверку остатка одновременно: каждый видел
                    // «1 шт. есть», оба создавали заказ, и склад уходил в
                    // минус — товар продан дважды, а есть он один. На витрине
                    // это невидимо и всплывает только при отгрузке.
                    //
                    // `lockForUpdate()` заставляет второго покупателя ждать
                    // конца первой транзакции и читать УЖЕ уменьшенный
                    // остаток. На SQLite (тесты) блокировка вырождается в
                    // обычную выборку — там гонки и нет, запись одна.
                    $product = News::whereKey($item['id'])->lockForUpdate()->first();

                    if (! $product) {
                        throw new \Exception('Товар из корзины больше не продаётся');
                    }

                    if (!is_null($product->stock) && $product->stock < $item['qty']) {
                        throw new \Exception('Недостаточно товара на складе: ' . $product->title);
                    }

                    OrderItem::create([
                        'order_id'   => $order->id,
                        'product_id' => $item['id'],
                        'title'      => $item['title'],
                        'price'      => $item['price'],
                        'qty'        => $item['qty'],
                    ]);

                    if (!is_null($product->stock)) {
                        $product->decrement('stock', $item['qty']);
                    }
                }

                // Сохраняем ID заказа в сессии для использования после транзакции
                session(['last_order_id' => $order->id]);
            });

            $orderId = session('last_order_id');
            session()->forget(['cart', 'last_order_id']);

            // Свои заказы помним в сессии — по ним гостю открывается страница
            // подтверждения. Держим последние 20: список нужен на один заход в
            // магазин, а не навсегда.
            $мои = array_slice(array_merge((array) session('my_orders', []), [(int) $orderId]), -20);
            session(['my_orders' => $мои]);

            // Онлайн-метод: создаём платёж и уводим покупателя на оплату.
            // Офлайн-методы (наличные, счёт) платежа не создают — для них
            // подтверждение заказа и есть конец пути.
            $redirect = $this->startOnlinePayment((int) $orderId, $paymentMethod);

            if ($redirect !== null) {
                return redirect()->away($redirect);
            }

            return redirect()->route('cart.confirm', ['id' => $orderId]);
        } catch (\Exception $e) {
            return redirect()->route('cart.index')->with('error', 'Ошибка при создании заказа: ' . $e->getMessage());
        }
    }

    public function confirm($id)
    {
        $order = Order::with(['paymentMethod', 'deliveryMethod', 'items'])->findOrFail($id);

        // 🔴 Чужой заказ по этому адресу не показывается.
        //
        // Раньше страница отдавала ЛЮБОЙ заказ по номеру в адресе: посторонний
        // перебирал /cart/confirm/1, /2, /3 и читал, что и на какую сумму
        // купили — то есть всю историю продаж магазина, включая объёмы.
        //
        // Гостю доступ даёт сессия: номера своих заказов складываются в неё
        // при оформлении. Это тот же браузер, в котором покупали, поэтому
        // возврат с платёжной страницы работает как прежде.
        if (! $this->свойЗаказ($order)) {
            abort(404);
        }

        return view('Payments::public.confirm', [
            'paymentMethod'  => $order->paymentMethod,
            'deliveryMethod' => $order->deliveryMethod,
            'order'          => $order,
        ]);
    }

    /**
     * Этот заказ принадлежит тому, кто сейчас смотрит?
     *
     * ⚠️ Отдаём 404, а не 403: «нет доступа» подтверждает, что заказ с таким
     * номером существует, и по этому признаку перебором вычисляется число
     * продаж.
     */
    private function свойЗаказ(Order $order): bool
    {
        if (Auth::check() && (Auth::user()->is_admin || $order->user_id === Auth::id())) {
            return true;
        }

        return in_array((int) $order->id, array_map('intval', (array) session('my_orders', [])), true);
    }

    /**
     * Начать онлайн-оплату заказа.
     *
     * @return string|null адрес страницы оплаты либо null, если метод
     *                     офлайновый или драйвера для него нет
     */
    private function startOnlinePayment(int $orderId, PaymentMethod $paymentMethod): ?string
    {
        if ($paymentMethod->type === 'offline') {
            return null;
        }

        $order = Order::find($orderId);

        if (! $order) {
            return null;
        }

        try {
            $result = app(\Modules\Payments\Services\PaymentGatewayService::class)
                ->createPayment($order, $paymentMethod);
        } catch (\Throwable $e) {
            // Платёж не начался — заказ уже создан и терять его нельзя.
            // Владелец увидит неоплаченный заказ в панели и свяжется с
            // покупателем, а покупатель получит внятное сообщение.
            \Illuminate\Support\Facades\Log::warning('Не удалось начать оплату заказа', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', __('frontend.cart.payment_start_failed'));

            return null;
        }

        if (! ($result['success'] ?? false)) {
            session()->flash('error', __('frontend.cart.payment_start_failed'));

            return null;
        }

        if (! empty($result['payment_id'])) {
            $order->update(['payment_id' => $result['payment_id']]);
        }

        return $result['payment_url'] ?? $result['confirmation_url'] ?? null;
    }
}
