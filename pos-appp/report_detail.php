<?php
require_once __DIR__ . '/db.php';

header('Content-Type: text/html; charset=utf-8');

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
	echo '<div class="text-danger">Geçersiz sipariş.</div>';
	exit;
}

$detail = get_order_details($orderId);
if (empty($detail)) {
	echo '<div class="text-danger">Sipariş bulunamadı.</div>';
	exit;
}

$order = $detail['order'];
$items = $detail['items'];
?>
<div class="mb-2">
	<div class="d-flex justify-content-between">
		<span><strong>#<?php echo (int)$order['id']; ?></strong></span>
		<span class="text-muted">Tarih: <?php echo e($order['date']); ?></span>
	</div>
	<?php if (!empty($order['note'])): ?>
		<div class="small text-muted">Not: <?php echo e($order['note']); ?></div>
	<?php endif; ?>
</div>
<div class="table-responsive">
	<table class="table table-sm table-striped">
		<thead>
			<tr>
				<th>Ürün</th>
				<th class="text-end">Adet</th>
				<th class="text-end">Birim</th>
				<th class="text-end">Tutar</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($items as $it): ?>
				<tr>
					<td><?php echo e($it['name']); ?></td>
					<td class="text-end"><?php echo (int)$it['quantity']; ?></td>
					<td class="text-end"><?php echo number_format((float)$it['price'], 2, ',', '.'); ?> ₺</td>
					<td class="text-end"><?php echo number_format((float)$it['total'], 2, ',', '.'); ?> ₺</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
<div class="d-flex justify-content-between">
	<span>Ara Toplam</span>
	<span><?php echo number_format((float)$order['subtotal'], 2, ',', '.'); ?> ₺</span>
</div>
<div class="d-flex justify-content-between">
	<span>KDV</span>
	<span><?php echo number_format((float)$order['tax'], 2, ',', '.'); ?> ₺</span>
</div>
<div class="d-flex justify-content-between">
	<span>Servis</span>
	<span><?php echo number_format((float)$order['service_fee'], 2, ',', '.'); ?> ₺</span>
</div>
<div class="d-flex justify-content-between">
	<span>İndirim</span>
	<span>-<?php echo number_format((float)$order['discount'], 2, ',', '.'); ?> ₺</span>
</div>
<hr>
<div class="d-flex justify-content-between fs-5">
	<strong>TOPLAM</strong>
	<strong><?php echo number_format((float)$order['total'], 2, ',', '.'); ?> ₺</strong>
</div>

