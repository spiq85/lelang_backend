<?php

namespace App\Filament\Resources\LotWinners\Pages;

use App\Filament\Resources\LotWinners\LotWinnerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLotWinner extends EditRecord
{
    protected static string $resource = LotWinnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
