<?php
$pageTitle = 'Products';
require_once __DIR__ . '/includes/header.php';

$action = $_GET['action'] ?? 'list';
$userRole = $_SESSION['user_role'] ?? 'user';
$isDeveloper = $userRole === 'developer';
$currentSection = getCurrentSection();
$userId = $_SESSION['user_id'];

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT file_path FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if ($product && canManageProduct($id)) {
        if ($product['file_path'] && file_exists(UPLOAD_DIR . $product['file_path'])) {
            unlink(UPLOAD_DIR . $product['file_path']);
        }

        $screenshots = json_decode($product['screenshots'] ?? '[]', true);
        foreach ($screenshots as $screenshot) {
            if (file_exists(SCREENSHOT_DIR . $screenshot)) {
                unlink(SCREENSHOT_DIR . $screenshot);
            }
        }

        // Delete extra product files from disk
        $pfStmt = $pdo->prepare("SELECT file_path FROM product_files WHERE product_id = ?");
        $pfStmt->execute([$id]);
        foreach ($pfStmt->fetchAll(PDO::FETCH_COLUMN) as $pfPath) {
            if ($pfPath && file_exists(UPLOAD_DIR . $pfPath)) {
                unlink(UPLOAD_DIR . $pfPath);
            }
        }
        $pdo->prepare("DELETE FROM product_files WHERE product_id = ?")->execute([$id]);

        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        setFlash('success', 'Product deleted successfully.');
    }
    redirect(ADMIN_URL . '/products.php');
}

// Handle approve/reject (admin only)
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    if (!$isDeveloper && canManageProduct($id)) {
        $pdo->prepare("UPDATE products SET status = 'active' WHERE id = ?")->execute([$id]);
        setFlash('success', 'Product approved and published.');
    }
    redirect(ADMIN_URL . '/products.php');
}
if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    if (!$isDeveloper && canManageProduct($id)) {
        $reason = sanitize($_GET['reason'] ?? '');
        $pdo->prepare("UPDATE products SET status = 'inactive', rejection_reason = ? WHERE id = ?")->execute([$reason, $id]);
        setFlash('success', 'Product rejected.');
    }
    redirect(ADMIN_URL . '/products.php');
}

