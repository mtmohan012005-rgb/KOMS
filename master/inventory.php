<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$master_id = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id, name FROM dojos WHERE master_id = ? AND status = 'approved' LIMIT 1");
$stmt->execute([$master_id]);
$dojo = $stmt->fetch();

if (!$dojo) {
    $_SESSION['error_msg'] = 'You need an approved dojo to manage inventory.';
    redirect('/master/dashboard.php');
}

$dojo_id = (int)$dojo['id'];
$error = '';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS dojo_inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        dojo_id INT NOT NULL,
        item_name VARCHAR(150) NOT NULL,
        category VARCHAR(80) NOT NULL DEFAULT 'General',
        quantity INT NOT NULL DEFAULT 0,
        unit VARCHAR(40) NOT NULL DEFAULT 'pcs',
        reorder_level INT NOT NULL DEFAULT 0,
        cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        supplier VARCHAR(150) NULL,
        notes TEXT NULL,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_inventory_dojo (dojo_id),
        INDEX idx_inventory_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {
    $error = 'Inventory storage could not be prepared.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            $item_id = (int)($_POST['item_id'] ?? 0);
            $item_name = trim(sanitize_input($_POST['item_name'] ?? ''));
            $category = trim(sanitize_input($_POST['category'] ?? 'General')) ?: 'General';
            $quantity = max(0, (int)($_POST['quantity'] ?? 0));
            $unit = trim(sanitize_input($_POST['unit'] ?? 'pcs')) ?: 'pcs';
            $reorder_level = max(0, (int)($_POST['reorder_level'] ?? 0));
            $cost = max(0, (float)($_POST['cost'] ?? 0));
            $supplier = trim(sanitize_input($_POST['supplier'] ?? ''));
            $notes = trim(sanitize_input($_POST['notes'] ?? ''));

            if ($item_name === '') {
                $error = 'Item name is required.';
            } elseif (mb_strlen($item_name) > 150) {
                $error = 'Item name is too long.';
            } else {
                if ($item_id > 0) {
                    $check = $pdo->prepare("SELECT id FROM dojo_inventory WHERE id = ? AND dojo_id = ? LIMIT 1");
                    $check->execute([$item_id, $dojo_id]);
                    if (!$check->fetchColumn()) {
                        $error = 'Inventory item not found.';
                    } else {
                        $update = $pdo->prepare("UPDATE dojo_inventory SET item_name = ?, category = ?, quantity = ?, unit = ?, reorder_level = ?, cost = ?, supplier = ?, notes = ? WHERE id = ? AND dojo_id = ?");
                        $update->execute([$item_name, $category, $quantity, $unit, $reorder_level, $cost, $supplier ?: null, $notes ?: null, $item_id, $dojo_id]);
                        log_audit_action($pdo, $master_id, 'UPDATE', 'dojo_inventory', $item_id, 'Updated inventory item: ' . $item_name);
                        $_SESSION['success_msg'] = 'Inventory item updated successfully.';
                        redirect('/master/inventory.php');
                    }
                } else {
                    $insert = $pdo->prepare("INSERT INTO dojo_inventory (dojo_id, item_name, category, quantity, unit, reorder_level, cost, supplier, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $insert->execute([$dojo_id, $item_name, $category, $quantity, $unit, $reorder_level, $cost, $supplier ?: null, $notes ?: null, $master_id]);
                    $new_id = (int)$pdo->lastInsertId();
                    log_audit_action($pdo, $master_id, 'CREATE', 'dojo_inventory', $new_id, 'Created inventory item: ' . $item_name);
                    $_SESSION['success_msg'] = 'Inventory item added successfully.';
                    redirect('/master/inventory.php');
                }
            }
        } elseif ($action === 'delete') {
            $item_id = (int)($_POST['item_id'] ?? 0);
            $check = $pdo->prepare("SELECT item_name FROM dojo_inventory WHERE id = ? AND dojo_id = ? LIMIT 1");
            $check->execute([$item_id, $dojo_id]);
            $item_name = $check->fetchColumn();
            if ($item_name === false) {
                $error = 'Inventory item not found.';
            } else {
                $delete = $pdo->prepare("DELETE FROM dojo_inventory WHERE id = ? AND dojo_id = ?");
                $delete->execute([$item_id, $dojo_id]);
                log_audit_action($pdo, $master_id, 'DELETE', 'dojo_inventory', $item_id, 'Deleted inventory item: ' . $item_name);
                $_SESSION['success_msg'] = 'Inventory item deleted.';
                redirect('/master/inventory.php');
            }
        }
    }
}

