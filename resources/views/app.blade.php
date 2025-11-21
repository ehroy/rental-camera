<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    {{-- Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Basic Meta --}}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Security --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO --}}
    <meta name="author" content="{{ $setting->meta_author ?? 'https://github.com/ehroy' }}">
    <meta name="description" content="{{ $setting->meta_description ?? 'Default description' }}">
    <meta name="keywords" content="{{ $setting->meta_keywords ?? 'default,keywords' }}">

    {{-- OG Tags (for Social Preview) --}}
    <meta property="og:title" content="{{ $setting->name ?? config('app.name') }}">
    <meta property="og:description" content="{{ $setting->meta_description ?? '' }}">
    <meta property="og:image" content="{{ !empty($setting?->icon) ? asset('storage/' . $setting->icon) : '' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">

    {{-- Twitter Cards --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $setting->name ?? config('app.name') }}">
    <meta name="twitter:description" content="{{ $setting->meta_description ?? '' }}">
    <meta name="twitter:image" content="{{ !empty($setting?->icon) ? asset('storage/' . $setting->icon) : '' }}">

    {{-- Additional Custom Meta --}}
    {!! $setting->additional_meta ?? '' !!}

    {{-- Title --}}
    <title>{{ $setting->name ?? config('app.name') }}</title>

    {{-- Favicon --}}
    @if(!empty($setting?->icon))
        <link rel="shortcut icon" href="{{ asset('storage/' . $setting->icon) }}">
        <link rel="apple-touch-icon" href="{{ asset('storage/' . $setting->icon) }}">
    @endif

    {{-- Preload Icon Font (Material Design Icons) --}}
    <link rel="preload"
          href="https://cdn.jsdelivr.net/npm/@mdi/font/css/materialdesignicons.min.css"
          as="style"
          crossorigin="anonymous"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet"
              href="https://cdn.jsdelivr.net/npm/@mdi/font/css/materialdesignicons.min.css">
    </noscript>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
          rel="stylesheet">

    {{-- Inertia Head --}}
    @inertiaHead
</head>


<body>
    @inertia
</body>

</html>
