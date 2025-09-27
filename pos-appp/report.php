<?php
require_once __DIR__ . '/db.php';

$start = get('start');
$end = get('end');
$period = get('period') ?: 'daily'; // daily | monthly

$orders = get_orders($start, $end);
$summary = get_revenue_summary($period === 'monthly' ? 'monthly' : 'daily');

?>
<!doctype html>
<html lang="tr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Geçmiş Siparişler & Rapor</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
	<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
		<div class="container-fluid">
			<a class="navbar-brand" href="#">Restoran POS</a>
			<div class="d-flex">
				<a class="btn btn-outline-light me-2" href="index.php">Yeni Sipariş</a>
				<a class="btn btn-warning" href="report.php">Geçmiş & Rapor</a>
			</div>
		</div>
	</nav>

	<div class="container-fluid mt-3">
		<div class="row g-3">
			<div class="col-xl-4">
				<div class="card">
					<div class="card-header"><strong>Rapor Özeti</strong></div>
					<div class="card-body">
						<form class="row g-2 align-items-end" method="get">
							<div class="col-6">
								<label class="form-label">Başlangıç</label>
								<input type="date" class="form-control" name="start" value="<?php echo e((string)$start); ?>">
							</div>
							<div class="col-6">
								<label class="form-label">Bitiş</label>
								<input type="date" class="form-control" name="end" value="<?php echo e((string)$end); ?>">
							</div>
							<div class="col-12">
								<label class="form-label">Periyot</label>
								<select class="form-select" name="period">
									<option value="daily" <?php echo $period==='daily'?'selected':''; ?>>Günlük</option>
									<option value="monthly" <?php echo $period==='monthly'?'selected':''; ?>>Aylık</option>
								</select>
							</div>
							<div class="col-12">
								<button class="btn btn-primary w-100" type="submit">Uygula</button>
							</div>
						</form>
						<hr>
						<div class="table-responsive">
							<table class="table table-sm table-striped">
								<thead>
									<tr>
										<th>Dönem</th>
										<th class="text-end">Ciro</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($summary as $row): ?>
										<tr>
											<td><?php echo e($row['period']); ?></td>
											<td class="text-end"><?php echo number_format((float)$row['revenue'], 2, ',', '.'); ?> ₺</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>

			<div class="col-xl-8">
				<div class="card">
					<div class="card-header d-flex justify-content-between align-items-center">
						<strong>Geçmiş Siparişler</strong>
						<a class="btn btn-sm btn-outline-secondary" href="report.php">Tümü</a>
					</div>
					<div class="card-body">
						<div class="table-responsive">
							<table class="table table-striped table-hover">
								<thead>
									<tr>
										<th>#</th>
										<th>Tarih</th>
										<th class="text-end">Ara Toplam</th>
										<th class="text-end">KDV</th>
										<th class="text-end">Servis</th>
										<th class="text-end">İndirim</th>
										<th class="text-end">Toplam</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($orders as $o): ?>
										<tr>
											<td><?php echo (int)$o['id']; ?></td>
											<td><?php echo e($o['date']); ?></td>
											<td class="text-end"><?php echo number_format((float)$o['subtotal'], 2, ',', '.'); ?> ₺</td>
											<td class="text-end"><?php echo number_format((float)$o['tax'], 2, ',', '.'); ?> ₺</td>
											<td class="text-end"><?php echo number_format((float)$o['service_fee'], 2, ',', '.'); ?> ₺</td>
											<td class="text-end"><?php echo number_format((float)$o['discount'], 2, ',', '.'); ?> ₺</td>
											<td class="text-end"><?php echo number_format((float)$o['total'], 2, ',', '.'); ?> ₺</td>
											<td class="text-end">
												<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detailModal" data-id="<?php echo (int)$o['id']; ?>">Detay</button>
												<a class="btn btn-sm btn-outline-secondary" href="orders.php?action=print&id=<?php echo (int)$o['id']; ?>" target="_blank">Yazdır</a>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Detay Modal -->
	<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Sipariş Detayı</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
				</div>
				<div class="modal-body">
					<div id="detailBody">Yükleniyor...</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script>
	const detailModal = document.getElementById('detailModal');
	detailModal?.addEventListener('show.bs.modal', async (event) => {
		const button = event.relatedTarget;
		const id = button.getAttribute('data-id');
		const target = document.getElementById('detailBody');
		target.innerHTML = 'Yükleniyor...';
		try {
			const res = await fetch('report_detail.php?id=' + id);
			target.innerHTML = await res.text();
		} catch (e) {
			target.innerHTML = 'Detay yüklenemedi.';
		}
	});
	</script>
</body>
</html>

