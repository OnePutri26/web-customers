<?php

session_start();

require_once "config/database.php";

$error = "";
$success = "";

$username  = "";
$nama      = "";
$telephone = "";
$email     = "";
$alamat    = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ambil data dengan aman
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $nama      = trim($_POST['nama'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $alamat    = trim($_POST['alamat'] ?? '');

    // Validasi
    if (
        $username === '' ||
        $password === '' ||
        $nama === '' ||
        $telephone === '' ||
        $alamat === ''
    ) {

        $error = "Semua field wajib diisi.";

    } elseif (strlen($password) < 6) {

        $error = "Password minimal 6 karakter.";

    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Format email tidak valid.";

    } else {

        // Cek username
        $check = $conn->prepare(
            "SELECT id
             FROM users
             WHERE username = ?
             LIMIT 1"
        );

        if (!$check) {

            $error = "Terjadi kesalahan database.";

        } else {

            $check->bind_param("s", $username);
            $check->execute();

            $result = $check->get_result();

            if ($result->num_rows > 0) {

                $error = "Username sudah digunakan.";

            } else {

                // Hash password
                $hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Buat user
                $stmt = $conn->prepare(
                    "INSERT INTO users
                    (username, password, role)
                    VALUES (?, ?, 'customer')"
                );

                if (!$stmt) {

                    $error = "Gagal membuat akun.";

                } else {

                    $stmt->bind_param(
                        "ss",
                        $username,
                        $hash
                    );

                    if ($stmt->execute()) {

                        $userId = $stmt->insert_id;

                        // Buat kode pelanggan
                        $customerCode =
                            "CUS" .
                            date("Ymd") .
                            rand(100, 999);

                        // Simpan data customer
                        $customerStmt = $conn->prepare(
                            "INSERT INTO customers
                            (
                                user_id,
                                kode_pelanggan,
                                nama,
                                telephone,
                                email,
                                alamat
                            )
                            VALUES (?, ?, ?, ?, ?, ?)"
                        );

                        if (!$customerStmt) {

                            // Jika gagal membuat data customer,
                            // hapus user yang sudah dibuat
                            $delete = $conn->prepare(
                                "DELETE FROM users WHERE id = ?"
                            );

                            $delete->bind_param("i", $userId);
                            $delete->execute();
                            $delete->close();

                            $error = "Gagal menyimpan data customer.";

                        } else {

                            $customerStmt->bind_param(
                                "isssss",
                                $userId,
                                $customerCode,
                                $nama,
                                $telephone,
                                $email,
                                $alamat
                            );

                            if ($customerStmt->execute()) {

                                $success =
                                    "Registrasi berhasil. Silakan login.";

                                // Kosongkan form
                                $username  = "";
                                $nama      = "";
                                $telephone = "";
                                $email     = "";
                                $alamat    = "";

                            } else {

                                // Hapus user jika customer gagal disimpan
                                $delete = $conn->prepare(
                                    "DELETE FROM users WHERE id = ?"
                                );

                                $delete->bind_param("i", $userId);
                                $delete->execute();
                                $delete->close();

                                $error =
                                    "Data customer gagal disimpan.";
                            }

                            $customerStmt->close();
                        }

                    } else {

                        $error = "Gagal membuat akun.";
                    }

                    $stmt->close();
                }
            }

            $check->close();
        }
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

    <title>Register Customer - WiFi Management System</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Register CSS -->
    <link
        rel="stylesheet"
        href="assets/css/register.css"
    >

</head>

<body class="bg-light">

<div class="container">

    <div class="row justify-content-center py-5">

        <div class="col-md-6">

            <div class="card shadow border-0">

                <div class="card-body p-4">

                    <h3 class="text-center mb-2">
                        📡 WiFi Management System
                    </h3>

                    <p class="text-center text-muted mb-4">
                        Registrasi Customer
                    </p>

                    <?php if ($error !== ''): ?>

                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error) ?>
                        </div>

                <?php endif; ?>


                <!-- Success -->

                <?php if ($success !== ''): ?>

                    <div class="alert alert-success custom-alert">

                        <span class="alert-icon">
                            ✓
                        </span>

                        <span>
                            <?= htmlspecialchars($success) ?>
                        </span>

                    </div>

                <?php endif; ?>


                <form method="POST" action="">


                    <!-- =====================================
                         PERSONAL DATA
                    ====================================== -->

                    <div class="section-title">

                        <span class="section-number">
                            01
                        </span>

                        <div>
                            <strong>Data Pribadi</strong>

                            <small>
                                Informasi dasar customer
                            </small>
                        </div>

                    </div>


                        <!-- Nama -->
                        <div class="mb-3">

                            <label
                                for="nama"
                                class="form-label"
                            >
                                Nama Lengkap
                            </label>

                            <input
                                type="text"
                                id="nama"
                                name="nama"
                                class="form-control"
                                placeholder="Masukkan nama lengkap"
                                value="<?= htmlspecialchars($nama) ?>"
                                required
                            >

                        </div>

                    </div>


                    <!-- Telephone -->

                    <div class="form-group">

                            <label
                                for="telephone"
                                class="form-label"
                            >
                                Nomor Telepon
                            </label>

                            <input
                                type="text"
                                id="telephone"
                                name="telephone"
                                class="form-control"
                                placeholder="081234567890"
                                value="<?= htmlspecialchars($telephone) ?>"
                                required
                            >

                        </div>

                    </div>


                    <!-- Email -->

                    <div class="form-group">

                        <label
                            for="email"
                            class="form-label"
                        >
                            Email
                            <span class="optional">
                                Opsional
                            </span>
                        </label>

                        <div class="input-wrapper">

                            <span class="input-icon">
                                ✉️
                            </span>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                placeholder="nama@email.com"
                                value="<?= htmlspecialchars($email) ?>"
                            >

                        </div>

                    </div>


                    <!-- Alamat -->

                    <div class="form-group">

                        <label
                            for="alamat"
                            class="form-label"
                        >
                            Alamat Lengkap
                        </label>

                        <div class="input-wrapper textarea-wrapper">

                            <span class="input-icon textarea-icon">
                                📍
                            </span>

                            <textarea
                                id="alamat"
                                name="alamat"
                                class="form-control"
                                rows="3"
                                placeholder="Masukkan alamat lengkap"
                                required
                            ><?= htmlspecialchars($alamat) ?></textarea>

                        </div>

                    </div>


                    <!-- =====================================
                         ACCOUNT DATA
                    ====================================== -->

                    <div class="section-title account-section">

                        <span class="section-number">
                            02
                        </span>

                        <div>
                            <strong>Data Akun</strong>

                            <small>
                                Digunakan untuk login
                            </small>
                        </div>

                    </div>


                    <!-- Username -->

                    <div class="form-group">

                        <label
                            for="username"
                            class="form-label"
                        >
                            Username
                        </label>

                        <div class="input-wrapper">

                            <span class="input-icon">
                                @
                            </span>

                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="form-control"
                                placeholder="Masukkan username"
                                value="<?= htmlspecialchars($username) ?>"
                                required
                                autocomplete="username"
                            >

                        </div>

                    </div>


                    <!-- Password -->

                    <div class="form-group">

                        <label
                            for="password"
                            class="form-label"
                        >
                            Password
                        </label>

                        <div class="input-wrapper">

                            <span class="input-icon">
                                🔒
                            </span>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                placeholder="Minimal 6 karakter"
                                minlength="6"
                                required
                                autocomplete="new-password"
                            >

                        </div>

                        <div class="password-hint">
                            Minimal 6 karakter
                        </div>

                    </div>


                    <!-- Submit -->

                    <button
                        type="submit"
                        class="register-btn"
                    >

                        <span>
                            Buat Akun
                        </span>

                        <span class="btn-arrow">
                            →
                        </span>

                    </button>


                </form>


                <!-- Login -->

                <div class="login-link">

                    Sudah punya akun?

                    <a href="login.php">
                        Login di sini
                    </a>

                </div>


                <div class="security-text">

                    🔐 Registrasi aman & terproteksi

                </div>


            </div>

        </div>

    </div>

</body>

</html>
