<?php
require_once __DIR__ . '/db.php';

$error = null;
$success = null;

// Menü CRUD işlemleri (ekle/güncelle/sil)
try {
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
		$action = $_POST['action'];
		if ($action === 'menu_add') {
			$name = trim((string)post('name'));
			$price = (float)post('price');
			add_menu_item($name, $price);
			$success = 'Ürün eklendi.';
		} elseif ($action === 'menu_update') {
			$id = (int)post('id');
			$name = trim((string)post('name'));
			$price = (float)post('price');
			update_menu_item($id, $name, $price);
			$success = 'Ürün güncellendi.';
		} elseif ($action === 'menu_delete') {
			$id = (int)post('id');
			delete_menu_item($id);
			$success = 'Ürün silindi.';
		}
	}
} catch (Throwable $e) {
	$error = $e->getMessage();
}

$menuItems = get_menu_items();
?>
<!doctype html>
<html lang="tr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Adisyon Sistemi</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<style>
		:root {
			--primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			--secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
			--success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
			--warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
			--dark-gradient: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
			--glass-bg: rgba(255, 255, 255, 0.25);
			--glass-border: rgba(255, 255, 255, 0.18);
			--shadow-soft: 0 8px 32px rgba(0, 0, 0, 0.1);
			--shadow-hover: 0 15px 35px rgba(0, 0, 0, 0.2);
		}

		* {
			font-family: 'Inter', sans-serif;
		}

		body { 
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			min-height: 100vh;
			position: relative;
			overflow-x: hidden;
		}

		body::before {
			content: '';
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
			pointer-events: none;
			z-index: -1;
		}

		.navbar {
			background: var(--glass-bg) !important;
			backdrop-filter: blur(20px);
			border-bottom: 1px solid var(--glass-border);
			box-shadow: var(--shadow-soft);
		}

		.navbar-brand {
			font-weight: 700;
			font-size: 1.5rem;
			background: var(--primary-gradient);
			-webkit-background-clip: text;
			-webkit-text-fill-color: transparent;
			background-clip: text;
		}

		.card {
			background: var(--glass-bg);
			backdrop-filter: blur(20px);
			border: 1px solid var(--glass-border);
			border-radius: 20px;
			box-shadow: var(--shadow-soft);
			transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
			overflow: hidden;
		}

		.card:hover {
			transform: translateY(-5px);
			box-shadow: var(--shadow-hover);
		}

		.card-header {
			background: var(--primary-gradient);
			color: white;
			border: none;
			padding: 1.5rem;
			font-weight: 600;
			font-size: 1.1rem;
		}

		.menu-card {
			background: rgba(255, 255, 255, 0.9);
			border: none;
			border-radius: 16px;
			transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
			overflow: hidden;
			position: relative;
		}

		.menu-card::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			height: 4px;
			background: var(--success-gradient);
			transform: scaleX(0);
			transition: transform 0.3s ease;
		}

		.menu-card:hover::before {
			transform: scaleX(1);
		}

		.menu-card:hover {
			transform: translateY(-8px) scale(1.02);
			box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
		}

		.menu-card .card-body {
			padding: 1.5rem;
		}

		.product-name {
			font-weight: 600;
			font-size: 1.1rem;
			color: #2c3e50;
			margin-bottom: 0.5rem;
		}

		.product-price {
			font-weight: 700;
			font-size: 1.2rem;
			background: var(--success-gradient);
			-webkit-background-clip: text;
			-webkit-text-fill-color: transparent;
			background-clip: text;
		}

		.btn {
			border-radius: 12px;
			font-weight: 500;
			transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
			position: relative;
			overflow: hidden;
		}

		.btn::before {
			content: '';
			position: absolute;
			top: 0;
			left: -100%;
			width: 100%;
			height: 100%;
			background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
			transition: left 0.5s;
		}

		.btn:hover::before {
			left: 100%;
		}

		.btn-success {
			background: var(--success-gradient);
			border: none;
			box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);
		}

		.btn-success:hover {
			transform: translateY(-2px);
			box-shadow: 0 8px 25px rgba(79, 172, 254, 0.6);
		}

		.btn-primary {
			background: var(--primary-gradient);
			border: none;
			box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
		}

		.btn-primary:hover {
			transform: translateY(-2px);
			box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
		}

		.btn-warning {
			background: var(--warning-gradient);
			border: none;
			box-shadow: 0 4px 15px rgba(67, 233, 123, 0.4);
		}

		.btn-warning:hover {
			transform: translateY(-2px);
			box-shadow: 0 8px 25px rgba(67, 233, 123, 0.6);
		}

		.btn-outline-secondary {
			border: 2px solid #e9ecef;
			color: #6c757d;
			background: transparent;
		}

		.btn-outline-secondary:hover {
			background: #e9ecef;
			border-color: #e9ecef;
			color: #495057;
			transform: translateY(-2px);
		}

		.sidebar, .order-summary { 
			max-height: 80vh; 
			overflow-y: auto;
			scrollbar-width: thin;
			scrollbar-color: rgba(102, 126, 234, 0.3) transparent;
		}

		.sidebar::-webkit-scrollbar, .order-summary::-webkit-scrollbar {
			width: 6px;
		}

		.sidebar::-webkit-scrollbar-track, .order-summary::-webkit-scrollbar-track {
			background: transparent;
		}

		.sidebar::-webkit-scrollbar-thumb, .order-summary::-webkit-scrollbar-thumb {
			background: rgba(102, 126, 234, 0.3);
			border-radius: 3px;
		}

		.order-item {
			background: rgba(255, 255, 255, 0.8);
			border-radius: 12px;
			padding: 1rem;
			margin-bottom: 0.75rem;
			transition: all 0.3s ease;
			border-left: 4px solid transparent;
		}

		.order-item:hover {
			background: rgba(255, 255, 255, 0.95);
			border-left-color: #667eea;
			transform: translateX(5px);
		}

		.form-control {
			border-radius: 12px;
			border: 2px solid #e9ecef;
			padding: 0.75rem 1rem;
			transition: all 0.3s ease;
			background: rgba(255, 255, 255, 0.9);
		}

		.form-control:focus {
			border-color: #667eea;
			box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
			background: white;
		}

		.alert {
			border-radius: 12px;
			border: none;
			backdrop-filter: blur(10px);
		}

		.alert-success {
			background: rgba(67, 233, 123, 0.1);
			color: #155724;
			border-left: 4px solid #43e97b;
		}

		.alert-danger {
			background: rgba(245, 87, 108, 0.1);
			color: #721c24;
			border-left: 4px solid #f5576c;
		}

		.modal-content {
			border-radius: 20px;
			border: none;
			box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
			backdrop-filter: blur(20px);
		}

		.modal-header {
			background: var(--primary-gradient);
			color: white;
			border-radius: 20px 20px 0 0;
			border: none;
		}

		.btn-close {
			filter: invert(1);
		}

		.total-display {
			background: var(--primary-gradient);
			color: white;
			padding: 1.5rem;
			border-radius: 16px;
			text-align: center;
			font-size: 1.5rem;
			font-weight: 700;
			box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
		}

		.input-group .form-control {
			border-top-right-radius: 0;
			border-bottom-right-radius: 0;
		}

		.input-group .btn {
			border-top-left-radius: 0;
			border-bottom-left-radius: 0;
		}

		.fade-in {
			animation: fadeIn 0.5s ease-in;
		}

		@keyframes fadeIn {
			from { opacity: 0; transform: translateY(20px); }
			to { opacity: 1; transform: translateY(0); }
		}

		.slide-in {
			animation: slideIn 0.3s ease-out;
		}

		@keyframes slideIn {
			from { transform: translateX(-100%); opacity: 0; }
			to { transform: translateX(0); opacity: 1; }
		}

		.pulse {
			animation: pulse 2s infinite;
		}

		@keyframes pulse {
			0% { transform: scale(1); }
			50% { transform: scale(1.05); }
			100% { transform: scale(1); }
		}

		@media (max-width: 768px) {
			.card {
				margin-bottom: 1rem;
			}
			
			.menu-card .card-body {
				padding: 1rem;
			}
			
			.total-display {
				font-size: 1.2rem;
				padding: 1rem;
			}
		}
	</style>
