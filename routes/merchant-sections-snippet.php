// Add this inside the existing
`Route::prefix('merchant')->middleware(['auth','merchant'])->name('merchant.')->group(function () { ... })`
// block in routes/web.php, right after the Products resource routes.

// Storefront Sections


// Note: sections/{section}/... routes are placed above sections/reorder is fine since
// Laravel matches literal segments before route-model-bound ones only if declared first —
// keep the `reorder` POST route registered AFTER the resource-style routes above, as shown,
// so `/merchant/sections/reorder` isn't swallowed by `sections/{section}`. Since reorder
// is POST-only and {section} routes needing POST all require an ID segment first
// (sections/{section}), there's no actual collision here — just keeping this note for
// awareness if you later add a GET version of reorder.