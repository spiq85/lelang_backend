<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        // Jika yang login adalah seller, set otomatis
        if ($user && $user->role === 'seller') {
            $data['seller_id'] = $user->id;
        }

        // Jika admin/super_admin, seller_id akan ikut dari form (karena visible)
        return $data;
    }
}