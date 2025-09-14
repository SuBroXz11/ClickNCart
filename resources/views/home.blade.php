@extends('layouts.app')

@section('content')
    {{-- DaisyUI Carousel using Tailwind classes --}}
    <div class="carousel w-full" id="customCarousel">
  <div id="slide1" class="carousel-item relative w-full">
    <img
      src="{{ asset('images/banner.png') }}"
      class="w-full" />
    <div class="absolute left-5 right-5 top-1/2 flex -translate-y-1/2 transform justify-between">
      <a href="#slide4" class="btn btn-circle">❮</a>
      <a href="#slide2" class="btn btn-circle">❯</a>
    </div>
  </div>
  <div id="slide2" class="carousel-item relative w-full">
    <img
      src="{{ asset('images/banner.png') }}"
      class="w-full" />
    <div class="absolute left-5 right-5 top-1/2 flex -translate-y-1/2 transform justify-between">
      <a href="#slide1" class="btn btn-circle">❮</a>
      <a href="#slide3" class="btn btn-circle">❯</a>
    </div>
  </div>
  <div id="slide3" class="carousel-item relative w-full">
    <img
      src="{{ asset('images/banner.png') }}"
      class="w-full" />
    <div class="absolute left-5 right-5 top-1/2 flex -translate-y-1/2 transform justify-between">
      <a href="#slide2" class="btn btn-circle">❮</a>
      <a href="#slide4" class="btn btn-circle">❯</a>
    </div>
  </div>
  <div id="slide4" class="carousel-item relative w-full">
    <img
      src="{{ asset('images/banner.png') }}"
      class="w-full" />
    <div class="absolute left-5 right-5 top-1/2 flex -translate-y-1/2 transform justify-between">
      <a href="#slide3" class="btn btn-circle">❮</a>
      <a href="#slide1" class="btn btn-circle">❯</a>
    </div>
  </div>
</div>

{{-- All Categories Section --}}
<section class="py-10 px-2 bg-base-100">
    <h2 class="text-2xl font-bold mb-6 text-center">All Categories</h2>

    {{-- Responsive Carousel --}}
    <div class="carousel carousel-center p-4 space-x-4 rounded-box max-w-7xl mx-auto">
        @foreach ($categories as $category)
            <div class="carousel-item">
                <div class="card w-56 bg-base-100 shadow-md hover:shadow-xl transition border">
                    <figure>
                        <img src="{{ $category->image }}" alt="{{ $category->name }}"
                             class="w-full h-40 object-cover" />
                    </figure>
                    <div class="card-body items-center text-center">
                        <h3 class="font-semibold text-sm text-primary">{{ $category->name }}</h3>
                        <p class="text-xs text-gray-500 mb-2">Explore a variety of items</p>
                        <div class="card-actions">
                            <a href="/category/{{ $category->slug }}" class="btn btn-sm btn-outline btn-primary">See More</a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- View All Button --}}
    <div class="flex justify-center mt-6">
        <a href="/categories" class="btn btn-primary btn-md px-8">View All Categories</a>
    </div>
</section>



    {{-- Latest Products --}}
    <h2 class="text-2xl font-bold mb-6 mt-4">Latest Products</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @foreach ($products as $product)
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition">
                <a href="/product/{{ $product->id }}">
                    <figure><img src="{{ $product->image }}" alt="{{ $product->name }}" class="h-48 w-full object-cover" /></figure>
                </a>
                <div class="card-body text-center p-4">
                    <a href="/product/{{ $product->id }}" class="hover:text-primary">
                        <h6 class="text-lg font-semibold">{{ $product->name }}</h6>
                    </a>
                    <div class="text-yellow-500 text-sm">
                        ★ {{ $product->rating }}
                        <span class="text-gray-500 text-xs">({{ $product->num_reviews }} reviews)</span>
                    </div>
                    <div class="text-success font-bold text-xl">${{ $product->price }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="flex justify-end mt-8 space-x-2">
        @for ($i = 1; $i <= $pages; $i++)
            <a href="?page={{ $i }}" class="btn btn-sm {{ $i == $currentPage ? 'btn-primary' : 'btn-outline' }}">
                {{ $i }}
            </a>
        @endfor
    </div>
@endsection
