<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = ['category_id', 'sku', 'name', 'description', 'purchase_price', 'selling_price', 'stock', 'minimum_stock', 'uploaded_image_path'];

    protected function casts(): array
    {
        return ['purchase_price' => 'decimal:2', 'selling_price' => 'decimal:2'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock <= $this->minimum_stock;
    }

    public function getImagePathAttribute(): ?string
    {
        if ($this->uploaded_image_path) {
            return 'images/products/uploads/'.$this->uploaded_image_path;
        }

        $path = 'images/products/'.strtolower($this->sku).'.webp';

        return file_exists(public_path($path)) ? $path : null;
    }
}
