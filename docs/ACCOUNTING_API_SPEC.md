# 🔌 API SPECIFICATION - Sistem Akuntansi SIPKUD (Future Enhancement)

## 📋 OVERVIEW

Dokumentasi ini adalah spesifikasi API untuk sistem akuntansi SIPKUD. 
**Note**: API ini belum diimplementasi, ini adalah blueprint untuk pengembangan future.

---

## 🔐 AUTHENTICATION

Semua endpoint memerlukan authentication menggunakan Laravel Sanctum.

**Headers**:
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

---

## 📊 ENDPOINTS

### **1. UNIT USAHA**

#### **GET /api/unit-usaha**
List semua unit usaha

**Response**:
```json
{
  "data": [
    {
      "id": 1,
      "kode_unit": "USP",
      "nama_unit": "Unit Simpan Pinjam",
      "deskripsi": "Unit usaha simpan pinjam",
      "status": "aktif"
    }
  ]
}
```

#### **POST /api/unit-usaha**
Buat unit usaha baru

**Request**:
```json
{
  "kode_unit": "PERDAGANGAN",
  "nama_unit": "Unit Perdagangan",
  "deskripsi": "Unit usaha perdagangan umum",
  "status": "aktif"
}
```

---

### **2. AKUN (COA)**

#### **GET /api/akun**
List semua akun

**Query Parameters**:
- `tipe_akun`: aset, kewajiban, ekuitas, pendapatan, beban
- `status`: aktif, nonaktif

**Response**:
```json
{
  "data": [
    {
      "id": 1,
      "kode_akun": "1-1000",
      "nama_akun": "Kas",
      "tipe_akun": "aset",
      "status": "aktif"
    }
  ]
}
```

---

### **3. TRANSAKSI KAS**

#### **GET /api/transaksi-kas**
List transaksi kas

**Query Parameters**:
- `tanggal_dari`: Y-m-d
- `tanggal_sampai`: Y-m-d
- `unit_usaha_id`: integer
- `jenis_transaksi`: masuk, keluar

**Response**:
```json
{
  "data": [
    {
      "id": 1,
      "tanggal_transaksi": "2025-01-23",
      "uraian": "Pendapatan bunga pinjaman",
      "jenis_transaksi": "masuk",
      "jumlah": 1000000,
      "akun_kas": {
        "id": 1,
        "kode_akun": "1-1000",
        "nama_akun": "Kas"
      },
      "akun_lawan": {
        "id": 23,
        "kode_akun": "4-1000",
        "nama_akun": "Pendapatan Jasa Pinjaman"
      },
      "unit_usaha": {
        "id": 1,
        "nama_unit": "Unit Simpan Pinjam"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 100
  }
}
```

#### **POST /api/transaksi-kas**
Buat transaksi kas baru (auto-create jurnal)

**Request**:
```json
{
  "tanggal_transaksi": "2025-01-23",
  "unit_usaha_id": 1,
  "jenis_transaksi": "masuk",
  "akun_kas_id": 1,
  "akun_lawan_id": 23,
  "jumlah": 1000000,
  "uraian": "Pendapatan bunga pinjaman Januari 2025"
}
```

**Response**:
```json
{
  "message": "Transaksi kas berhasil disimpan",
  "data": {
    "id": 1,
    "tanggal_transaksi": "2025-01-23",
    "uraian": "Pendapatan bunga pinjaman Januari 2025",
    "jenis_transaksi": "masuk",
    "jumlah": 1000000,
    "jurnal": {
      "id": 1,
      "nomor_jurnal": "JRN/2025/01/00001",
      "status": "posted"
    }
  }
}
```

---

### **4. JURNAL (MEMORIAL)**

#### **GET /api/jurnal**
List jurnal

**Query Parameters**:
- `tanggal_dari`: Y-m-d
- `tanggal_sampai`: Y-m-d
- `unit_usaha_id`: integer
- `jenis_jurnal`: kas_harian, memorial, penyesuaian, penutup
- `status`: draft, posted, void

**Response**:
```json
{
  "data": [
    {
      "id": 1,
      "nomor_jurnal": "JRN/2025/01/00001",
      "tanggal_transaksi": "2025-01-23",
      "jenis_jurnal": "memorial",
      "keterangan": "Penyusutan peralatan kantor",
      "total_debit": 500000,
      "total_kredit": 500000,
      "status": "posted",
      "details": [
        {
          "akun": {
            "kode_akun": "5-5000",
            "nama_akun": "Beban Penyusutan Peralatan"
          },
          "posisi": "debit",
          "jumlah": 500000
        },
        {
          "akun": {
            "kode_akun": "1-1310",
            "nama_akun": "Akumulasi Penyusutan Peralatan"
          },
          "posisi": "kredit",
          "jumlah": 500000
        }
      ]
    }
  ]
}
```

#### **POST /api/jurnal**
Buat jurnal baru

