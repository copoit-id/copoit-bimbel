# Dashboard Refactor Documentation

## Overview
Dashboard admin telah direfactor dengan layout modern seperti Shopall dashboard dengan fitur-fitur berikut:

## Fitur Baru

### 1. Upgrade Plan Banner
- Banner promosi upgrade plan di bagian atas dashboard
- Bisa di-close (disimpan di localStorage)
- Bisa di-setting via:
  - Database (table `settings`)
  - Config file (`config/settings.php`)
  - Environment variables

**Settings:**
```php
// config/settings.php atau database
'upgrade_banner_enabled' => true,
'upgrade_banner_title' => 'Unlock premium features',
'upgrade_banner_description' => 'Upgrade to Pro...',
'upgrade_banner_button_text' => 'Upgrade Now',
'upgrade_banner_button_url' => '#',
```

### 2. Statistik Cards dengan Trend
4 statistik card dengan perbandingan periode sebelumnya:
- **Total Revenue** - Pendapatan total dengan trend
- **Active Users** - Jumlah user dengan trend
- **Tryout Attempts** - Percobaan tryout dengan trend
- **Packages Sold** - Package terjual dengan trend

**Periode yang tersedia:**
- 7 Days
- 30 Days (default)
- 3 Months
- 1 Year

### 3. Grafik
- **Bar Chart** - Tryout attempts over time
- **Line Chart** - Revenue trends dengan gradient

Grafik support periode filter dan auto-update saat ganti periode.

### 4. Recent Activity Tables
- Recent Transactions (8 terbaru)
- Recent Users (8 terbaru)
- Dengan status badge dan action buttons

## Komponen Baru

### Dashboard Components
```blade
<x-dashboard.stat-card />
<x-dashboard.upgrade-banner />
<x-dashboard.chart-card />
<x-dashboard.activity-table />
```

### Usage Examples

```blade
{{-- Stat Card --}}
<x-dashboard.stat-card
    label="Total Revenue"
    value="Rp 1,234,567"
    icon="ri-money-dollar-circle-line"
    trend="up"
    trend-value="+41%"
    trend-label="from last period"
    color="green"
/>

{{-- Upgrade Banner --}}
<x-dashboard.upgrade-banner
    title="Unlock premium features"
    description="Upgrade to Pro..."
    button-text="Upgrade Now"
    button-url="#"
    can-close="true"
/>

{{-- Chart Card --}}
<x-dashboard.chart-card
    title="Tryout Attempts"
    chart-id="tryoutChart"
    chart-type="bar"
    height="280px"
    selected-period="30d"
/>

{{-- Activity Table --}}
<x-dashboard.activity-table
    title="Recent Transactions"
    :columns="['Customer', 'Date', 'Amount', 'Status']"
/>
```

## API Endpoint

Dashboard sekarang menerima parameter `period`:
```
GET /admin/dashboard?period=7d
GET /admin/dashboard?period=30d
GET /admin/dashboard?period=90d
GET /admin/dashboard?period=1y
```

## Database

### Settings Table
Table `settings` untuk menyimpan konfigurasi dashboard:

| Column | Type | Description |
|--------|------|-------------|
| key | string | Setting key |
| value | text | Setting value |
| type | string | string, boolean, integer, json |
| group | string | Setting group |
| label | string | Display label |
| description | text | Description |
| is_public | boolean | Public/private |

### Setting Model
```php
// Get setting
Setting::get('upgrade_banner_enabled', true);

// Set setting
Setting::set('upgrade_banner_enabled', false);
```

## Dependencies

- Chart.js (via CDN) - untuk grafik
- Alpine.js (sudah ada) - untuk interaktivitas

## File Changes

### New Files
- `resources/views/components/dashboard/stat-card.blade.php`
- `resources/views/components/dashboard/upgrade-banner.blade.php`
- `resources/views/components/dashboard/chart-card.blade.php`
- `resources/views/components/dashboard/activity-table.blade.php`
- `resources/views/admin/pages/dashboard.blade.php` (refactored)
- `app/Models/Setting.php`
- `config/settings.php`
- `database/migrations/2026_03_14_213011_create_settings_table.php`

### Modified Files
- `app/Http/Controllers/admin/DashboardController.php`

## Next Steps (Optional)

1. **Super Admin Settings Page** - Buat halaman untuk mengatur settings via UI
2. **Export Functionality** - Implement export CSV/Excel untuk transactions
3. **Real-time Updates** - Tambahkan WebSocket untuk data real-time
4. **More Charts** - Tambahkan chart lain seperti pie chart, doughnut chart
