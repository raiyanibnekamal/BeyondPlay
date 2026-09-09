<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\Admin\TournamentController as AdminTournamentController;
use App\Http\Controllers\Admin\MatchController as AdminMatchController;
use App\Http\Controllers\Admin\DisputeController as AdminDisputeController;
use App\Http\Controllers\ActivityFeedController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PvpChallengeController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\Admin\GameSuggestionController as AdminGameSuggestionController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\SponsorController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Admin\SponsorController as AdminSponsorController;
use App\Http\Controllers\Admin\BulkEmailController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\Admin\BlogPostController as AdminBlogPostController;
use App\Http\Controllers\Admin\GameController as AdminGameController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\UserCartController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\LiveStreamController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // ---------------- Authentication ----------------
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:20,1');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:20,1');
        Route::post('forgot-password', [PasswordResetController::class, 'forgotPassword'])->middleware('throttle:5,1');
        Route::post('reset-password', [PasswordResetController::class, 'resetPassword'])->middleware('throttle:5,1');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::post('resend-verification', [AuthController::class, 'resendVerification'])->middleware('throttle:3,1');
        });
    });

    Route::get('auth/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('api.v1.auth.verify-email');

    // ---------------- Tournaments (public) ----------------
    Route::get('tournaments', [TournamentController::class, 'index']);
    Route::get('tournaments/history', [TournamentController::class, 'history']);
    Route::get('tournaments/{slug}', [TournamentController::class, 'show']);
    Route::get('tournaments/{id}/rules', [TournamentController::class, 'rules']);
    Route::get('tournaments/{id}/bracket', [TournamentController::class, 'bracket']);
    Route::get('tournaments/{id}/matches', [TournamentController::class, 'matches']);
    Route::get('tournaments/{id}/standings', [TournamentController::class, 'standings']);
    Route::get('tournaments/{id}/predictions', [PredictionController::class, 'leaderboard']);

    // ---------------- Stats (public) ----------------
    Route::get('stats/player/{userId}', [StatsController::class, 'player']);
    Route::get('stats/head-to-head', [StatsController::class, 'headToHead']);
    Route::get('users/lookup', [UserProfileController::class, 'lookup']);

    // ---------------- Matches (public) ----------------
    Route::get('matches/{id}', [MatchController::class, 'show']);
    Route::get('matches/{id}/replay', [MatchController::class, 'replay']);

    // ---------------- Teams (public) ----------------
    Route::get('teams', [TeamController::class, 'index']);
    Route::get('teams/{slug}', [TeamController::class, 'show']);

    // ---------------- Games (public) ----------------
    Route::get('games', [GameController::class, 'index']);
    Route::get('games/{slug}', [GameController::class, 'show']);

    // ---------------- Shop (public) ----------------
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{slug}', [ProductController::class, 'show']);

    // ---------------- Banners & sponsors (public) ----------------
    Route::get('banners/active', [BannerController::class, 'active']);
    Route::get('sponsors', [SponsorController::class, 'index']);

    Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:10,1');
    Route::post('newsletter/subscribe', [NewsletterController::class, 'subscribe'])->middleware('throttle:10,1');

    Route::get('posts', [PostController::class, 'index']);
    Route::get('posts/{slug}', [PostController::class, 'show']);
    Route::get('search', [SearchController::class, 'index']);
    Route::get('streams/live', [LiveStreamController::class, 'index']);
    Route::get('payments/config', [PaymentController::class, 'config']);

    Route::get('orders/{order}/download/{product}', [OrderController::class, 'download'])
        ->middleware('signed')
        ->name('api.v1.orders.download');

    // ---------------- Authenticated user actions ----------------
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::post('payments/stripe/intent', [PaymentController::class, 'createIntent'])->middleware('throttle:15,1');
        Route::post('tournaments/{id}/register', [TournamentController::class, 'register'])->middleware('throttle:10,1');
        Route::delete('tournaments/{id}/register', [TournamentController::class, 'withdraw']);

        Route::post('matches/{id}/checkin', [MatchController::class, 'checkin']);
        Route::post('matches/{id}/dispute', [MatchController::class, 'dispute'])->middleware('throttle:10,1');

        Route::post('teams', [TeamController::class, 'store']);
        Route::put('teams/{id}', [TeamController::class, 'update']);
        Route::post('teams/{id}/invite', [TeamController::class, 'invite']);
        Route::get('teams/invites/mine', [TeamController::class, 'myInvites']);
        Route::put('teams/invites/{inviteId}/accept', [TeamController::class, 'acceptInvite']);
        Route::put('teams/invites/{inviteId}/decline', [TeamController::class, 'declineInvite']);

        Route::post('games/suggest', [GameController::class, 'suggest'])->middleware('throttle:5,1');
        Route::get('games/suggestions/mine', [GameController::class, 'mySuggestions']);

        Route::get('user/stats', [StatsController::class, 'me']);
        Route::get('user/achievements', [UserDashboardController::class, 'achievements']);
        Route::get('user/tournaments', [UserDashboardController::class, 'tournaments']);
        Route::get('user/cart', [UserCartController::class, 'show']);
        Route::put('user/cart', [UserCartController::class, 'sync']);
        Route::put('user/profile', [UserProfileController::class, 'update']);
        Route::post('user/avatar', [UserProfileController::class, 'uploadAvatar']);
        Route::post('tournaments/{id}/predict', [PredictionController::class, 'store']);

        Route::get('user/notifications', [NotificationController::class, 'index']);
        Route::put('user/notifications/read', [NotificationController::class, 'markAllRead']);
        Route::get('user/activity-feed', [ActivityFeedController::class, 'index']);

        Route::get('friends', [FriendController::class, 'index']);
        Route::get('friends/requests', [FriendController::class, 'requests']);
        Route::get('friends/requests/sent', [FriendController::class, 'sentRequests']);
        Route::get('friends/status/{userId}', [FriendController::class, 'relationship']);
        Route::post('friends/request/{userId}', [FriendController::class, 'sendRequest'])->middleware('throttle:20,1');
        Route::put('friends/request/{id}/accept', [FriendController::class, 'accept']);
        Route::put('friends/request/{id}/decline', [FriendController::class, 'decline']);
        Route::delete('friends/request/{id}/cancel', [FriendController::class, 'cancelRequest']);
        Route::delete('friends/{userId}', [FriendController::class, 'remove']);

        Route::post('challenges', [PvpChallengeController::class, 'store']);
        Route::get('challenges/incoming', [PvpChallengeController::class, 'incoming']);
        Route::get('challenges/sent', [PvpChallengeController::class, 'sent']);
        Route::get('challenges/status/{userId}', [PvpChallengeController::class, 'statusWithUser']);
        Route::put('challenges/{id}/accept', [PvpChallengeController::class, 'accept']);
        Route::put('challenges/{id}/decline', [PvpChallengeController::class, 'decline']);
        Route::delete('challenges/{id}/cancel', [PvpChallengeController::class, 'cancel']);

        Route::get('user/orders', [OrderController::class, 'index']);
        Route::post('cart/validate-coupon', [CartController::class, 'validateCoupon']);
        Route::post('orders', [OrderController::class, 'store'])->middleware('throttle:15,1');
        Route::get('orders/{id}', [OrderController::class, 'show']);

        Route::get('wishlist', [WishlistController::class, 'index']);
        Route::post('wishlist/{productId}', [WishlistController::class, 'store']);
        Route::delete('wishlist/{productId}', [WishlistController::class, 'destroy']);

        Route::post('posts/{id}/comments', [PostController::class, 'storeComment']);
    });

    // ---------------- Admin ----------------
    Route::prefix('admin')->middleware(['auth:sanctum', 'admin', 'throttle:60,1'])->group(function () {
        Route::get('tournaments', [AdminTournamentController::class, 'index']);
        Route::post('tournaments', [AdminTournamentController::class, 'store']);
        Route::put('tournaments/{tournament}', [AdminTournamentController::class, 'update']);
        Route::delete('tournaments/{tournament}', [AdminTournamentController::class, 'destroy']);
        Route::post('tournaments/{tournament}/rules', [AdminTournamentController::class, 'saveRules']);
        Route::post('tournaments/{tournament}/generate-bracket', [AdminTournamentController::class, 'generateBracket']);

        Route::get('matches', [AdminMatchController::class, 'index']);
        Route::put('matches/{match}/score', [AdminMatchController::class, 'updateScore']);
        Route::post('matches/{match}/replay', [AdminMatchController::class, 'addReplay']);

        Route::get('disputes', [AdminDisputeController::class, 'index']);
        Route::put('disputes/{dispute}/resolve', [AdminDisputeController::class, 'resolve']);

        Route::get('game-suggestions', [AdminGameSuggestionController::class, 'index']);
        Route::put('game-suggestions/{id}/approve', [AdminGameSuggestionController::class, 'approve']);
        Route::put('game-suggestions/{id}/reject', [AdminGameSuggestionController::class, 'reject']);

        Route::get('banners', [AdminBannerController::class, 'index']);
        Route::post('banners', [AdminBannerController::class, 'store']);
        Route::put('banners/{id}', [AdminBannerController::class, 'update']);
        Route::delete('banners/{id}', [AdminBannerController::class, 'destroy']);

        Route::get('sponsors', [AdminSponsorController::class, 'index']);
        Route::post('sponsors', [AdminSponsorController::class, 'store']);
        Route::put('sponsors/{id}', [AdminSponsorController::class, 'update']);
        Route::delete('sponsors/{id}', [AdminSponsorController::class, 'destroy']);

        Route::post('bulk-email', [BulkEmailController::class, 'send'])->middleware('throttle:3,1');

        Route::get('analytics/overview', [AnalyticsController::class, 'overview']);
        Route::get('analytics/tournaments', [AnalyticsController::class, 'tournaments']);

        Route::get('products', [AdminProductController::class, 'index']);
        Route::post('products', [AdminProductController::class, 'store']);
        Route::put('products/{id}', [AdminProductController::class, 'update']);
        Route::delete('products/{id}', [AdminProductController::class, 'destroy']);

        Route::get('orders', [AdminOrderController::class, 'index']);
        Route::put('orders/{id}/status', [AdminOrderController::class, 'updateStatus']);

        Route::get('users', [AdminUserController::class, 'index']);
        Route::put('users/{id}/status', [AdminUserController::class, 'updateStatus']);

        Route::get('coupons', [AdminCouponController::class, 'index']);
        Route::post('coupons', [AdminCouponController::class, 'store']);
        Route::put('coupons/{id}', [AdminCouponController::class, 'update']);
        Route::delete('coupons/{id}', [AdminCouponController::class, 'destroy']);

        Route::get('contact-messages', [AdminContactMessageController::class, 'index']);
        Route::put('contact-messages/{id}/read', [AdminContactMessageController::class, 'markRead']);

        Route::get('settings', [AdminSettingsController::class, 'show']);
        Route::put('settings', [AdminSettingsController::class, 'update']);

        Route::get('stats', [AdminNotificationController::class, 'stats']);
        Route::post('notifications/send', [AdminNotificationController::class, 'send']);
        Route::put('comments/{id}/approve', [AdminNotificationController::class, 'approveComment']);
        Route::delete('comments/{id}', [AdminNotificationController::class, 'deleteComment']);

        Route::get('games', [AdminGameController::class, 'index']);
        Route::post('games', [AdminGameController::class, 'store']);
        Route::put('games/{id}', [AdminGameController::class, 'update']);
        Route::delete('games/{id}', [AdminGameController::class, 'destroy']);

        Route::get('posts', [AdminBlogPostController::class, 'index']);
        Route::post('posts', [AdminBlogPostController::class, 'store']);
        Route::put('posts/{id}', [AdminBlogPostController::class, 'update']);
        Route::put('posts/{id}/publish', [AdminBlogPostController::class, 'publish']);
        Route::delete('posts/{id}', [AdminBlogPostController::class, 'destroy']);

        Route::put('users/{id}/promote', [AdminUserController::class, 'promote']);
    });
});
