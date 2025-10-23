<?php

namespace App\Filament\Resources\BatchLots\Pages;

use App\Filament\Resources\BatchLots\BatchLotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBatchLots extends ListRecords
{
    protected static string $resource = BatchLotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
