<?php
// Include config
require_once '../includes/config.php';

// Set proper headers for Excel-compatible CSV file
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="template_import_buku.csv"');
header('Cache-Control: no-cache');

// Output UTF-8 BOM for Excel compatibility
echo "\xEF\xBB\xBF";

// Create sample data
$data = array(
    array('Judul', 'Pengarang', 'Penerbit', 'Tahun Terbit', 'ISBN', 'Jumlah'),
    array('Harry Potter dan Batu Bertuah', 'J.K. Rowling', 'Gramedia', '2001', '978-602-03-0408-4', '10'),
    array('Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', '2005', '979-3062-79-7', '5')
);

// Create output stream
$output = fopen('php://output', 'w');

// Output each row of the data
foreach ($data as $row) {
    fputcsv($output, $row, ',', '"');
}

fclose($output);
exit;