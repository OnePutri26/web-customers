<?php

require_once "../config/database.php";
require_once "../config/auth.php";

requireRole('customer');

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT id, nama, alamat
     FROM customers
     WHERE user_id = ?"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$customer = $stmt->get_result()->fetch_assoc();

$packages = $conn->query(
    "SELECT *
     FROM instalasi
     WHERE status = 'active'
     ORDER BY speed_mbps"
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $packageId =
        intval($_POST['id_paket']);

    $alamat =
        trim($_POST['alamat']);

    $date =
        $_POST['tgl_permintaan'];

    $code =
        "INS" . date("YmdHis") . rand(10, 99);

    $stmt = $conn->prepare(
        "INSERT INTO instalasi
        (
            id_customer,
            id_paket,
            kode_pelanggan,
            alamat,
            tgl_permintaan
        )
        VALUES (?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "iisss",
        $customer['id'],
        $packageId,
        $code,
        $address,
        $date
    );

    $stmt->execute();

    header("Location: instalasi.php");
    exit;
}
?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<title>Pengajuan Pemasangan</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet"
>

</head>

<body>

<div class="container py-4">

<h3>
📡 Pengajuan Pemasangan WiFi
</h3>

<div class="card mt-3">

<div class="card-body">

<form method="POST">

<label class="form-label">
Pilih Paket
</label>

<select
name="package_id"
class="form-select mb-3"
required
>

<option value="">
-- Pilih Paket --
</option>

<?php while ($package = $packages->fetch_assoc()): ?>

<option value="<?= $package['id'] ?>">

<?= htmlspecialchars($package['package_name']) ?>

-
<?= $package['speed_mbps'] ?> Mbps

-
Rp <?= number_format($package['price'], 0, ',', '.') ?>

</option>

<?php endwhile; ?>

</select>

<label>
Alamat Pemasangan
</label>

<textarea
name="alamat"
class="form-control mb-3"
required
><?= htmlspecialchars($customer['alamat']) ?></textarea>

<label>
Tanggal yang diinginkan
</label>

<input
type="date"
name="requested_date"
class="form-control mb-3"
required
>

<button class="btn btn-primary">
Kirim Pengajuan
</button>

<a
href="dashboard.php"
class="btn btn-secondary"
>
Kembali
</a>

</form>

</div>

</div>

</div>

</body>

</html>