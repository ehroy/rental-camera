<?xml version="1.0" encoding="UTF-8"?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

<url>
  <loc>{{ url('/') }}</loc>
  <priority>1.00</priority>
</url>

@foreach ($products as $product)
<url>
  <loc>{{ url('/product/'.$product->slug) }}</loc>
  <lastmod>{{ $product->updated_at->toDateString() }}</lastmod>
  <priority>0.80</priority>
</url>
@endforeach

</urlset>
