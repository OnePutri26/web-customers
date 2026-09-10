<?php

require_once "../config/database.php";
require_once "../config/auth.php";

requireRole('customer');

$userId = $_SESSION['user_id'];

$result = mysqli_query(
    $conn,
    "SELECT * FROM packages
     WHERE status = 'aktif'
     ORDER BY harga ASC"
);

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Pilih Paket WiFi</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="assets/css/packages.css">

</head>

<body>

<div class="packages-page">
    <nav class="packages-navbar">
        <a href="subscription.php" class="back-dashboard" aria-label="Kembali ke langganan"><i class="bi bi-arrow-left"></i></a>
        <div class="nav-title"><strong>Paket WiFi</strong><span>Pilih layanan untuk kebutuhanmu</span></div>
        <a href="subscription.php" class="dashboard-link"><i class="bi bi-person-check-fill"></i> Langganan</a>
    </nav>

    <main class="container">

    <div class="header">

        <span class="eyebrow"><i class="bi bi-stars"></i> PILIHAN TERBAIK UNTUKMU</span>
        <h1>Pilih paket WiFi</h1>

        <p>
            Pilih paket internet yang sesuai dengan kebutuhan Anda.
        </p>

    </div>

    <div class="packages">

        <?php while ($package = mysqli_fetch_assoc($result)): ?>

            <div class="package-card">

                <div class="package-icon">
                    <i class="bi bi-wifi"></i>
                </div>

                <h2>
                    <?= htmlspecialchars($package['nama_paket']) ?>
                </h2>

                <div class="speed">
                    <?= htmlspecialchars($package['kecepatan']) ?>
                </div>

                <div class="price">
                    Rp <?= number_format($package['harga'], 0, ',', '.') ?>
                    <span>/bulan</span>
                </div>

                <p>
                    <?= htmlspecialchars($package['deskripsi']) ?>
                </p>

                <form action="payment.php" method="POST">

                    <input
                        type="hidden"
                        name="package_id"
                        value="<?= $package['id'] ?>"
                    >

                    <button type="submit">
                        <i class="bi bi-arrow-right-circle-fill"></i> Pilih Paket
                    </button>

                </form>

            </div>

        <?php endwhile; ?>

    </main>
</div>

</body>
</html>