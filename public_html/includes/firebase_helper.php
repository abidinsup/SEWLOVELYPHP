<?php
/**
 * Firebase Cloud Messaging Helper
 * Mengirim push notification via FCM HTTP v1 API
 * 
 * Membutuhkan:
 * - firebase-service-account.json di folder includes/
 * - PHP 7.4+ dengan openssl extension
 */

// Path ke file service account JSON
define('FIREBASE_SERVICE_ACCOUNT_PATH', __DIR__ . '/firebase-service-account.json');

/**
 * Generate JWT token dari service account untuk OAuth2
 */
function generateFirebaseJWT() {
    $serviceAccount = json_decode(file_get_contents(FIREBASE_SERVICE_ACCOUNT_PATH), true);
    
    if (!$serviceAccount) {
        error_log('FCM Error: Tidak bisa membaca firebase-service-account.json');
        return null;
    }
    
    $now = time();
    
    // JWT Header
    $header = base64url_encode(json_encode([
        'alg' => 'RS256',
        'typ' => 'JWT'
    ]));
    
    // JWT Payload
    $payload = base64url_encode(json_encode([
        'iss' => $serviceAccount['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600
    ]));
    
    // Sign dengan private key
    $signatureInput = "$header.$payload";
    $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);
    
    if (!$privateKey) {
        error_log('FCM Error: Private key tidak valid');
        return null;
    }
    
    openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    $encodedSignature = base64url_encode($signature);
    
    return "$header.$payload.$encodedSignature";
}

/**
 * Base64 URL-safe encode
 */
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Dapatkan OAuth2 Access Token dari Google
 */
function getFirebaseAccessToken() {
    $jwt = generateFirebaseJWT();
    if (!$jwt) return null;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://oauth2.googleapis.com/token',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("FCM Error: Gagal mendapat access token. HTTP $httpCode. Response: $response");
        return null;
    }
    
    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

/**
 * Dapatkan Project ID dari service account
 */
function getFirebaseProjectId() {
    $serviceAccount = json_decode(file_get_contents(FIREBASE_SERVICE_ACCOUNT_PATH), true);
    return $serviceAccount['project_id'] ?? null;
}

/**
 * Kirim notifikasi ke satu device token
 * 
 * @param string $token FCM device token
 * @param string $title Judul notifikasi
 * @param string $body Isi notifikasi
 * @param array $data Data tambahan (optional)
 * @return bool Success or not
 */
function sendFCMToDevice($token, $title, $body, $data = []) {
    $accessToken = getFirebaseAccessToken();
    $projectId = getFirebaseProjectId();
    
    if (!$accessToken || !$projectId) {
        error_log('FCM Error: Gagal mendapat access token atau project ID');
        return false;
    }
    
    $message = [
        'message' => [
            'token' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body
            ],
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'sewlovely_notifications',
                    'sound' => 'default'
                ]
            ],
            'data' => array_map('strval', $data) // FCM data harus string
        ]
    ];
    
    $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($message),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $accessToken",
            'Content-Type: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("FCM Error: Gagal kirim notifikasi. HTTP $httpCode. Response: $response");
        return false;
    }
    
    return true;
}

/**
 * Kirim notifikasi ke user tertentu (semua device-nya)
 * Juga menyimpan ke tabel notifications
 * 
 * @param PDO $pdo Database connection
 * @param int $userId Target user ID
 * @param string $title Judul notifikasi
 * @param string $body Isi notifikasi  
 * @param string $type Tipe: commission, withdrawal, promo
 * @param array $data Data tambahan (optional)
 */
function sendNotificationToUser($pdo, $userId, $title, $body, $type, $data = []) {
    // 1. Simpan ke tabel notifications
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, body, type, data) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $title, $body, $type, json_encode($data)]);
    } catch (PDOException $e) {
        error_log("FCM DB Error: " . $e->getMessage());
    }
    
    // 2. Ambil semua FCM token user ini
    try {
        $stmt = $pdo->prepare("SELECT token FROM fcm_tokens WHERE user_id = ?");
        $stmt->execute([$userId]);
        $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // 3. Kirim ke setiap device
        foreach ($tokens as $token) {
            sendFCMToDevice($token, $title, $body, array_merge($data, ['type' => $type]));
        }
    } catch (PDOException $e) {
        error_log("FCM Token Error: " . $e->getMessage());
    }
}

/**
 * Kirim notifikasi broadcast ke SEMUA user
 * 
 * @param PDO $pdo Database connection
 * @param string $title Judul notifikasi
 * @param string $body Isi notifikasi
 * @param array $data Data tambahan (optional)
 */
function sendBroadcastNotification($pdo, $title, $body, $data = []) {
    // 1. Simpan ke tabel notifications (user_id = NULL = broadcast)
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, body, type, data) VALUES (NULL, ?, ?, 'promo', ?)");
        $stmt->execute([$title, $body, json_encode($data)]);
    } catch (PDOException $e) {
        error_log("FCM DB Error: " . $e->getMessage());
    }
    
    // 2. Ambil semua FCM token yang aktif
    try {
        $stmt = $pdo->query("SELECT DISTINCT token FROM fcm_tokens");
        $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $sent = 0;
        $failed = 0;
        
        // 3. Kirim ke setiap device
        foreach ($tokens as $token) {
            $result = sendFCMToDevice($token, $title, $body, array_merge($data, ['type' => 'promo']));
            if ($result) {
                $sent++;
            } else {
                $failed++;
            }
        }
        
        return ['sent' => $sent, 'failed' => $failed, 'total' => count($tokens)];
    } catch (PDOException $e) {
        error_log("FCM Broadcast Error: " . $e->getMessage());
        return ['sent' => 0, 'failed' => 0, 'total' => 0];
    }
}
?>
