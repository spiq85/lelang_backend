<?php

namespace App\Filament\Resources\BatchLots\Pages;

use App\Filament\Resources\BatchLots\BatchLotResource;
use App\Models\BatchLot;
use Filament\Resources\Pages\EditRecord;

class EditBatchLot extends EditRecord
{
    protected static string $resource = BatchLotResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Hanya load lot yang sedang di-edit
        $data['lot_products'] = [
            [
                'product_id' => $data['product_id'],
                'starting_price' => $data['starting_price'],
                'reserve_price' => $data['reserve_price'],
            ]
        ];
        
        return $data;
    }

    protected array $cachedProducts = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $productsData = $data['lot_products'] ?? [];
        
        // Ambil product pertama untuk main record
        if (!empty($productsData)) {
            $firstProduct = $productsData[0];
            $data['product_id'] = $firstProduct['product_id'];
            $data['starting_price'] = $firstProduct['starting_price'];
            $data['reserve_price'] = $firstProduct['reserve_price'] ?? null;
        }
        
        // Simpan products tambahan
        $this->cachedProducts = array_slice($productsData, 1); // Skip product pertama
        
        unset($data['lot_products']);
        
        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $additionalProducts = $this->cachedProducts;
        
        // Jika ada products tambahan, buat lot baru dengan lot_number yang increment
        if (!empty($additionalProducts)) {
            // Cari lot_number tertinggi di batch ini
            $maxLotNumber = BatchLot::where('batch_id', $record->batch_id)
                ->max('lot_number');
            
            $nextLotNumber = max($maxLotNumber, $record->lot_number) + 1;
            
            foreach ($additionalProducts as $productData) {
                BatchLot::create([
                    'batch_id' => $record->batch_id,
                    'product_id' => $productData['product_id'],
                    'lot_number' => $nextLotNumber,
                    'starting_price' => $productData['starting_price'],
                    'reserve_price' => $productData['reserve_price'] ?? null,
                    'status' => $record->status,
                ]);
                
                $nextLotNumber++;
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}