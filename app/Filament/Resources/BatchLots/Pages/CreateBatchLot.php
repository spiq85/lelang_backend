<?php

namespace App\Filament\Resources\BatchLots\Pages;

use App\Filament\Resources\BatchLots\BatchLotResource;
use App\Models\BatchLot;
use Filament\Resources\Pages\CreateRecord;

class CreateBatchLot extends CreateRecord
{
    protected static string $resource = BatchLotResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $productsData = $data['lot_products'] ?? [];
        
        // Ambil product pertama sebagai main record
        if (!empty($productsData)) {
            $firstProduct = $productsData[0];
            $data['product_id'] = $firstProduct['product_id'];
            $data['starting_price'] = $firstProduct['starting_price'];
            $data['reserve_price'] = $firstProduct['reserve_price'] ?? null;
        }
        
        // Simpan untuk diproses setelah create
        $this->cachedProducts = $productsData;
        
        // Hapus lot_products dari data
        unset($data['lot_products']);
        
        return $data;
    }

    protected array $cachedProducts = [];

    protected function afterCreate(): void
    {
        $record = $this->record;
        $productsData = $this->cachedProducts;
        
        // Jika ada lebih dari 1 product, buat BatchLot baru dengan lot_number yang increment
        if (count($productsData) > 1) {
            $baseLotNumber = $record->lot_number;
            
            for ($i = 1; $i < count($productsData); $i++) {
                $productData = $productsData[$i];
                
                // Auto-increment lot number untuk setiap product tambahan
                $newLotNumber = $baseLotNumber + $i;
                
                BatchLot::create([
                    'batch_id' => $record->batch_id,
                    'product_id' => $productData['product_id'],
                    'lot_number' => $newLotNumber, // Lot number yang berbeda
                    'starting_price' => $productData['starting_price'],
                    'reserve_price' => $productData['reserve_price'] ?? null,
                    'status' => $record->status,
                ]);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}