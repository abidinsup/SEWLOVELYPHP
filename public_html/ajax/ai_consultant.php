<?php
require_once '../includes/session.php';
require_once '../includes/config.php';

// Hanya admin yang boleh akses fitur AI Consultant
checkAdmin();

header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$query = isset($input['query']) ? $input['query'] : null;
$image = isset($input['image']) ? $input['image'] : null; // Base64 image data string

if (!$query && !$image) {
    echo json_encode(['success' => false, 'error' => 'No input provided']);
    exit;
}

$apiKey = GEMINI_API_KEY;
$model = "gemini-2.0-flash-lite"; // Using flash-lite for speed and cost efficiency
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

$promptText = "Kamu adalah konsultan desain interior profesional yang ahli dalam pemilihan gorden/tirai untuk Sewlovely Homeset. ";

if ($image) {
    // Vision Prompt
    $promptText .= "Analisis foto ruangan ini dan berikan rekomendasi gorden yang paling cocok dengan fokus utama pada PEMILIHAN WARNA.
    
    Berikan respons dalam format JSON dengan struktur berikut (HANYA JSON, tanpa markdown atau text lain):
    {
        \"title\": \"Nama warna yang direkomendasikan (contoh: Warna Beige)\",
        \"description\": \"Penjelasan singkat (2-3 kalimat) mengapa warna tersebut cocok untuk ruangan ini, mempertimbangkan warna dinding, furniture, dan pencahayaan\",
        \"colorHex\": \"Kode warna hex untuk warna gorden yang direkomendasikan (contoh: #F5F5DC)\",
        \"fabricType\": \"PILIH SALAH SATU: Blackout atau Dimout\",
        \"style\": \"PILIH SALAH SATU: Modern atau Minimalis\"
    }";
    
    // Prepare parts for vision request
    // Extract mime type from base64 string if present (e.g. data:image/jpeg;base64,...)
    $mimeType = "image/jpeg";
    $base64Data = $image;
    if (preg_match('/^data:(image\/[a-z]+);base64,(.*)$/', $image, $matches)) {
        $mimeType = $matches[1];
        $base64Data = $matches[2];
    }

    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $promptText],
                    [
                        "inlineData" => [
                            "mimeType" => $mimeType,
                            "data" => $base64Data
                        ]
                    ]
                ]
            ]
        ]
    ];
} else {
    // Text Chat Prompt
    $promptText .= "Jawablah pertanyaan berikut dengan gaya bahasa yang profesional, ramah, dan membantu.
    Gunakan istilah teknis gorden seperti 'Blackout', 'Dimout', 'Vitras', 'Smokering', dll jika relevan.
    
    Pertanyaan: " . $query;

    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $promptText]
                ]
            ]
        ]
    ];
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For some hosting environments

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode([
        'success' => false, 
        'error' => 'AI Service Error', 
        'details' => json_decode($response, true)
    ]);
    exit;
}

$result = json_decode($response, true);
$aiText = $result['candidates'][0]['content']['parts'][0]['text'];

// If image, try to parse JSON from AI response
if ($image) {
    // Clean up markdown code blocks if AI included them
    $cleanedText = trim($aiText);
    $cleanedText = preg_replace('/^```json\s*|\s*```$/i', '', $cleanedText);
    
    $recommendation = json_decode($cleanedText, true);
    
    if ($recommendation) {
        echo json_encode([
            'success' => true,
            'type' => 'recommendation',
            'data' => $recommendation
        ]);
    } else {
        // Fallback if parsing fails
        echo json_encode([
            'success' => true,
            'type' => 'text',
            'text' => $aiText
        ]);
    }
} else {
    // Just return text for chat
    echo json_encode([
        'success' => true,
        'type' => 'text',
        'text' => $aiText
    ]);
}
