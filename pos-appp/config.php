<?php
/**
 * POS Sistemi Konfigürasyon Dosyası
 * Bu dosyayı farklı ortamlar için düzenleyebilirsiniz
 */

// Veritabanı Ayarları
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'pos_app');
define('DB_USER', 'root');
define('DB_PASS', '');

// Sistem Ayarları
define('SITE_NAME', 'Restoran POS');
define('SITE_URL', 'http://localhost/pos-app/');
define('TIMEZONE', 'Europe/Istanbul');

// Varsayılan Değerler
define('DEFAULT_TAX_RATE', 8.0);      // KDV oranı (%)
define('DEFAULT_SERVICE_RATE', 0.0);  // Servis oranı (%)
define('DEFAULT_CURRENCY', '₺');      // Para birimi
define('DEFAULT_LANGUAGE', 'tr');     // Dil

// Güvenlik Ayarları
define('SESSION_TIMEOUT', 3600);      // Oturum süresi (saniye)
define('MAX_LOGIN_ATTEMPTS', 5);      // Maksimum giriş denemesi
define('PASSWORD_MIN_LENGTH', 6);     // Minimum şifre uzunluğu

// Dosya Yolları
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('LOG_PATH', __DIR__ . '/logs/');
define('BACKUP_PATH', __DIR__ . '/backups/');

// E-posta Ayarları (Gelecekte kullanım için)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_FROM_EMAIL', 'noreply@restoran.com');
define('SMTP_FROM_NAME', 'Restoran POS');

// Rapor Ayarları
define('REPORT_ITEMS_PER_PAGE', 20);
define('REPORT_DATE_FORMAT', 'd.m.Y H:i');
define('REPORT_CURRENCY_FORMAT', 'tr-TR');

// Debug Ayarları (Geliştirme ortamında true yapın)
define('DEBUG_MODE', false);
define('LOG_ERRORS', true);
define('SHOW_ERRORS', false);

// Zaman dilimi ayarla
date_default_timezone_set(TIMEZONE);

// Hata raporlama ayarla
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Log klasörünü oluştur
if (!file_exists(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}

// Upload klasörünü oluştur
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

// Backup klasörünü oluştur
if (!file_exists(BACKUP_PATH)) {
    mkdir(BACKUP_PATH, 0755, true);
}

/**
 * Ortam değişkenlerini yükle
 * .env dosyası varsa ondan oku
 */
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Tırnak işaretlerini kaldır
            if (($value[0] === '"' && $value[-1] === '"') || 
                ($value[0] === "'" && $value[-1] === "'")) {
                $value = substr($value, 1, -1);
            }
            
            // Sabit tanımla
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }
}

/**
 * Yardımcı fonksiyonlar
 */
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

function isDebugMode() {
    return getConfig('DEBUG_MODE', false);
}

function getSiteUrl() {
    return getConfig('SITE_URL', 'http://localhost/pos-app/');
}

function getCurrency() {
    return getConfig('DEFAULT_CURRENCY', '₺');
}

function getLanguage() {
    return getConfig('DEFAULT_LANGUAGE', 'tr');
}

/**
 * Hata loglama fonksiyonu
 */
function logError($message, $context = []) {
    if (!getConfig('LOG_ERRORS', true)) {
        return;
    }
    
    $logFile = getConfig('LOG_PATH', __DIR__ . '/logs/') . 'error_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    $logMessage = "[{$timestamp}] {$message}{$contextStr}" . PHP_EOL;
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Genel log fonksiyonu
 */
function logMessage($message, $level = 'INFO', $context = []) {
    if (!getConfig('LOG_ERRORS', true)) {
        return;
    }
    
    $logFile = getConfig('LOG_PATH', __DIR__ . '/logs/') . 'app_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    $logMessage = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Sistem bilgilerini döndür
 */
function getSystemInfo() {
    return [
        'php_version' => PHP_VERSION,
        'mysql_version' => getMysqlVersion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'site_url' => getSiteUrl(),
        'timezone' => date_default_timezone_get(),
        'debug_mode' => isDebugMode(),
        'config_loaded' => true
    ];
}

/**
 * MySQL versiyonunu al
 */
function getMysqlVersion() {
    try {
        require_once __DIR__ . '/db.php';
        $pdo = db();
        $stmt = $pdo->query('SELECT VERSION() as version');
        $result = $stmt->fetch();
        return $result['version'] ?? 'Unknown';
    } catch (Exception $e) {
        return 'Connection Error';
    }
}

/**
 * Sistem durumunu kontrol et
 */
function checkSystemStatus() {
    $status = [
        'database' => false,
        'writable' => false,
        'php_version' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'extensions' => []
    ];
    
    // Veritabanı bağlantısı
    try {
        require_once __DIR__ . '/db.php';
        $pdo = db();
        $status['database'] = true;
    } catch (Exception $e) {
        $status['database'] = false;
    }
    
    // Yazma izni
    $status['writable'] = is_writable(__DIR__);
    
    // Gerekli uzantılar
    $required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
    foreach ($required_extensions as $ext) {
        $status['extensions'][$ext] = extension_loaded($ext);
    }
    
    return $status;
}
?>