**Request**:
```json
{
  "tanggal_transaksi": "2025-01-31",
  "unit_usaha_id": 1,
  "jenis_jurnal": "memorial",
  "keterangan": "Penyusutan peralatan kantor bulan Januari 2025",
  "status": "posted",
  "details": [
    {
      "akun_id": 38,
      "posisi": "debit",
      "jumlah": 500000,
      "keterangan": "Beban penyusutan"
    },
    {
      "akun_id": 8,
      "posisi": "kredit",
      "jumlah": 500000,
      "keterangan": "Akumulasi penyusutan"
    }
  ]
}
```

**Response**:
```json
{
  "message": "Jurnal berhasil disimpan",
  "data": {
    "id": 2,
    "nomor_jurnal": "JRN/2025/01/00002",
    "tanggal_transaksi": "2025-01-31",
    "jenis_jurnal": "memorial",
    "keterangan": "Penyusutan peralatan kantor bulan Januari 2025",
    "total_debit": 500000,
    "total_kredit": 500000,
    "status": "posted",
    "is_balanced": true
  }
}
```

#### **PUT /api/jurnal/{id}**
Update jurnal (draft only)

#### **DELETE /api/jurnal/{id}**
Hapus jurnal (draft only)

#### **POST /api/jurnal/{id}/void**
Void jurnal

---

### **5. LAPORAN**

#### **GET /api/laporan/neraca-saldo**
Neraca saldo

**Query Parameters**:
- `bulan`: 1-12 (required)
- `tahun`: YYYY (required)
- `unit_usaha_id`: integer (optional)

**Response**:
```json
{
  "periode": {
    "bulan": 1,
    "tahun": 2025,
    "bulan_nama": "Januari"
  },
  "unit_usaha": {
    "id": 1,
    "nama_unit": "Unit Simpan Pinjam"
  },
  "data": [
    {
      "kode_akun": "1-1000",
      "nama_akun": "Kas",
      "tipe_akun": "aset",
      "total_debit": 10000000,
      "total_kredit": 5000000,
      "saldo": 5000000,
      "posisi_saldo": "debit"
    }
  ],
  "summary": {
    "total_debit": 50000000,
    "total_kredit": 50000000,
    "is_balanced": true
  }
}
```

#### **GET /api/laporan/laba-rugi**
Laporan laba rugi

**Query Parameters**:
- `bulan`: 1-12 (required)
- `tahun`: YYYY (required)
- `unit_usaha_id`: integer (optional)

**Response**:
```json
{
  "periode": {
    "bulan": 1,
    "tahun": 2025,
    "bulan_nama": "Januari"
  },
  "pendapatan": {
    "total": 15000000,
    "detail": [
      {
        "kode_akun": "4-1000",
        "nama_akun": "Pendapatan Jasa Pinjaman",
        "jumlah": 12000000
      },
      {
        "kode_akun": "4-1100",
        "nama_akun": "Pendapatan Administrasi",
        "jumlah": 3000000
      }
    ]
  },
  "beban": {
    "total": 8000000,
    "detail": [
      {
        "kode_akun": "5-1000",
        "nama_akun": "Beban Gaji dan Upah",
        "jumlah": 5000000
      },
      {
        "kode_akun": "5-2000",
        "nama_akun": "Beban Listrik",
        "jumlah": 500000
      }
    ]
  },
  "laba_rugi": 7000000
}
```

#### **GET /api/laporan/neraca**
Neraca (Balance Sheet)

**Query Parameters**:
- `tanggal`: Y-m-d (required)
- `unit_usaha_id`: integer (optional)

**Response**:
```json
{
  "tanggal": "2025-01-31",
  "aset": {
    "total": 50000000,
    "detail": [
      {
        "kode_akun": "1-1000",
        "nama_akun": "Kas",
        "saldo": 5000000
      }
    ]
  },
  "kewajiban": {
    "total": 20000000,
    "detail": [
      {
        "kode_akun": "2-1000",
        "nama_akun": "Simpanan Anggota - Pokok",
        "saldo": 15000000
      }
    ]
  },
  "ekuitas": {
    "total": 30000000,
    "detail": [
      {
        "kode_akun": "3-1000",
        "nama_akun": "Modal Penyertaan Desa",
        "saldo": 25000000
      }
    ]
  },
  "is_balanced": true
}
```

---

## 📤 EXPORT ENDPOINTS

#### **GET /api/laporan/neraca-saldo/export**
Export neraca saldo ke PDF

**Query Parameters**: sama dengan GET neraca-saldo

**Response**: File PDF

#### **GET /api/laporan/laba-rugi/export**
Export laba rugi ke PDF

#### **GET /api/laporan/neraca/export**
Export neraca ke PDF

---

## ❌ ERROR RESPONSES

### **400 Bad Request**
```json
{
  "message": "Validation error",
  "errors": {
    "jumlah": ["Jumlah harus diisi"],
    "akun_kas_id": ["Akun kas harus dipilih"]
  }
}
```

### **401 Unauthorized**
```json
{
  "message": "Unauthenticated"
}
```

### **403 Forbidden**
```json
{
  "message": "Anda tidak memiliki akses ke resource ini"
}
```

