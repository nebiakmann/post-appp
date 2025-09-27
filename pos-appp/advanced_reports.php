<?php
require_once __DIR__ . '/db.php';

$start = get('start', date('Y-m-01')); // Bu ayın başı
$end = get('end', date('Y-m-d')); // Bugün
$report_type = get('type', 'summary'); // summary, products, trends, export

// Rapor verilerini al
$orders = get_orders($start, $end);
$summary = get_revenue_summary('daily');
$product_stats = get_product_statistics($start, $end);
$monthly_comparison = get_monthly_comparison();
$top_products = get_top_products($start, $end);

// Excel export
if ($report_type === 'export' && get('format') === 'excel') {
    export_to_excel($orders, $start, $end);
    exit;
}

// PDF export
if ($report_type === 'export' && get('format') === 'pdf') {
    export_to_pdf($orders, $start, $end);
    exit;
}

// Ürün istatistikleri
function get_product_statistics($start, $end) {
    $sql = "
        SELECT 
            m.name,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.total) as total_revenue,
            AVG(oi.price) as avg_price,
            COUNT(DISTINCT oi.order_id) as order_count
        FROM order_items oi
        JOIN menu m ON m.id = oi.menu_id
        JOIN orders o ON o.id = oi.order_id
        WHERE o.date >= ? AND o.date <= ?
        GROUP BY m.id, m.name
        ORDER BY total_revenue DESC
    ";
    
    $stmt = db()->prepare($sql);
    $stmt->execute([$start . ' 00:00:00', $end . ' 23:59:59']);
    return $stmt->fetchAll();
}

// Aylık karşılaştırma
function get_monthly_comparison() {
    $sql = "
        SELECT 
            DATE_FORMAT(date, '%Y-%m') as month,
            COUNT(*) as order_count,
            SUM(total) as total_revenue,
            AVG(total) as avg_order_value
        FROM orders 
        WHERE date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(date, '%Y-%m')
        ORDER BY month DESC
    ";
    
    $stmt = db()->query($sql);
    return $stmt->fetchAll();
}

// En çok satan ürünler
function get_top_products($start, $end, $limit = 10) {
    $sql = "
        SELECT 
            m.name,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.total) as total_revenue
        FROM order_items oi
        JOIN menu m ON m.id = oi.menu_id
        JOIN orders o ON o.id = oi.order_id
        WHERE o.date >= ? AND o.date <= ?
        GROUP BY m.id, m.name
        ORDER BY total_quantity DESC
        LIMIT ?
    ";
    
    $stmt = db()->prepare($sql);
    $stmt->execute([$start . ' 00:00:00', $end . ' 23:59:59', $limit]);
    return $stmt->fetchAll();
}

// Excel export
function export_to_excel($orders, $start, $end) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="pos_rapor_' . $start . '_' . $end . '.xls"');
    
    echo "Sipariş No\tTarih\tAra Toplam\tKDV\tServis\tİndirim\tToplam\tNot\n";
    
    foreach ($orders as $order) {
        echo $order['id'] . "\t";
        echo $order['date'] . "\t";
        echo number_format($order['subtotal'], 2, ',', '.') . "\t";
        echo number_format($order['tax'], 2, ',', '.') . "\t";
        echo number_format($order['service_fee'], 2, ',', '.') . "\t";
        echo number_format($order['discount'], 2, ',', '.') . "\t";
        echo number_format($order['total'], 2, ',', '.') . "\t";
        echo $order['note'] . "\n";
    }
}

