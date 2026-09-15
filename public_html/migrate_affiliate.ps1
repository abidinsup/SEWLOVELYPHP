# ============================================
# Migrasi Division: Lainnya → Affiliate
# Tiga perubahan sekaligus dalam satu script
# ============================================

$ErrorActionPreference = "Stop"
$basePath = Split-Path -Parent $MyInvocation.MyCommand.Path

Write-Host "=== Migrasi Affiliate ===" -ForegroundColor Cyan

# ✅ 1. api/register.php — komisi affiliate jadi 5%
$registerFile = Join-Path $basePath "api\register.php"
if (Test-Path $registerFile) {
    $content = Get-Content $registerFile -Raw
    $old = "`$commissionRate = (`$division === 'marketing') ? 5 : 2.5;"
    $new = "`$commissionRate = (`$division === 'marketing' || `$division === 'affiliate') ? 5 : 2.5;"
    if ($content.Contains($old)) {
        $content = $content.Replace($old, $new)
        Set-Content $registerFile -Value $content -NoNewline
        Write-Host "[OK] api/register.php — komisi affiliate diset 5%" -ForegroundColor Green
    } else {
        Write-Host "[SKIP] api/register.php — pattern tidak ditemukan (mungkin sudah diubah)" -ForegroundColor Yellow
    }
} else {
    Write-Host "[ERROR] File tidak ditemukan: $registerFile" -ForegroundColor Red
}

# ✅ 2. admin/partners.php — label Lainnya → Affiliate
$partnersFile = Join-Path $basePath "admin\partners.php"
if (Test-Path $partnersFile) {
    $content = Get-Content $partnersFile -Raw
    if ($content.Contains(">Lainnya<")) {
        $content = $content.Replace(">Lainnya<", ">Affiliate<")
        Set-Content $partnersFile -Value $content -NoNewline
        Write-Host "[OK] admin/partners.php — label Lainnya diubah ke Affiliate" -ForegroundColor Green
    } else {
        Write-Host "[SKIP] admin/partners.php — pattern tidak ditemukan (mungkin sudah diubah)" -ForegroundColor Yellow
    }
} else {
    Write-Host "[ERROR] File tidak ditemukan: $partnersFile" -ForegroundColor Red
}

# ✅ 3. Database — ENUM + migrasi data lama
$phpMigration = @'
<?php
require __DIR__ . '/includes/config.php';

try {
    // Step 1: Tambah 'affiliate' ke ENUM (sementara biarkan 'lainnya')
    $pdo->exec("ALTER TABLE partners MODIFY division ENUM('marketing','security','lainnya','affiliate') NOT NULL DEFAULT 'marketing'");

    // Step 2: Migrasi data lama
    $stmt = $pdo->query("UPDATE partners SET division='affiliate' WHERE division='lainnya'");
    $affected = $stmt->rowCount();

    // Step 3: Hapus 'lainnya' dari ENUM
    $pdo->exec("ALTER TABLE partners MODIFY division ENUM('marketing','security','affiliate') NOT NULL DEFAULT 'marketing'");

    echo "Done! $affected row(s) migrated from 'lainnya' to 'affiliate'.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit(1);
}
'@

$tempPhp = Join-Path $basePath "_migrate_affiliate_temp.php"
Set-Content $tempPhp -Value $phpMigration -NoNewline

Write-Host "`nMenjalankan migrasi database..." -ForegroundColor Cyan
try {
    $result = & php $tempPhp 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "[OK] Database — $result" -ForegroundColor Green
    } else {
        Write-Host "[ERROR] Database — $result" -ForegroundColor Red
    }
} finally {
    # Bersihkan file temp
    Remove-Item $tempPhp -ErrorAction SilentlyContinue
}

Write-Host "`n=== Migrasi selesai! ===" -ForegroundColor Cyan
