<?php

namespace App\Filament\Resources\BatchLots\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\HtmlString;

class BatchLotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([

            /* ---------- header field ---------- */
            Forms\Components\Select::make('batch_id')
                ->label('Auction Batch')
                ->relationship('batch', 'title')
                ->searchable()
                ->preload()
                ->required()
                ->reactive(),

            Forms\Components\TextInput::make('lot_number')
                ->label('Lot Number')
                ->numeric()
                ->required()
                ->reactive()          // <— agar repeater bisa re-validate saat berubah
                ->minValue(1),

            /* ---------- hidden field utama (aman DB) ---------- */
            Forms\Components\Hidden::make('product_id')->default(null),
            Forms\Components\Hidden::make('starting_price')->default(0),
            Forms\Components\Hidden::make('reserve_price')->default(null),

            /* ---------- repeater produk ---------- */
            Section::make('Produk dalam Lot Ini')
                ->collapsible()
                ->collapsed(fn ($record) => $record?->lotProducts->count() > 8)
                ->schema([
                    Forms\Components\Repeater::make('lotProducts')
                        ->relationship('lotProducts')
                        ->schema([
                            /* ---- pilih produk ---- */
                            Forms\Components\Select::make('product_id')
                                ->label('Pilih Produk')
                                ->options(Product::pluck('product_name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->reactive()
                                ->distinct()
                                ->afterStateUpdated(function ($state, $set) {
                                    if (!$state) return;
                                    $product = Product::find($state);
                                    $price = $product?->base_price ?? 0;
                                    $set('starting_price', $price);
                                    $set('reserve_price', $price);
                                }),

                            /* ---- preview gambar ---- */
                            Forms\Components\Placeholder::make('image_preview')
                                ->label('Preview Gambar')
                                ->content(function ($get) {
                                    $id = $get('product_id');
                                    if (!$id) return new HtmlString('<em>Pilih produk untuk melihat gambar</em>');

                                    $product = Product::with('images')->find($id);
                                    if (!$product || $product->images->isEmpty()) {
                                        return new HtmlString('<em>Tidak ada gambar</em>');
                                    }

                                    $html = '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">';
                                    foreach ($product->images->sortBy('sort_order')->take(5) as $img) {
                                        $url = asset('storage/' . $img->image_url);
                                        $html .= "<img src=\"{$url}\" style=\"width:72px;height:72px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;\" />";
                                    }
                                    $html .= '</div>';
                                    return new HtmlString($html);
                                })
                                ->columnSpanFull(),

                            /* ---- grid harga ---- */
                            Grid::make(2)->schema([
                                Forms\Components\TextInput::make('starting_price')
                                    ->label('Starting Price')
                                    ->numeric()
                                    ->prefix('Rp ')
                                    ->required()
                                    ->minValue(0),

                                Forms\Components\TextInput::make('reserve_price')
                                    ->label('Reserve Price')
                                    ->numeric()
                                    ->prefix('Rp ')
                                    ->nullable()
                                    ->helperText('Kosongkan jika tidak ada reserve'),
                            ]),
                        ])
                        ->columns(1)
                        ->collapsible()
                        ->cloneable()
                        ->reorderableWithDragAndDrop(false)
                        ->itemLabel(fn (array $state) => Product::find($state['product_id'] ?? null)?->product_name ?? 'Produk Baru')
                        ->minItems(1)
                        ->maxItems(50)

                        /* ======  CORE : validasi & tombol dinamis  ====== */
                        ->rules(function (callable $get) {
                            return [
                                function ($attribute, $value, $fail) use ($get) {
                                    $max = (int) $get('lot_number');
                                    if (!$max) {
                                        $fail('Isi Lot Number terlebih dahulu.');
                                        return;
                                    }
                                    if (count($value) > $max) {
                                        $fail("Maksimal {$max} produk sesuai Lot Number.");
                                    }
                                },
                            ];
                        })

                        ->addAction(function (Action $action, callable $get) {
                            // sembunyikan tombol saat jumlah bar == lot_number
                            $action->visible(fn () => count($get('lotProducts') ?? []) < (int) $get('lot_number'));
                        })

                        ->addActionLabel('Tambah Produk Lagi')
                        ->deleteAction(fn ($action) => $action->requiresConfirmation()),
                ]),
        ]);
    }
}
