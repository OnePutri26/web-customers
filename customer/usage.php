<?php

session_start();

require_once "../config/database.php";

$userId = 0;

if (isset($_SESSION['id_user'])) {
    $userId = (int) $_SESSION['id_user'];
} elseif (isset($_SESSION['user_id'])) {
    $userId = (int) $_SESSION['user_id'];
}

if ($userId <= 0) {
    header("Location: ../login.php");
    exit;
}

$customerName = $_SESSION['nama'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? 'Pelanggan';
$today = date('Y-m-d');
$sevenDaysAgo = date('Y-m-d', strtotime('-6 days'));

$downloadUsed = 0;
$uploadUsed   = 0;
$totalUsed    = 0;

$sqlSummary = "
    SELECT
        COALESCE(SUM(download_gb), 0) AS download_used,
        COALESCE(SUM(upload_gb), 0) AS upload_used,
        COALESCE(SUM(total_gb), 0) AS total_used
    FROM usage_data
    WHERE id_user = ?
    AND tanggal BETWEEN ? AND ?
";

$stmtSummary = mysqli_prepare($conn, $sqlSummary);

if ($stmtSummary) {

    mysqli_stmt_bind_param(
        $stmtSummary,
        "iss",
        $userId,
        $sevenDaysAgo,
        $today
    );

    mysqli_stmt_execute($stmtSummary);

    $resultSummary = mysqli_stmt_get_result($stmtSummary);

    if ($resultSummary && $row = mysqli_fetch_assoc($resultSummary)) {

        $downloadUsed = (float) $row['download_used'];
        $uploadUsed   = (float) $row['upload_used'];
        $totalUsed    = (float) $row['total_used'];
    }

    mysqli_stmt_close($stmtSummary);
}

$usageData = [];

for ($i = 6; $i >= 0; $i--) {

    $date = date('Y-m-d', strtotime("-$i days"));

    $usageData[$date] = [
        'day'      => date('D', strtotime($date)),
        'download' => 0,
        'upload'   => 0
    ];
}

$sqlChart = "
    SELECT
        tanggal,
        COALESCE(SUM(download_gb), 0) AS download_gb,
        COALESCE(SUM(upload_gb), 0) AS upload_gb
    FROM usage_data
    WHERE id_user = ?
    AND tanggal BETWEEN ? AND ?
    GROUP BY tanggal
    ORDER BY tanggal ASC
";

$stmtChart = mysqli_prepare($conn, $sqlChart);

if ($stmtChart) {

    mysqli_stmt_bind_param(
        $stmtChart,
        "iss",
        $userId,
        $sevenDaysAgo,
        $today
    );

    mysqli_stmt_execute($stmtChart);

    $resultChart = mysqli_stmt_get_result($stmtChart);

    if ($resultChart) {

        while ($row = mysqli_fetch_assoc($resultChart)) {

            $tanggal = $row['tanggal'];

            if (isset($usageData[$tanggal])) {

                $usageData[$tanggal]['download'] =
                    (float) $row['download_gb'];

                $usageData[$tanggal]['upload'] =
                    (float) $row['upload_gb'];
            }
        }
    }

    mysqli_stmt_close($stmtChart);
}

$usageHistory = [];

$sqlHistory = "
    SELECT
        tanggal,
        download_gb,
        upload_gb,
        total_gb
    FROM usage_data
    WHERE id_user = ?
    ORDER BY tanggal DESC
    LIMIT 10
";

$stmtHistory = mysqli_prepare($conn, $sqlHistory);

if ($stmtHistory) {

    mysqli_stmt_bind_param(
        $stmtHistory,
        "i",
        $userId
    );

    mysqli_stmt_execute($stmtHistory);

    $resultHistory = mysqli_stmt_get_result($stmtHistory);

    if ($resultHistory) {

        while ($row = mysqli_fetch_assoc($resultHistory)) {

            $usageHistory[] = $row;
        }
    }

    mysqli_stmt_close($stmtHistory);
}

$quota = 500;

$percentage = 0;

if ($quota > 0) {

    $percentage = ($totalUsed / $quota) * 100;

    if ($percentage > 100) {
        $percentage = 100;
    }
}

$maxUsage = 1;

foreach ($usageData as $data) {

    $dailyMaximum = max($data['download'], $data['upload']);

    if ($dailyMaximum > $maxUsage) {
        $maxUsage = $dailyMaximum;
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Pemakaian Internet</title>

    <link
        rel="stylesheet"
        href="assets/css/customer-dashboard.css"
    >

    <link
        rel="stylesheet"
        href="assets/css/usage.css"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

</head>

<body>

<div class="dashboard-container">


    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="sidebar-logo">

            <div class="logo-icon">
                <i class="bi bi-wifi"></i>
            </div>

            <div class="logo-text">
                <h5>WiFi Management</h5>
                <span>Customer Portal</span>
            </div>

        </div>


        <nav class="sidebar-menu">

            <a href="dashboard.php" class="menu-item">
                <i class="bi bi-grid-fill"></i>
                <span>Dashboard</span>
            </a>

            <a href="billing.php" class="menu-item">
                <i class="bi bi-credit-card-fill"></i>
                <span>Tagihan</span>
            </a>

            <a href="usage.php" class="menu-item active">
                <i class="bi bi-bar-chart-fill"></i>
                <span>Pemakaian</span>
            </a>

            <a href="speedtest.php" class="menu-item">
                <i class="bi bi-speedometer2"></i>
                <span>Speed Test</span>
            </a>

            <a href="complaint.php" class="menu-item">
                <i class="bi bi-tools"></i>
                <span>Pengaduan</span>
            </a>

            <a href="network_status.php" class="menu-item">
                <i class="bi bi-globe2"></i>
                <span>Status Jaringan</span>
            </a>

            <a href="notifikasi.php" class="menu-item">
                <i class="bi bi-bell-fill"></i>
                <span>Notifikasi</span>
            </a>

            <a href="profile.php" class="menu-item">
                <i class="bi bi-person-circle"></i>
                <span>Profil</span>
            </a>

        </nav>


        <div class="sidebar-bottom">

            <div class="user-mini">

                <div class="user-avatar">
                    <?= htmlspecialchars(strtoupper(substr($customerName, 0, 1))) ?>
                </div>

                <div>

                    <strong>
                        <?= htmlspecialchars($customerName) ?>
                    </strong>

                    <small>
                        Pelanggan
                    </small>

                </div>

            </div>

        </div>

    </aside>



    <!-- MAIN CONTENT -->

    <main class="main-content">


        <!-- TOPBAR -->

        <header class="topbar">

            <div>

                <h4>Pemakaian Internet</h4>

                <span>
                    Pantau penggunaan internet kamu
                    selama 7 hari terakhir.
                </span>

            </div>


            <div class="topbar-right">

                <a
                    href="notifikasi.php"
                    class="notification"
                >

                    <i class="bi bi-bell"></i>

                </a>


                <a href="profile.php" class="top-profile">

                    <div class="top-avatar">
                        <?= htmlspecialchars(strtoupper(substr($customerName, 0, 1))) ?>
                    </div>

                    <div class="top-user">
                        <strong><?= htmlspecialchars($customerName) ?></strong>
                        <span>Customer</span>
                    </div>

                </a>

            </div>

        </header>



        <div class="usage-page">

        <!-- SUMMARY -->

        <section class="summary-grid">


            <div class="summary-card download">

                <div class="summary-icon">

                    <i class="bi bi-download"></i>

                </div>

                <div class="summary-info">

                    <span>Download</span>

                    <strong>
                        <?= number_format($downloadUsed, 1, ',', '.') ?>
                        GB
                    </strong>

                    <small>
                        7 hari terakhir
                    </small>

                </div>

            </div>



            <div class="summary-card upload">

                <div class="summary-icon">

                    <i class="bi bi-upload"></i>

                </div>

                <div class="summary-info">

                    <span>Upload</span>

                    <strong>
                        <?= number_format($uploadUsed, 1, ',', '.') ?>
                        GB
                    </strong>

                    <small>
                        7 hari terakhir
                    </small>

                </div>

            </div>



            <div class="summary-card total">

                <div class="summary-icon">

                    <i class="bi bi-database"></i>

                </div>

                <div class="summary-info">

                    <span>Total Pemakaian</span>

                    <strong>
                        <?= number_format($totalUsed, 1, ',', '.') ?>
                        GB
                    </strong>

                    <small>
                        <?= number_format($percentage, 1, ',', '.') ?>%
                        dari quota
                    </small>

                </div>

            </div>

        </section>



        <!-- QUOTA -->

        <section class="content-card quota-card">

            <div class="card-header">

                <div>

                    <h2>Penggunaan Quota</h2>

                    <p>
                        Total pemakaian dalam periode
                        7 hari terakhir.
                    </p>

                </div>

                <strong class="quota-value">

                    <?= number_format($totalUsed, 1, ',', '.') ?>

                    /

                    <?= number_format($quota, 0, ',', '.') ?>

                    GB

                </strong>

            </div>


            <div class="progress-wrapper">

                <div class="progress-bar">

                    <div
                        class="progress-fill"
                        style="width: <?= $percentage ?>%;"
                    ></div>

                </div>

            </div>


            <div class="quota-footer">

                <span>
                    0 GB
                </span>

                <span>
                    <?= number_format($percentage, 1, ',', '.') ?>%
                </span>

                <span>
                    <?= number_format($quota, 0, ',', '.') ?> GB
                </span>

            </div>

        </section>



        <!-- CHART -->

        <section class="content-card chart-card">

            <div class="card-header">

                <div>

                    <h2>Grafik Pemakaian</h2>

                    <p>
                        Pemakaian download dan upload
                        selama 7 hari terakhir.
                    </p>

                </div>

            </div>


            <div class="chart-container">

                <?php foreach ($usageData as $tanggal => $data): ?>

                    <?php

                    $downloadHeight =
                        ($data['download'] / $maxUsage) * 100;

                    $uploadHeight =
                        ($data['upload'] / $maxUsage) * 100;

                    $dailyTotal = $data['download'] + $data['upload'];

                    ?>

                    <div class="chart-column">

                        <div class="chart-value">

                            <?= number_format($dailyTotal, 1, ',', '.') ?> GB

                        </div>


                        <div class="bar-wrapper">

                            <div class="usage-bar-group">
                                <div
                                    class="usage-bar download-bar"
                                    style="height: <?= $downloadHeight ?>%;"
                                    title="Download: <?= number_format($data['download'], 1, ',', '.') ?> GB"
                                ></div>
                                <div
                                    class="usage-bar upload-bar"
                                    style="height: <?= $uploadHeight ?>%;"
                                    title="Upload: <?= number_format($data['upload'], 1, ',', '.') ?> GB"
                                ></div>
                            </div>

                        </div>


                        <span class="chart-day">

                            <?= date(
                                'd M',
                                strtotime($tanggal)
                            ) ?>

                        </span>

                    </div>

                <?php endforeach; ?>

            </div>


            <div class="chart-legend">

                <span>

                    <i class="legend-download"></i>

                    Download

                </span>


                <span>

                    <i class="legend-upload"></i>

                    Upload

                </span>

            </div>

        </section>



        <!-- INFO -->

        <section class="info-grid">


            <div class="info-card">

                <div class="info-icon">

                    <i class="bi bi-speedometer2"></i>

                </div>

                <div>

                    <span>Paket Internet</span>

                    <strong>Home Internet</strong>

                </div>

            </div>



            <div class="info-card">

                <div class="info-icon">

                    <i class="bi bi-wifi"></i>

                </div>

                <div>

                    <span>Status Koneksi</span>

                    <strong class="online">
                        Online
                    </strong>

                </div>

            </div>



            <div class="info-card">

                <div class="info-icon">

                    <i class="bi bi-clock-history"></i>

                </div>

                <div>

                    <span>Update Data</span>

                    <strong>
                        <?= date('d M Y H:i') ?>
                    </strong>

                </div>

            </div>


        </section>



        <!-- HISTORY -->

        <section class="content-card history-card">

            <div class="card-header">

                <div>

                    <h2>Riwayat Pemakaian</h2>

                    <p>
                        Data pemakaian internet terbaru.
                    </p>

                </div>

            </div>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>Tanggal</th>

                            <th>Download</th>

                            <th>Upload</th>

                            <th>Total</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (count($usageHistory) > 0): ?>

                        <?php foreach ($usageHistory as $history): ?>

                            <tr>

                                <td>

                                    <?= date(
                                        'd M Y',
                                        strtotime($history['tanggal'])
                                    ) ?>

                                </td>

                                <td>

                                    <span class="download-text">

                                        <i class="bi bi-download"></i>

                                        <?= number_format(
                                            (float) $history['download_gb'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>

                                        GB

                                    </span>

                                </td>

                                <td>

                                    <span class="upload-text">

                                        <i class="bi bi-upload"></i>

                                        <?= number_format(
                                            (float) $history['upload_gb'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>

                                        GB

                                    </span>

                                </td>

                                <td>

                                    <strong>

                                        <?= number_format(
                                            (float) $history['total_gb'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>

                                        GB

                                    </strong>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="4"
                                class="empty-state"
                            >

                                <i class="bi bi-database"></i>

                                <span>
                                    Belum ada data pemakaian.
                                </span>

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>



        <!-- NOTE -->

        <div class="usage-note">

            <i class="bi bi-info-circle"></i>

            <span>
                Data pemakaian dapat mengalami keterlambatan
                sinkronisasi beberapa menit.
            </span>

        </div>

        </div>


    </main>

</div>

</body>

</html>
