<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Валидация российских телефонных номеров
 * 
 * Поддерживает форматы:
 * - +7 (XXX) XXX-XX-XX
 * - 8 (XXX) XXX-XX-XX
 * - +7XXXXXXXXXX
 * - 8XXXXXXXXXX
 * - 7XXXXXXXXXX
 */
class RussianPhone implements Rule
{
    public function passes($attribute, $value): bool
    {
        if (empty($value)) {
            return true; // Необязательное поле
        }

        // Удаляем все символы кроме цифр и +
        $cleaned = preg_replace('/[^\d+]/', '', $value);

        // Проверяем различные форматы
        // +7XXXXXXXXXX (11 цифр после +7)
        if (preg_match('/^\+7\d{10}$/', $cleaned)) {
            return true;
        }

        // 8XXXXXXXXXX (11 цифр, начинается с 8)
        if (preg_match('/^8\d{10}$/', $cleaned)) {
            return true;
        }

        // 7XXXXXXXXXX (11 цифр, начинается с 7)
        if (preg_match('/^7\d{10}$/', $cleaned)) {
            return true;
        }

        return false;
    }

    public function message(): string
    {
        return 'Номер телефона должен быть в формате: +7 (XXX) XXX-XX-XX или 8 (XXX) XXX-XX-XX';
    }

    /**
     * Нормализация номера телефона к единому формату +7XXXXXXXXXX
     */
    public static function normalize(string $phone): string
    {
        // Удаляем все символы кроме цифр
        $cleaned = preg_replace('/[^\d]/', '', $phone);

        // Если начинается с 8, заменяем на 7
        if (strlen($cleaned) === 11 && $cleaned[0] === '8') {
            $cleaned = '7' . substr($cleaned, 1);
        }

        // Если 10 цифр, добавляем 7 в начало
        if (strlen($cleaned) === 10) {
            $cleaned = '7' . $cleaned;
        }

        return '+' . $cleaned;
    }

    /**
     * Форматирование номера для отображения: +7 (XXX) XXX-XX-XX
     */
    public static function format(string $phone): string
    {
        $normalized = self::normalize($phone);
        $digits = preg_replace('/[^\d]/', '', $normalized);

        if (strlen($digits) === 11) {
            return sprintf(
                '+7 (%s) %s-%s-%s',
                substr($digits, 1, 3),
                substr($digits, 4, 3),
                substr($digits, 7, 2),
                substr($digits, 9, 2)
            );
        }

        return $phone; // Возвращаем как есть, если не удалось отформатировать
    }
}





