# 🍽️ Restoran POS Sistemi

Modern, responsive ve kullanıcı dostu restoran POS (Point of Sale) sistemi.

## ✨ Özellikler

- 🎨 **Modern UI/UX Tasarım** - Gradient, glassmorphism ve animasyon efektleri
- 📱 **Responsive Tasarım** - Mobil, tablet ve masaüstü uyumlu
- 🛒 **Sipariş Yönetimi** - Kolay ürün ekleme ve sepet yönetimi
- 💰 **Fiyat Hesaplama** - KDV, servis ve indirim desteği
- 🧾 **Fiş Yazdırma** - Print-friendly modern fiş tasarımı
- 📊 **Raporlama** - Sipariş geçmişi ve satış raporları
- 🔧 **Kolay Kurulum** - Otomatik kurulum scripti
- 🌐 **Ağ Desteği** - Yerel ağda paylaşım

## 🚀 Hızlı Başlangıç

### 1. Otomatik Kurulum (Önerilen)
```bash
# Windows'ta
install.bat

# Kurulum tamamlandıktan sonra
http://localhost/pos-app/
```

### 2. Manuel Kurulum
1. XAMPP'ı indirin ve kurun
2. Dosyaları `C:\xampp\htdocs\pos-app\` klasörüne kopyalayın
3. Apache ve MySQL'i başlatın
4. `http://localhost/phpmyadmin/` adresine gidin
5. `pos_app` veritabanını oluşturun
6. `db.sql` dosyasını import edin

### 3. Docker Kurulumu (Gelişmiş)
```bash
docker-compose up -d
```

## 📋 Sistem Gereksinimleri

- **PHP:** 7.4+
- **MySQL:** 5.7+
- **Web Sunucu:** Apache/Nginx
- **Tarayıcı:** Chrome, Firefox, Safari, Edge

## 🎯 Kullanım

### Menü Yönetimi
- Yeni ürün ekleme
- Ürün düzenleme/silme
- Fiyat güncelleme

### Sipariş Alma
- Menüden ürün seçme
- Miktar belirleme
- Sepete ekleme
- KDV/servis/indirim ayarlama

### Fiş İşlemleri
- Sipariş kaydetme
- Fiş yazdırma
- Sipariş geçmişi

## 🔧 Konfigürasyon

### Veritabanı Ayarları
`config.php` dosyasını düzenleyin:
```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'pos_app');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Ortam Değişkenleri
`env.example` dosyasını `.env` olarak kopyalayın ve düzenleyin.

## 🌐 Ağ Üzerinden Erişim

### Yerel Ağda Paylaşım
1. XAMPP'da Apache'yi başlatın
2. Windows Firewall'da port 80'i açın
3. IP adresinizi bulun: `ipconfig`
4. Diğer bilgisayarlardan: `http://[IP-ADRESI]/pos-app/`

### Güvenlik
- Sadece güvenilir ağlarda kullanın
- Güçlü MySQL şifresi kullanın
- Firewall ayarlarını kontrol edin

## 📁 Dosya Yapısı

```
pos-app/
├── index.php              # Ana sayfa
├── orders.php             # Sipariş işlemleri
├── report.php             # Raporlar
├── db.php                 # Veritabanı bağlantısı
├── config.php             # Konfigürasyon
├── db.sql                 # Veritabanı şeması
├── install.bat            # Otomatik kurulum
├── docker-compose.yml     # Docker kurulumu
├── KURULUM_REHBERI.md     # Detaylı kurulum rehberi
└── README.md              # Bu dosya
```

## 🛠️ Sorun Giderme

### Apache Başlamıyor
- Port 80'in kullanımda olup olmadığını kontrol edin
- Skype, IIS gibi programları kapatın
- XAMPP'ı yönetici olarak çalıştırın

### MySQL Başlamıyor
- Port 3306'in kullanımda olup olmadığını kontrol edin
- XAMPP'ı yeniden başlatın

### Veritabanı Bağlantı Hatası
- MySQL servisinin çalıştığını kontrol edin
- `config.php` dosyasındaki ayarları kontrol edin
- `test_connection.php` dosyasını çalıştırın

### Sistem Durumu
`http://localhost/pos-app/system_status.php` adresinden sistem durumunu kontrol edebilirsiniz.

## 📞 Destek

### Log Dosyaları
- Apache: `C:\xampp\apache\logs\error.log`
- MySQL: `C:\xampp\mysql\data\*.err`
- PHP: `C:\xampp\php\logs\php_error_log`

### Test Dosyaları
- `test_connection.php` - Veritabanı bağlantı testi
- `system_status.php` - Sistem durumu kontrolü

## 🎨 Tasarım Özellikleri

- **Gradient Arka Planlar** - Modern görünüm
- **Glassmorphism Efektleri** - Cam görünümü
- **Smooth Animasyonlar** - Yumuşak geçişler
- **Font Awesome İkonlar** - Profesyonel ikonlar
- **Inter Font** - Modern tipografi
- **Responsive Grid** - Esnek düzen

## 📊 Teknik Detaylar

- **Frontend:** HTML5, CSS3, JavaScript ES6+, Bootstrap 5
- **Backend:** PHP 7.4+, PDO
- **Veritabanı:** MySQL 8.0
- **İkonlar:** Font Awesome 6.4.0
- **Font:** Inter (Google Fonts)

## 📝 Lisans

Bu proje eğitim amaçlı geliştirilmiştir. Ticari kullanım için güvenlik testlerini yapın.

## 🤝 Katkıda Bulunma

1. Projeyi fork edin
2. Feature branch oluşturun (`git checkout -b feature/AmazingFeature`)
3. Değişikliklerinizi commit edin (`git commit -m 'Add some AmazingFeature'`)
4. Branch'inizi push edin (`git push origin feature/AmazingFeature`)
5. Pull Request oluşturun

---

**Not:** Bu sistem sürekli geliştirilmektedir. Yeni özellikler ve iyileştirmeler için güncellemeleri takip edin.

