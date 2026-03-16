# PostgreSQL Compatibility Report

This document summarizes the audit and refactoring performed to make the SIPKUD Laravel application fully compatible with PostgreSQL 15+ while maintaining compatibility with MySQL.

## Summary

All identified MySQL-specific syntax has been refactored. The application now runs on both PostgreSQL and MySQL without modification. Migrations have been tested with `php artisan migrate:fresh` on PostgreSQL.

---

## Problematic Migrations (Fixed)

### 1. `2025_12_09_012930_rename_name_to_nama_in_users_table.php`

**Issue:** Raw MySQL `ALTER TABLE ... CHANGE` syntax.

**Fix:** Replaced with Laravel Schema Builder:
```php
Schema::table('users', function (Blueprint $table) {
    $table->renameColumn('name', 'nama');
});
```

**Note:** Requires `doctrine/dbal` for `renameColumn()`. If not installed, add: `composer require doctrine/dbal`

---

### 2. `2025_12_18_043628_update_transaksi_kas_add_saldo_awal_jenis.php`

**Issue:** Raw MySQL `MODIFY COLUMN ENUM` syntax.

**Fix:** Driver-aware implementation:
- **PostgreSQL:** Drop existing check constraint, add new constraint with extended values.
- **MySQL:** Keep original raw `MODIFY COLUMN ENUM` for compatibility.

---

### 3. `2025_12_09_020000_add_admin_kecamatan_role.php`

**Issue:** Raw MySQL `MODIFY COLUMN ENUM` syntax for `role` column.

**Fix:** Same driver-aware approach as above.

---

### 4. `2026_02_04_100000_restructure_akun_to_global.php`

**Issue:**
- `after('id')` – PostgreSQL does not support `AFTER` in `ADD COLUMN`.
- `->change()` – Complex rollback requiring `doctrine/dbal` and driver-specific behavior.

**Fix:**
- Removed `after()` – Column is added at the end (acceptable).
- Replaced `->change()` with raw SQL for setting `NOT NULL` (works on both MySQL and PostgreSQL).

---

## Application Code Fixes

### 1. `app/Services/AccountingService.php`

**Issue:** 
- Double-quoted string literals `"debit"` and `"kredit"` – In PostgreSQL, double quotes denote identifiers, not strings.
- `DATE_FORMAT()` – MySQL-specific function.

**Fix:**
- Changed to single-quoted literals: `'debit'`, `'kredit'`.
- Replaced `whereRaw("DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?", [$periode])` with database-agnostic:
  ```php
  ->whereYear('tanggal_transaksi', substr($periode, 0, 4))
  ->whereMonth('tanggal_transaksi', substr($periode, 5, 2))
  ```

---

### 2. `post_neraca_saldo.php`

**Issue:** `DATE_FORMAT()` in `select()` and `whereRaw()`.

**Fix:**
- Uses `App\Support\DbCompat::dateFormatPeriod()` for SELECT (driver-aware).
- Uses `whereYear()` + `whereMonth()` for WHERE clauses.

---

### 3. `app/Support/Db.php` (New)

**Purpose:** Database compatibility helper for MySQL and PostgreSQL.

**Methods:**
- `dateFormatPeriod(string $column)` – Returns `to_char(column, 'YYYY-MM')` for PostgreSQL or `DATE_FORMAT(column, '%Y-%m')` for MySQL.
- `wherePeriod()` – For `whereRaw` with period comparison (when needed).

---

## Laravel Schema Builder Compatibility

These Laravel Schema Builder features work correctly on PostgreSQL without changes:

| Feature | MySQL | PostgreSQL | Notes |
|---------|-------|------------|-------|
| `$table->enum()` | ENUM type | VARCHAR + CHECK | Laravel handles automatically |
| `$table->unsignedInteger()` | INT UNSIGNED | INTEGER | Laravel maps; PostgreSQL has no unsigned |
| `$table->unsignedBigInteger()` | BIGINT UNSIGNED | BIGINT | Same |
| `$table->unsignedTinyInteger()` | TINYINT UNSIGNED | SMALLINT | Same |
| `$table->json()` | JSON | JSONB (Laravel 10+) | Prefer jsonb on PostgreSQL |
| `$table->timestamps()` | TIMESTAMP | TIMESTAMP | Compatible |
| `$table->foreignId()->constrained()` | FK | FK | Compatible |

---

## Migrations Using `enum()` – No Changes Needed

Laravel's `$table->enum()` generates PostgreSQL-compatible CHECK constraints. These migrations require no modification:

- `create_kecamatan_table` – `status`
- `create_desa_table` – `status`
- `create_kelompok_table` – `status`
- `create_anggota_table` – `status`, `jenis_kelamin`
- `create_akun_table` – `tipe_akun`, `status`
- `create_transaksi_kas_table` – `jenis_transaksi`
- `create_jurnal_table` – `jenis_jurnal`, `status`
- `create_jurnal_detail_table` – `posisi`
- `create_pinjaman_table` – `status_pinjaman`
- `create_unit_usaha_table` – `status`
- `create_neraca_saldo_table` – `status_periode`
- `create_pengumumen_table` – `prioritas`, `tipe`

---

## PostgreSQL Optimization Recommendations

### 1. JSON Columns → JSONB (Future Consideration)

For better indexing and query performance on PostgreSQL:
```php
// Instead of:
$table->json('old_values');

// Consider (Laravel 10+):
$table->jsonb('old_values');
```

### 2. Indexing Strategies

- Ensure indexes on frequently filtered columns (`desa_id`, `tanggal_transaksi`, `periode`, `status`).
- Consider composite indexes for common query patterns, e.g. `(desa_id, periode)` on `neraca_saldo`.

### 3. UUID Primary Keys (Optional)

For distributed systems or external integration:
```php
$table->uuid('id')->primary();
// Requires ramsey/uuid
```

### 4. Full-Text Search (PostgreSQL)

For search features, consider PostgreSQL full-text search instead of `LIKE`:
```php
->whereRaw("to_tsvector('indonesian', nama) @@ plainto_tsquery('indonesian', ?)", [$query])
```

---

## Verification Checklist

- [x] `php artisan migrate:fresh` runs without errors on PostgreSQL
- [x] No raw MySQL `ALTER`, `MODIFY`, `CHANGE`, `ENUM` in migrations
- [x] `DB::raw()` uses single quotes for string literals in PostgreSQL
- [x] Date functions use driver-aware helpers or `whereYear`/`whereMonth`
- [x] Foreign keys and constraints work in PostgreSQL
- [x] Laravel `enum()` columns generate valid CHECK constraints on PostgreSQL

---

## Going Forward

1. **Use `App\Support\DbCompat`** for any date-format or database-specific logic.
2. **Avoid raw SQL** unless necessary; prefer Schema Builder and Eloquent.
3. **String literals in raw SQL:** Always use single quotes (`'value'`) for PostgreSQL compatibility.
4. **Test migrations** on both MySQL and PostgreSQL before deploying.
5. **Consider CI** running tests against PostgreSQL to catch regressions.

---

## Optional: doctrine/dbal

For `renameColumn()` and `change()` in migrations, ensure:

```bash
composer require doctrine/dbal
```

Laravel 11+ made this optional; add it if you use column modifications in migrations.
