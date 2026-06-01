<?php
require_once __DIR__ . '/config.php';
// Default Configuration
$site_title = isset($page_title) ? $page_title . " - Sewlovely Homeset" : "Sewlovely Homeset";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($site_title); ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (Generated) -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Supabase JS Client (Optional for Frontend direct DB access) -->
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .swal2-popup {
            border-radius: 1.5rem !important;
            padding: 2rem !important;
        }
        .swal2-title {
            font-weight: 800 !important;
            color: #0f172a !important;
        }
        .swal2-confirm {
            background-color: #059669 !important;
            border-radius: 1rem !important;
            padding: 0.75rem 2rem !important;
            font-weight: 700 !important;
        }
        .swal2-cancel {
            border-radius: 1rem !important;
            padding: 0.75rem 2rem !important;
            font-weight: 700 !important;
        }
    </style>
</head>
<body class="antialiased bg-slate-50 text-slate-900 selection:bg-emerald-100 selection:text-emerald-900 overflow-x-hidden">
    <div class="min-h-screen flex flex-col lg:flex-row relative">

