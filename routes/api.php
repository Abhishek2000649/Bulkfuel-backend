<?php
use App\Http\Controllers\Admin\SettlementController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Delivery\DeliveryDashboardController;
Route::post('/login', [AuthController::class, 'doLogin']);
Route::post('/register', [AuthController::class, 'register']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/profile', [UserController::class, 'profile'])
            ->name('user.profile');
    // Route::post('/profile', [UserController::class, 'saveProfile']);

      Route::post('/profile/basic', [UserController::class, 'updateBasic']);
    Route::post('/profile/address', [UserController::class, 'updateAddress']);
    Route::post('/profile/password', [UserController::class, 'updatePassword']);

});

// Route::middleware(['auth:sanctum'])->get('user/', [UserController::class, 'home'])
//     ->name('user.dashboard');
Route::get('user/', [UserController::class, 'home']);


Route::middleware(['auth:sanctum', 'role:USER'])
    ->prefix('user')
    ->group(function () {

        Route::post('/order', [UserController::class, 'placeOrder'])
            ->name('user.order');
        
        // Route::get('/', [UserController::class, 'home'])
        //     ->name('user.dashboard');

        Route::get('/my-orders', [UserController::class, 'orders'])
            ->name('user.my-orders');

        // Route::get('/profile', [UserController::class, 'profile'])
        //     ->name('user.profile');

        // Route::post('/profile', [UserController::class, 'saveProfile']);

        /* CART */
        Route::post('/add-to-cart/{id}', [CartController::class, 'add'])
            ->name('addToCart');

        Route::get('/cart', [CartController::class, 'index'])
            ->name('user.cart');

        Route::post('/cart/update/{id}', [CartController::class, 'update'])
            ->name('user.cart.update');

        Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])
            ->name('user.cart.remove');

        /* CHECKOUT */
        Route::post('/checkout', [OrderController::class, 'checkout'])
            ->name('user.checkout');

        Route::post('/place-order', [OrderController::class, 'placeOrder'])
            ->name('user.place.order');
    });

Route::middleware(['auth:sanctum', 'role:ADMIN'])
    ->prefix('admin')
    ->group(function () {

        Route::get('/', [AdminController::class, 'dashboard'])
            ->name('admin.dashboard');

        /* PRODUCTS */
        Route::prefix('products')->group(function () {

            Route::get('/', [AdminController::class, 'products'])
                ->name('admin.products');

            Route::post('/store', [AdminController::class, 'store'])
                ->name('admin.products.store');

            Route::get('/edit/{id}', [AdminController::class, 'edit'])
                ->name('admin.products.edit');

            Route::put('/update/{id}', [AdminController::class, 'update'])
                ->name('admin.products.update');

            Route::delete('/delete/{id}', [AdminController::class, 'delete'])
                ->name('admin.products.delete');
        });

           

        // 1️⃣ Get all delivery agents
        Route::get('/delivery-agents', [SettlementController::class, 'deliveryAgents']);

        // 2️⃣ Get pending settlement by agent
        Route::get('/settlement/pending/{agentId}', [SettlementController::class, 'pendingSettlement']);

        // 3️⃣ Complete settlement (ADMIN action)
        Route::post('/settlement/complete', [SettlementController::class, 'completeSettlement']);


        /* USERS */
        Route::prefix('users')->group(function () {

            Route::get('/', [AdminUserController::class, 'index'])
                ->name('admin.users');

            Route::post('/store', [AdminUserController::class, 'store'])
                ->name('admin.users.store');

            Route::get('/edit/{id}', [AdminUserController::class, 'edit'])
                ->name('admin.users.edit');

            Route::post('/update/{id}', [AdminUserController::class, 'update'])
                ->name('admin.users.update');

            Route::delete('/delete/{id}', [AdminUserController::class, 'destroy'])
                ->name('admin.users.delete');
        });

        /* ORDERS */
        Route::get('/orders', [AdminOrderController::class, 'index'])
            ->name('admin.orders');
         Route::get('/orders/history', [AdminOrderController::class, 'history']);

        Route::post('/orders/{id}/status', [AdminOrderController::class, 'updateStatus'])
            ->name('admin.orders.status');

        /* WAREHOUSES */
        Route::get('/warehouses', [WarehouseController::class, 'index'])
            ->name('admin.warehouse.index');

        Route::post('/warehouses/store', [WarehouseController::class, 'store'])
            ->name('admin.warehouse.store');

        Route::get('/warehouses/{id}', [WarehouseController::class, 'edit'])
            ->name('admin.warehouse.edit');

        Route::post('/warehouses/update/{id}', [WarehouseController::class, 'update'])
            ->name('admin.warehouse.update');

        Route::delete('/warehouses/delete/{id}', [WarehouseController::class, 'delete'])
            ->name('admin.warehouse.delete');

        /* STOCK */
        Route::get('/stock', [StockController::class, 'index'])
            ->name('admin.stock.index');

        Route::post('/stock/store', [StockController::class, 'store'])
            ->name('admin.stock.store');

        Route::get('/stock/{id}', [StockController::class, 'edit'])
            ->name('admin.stock.edit');

        Route::put('/stock/update/{id}', [StockController::class, 'update'])
            ->name('admin.stock.update');

        Route::post('/stock/delete/{id}', [StockController::class, 'delete'])
            ->name('admin.stock.delete');

        /* CATEGORIES */
        Route::get('/categories', [CategoryController::class, 'index'])
            ->name('admin.categories');

        Route::post('/categories', [CategoryController::class, 'store'])
            ->name('admin.category.store');
        Route::get('/categories/{id}', [CategoryController::class, 'edit'])
            ->name('admin.categories.edit');


        Route::post('/category/update/{id}', [CategoryController::class, 'update'])
            ->name('admin.category.update');

        Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])
            ->name('admin.category.delete');
    });

Route::middleware(['auth:sanctum', 'role:delivery_agent'])
    ->prefix('delivery')
    ->group(function () {

        Route::get('/dashboard', [DeliveryDashboardController::class, 'dashboard'])
            ->name('delivery.dashboard');

        Route::get('/available', [DeliveryDashboardController::class, 'availableOrders'])
            ->name('delivery.available');

        Route::post('/accept/{id}', [DeliveryDashboardController::class, 'accept']);
        Route::post('/reject/{id}', [DeliveryDashboardController::class, 'reject']);
        Route::post('/delivered/{id}', [DeliveryDashboardController::class, 'delivered']);
        Route::post('/cancel/{id}', [DeliveryDashboardController::class, 'cancel']);
          Route::get('/history', [DeliveryDashboardController::class, 'history']);
    });
