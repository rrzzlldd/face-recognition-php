<?php
// upload.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Untuk development, bisa diperketat di produksi

$response = [];

try {
    // 1. Validasi Keamanan Upload
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Gagal menerima file. Error code: ' . $_FILES['image']['error']);
    }

    $file = $_FILES['image'];
    $max_size = 5 * 1024 * 1024; // 5 MB
    $allowed_types = ['image/jpeg', 'image/png'];

    if ($file['size'] > $max_size) {
        throw new Exception('Ukuran file terlalu besar. Maksimal 5 MB.');
    }

    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('Tipe file tidak valid. Hanya JPG atau PNG yang diizinkan.');
    }

    // 2. Simpan File Sementara
    $upload_dir = 'uploads/';
    // Buat nama file yang unik untuk menghindari konflik
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid('img_', true) . '.' . $file_extension;
    $target_path = $upload_dir . $new_filename;

    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        throw new Exception('Gagal menyimpan file yang diunggah.');
    }

    // 3. Panggil Skrip Python (Bagian Inti!)
    // escapeshellarg() SANGAT PENTING untuk mencegah command injection
    $safe_path = escapeshellarg($target_path);
    $command = "python3 recognize.py " . $safe_path;

    // Jalankan perintah dan tangkap outputnya
    $python_output = shell_exec($command);

    // 4. Proses Hasil dan Kirim Respons
    if ($python_output === null) {
        throw new Exception('Gagal menjalankan skrip pengenalan wajah.');
    }

    $response['status'] = 'success';
    $response['data'] = json_decode($python_output); // Output dari python sudah JSON

} catch (Exception $e) {
    http_response_code(400); // Bad Request
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
} finally {
    // 5. Hapus File Sementara (Penting!)
    if (isset($target_path) && file_exists($target_path)) {
        unlink($target_path);
    }
}

// Kirimkan respons JSON kembali ke frontend
echo json_encode($response);
?>