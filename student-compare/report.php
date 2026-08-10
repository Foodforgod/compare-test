<?php
require_once __DIR__ . '/app_bootstrap.php';

use App\Classes\Exporter;

if (isset($_GET['action']) && $_GET['action'] === 'export' && isset($_SESSION['comparison_res'])) {
    Exporter::exportCSV($_SESSION['comparison_res']['details']);
}

$res = $_SESSION['comparison_res'] ?? null;
$summary = $res['summary'] ?? [];

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';
?>

<main class="flex-grow-1 p-4">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0"><?= __t('report'); ?></h2>
            <?php if ($res): ?>
                <a href="report.php?action=export" class="btn btn-outline-primary rounded-3"><i class="fa-solid fa-download me-2"></i><?= __t('export_csv'); ?></a>
            <?php endif; ?>
        </div>

        <?php if (!$res): ?>
            <div class="alert alert-warning">No active comparison result found. Please run a comparison first.</div>
        <?php else: ?>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="glass-card p-3 border-start border-success border-4">
                        <div class="text-muted small"><?= __t('matched'); ?></div>
                        <div class="fs-2 fw-bold text-success"><?= $summary['matched']; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="glass-card p-3 border-start border-warning border-4">
                        <div class="text-muted small"><?= __t('modified'); ?></div>
                        <div class="fs-2 fw-bold text-warning"><?= $summary['modified']; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="glass-card p-3 border-start border-danger border-4">
                        <div class="text-muted small"><?= __t('missing_a'); ?></div>
                        <div class="fs-2 fw-bold text-danger"><?= $summary['missing_a']; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="glass-card p-3 border-start border-info border-4">
                        <div class="text-muted small"><?= __t('match_rate'); ?></div>
                        <div class="fs-2 fw-bold text-info"><?= $summary['match_percentage']; ?>%</div>
                    </div>
                </div>
            </div>

            <div class="glass-card p-4">
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle" id="resultsTable">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Name (A)</th>
                                <th>Name (B)</th>
                                <th>IC (A)</th>
                                <th>IC (B)</th>
                                <th>Difference</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($res['details'] as $row): 
                                $badgeClass = match($row['status']) {
                                    'Match' => 'bg-success',
                                    'Modified' => 'bg-warning text-dark',
                                    'Missing in A' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                            ?>
                                <tr>
                                    <td><span class="badge <?= $badgeClass; ?>"><?= htmlspecialchars((string)$row['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td><?= htmlspecialchars((string)$row['name_a'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string)$row['name_b'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string)$row['ic_a'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string)$row['ic_b'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string)$row['difference'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string)$row['remarks'], ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
<script>
    $(document).ready(function() {
        $('#resultsTable').DataTable({
            pageLength: 25,
            responsive: true
        });
    });
</script>