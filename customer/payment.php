<?php

require_once "../config/database.php";
require_once "../config/auth.php";

requireRole('customer');

$userId = (int) $_SESSION['user_id'];
$packageId = (int) ($_POST['package_id'] ?? $_GET['package_id'] ?? 0);
$isRenewal = (int) ($_POST['renewal'] ?? $_GET['renewal'] ?? 0) === 1;

if ($packageId <= 0) {
    header("Location: packages.php");
    exit;
}

$packageStmt = $conn->prepare(
    "SELECT id, nama_paket, kecepatan, harga, deskripsi
     FROM packages
     WHERE id = ? AND status = 'aktif'
     LIMIT 1"
);
$packageStmt->bind_param("i", $packageId);
$packageStmt->execute();
$package = $packageStmt->get_result()->fetch_assoc();
$packageStmt->close();

if (!$package) {
    http_response_code(404);
    exit('Paket tidak ditemukan.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $method = trim($_POST['method'] ?? 'transfer_bank');
    $allowedMethods = ['transfer_bank', 'e_wallet', 'virtual_account'];

    if (!in_array($method, $allowedMethods, true)) {
        $error = 'Metode pembayaran tidak valid.';
    } else {
        try {
            $conn->begin_transaction();

            $existingStmt = $conn->prepare(
                "SELECT id, status
                 FROM subscriptions
                 WHERE id_user = ?
                 ORDER BY id DESC
                 LIMIT 1
                 FOR UPDATE"
            );
            $existingStmt->bind_param("i", $userId);
            $existingStmt->execute();
            $existing = $existingStmt->get_result()->fetch_assoc();
            $existingStmt->close();

            if ($existing && $existing['status'] === 'active' && !$isRenewal) {
                $conn->rollback();
                header("Location: dashboard.php");
                exit;
            }

            $subscriptionId = (int) ($existing['id'] ?? 0);
            if ($subscriptionId > 0) {
                $subscriptionStmt = $conn->prepare(
                    "UPDATE subscriptions
                     SET id_package = ?, status = 'active',
                         tgl_pengajuan = NOW(), tgl_aktif = NOW(),
                         tgl_berakhir = DATE_ADD(NOW(), INTERVAL 30 DAY)
                     WHERE id = ?"
                );
                $subscriptionStmt->bind_param("ii", $packageId, $subscriptionId);
            } else {
                $subscriptionStmt = $conn->prepare(
                    "INSERT INTO subscriptions
                     (id_user, id_package, status, tgl_pengajuan, tgl_aktif, tgl_berakhir)
                     VALUES (?, ?, 'active', NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))"
                );
                $subscriptionStmt->bind_param("ii", $userId, $packageId);
            }
            $subscriptionStmt->execute();
            if ($subscriptionId === 0) {
                $subscriptionId = $conn->insert_id;
            }
            $subscriptionStmt->close();

            $customerStmt = $conn->prepare("SELECT id FROM customers WHERE user_id = ? LIMIT 1");
            $customerStmt->bind_param("i", $userId);
            $customerStmt->execute();
            $customer = $customerStmt->get_result()->fetch_assoc();
            $customerStmt->close();

            if (!$customer) {
                throw new RuntimeException('Data customer tidak ditemukan.');
            }

            $invoice = 'INV' . date('YmdHis') . random_int(10, 99);
            $period = date('Y-m');
            $customerId = (int) $customer['id'];
            $amount = (float) $package['harga'];
            $note = 'Pembayaran subscription #' . $subscriptionId;

            $billingStmt = $conn->prepare(
                "INSERT INTO billing
                 (id_warga, invoice, period, jatuh_tempo, nominal, status, metode_pembayaran, tgl_bayar, catatan)
                 VALUES (?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 30 DAY), ?, 'lunas', ?, NOW(), ?)"
            );
            $billingStmt->bind_param("issdss", $customerId, $invoice, $period, $amount, $method, $note);
            $billingStmt->execute();
            $billingStmt->close();

            $conn->commit();
            header("Location: dashboard.php?payment=success");
            exit;
        } catch (Throwable $exception) {
            $conn->rollback();
            $error = 'Pembayaran gagal diproses. Silakan coba lagi.';
        }
    }
}

