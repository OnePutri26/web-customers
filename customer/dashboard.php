<?php

require_once "../config/database.php";
require_once "../config/auth.php";

requireRole('customer');

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT *
     FROM customers
     WHERE user_id = ?"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$customer = $stmt->get_result()->fetch_assoc();

$customerId = $customer['id'];

$stmt = $conn->prepare(
    "SELECT COUNT(*) total
     FROM instalasi
     WHERE id_customer = ?"
);

$stmt->bind_param("i", $customerId);
$stmt->execute();

$totalInstalasi =
    $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare(
    "SELECT COUNT(*) total
     FROM complaint
     WHERE id_customer = ?
     AND status NOT IN ('closed','resolved')"
);

$stmt->bind_param("i", $customerId);
$stmt->execute();

$totalComplaint =
    $stmt->get_result()->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>Dashboard Customer</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet"
>

</head>

<body>

<nav class="navbar navbar-dark bg-primary">

<div class="container">

<span class="navbar-brand">
📡 WiFi Customer
</span>

<div class="text-white">
    Profile Saya
</div>

<a href="profile.php" class="text-white text-decoration-none">
    👤 Profile Saya
</a>

<?= htmlspecialchars($customer['nama']) ?>

<a
href="../logout.php"
class="btn btn-sm btn-light ms-2"
>
Logout
</a>

</div>

</div>

</nav>

<div class="container py-4">

<h3>
Halo, <?= htmlspecialchars($customer['nama']) ?> 👋
</h3>

<p class="text-muted">
Selamat datang di layanan customer WiFi.
</p>

<div class="row g-3">

<div class="col-md-4">

<div class="card shadow-sm border-0">

<div class="card-body">

<h6>Status internet</h6>

<h2>
<?= $totalInstalasi ?>
</h2>

<a
href="installation.php"
class="btn btn-primary"
>
Lihat
</a>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card shadow-sm border-0">

<div class="card-body">

<h6>🎫 Laporan Gangguan</h6>

<h2>
<?= $totalComplaint ?>
</h2>

<a
href="complaint.php"
class="btn btn-danger"
>
Lihat Complaint
</a>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card shadow-sm border-0">

<div class="card-body">

<h6>🎫 Paket Internet</h6>

<h2>
<?= $totalComplaint ?>
</h2>

<a
href="complaint.php"
class="btn btn-danger"
>
Lihat Complaint
</a>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card shadow-sm border-0">

<div class="card-body">

<h6>Customer ID</h6>

<h2>
<?= htmlspecialchars($customer['kode_pelanggan']) ?>
</h2>

</div>

</div>

</div>

</div>

<div class="row mt-4">

<div class="col-md-6">

<div class="card">

<div class="card-body">

<h5>📡 Pemasangan Baru</h5>

<p>
Ingin memasang WiFi baru?
</p>

<a
href="installation_create.php"
class="btn btn-primary"
>
Ajukan Pemasangan
</a>

</div>

</div>

</div>

<div class="col-md-6">

<div class="card">

<div class="card-body">

<h5> 🔧 Buat Tiket Gangguan</h5>

<p>
Internet bermasalah?
</p>

<a
href="complaint_create.php"
class="btn btn-danger"
>
Buat Complaint
</a>

</div>

</div>

</div>

</div>

</div>

</body>
</html>