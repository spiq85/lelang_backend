<?php

namespace App\Filament\Resources\AuctionBatches\Pages;

use App\Filament\Resources\AuctionBatches\AuctionBatchResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAuctionBatch extends EditRecord
{
    protected static string $resource = AuctionBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
