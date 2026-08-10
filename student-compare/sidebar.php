<aside class="sidebar p-3 glass-card d-flex flex-column flex-shrink-0" style="width: 260px;">
    <a href="index.php" class="d-flex align-items-center mb-4 me-md-auto text-decoration-none text-primary fw-bold fs-5">
        <i class="fa-solid fa-code-compare fa-lg me-2"></i><?= __t('app_name'); ?>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto gap-2">
        <li class="nav-item">
            <a href="index.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-cloud-arrow-up me-2"></i><?= __t('dashboard'); ?>
            </a>
        </li>
        <li>
            <a href="report.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'report.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-pie me-2"></i><?= __t('report'); ?>
            </a>
        </li>
        <li>
            <a href="history.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'history.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-clock-rotate-left me-2"></i><?= __t('history'); ?>
            </a>
        </li>
        <li>
            <a href="settings.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-sliders me-2"></i><?= __t('settings'); ?>
            </a>
        </li>
    </ul>
    <hr>
    <div class="small text-muted text-center">
        System Version 2.5 Pro
    </div>
</aside>