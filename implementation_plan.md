# Invoice Image Upload 
Implement a multi-file invoice image upload feature with full backend stack (migration, model, repository, service, form request, API resource, controller, routes) and a Vue frontend upload page.

---

## Proposed Changes

### 1. Database — Migration

#### [NEW] `create_invoices_table` migration

Columns:
| Column | Type | Notes |
|---|---|---|
| `id` | bigint (PK) | auto-increment |
| `original_file_name` | string | original filename from upload |
| `file_path` | string | path within `storage/app/invoices/` |
| `mime_type` | string | e.g. `image/jpeg` |
| `file_size` | unsignedInteger | bytes |
| `status` | string | default `'pending'` |
| `uploaded_at` | timestamp | nullable |
| `created_at` / `updated_at` | timestamps | |

---

### 2. Backend PHP

#### [NEW] `app/Models/Invoice.php`
Eloquent model with `$fillable`, casts (`uploaded_at` → datetime), and a `StatusEnum` for status values.

#### [NEW] `app/Enums/InvoiceStatus.php`
```
Pending, Processing, Completed, Failed
```

#### [NEW] `app/Repositories/InvoiceRepository.php`
Repository interface + concrete class:
- `create(array $data): Invoice`
- `findById(int $id): Invoice`
- `all(): Collection`

#### [NEW] `app/Services/InvoiceUploadService.php`
Service class:
- Stores file to `storage/app/invoices/{unique-name}` via `Storage::disk('local')`
- Delegates DB persistence to `InvoiceRepository`
- Returns created `Invoice` model

#### [NEW] `app/Http/Requests/StoreInvoiceRequest.php`
Validates:
- `invoices` — required array, min 1 item
- `invoices.*` — file, mimes: jpeg/jpg/png/pdf, max 10 MB

#### [NEW] `app/Http/Resources/InvoiceResource.php`
API resource wrapping all invoice fields.

#### [NEW] `app/Http/Controllers/InvoiceController.php`
- `index()` → Inertia page `Invoices/Upload`
- `store(StoreInvoiceRequest)` → loop files, call service, return `InvoiceResource::collection()`

---

### 3. Routes

#### [MODIFY] [web.php](file:///home/ali/Development/invoice-analyse-agent/routes/web.php)

Add inside the `auth + verified` group:
```php
Route::get('invoices/upload', [InvoiceController::class, 'index'])->name('invoices.upload');
Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
```

---

### 4. Frontend Vue

#### [NEW] `resources/js/pages/Invoices/Upload.vue`

A polished Inertia + Vue 3 upload page with:
- Drag-and-drop file drop zone (+ click-to-browse fallback)
- File list preview showing name, size, type with remove button
- Submit button triggers `useForm` POST to `invoices.store`
- Success state shows each uploaded invoice's status badge
- Wayfinder import for the `store` action URL

#### [NEW] Wayfinder regeneration
Run `php artisan wayfinder:generate` after controller is created.

---

### 5. Tests

#### [NEW] `tests/Feature/InvoiceUploadTest.php`
- Unauthenticated requests to `GET /invoices/upload` redirect to login
- Unauthenticated `POST /invoices` → 302
- Authenticated `GET /invoices/upload` → Inertia component rendered
- Authenticated `POST /invoices` with valid images → 201, DB has records, files stored
- `POST /invoices` with invalid file type → 422 validation error
- Multiple files in one request → separate invoice records created

---

## Open Questions

> [!IMPORTANT]
> **Repository interface or concrete class only?**
> Should `InvoiceRepository` be a plain concrete class, or should there be an interface + binding in a service provider? Using an interface is best practice (allows swapping implementations), but adds more files. Please confirm your preference.

> [!IMPORTANT]
> **Disk / visibility**
> Files will be stored on the `local` disk (not publicly accessible) since invoices may be sensitive. If you need a public URL or S3 in the future, this is easy to change. Is `local` correct for now?

> [!NOTE]
> **Accepted MIME types**
> Plan allows `image/jpeg`, `image/jpg`, `image/png`, and `application/pdf`. Should PDF be excluded (task says "image")?

---

## Verification Plan

### Automated Tests
```bash
php artisan test --compact --filter=InvoiceUpload
```

### Manual Verification
- Browse to `/invoices/upload` while logged in and upload 1–3 image files
- Confirm files appear in `storage/app/invoices/`
- Confirm rows appear in the `invoices` database table
