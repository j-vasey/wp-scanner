<?php
// Set a unique secret key for authentication
$secret_key = 'testkey';

// Verify the secret key before processing the request
if (empty($_POST['secret_key']) || $_POST['secret_key'] !== $secret_key) {
	header('HTTP/1.1 401 Unauthorized');
	die('Unauthorized');
}

// Check if the file is received
if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
	header('HTTP/1.1 400 Bad Request');
	die('No file uploaded');
}

// Define the directory where the zip file will be extracted
$extracted_dir = "/endpoint/$secret_key";

// Create the directory if it doesn't exist
if (!file_exists($extracted_dir)) {
	mkdir($extracted_dir, 0777, true);
}

// Move the uploaded zip file to the extraction directory
$zip_file_path = $_FILES['file']['tmp_name'];
$extracted_path = $extracted_dir . '/extracted_files';
move_uploaded_file($zip_file_path, $extracted_path . '.zip');

// Unzip the file
$zip = new ZipArchive;
if ($zip->open($extracted_path . '.zip') === true) {
	$zip->extractTo($extracted_dir);
	$zip->close();
	unlink($extracted_path . '.zip');
	chmod_recursive($extracted_dir, 'clamav','clamav'); // Change permission mode to 0755 (adjust as needed)

} else {
	header('HTTP/1.1 500 Internal Server Error');
	die('Failed to unzip the file');
}

// Initialize the ClamAV scan
$clamscan_output = shell_exec("clamscan -r --no-summary $extracted_dir");

// Process the ClamAV scan results
$scan_results = array();
$lines = explode("\n", $clamscan_output);
foreach ($lines as $line) {
	if (preg_match('/^(.*): (.*) (FOUND)$/', $line, $matches)) {
		$scan_results[] = array('file' => $matches[1], 'status' => 'infected');
	}
}

// Send the scan results back as JSON response
header('Content-Type: application/json');
echo json_encode(array('scanned_files' => count($scan_results), 'results' => $scan_results));


// Check if the file is received
if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    header('HTTP/1.1 400 Bad Request');
    die('No file uploaded');
}

function chmod_recursive($path, $owner, $group) {
    if (is_dir($path)) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                chown($file->getPathname(), $owner);
                chgrp($file->getPathname(), $group);
            } else {
                chown($file->getPathname(), $owner);
                chgrp($file->getPathname(), $group);
            }
        }
    } else {
        chown($path, $owner);
        chgrp($path, $group);
    }
}
