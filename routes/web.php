<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Laravel is now exclusively a REST API backend.
// All API routes are defined in routes/api.php under the /api/v0 prefix.

Route::get('/health', function () {
    return 'OK';
});
