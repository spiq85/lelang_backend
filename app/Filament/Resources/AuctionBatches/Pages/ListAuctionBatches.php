<?php

namespace App\Filament\Resources\AuctionBatches\Pages;

use App\Filament\Resources\AuctionBatches\AuctionBatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAuctionBatches extends ListRecords
{
    protected static string $resource = AuctionBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
