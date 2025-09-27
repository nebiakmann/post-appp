<?php
require_once __DIR__ . '/db.php';

$action = $_POST['action'] ?? $_GET['action'] ?? null;

function redirect(string $url): never {
	header('Location: ' . $url);
	exit;
}

try {
	if ($action === 'add_single') {
		$menuId = (int)($_POST['menu_id'] ?? 0);
		$qty = max(1, (int)($_POST['quantity'] ?? 1));
		// Basit senaryo: kullanıcıyı ana sayfaya geri yönlendir (sepet mantığını client tarafında tutuyoruz)
		// İsteğe göre session sepeti eklenebilir. Şimdilik bilgi mesajı ile dön.
		redirect('index.php');
	}

	if ($action === 'save_order') {
		$items = $_POST['items'] ?? [];
		$taxRate = (float)($_POST['tax_rate'] ?? 8);
		$serviceRate = (float)($_POST['service_rate'] ?? 0);
		$discount = (float)($_POST['discount'] ?? 0);
		$note = trim((string)($_POST['note'] ?? ''));

		$normalized = [];
		foreach ($items as $it) {
			$normalized[] = [
				'menu_id' => (int)($it['menu_id'] ?? 0),
				'quantity' => (int)($it['quantity'] ?? 0),
			];
		}
		if (empty($normalized)) {
			throw new InvalidArgumentException('Herhangi bir ürün eklenmedi.');
		}

		$orderId = create_order($normalized, $taxRate/100.0, $serviceRate/100.0, $discount, $note);
		$detail = get_order_details($orderId);
		$order = $detail['order'];
		$items = $detail['items'];
	} else {
		redirect('index.php');
	}
} catch (Throwable $e) {
	$error = $e->getMessage();
}
?>
<!doctype html>
<html lang="tr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Fiş / Adisyon</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<style>
		:root {
			--primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			--success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
			--glass-bg: rgba(255, 255, 255, 0.25);
			--glass-border: rgba(255, 255, 255, 0.18);
			--shadow-soft: 0 8px 32px rgba(0, 0, 0, 0.1);
		}

		* {
			font-family: 'Inter', sans-serif;
		}

		body { 
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			min-height: 100vh;
		}

		.receipt { 
			max-width: 400px; 
			margin: 20px auto; 
			background: white; 
			padding: 30px; 
			border-radius: 20px;
			box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
			position: relative;
			overflow: hidden;
		}

		.receipt::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			height: 4px;
			background: var(--primary-gradient);
		}

		.receipt h5 { 
			text-align: center; 
			margin-bottom: 20px; 
			color: #2c3e50;
			font-weight: 700;
			font-size: 1.5rem;
		}

		.receipt .date {
			text-align: center;
			color: #6c757d;
			font-size: 0.9rem;
			margin-bottom: 20px;
		}

		.receipt table {
			margin-bottom: 20px;
		}

		.receipt table th {
			background: #f8f9fa;
			border: none;
			font-weight: 600;
			color: #495057;
			padding: 12px 8px;
		}

		.receipt table td {
			border: none;
			padding: 10px 8px;
			border-bottom: 1px solid #e9ecef;
		}

		.receipt .total-section {
			background: var(--primary-gradient);
			color: white;
			padding: 20px;
			border-radius: 12px;
			margin-top: 20px;
			text-align: center;
		}

		.receipt .total-section h4 {
			margin: 0;
			font-size: 1.8rem;
			font-weight: 700;
		}

		.receipt .note {
			background: #f8f9fa;
			padding: 15px;
			border-radius: 8px;
			margin-top: 15px;
			font-style: italic;
			color: #6c757d;
		}

		.btn {
			border-radius: 12px;
			font-weight: 500;
			transition: all 0.3s ease;
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
			background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
			border: none;
			box-shadow: 0 4px 15px rgba(67, 233, 123, 0.4);
		}

		.btn-warning:hover {
			transform: translateY(-2px);
			box-shadow: 0 8px 25px rgba(67, 233, 123, 0.6);
		}

		.alert {
			border-radius: 12px;
			border: none;
			backdrop-filter: blur(10px);
		}

		.alert-danger {
			background: rgba(245, 87, 108, 0.1);
			color: #721c24;
			border-left: 4px solid #f5576c;
		}

		@media print {
			.no-print { display: none !important; }
			.receipt { 
				border: none; 
				box-shadow: none;
				margin: 0;
				max-width: none;
			}
			body {
				background: white;
			}
		}

		.fade-in {
			animation: fadeIn 0.5s ease-in;
		}

		@keyframes fadeIn {
			from { opacity: 0; transform: translateY(20px); }
			to { opacity: 1; transform: translateY(0); }
		}
	</style>
