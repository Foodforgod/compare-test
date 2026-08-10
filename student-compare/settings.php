<?php
require_once __DIR__ . '/app_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['sys_lang'] = $_POST['lang'] ?? 'en';
        $_SESSION['sys_theme'] = $_POST['theme'] ?? 'dark';
        header('Location: settings.php?saved=1');
        exit;
    }
}

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';
?>

<main class="flex-grow-1 p-4">
    <div class="container-fluid">
        <h2 class="fw-bold mb-4"><?= __t('settings'); ?></h2>

        <?php if (isset($_GET['saved'])): ?>
            <div class="alert alert-success rounded-3">Settings updated successfully.</div>
        <?php endif; ?>

        <div class="glass-card p-4 col-md-6">
            <form action="settings.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                <div class="mb-3">
                    <label class="form-label"><?= __t('language'); ?></label>
                    <select name="lang" class="form-select">
                        <option value="en" <?= ($_SESSION['sys_lang'] ?? 'en') === 'en' ? 'selected' : ''; ?>>English</option>
                        <option value="zh" <?= ($_SESSION['sys_lang'] ?? 'en') === 'zh' ? 'selected' : ''; ?>>中文</option>
                        <option value="ms" <?= ($_SESSION['sys_lang'] ?? 'en') === 'ms' ? 'selected' : ''; ?>>Bahasa Melayu</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label"><?= __t('theme'); ?></label>
                    <select name="theme" class="form-select">
                        <option value="dark" <?= ($_SESSION['sys_theme'] ?? 'dark') === 'dark' ? 'selected' : ''; ?>>Dark Mode</option>
                        <option value="light" <?= ($_SESSION['sys_theme'] ?? 'dark') === 'light' ? 'selected' : ''; ?>>Light Mode</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary rounded-3 px-4">Save Configuration</button>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>