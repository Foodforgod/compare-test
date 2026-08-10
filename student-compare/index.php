<?php
require_once __DIR__ . '/app_bootstrap.php';

use App\Classes\ExcelReader;
use App\Classes\Comparison;

$xlsxSupported = $GLOBALS['xlsx_supported'] ?? class_exists('ZipArchive');

$step = (int)($_GET['step'] ?? 1);
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }

    if ($step === 1 && isset($_FILES['file_a'], $_FILES['file_b'])) {
        $extA = strtolower(pathinfo($_FILES['file_a']['name'], PATHINFO_EXTENSION));
        $extB = strtolower(pathinfo($_FILES['file_b']['name'], PATHINFO_EXTENSION));

        $allowed = ['xlsx', 'csv'];
        // If XLSX support not available, disallow .xlsx uploads and require CSV
        if (!$xlsxSupported) {
            $allowed = ['csv'];
        }

        if (in_array($extA, $allowed, true) && in_array($extB, $allowed, true)) {
            if (($extA === 'xlsx' || $extB === 'xlsx') && !$xlsxSupported) {
                $msg = 'Server cannot read .xlsx files because PHP ZipArchive is not available. Please enable the zip extension or upload CSV files.';
            } else {
            $pathA = __DIR__ . '/uploads/A_' . time() . '.' . $extA;
            $pathB = __DIR__ . '/uploads/B_' . time() . '.' . $extB;

            if (move_uploaded_file($_FILES['file_a']['tmp_name'], $pathA) && move_uploaded_file($_FILES['file_b']['tmp_name'], $pathB)) {
                $_SESSION['file_a'] = $pathA;
                $_SESSION['file_b'] = $pathB;
                header('Location: index.php?step=2');
                exit;
            }
            }
        }
        $msg = 'Invalid file format. Please upload .xlsx or .csv files.';
    }

    if ($step === 2) {
        $mapA = ['name' => (int)$_POST['map_a_name'], 'ic' => (int)$_POST['map_a_ic'], 'id' => (int)$_POST['map_a_id']];
        $mapB = ['name' => (int)$_POST['map_b_name'], 'ic' => (int)$_POST['map_b_ic'], 'id' => (int)$_POST['map_b_id']];
        $mode = $_POST['match_mode'] ?? Comparison::MODE_SMART;

        $results = Comparison::run($_SESSION['file_a'], $_SESSION['file_b'], $mapA, $mapB, $mode);
        $_SESSION['comparison_res'] = $results;

        $stmt = $pdo->prepare('INSERT INTO comparison_history (session_hash, file_a, file_b, summary) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            md5(uniqid('', true)),
            basename($_SESSION['file_a']),
            basename($_SESSION['file_b']),
            json_encode($results['summary'])
        ]);

        header('Location: report.php');
        exit;
    }
}

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';
?>

<main class="flex-grow-1 p-4">
    <div class="container-fluid">
        <h2 class="fw-bold mb-4"><?= __t('dashboard'); ?></h2>

        <?php if ($msg !== ''): ?>
            <div class="alert alert-danger rounded-3"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <div class="glass-card p-5">
                <h4 class="mb-3"><?= __t('upload_files'); ?></h4>
                <form action="index.php?step=1" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">File A (.xlsx, .csv)</label>
                            <input type="file" name="file_a" class="form-control form-control-lg" required accept="<?= $xlsxSupported ? '.xlsx,.csv' : '.csv'; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">File B (.xlsx, .csv)</label>
                            <input type="file" name="file_b" class="form-control form-control-lg" required accept="<?= $xlsxSupported ? '.xlsx,.csv' : '.csv'; ?>">
                        </div>
                    </div>
                    <?php if (!$xlsxSupported): ?>
                        <div class="mb-3 text-warning">Server cannot read .xlsx files (missing PHP Zip extension). Please upload CSV files or enable `zip` in php.ini.</div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary btn-lg rounded-3 px-5"><?= __t('map_columns'); ?> <i class="fa-solid fa-arrow-right ms-2"></i></button>
                </form>
            </div>
        <?php elseif ($step === 2 && !empty($_SESSION['file_a'])): 
            $fileA = $_SESSION['file_a'];
            $fileB = $_SESSION['file_b'];

            // Basic diagnostics for uploaded files so user sees actionable errors
            if (!file_exists($fileA) || !is_readable($fileA)) {
                $msg = 'Uploaded File A is not readable. Check permissions and that the upload succeeded.';
                $previewA = $previewB = [];
            } elseif (!file_exists($fileB) || !is_readable($fileB)) {
                $msg = 'Uploaded File B is not readable. Check permissions and that the upload succeeded.';
                $previewA = $previewB = [];
            } else {
                $extA = strtolower(pathinfo($fileA, PATHINFO_EXTENSION));
                $extB = strtolower(pathinfo($fileB, PATHINFO_EXTENSION));

                if (($extA === 'xlsx' || $extB === 'xlsx') && !class_exists('ZipArchive')) {
                    $msg = 'PHP ZipArchive is not available. Enable the `zip` extension to read .xlsx files.';
                    $previewA = $previewB = [];
                } else {
                    $previewA = ExcelReader::preview($fileA, 6);
                    $previewB = ExcelReader::preview($fileB, 6);
                }
            }

            $headersA = $previewA[0] ?? [];
            $headersB = $previewB[0] ?? [];
        ?>
            <div class="glass-card p-5">
                <h4 class="mb-4"><?= __t('map_columns'); ?></h4>
                <form action="index.php?step=2" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Matching Mode</label>
                        <select name="match_mode" class="form-select w-auto">
                            <option value="smart"><?= __t('smart_match'); ?> (Ignores spaces, dashes, case)</option>
                            <option value="exact"><?= __t('exact_match'); ?></option>
                        </select>
                    </div>
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary">File A Mapping</h6>
                            <div class="mb-3">
                                <label class="form-label">Student Name</label>
                                <select name="map_a_name" class="form-select">
                                    <?php foreach ($headersA as $idx => $h): ?><option value="<?= $idx; ?>"><?= htmlspecialchars((string)$h, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">IC Number</label>
                                <select name="map_a_ic" class="form-select">
                                    <?php foreach ($headersA as $idx => $h): ?><option value="<?= $idx; ?>" <?= $idx === 1 ? 'selected' : ''; ?>><?= htmlspecialchars((string)$h, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Student ID</label>
                                <select name="map_a_id" class="form-select">
                                    <?php foreach ($headersA as $idx => $h): ?><option value="<?= $idx; ?>"><?= htmlspecialchars((string)$h, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-info">File B Mapping</h6>
                            <div class="mb-3">
                                <label class="form-label">Student Name</label>
                                <select name="map_b_name" class="form-select">
                                    <?php foreach ($headersB as $idx => $h): ?><option value="<?= $idx; ?>"><?= htmlspecialchars((string)$h, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">IC Number</label>
                                <select name="map_b_ic" class="form-select">
                                    <?php foreach ($headersB as $idx => $h): ?><option value="<?= $idx; ?>" <?= $idx === 1 ? 'selected' : ''; ?>><?= htmlspecialchars((string)$h, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Student ID</label>
                                <select name="map_b_id" class="form-select">
                                    <?php foreach ($headersB as $idx => $h): ?><option value="<?= $idx; ?>"><?= htmlspecialchars((string)$h, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg rounded-3 px-5"><?= __t('run_comparison'); ?></button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>