<?php
require_once __DIR__ . '/app_bootstrap.php';

$stmt = $pdo->query('SELECT * FROM comparison_history ORDER BY id DESC LIMIT 20');
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';
?>

<main class="flex-grow-1 p-4">
    <div class="container-fluid">
        <h2 class="fw-bold mb-4"><?= __t('history'); ?></h2>

        <div class="glass-card p-4">
            <div class="table-responsive">
                <table class="table table-dark table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hash</th>
                            <th>File A</th>
                            <th>File B</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $item): ?>
                            <tr>
                                <td><?= (int)$item['id']; ?></td>
                                <td><code><?= htmlspecialchars((string)$item['session_hash'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td><?= htmlspecialchars((string)$item['file_a'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string)$item['file_b'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string)$item['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>