$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_stmt = $pdo->prepare("SELECT * FROM dojo_inventory WHERE id = ? AND dojo_id = ? LIMIT 1");
    $edit_stmt->execute([$edit_id, $dojo_id]);
    $edit_item = $edit_stmt->fetch() ?: null;
}

$list_stmt = $pdo->prepare("SELECT * FROM dojo_inventory WHERE dojo_id = ? ORDER BY CASE WHEN quantity <= reorder_level THEN 0 ELSE 1 END, item_name ASC");
$list_stmt->execute([$dojo_id]);
$items = $list_stmt->fetchAll();

$total_items = count($items);
$low_stock = 0;
$out_of_stock = 0;
$total_value = 0.0;
foreach ($items as $item) {
    if ((int)$item['quantity'] <= (int)$item['reorder_level']) $low_stock++;
    if ((int)$item['quantity'] === 0) $out_of_stock++;
    $total_value += ((float)$item['quantity'] * (float)$item['cost']);
}

$page_title = 'Inventory';
require_once '../includes/header.php';
?>

<style>
.inv-wrap{max-width:1200px;margin:1.5rem auto}.inv-hero{background:linear-gradient(135deg,#080808,#202020 58%,#4d0808);color:#fff;border-radius:24px;padding:1.8rem 2rem;box-shadow:0 22px 50px rgba(0,0,0,.15)}.inv-kicker{font-size:.72rem;letter-spacing:.14em;text-transform:uppercase;font-weight:800;color:#ffd15e}.inv-title{font-size:clamp(1.7rem,4vw,2.7rem);font-weight:900;margin:.25rem 0 .45rem}.metric{background:#fff;border:0;border-radius:18px;padding:1rem 1.1rem;box-shadow:0 12px 30px rgba(17,24,39,.06);height:100%}.metric-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#888;font-weight:800}.metric-value{font-size:1.65rem;font-weight:900}.panel{border:0;border-radius:20px;overflow:hidden;box-shadow:0 15px 38px rgba(17,24,39,.08)}.panel .card-header{background:#fff;border:0;padding:1.2rem 1.35rem}.panel .card-body{background:#fff;padding:1.35rem}.form-control,.form-select{border-radius:12px;padding:.7rem .82rem}.table th{font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:#777;white-space:nowrap}.stock-badge{display:inline-flex;border-radius:999px;padding:.4rem .65rem;font-size:.72rem;font-weight:800}.stock-ok{background:#ddf5e5;color:#166534}.stock-low{background:#fff2c2;color:#7a5700}.stock-out{background:#ffe1e1;color:#991b1b}.table td{padding-top:.9rem;padding-bottom:.9rem}@media(max-width:768px){.inv-table{min-width:980px}.inv-hero{padding:1.35rem}}
</style>

<div class="inv-wrap">
    <section class="inv-hero mb-4"><div class="inv-kicker">Master Control • Dojo Assets</div><div class="inv-title">Inventory Management</div><p class="mb-0" style="color:rgba(255,255,255,.72)">Track uniforms, belts, training equipment, certificates, office supplies and other dojo stock.</p></section>

    <?php if ($error): ?><div class="alert alert-danger border-0 shadow-sm mb-4"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="metric"><div class="metric-label">Items</div><div class="metric-value"><?= $total_items ?></div></div></div>
        <div class="col-md-3"><div class="metric"><div class="metric-label">Low stock</div><div class="metric-value text-warning"><?= $low_stock ?></div></div></div>
        <div class="col-md-3"><div class="metric"><div class="metric-label">Out of stock</div><div class="metric-value text-danger"><?= $out_of_stock ?></div></div></div>
        <div class="col-md-3"><div class="metric"><div class="metric-label">Stock value</div><div class="metric-value">₹<?= number_format($total_value,2) ?></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card panel">
                <div class="card-header"><div class="small text-muted fw-bold text-uppercase"><?= $edit_item ? 'Edit item' : 'New item' ?></div><h5 class="mb-0 mt-1"><?= $edit_item ? 'Update Inventory' : 'Add Inventory Item' ?></h5></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="item_id" value="<?= $edit_item ? (int)$edit_item['id'] : 0 ?>">
                        <div class="mb-3"><label class="form-label fw-bold">Item name</label><input type="text" name="item_name" class="form-control" maxlength="150" required value="<?= htmlspecialchars($edit_item['item_name'] ?? '') ?>" placeholder="Karate uniform"></div>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label fw-bold">Category</label><input type="text" name="category" class="form-control" maxlength="80" value="<?= htmlspecialchars($edit_item['category'] ?? 'General') ?>" placeholder="Equipment"></div>
                            <div class="col-md-6"><label class="form-label fw-bold">Unit</label><input type="text" name="unit" class="form-control" maxlength="40" value="<?= htmlspecialchars($edit_item['unit'] ?? 'pcs') ?>" placeholder="pcs"></div>
                            <div class="col-md-6"><label class="form-label fw-bold">Quantity</label><input type="number" name="quantity" class="form-control" min="0" value="<?= (int)($edit_item['quantity'] ?? 0) ?>"></div>
                            <div class="col-md-6"><label class="form-label fw-bold">Reorder level</label><input type="number" name="reorder_level" class="form-control" min="0" value="<?= (int)($edit_item['reorder_level'] ?? 0) ?>"></div>
                            <div class="col-md-6"><label class="form-label fw-bold">Unit cost (₹)</label><input type="number" name="cost" class="form-control" min="0" step="0.01" value="<?= htmlspecialchars($edit_item['cost'] ?? '0.00') ?>"></div>
                            <div class="col-md-6"><label class="form-label fw-bold">Supplier</label><input type="text" name="supplier" class="form-control" maxlength="150" value="<?= htmlspecialchars($edit_item['supplier'] ?? '') ?>"></div>
                        </div>
                        <div class="mb-3 mt-3"><label class="form-label fw-bold">Notes</label><textarea name="notes" class="form-control" rows="4" maxlength="1000" placeholder="Size, color, location or purchase notes..."><?= htmlspecialchars($edit_item['notes'] ?? '') ?></textarea></div>
                        <div class="d-flex gap-2"><button class="btn btn-dark flex-grow-1"><i class="fas fa-boxes-stacked me-2"></i><?= $edit_item ? 'Update Item' : 'Add Item' ?></button><?php if ($edit_item): ?><a href="inventory.php" class="btn btn-outline-secondary">Cancel</a><?php endif; ?></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card panel">
                <div class="card-header d-flex justify-content-between align-items-center gap-2"><div><div class="small text-muted fw-bold text-uppercase">Stock register</div><h5 class="mb-0 mt-1">Dojo Inventory</h5></div><a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Dashboard</a></div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0 inv-table"><thead class="table-light"><tr><th>Item</th><th>Category</th><th>Stock</th><th>Unit Cost</th><th>Value</th><th>Supplier</th><th>Action</th></tr></thead><tbody>
                    <?php foreach ($items as $item): ?>
                        <?php $qty=(int)$item['quantity']; $reorder=(int)$item['reorder_level']; $stock_class=$qty===0?'stock-out':(($qty<=$reorder)?'stock-low':'stock-ok'); $stock_text=$qty===0?'OUT OF STOCK':(($qty<=$reorder)?'LOW STOCK':'IN STOCK'); ?>
                        <tr><td><div class="fw-bold"><?= htmlspecialchars($item['item_name']) ?></div><?php if ($item['notes']): ?><div class="small text-muted"><?= htmlspecialchars($item['notes']) ?></div><?php endif; ?></td><td><?= htmlspecialchars($item['category']) ?></td><td><span class="stock-badge <?= $stock_class ?>"><?= $qty.' '.htmlspecialchars($item['unit']) ?></span><div class="small text-muted mt-1">Reorder at <?= $reorder ?></div></td><td>₹<?= number_format((float)$item['cost'],2) ?></td><td class="fw-bold">₹<?= number_format($qty*(float)$item['cost'],2) ?></td><td><?= htmlspecialchars($item['supplier'] ?: '-') ?></td><td><div class="d-flex gap-1"><a class="btn btn-sm btn-outline-dark" href="inventory.php?edit=<?= (int)$item['id'] ?>"><i class="fas fa-pen"></i></a><form method="POST" onsubmit="return confirm('Delete this inventory item?');"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form></div></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$items): ?><tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-box-open fa-2x mb-2"></i><div class="fw-bold">No inventory items yet</div><div class="small">Add your first dojo stock item using the form.</div></td></tr><?php endif; ?>
                </tbody></table></div></div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
