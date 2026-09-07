<?php

require_once "../config/database.php";
require_once "../config/auth.php";

requireRole('customer');

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function statusClass($status): string
{
    return match (strtolower(trim((string) $status))) {
        'open', 'baru', 'pending' => 'status-open',
        'process', 'proses', 'diproses', 'on progress', 'in progress' => 'status-process',
        'closed', 'selesai', 'resolved' => 'status-closed',
        'rejected', 'ditolak' => 'status-rejected',
        default => 'status-default',
    };
}

function priorityClass($priority): string
{
    return match (strtolower(trim((string) $priority))) {
        'high', 'tinggi' => 'priority-high',
        'medium', 'sedang' => 'priority-medium',
        'low', 'rendah' => 'priority-low',
        default => 'priority-normal',
    };
}

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    die('Session user tidak valid.');
}

$stmtCustomer = $conn->prepare(
    'SELECT id, nama FROM customers WHERE user_id = ? LIMIT 1'
);

if (!$stmtCustomer) {
    die('Query customer gagal: ' . $conn->error);
}

$stmtCustomer->bind_param('i', $userId);

if (!$stmtCustomer->execute()) {
    die('Execute query customer gagal: ' . $stmtCustomer->error);
}

$customer = $stmtCustomer->get_result()->fetch_assoc();
$stmtCustomer->close();

if (!$customer) {
    die('Data customer tidak ditemukan.');
}

$customerId = (int) $customer['id'];
$nama = trim((string) ($customer['nama'] ?? 'Customer')) ?: 'Customer';
$initial = strtoupper(substr($nama, 0, 1));

$stmtComplaint = $conn->prepare(
    'SELECT * FROM complaint WHERE id_customer = ? ORDER BY id DESC'
);

if (!$stmtComplaint) {
    die('Query complaint gagal: ' . $conn->error);
}

$stmtComplaint->bind_param('i', $customerId);

if (!$stmtComplaint->execute()) {
    die('Execute query complaint gagal: ' . $stmtComplaint->error);
}

$resultComplaint = $stmtComplaint->get_result();
$complaints = [];
$totalOpen = 0;
$totalProcess = 0;
$totalResolved = 0;

while ($row = $resultComplaint->fetch_assoc()) {
    $complaints[] = $row;
    $status = strtolower(trim((string) ($row['status'] ?? '')));

    if (in_array($status, ['open', 'baru', 'pending'], true)) {
        $totalOpen++;
    } elseif (in_array($status, ['process', 'proses', 'diproses', 'on progress', 'in progress'], true)) {
        $totalProcess++;
    } elseif (in_array($status, ['closed', 'selesai', 'resolved'], true)) {
        $totalResolved++;
    }
}

$stmtComplaint->close();
$totalComplaint = count($complaints);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Saya | Customer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/customer-dashboard.css">
    <link rel="stylesheet" href="assets/css/complaint.css">
</head>
<body>
    <main class="complaint-page">
        <header class="complaint-header">
            <div>
                <div class="breadcrumb-text"><i class="bi bi-ticket-detailed"></i> Customer Portal</div>
                <h1>Complaint Saya</h1>
                <p>Lihat dan pantau laporan complaint Anda.</p>
            </div>
            <div class="header-actions">
                <a href="complaint_create.php" class="btn-add">
                    <i class="bi bi-plus-lg"></i> Buat Complaint
                </a>
            </div>
        </header>

        <section class="complaint-stat-grid" aria-label="Ringkasan complaint">
            <div class="complaint-stat-card"><div class="complaint-stat-icon icon-blue"><i class="bi bi-ticket-perforated"></i></div><div class="stat-content"><span>Total Complaint</span><strong><?= number_format($totalComplaint) ?></strong><small>Semua laporan</small></div></div>
            <div class="complaint-stat-card"><div class="complaint-stat-icon icon-orange"><i class="bi bi-clock-history"></i></div><div class="stat-content"><span>Menunggu</span><strong><?= number_format($totalOpen) ?></strong><small>Menunggu ditangani</small></div></div>
            <div class="complaint-stat-card"><div class="complaint-stat-icon icon-blue"><i class="bi bi-arrow-repeat"></i></div><div class="stat-content"><span>Diproses</span><strong><?= number_format($totalProcess) ?></strong><small>Sedang ditangani</small></div></div>
            <div class="complaint-stat-card"><div class="complaint-stat-icon icon-green"><i class="bi bi-check-circle"></i></div><div class="stat-content"><span>Selesai</span><strong><?= number_format($totalResolved) ?></strong><small>Complaint selesai</small></div></div>
        </section>

        <section class="complaint-card">
            <div class="complaint-card-header">
                <div><h3>Daftar Complaint</h3><p>Riwayat laporan complaint Anda</p></div>
            </div>
            <div class="table-wrapper">
                <table class="complaint-table">
                    <thead><tr><th>#</th><th>Kode</th><th>Subject</th><th>Prioritas</th><th>Status</th><th>Tanggal</th></tr></thead>
                    <tbody>
                    <?php if (!$complaints): ?>
                        <tr><td colspan="6" class="empty-state"><div class="empty-icon"><i class="bi bi-inbox"></i></div><strong>Belum ada complaint</strong><span>Complaint yang Anda buat akan tampil di sini.</span></td></tr>
                    <?php else: ?>
                        <?php foreach ($complaints as $index => $complaint): ?>
                            <?php
                            $status = (string) ($complaint['status'] ?? 'Unknown');
                            $priority = (string) ($complaint['priority'] ?? 'Normal');
                            $date = $complaint['created_at'] ?? $complaint['tanggal'] ?? '-';
                            ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td class="complaint-code"><?= e($complaint['complaint_code'] ?? $complaint['kode'] ?? '-') ?></td>
                                <td class="subject-cell"><strong><?= e($complaint['subject'] ?? $complaint['judul'] ?? '-') ?></strong></td>
                                <td><span class="priority-badge <?= e(priorityClass($priority)) ?>"><span class="priority-dot"></span><?= e(ucwords($priority)) ?></span></td>
                                <td><span class="status-badge <?= e(statusClass($status)) ?>"><span class="status-dot"></span><?= e(ucwords($status)) ?></span></td>
                                <td class="complaint-date"><i class="bi bi-calendar3"></i><?= e($date) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <footer class="complaint-card-footer"><span class="footer-info"><i class="bi bi-info-circle"></i> Menampilkan complaint milik <?= e($nama) ?></span><span><?= e($initial) ?></span></footer>
        </section>
    </main>
</body>
</html>
