<?php
// db.php - Veritabanı bağlantısı ve yardımcı fonksiyonlar

declare(strict_types=1);

// Konfigürasyon dosyasını yükle
require_once __DIR__ . '/config.php';

// Veritabanı ayarlarını al
$DB_HOST = getConfig('DB_HOST', '127.0.0.1');
$DB_PORT = getConfig('DB_PORT', '3306');
$DB_NAME = getConfig('DB_NAME', 'pos_app');
$DB_USER = getConfig('DB_USER', 'root');
$DB_PASS = getConfig('DB_PASS', '');

// PDO nesnesini tekil (singleton) döndürür
function db(): PDO {
    static $pdo = null;
    global $DB_HOST, $DB_PORT, $DB_NAME, $DB_USER, $DB_PASS;
    if ($pdo === null) {
        $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Veritabanı bağlantı hatası: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
        }
    }
    return $pdo;
}

// Yardımcı: güvenli değer okuma
function post(string $key, $default = null) {
    return $_POST[$key] ?? $default;
}

function get(string $key, $default = null) {
    return $_GET[$key] ?? $default;
}

// Yardımcı: doğrulama
function require_field($value, string $message): void {
    if ($value === null || $value === '' ) {
        throw new InvalidArgumentException($message);
    }
}

// Menüyü getir
function get_menu_items(): array {
    $stmt = db()->query('SELECT id, name, price FROM menu ORDER BY name ASC');
    return $stmt->fetchAll();
}

// Menüde tek bir ürünü getir
function get_menu_item(int $id): ?array {
    $stmt = db()->prepare('SELECT id, name, price FROM menu WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// Menü ürün ekle
function add_menu_item(string $name, float $price): int {
    require_field($name, 'Ürün adı boş olamaz.');
    if (!is_numeric($price) || $price < 0) {
        throw new InvalidArgumentException('Fiyat geçerli bir sayı olmalıdır.');
    }
    $stmt = db()->prepare('INSERT INTO menu (name, price) VALUES (?, ?)');
    $stmt->execute([$name, $price]);
    return (int) db()->lastInsertId();
}

// Menü ürün güncelle
function update_menu_item(int $id, string $name, float $price): void {
    require_field($name, 'Ürün adı boş olamaz.');
    if (!is_numeric($price) || $price < 0) {
        throw new InvalidArgumentException('Fiyat geçerli bir sayı olmalıdır.');
    }
    $stmt = db()->prepare('UPDATE menu SET name = ?, price = ? WHERE id = ?');
    $stmt->execute([$name, $price, $id]);
}

// Menü ürün sil
function delete_menu_item(int $id): void {
    $stmt = db()->prepare('DELETE FROM menu WHERE id = ?');
    $stmt->execute([$id]);
}

// Sipariş oluştur (items: [ [menu_id, quantity], ... ])
function create_order(array $items, float $taxRate = 0.08, float $serviceRate = 0.00, float $discount = 0.00, ?string $note = null): int {
    if (empty($items)) {
        throw new InvalidArgumentException('Sipariş boş olamaz.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $subtotal = 0.0;
        $resolvedItems = [];
        foreach ($items as $item) {
            $menuId = (int)($item['menu_id'] ?? 0);
            $qty = (int)($item['quantity'] ?? 0);
            if ($menuId <= 0 || $qty <= 0) {
                throw new InvalidArgumentException('Ürün ve adet geçerli olmalıdır.');
            }
            $menu = get_menu_item($menuId);
            if (!$menu) {
                throw new InvalidArgumentException('Seçilen ürün bulunamadı.');
            }
            $linePrice = (float)$menu['price'];
            $lineTotal = $linePrice * $qty;
            $subtotal += $lineTotal;
            $resolvedItems[] = [
                'menu_id' => $menuId,
                'quantity' => $qty,
                'price' => $linePrice,
                'total' => $lineTotal,
            ];
        }

        $tax = round($subtotal * $taxRate, 2);
        $service = round($subtotal * $serviceRate, 2);
        $discountAmount = round($discount, 2);
        $total = round($subtotal + $tax + $service - $discountAmount, 2);

        $stmtOrder = $pdo->prepare('INSERT INTO orders (date, subtotal, tax, service_fee, discount, total, note) VALUES (NOW(), ?, ?, ?, ?, ?, ?)');
        $stmtOrder->execute([$subtotal, $tax, $service, $discountAmount, $total, $note]);
        $orderId = (int)$pdo->lastInsertId();

        $stmtItem = $pdo->prepare('INSERT INTO order_items (order_id, menu_id, quantity, price, total) VALUES (?, ?, ?, ?, ?)');
        foreach ($resolvedItems as $ri) {
            $stmtItem->execute([$orderId, $ri['menu_id'], $ri['quantity'], $ri['price'], $ri['total']]);
        }

        $pdo->commit();
        return $orderId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Raporlar
function get_orders(?string $startDate = null, ?string $endDate = null): array {
    $sql = 'SELECT * FROM orders WHERE 1=1';
    $params = [];
    if ($startDate) {
        $sql .= ' AND date >= ?';
        $params[] = $startDate . ' 00:00:00';
    }
    if ($endDate) {
        $sql .= ' AND date <= ?';
        $params[] = $endDate . ' 23:59:59';
    }
    $sql .= ' ORDER BY date DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_order_details(int $orderId): array {
    $stmtOrder = db()->prepare('SELECT * FROM orders WHERE id = ?');
    $stmtOrder->execute([$orderId]);
    $order = $stmtOrder->fetch();
    if (!$order) return [];

    $stmtItems = db()->prepare('SELECT oi.*, m.name FROM order_items oi JOIN menu m ON m.id = oi.menu_id WHERE oi.order_id = ? ORDER BY oi.id ASC');
    $stmtItems->execute([$orderId]);
    $items = $stmtItems->fetchAll();
    return ['order' => $order, 'items' => $items];
}

function get_revenue_summary(string $period = 'daily'): array {
    if ($period === 'monthly') {
        $sql = "SELECT DATE_FORMAT(date, '%Y-%m') AS period, SUM(total) AS revenue FROM orders GROUP BY DATE_FORMAT(date, '%Y-%m') ORDER BY period DESC";
    } else {
        $sql = "SELECT DATE(date) AS period, SUM(total) AS revenue FROM orders GROUP BY DATE(date) ORDER BY period DESC";
    }
    $stmt = db()->query($sql);
    return $stmt->fetchAll();
}

// Basit HTML kaçışı
function e(string $str): string { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }
?>
