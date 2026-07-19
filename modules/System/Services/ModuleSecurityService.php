<?php

namespace Modules\System\Services;

use Modules\System\Models\ModuleSignature;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * 🔒 Сервис безопасности модулей
 *
 * Обеспечивает:
 * - Проверку цифровых подписей
 * - Генерацию ключей
 * - Валидацию целостности
 * - Защиту от взлома
 */
class ModuleSecurityService
{
    /**
     * Генерация пары ключей для модуля
     */
    public static function generateKeys(): array
    {
        $config = [
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ];

        $keyPair = openssl_pkey_new($config);

        openssl_pkey_export($keyPair, $privateKey);
        $publicKeyDetails = openssl_pkey_get_details($keyPair);
        $publicKey = $publicKeyDetails['key'];

        return [
            'private' => $privateKey,
            'public' => $publicKey,
        ];
    }

    /**
     * Подпись содержимого модуля
     */
    public static function signModule(string $modulePath, string $privateKey): string
    {
        $content = self::getModuleContentHash($modulePath);

        openssl_sign($content, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return base64_encode($signature);
    }

    /**
     * Проверка подписи модуля
     */
    public static function verifyModule(string $modulePath, string $moduleName): bool
    {
        try {
            $signatureRecord = ModuleSignature::where('module_name', $moduleName)->first();

            if (!$signatureRecord || !$signatureRecord->signature) {
                Log::warning("ModuleSecurity: No signature found for module {$moduleName}");
                return false;
            }

            $content = self::getModuleContentHash($modulePath);
            $signature = base64_decode($signatureRecord->signature);
            $publicKey = $signatureRecord->public_key;

            $result = openssl_verify(
                $content,
                $signature,
                $publicKey,
                OPENSSL_ALGO_SHA256
            );

            if ($result === 1) {
                Log::info("ModuleSecurity: Signature verified for module {$moduleName}");
                return true;
            }

            Log::error("ModuleSecurity: Signature verification failed for {$moduleName}", [
                'result' => $result,
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error("ModuleSecurity: Verification error for {$moduleName}", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Получение хеша содержимого модуля
     */
    public static function getModuleContentHash(string $modulePath): string
    {
        $files = [];

        if (is_dir($modulePath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($modulePath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $relativePath = str_replace($modulePath . DIRECTORY_SEPARATOR, '', $file->getRealPath());
                    $files[$relativePath] = file_get_contents($file->getRealPath());
                }
            }
        }

        ksort($files);
        $content = json_encode($files, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash('sha256', $content);
    }

    /**
     * Сохранение подписи в БД
     */
    public static function storeSignature(string $moduleName, string $signature, string $publicKey): void
    {
        ModuleSignature::updateOrCreate(
            ['module_name' => $moduleName],
            [
                'signature' => $signature,
                'public_key' => $publicKey,
                'signed_at' => now(),
                'hash_algorithm' => 'sha256',
            ]
        );
    }

    /**
     * Удаление подписи модуля
     */
    public static function removeSignature(string $moduleName): void
    {
        ModuleSignature::where('module_name', $moduleName)->delete();
    }

    /**
     * Проверка на наличие вредоносного кода
     */
    public static function scanForMaliciousCode(string $modulePath): array
    {
        $warnings = [];
        $dangerousPatterns = [
            '/\beval\s*\(/i',
            '/\bexec\s*\(/i',
            '/\bsystem\s*\(/i',
            '/\bshell_exec\s*\(/i',
            '/\bpassthru\s*\(/i',
            '/\bfile_get_contents\s*\(\s*["\']http/i',
            '/\bfile_put_contents\s*\(/i',
            '/\bunlink\s*\(/i',
            '/\brmdir\s*\(/i',
            '/\bchmod\s*\(/i',
            '/\bchown\s*\(/i',
        ];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($modulePath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getRealPath());

                foreach ($dangerousPatterns as $pattern) {
                    if (preg_match($pattern, $content)) {
                        $warnings[] = [
                            'file' => str_replace($modulePath . DIRECTORY_SEPARATOR, '', $file->getRealPath()),
                            'pattern' => $pattern,
                            'line' => self::findLineNumber($content, $pattern),
                        ];
                    }
                }
            }
        }

        return $warnings;
    }

    private static function findLineNumber(string $content, string $pattern): int
    {
        $lines = explode("\n", $content);
        foreach ($lines as $index => $line) {
            if (preg_match($pattern, $line)) {
                return $index + 1;
            }
        }
        return 0;
    }
}