</head>
<body>
	<nav class="navbar navbar-expand-lg navbar-dark">
		<div class="container-fluid">
			<a class="navbar-brand" href="#">
				<i class="fas fa-utensils me-2"></i>
				Akmanlar Pos Sistemleri
			</a>
			<div class="d-flex">
				<a class="btn btn-outline-light me-2" href="index.php">
					<i class="fas fa-plus me-1"></i>
					Yeni Sipariş
				</a>
				<a class="btn btn-warning me-2" href="report.php">
					<i class="fas fa-chart-line me-1"></i>
					Basit Rapor
				</a>
				<a class="btn btn-info me-2" href="advanced_reports.php">
					<i class="fas fa-chart-bar me-1"></i>
					Gelişmiş Rapor
				</a>
				<a class="btn btn-secondary me-2" href="backup_system.php">
					<i class="fas fa-database me-1"></i>
					Yedekleme
				</a>
				<a class="btn btn-success" href="printer_management.php">
					<i class="fas fa-print me-1"></i>
					Termal Yazıcı
				</a>
			</div>
		</div>
	</nav>

	<div class="container-fluid mt-3">
		<?php if ($error): ?>
			<div class="alert alert-danger"><?php echo e($error); ?></div>
		<?php elseif ($success): ?>
			<div class="alert alert-success"><?php echo e($success); ?></div>
		<?php endif; ?>
		<div class="row g-4">
			<div class="col-lg-7">
				<div class="card fade-in">
					<div class="card-header d-flex justify-content-between align-items-center">
						<div>
							<i class="fas fa-book-open me-2"></i>
							<strong>Menü</strong>
						</div>
						<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd">
							<i class="fas fa-plus me-1"></i>
							Yeni Ürün
						</button>
					</div>
					<div class="card-body sidebar">
						<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
							<?php foreach ($menuItems as $item): ?>
								<div class="col">
									<div class="card menu-card h-100">
										<div class="card-body d-flex flex-column">
											<div class="d-flex justify-content-between align-items-start mb-3">
												<div class="flex-grow-1">
													<div class="product-name"><?php echo e($item['name']); ?></div>
													<div class="product-price"><?php echo number_format((float)$item['price'], 2, ',', '.'); ?> ₺</div>
												</div>
												<div>
													<button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEdit" data-id="<?php echo (int)$item['id']; ?>" data-name="<?php echo e($item['name']); ?>" data-price="<?php echo e((string)$item['price']); ?>">
														<i class="fas fa-edit"></i>
													</button>
												</div>
											</div>
											<div class="mt-auto">
												<div class="input-group">
													<input type="number" class="form-control" min="1" step="1" value="1" id="qty_<?php echo (int)$item['id']; ?>" placeholder="Adet">
													<button type="button" class="btn btn-success" onclick="addToCart(<?php echo (int)$item['id']; ?>, '<?php echo e($item['name']); ?>', <?php echo (float)$item['price']; ?>)">
														<i class="fas fa-plus me-1"></i>
														Ekle
													</button>
												</div>
											</div>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>

			<div class="col-lg-5">
				<div class="card fade-in">
					<div class="card-header">
						<i class="fas fa-shopping-cart me-2"></i>
						<strong>Yeni Sipariş</strong>
					</div>
					<div class="card-body order-summary">
						<form id="orderForm" method="post" action="orders.php">
							<input type="hidden" name="action" value="save_order">
							<div id="orderItemsContainer">
								<div class="text-center text-muted py-4">
									<i class="fas fa-shopping-cart fa-3x mb-3"></i>
									<p>Sepetiniz boş. Menüden ürün ekleyin.</p>
								</div>
							</div>
							
							<div class="row g-3 mb-3">
								<div class="col-6">
									<label for="taxRate" class="form-label small">
										<i class="fas fa-percentage me-1"></i>
										KDV (%)
									</label>
									<input type="number" class="form-control form-control-sm" id="taxRate" name="tax_rate" value="8" min="0" step="0.1">
								</div>
								<div class="col-6">
									<label for="serviceRate" class="form-label small">
										<i class="fas fa-concierge-bell me-1"></i>
										Servis (%)
									</label>
									<input type="number" class="form-control form-control-sm" id="serviceRate" name="service_rate" value="0" min="0" step="0.1">
								</div>
								<div class="col-12">
									<label for="discount" class="form-label small">
										<i class="fas fa-tag me-1"></i>
										İndirim (₺)
									</label>
									<input type="number" class="form-control form-control-sm" id="discount" name="discount" value="0" min="0" step="0.01">
								</div>
							</div>
							
							<div class="mb-3">
								<label for="note" class="form-label small">
									<i class="fas fa-sticky-note me-1"></i>
									Not
								</label>
								<textarea class="form-control" id="note" name="note" rows="2" placeholder="Müşteri notu (isteğe bağlı)"></textarea>
							</div>
							
							<div class="order-item mb-3">
								<div class="d-flex justify-content-between">
									<span class="fw-semibold">Ara Toplam</span>
									<span class="fw-semibold" id="subtotalText">0,00 ₺</span>
								</div>
							</div>
							
							<div class="total-display mb-3">
								<div class="d-flex justify-content-between align-items-center">
									<span>
										<i class="fas fa-calculator me-2"></i>
										TOPLAM
									</span>
									<span id="totalText">0,00 ₺</span>
								</div>
							</div>
							
							<div class="d-grid gap-2">
								<button type="submit" class="btn btn-primary btn-lg">
									<i class="fas fa-save me-2"></i>
									Kaydet
								</button>
								<button type="button" class="btn btn-outline-secondary" onclick="window.location.reload()">
									<i class="fas fa-refresh me-2"></i>
									Temizle
								</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Add Modal -->
	<div class="modal fade" id="modalAdd" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">
						<i class="fas fa-plus-circle me-2"></i>
						Yeni Ürün
					</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
				</div>
				<form method="post">
					<input type="hidden" name="action" value="menu_add">
					<div class="modal-body">
						<div class="mb-3">
							<label class="form-label">
								<i class="fas fa-tag me-1"></i>
								Ürün Adı
							</label>
							<input type="text" class="form-control" name="name" required placeholder="Örn: Çay, Kahve, Tost">
						</div>
						<div class="mb-3">
							<label class="form-label">
								<i class="fas fa-lira-sign me-1"></i>
								Fiyat (₺)
							</label>
							<input type="number" class="form-control" name="price" min="0" step="0.01" required placeholder="0.00">
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
							<i class="fas fa-times me-1"></i>
							Vazgeç
						</button>
						<button type="submit" class="btn btn-primary">
							<i class="fas fa-save me-1"></i>
							Kaydet
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- Edit Modal -->
	<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">
						<i class="fas fa-edit me-2"></i>
						Ürün Düzenle
					</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
				</div>
				<form method="post">
					<input type="hidden" name="action" value="menu_update">
					<input type="hidden" name="id" id="editId">
					<div class="modal-body">
						<div class="mb-3">
							<label class="form-label">
								<i class="fas fa-tag me-1"></i>
								Ürün Adı
							</label>
							<input type="text" class="form-control" name="name" id="editName" required>
						</div>
						<div class="mb-3">
							<label class="form-label">
								<i class="fas fa-lira-sign me-1"></i>
								Fiyat (₺)
							</label>
							<input type="number" class="form-control" name="price" id="editPrice" min="0" step="0.01" required>
						</div>
					</div>
					<div class="modal-footer justify-content-between">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
							<i class="fas fa-times me-1"></i>
							Vazgeç
						</button>
						<div>
							<button type="submit" class="btn btn-primary me-2">
								<i class="fas fa-save me-1"></i>
								Güncelle
							</button>
						</div>
					</div>
				</form>
				<form method="post">
					<input type="hidden" name="action" value="menu_delete">
					<input type="hidden" name="id" id="deleteId">
					<div class="modal-footer">
						<button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Bu ürünü silmek istediğinize emin misiniz?');">
							<i class="fas fa-trash me-1"></i>
							Ürünü Sil
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script>
	// Düzenle modalına veri doldur
	const editModal = document.getElementById('modalEdit');
	editModal?.addEventListener('show.bs.modal', event => {
		const button = event.relatedTarget;
		const id = button.getAttribute('data-id');
		const name = button.getAttribute('data-name');
		const price = button.getAttribute('data-price');
		document.getElementById('editId').value = id;
		document.getElementById('deleteId').value = id;
		document.getElementById('editName').value = name;
		document.getElementById('editPrice').value = price;
	});

	// Sağ panel: sipariş satırı ekleme (client-side)
	const orderItemsContainer = document.getElementById('orderItemsContainer');
	let orderItems = [];

	function formatCurrency(value) {
		return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(value);
	}

	function renderOrderItems() {
		orderItemsContainer.innerHTML = '';
		let subtotal = 0;
		
		if (orderItems.length === 0) {
			orderItemsContainer.innerHTML = `
				<div class="text-center text-muted py-4">
					<i class="fas fa-shopping-cart fa-3x mb-3"></i>
					<p>Sepetiniz boş. Menüden ürün ekleyin.</p>
				</div>
			`;
		} else {
			orderItems.forEach((it, idx) => {
				subtotal += it.price * it.quantity;
				const row = document.createElement('div');
				row.className = 'order-item slide-in';
				row.innerHTML = `
					<input type="hidden" name="items[${idx}][menu_id]" value="${it.id}">
					<input type="hidden" name="items[${idx}][quantity]" value="${it.quantity}">
					<div class="d-flex align-items-center gap-3">
						<div class="flex-grow-1">
							<div class="fw-semibold text-dark">${it.name}</div>
							<div class="text-muted small">
								<i class="fas fa-times me-1"></i>
								${it.quantity} x ${formatCurrency(it.price)}
							</div>
						</div>
						<div class="fw-semibold text-success">${formatCurrency(it.price * it.quantity)}</div>
						<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(${idx})" title="Sil">
							<i class="fas fa-trash"></i>
						</button>
					</div>
				`;
				orderItemsContainer.appendChild(row);
			});
		}
		
		document.getElementById('subtotalText').textContent = formatCurrency(subtotal);

		const taxRate = parseFloat(document.getElementById('taxRate').value || '0') / 100;
		const serviceRate = parseFloat(document.getElementById('serviceRate').value || '0') / 100;
		const discount = parseFloat(document.getElementById('discount').value || '0');
		let total = subtotal + (subtotal * taxRate) + (subtotal * serviceRate) - discount;
		document.getElementById('totalText').textContent = formatCurrency(Math.max(total, 0));
	}

	function removeItem(idx) {
		orderItems.splice(idx, 1);
		renderOrderItems();
	}

	// Sepete ürün ekleme fonksiyonu
	function addToCart(id, name, price) {
		const qtyInput = document.getElementById('qty_' + id);
		const quantity = parseInt(qtyInput.value) || 1;
		
		// Buton animasyonu
		const button = qtyInput.nextElementSibling;
		button.classList.add('pulse');
		setTimeout(() => button.classList.remove('pulse'), 2000);
		
		// Aynı ürün varsa miktarını artır
		const existingItem = orderItems.find(item => item.id === id);
		if (existingItem) {
			existingItem.quantity += quantity;
		} else {
			orderItems.push({
				id: id,
				name: name,
				price: price,
				quantity: quantity
			});
		}
		
		renderOrderItems();
		qtyInput.value = 1; // Miktarı sıfırla
		
		// Başarı mesajı
		showNotification(`${name} sepete eklendi!`, 'success');
	}

	// Bildirim gösterme fonksiyonu
	function showNotification(message, type = 'info') {
		const notification = document.createElement('div');
		notification.className = `alert alert-${type} position-fixed`;
		notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; animation: slideIn 0.3s ease-out;';
		notification.innerHTML = `
			<i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
			${message}
		`;
		
		document.body.appendChild(notification);
		
		setTimeout(() => {
			notification.style.animation = 'fadeOut 0.3s ease-in';
			setTimeout(() => notification.remove(), 300);
		}, 3000);
	}

	// Fade out animasyonu
	const style = document.createElement('style');
	style.textContent = `
		@keyframes fadeOut {
			from { opacity: 1; transform: translateX(0); }
			to { opacity: 0; transform: translateX(100%); }
		}
	`;
	document.head.appendChild(style);
	</script>
</body>
</html>

