<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\User;
use App\Notifications\ProductStatusNotification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\Log;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * Sebelum create: auto set seller_id & status.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if ($user && $user->role === 'seller') {
            $data['seller_id'] = $user->id;
            $data['status'] = $data['status'] ?? 'pending';
        }

        return $data;
    }

    /**
     * Setelah create: kirim notifikasi ke admin/super_admin.
     */
    protected function afterCreate(): void
    {
        $product = $this->record;
        $seller = auth()->user();

        Log::info('After Create triggered', [
            'product_id' => $product->id,
            'seller_role' => $seller?->role
        ]);

        if ($seller && $seller->role === 'seller') {
            // Ambil semua admin & super_admin
            $admins = User::whereRaw("LOWER(role) IN ('admin', 'super_admin')")->get();

            Log::info('Found admins', ['count' => $admins->count()]);

            foreach ($admins as $admin) {
                try {
                    // Kirim notifikasi Laravel (disimpan di tabel notifications)
                    $admin->notify(new ProductStatusNotification(
                        status: 'pending_review',
                        message: "A new product '{$product->product_name}' has been submitted by {$seller->full_name} and is awaiting your review.",
                        productId: $product->id,
                        senderRole: $seller->role,
                    ));

                    // Kirim juga notifikasi Filament agar muncul di 🔔 UI
                    FilamentNotification::make()
                        ->title("New Product Pending Review")
                        ->body("{$seller->full_name} submitted '{$product->product_name}' for your approval.")
                        ->icon('heroicon-o-paper-airplane')
                        ->iconColor('warning')
                        ->sendToDatabase($admin);

                    Log::info('Notification sent to admin', ['admin_id' => $admin->id]);
                } catch (\Exception $e) {
                    Log::error('Notification failed', [
                        'admin_id' => $admin->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Kirim notifikasi sukses ke seller sendiri
            FilamentNotification::make()
                ->title('Product Submitted')
                ->body('Your product has been submitted successfully and is now pending review.')
                ->success()
                ->send();
        }
    }

    /**
     * Redirect setelah create.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
