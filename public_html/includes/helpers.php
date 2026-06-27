<?php
/**
 * Shared helper functions for Sewlovely Homeset
 * Include this file wherever you need getStatusBadge(), getTypeLabel(), or generateInvoiceNumber().
 */

/**
 * Generate a consistent invoice number format.
 * Format: INV-{YYYYMMDD}-{zero-padded survey_id}
 * Example: INV-20260512-0005
 */
function generateInvoiceNumber($survey_id) {
    return 'INV-' . date('Ymd') . '-' . str_pad($survey_id, 4, '0', STR_PAD_LEFT);
}

/**
 * Return an HTML badge for survey status.
 */
function getStatusBadge($status) {
    $config = [
        'pending'         => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => 'Menunggu'],
        'survey'          => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'Proses Survey'],
        'waiting_payment' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'label' => 'Menunggu Pembayaran'],
        'production'      => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-700', 'label' => 'Pengerjaan'],
        'installation'    => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'label' => 'Pemasangan'],
        'done'            => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'label' => 'Selesai'],
        'cancelled'       => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => 'Batal'],
    ];
    $cfg = isset($config[$status]) ? $config[$status] : ['bg' => 'bg-slate-100', 'text' => 'text-slate-500', 'label' => $status];
    return "<span class=\"px-3 py-1 rounded-full text-xs font-bold capitalize {$cfg['bg']} {$cfg['text']}\">{$cfg['label']}</span>";
}

/**
 * Return a human-readable label for calculator/product type.
 */
function getTypeLabel($type) {
    $labels = [
        'rumah'  => 'Gorden Rumah',
        'gorden' => 'Gorden Rumah',
        'kantor' => 'Gorden Kantor',
        'rs'     => 'Gorden RS',
        'sprei'  => 'Sprei & Bedcover'
    ];
    return isset($labels[$type]) ? $labels[$type] : $type;
}

/**
 * Allowed status transitions map.
 * Returns true if $from -> $to is a valid transition.
 */
function isValidStatusTransition($from, $to) {
    $allowed = [
        'pending'         => ['survey', 'cancelled'],
        'survey'          => ['waiting_payment', 'pending', 'cancelled'],
        'waiting_payment' => ['production', 'survey', 'cancelled'],
        'production'      => ['installation', 'waiting_payment', 'survey'],
        'installation'    => ['done', 'production'],
        'done'            => ['installation'],
        'cancelled'       => [],
    ];
    if (!isset($allowed[$from])) return false;
    return in_array($to, $allowed[$from]);
}
