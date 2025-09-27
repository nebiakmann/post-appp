# 🍽️ Restoran POS Sistemi - Kurulum Rehberi

## 📋 Sistem Gereksinimleri

- **İşletim Sistemi:** Windows 7/8/10/11
- **RAM:** Minimum 2GB (Önerilen 4GB+)
- **Disk Alanı:** 500MB boş alan
- **İnternet:** İlk kurulum için (CDN'ler için)

## 🚀 Hızlı Kurulum (XAMPP ile)

### 1. XAMPP İndirme ve Kurulum
1. [XAMPP'ı indirin](https://www.apachefriends.org/download.html)
2. İndirilen dosyayı çalıştırın
3. Kurulum sırasında **Apache** ve **MySQL** seçeneklerini işaretleyin
4. Kurulumu tamamlayın

### 2. POS Sistemi Kurulumu
1. Bu klasördeki tüm dosyaları `C:\xampp\htdocs\pos-app\` klasörüne kopyalayın
2. XAMPP Control Panel'i açın
3. **Apache** ve **MySQL** servislerini başlatın
4. Tarayıcıda `http://localhost/pos-app/` adresine gidin

### 3. Veritabanı Kurulumu
1. `http://localhost/phpmyadmin/` adresine gidin
2. "Yeni" butonuna tıklayın
3. Veritabanı adı: `pos_app`
4. Karakter seti: `utf8mb4_turkish_ci`
5. "Oluştur" butonuna tıklayın
6. `pos_app` veritabanını seçin
7. "İçe Aktar" sekmesine gidin
8. `db.sql` dosyasını seçin ve "Git" butonuna tıklayın

## 🔧 Alternatif Kurulum Yöntemleri

### Yöntem 1: Portable XAMPP
1. [Portable XAMPP indirin](https://portableapps.com/apps/development/xampp)
2. USB'ye kopyalayın
3. `xampp-control.exe` çalıştırın
4. Apache ve MySQL'i başlatın
5. POS dosyalarını `htdocs` klasörüne kopyalayın

### Yöntem 2: Docker (Gelişmiş)
```bash
# Docker Compose dosyası ile
docker-compose up -d
```

### Yöntem 3: WAMP/MAMP
1. WAMP (Windows) veya MAMP (Mac) kurun
2. POS dosyalarını `www` klasörüne kopyalayın
3. Veritabanını oluşturun ve `db.sql` dosyasını import edin

## 🌐 Ağ Üzerinden Erişim

### Yerel Ağda Paylaşım
1. XAMPP Control Panel'de Apache'yi başlatın
2. Windows Firewall'da port 80'i açın
3. Diğer bilgisayarlardan `http://[BILGISAYAR-IP]/pos-app/` adresine erişin

### IP Adresi Bulma
```cmd
ipconfig
```
"IPv4 Adresi" değerini not edin.

## 📱 Mobil Uyumluluk

Sistem tamamen responsive tasarıma sahiptir:
- **Mobil cihazlarda** otomatik olarak uyum sağlar
- **Tablet** ve **telefon** desteği
- **Dokunmatik** arayüz optimizasyonu

## 🔒 Güvenlik Ayarları

### Veritabanı Güvenliği
1. `db.php` dosyasında şifreleri değiştirin
2. Güçlü MySQL şifresi kullanın
3. `phpmyadmin` klasörünü şifreleyin

### Ağ Güvenliği
- Sadece güvenilir ağlarda kullanın
- Firewall ayarlarını kontrol edin
- HTTPS kullanımı için SSL sertifikası ekleyin

## 🛠️ Sorun Giderme

### Apache Başlamıyor
- Port 80'in başka program tarafından kullanılıp kullanılmadığını kontrol edin
- Skype, IIS gibi programları kapatın
- XAMPP'ı yönetici olarak çalıştırın

### MySQL Başlamıyor
- Port 3306'in kullanımda olup olmadığını kontrol edin
- `my.ini` dosyasında port değiştirin
- XAMPP'ı yeniden başlatın

### Veritabanı Bağlantı Hatası
- MySQL servisinin çalıştığını kontrol edin
- `db.php` dosyasındaki ayarları kontrol edin
- `test_connection.php` dosyasını çalıştırın

### Sayfa Görünmüyor
- Dosya yollarını kontrol edin
- Apache error log'larını inceleyin
- PHP hatalarını görmek için `error_reporting` açın

## 📞 Destek

### Log Dosyaları
- Apache: `C:\xampp\apache\logs\error.log`
- MySQL: `C:\xampp\mysql\data\*.err`
- PHP: `C:\xampp\php\logs\php_error_log`

### Test Dosyaları
- `test_connection.php` - Veritabanı bağlantı testi
- `http://localhost/pos-app/test_connection.php`

## 🎯 Özellikler

✅ **Modern UI/UX Tasarım**
✅ **Responsive Mobil Uyumluluk**
✅ **Gradient ve Animasyon Efektleri**
✅ **Glassmorphism Tasarım**
✅ **Font Awesome İkonlar**
✅ **Inter Modern Font**
✅ **Smooth Animasyonlar**
✅ **Print-Friendly Fiş**
✅ **Gerçek Zamanlı Hesaplama**
✅ **KDV/Servis/İndirim Desteği**
✅ **Sipariş Geçmişi**
✅ **Raporlama Sistemi**

## 📊 Sistem Bilgileri

- **PHP Sürümü:** 7.4+
- **MySQL Sürümü:** 5.7+
- **Bootstrap:** 5.3.3
- **Font Awesome:** 6.4.0
- **Tarayıcı Desteği:** Chrome, Firefox, Safari, Edge

---

**Not:** Bu sistem eğitim amaçlı geliştirilmiştir. Üretim ortamında kullanmadan önce güvenlik testlerini yapın.

