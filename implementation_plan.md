# Invoice List Endpoint

Implement a page and endpoint to list uploaded invoices with search, filtering, sorting, and pagination.

---

## Proposed Changes

### 1. Routes & Controller Structure

#### [MODIFY] `routes/web.php`
- Change `Route::get('invoices/upload', [InvoiceController::class, 'index'])` to use the `create` method: `[InvoiceController::class, 'create']`.
- Add a new route `Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');`

#### [MODIFY] `app/Http/Controllers/InvoiceController.php`
- Change the current `index` method to `create`.
- Add a new `index(Request $request)` method:
  - Extract search term (`search`), status filter (`status`), sort field (`sort` defaulting to `uploaded_at`), sort direction (`direction` defaulting to `desc`).
  - Query the `Invoice` model:
    - Apply `where('original_file_name', 'like', "%{$search}%")` if search is present.
    - Apply `where('status', $status)` if status is present.
    - Apply `orderBy($sort, $direction)`.
    - Paginate the results (e.g., 10 per page) while preserving query string parameters `withQueryString()`.
  - Return `Inertia::render('Invoices/Index', ['invoices' => InvoiceResource::collection($paginatedInvoices), 'filters' => $request->only(['search', 'status', 'sort', 'direction'])])`.

### 2. Frontend Vue Component

#### [NEW] `resources/js/pages/Invoices/Index.vue`
- Create an Inertia page to display the list of invoices.
- **Search & Filters**: 
  - Text input bound to `search` query parameter.
  - Select dropdown for `status` filtering (Pending, Processing, Completed, Failed).
- **Data Table**:
  - Columns: Invoice Thumb, Name (`original_file_name`), Size (`file_size`), Uploaded At (`uploaded_at`), Status Badge (`status`).
  - Column headers for Name, Size, and Uploaded At will be clickable to toggle sorting (`asc`/`desc`).
  - Invoice Thumb will display an image tag using `invoice.file_url`.
- **Status Badge**:
  - Colored badges based on status (e.g., green for Completed, gray for Pending, red for Failed).
- **Pagination**:
  - Use Inertia links to navigate through `invoices.links`.

#### [MODIFY] `resources/js/components/AppSidebar.vue`
- Add a navigation link to `invoices.index` using the `wayfinder` helper or Inertia `<Link>`.

### 3. Tests

#### [NEW] `tests/Feature/InvoiceListTest.php`
- Verify `GET /invoices` requires authentication.
- Verify the `invoices.index` page renders successfully with invoices.
- Test sorting by name, size, and uploaded at.
- Test searching by name.
- Test filtering by status.

---

## Open Questions

> [!IMPORTANT]
> **Invoice Thumbnails**
> Currently, invoices are uploaded as images (jpeg, png) or pdfs. The database contains a `file_path`. Using `file_url` as an image source works for images, but for PDFs, we can't easily display a thumbnail without generating one on the backend (e.g., using Imagick/Spatie Media Library). 
> For Phase One, should we display a generic PDF icon for PDF files, or do you want to implement PDF thumbnail generation?

> [!IMPORTANT]
> **Default Sorting**
> I plan to sort by `uploaded_at` descending by default so the newest uploads are at the top. Is this acceptable?

---

## Verification Plan

### Automated Tests
Run tests to verify correct sorting, searching, and filtering.
```bash
php artisan test --compact --filter=InvoiceList
```

### Manual Verification
- Navigate to `/invoices` in the browser.
- Verify the table loads properly with thumbnails.
- Test the search bar to ensure it filters by filename.
- Select a status from the dropdown to ensure it filters by status.
- Click the table headers (Name, Size, Uploaded At) to verify sorting works.
- Navigate to page 2 using the pagination links.
