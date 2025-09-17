<?php
// blob.php — Azure Blob upload/list helpers
require __DIR__ . '/vendor/autoload.php';

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;

function blob_client() {
    $env = parse_ini_file("/etc/webapp/storage.env", false, INI_SCANNER_TYPED);
    if (!$env) { throw new RuntimeException("Cannot read /etc/webapp/storage.env"); }

    // In production prefer Managed Identity; for simplicity we use anonymous public container URLs for reads.
    // Here we use connection string from MSI if later added; for now use DefaultAzureCredential is not available in this SDK.
    // The container was created server-side and is private; uploads use SAS generated manually if you want client-side.
    // For server-side we authenticate with Azure CLI login via MSI not available; so we rely on connection string env if provided.
    // If no connection string is present, this will throw. (You can extend to use SAS per-request.)
    $conn = getenv('AZURE_STORAGE_CONNECTION_STRING') ?: ($env['STORAGE_CONNECTION_STRING'] ?? null);
    if (!$conn) {
        // fallback: try emulator or throw
        throw new RuntimeException("No storage connection string provided.");
    }
    return BlobRestProxy::createBlobService($conn);
}

function blob_public_url($account, $container, $name) {
    return "https://{$account}.blob.core.windows.net/{$container}/" . rawurlencode($name);
}
