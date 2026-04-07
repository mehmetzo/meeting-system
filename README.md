# 📅 Dijital Toplantı Katılım Sistemi

<div align="center">

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Apache](https://img.shields.io/badge/Apache-2.4-D22128?style=for-the-badge&logo=apache&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![LDAP](https://img.shields.io/badge/LDAP-Active%20Directory-0078D4?style=for-the-badge&logo=microsoft&logoColor=white)

**QR kod tabanlı, kurumsal düzeyde dijital toplantı katılım yönetim sistemi.**

</div>

---

## 📋 İçindekiler

- [Özellikler](#-özellikler)
- [Gereksinimler](#-gereksinimler)
- [Kurulum](#-kurulum)
- [Kullanım](#-kullanım)
- [Admin Paneli](#-admin-paneli)
- [Ayarlar](#-ayarlar)
- [Güvenlik](#-güvenlik)
- [Proje Yapısı](#-proje-yapısı)

---

## ✨ Özellikler

### 📱 Katılım Ekranı
- 🔲 **QR Kod ile Katılım** — Uygulama indirmeye gerek yok, QR okutunca form açılır
- 👤 **Personel Girişi** — LDAP / Active Directory ile kimlik doğrulama
- 🙋 **Misafir Girişi** — Ad, kurum, unvan, e-posta ve telefon ile kayıt
- ✅ **Anlık Onay** — Kayıt tamamlandığında animasyonlu teşekkür ekranı
- 🔒 **Tamamlanmış Toplantı Koruması** — Tamamlanan toplantılara yeni kayıt engeli

### 🖥️ Admin Paneli
- 📊 **Gösterge Paneli** — Gerçek zamanlı istatistikler ve Chart.js grafikleri
- 📋 **Toplantı Yönetimi** — Oluşturma, listeleme, filtreleme, silme
- 🔲 **QR Kod Üretimi** — Her toplantı için otomatik QR kod ve yazdırma desteği
- 📄 **Raporlama** — Katılımcı listesi, personel/misafir ayrımı
- 📤 **Dışa Aktarma** — CSV ve PDF formatında toplantı bazlı rapor
- 📜 **Erişim Logları** — Tüm kullanıcı işlemlerinin kayıt altına alınması
- 🔐 **LDAP ile Admin Girişi** — Kurumsal AD hesaplarıyla yönetici erişimi

### ⚙️ Sistem Ayarları *(Admin Panelinden)*
- 🏥 **Kurum Bilgileri** — Kurum adı, logo, footer metni, tema rengi
- 🔐 **LDAP / Active Directory** — Kurumsal hesaplarla giriş ve grup bazlı yetkilendirme
- 👥 **Admin Kullanıcılar** — Çoklu yönetici desteği, rol yönetimi
- 🔑 **Şifre Değiştirme** — Admin şifresi güvenli değiştirme

---

## 📦 Gereksinimler

- [Docker](https://docs.docker.com/get-docker/) 20.10+
- [Docker Compose](https://docs.docker.com/compose/install/) v2+
- 512MB RAM (minimum)

---

## 🚀 Kurulum

### 1. Repoyu klonlayın

```bash
git clone https://github.com/mehmetzo/meeting-system.git
cd meeting-system
```

### 2. Container'ları başlatın

```bash
docker compose up -d --build
```

### 4. Admin girişi

```
URL      : http://SUNUCU_IP:6767/admin/login.php
Kullanıcı: admin
Şifre    : admin
```

> ⚠️ İlk girişten sonra **Ayarlar → Şifre Değiştir** kısmından şifrenizi değiştirin.

---

## 🌐 Erişim Adresleri

| Sayfa | URL |
|-------|-----|
| 🏠 Karşılama | `http://SUNUCU_IP:6767/` |
| 🔧 Admin Paneli | `http://SUNUCU_IP:6767/admin/login.php` |
| 🗄️ Veritabanı (dış) | `SUNUCU_IP:3310` |

---

## 📱 Kullanım

QR kodu toplantı salonuna asın veya ekrana yansıtın. Katılımcılar telefon kamerasıyla okutarak forma ulaşır.

### Katılım Akışı

```
QR Okut → Personel / Misafir Seç → Formu Doldur → Kaydet → Onay Ekranı
```

### Personel Akışı (LDAP aktif)

```
QR Okut → Personel → LDAP Kullanıcı Adı & Şifre → Kaydet
         → TC No ve Telefon LDAP'tan otomatik çekilir → Rapora yansır
```

### Misafir Akışı

```
QR Okut → Misafir → Ad Soyad, Kurum, Unvan, E-posta, Telefon → Kaydet
```

---

## 🖥️ Admin Paneli

### Toplantı Yönetimi

| Özellik | Açıklama |
|---------|----------|
| Toplantı Oluştur | Ad, tarih, saat ve konum bilgisiyle yeni toplantı |
| QR Kod | A4 yatay baskıya uygun QR çıktısı |
| Rapor | Katılımcı listesi, personel/misafir ayrımı |
| Tamamla | Toplantıyı kapatır, yeni katılım engellenir |
| Sil | Toplantı ve tüm katılım kayıtlarını siler |

### Yetkiler

| Özellik | Superadmin | Admin | Viewer |
|---------|-----------|-------|--------|
| Toplantı oluştur | ✅ | ✅ | ✅ |
| QR görüntüle | ✅ | ✅ | ✅ |
| Rapor görüntüle | ✅ | ✅ | ✅ |
| Dışa aktar | ✅ | ✅ | ❌ |
| Toplantı sil | ✅ | ❌ | ❌ |
| Erişim logları | ✅ | ❌ | ❌ |
| Sistem ayarları | ✅ | ❌ | ❌ |

---

## ⚙️ Ayarlar

Tüm sistem ayarları **Admin Paneli → Ayarlar** üzerinden yapılır.

### 🏥 Kurum Ayarları
- Kurum adı ve hastane/birim adı
- Kurum logosu yükleme (PNG, JPG, SVG — max 2MB)
- Footer metni
- Tema rengi

### 🔐 LDAP Ayarları

```
Host      : Active Directory sunucu IP
Port      : 389
Base DN   : dc=domain,dc=local
Domain    : domain.local
Bind User : servis_hesabi
Grup      : yetkili_grup (opsiyonel)
TC Attr   : employeeID
Tel Attr  : mobile
```

---

## 🔒 Güvenlik

| Özellik | Açıklama |
|---------|----------|
| **LDAP Entegrasyonu** | Kurumsal AD hesaplarıyla merkezi kimlik doğrulama |
| **Grup Bazlı Yetki** | Sadece belirlenen AD grubundaki kullanıcılar giriş yapabilir |
| **Erişim Logları** | Tüm admin işlemleri kullanıcı bazlı kayıt altında |
| **Session Güvenliği** | 60 dakikalık oturum süresi, HTTPOnly cookie |
| **Rol Yönetimi** | superadmin / admin / viewer rol hiyerarşisi |
| **QR Token** — | Her toplantı için kriptografik güvenli token |
| **Tamamlanmış Koruma** | Kapatılan toplantılara yeni katılım engeli |

---

## 📁 Proje Yapısı

```
meeting-system/
├── docker-compose.yml
├── Dockerfile
├── apache/
│   └── 000-default.conf
├── php/
│   └── php.ini
├── mysql/
│   └── my.cnf
├── sql/
│   └── init.sql
└── html/
    ├── index.php
    ├── config/
    │   ├── database.php
    │   ├── session.php
    │   └── ldap.php
    ├── includes/
    │   ├── lang.php
    │   ├── header.php
    │   ├── sidebar.php
    │   └── footer.php
    ├── admin/
    │   ├── login.php
    │   ├── logout.php
    │   ├── index.php
    │   ├── meetings.php
    │   ├── meeting_detail.php
    │   ├── export.php
    │   ├── reports.php
    │   ├── logs.php
    │   ├── settings.php
    │   └── api.php
    ├── meeting/
    │   ├── create.php
    │   ├── qr.php
    │   └── report.php
    ├── attend/
    │   ├── index.php
    │   ├── staff.php
    │   ├── guest.php
    │   └── success.php
    └── assets/
        ├── css/
        │   ├── style.css
        │   └── attend.css
        ├── js/
        │   └── app.js
        └── img/
```

---

## 🗄️ Veritabanı Şeması

```
settings         — Sistem yapılandırması
admin_users      — Admin kullanıcıları ve roller
meetings         — Toplantı kayıtları
attendees        — Katılımcı kayıtları (personel & misafir)
access_logs      — Erişim ve işlem logları
ldap_users_cache — LDAP kullanıcı önbelleği
```

<div align="center">
  <sub>Kurumsal toplantı yönetimi için dijital dönüşüm 📅</sub>
</div>
