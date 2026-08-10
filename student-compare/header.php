<!DOCTYPE html>
<html lang="<?= $_SESSION['sys_lang'] ?? 'en'; ?>" data-bs-theme="<?= $_SESSION['sys_theme'] ?? 'dark'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __t('app_name'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; background: #0f172a; color: #f8fafc; }
        [data-bs-theme="light"] body { background: #f1f5f9; color: #0f172a; }
        .glass-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; }
        [data-bs-theme="light"] .glass-card { background: rgba(255, 255, 255, 0.9); border-color: rgba(0, 0, 0, 0.08); }
        .sidebar { min-height: 100vh; border-right: 1px solid rgba(255, 255, 255, 0.1); }
        .nav-link.active { background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff !important; font-weight: 600; border-radius: 10px; }
    </style>
</head>
<body>
<div class="d-flex">