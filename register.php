<?php

session_start();

require_once "config/database.php";

require_once "config/database.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

echo "<pre>";

$result = $conn->query("SELECT DATABASE() AS db");
$row = $result->fetch_assoc();

echo "DATABASE YANG DIPAKAI PHP: ";
print_r($row);

echo "\n\nSTRUKTUR CUSTOMERS:\n";

$result = $conn->query("DESCRIBE customers");

while ($row = $result->fetch_assoc()) {
    print_r($row);
}

echo "</pre>";

exit;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$error =
    "REGISTRASI GAGAL: " .
    $e->getMessage() .
    " | FILE: " .
    basename($e->getFile()) .
    " | LINE: " .
    $e->getLine();

$nama      = "";
$username  = "";
$email     = "";
$telephone = "";
$nik       = "";
$alamat    = "";


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| CEK KONEKSI DATABASE
|--------------------------------------------------------------------------
*/

if (!isset($conn) || !($conn instanceof mysqli)) {

    die("Koneksi database tidak tersedia.");
}


/*
|--------------------------------------------------------------------------
| PROSES REGISTER
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | AMBIL DATA FORM
    |--------------------------------------------------------------------------
    */

    $nama      = trim($_POST['nama'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $nik       = trim($_POST['nik'] ?? '');
    $alamat    = trim($_POST['alamat'] ?? '');


    /*
    |--------------------------------------------------------------------------
    | VALIDASI
    |--------------------------------------------------------------------------
    */

    if (
        $nama === '' ||
        $username === '' ||
        $email === '' ||
        $telephone === '' ||
        $password === '' ||
        $nik === '' ||
        $alamat === ''
    ) {

        $error = "Semua field wajib diisi.";
    }

    elseif (strlen($nama) < 3) {

        $error = "Nama minimal 3 karakter.";
    }

    elseif (strlen($nama) > 150) {

        $error = "Nama maksimal 150 karakter.";
    }

    elseif (strlen($username) < 3) {

        $error = "Username minimal 3 karakter.";
    }

    elseif (strlen($username) > 50) {

        $error = "Username maksimal 50 karakter.";
    }

    elseif (!preg_match('/^[a-zA-Z0-9_.]+$/', $username)) {

        $error =
            "Username hanya boleh menggunakan huruf, angka, titik dan underscore.";
    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Format email tidak valid.";
    }

    elseif (strlen($email) > 150) {

        $error = "Email maksimal 150 karakter.";
    }

    elseif (strlen($telephone) > 30) {

        $error = "Nomor telepon maksimal 30 karakter.";
    }

    elseif (strlen($password) < 6) {

        $error = "Password minimal 6 karakter.";
    }

    elseif (!preg_match('/^[0-9]+$/', $nik)) {

        $error = "NIK hanya boleh berisi angka.";
    }

    elseif (strlen($nik) < 10) {

        $error = "NIK minimal 10 angka.";
    }

    elseif (strlen($nik) > 50) {

        $error = "NIK maksimal 50 angka.";
    }


    /*
    |--------------------------------------------------------------------------
    | CEK USERNAME
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        try {

            $stmt = $conn->prepare("
                SELECT id
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            $stmt->bind_param(
                "s",
                $username
            );

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows > 0) {

                $error =
                    "Username sudah digunakan. Silakan pilih username lain.";
            }

            $stmt->close();

        } catch (Throwable $e) {

            $error =
                "Gagal mengecek username: " .
                $e->getMessage();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CEK EMAIL
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        try {

            $stmt = $conn->prepare("
                SELECT id
                FROM customers
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->bind_param(
                "s",
                $email
            );

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows > 0) {

                $error =
                    "Email sudah terdaftar.";
            }

            $stmt->close();

        } catch (Throwable $e) {

            $error =
                "Gagal mengecek email: " .
                $e->getMessage();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CEK NIK
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        try {

            $stmt = $conn->prepare("
                SELECT id
                FROM customers
                WHERE nik = ?
                LIMIT 1
            ");

            $stmt->bind_param(
                "s",
                $nik
            );

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows > 0) {

                $error =
                    "NIK sudah terdaftar.";
            }

            $stmt->close();

        } catch (Throwable $e) {

            $error =
                "Gagal mengecek NIK: " .
                $e->getMessage();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | SIMPAN DATA
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        try {

            /*
            |------------------------------------------------------------------
            | MULAI TRANSAKSI
            |------------------------------------------------------------------
            */

            $conn->begin_transaction();


            /*
            |------------------------------------------------------------------
            | HASH PASSWORD
            |------------------------------------------------------------------
            */

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            if ($passwordHash === false) {

                throw new Exception(
                    "Password gagal diproses."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | INSERT USERS
            |--------------------------------------------------------------------------
            |
            | Struktur users:
            |
            | id
            | username
            | nama
            | password
            | role
            | status
            |
            */

            $stmt = $conn->prepare("
                INSERT INTO users
                (
                    username,
                    nama,
                    password,
                    role,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    'customer',
                    1
                )
            ");

            /*
            | Ada 3 tanda ?:
            |
            | 1 = username
            | 2 = nama
            | 3 = password
            |
            | Jadi:
            | sss
            */

            $stmt->bind_param(
                "sss",
                $username,
                $nama,
                $passwordHash
            );

            $stmt->execute();

            $userId = (int) $conn->insert_id;

            $stmt->close();


            /*
            |--------------------------------------------------------------------------
            | PASTIKAN USER BERHASIL DIBUAT
            |--------------------------------------------------------------------------
            */

            if ($userId <= 0) {

                throw new Exception(
                    "User ID gagal dibuat."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | INSERT CUSTOMERS
            |--------------------------------------------------------------------------
            |
            | paket_id = NULL
            |
            | Karena user baru belum memilih paket.
            |
            | status_langganan = belum_berlangganan
            |
            */

            $stmt = $conn->prepare("
                INSERT INTO customers
                (
                    user_id,
                    paket_id,
                    nama,
                    telephone,
                    email,
                    nik,
                    alamat,
                    status_langganan
                )
                VALUES
                (
                    ?,
                    NULL,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'belum_berlangganan'
                )
            ");

            /*
            | Ada 6 tanda ?:
            |
            | 1 = userId
            | 2 = nama
            | 3 = telephone
            | 4 = email
            | 5 = nik
            | 6 = alamat
            |
            | Jadi:
            | isssss
            */

            $stmt->bind_param(
                "isssss",
                $userId,
                $nama,
                $telephone,
                $email,
                $nik,
                $alamat
            );

            $stmt->execute();

            $customerId = (int) $conn->insert_id;

            $stmt->close();


            /*
            |--------------------------------------------------------------------------
            | PASTIKAN CUSTOMER BERHASIL DIBUAT
            |--------------------------------------------------------------------------
            */

            if ($customerId <= 0) {

                throw new Exception(
                    "Customer ID gagal dibuat."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */

            $conn->commit();


            /*
            |--------------------------------------------------------------------------
            | BUAT SESSION CUSTOMER
            |--------------------------------------------------------------------------
            */

            session_regenerate_id(true);

            $_SESSION['user_id'] = $userId;

            $_SESSION['customer_id'] = $customerId;

            $_SESSION['username'] = $username;

            $_SESSION['nama'] = $nama;

            $_SESSION['email'] = $email;

            $_SESSION['telephone'] = $telephone;

            $_SESSION['nik'] = $nik;

            $_SESSION['alamat'] = $alamat;

            $_SESSION['role'] = 'customer';

            $_SESSION['user_status'] = 1;


            /*
            |--------------------------------------------------------------------------
            | REDIRECT KE LANGGANAN
            |--------------------------------------------------------------------------
            */

            header(
                "Location: customer/langganan.php"
            );

            exit;


        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | ROLLBACK
            |--------------------------------------------------------------------------
            */

            try {

                $conn->rollback();

            } catch (Throwable $rollbackError) {

                // Tidak perlu ditampilkan.
            }


            /*
            |--------------------------------------------------------------------------
            | TAMPILKAN ERROR
            |--------------------------------------------------------------------------
            */

            $error =
                "REGISTRASI GAGAL: " .
                $e->getMessage() .
                " | FILE: " .
                basename($e->getFile()) .
                " | LINE: " .
                $e->getLine();
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

    <meta
        name="description"
        content="Registrasi Customer WiFi Management System"
    >

    <title>
        Daftar Akun - WiFi Management
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <!-- Custom CSS -->

    <link
        rel="stylesheet"
        href="assets/css/register.css?v=6"
    >

</head>


<body>


<div class="background-circle circle-1"></div>

<div class="background-circle circle-2"></div>


<div class="register-wrapper">


    <div class="register-container">


        <!-- =====================================================
             LEFT INFORMATION
        ====================================================== -->

        <div class="register-info">


            <div class="wifi-icon">

                <img
                    src="logo-yesnet.png"
                    alt="WiFi Management"
                >

            </div>


            <h1>

                Selamat Datang di

                <span>
                    WiFi Management
                </span>

            </h1>


            <p>

                Buat akun pelanggan untuk mengelola
                layanan internet dengan lebih mudah,
                cepat, dan praktis.

            </p>


            <div class="feature-list">


                <div class="feature-item">

                    <div class="feature-icon">

                        <i class="bi bi-wifi"></i>

                    </div>

                    <span>
                        Pantau penggunaan internet
                    </span>

                </div>


                <div class="feature-item">

                    <div class="feature-icon">

                        <i class="bi bi-receipt"></i>

                    </div>

                    <span>
                        Cek tagihan dan pembayaran
                    </span>

                </div>


                <div class="feature-item">

                    <div class="feature-icon">

                        <i class="bi bi-headset"></i>

                    </div>

                    <span>
                        Hubungi customer service
                    </span>

                </div>


                <div class="feature-item">

                    <div class="feature-icon">

                        <i class="bi bi-lightning-charge"></i>

                    </div>

                    <span>
                        Pilih paket internet sesuai kebutuhan
                    </span>

                </div>


            </div>


        </div>


        <!-- =====================================================
             REGISTER CARD
        ====================================================== -->

        <div class="register-card">


            <div class="register-header">

                <h2>
                    Buat Akun Baru
                </h2>

                <p>
                    Daftarkan diri Anda untuk mulai menggunakan
                    layanan WiFi Management.
                </p>

            </div>


            <!-- ERROR -->

            <?php if ($error !== ''): ?>

                <div
                    class="alert alert-danger"
                    role="alert"
                >

                    <i class="bi bi-exclamation-circle me-2"></i>

                    <?= e($error) ?>

                </div>

            <?php endif; ?>


            <!-- FORM -->

            <form
                method="POST"
                action=""
                autocomplete="on"
            >


                <!-- DATA PRIBADI -->

                <div class="section-title">

                    <span>
                        01
                    </span>

                    <div>

                        <strong>
                            Data Pribadi
                        </strong>

                        <small>
                            Masukkan informasi pribadi Anda
                        </small>

                    </div>

                </div>


                <!-- NAMA -->

                <div class="mb-3">

                    <label
                        for="nama"
                        class="form-label"
                    >
                        Nama Lengkap
                    </label>

                    <div class="input-wrapper">

                        <i class="bi bi-person input-icon"></i>

                        <input
                            type="text"
                            id="nama"
                            name="nama"
                            class="form-control"
                            placeholder="Masukkan nama lengkap"
                            value="<?= e($nama) ?>"
                            maxlength="150"
                            autocomplete="name"
                            required
                        >

                    </div>

                </div>


                <!-- EMAIL -->

                <div class="mb-3">

                    <label
                        for="email"
                        class="form-label"
                    >
                        Email
                    </label>

                    <div class="input-wrapper">

                        <i class="bi bi-envelope input-icon"></i>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="nama@email.com"
                            value="<?= e($email) ?>"
                            maxlength="150"
                            autocomplete="email"
                            required
                        >

                    </div>

                </div>


                <!-- TELEPHONE -->

                <div class="mb-3">

                    <label
                        for="telephone"
                        class="form-label"
                    >
                        Nomor Telepon
                    </label>

                    <div class="input-wrapper">

                        <i class="bi bi-telephone input-icon"></i>

                        <input
                            type="text"
                            id="telephone"
                            name="telephone"
                            class="form-control"
                            placeholder="08xxxxxxxxxx"
                            value="<?= e($telephone) ?>"
                            maxlength="30"
                            autocomplete="tel"
                            required
                        >

                    </div>

                </div>


                <!-- NIK -->

                <div class="mb-3">

                    <label
                        for="nik"
                        class="form-label"
                    >
                        NIK
                    </label>

                    <div class="input-wrapper">

                        <i class="bi bi-card-text input-icon"></i>

                        <input
                            type="text"
                            id="nik"
                            name="nik"
                            class="form-control"
                            placeholder="Masukkan NIK"
                            value="<?= e($nik) ?>"
                            maxlength="50"
                            inputmode="numeric"
                            autocomplete="off"
                            required
                        >

                    </div>

                </div>


                <!-- ALAMAT -->

                <div class="mb-3">

                    <label
                        for="alamat"
                        class="form-label"
                    >
                        Alamat
                    </label>

                    <div class="input-wrapper textarea-wrapper">

                        <i class="bi bi-geo-alt input-icon"></i>

                        <textarea
                            id="alamat"
                            name="alamat"
                            class="form-control"
                            placeholder="Masukkan alamat lengkap"
                            autocomplete="street-address"
                            required
                        ><?= e($alamat) ?></textarea>

                    </div>

                </div>


                <!-- DATA AKUN -->

                <div class="section-title">

                    <span>
                        02
                    </span>

                    <div>

                        <strong>
                            Data Akun
                        </strong>

                        <small>
                            Buat username dan password untuk login
                        </small>

                    </div>

                </div>


                <!-- USERNAME -->

                <div class="mb-3">

                    <label
                        for="username"
                        class="form-label"
                    >
                        Username
                    </label>

                    <div class="input-wrapper">

                        <i class="bi bi-person-badge input-icon"></i>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-control"
                            placeholder="Masukkan username"
                            value="<?= e($username) ?>"
                            maxlength="50"
                            minlength="3"
                            autocomplete="username"
                            required
                        >

                    </div>

                    <div class="password-hint">

                        <i class="bi bi-info-circle"></i>

                        <span>
                            Username minimal 3 karakter.
                        </span>

                    </div>

                </div>


                <!-- PASSWORD -->

                <div class="mb-3">

                    <label
                        for="password"
                        class="form-label"
                    >
                        Password
                    </label>

                    <div class="input-wrapper">

                        <i class="bi bi-lock input-icon"></i>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Masukkan password"
                            minlength="6"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword()"
                            aria-label="Tampilkan password"
                        >

                            <i
                                class="bi bi-eye"
                                id="passwordIcon"
                            ></i>

                        </button>

                    </div>

                    <div class="password-hint">

                        <i class="bi bi-info-circle"></i>

                        <span>
                            Password minimal 6 karakter.
                        </span>

                    </div>

                </div>


                <!-- SUBMIT -->

                <button
                    type="submit"
                    class="register-btn"
                >

                    <span>
                        Daftar Sekarang
                    </span>

                    <i class="bi bi-arrow-right"></i>

                </button>


            </form>


            <!-- LOGIN -->

            <div class="login-text">

                Sudah mempunyai akun?

                <a href="login.php">
                    Login di sini
                </a>

            </div>


            <!-- SECURITY -->

            <div class="security-text">

                <i class="bi bi-shield-check"></i>

                <span>
                    Data Anda akan disimpan dengan aman.
                </span>

            </div>


        </div>


    </div>


</div>


<script>

function togglePassword()
{
    const password =
        document.getElementById('password');

    const icon =
        document.getElementById('passwordIcon');


    if (password.type === 'password') {

        password.type = 'text';

        icon.classList.remove('bi-eye');

        icon.classList.add('bi-eye-slash');

    } else {

        password.type = 'password';

        icon.classList.remove('bi-eye-slash');

        icon.classList.add('bi-eye');

    }
}

</script>


</body>

</html>
