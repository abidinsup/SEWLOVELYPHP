<?php
/**
 * AJAX handler for Curtain Catalog Management (Fabrics & Rails)
 */
require_once '../includes/session.php';
require_once '../includes/config.php';
checkAdmin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$entity = $_POST['entity'] ?? ''; // 'fabric' or 'rail'

try {
    switch ($action) {

        // ============== FABRIC OPERATIONS ==============
        case 'add_fabric':
            $stmt = $pdo->prepare("INSERT INTO curtain_fabrics (name, type, price_per_meter, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                trim($_POST['name']),
                $_POST['type'],
                (float) str_replace('.', '', $_POST['price_per_meter']),
                trim($_POST['description'] ?? '')
            ]);
            echo json_encode(['success' => true, 'message' => 'Kain berhasil ditambahkan']);
            break;

        case 'edit_fabric':
            $stmt = $pdo->prepare("UPDATE curtain_fabrics SET name = ?, type = ?, price_per_meter = ?, description = ? WHERE id = ?");
            $stmt->execute([
                trim($_POST['name']),
                $_POST['type'],
                (float) str_replace('.', '', $_POST['price_per_meter']),
                trim($_POST['description'] ?? ''),
                (int) $_POST['id']
            ]);
            echo json_encode(['success' => true, 'message' => 'Kain berhasil diperbarui']);
            break;

        case 'delete_fabric':
            $stmt = $pdo->prepare("DELETE FROM curtain_fabrics WHERE id = ?");
            $stmt->execute([(int) $_POST['id']]);
            echo json_encode(['success' => true, 'message' => 'Kain berhasil dihapus']);
            break;

        case 'toggle_fabric':
            $stmt = $pdo->prepare("UPDATE curtain_fabrics SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([(int) $_POST['id']]);
            echo json_encode(['success' => true, 'message' => 'Status kain berhasil diubah']);
            break;

        // ============== RAIL OPERATIONS ==============
        case 'add_rail':
            $stmt = $pdo->prepare("INSERT INTO curtain_rails (name, type, price_per_meter) VALUES (?, ?, ?)");
            $stmt->execute([
                trim($_POST['name']),
                $_POST['type'],
                (float) str_replace('.', '', $_POST['price_per_meter'])
            ]);
            echo json_encode(['success' => true, 'message' => 'Rel berhasil ditambahkan']);
            break;

        case 'edit_rail':
            $stmt = $pdo->prepare("UPDATE curtain_rails SET name = ?, type = ?, price_per_meter = ? WHERE id = ?");
            $stmt->execute([
                trim($_POST['name']),
                $_POST['type'],
                (float) str_replace('.', '', $_POST['price_per_meter']),
                (int) $_POST['id']
            ]);
            echo json_encode(['success' => true, 'message' => 'Rel berhasil diperbarui']);
            break;

        case 'delete_rail':
            $stmt = $pdo->prepare("DELETE FROM curtain_rails WHERE id = ?");
            $stmt->execute([(int) $_POST['id']]);
            echo json_encode(['success' => true, 'message' => 'Rel berhasil dihapus']);
            break;

        // ============== FETCH FOR CALCULATOR ==============
        case 'get_catalog':
            $fabrics_gorden = $pdo->query("SELECT id, name, price_per_meter FROM curtain_fabrics WHERE type = 'gorden' AND is_active = 1 ORDER BY name")->fetchAll();
            $fabrics_vitrase = $pdo->query("SELECT id, name, price_per_meter FROM curtain_fabrics WHERE type = 'vitrase' AND is_active = 1 ORDER BY name")->fetchAll();
            $rails_single = $pdo->query("SELECT id, name, price_per_meter FROM curtain_rails WHERE type = 'single' AND is_active = 1 ORDER BY name")->fetchAll();
            $rails_double = $pdo->query("SELECT id, name, price_per_meter FROM curtain_rails WHERE type = 'double' AND is_active = 1 ORDER BY name")->fetchAll();

            echo json_encode([
                'success' => true,
                'fabrics_gorden' => $fabrics_gorden,
                'fabrics_vitrase' => $fabrics_vitrase,
                'rails_single' => $rails_single,
                'rails_double' => $rails_double
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