// CSRF check for all POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if ($csrf !== ($_SESSION['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(ADMIN_URL . '/products.php');
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = $_POST['description'];
    $shortDescription = sanitize($_POST['short_description']);
    $price = (float)$_POST['price'];
    $salePrice = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
    $isFree = isset($_POST['is_free']) ? 1 : 0;
    $categoryId = isset($_POST['category_id']) && $_POST['category_id'] ? (int)$_POST['category_id'] : null;
    $status = sanitize($_POST['status']);
    $demoUrl = sanitize($_POST['demo_url']);
    $downloadLimit = (int)($_POST['download_limit'] ?? 5);
    $downloadExpiry = (int)($_POST['download_expiry'] ?? 48);
    $productId = (int)($_POST['product_id'] ?? 0);
    $productType = sanitize($_POST['product_type'] ?? $currentSection);
    $fileSourceType = sanitize($_POST['file_source_type'] ?? 'upload');
    $fileUrl = sanitizeUrl($_POST['file_url'] ?? '');
    $imageSourceType = sanitize($_POST['image_source_type'] ?? 'upload');
    $imageUrl = sanitizeUrl($_POST['image_url'] ?? '');

    $errors = [];
    if (empty($title)) $errors[] = 'Title is required.';
    if (empty($description)) $errors[] = 'Description is required.';

    if (!$isFree) {
        if ($price <= 0) $errors[] = 'Price must be greater than 0.';
    }

    if (empty($errors)) {
        $slug = generateSlug($title);

        $filePath = '';
        $fileName = '';
        $fileSize = '';
        $finalFileUrl = $fileSourceType === 'link' ? $fileUrl : '';

        if ($fileSourceType === 'upload' && isset($_FILES['product_file']) && $_FILES['product_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['product_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (in_array($ext, ALLOWED_FILE_TYPES)) {
                $newFileName = uniqid() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $newFileName)) {
                    $filePath = $newFileName;
                    $fileName = $file['name'];
                    $fileSize = formatFileSize($file['size']);
                }
            } else {
                $errors[] = 'Invalid file type.';
            }
        } elseif ($fileSourceType === 'link' && !empty($fileUrl)) {
            $fileName = basename(parse_url($fileUrl, PHP_URL_PATH)) ?: 'Download';
            $fileSize = 'External';
        }

        $finalImageUrl = $imageSourceType === 'link' ? $imageUrl : '';

        $existingScreenshots = [];
        if ($productId) {
            $stmt = $pdo->prepare("SELECT screenshots FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $existingScreenshots = json_decode($stmt->fetchColumn() ?: '[]', true);
        }

        $newScreenshots = $_POST['existing_screenshots'] ?? [];
        if (isset($_FILES['screenshots'])) {
            foreach ($_FILES['screenshots']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['screenshots']['error'][$key] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['screenshots']['name'][$key], PATHINFO_EXTENSION));
                    if (in_array($ext, ALLOWED_IMAGE_TYPES)) {
                        $newFileName = uniqid() . '.' . $ext;
                        if (move_uploaded_file($tmpName, SCREENSHOT_DIR . $newFileName)) {
                            $newScreenshots[] = $newFileName;
                        }
                    }
                }
            }
        }

        $screenshotsJson = json_encode($newScreenshots);

        if (empty($errors)) {
            if ($productId) {
                if (!canManageProduct($productId)) {
                    $errors[] = 'You cannot edit this product.';
                } else {
                    $sql = "UPDATE products SET title = ?, slug = ?, description = ?, short_description = ?, price = ?, sale_price = ?, is_free = ?, category_id = ?, status = ?, demo_url = ?, screenshots = ?, download_limit = ?, download_expiry_hours = ?, file_url = ?, image_url = ?, product_type = ?";
                    $params = [$title, $slug, $description, $shortDescription, $price, $salePrice, $isFree, $categoryId, $status, $demoUrl, $screenshotsJson, $downloadLimit, $downloadExpiry, $finalFileUrl, $finalImageUrl, $productType];

                    if ($filePath) {
                        $sql .= ", file_path = ?, file_name = ?, file_size = ?";
                        $params[] = $filePath;
                        $params[] = $fileName;
                        $params[] = $fileSize;
                    }

                    $sql .= " WHERE id = ?";
                    $params[] = $productId;

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    setFlash('success', 'Product updated successfully.');
                }
            } else {
                if (empty($filePath) && empty($finalFileUrl)) {
                    $errors[] = 'Product file or download link is required.';
                } else {
                    $devId = $isDeveloper ? $userId : null;
                    // Developer products start as pending (require admin approval)
                    $insertStatus = $isDeveloper ? 'pending' : $status;
                    $stmt = $pdo->prepare("
                        INSERT INTO products (developer_id, title, slug, description, short_description, price, sale_price, is_free, category_id, file_path, file_name, file_size, file_url, screenshots, image_url, demo_url, product_type, status, download_limit, download_expiry_hours)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$devId, $title, $slug, $description, $shortDescription, $price, $salePrice, $isFree, $categoryId, $filePath, $fileName, $fileSize, $finalFileUrl, $screenshotsJson, $finalImageUrl, $demoUrl, $productType, $insertStatus, $downloadLimit, $downloadExpiry]);
                    setFlash('success', $isDeveloper ? 'Product submitted for admin approval.' : 'Product added successfully.');
                }
            }

            redirect(ADMIN_URL . '/products.php');
        }

        if (!empty($errors)) {
            setFlash('error', implode(' ', $errors));
        }
    }
}

// Edit mode
$editProduct = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $productId = (int)$_GET['id'];
    if (canManageProduct($productId)) {
        $editProduct = getProduct($productId);
    }
    if (!$editProduct) {
        setFlash('error', 'Product not found.');
        redirect(ADMIN_URL . '/products.php');
    }
}

// Get products based on role and section
if ($isDeveloper) {
    $products = getDeveloperProducts($userId, ($currentSection !== 'general' ? $currentSection : null));
} elseif ($currentSection !== 'general') {
    $products = getProductsByType($currentSection);
} else {
    $products = $pdo->query("SELECT p.*, c.name as category_name, u.store_name as developer_store, u.name as developer_name FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN users u ON p.developer_id = u.id ORDER BY p.created_at DESC")->fetchAll();
}

$productTypes = getProductTypes();
?>

