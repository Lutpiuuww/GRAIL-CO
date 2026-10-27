<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderManagementController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/catalog');
Route::get('/catalog', CatalogController::class)->name('catalog');
Route::get('/catalog/{product}', [CatalogController::class, 'show'])->name('catalog.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:30,1');
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->middleware('throttle:6,1')->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->middleware('throttle:6,1')->name('profile.password');
    Route::get('/cart', [CartController::class, 'index'])->name('cart');
    Route::post('/cart/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/{product}', [CartController::class, 'update'])->withTrashed()->name('cart.update');
    Route::delete('/cart/{product}', [CartController::class, 'remove'])->withTrashed()->name('cart.remove');
    Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('/checkout/review', [CheckoutController::class, 'prepare'])->name('checkout.prepare');
    Route::get('/checkout/review', [CheckoutController::class, 'review'])->name('checkout.review');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout');
    Route::get('/checkout/success/{sale}', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/my-orders', OrderController::class)->name('orders');
    Route::get('/my-orders/{sale}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/my-orders/{sale}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/dashboard', DashboardController::class)->middleware('role:admin,staff')->name('dashboard');

    Route::middleware('role:admin,staff')->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show']);
        Route::get('/sales/updates', [SaleController::class, 'updates'])->name('sales.updates');
        Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show']);
        Route::patch('/sales/{sale}/payment', [OrderManagementController::class, 'payment'])->name('sales.payment');
        Route::patch('/sales/{sale}/fulfillment', [OrderManagementController::class, 'fulfillment'])->name('sales.fulfillment');
    });

    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::resource('categories', CategoryController::class)->except('show');
        Route::get('/products/archive', [ProductController::class, 'archive'])->name('products.archive');
        Route::patch('/products/{product}/restore', [ProductController::class, 'restore'])->withTrashed()->name('products.restore');
        Route::resource('products', ProductController::class)->except(['index', 'show']);
        Route::resource('suppliers', SupplierController::class)->except(['index', 'show']);
        Route::patch('/purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
        Route::patch('/sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');
    });
});
