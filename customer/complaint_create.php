<?php

session_start();

require_once "../config/database.php";
require_once "../config/auth.php";

requireRole('customer');

date_default_timezone_set('Asia/Jakarta');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


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
| LOGIN
|--------------------------------------------------------------------------
*/

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header("Location: ../login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| DATA CUSTOMER
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        user_id,
        nama,
        telephone,
        email,
        alamat,
        status_langganan
    FROM customers
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$resultCustomer = $stmt->get_result();
$customer = $resultCustomer->fetch_assoc();

$stmt->close();


if (!$customer) {
    die("Data customer tidak ditemukan.");
}


$customerId = (int) $customer['id'];

$namaCustomer = $customer['nama'] ?? 'Customer';

$avatarInitial = strtoupper(
    mb_substr(trim($namaCustomer), 0, 1)
);

$statusLangganan = strtolower(
    trim((string) ($customer['status_langganan'] ?? ''))
);

$alamatCustomer = $customer['alamat'] ?? '';


/*
|--------------------------------------------------------------------------
| FORM
|--------------------------------------------------------------------------
*/

$judul = '';
$deskripsi = '';

$error = '';


/*
|--------------------------------------------------------------------------
| PROSES FORM
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $judul = trim(
        (string) ($_POST['judul'] ?? '')
    );

    $deskripsi = trim(
        (string) ($_POST['deskripsi'] ?? '')
    );

    $alamat = trim(
        (string) (
            $_POST['alamat']
            ?? $alamatCustomer
            ?? ''
        )
    );

    $latitudeInput = trim(
        (string) ($_POST['latitude'] ?? '')
    );

    $longitudeInput = trim(
        (string) ($_POST['longitude'] ?? '')
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDASI
    |--------------------------------------------------------------------------
    */

    if ($judul === '') {

        $error = "Judul keluhan wajib diisi.";

    } elseif (mb_strlen($judul) < 5) {

        $error = "Judul keluhan minimal 5 karakter.";

    } elseif (mb_strlen($judul) > 150) {

        $error = "Judul keluhan maksimal 150 karakter.";

    } elseif ($deskripsi === '') {

        $error = "Deskripsi keluhan wajib diisi.";

    } elseif (mb_strlen($deskripsi) < 10) {

        $error = "Deskripsi keluhan minimal 10 karakter.";

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDASI KOORDINAT
    |--------------------------------------------------------------------------
    */

    $latitude = null;
    $longitude = null;


    if ($latitudeInput !== '') {

        if (!is_numeric($latitudeInput)) {

            $error = "Latitude tidak valid.";

        } else {

            $latitude = (float) $latitudeInput;

            if ($latitude < -90 || $latitude > 90) {
                $error = "Nilai latitude harus antara -90 sampai 90.";
            }
        }
    }


    if (
        $error === '' &&
        $longitudeInput !== ''
    ) {

        if (!is_numeric($longitudeInput)) {

            $error = "Longitude tidak valid.";

        } else {

            $longitude = (float) $longitudeInput;

            if ($longitude < -180 || $longitude > 180) {
                $error = "Nilai longitude harus antara -180 sampai 180.";
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | SIMPAN COMPLAINT
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        try {

            /*
            |--------------------------------------------------------------------------
            | STATUS AWAL
            |--------------------------------------------------------------------------
            |
            | Complaint baru otomatis memiliki status:
            | open
            |
            */

            $status = 'open';


            /*
            |--------------------------------------------------------------------------
            | INSERT
            |--------------------------------------------------------------------------
            */

            $stmt = $conn->prepare("
                INSERT INTO complaint
                (
                    customer_id,
                    judul,
                    deskripsi,
                    alamat,
                    latitude,
                    longitude,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");


            $stmt->bind_param(
                "isssdds",
                $customerId,
                $judul,
                $deskripsi,
                $alamat,
                $latitude,
                $longitude,
                $status
            );


            $stmt->execute();

            $stmt->close();


            /*
            |--------------------------------------------------------------------------
            | REDIRECT
            |--------------------------------------------------------------------------
            */

            header(
                "Location: complaint.php?success=created"
            );

            exit;


        } catch (Throwable $e) {

            $error =
                "Keluhan gagal disimpan. Silakan coba lagi.";
        }
    }
}

?>

<!DOCTYPE html>

<html lang="id">

<head>

```
<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Buat Keluhan - WiFi Management</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<link
    rel="stylesheet"
    href="assets/css/dashboard.css"
>


<link
    rel="stylesheet"
    href="assets/css/complaint_create.css?v=2"
>
```

</head>

<body>

<div class="customer-layout">

```
<!-- SIDEBAR -->

<aside class="sidebar" id="sidebar">


    <div class="sidebar-brand">

        <div class="brand-logo">
            W
        </div>

        <div class="brand-text">

            <strong>
                WiFi Management
            </strong>

            <small>
                Customer Portal
            </small>

        </div>

        <button
            type="button"
            class="sidebar-close"
            id="sidebarClose"
        >
            &times;
        </button>

    </div>


    <div class="sidebar-scroll">


        <div class="menu-section">

            <div class="menu-label">
                MENU UTAMA
            </div>


            <a
                href="dashboard.php"
                class="menu-item"
            >
                <span class="menu-icon">⌂</span>
                <span class="menu-text">
                    Dashboard
                </span>
            </a>


            <?php if (
                in_array(
                    $statusLangganan,
                    ['active', 'aktif'],
                    true
                )
            ): ?>


                <a
                    href="billing.php"
                    class="menu-item"
                >
                    <span class="menu-icon">▣</span>
                    <span class="menu-text">
                        Tagihan
                    </span>
                </a>


                <a
                    href="usage.php"
                    class="menu-item"
                >
                    <span class="menu-icon">◔</span>
                    <span class="menu-text">
                        Pemakaian
                    </span>
                </a>


                <a
                    href="speedtest.php"
                    class="menu-item"
                >
                    <span class="menu-icon">◉</span>
                    <span class="menu-text">
                        Speed Test
                    </span>
                </a>


                <a
                    href="complaint.php"
                    class="menu-item active"
                >
                    <span class="menu-icon">⚠</span>
                    <span class="menu-text">
                        Keluhan
                    </span>
                </a>


                <a
                    href="chat.php"
                    class="menu-item"
                >
                    <span class="menu-icon">☏</span>
                    <span class="menu-text">
                        Chat
                    </span>
                </a>


                <a
                    href="network_status.php"
                    class="menu-item"
                >
                    <span class="menu-icon">⌁</span>
                    <span class="menu-text">
                        Status Jaringan
                    </span>
                </a>


                <a
                    href="upgrade.php"
                    class="menu-item"
                >
                    <span class="menu-icon">↑</span>
                    <span class="menu-text">
                        Upgrade Paket
                    </span>
                </a>


                <a
                    href="service_request.php"
                    class="menu-item"
                >
                    <span class="menu-icon">⚙</span>
                    <span class="menu-text">
                        Permintaan Layanan
                    </span>
                </a>

            <?php endif; ?>

        </div>


        <div class="menu-section">

            <div class="menu-label">
                AKUN
            </div>


            <a
                href="profile.php"
                class="menu-item"
            >
                <span class="menu-icon">♙</span>
                <span class="menu-text">
                    Profil Saya
                </span>
            </a>


            <a
                href="notifikasi.php"
                class="menu-item"
            >
                <span class="menu-icon">🔔</span>
                <span class="menu-text">
                    Notifikasi
                </span>
            </a>


            <a
                href="../logout.php"
                class="menu-item logout-item"
            >
                <span class="menu-icon">⇥</span>
                <span class="menu-text">
                    Keluar
                </span>
            </a>

        </div>

    </div>


    <div class="sidebar-user">

        <div class="sidebar-user-avatar">
            <?= e($avatarInitial) ?>
        </div>

        <div class="sidebar-user-info">

            <strong>
                <?= e($namaCustomer) ?>
            </strong>

            <small>
                Customer
            </small>

        </div>

    </div>

</aside>


<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>


<!-- MAIN -->

<main class="main-content">


    <!-- TOPBAR -->

    <header class="topbar">

        <div class="topbar-left">

            <button
                type="button"
                class="mobile-menu-button"
                id="mobileMenuButton"
            >
                ☰
            </button>


            <div class="breadcrumb-text">
                Customer Portal / Keluhan / Buat Keluhan
            </div>

        </div>


        <div class="topbar-right">

            <a
                href="notifikasi.php"
                class="notification-button"
            >
                🔔
            </a>


            <a
                href="profile.php"
                class="top-profile"
            >

                <div class="top-profile-avatar">
                    <?= e($avatarInitial) ?>
                </div>


                <div class="top-profile-info">

                    <strong>
                        <?= e($namaCustomer) ?>
                    </strong>

                    <small>
                        Customer
                    </small>

                </div>

            </a>

        </div>

    </header>


    <!-- PAGE -->

    <div class="complaint-create-page">


        <!-- HEADER -->

        <div class="create-header">

            <div class="create-header-left">


                <a
                    href="complaint.php"
                    class="back-button"
                >
                    ←
                </a>


                <div>

                    <span class="page-eyebrow">
                        CUSTOMER SERVICE
                    </span>

                    <h1>
                        Buat Keluhan
                    </h1>

                    <p>
                        Laporkan gangguan layanan WiFi yang Anda alami.
                    </p>

                </div>

            </div>

        </div>


        <!-- ERROR -->

        <?php if ($error !== ''): ?>

            <div class="form-alert">

                <div class="alert-icon">
                    !
                </div>

                <div>

                    <strong>
                        Keluhan belum dapat dikirim
                    </strong>

                    <p>
                        <?= e($error) ?>
                    </p>

                </div>

            </div>

        <?php endif; ?>


        <!-- LAYOUT -->

        <div class="create-layout">


            <!-- FORM -->

            <section class="create-card">


                <div class="create-card-header">

                    <div>

                        <h2>
                            Detail Keluhan
                        </h2>

                        <p>
                            Jelaskan gangguan yang Anda alami
                            agar tim dapat menanganinya.
                        </p>

                    </div>


                    <div class="required-info">
                        * Wajib diisi
                    </div>

                </div>


                <form
                    method="POST"
                    class="complaint-form"
                >


                    <!-- JUDUL -->

                    <div class="form-group">

                        <label for="judul">

                            Judul Keluhan

                            <span>*</span>

                        </label>


                        <input
                            type="text"
                            name="judul"
                            id="judul"
                            value="<?= e($judul) ?>"
                            maxlength="150"
                            placeholder="Contoh: Internet tidak bisa digunakan"
                            required
                        >


                        <div class="input-hint">
                            Buat judul singkat yang menggambarkan masalah.
                        </div>

                    </div>


                    <!-- DESKRIPSI -->

                    <div class="form-group">

                        <label for="deskripsi">

                            Deskripsi Gangguan

                            <span>*</span>

                        </label>


                        <textarea
                            name="deskripsi"
                            id="deskripsi"
                            rows="7"
                            maxlength="2000"
                            placeholder="Jelaskan masalah yang Anda alami secara detail..."
                            required
                        ><?= e($deskripsi) ?></textarea>


                        <div class="textarea-footer">

                            <span>
                                Jelaskan kapan masalah mulai terjadi
                                dan kondisi perangkat jika diketahui.
                            </span>

                            <span id="charCounter">
                                0 / 2000
                            </span>

                        </div>

                    </div>


                    <!-- ALAMAT -->

                    <div class="form-group">

                        <label for="alamat">
                            Alamat Lokasi Gangguan
                        </label>


                        <textarea
                            name="alamat"
                            id="alamat"
                            rows="3"
                            placeholder="Masukkan alamat lokasi gangguan"
                        ><?= e($alamatCustomer) ?></textarea>


                        <div class="input-hint">
                            Alamat akan digunakan untuk membantu teknisi
                            menemukan lokasi gangguan.
                        </div>

                    </div>


                    <!-- LOCATION -->

                    <div class="location-section">


                        <div class="location-header">

                            <div>

                                <label>
                                    Lokasi GPS
                                </label>

                                <p>
                                    Opsional. Gunakan lokasi perangkat
                                    untuk membantu teknisi.
                                </p>

                            </div>


                            <button
                                type="button"
                                id="getLocation"
                                class="btn-location"
                            >
                                📍
                                Gunakan Lokasi Saya
                            </button>

                        </div>


                        <div class="coordinate-grid">


                            <div class="coordinate-field">

                                <label for="latitude">
                                    Latitude
                                </label>

                                <input
                                    type="text"
                                    name="latitude"
                                    id="latitude"
                                    value=""
                                    placeholder="Contoh: -7.245"
                                    readonly
                                >

                            </div>


                            <div class="coordinate-field">

                                <label for="longitude">
                                    Longitude
                                </label>

                                <input
                                    type="text"
                                    name="longitude"
                                    id="longitude"
                                    value=""
                                    placeholder="Contoh: 112.736"
                                    readonly
                                >

                            </div>

                        </div>


                        <div
                            id="locationMessage"
                            class="location-message"
                        ></div>

                    </div>


                    <!-- ACTION -->

                    <div class="form-actions">

                        <a
                            href="complaint.php"
                            class="btn-cancel"
                        >
                            Batal
                        </a>


                        <button
                            type="submit"
                            class="btn-submit"
                        >

                            <span>
                                ✓
                            </span>

                            Kirim Keluhan

                        </button>

                    </div>


                </form>

            </section>


            <!-- INFO -->

            <aside class="create-info">


                <div class="info-card">

                    <div class="info-card-icon">
                        💡
                    </div>


                    <h3>
                        Tips Melaporkan Gangguan
                    </h3>


                    <ul>

                        <li>
                            Jelaskan masalah secara jelas.
                        </li>

                        <li>
                            Sertakan waktu mulai gangguan.
                        </li>

                        <li>
                            Periksa kondisi lampu indikator router.
                        </li>

                        <li>
                            Masukkan lokasi jika gangguan membutuhkan
                            kunjungan teknisi.
                        </li>

                    </ul>

                </div>


                <div class="info-card status-info">

                    <div class="info-card-icon">
                        ✓
                    </div>


                    <h3>
                        Setelah Dikirim
                    </h3>


                    <p>
                        Laporan akan masuk ke sistem dan dapat dipantau
                        melalui halaman keluhan.
                    </p>


                    <div class="status-step">

                        <span>
                            1
                        </span>

                        <div>

                            <strong>
                                Laporan diterima
                            </strong>

                            <small>
                                Status: Baru
                            </small>

                        </div>

                    </div>


                    <div class="status-step">

                        <span>
                            2
                        </span>

                        <div>

                            <strong>
                                Diproses
                            </strong>

                            <small>
                                Tim menangani laporan
                            </small>

                        </div>

                    </div>


                    <div class="status-step">

                        <span>
                            3
                        </span>

                        <div>

                            <strong>
                                Selesai
                            </strong>

                            <small>
                                Gangguan telah ditangani
                            </small>

                        </div>

                    </div>

                </div>

            </aside>

        </div>

    </div>


    <!-- FOOTER -->

    <footer class="customer-footer">

        <span>
            © <?= date('Y') ?> WiFi Management
        </span>

        <span>
            Customer Portal
        </span>

    </footer>


</main>
```

</div>

<script>

/*
|--------------------------------------------------------------------------
| SIDEBAR
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', function () {


    const sidebar =
        document.getElementById('sidebar');

    const overlay =
        document.getElementById('sidebarOverlay');

    const menuButton =
        document.getElementById('mobileMenuButton');

    const closeButton =
        document.getElementById('sidebarClose');


    function openSidebar() {

        if (sidebar) {
            sidebar.classList.add('show');
        }

        if (overlay) {
            overlay.classList.add('show');
        }

        document.body.classList.add('sidebar-open');
    }


    function closeSidebar() {

        if (sidebar) {
            sidebar.classList.remove('show');
        }

        if (overlay) {
            overlay.classList.remove('show');
        }

        document.body.classList.remove('sidebar-open');
    }


    if (menuButton) {
        menuButton.addEventListener(
            'click',
            openSidebar
        );
    }


    if (closeButton) {
        closeButton.addEventListener(
            'click',
            closeSidebar
        );
    }


    if (overlay) {
        overlay.addEventListener(
            'click',
            closeSidebar
        );
    }


    window.addEventListener(
        'resize',
        function () {

            if (window.innerWidth > 991) {
                closeSidebar();
            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | CHARACTER COUNTER
    |--------------------------------------------------------------------------
    */

    const description =
        document.getElementById('deskripsi');

    const charCounter =
        document.getElementById('charCounter');


    function updateCounter() {

        if (!description || !charCounter) {
            return;
        }

        charCounter.textContent =
            description.value.length +
            ' / 2000';
    }


    if (description) {

        description.addEventListener(
            'input',
            updateCounter
        );

        updateCounter();
    }


    /*
    |--------------------------------------------------------------------------
    | GPS LOCATION
    |--------------------------------------------------------------------------
    */

    const locationButton =
        document.getElementById('getLocation');

    const latitude =
        document.getElementById('latitude');

    const longitude =
        document.getElementById('longitude');

    const locationMessage =
        document.getElementById('locationMessage');


    if (locationButton) {

        locationButton.addEventListener(
            'click',
            function () {


                if (!navigator.geolocation) {

                    locationMessage.textContent =
                        'Browser Anda tidak mendukung GPS.';

                    locationMessage.className =
                        'location-message error';

                    return;
                }


                locationButton.disabled = true;

                locationButton.textContent =
                    '📍 Mengambil lokasi...';


                navigator.geolocation.getCurrentPosition(

                    function (position) {

                        const lat =
                            position.coords.latitude;

                        const lng =
                            position.coords.longitude;


                        latitude.value =
                            lat.toFixed(7);

                        longitude.value =
                            lng.toFixed(7);


                        locationMessage.textContent =
                            'Lokasi berhasil ditemukan.';

                        locationMessage.className =
                            'location-message success';


                        locationButton.disabled = false;

                        locationButton.textContent =
                            '✓ Lokasi Ditemukan';

                    },


                    function (error) {

                        let message =
                            'Lokasi tidak dapat ditemukan.';


                        if (error.code === 1) {
                            message =
                                'Izin lokasi ditolak. Silakan izinkan akses lokasi pada browser.';
                        }

                        if (error.code === 2) {
                            message =
                                'Lokasi tidak tersedia.';
                        }

                        if (error.code === 3) {
                            message =
                                'Waktu pengambilan lokasi habis.';
                        }


                        locationMessage.textContent =
                            message;

                        locationMessage.className =
                            'location-message error';


                        locationButton.disabled = false;

                        locationButton.textContent =
                            '📍 Coba Lagi';

                    },

                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }

                );

            }
        );

    }

});

</script>

</body>

</html>