<?php if ($action === 'list'): ?>
<div class="d-flex justify-between align-center mb-3 flex-wrap gap-1">
    <h2 class="mb-0">Products<?php echo $currentSection !== 'general' ? ' - ' . getProductTypeLabel($currentSection) : ''; ?></h2>
    <div class="d-flex gap-1 flex-wrap">
        <a href="<?php echo SITE_URL; ?>/admin/pdf-preview.php?type=products" class="btn btn-sm btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export PDF
        </a>
        <a href="?action=add" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Product
        </a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Sales</th>
                    <th>Status</th>
                    <th>Developer</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 0; foreach ($products as $product): $i++; ?>
                <tr>
                    <td><?php echo $i; ?></td>
                    <td><?php echo $product['id']; ?></td>
                    <td><?php echo sanitize($product['title']); ?></td>
                    <td><span class="badge badge-info" style="font-size:0.65rem"><?php echo getProductTypeLabel($product['product_type'] ?? 'general'); ?></span></td>
                    <td><?php echo sanitize($product['category_name'] ?? '-'); ?></td>
                    <td>
                        <?php if ($product['is_free']): ?>
                        <span class="badge badge-success">Free</span>
                        <?php else: ?>
                        <?php echo formatPrice($product['sale_price'] ?? $product['price']); ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $product['sales_count']; ?></td>
                    <td><span class="badge badge-<?php echo $product['status'] === 'active' ? 'success' : ($product['status'] === 'pending' ? 'warning' : ($product['status'] === 'draft' ? 'secondary' : 'danger')); ?>"><?php echo ucfirst($product['status']); ?></span><?php if ($product['status'] === 'inactive' && !empty($product['rejection_reason'])): ?><br><small style="color:#dc3545;font-size:0.7rem;white-space:normal;display:block;max-width:180px;margin-top:2px" title="Rejection reason"><?php echo sanitize($product['rejection_reason']); ?></small><?php endif; ?></td>
                    <td><?php echo sanitize($product['developer_store'] ?? $product['developer_name'] ?? '-'); ?></td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="?action=edit&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                            <?php if ($product['status'] === 'pending' && !$isDeveloper): ?>
                            <a href="?approve=<?php echo $product['id']; ?>" class="btn btn-sm btn-success" onclick="showConfirm('Approve this product?',this.href);return false">Approve</a>
                            <a href="?reject=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" onclick="return rejectProduct(<?php echo $product['id']; ?>)">Reject</a>
                            <?php endif; ?>
                            <?php if (canManageProduct($product['id'])): ?>
                            <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" onclick="showConfirm('Are you sure you want to delete this?',this.href);return false">Delete</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
