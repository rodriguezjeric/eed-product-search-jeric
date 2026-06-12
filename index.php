<?php $config = require __DIR__ . '/config.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Jeric Rodriguez | Product Search</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
  <main class="relative overflow-hidden">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-72 bg-gradient-to-b from-sky-100 via-white to-transparent"></div>
    <div class="pointer-events-none absolute -right-32 top-20 h-80 w-80 rounded-full bg-blue-100 blur-3xl"></div>
    <div class="pointer-events-none absolute -left-24 top-64 h-72 w-72 rounded-full bg-slate-200 blur-3xl"></div>

    <section class="relative mx-auto max-w-7xl px-5 py-8 md:py-12">
      <header class="mb-8 grid gap-6 lg:grid-cols-[1.1fr_.9fr] lg:items-end">
        <div>
          <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            Jeric Rodriguez (rodriguezjeric@gmail.com)
          </div>
          <h1 class="max-w-4xl text-4xl font-black tracking-tight text-slate-950 md:text-6xl">
            Product search interface for spare parts catalogues.
          </h1>
          <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-600">
            A test PHP product search sample with instant filtering, and a focused detail view connected through a server-side API layer.
          </p>
        </div>

        <div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/70 md:p-6">
          <label for="searchInput" class="mb-3 block text-sm font-bold text-slate-800">Search products</label>
          <div class="relative">
            <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z"/></svg>
            <input id="searchInput" type="search" value="<?= htmlspecialchars($config['default_query'], ENT_QUOTES) ?>" autocomplete="off" class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-4 pl-12 pr-4 text-lg text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:bg-white focus:ring-4 focus:ring-sky-100" placeholder="Try remote, pump, filter...">
          </div>
          <div class="mt-4 flex flex-wrap gap-2 text-sm">
            <button class="quick-search pill" data-query="REMOTE">Remote</button>
            <button class="quick-search pill" data-query="pump">Pump</button>
            <button class="quick-search pill" data-query="filter">Filter</button>
            <button class="quick-search pill" data-query="seal">Seal</button>
          </div>
        </div>
      </header>

      <div class="mb-5 flex flex-col gap-3 rounded-3xl border border-slate-200 bg-white/80 p-4 text-sm text-slate-600 shadow-sm md:flex-row md:items-center md:justify-between">
        <div id="statusBar"></div>
        <div class="font-medium text-slate-500">Click any product card to view more details</div>
      </div>

      <div id="productGrid" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3"></div>

      <div id="emptyState" class="hidden rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
        <h2 class="text-2xl font-bold text-slate-950">No products found</h2>
        <p class="mt-2 text-slate-500">Try another keyword or check the API connection.</p>
      </div>
    </section>
  </main>

  <div id="detailOverlay" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm">
    <article id="detailCard" class="relative max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
      <button id="closeDetail" class="absolute right-4 top-4 z-10 rounded-full bg-slate-950 px-3 py-2 text-white shadow-lg transition hover:bg-slate-700">✕</button>
      <div id="detailContent"></div>
    </article>
  </div>

  <template id="productTemplate">
    <button class="product-card group text-left rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-lg shadow-slate-200/70 transition hover:-translate-y-1 hover:border-sky-300 hover:shadow-xl">
      <div class="aspect-[4/3] overflow-hidden rounded-[1.25rem] bg-slate-100">
        <img class="product-image h-full w-full object-cover transition duration-500 group-hover:scale-105" alt="">
      </div>
      <div class="pt-4">
        <div class="flex items-start justify-between gap-3">
          <h3 class="product-name text-lg font-bold leading-snug text-slate-950"></h3>
          <span class="product-price shrink-0 rounded-full bg-slate-950 px-3 py-1 text-sm font-black text-white"></span>
        </div>
        <p class="product-meta mt-2 text-sm text-slate-500"></p>
        <div class="mt-4 flex items-center justify-between">
          <span class="product-stock rounded-full px-3 py-1 text-xs font-bold"></span>
          <span class="text-sm font-bold text-sky-700">View details →</span>
        </div>
      </div>
    </button>
  </template>

  <script src="assets/js/app.js"></script>
</body>
</html>
