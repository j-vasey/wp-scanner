<h1>WP Malware Scanner</h1>

A WordPress plugin that can do scans on command by securely sending copies of files to an API endpoint which performs various malware scans.

This causes decreased load on the web host, whilst also maintaining security.

The intention is not to send config files, and to only send files via either SSH or SFTP using the API endpoint's certificate to confirm identity.