<div class="d-flex justify-between align-center mb-3 flex-wrap gap-1">
    <h2 class="mb-0"><?php echo $action === 'add' ? 'Add' : 'Edit'; ?> Product</h2>
    <a href="products.php" class="btn btn-secondary">Back to List</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" data-validate>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <?php if ($editProduct): ?>
            <input type="hidden" name="product_id" value="<?php echo $editProduct['id']; ?>">
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-2">
                <div class="form-group" style="flex:1;min-width:250px">
                    <label for="title">Product Title *</label>
                    <input type="text" id="title" name="title" class="form-control" value="<?php echo $editProduct ? sanitize($editProduct['title']) : (isset($_POST['title']) ? sanitize($_POST['title']) : ''); ?>" required>
                </div>

                <div class="form-group" style="flex:1;min-width:200px">
                    <label for="product_type">Category</label>
                    <select id="product_type" name="product_type" class="form-control">
                        <option value="general">General</option>
                        <?php foreach ($productTypes as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo (($editProduct && $editProduct['product_type'] === $key) || ($currentSection !== 'general' && $currentSection === $key) || (isset($_POST['product_type']) && $_POST['product_type'] === $key)) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="flex:1;min-width:150px">
                    <label for="status">Status</label>
                    <?php if ($isDeveloper): ?>
                    <input type="text" class="form-control" value="<?php echo $editProduct ? ucfirst($editProduct['status']) : 'Pending (awaiting approval)'; ?>" readonly>
                    <input type="hidden" name="status" value="<?php echo $editProduct ? $editProduct['status'] : 'pending'; ?>">
                    <?php else: ?>
                    <select id="status" name="status" class="form-control">
                        <option value="pending" <?php echo ($editProduct && $editProduct['status'] === 'pending') || (!isset($editProduct) && !isset($_POST['status'])) ? 'selected' : ''; ?>>Pending</option>
                        <option value="active" <?php echo ($editProduct && $editProduct['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($editProduct && $editProduct['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        <option value="draft" <?php echo ($editProduct && $editProduct['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                    </select>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="short_description">Short Description</label>
                <input type="text" id="short_description" name="short_description" class="form-control" value="<?php echo $editProduct ? sanitize($editProduct['short_description']) : (isset($_POST['short_description']) ? sanitize($_POST['short_description']) : ''); ?>" placeholder="Brief summary for product cards">
            </div>

            <div class="form-group">
                <label for="description">Full Description *</label>
                <textarea id="description" name="description" class="form-control" required><?php echo $editProduct ? sanitize($editProduct['description']) : (isset($_POST['description']) ? sanitize($_POST['description']) : ''); ?></textarea>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <div class="form-group" style="flex:1;min-width:150px">
                    <label for="price">Price *</label>
                    <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" value="<?php echo $editProduct ? $editProduct['price'] : (isset($_POST['price']) ? $_POST['price'] : ''); ?>" required>
                </div>

                <div class="form-group" style="flex:1;min-width:150px">
                    <label for="sale_price">Sale Price (optional)</label>
                    <input type="number" id="sale_price" name="sale_price" class="form-control" step="0.01" min="0" value="<?php echo $editProduct ? ($editProduct['sale_price'] ?? '') : (isset($_POST['sale_price']) ? $_POST['sale_price'] : ''); ?>">
                </div>

                <div class="form-group" style="flex:1;min-width:150px">
                    <label for="demo_url">Demo URL</label>
                    <input type="url" id="demo_url" name="demo_url" class="form-control" value="<?php echo $editProduct ? sanitize($editProduct['demo_url']) : (isset($_POST['demo_url']) ? sanitize($_POST['demo_url']) : ''); ?>" placeholder="https://...">
                </div>

                <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:0.5rem;min-width:120px">
                    <label class="checkbox-inline" style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                        <input type="checkbox" name="is_free" value="1" <?php echo ($editProduct && $editProduct['is_free']) ? 'checked' : ''; ?> onchange="toggleFreePrice(this)">
                        Mark as Free
                    </label>
                </div>
            </div>


            <div class="d-flex flex-wrap gap-2">
                <div class="form-group" style="flex:1;min-width:150px">
                    <label for="download_limit">Download Limit</label>
                    <input type="number" id="download_limit" name="download_limit" class="form-control" value="<?php echo $editProduct ? $editProduct['download_limit'] : (isset($_POST['download_limit']) ? $_POST['download_limit'] : 5); ?>">
                </div>

                <div class="form-group" style="flex:1;min-width:150px">
                    <label for="download_expiry">Download Expiry (hours)</label>
                    <input type="number" id="download_expiry" name="download_expiry" class="form-control" value="<?php echo $editProduct ? $editProduct['download_expiry_hours'] : (isset($_POST['download_expiry']) ? $_POST['download_expiry'] : 48); ?>">
                </div>
            </div>

            <!-- Product File -->
            <div class="form-group">
                <label>Product File *</label>
                <div class="d-flex gap-2 mb-2">
                    <label class="radio-inline"><input type="radio" name="file_source_type" value="upload" checked onchange="toggleFileSource('file')"> Upload File</label>
                    <label class="radio-inline"><input type="radio" name="file_source_type" value="link" onchange="toggleFileSource('file')"> External Link</label>
                </div>
                <div id="fileUploadSection">
                    <input type="file" id="product_file" name="product_file" class="form-control" accept=".zip,.rar,.pdf,.doc,.docx,.exe,.dmg,.mp4,.mp3">
                    <?php if ($editProduct && $editProduct['file_name'] && !$editProduct['file_url']): ?>
                    <p class="text-muted mt-1">Current file: <?php echo sanitize($editProduct['file_name']); ?> (<?php echo $editProduct['file_size']; ?>)</p>
                    <?php endif; ?>
                </div>
                <div id="fileLinkSection" style="display:none">
                    <input type="url" id="file_url" name="file_url" class="form-control" placeholder="https://drive.google.com/..." value="<?php echo $editProduct ? sanitize($editProduct['file_url'] ?? '') : ''; ?>">
                </div>
            </div>

            <!-- Image -->
            <div class="form-group">
                <label>Product Image</label>
                <div class="d-flex gap-2 mb-2">
                    <label class="radio-inline"><input type="radio" name="image_source_type" value="upload" checked onchange="toggleFileSource('image')"> Upload Image</label>
                    <label class="radio-inline"><input type="radio" name="image_source_type" value="link" onchange="toggleFileSource('image')"> External Link</label>
                </div>
                <div id="imageUploadSection">
                    <input type="file" id="product_image" name="product_image" class="form-control" accept="image/*">
                </div>
                <div id="imageLinkSection" style="display:none">
                    <input type="url" id="image_url" name="image_url" class="form-control" placeholder="https://example.com/image.jpg" value="<?php echo $editProduct ? sanitize($editProduct['image_url'] ?? '') : ''; ?>">
                </div>
            </div>

            <!-- Screenshots -->
            <div class="form-group">
                <label>Screenshots</label>
                <?php if ($editProduct): ?>
                <?php $existingScreenshots = json_decode($editProduct['screenshots'] ?? '[]', true); ?>
                <?php foreach ($existingScreenshots as $i => $screenshot): ?>
                <div style="display:inline-block;margin:0.25rem;position:relative">
                    <img src="<?php echo SITE_URL . '/uploads/screenshots/' . sanitize($screenshot); ?>" style="width:100px;height:70px;object-fit:cover;border-radius:var(--border-radius-sm)">
                    <input type="hidden" name="existing_screenshots[]" value="<?php echo sanitize($screenshot); ?>">
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                <input type="file" id="screenshots" name="screenshots[]" class="form-control" multiple accept="image/*">
            </div>

            <button type="submit" class="btn btn-primary btn-lg"><?php echo $action === 'add' ? 'Add' : 'Update'; ?> Product</button>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
.modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    align-items: center;
    justify-content: center;
    z-index: 9999;
}
.modal-box {
    background: var(--bg-card);
    border-radius: 8px;
    padding: 24px;
    width: 420px;
    max-width: 90vw;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}
.modal-box h3 {
    font-size: 1.1rem;
    margin-bottom: 4px;
    color: var(--text-primary);
}
.modal-box .modal-desc {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-bottom: 14px;
}
.modal-box textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 0.9rem;
    resize: vertical;
    min-height: 90px;
    box-sizing: border-box;
    background: var(--bg-input);
    color: var(--text-primary);
}
.modal-box textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 2px var(--primary-glow-sm);
}
.modal-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    margin-top: 16px;
}
.modal-actions button {
    padding: 8px 18px;
    border: none;
    border-radius: 6px;
    font-size: 0.85rem;
    cursor: pointer;
    font-weight: 500;
}
.modal-actions .btn-cancel {
    background: var(--bg-tertiary);
    color: var(--text-primary);
}
.modal-actions .btn-cancel:hover {
    background: var(--border-color);
}
.modal-actions .btn-submit {
    background: #dc3545;
    color: #fff;
}
.modal-actions .btn-submit:hover {
    background: #c82333;
}
</style>

<div class="modal-overlay" id="rejectModal" onclick="if(event.target===this)closeRejectModal()">
    <div class="modal-box">
        <h3>Reject Product</h3>
        <p class="modal-desc">Please provide a reason for rejecting this product. The developer will see this reason.</p>
        <input type="hidden" id="rejectProductId">
        <textarea id="rejectReason" placeholder="Enter rejection reason..."></textarea>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeRejectModal()">Cancel</button>
            <button class="btn-submit" onclick="submitReject()">Submit &amp; Reject</button>
        </div>
    </div>
</div>

<script>
function toggleFileSource(type) {
    if (type === 'file') {
        var isUpload = document.querySelector('input[name="file_source_type"][value="upload"]').checked;
        document.getElementById('fileUploadSection').style.display = isUpload ? 'block' : 'none';
        document.getElementById('fileLinkSection').style.display = isUpload ? 'none' : 'block';
    } else if (type === 'image') {
        var isUpload = document.querySelector('input[name="image_source_type"][value="upload"]').checked;
        document.getElementById('imageUploadSection').style.display = isUpload ? 'block' : 'none';
        document.getElementById('imageLinkSection').style.display = isUpload ? 'none' : 'block';
    }
}
function toggleFreePrice(checkbox) {
    var priceInputs = document.querySelectorAll('#price, #sale_price');
    priceInputs.forEach(function(input) {
        if (input && input.closest) {
            var group = input.closest('.form-group');
            if (group) {
                group.style.opacity = checkbox.checked ? '0.5' : '1';
                input.readOnly = checkbox.checked;
                if (checkbox.checked) input.value = '0';
            }
        }
    });
}
function rejectProduct(id) {
    document.getElementById('rejectModal').style.display = 'flex';
    document.getElementById('rejectProductId').value = id;
    document.getElementById('rejectReason').value = '';
    document.getElementById('rejectReason').focus();
    return false;
}
function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}
function submitReject() {
    var id = document.getElementById('rejectProductId').value;
    var reason = document.getElementById('rejectReason').value.trim();
    if (!reason) {
        showToast('Please enter a reason for rejection.','warning');
        return;
    }
    window.location.href = '?reject=' + id + '&reason=' + encodeURIComponent(reason);
}
<?php if ($editProduct): ?>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($editProduct['file_url']): ?>
    document.querySelector('input[name="file_source_type"][value="link"]').checked = true;
    toggleFileSource('file');
    <?php endif; ?>
    <?php if ($editProduct['image_url']): ?>
    document.querySelector('input[name="image_source_type"][value="link"]').checked = true;
    toggleFileSource('image');
    <?php endif; ?>
    <?php if ($editProduct['is_free']): ?>
    toggleFreePrice(document.querySelector('input[name="is_free"]'));
    <?php endif; ?>
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
