<?php
// /var/www/html/upload.php

header('Content-Type: application/json');
// Di produksi, ganti '*' dengan domain frontend Anda, misal: 'https://domainanda.com'
header('Access-Control-Allow-Origin: *'); 

$response = [];
$target_path = null; // Inisialisasi variabel

try {
    // 1. Validasi Keamanan Upload
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Gagal menerima file. Kode Error: ' . ($_FILES['image']['error'] ?? 'N/A'));
    }

    $file = $_FILES['image'];
    $max_size = 5 * 1024 * 1024; // 5 MB
    $allowed_types = ['image/jpeg', 'image/png'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($file['size'] > $max_size) throw new Exception('Ukuran file terlalu besar. Maksimal 5 MB.');
    if (!in_array($mime_type, $allowed_types)) throw new Exception('Tipe file tidak valid. Hanya JPG atau PNG.');

    // 2. Simpan File Sementara
    $upload_dir = 'uploads/';
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid('img_', true) . '.' . $file_extension;
    $target_path = $upload_dir . $new_filename;

    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        throw new Exception('Gagal menyimpan file yang diunggah. Periksa izin folder uploads.');
    }

    // 3. Panggil Skrip Python
    $safe_path = escapeshellarg($target_path);
    // Pastikan path ke python3 dan venv benar.
    // '/var/www/html/venv/bin/python3' adalah path absolut ke Python di dalam virtual environment
    $python_executable = '/var/www/html/venv/bin/python3';
    $python_script = '/var/www/html/recognize.py';
    $command = $python_executable . ' ' . $python_script . ' ' . $safe_path;

    $python_output = shell_exec($command);

    // 4. Proses Hasil
    if ($python_output === null) {
        throw new Exception('Gagal menjalankan skrip pengenalan wajah. Periksa log error server.');
    }

    $decoded_output = json_decode($python_output, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
         throw new Exception('Output dari skrip Python bukan JSON yang valid. Output: ' . $python_output);
    }
    
    $response['status'] = 'success';
    $response['data'] = $decoded_output;

} catch (Exception $e) {
    http_response_code(400);
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
} finally {
    // 5. Hapus File Sementara
    if ($target_path && file_exists($target_path)) {
        unlink($target_path);
    }
}

echo json_encode($response);
?>