### **404 Not Found**
```json
{
  "message": "Resource tidak ditemukan"
}
```

### **422 Unprocessable Entity**
```json
{
  "message": "Jurnal tidak balance. Debit: 1000000.00, Kredit: 1000001.00"
}
```

### **500 Internal Server Error**
```json
{
  "message": "Terjadi kesalahan pada server"
}
```

---

## 🔧 IMPLEMENTATION GUIDE

### **1. Install Laravel Sanctum**
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### **2. Buat API Controller**
```bash
php artisan make:controller Api/UnitUsahaController --api
php artisan make:controller Api/AkunController --api
php artisan make:controller Api/TransaksiKasController --api
php artisan make:controller Api/JurnalController --api
php artisan make:controller Api/LaporanController
```

### **3. Buat API Resource**
```bash
php artisan make:resource UnitUsahaResource
php artisan make:resource AkunResource
php artisan make:resource TransaksiKasResource
php artisan make:resource JurnalResource
```

### **4. Setup Routes**
```php
// routes/api.php
use App\Http\Controllers\Api;

Route::middleware('auth:sanctum')->group(function () {
    // Unit Usaha
    Route::apiResource('unit-usaha', Api\UnitUsahaController::class);
    
    // Akun
    Route::apiResource('akun', Api\AkunController::class);
    
    // Transaksi Kas
    Route::apiResource('transaksi-kas', Api\TransaksiKasController::class);
    
    // Jurnal
    Route::apiResource('jurnal', Api\JurnalController::class);
    Route::post('jurnal/{id}/void', [Api\JurnalController::class, 'void']);
    
    // Laporan
    Route::prefix('laporan')->group(function () {
        Route::get('neraca-saldo', [Api\LaporanController::class, 'neracaSaldo']);
        Route::get('laba-rugi', [Api\LaporanController::class, 'labaRugi']);
        Route::get('neraca', [Api\LaporanController::class, 'neraca']);
        
        // Export
        Route::get('neraca-saldo/export', [Api\LaporanController::class, 'exportNeracaSaldo']);
        Route::get('laba-rugi/export', [Api\LaporanController::class, 'exportLabaRugi']);
        Route::get('neraca/export', [Api\LaporanController::class, 'exportNeraca']);
    });
});
```

### **5. Implement Controller**
```php
// app/Http/Controllers/Api/TransaksiKasController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransaksiKasResource;
use App\Models\TransaksiKas;
use App\Services\AccountingService;
use Illuminate\Http\Request;

class TransaksiKasController extends Controller
{
    public function __construct(
        private AccountingService $accountingService
    ) {}

    public function index(Request $request)
    {
        $query = TransaksiKas::query()
            ->with(['unitUsaha', 'akunKas', 'akunLawan'])
            ->where('desa_id', $request->user()->desa_id);
        
        // Apply filters...
        
        return TransaksiKasResource::collection(
            $query->paginate(20)
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal_transaksi' => 'required|date',
            'unit_usaha_id' => 'nullable|exists:unit_usaha,id',
            'jenis_transaksi' => 'required|in:masuk,keluar',
            'akun_kas_id' => 'required|exists:akun,id',
            'akun_lawan_id' => 'required|exists:akun,id',
            'jumlah' => 'required|numeric|min:0.01',
            'uraian' => 'required|string|max:1000',
        ]);
        
        DB::transaction(function () use ($validated, $request) {
            // Create transaksi kas
            $transaksi = TransaksiKas::create([
                'desa_id' => $request->user()->desa_id,
                ...$validated
            ]);
            
            // Auto-create jurnal
            $details = $this->buildJurnalDetails($validated);
            
            $this->accountingService->createJurnal([
                'desa_id' => $request->user()->desa_id,
                'unit_usaha_id' => $validated['unit_usaha_id'],
                'tanggal_transaksi' => $validated['tanggal_transaksi'],
                'jenis_jurnal' => 'kas_harian',
                'keterangan' => $validated['uraian'],
                'status' => 'posted',
                'transaksi_kas_id' => $transaksi->id,
                'details' => $details,
            ]);
            
            return $transaksi;
        });
        
        return new TransaksiKasResource($transaksi);
    }
}
```

---

## 🧪 TESTING

### **Postman Collection**
Buat Postman collection untuk testing API:

1. **Environment Variables**:
   - `base_url`: http://localhost:8000/api
   - `token`: {your_auth_token}

2. **Test Cases**:
   - Create transaksi kas masuk
   - Create transaksi kas keluar
   - Create jurnal memorial
   - Get neraca saldo
   - Get laba rugi
   - Get neraca

---

## 📝 NOTES

- API ini menggunakan AccountingService yang sama dengan Livewire
- Semua validasi business logic ada di service layer
- API response mengikuti JSON:API specification
- Rate limiting: 60 requests per minute per user

---

**Status**: 📋 **SPECIFICATION ONLY** (Belum diimplementasi)

**© 2025 SIPKUD - Sistem Informasi Pelaporan Keuangan USP Desa**
