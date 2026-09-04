<?php

require_once "../config/database.php";
require_once "../config/auth.php";

requireRole('customer');

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT c.*
     FROM complaint c
     JOIN customer cu
       ON cu.id = c.id_customer
     WHERE cu.user_id = ?
     ORDER BY c.created_at DESC"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$complaint = $stmt->get_result();
?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<title>Complaint Saya</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet"
>

</head>

<body>

<div class="container py-4">

<div class="d-flex justify-content-between">

<h3>🎫 Complaint Saya</h3>

<a
href="complaint_create.php"
class="btn btn-danger"
>
+ Complaint
</a>

</div>

<div class="table-responsive mt-3">

<table class="table table-bordered">

<thead>

<tr>

<th>Kode</th>
<th>Subject</th>
<th>Prioritas</th>
<th>Status</th>
<th>Tanggal</th>

</tr>

</thead>

<tbody>

<?php while ($row = $complaint->fetch_assoc()): ?>

<tr>

<td>
<?= htmlspecialchars($row['kode_pelanggan']) ?>
</td>

<td>
<?= htmlspecialchars($row['subject']) ?>
</td>

<td>

<span class="badge bg-warning text-dark">

<?= strtoupper($row['prioritas']) ?>

</span>

</td>

<td>

<span class="badge bg-primary">

<?= strtoupper($row['status']) ?>

</span>

</td>

<td>
<?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

</div>

</div>

</body>

</html>