// PDF export (basit HTML to PDF)
function export_to_pdf($orders, $start, $end) {
    // Bu özellik için daha gelişmiş PDF kütüphanesi gerekir
    // Şimdilik HTML olarak döndürüyoruz
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>POS Raporu - $start / $end</h1>";
    echo "<table border='1'>";
    echo "<tr><th>Sipariş No</th><th>Tarih</th><th>Toplam</th></tr>";
    foreach ($orders as $order) {
        echo "<tr>";
        echo "<td>" . $order['id'] . "</td>";
        echo "<td>" . $order['date'] . "</td>";
        echo "<td>" . number_format($order['total'], 2, ',', '.') . " ₺</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gelişmiş Raporlar - POS Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .card { background: rgba(255, 255, 255, 0.95); border-radius: 15px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); }
        .stat-card { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
        .chart-container { position: relative; height: 300px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-utensils me-2"></i>
                Restoran POS
            </a>
            <div class="d-flex">
                <a class="btn btn-outline-light me-2" href="index.php">Yeni Sipariş</a>
                <a class="btn btn-warning me-2" href="report.php">Basit Rapor</a>
                <a class="btn btn-primary" href="advanced_reports.php">Gelişmiş Rapor</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Filtreler -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-filter me-2"></i>Rapor Filtreleri</h5>
            </div>
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Başlangıç Tarihi</label>
                        <input type="date" class="form-control" name="start" value="<?php echo e($start); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Bitiş Tarihi</label>
                        <input type="date" class="form-control" name="end" value="<?php echo e($end); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Rapor Türü</label>
                        <select class="form-select" name="type">
                            <option value="summary" <?php echo $report_type === 'summary' ? 'selected' : ''; ?>>Özet</option>
                            <option value="products" <?php echo $report_type === 'products' ? 'selected' : ''; ?>>Ürün Analizi</option>
                            <option value="trends" <?php echo $report_type === 'trends' ? 'selected' : ''; ?>>Trend Analizi</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                                Rapor Oluştur
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Özet Kartları -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                        <h4><?php echo count($orders); ?></h4>
                        <p class="mb-0">Toplam Sipariş</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-lira-sign fa-2x mb-2"></i>
                        <h4><?php echo number_format(array_sum(array_column($orders, 'total')), 2, ',', '.'); ?> ₺</h4>
                        <p class="mb-0">Toplam Ciro</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-line fa-2x mb-2"></i>
                        <h4><?php echo count($orders) > 0 ? number_format(array_sum(array_column($orders, 'total')) / count($orders), 2, ',', '.') : '0'; ?> ₺</h4>
                        <p class="mb-0">Ortalama Sipariş</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-box fa-2x mb-2"></i>
                        <h4><?php echo count($product_stats); ?></h4>
                        <p class="mb-0">Satılan Ürün Çeşidi</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Butonları -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <h5><i class="fas fa-download me-2"></i>Raporu Dışa Aktar</h5>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['type' => 'export', 'format' => 'excel'])); ?>" class="btn btn-success me-2">
                    <i class="fas fa-file-excel me-1"></i>
                    Excel (.xls)
                </a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['type' => 'export', 'format' => 'pdf'])); ?>" class="btn btn-danger">
                    <i class="fas fa-file-pdf me-1"></i>
                    PDF
                </a>
            </div>
        </div>

        <?php if ($report_type === 'summary'): ?>
        <!-- Sipariş Listesi -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>Sipariş Detayları</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Sipariş No</th>
                                <th>Tarih</th>
                                <th>Ara Toplam</th>
                                <th>KDV</th>
                                <th>Servis</th>
                                <th>İndirim</th>
                                <th>Toplam</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo $order['id']; ?></strong></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($order['date'])); ?></td>
                                <td><?php echo number_format($order['subtotal'], 2, ',', '.'); ?> ₺</td>
                                <td><?php echo number_format($order['tax'], 2, ',', '.'); ?> ₺</td>
                                <td><?php echo number_format($order['service_fee'], 2, ',', '.'); ?> ₺</td>
                                <td><?php echo number_format($order['discount'], 2, ',', '.'); ?> ₺</td>
                                <td><strong><?php echo number_format($order['total'], 2, ',', '.'); ?> ₺</strong></td>
                                <td>
                                    <a href="report_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php elseif ($report_type === 'products'): ?>
        <!-- Ürün Analizi -->
        <div class="row g-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-bar me-2"></i>Ürün Satış Analizi</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Ürün Adı</th>
                                        <th>Satılan Adet</th>
                                        <th>Toplam Ciro</th>
                                        <th>Ortalama Fiyat</th>
                                        <th>Sipariş Sayısı</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($product_stats as $product): ?>
                                    <tr>
                                        <td><strong><?php echo e($product['name']); ?></strong></td>
                                        <td><?php echo number_format($product['total_quantity']); ?></td>
                                        <td><?php echo number_format($product['total_revenue'], 2, ',', '.'); ?> ₺</td>
                                        <td><?php echo number_format($product['avg_price'], 2, ',', '.'); ?> ₺</td>
                                        <td><?php echo $product['order_count']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-trophy me-2"></i>En Çok Satan Ürünler</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach (array_slice($top_products, 0, 5) as $index => $product): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong><?php echo $index + 1; ?>.</strong> <?php echo e($product['name']); ?>
                            </div>
                            <div class="text-end">
                                <small class="text-muted"><?php echo $product['total_quantity']; ?> adet</small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif ($report_type === 'trends'): ?>
        <!-- Trend Analizi -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-line me-2"></i>Aylık Trend Analizi</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Trend grafiği
        <?php if ($report_type === 'trends'): ?>
        const ctx = document.getElementById('trendChart').getContext('2d');
        const trendData = <?php echo json_encode(array_reverse($monthly_comparison)); ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: trendData.map(item => item.month),
                datasets: [{
                    label: 'Aylık Ciro (₺)',
                    data: trendData.map(item => parseFloat(item.total_revenue)),
                    borderColor: 'rgb(102, 126, 234)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('tr-TR') + ' ₺';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
