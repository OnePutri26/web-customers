<?php

session_start();
require_once '../config/database.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$message = "";
$error = "";

/*
|--------------------------------------------------------------------------
| AMBIL DATA USER
|--------------------------------------------------------------------------
| SESUAIKAN nama kolom dengan database kamu.
*/

$stmt = $conn->prepare("
    SELECT id, username, email, telephone, alamat
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("Data user tidak ditemukan.");
}


/*
|--------------------------------------------------------------------------
| UPDATE PROFILE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username   = trim($_POST['username'] ?? '');
    $password  = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    // Validasi
    if ($username === '') {
        $error = "Username wajib diisi.";
    } elseif ($password === '') {
        $error = "Password wajib diisi.";
    } else {

        $update = $conn->prepare("
            UPDATE users
            SET username = ?,
                email = ?,
                telephone = ?,
                alamat = ?
            WHERE id = ?
        ");

        $update->bind_param(
            "ssssi",
            $username,
            $email,
            $telephone,
            $alamat,
            $user_id
        );

        if ($update->execute()) {

            // Update session nama jika digunakan di dashboard
            $_SESSION['nama'] = $username;

            $message = "Profile berhasil diperbarui.";

            // Update data yang ditampilkan
            $user['username'] = $username;
            $user['email'] = $email;
            $user['telephone'] = $telephone;
            $user['alamat'] = $alamat;

        } else {
            $error = "Gagal memperbarui profile.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Profile</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fb;
            font-family: Arial, sans-serif;
        }

        .profile-container {
            max-width: 650px;
            margin: 50px auto;
        }

        .profile-card {
            background: white;
            border-radius: 20px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,.08);
            overflow: hidden;
        }

        .profile-header {
            background: linear-gradient(
                135deg,
                #0d6efd,
                #0b5ed7
            );

            color: white;
            padding: 30px;
            text-align: center;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;

            margin: auto;
            margin-bottom: 15px;

            border-radius: 50%;

            background: white;
            color: #0d6efd;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 32px;
            font-weight: bold;
        }

        .profile-body {
            padding: 30px;
        }

        .form-label {
            font-weight: 600;
        }

        .btn-save {
            border-radius: 10px;
            padding: 10px 20px;
        }

        .btn-back {
            border-radius: 10px;
            padding: 10px 20px;
        }

    </style>

</head>

<body>

<div class="container">

    <div class="profile-container">

        <div class="card profile-card">

            <!-- HEADER -->

            <div class="profile-header">

                <div class="profile-avatar">

                    <?= strtoupper(
                        substr(
                            $user['username'] ?? 'U',
                            0,
                            1
                        )
                    ) ?>

                </div>

                <h4 class="mb-1">
                    Edit Profile
                </h4>

                <small>
                    Perbarui informasi profile kamu
                </small>

            </div>


            <!-- BODY -->

            <div class="profile-body">

                <?php if ($message): ?>

                    <div class="alert alert-success">
                        <?= htmlspecialchars($message) ?>
                    </div>

                <?php endif; ?>


                <?php if ($error): ?>

                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error) ?>
                    </div>

                <?php endif; ?>


                <form method="POST">


                    <!-- NAMA -->

                    <div class="mb-3">

                        <label class="form-label">
                            Nama Lengkap
                        </label>

                        <input
                            type="text"
                            name="username"
                            class="form-control"
                            value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                            required
                        >

                    </div>


                    <!-- EMAIL -->

                    <div class="mb-3">

                        <label class="form-label">
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                            required
                        >

                    </div>


                    <!-- NOMOR HP -->

                    <div class="mb-3">

                        <label class="form-label">
                            Nomor HP
                        </label>

                        <input
                            type="text"
                            name="telephone"
                            class="form-control"
                            value="<?= htmlspecialchars($user['telephone'] ?? '') ?>"
                            placeholder="08xxxxxxxxxx"
                        >

                    </div>


                    <!-- ALAMAT -->

                    <div class="mb-4">

                        <label class="form-label">
                            Alamat
                        </label>

                        <textarea
                            name="alamat"
                            class="form-control"
                            rows="3"
                            placeholder="Masukkan alamat"
                        ><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>

                    </div>


                    <!-- BUTTON -->

                    <div class="d-flex gap-2">

                        <button
                            type="submit"
                            class="btn btn-primary btn-save"
                        >
                            💾 Simpan Perubahan
                        </button>


                        <a
                            href="profile.php"
                            class="btn btn-outline-secondary btn-back"
                        >
                            Kembali
                        </a>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>

</body>

</html>