$customerName = $_SESSION['username'] ?? 'Customer';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Paket | Customer Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/packages.css">
    <style>
        .payment-page { min-height: 100vh; background: #f5f7fb; }
        .payment-box { max-width: 680px; margin: 0 auto; padding: 32px; border: 1px solid #e8ebf2; border-radius: 18px; background: #fff; box-shadow: 0 8px 28px rgba(15,23,42,.07); }
        .payment-box h1 { margin: 0 0 8px; color: #172033; font-size: 26px; }
        .payment-box > p { color: #667085; font-size: 13px; }
        .payment-package { display: flex; justify-content: space-between; gap: 20px; margin: 22px 0; padding: 18px; border-radius: 13px; color: #fff; background: linear-gradient(135deg, #2563eb, #4338ca); }
        .payment-package strong, .payment-package span { display: block; }
        .payment-package strong { font-size: 17px; }
        .payment-package span { margin-top: 5px; color: #dbeafe; font-size: 12px; }
        .payment-price { font-size: 18px; font-weight: 800; white-space: nowrap; }
        .method-label { display: block; margin-bottom: 10px; color: #344054; font-size: 12px; font-weight: 700; }
        .method-list { display: grid; gap: 9px; }
        .method-option { display: flex; align-items: center; gap: 10px; padding: 13px; border: 1px solid #e2e8f0; border-radius: 10px; color: #475569; font-size: 12px; cursor: pointer; }
        .method-option:has(input:checked) { border-color: #93c5fd; color: #2563eb; background: #eff6ff; }
        .payment-error { margin-bottom: 15px; padding: 12px; border-radius: 9px; color: #991b1b; background: #fee2e2; font-size: 12px; }
        .pay-submit { width: 100%; margin-top: 22px; padding: 13px; border: 0; border-radius: 10px; color: #fff; background: #2563eb; font-weight: 700; cursor: pointer; }
        @media (max-width: 600px) { .payment-box { padding: 24px 19px; } .payment-package { flex-direction: column; gap: 8px; } }
    </style>
</head>
<body>
<div class="payment-page">
    <nav class="packages-navbar"><a href="packages.php" class="back-dashboard"><i class="bi bi-arrow-left"></i></a><div class="nav-title"><strong>Pembayaran Paket</strong><span>Aktifkan layanan internet kamu</span></div><a href="subscription.php" class="dashboard-link"><i class="bi bi-person-check-fill"></i> Langganan</a></nav>
    <main class="container">
        <div class="payment-box">
            <span class="eyebrow"><i class="bi bi-shield-check"></i> PEMBAYARAN AMAN</span>
            <h1>Konfirmasi paket</h1>
            <p>Periksa detail paket dan pilih metode pembayaran untuk mengaktifkan layanan.</p>
            <?php if ($error): ?><div class="payment-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
            <div class="payment-package"><div><strong><?= htmlspecialchars($package['nama_paket']) ?></strong><span><i class="bi bi-lightning-charge-fill"></i> <?= htmlspecialchars($package['kecepatan']) ?></span></div><div class="payment-price">Rp <?= number_format($package['harga'], 0, ',', '.') ?><small>/bulan</small></div></div>
            <form method="POST">
                <input type="hidden" name="package_id" value="<?= $packageId ?>">
                <input type="hidden" name="renewal" value="<?= $isRenewal ? 1 : 0 ?>">
                <span class="method-label">Pilih metode pembayaran</span>
                <div class="method-list">
                    <label class="method-option"><input type="radio" name="method" value="transfer_bank" checked> <i class="bi bi-bank"></i> Transfer Bank</label>
                    <label class="method-option"><input type="radio" name="method" value="e_wallet"> <i class="bi bi-wallet2"></i> E-Wallet</label>
                    <label class="method-option"><input type="radio" name="method" value="virtual_account"> <i class="bi bi-credit-card"></i> Virtual Account</label>
                </div>
                <button type="submit" name="confirm_payment" class="pay-submit"><i class="bi bi-lock-fill"></i> Bayar dan Aktifkan Subscription</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
