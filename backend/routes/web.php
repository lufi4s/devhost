<?php

use Illuminate\Support\Facades\Route;

// The frontend is a Next.js SPA served on port 3000. This file exists so the
// Laravel app has a web route group; all UI is client-side.
route::get('/', fn () => 'Developer Hosting Platform API');

// Named 'login' route so unauthenticated guest redirects (e.g. a JSON-less
// request to a protected route) resolve instead of throwing a
// RouteNotFoundException. The Next.js SPA owns /login client-side; nginx
// proxies it there.
route::get('/login', fn () => 'Developer Hosting Platform')->name('login');
