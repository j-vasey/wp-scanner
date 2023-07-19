<?php
/**
 * Plugin Name: WordPress Cloud AV Scanner
 * Plugin URI: https://joshvasey.co.uk
 * Description: Josh's WordPress Cloud Antivirus Scanner
 * Version: 0.1
 * Author: Joshua Vasey
 * Author URI: https://joshvasey.co.uk
 **/

function malware_scanner_menu() {

	add_menu_page(
		'Malware Scanner',
		'Malware Scanner',
		'manage_options',
		'malware-scanner',
		'malware_scanner_page'
	);
}

add_action('admin_menu', 'malware_scanner_menu');

function malware_scanner_page(){

	echo '<div class="wrap">';
	echo '<h1>Malware Scanner</h1>';

	// Check if the user has pressed the "Scan" button
	if (isset($_POST['scan'])) {
		send_files_to_remote_server();
	}

	// Display the "Scan" button
	echo '<form method="post">';
	echo '<input type="submit" name="scan" class="button button-primary" value="Scan">';
	echo '</form>';

	echo '</div>';
}

function send_files_to_remote_server() {
	// Set the remote server's API endpoint URL
	$api_endpoint = 'wpapi.joshvasey.co.uk:1337';

	$secret_key = 'testkey';

	// Get the path to the WordPress root directory
	$root_directory = ABSPATH;

	// Create a temporary file for the zip archive
	$zip_file = tempnam(sys_get_temp_dir(), 'malware-scan-');
	$zip = new ZipArchive();

	// Open the zip file for writing
	if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
		// Failed to open the zip file
		return;
	}

	// Add all files in the WordPress root directory to the zip archive
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($root_directory),
		RecursiveIteratorIterator::LEAVES_ONLY
	);

	foreach ($files as $name => $file) {
		if (!$file->isDir()) {
			$filePath = $file->getRealPath();
			if (stripos($filePath, 'config') !== false) {
				continue;
			}
			$relativePath = substr($filePath, strlen($root_directory) + 1);
			$zip->addFile($filePath, $relativePath);
		}
	}

	$zip->close();

	// Create cURL resource
	$curl = curl_init();

	// Set cURL options
	curl_setopt($curl, CURLOPT_URL, $api_endpoint);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, [
		'secret_key' => $secret_key,
		'file' => new CURLFile($zip_file),
	]);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); // Enable SSL verification
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // Check that the certificate's common name matches the server host name

	// Execute the cURL request
	$response = curl_exec($curl);

	// Check for cURL errors
	if (curl_errno($curl)) {
		// Handle the error
		$error_message = curl_error($curl);
	}

	// Close cURL resource
	curl_close($curl);

	// Delete the temporary zip file
	unlink($zip_file);

	// Process the response from the remote server
	if (!empty($response)) {
		// Process the response as needed
	}
}