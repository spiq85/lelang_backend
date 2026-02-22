<?php

namespace App\Filament\Widgets;

use App\Models\AuctionBatch;
use App\Models\Category;
use App\Models\User;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = -1; 

    protected function getStats(): array
    {
        $user = auth()->user();
        $stats = [];

        // Default: Admin lihat semua
        $totalUsers      = User::count();
        $totalCategories = Category::count();
        $totalProducts = Product::count();
        $totalProductsDraft = Product::Where(['status' => 'draft'])->count();

        $batchQuery = AuctionBatch::whereIn('status', ['published', 'pending_review']);

        if ($user->role === 'seller') {
            $batchQuery->where('seller_id', $user->id);

            $myProducts = Product::where('seller_id', $user->id)->count();
            $ProductDraft = Product::where('seller_id', $user->id)->where('status', 'draft')->count();

            $stats[] = Stat::make('Draft Products', $ProductDraft)
                ->description('Produk dalam draft')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('danger');

            $stats[] = Stat::make('My Products', $myProducts)
                ->description('Produk yang saya jual')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info');
        }

        $totalPublishedBatches     = (clone $batchQuery)->where('status', 'published')->count();
        $totalPendingReviewBatches = (clone $batchQuery)->where('status', 'pending_review')->count();
        $totalBatches              = $totalPublishedBatches + $totalPendingReviewBatches;


        if ($user->role === 'admin') {
            $stats[] = Stat::make('Total Users', $totalUsers)
                ->description('Semua user di sistem')
                ->descriptionIcon('heroicon-m-users')
                ->color('success');

            $stats[] = Stat::make('Total Categories', $totalCategories)
                ->description('Kategori produk')
                ->descriptionIcon('heroicon-m-tag')
                ->color('info');
        }

        $stats[] = Stat::make('Total Products', $totalProducts)
            ->description('Semua Produk Terdaftar')
            ->descriptionIcon('heroicon-m-cube')
            ->color('success');

            $stats[] = Stat::make('Product Draft', $totalProductsDraft)
            ->description('Produk menunggu di publish')
            ->descriptionIcon('heroicon-m-document-text')
            ->color('danger');

        $stats[] = Stat::make('Auction Batches (Published)', $totalPublishedBatches)
            ->description('Sedang berlangsung / akan datang')
            ->descriptionIcon('heroicon-m-check-circle')
            ->color('success');

        $stats[] = Stat::make('Auction Batches (Pending Review)', $totalPendingReviewBatches)
            ->description('Menunggu approval admin')
            ->descriptionIcon('heroicon-m-clock')
            ->color('warning');

        $stats[] = Stat::make('Total Active Batches', $totalBatches)
            ->description('Published + Pending Review')
            ->descriptionIcon('heroicon-m-clipboard-document-list')
            ->color('primary');

        return $stats;
    }

    public static function canView(): bool
    {
        return true;
    }
}