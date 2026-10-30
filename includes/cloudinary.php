<?php
/**
 * Cloudinary upload helper — no SDK needed, uses plain cURL.
 * Reads credentials from environment variables (set on Render),
 * with fallback to the hardcoded values for local XAMPP testing.
 */

function uploadToCloudinary(string $tmpFilePath, string $resourceType = 'auto', string $folder = 'skillswap'): ?string {
    $cloudName = getenv('CLOUDINARY_CLOUD_NAME') ?: 'iztk39ed';
    $apiKey    = getenv('CLOUDINARY_API_KEY')    ?: '165157272579392';
    $apiSecret = getenv('CLOUDINARY_API_SECRET') ?: 'xXumFYo1QXohdR6VFEvEK7Jlt0E';

    $timestamp = time();

    // Build signature: sorted params (excluding file, api_key, resource_type, cloud_name) + api_secret
    $sigParams = "folder=$folder&timestamp=$timestamp";
    $signature = sha1($sigParams . $apiSecret);

    $url = "https://api.cloudinary.com/v1_1/$cloudName/$resourceType/upload";

    $postFields = [
        'file'      => new CURLFile($tmpFilePath),
        'api_key'   => $apiKey,
        'timestamp' => $timestamp,
        'signature' => $signature,
        'folder'    => $folder,
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // needed on some servers
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("Cloudinary cURL error: $curlError");
        return null;
    }

    $result = json_decode($response, true);
    if (!empty($result['secure_url'])) {
        return $result['secure_url']; // e.g. https://res.cloudinary.com/iztk39ed/image/upload/...
    }

    error_log("Cloudinary upload failed: $response");
    return null;
}
