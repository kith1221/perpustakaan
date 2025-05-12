<?php
// Include config
require_once '../includes/config.php';

// Set proper headers for Excel-compatible CSV file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="template_import_siswa.csv"');
header('Cache-Control: no-cache');

// Output UTF-8 BOM for Excel compatibility
echo "\xEF\xBB\xBF";

// Create template with header and 2 example rows
$data = array(
    array('NISN', 'Nama Siswa', 'Kelas'),
    array('1234567890', 'Ahmad Santoso', '1'),
    array('0987654321', 'Budi Setiawan', '2')
);

// Create output stream
$output = fopen('php://output', 'w');

// Output each row of the data
foreach ($data as $row) {
    fputcsv($output, $row, ',', '"');
}

fclose($output);
exit; 