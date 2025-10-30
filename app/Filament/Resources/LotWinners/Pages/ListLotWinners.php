<?php

namespace App\Filament\Resources\LotWinners\Pages;

use App\Filament\Resources\LotWinners\LotWinnerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLotWinners extends ListRecords
{
    protected static string $resource = LotWinnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
