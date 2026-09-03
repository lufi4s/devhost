<?php

use Illuminate\Support\Facades\Route;

// The frontend is a Next.js SPA served on port 3000. This file exists so the
// Laravel app has a web route group; all UI is client-side.
route::get('/', fn () => 'Developer Hosting Platform API');
