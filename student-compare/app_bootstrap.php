<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Class Autoloader (PSR-4 Mapping)
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\Classes\\';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $relativePath = str_replace('\\', '/', $relativeClass) . '.php';

    // Try multiple candidate directories: classes/ subfolder and project root
    $candidates = [
        __DIR__ . '/classes/' . $relativePath,
        __DIR__ . '/' . $relativePath,
    ];

    foreach ($candidates as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// 2. CSRF Token Guard
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 3. Localization Initialization
$lang = $_SESSION['sys_lang'] ?? 'en';

// Look for language files in multiple places to be tolerant of layout variations
$possibleLangFiles = [
    __DIR__ . '/languages/' . $lang . '.php',
    __DIR__ . '/' . $lang . '.php',
];
$defaultLangFiles = [
    __DIR__ . '/languages/en.php',
    __DIR__ . '/en.php',
];

$langFile = null;
foreach ($possibleLangFiles as $f) {
    if (file_exists($f)) {
        $langFile = $f;
        break;
    }
}
if ($langFile === null) {
    foreach ($defaultLangFiles as $f) {
        if (file_exists($f)) {
            $langFile = $f;
            break;
        }
    }
}

$GLOBALS['sys_translations'] = $langFile ? require $langFile : [];

/**
 * Translation helper function
 */
function __t(string $key): string {
    return $GLOBALS['sys_translations'][$key] ?? $key;
}

// 4. Database Setup (SQLite via PDO)
$uploadsDir = __DIR__ . '/uploads';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

try {
    $pdo = new PDO('sqlite:' . $uploadsDir . '/data.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec("CREATE TABLE IF NOT EXISTS comparison_history (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_hash TEXT UNIQUE,
        file_a TEXT,
        file_b TEXT,
        summary TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    error_log("Database initialization error: " . $e->getMessage());
}

// Expose whether XLSX reading is supported (ZipArchive required)
$GLOBALS['xlsx_supported'] = class_exists('ZipArchive');