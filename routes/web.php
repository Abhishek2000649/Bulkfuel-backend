<?php


Route::get('/', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Backend running'
    ]);
});


// use Illuminate\Support\Facades\Route;

// use App\Http\Controllers\AuthController;
// use App\Http\Controllers\UserController;
// use App\Http\Controllers\CartController;
// use App\Http\Controllers\OrderController;
// use App\Http\Controllers\AdminController;
// use App\Http\Controllers\admin\AdminOrderController;
// use App\Http\Controllers\Admin\AdminUserController;
// use App\Http\Controllers\Admin\CategoryController;
// use App\Http\Controllers\Admin\StockController;
// use App\Http\Controllers\Admin\WarehouseController;

// use App\Http\Controllers\Delivery\DeliveryDashboardController;


// Route::middleware('guest')->group(function () {
// Route::get('/login', [AuthController::class, 'login'])->name('login');
// Route::get('/signup', [AuthController::class, 'signup']);
// Route::post('/login', [AuthController::class, 'doLogin']);
// Route::post('/register', [AuthController::class, 'register']);


// });
// Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
// /*
// |--------------------------------------------------------------------------
// | USER ROUTES (ROLE = USER)
// |--------------------------------------------------------------------------
// */
// Route::get('/', [UserController::class, 'home'])
//             ->name('user.dashboard');
// Route::middleware(['auth', 'user'])
//     ->prefix('user')
//     ->group(function () {

//         // Route::get('/', [UserController::class, 'home'])
//         //     ->name('user.dashboard');

//         Route::post('/order', [UserController::class, 'placeOrder'])
//             ->name('user.order');

//         Route::get('/my-orders', [UserController::class, 'orders'])
//             ->name('user.my-orders');

//         Route::get('/profile', [UserController::class, 'profile'])
//             ->name('user.profile');

//         Route::post('/profile', [UserController::class, 'saveProfile']);

//         /* CART */
//         Route::post('/add-to-cart/{id}', [CartController::class, 'add'])
//             ->name('addToCart');

//         Route::get('/cart', [CartController::class, 'index'])
//             ->name('user.cart');

//         Route::post('/cart/update/{id}', [CartController::class, 'update'])
//             ->name('user.cart.update');

//         Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])
//             ->name('user.cart.remove');

//         /* CHECKOUT */
//         Route::get('/checkout', [OrderController::class, 'checkout'])
//             ->name('user.checkout');

//         Route::post('/place-order', [OrderController::class, 'placeOrder'])
//             ->name('user.place.order');
//     });



// Route::middleware(['auth', 'admin'])
//     ->prefix('admin')
//     ->group(function () {

//         Route::get('/', [AdminController::class, 'dashboard'])
//             ->name('admin.dashboard');

        
//         Route::prefix('products')->group(function () {
//             Route::get('/', [AdminController::class, 'products'])->name('admin.products');
//             Route::get('/add', [AdminController::class, 'addProduct'])->name('admin.products.add');
//             Route::post('/store', [AdminController::class, 'store'])->name('admin.products.store');
//             Route::get('/edit/{id}', [AdminController::class, 'edit'])->name('admin.products.edit');
//             Route::put('/update/{id}', [AdminController::class, 'update'])->name('admin.products.update');
//             Route::delete('/delete/{id}', [AdminController::class, 'delete'])->name('admin.products.delete');
//         });

        
//         Route::prefix('users')->group(function () {
//             Route::get('/', [AdminUserController::class, 'index'])->name('admin.users');
//             Route::get('/add', [AdminUserController::class, 'add'])->name('admin.users.add');
//             Route::post('/store', [AdminUserController::class, 'store'])->name('admin.users.store');
//             Route::get('/edit/{id}', [AdminUserController::class, 'edit'])->name('admin.users.edit');
//             Route::post('/update/{id}', [AdminUserController::class, 'update'])->name('admin.users.update');
//             Route::get('/delete/{id}', [AdminUserController::class, 'destroy'])->name('admin.users.delete');
//         });

        
//         Route::get('/orders', [AdminOrderController::class, 'index'])
//             ->name('admin.orders');

//         Route::post('/orders/{id}/status', [AdminOrderController::class, 'updateStatus'])
//             ->name('admin.orders.status');

        
//         Route::get('/warehouses', [WarehouseController::class, 'index'])->name('admin.warehouse.index');
//         Route::get('/warehouses/create', [WarehouseController::class, 'create'])->name('admin.warehouse.create');
//         Route::get('/warehouses/edit/{id}', [WarehouseController::class, 'edit'])->name('admin.warehouse.edit');
//         Route::post('/warehouses/store', [WarehouseController::class, 'store'])->name('admin.warehouse.store');
//         Route::post('/warehouses/update/{id}', [WarehouseController::class, 'update'])->name('admin.warehouse.update');
//         Route::get('/warehouses/delete/{id}', [WarehouseController::class, 'delete'])->name('admin.warehouse.delete');

        
//         Route::get('/stock', [StockController::class, 'index'])->name('admin.stock.index');
//         Route::get('/stock/create', [StockController::class, 'create'])->name('admin.stock.create');
//         Route::get('/stock/edit/{id}', [StockController::class, 'edit'])->name('admin.stock.edit');
//         Route::put('/stock/update/{id}', [StockController::class, 'update'])->name('admin.stock.update');
//         Route::post('/stock/store', [StockController::class, 'store'])->name('admin.stock.store');
//         Route::post('/stock/delete/{id}', [StockController::class, 'delete'])->name('admin.stock.delete');

        
//         Route::get('/categories', [CategoryController::class, 'index'])->name('admin.categories');
//         Route::get('/category/create', [CategoryController::class, 'create'])->name('admin.category.create');
//         Route::get('/category/edit/{id}', [CategoryController::class, 'edit'])->name('admin.category.edit');
//         Route::post('/category/update/{id}', [CategoryController::class, 'update'])->name('admin.category.update');
//         Route::post('/categories', [CategoryController::class, 'store'])->name('admin.category.store');
//         Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('admin.category.delete');
//     });



// Route::middleware(['auth', 'delivery_agent'])
//     ->prefix('delivery')
//     ->group(function () {

//         Route::get('/dashboard', [DeliveryDashboardController::class, 'dashboard'])
//             ->name('delivery.dashboard');

//         Route::get('/available', [DeliveryDashboardController::class, 'availableOrders'])
//             ->name('delivery.available');

//         Route::post('/accept/{id}', [DeliveryDashboardController::class, 'accept']);
//         Route::post('/reject/{id}', [DeliveryDashboardController::class, 'reject']);
//         Route::post('/delivered/{id}', [DeliveryDashboardController::class, 'delivered']);
//         Route::post('/cancel/{id}', [DeliveryDashboardController::class, 'cancel']);
//     });
