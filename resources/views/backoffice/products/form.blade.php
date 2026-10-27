@extends('layouts.backoffice')
@section('title',($product->exists?'Edit':'Add').' Product — GRAIL.CO')
@section('content')
<section class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <a href="{{ route('products.index') }}" class="inline-flex min-h-11 items-center text-sm text-stone-300 hover:text-champagne-300">← Back to products</a>
    <div class="glass-panel mt-5 rounded-2xl p-5 sm:p-6">
        <p class="eyebrow">{{ $product->exists ? 'Update object' : 'New object' }}</p>
        <h1 class="mt-2 text-2xl font-semibold">{{ $product->exists ? 'Edit product' : 'Add product' }}</h1>
        <form class="mt-7 grid gap-5 sm:grid-cols-2" method="POST" enctype="multipart/form-data" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}">
            @csrf
            @if($product->exists) @method('PUT') @endif
            <label class="block text-sm">SKU<input class="field mt-2 font-mono" name="sku" value="{{ old('sku', $product->sku) }}" required>@error('sku')<span class="mt-2 block text-red-300">{{ $message }}</span>@enderror</label>
            <label class="block text-sm">Category<select class="field mt-2 cursor-pointer" name="category_id" required><option value="">Choose category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select>@error('category_id')<span class="mt-2 block text-red-300">{{ $message }}</span>@enderror</label>
            <label class="block text-sm sm:col-span-2">Product name<input class="field mt-2" name="name" value="{{ old('name', $product->name) }}" required>@error('name')<span class="mt-2 block text-red-300">{{ $message }}</span>@enderror</label>
            <label class="block text-sm sm:col-span-2">Description<textarea class="field mt-2 min-h-28" name="description">{{ old('description', $product->description) }}</textarea>@error('description')<span class="mt-2 block text-red-300">{{ $message }}</span>@enderror</label>
            <div class="grid gap-4 rounded-xl border border-white/10 bg-black/10 p-4 sm:col-span-2 sm:grid-cols-[128px_1fr] sm:items-center">
                <div class="flex aspect-[4/5] items-center justify-center overflow-hidden rounded-lg bg-obsidian-950 text-champagne-300">
                    @if($product->image_path)<img src="{{ asset($product->image_path) }}" alt="Foto produk saat ini" class="h-full w-full object-cover" data-product-image-preview>@else<span class="font-mono text-4xl" data-product-image-placeholder>G</span><img src="" alt="Pratinjau foto produk baru" class="hidden h-full w-full object-cover" data-product-image-preview>@endif
                </div>
                <div>
                    <label class="block text-sm font-medium" for="product-image">Foto produk</label>
                    <input id="product-image" class="field mt-2 cursor-pointer text-sm file:mb-2 file:block file:rounded-lg file:border-0 file:bg-champagne-400/10 file:px-3 file:py-2 file:text-champagne-300" type="file" name="image" accept="image/*" data-product-image-input>
                    <p class="mt-2 text-xs leading-5 text-stone-300">Foto dipotong otomatis ke rasio 4:5 dan diperkecil saat dipilih. Tidak ada batas dimensi minimum. Foto baru menggantikan foto unggahan sebelumnya.</p>
                    <p class="mt-2 text-xs text-champagne-300" data-product-image-status role="status" aria-live="polite"></p>
                    @error('image')<span class="mt-2 block text-sm text-red-300">{{ $message }}</span>@enderror
                    @if($product->uploaded_image_path)<label class="mt-3 flex min-h-11 cursor-pointer items-center gap-2 text-sm text-stone-300"><input type="checkbox" name="remove_image" value="1" @checked(old('remove_image'))> Hapus foto unggahan dan gunakan gambar bawaan jika tersedia</label>@endif
                </div>
            </div>
            <label class="block text-sm">Purchase price<input class="field mt-2" type="number" min="0" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}" required>@error('purchase_price')<span class="mt-2 block text-red-300">{{ $message }}</span>@enderror</label>
            <label class="block text-sm">Selling price<input class="field mt-2" type="number" min="0" name="selling_price" value="{{ old('selling_price', $product->selling_price) }}" required>@error('selling_price')<span class="mt-2 block text-red-300">{{ $message }}</span>@enderror</label>
            <div class="text-sm"><p>Stok saat ini</p><p class="field mt-2 font-mono">{{ $product->stock ?? 0 }}</p><p class="mt-2 text-xs leading-5 text-stone-300">Penerimaan stok dicatat melalui <a class="text-champagne-300 underline" href="{{ route('purchases.create') }}">Stock In</a>.</p></div>
            <label class="block text-sm">Minimum stock<input class="field mt-2" type="number" min="0" name="minimum_stock" value="{{ old('minimum_stock', $product->minimum_stock ?? 0) }}" required>@error('minimum_stock')<span class="mt-2 block text-red-300">{{ $message }}</span>@enderror</label>
            <div class="flex flex-wrap gap-3 sm:col-span-2"><button class="btn-gold min-h-11">Save product</button><a class="btn-ghost min-h-11" href="{{ route('products.index') }}">Cancel</a></div>
        </form>
    </div>
</section>
@endsection
