<?php

namespace Modules\System\Services;

/**
 * 🛡️ Сервис для определения защищенных модулей
 * 
 * Ключевые модули, без которых система не может работать,
 * должны быть защищены от удаления и отключения.
 */
class ProtectedModulesService
{
    /**
     * Список защищенных модулей (нельзя удалять и отключать)
     * 
     * Эти модули критичны для работы системы:
     * - System: управление модулями, без него система не может работать
     * - Install: модуль установки, нужен для первичной настройки
     * - Localization: модуль локализации, критичен для мультиязычности
     */
    protected const PROTECTED_MODULES = [
        'System',       // Системный модуль - управление модулями
        'Install',      // Модуль установки - нужен для первичной настройки
        'Localization', // Модуль локализации - критичен для работы
    ];

    /**
     * Проверка, является ли модуль защищенным
     */
    public static function isProtected(string $moduleName): bool
    {
        return in_array($moduleName, self::PROTECTED_MODULES, true);
    }

    /**
     * Получить список всех защищенных модулей
     */
    public static function getProtectedModules(): array
    {
        return self::PROTECTED_MODULES;
    }

    /**
     * Проверка возможности удаления модуля
     */
    public static function canDelete(string $moduleName): bool
    {
        return !self::isProtected($moduleName);
    }

    /**
     * Проверка возможности отключения модуля
     */
    public static function canDisable(string $moduleName): bool
    {
        return !self::isProtected($moduleName);
    }

    /**
     * Получить сообщение о защите модуля
     */
    public static function getProtectionMessage(string $moduleName): string
    {
        if (!self::isProtected($moduleName)) {
            return '';
        }

        return "Модуль «{$moduleName}» является системным и не может быть удален или отключен.";
    }
}