</head>
<body class="bg-light">
	<div class="container">
		<?php if (!empty($error)): ?>
			<div class="alert alert-danger mt-3 fade-in">
				<i class="fas fa-exclamation-triangle me-2"></i>
				<?php echo e($error); ?>
			</div>
			<div class="text-center mt-3 no-print">
				<a class="btn btn-secondary" href="index.php">
					<i class="fas fa-arrow-left me-2"></i>
					Geri Dön
				</a>
			</div>
		<?php else: ?>
			<!-- Termal Yazıcı Butonu -->
			<div class="text-center mb-3">
				<button class="btn btn-success btn-lg" onclick="printThermalReceipt(<?php echo $order['id']; ?>)">
					<i class="fas fa-print me-2"></i>
					Termal Yazıcıdan Yazdır
				</button>
			</div>
			
			<div class="receipt fade-in">
				<h5>
					<i class="fas fa-utensils me-2"></i>
					Restoran POS
				</h5>
				<div class="date">
					<i class="fas fa-calendar-alt me-1"></i>
					<?php echo e($order['date']); ?>
				</div>
				
				<table class="table table-sm">
					<thead>
						<tr>
							<th><i class="fas fa-tag me-1"></i>Ürün</th>
							<th class="text-end"><i class="fas fa-hashtag me-1"></i>Adet</th>
							<th class="text-end"><i class="fas fa-lira-sign me-1"></i>Tutar</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($items as $it): ?>
							<tr>
								<td class="fw-semibold"><?php echo e($it['name']); ?></td>
								<td class="text-end"><?php echo (int)$it['quantity']; ?></td>
								<td class="text-end fw-semibold text-success"><?php echo number_format((float)$it['total'], 2, ',', '.'); ?> ₺</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				
				<div class="row g-2 mb-3">
					<div class="col-6">
						<div class="d-flex justify-content-between">
							<span>Ara Toplam</span>
							<span class="fw-semibold"><?php echo number_format((float)$order['subtotal'], 2, ',', '.'); ?> ₺</span>
						</div>
					</div>
					<div class="col-6">
						<div class="d-flex justify-content-between">
							<span>KDV</span>
							<span class="fw-semibold"><?php echo number_format((float)$order['tax'], 2, ',', '.'); ?> ₺</span>
						</div>
					</div>
					<div class="col-6">
						<div class="d-flex justify-content-between">
							<span>Servis</span>
							<span class="fw-semibold"><?php echo number_format((float)$order['service_fee'], 2, ',', '.'); ?> ₺</span>
						</div>
					</div>
					<div class="col-6">
						<div class="d-flex justify-content-between">
							<span>İndirim</span>
							<span class="fw-semibold text-danger">-<?php echo number_format((float)$order['discount'], 2, ',', '.'); ?> ₺</span>
						</div>
					</div>
				</div>
				
				<div class="total-section">
					<h4>
						<i class="fas fa-calculator me-2"></i>
						TOPLAM: <?php echo number_format((float)$order['total'], 2, ',', '.'); ?> ₺
					</h4>
				</div>
				
				<?php if (!empty($order['note'])): ?>
					<div class="note">
						<i class="fas fa-sticky-note me-2"></i>
						<strong>Not:</strong> <?php echo e($order['note']); ?>
					</div>
				<?php endif; ?>
			</div>
			
			<div class="text-center mt-4 no-print">
				<div class="d-grid gap-2 d-md-block">
					<button class="btn btn-primary btn-lg me-2" onclick="window.print()">
						<i class="fas fa-print me-2"></i>
						Yazdır
					</button>
					<a class="btn btn-secondary btn-lg me-2" href="index.php">
						<i class="fas fa-plus me-2"></i>
						Yeni Sipariş
					</a>
					<a class="btn btn-warning btn-lg" href="report.php">
						<i class="fas fa-chart-line me-2"></i>
						Geçmiş & Rapor
					</a>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<script>
		function printThermalReceipt(orderId) {
			if (confirm('Termal yazıcıdan fiş yazdırmak istediğinize emin misiniz?')) {
				// Loading göster
				const button = event.target;
				const originalText = button.innerHTML;
				button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Yazdırılıyor...';
				button.disabled = true;
				
				// AJAX ile yazdırma isteği gönder
				fetch('thermal_print.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: 'action=print_receipt&order_id=' + orderId
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						alert('Fiş başarıyla yazdırıldı!');
					} else {
						alert('Yazdırma hatası: ' + data.error);
					}
				})
				.catch(error => {
					alert('Hata: ' + error);
				})
				.finally(() => {
					button.innerHTML = originalText;
					button.disabled = false;
				});
			}
		}
	</script>
</body>
</html>

