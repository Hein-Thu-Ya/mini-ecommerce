<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Enums\ProductTypeEnum;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\MarkdownEditor;
use App\Filament\Resources\ProductResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProductResource\RelationManagers;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationGroup = 'Shop';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                ->schema([
                    Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state)))
                            ->unique(Product::class, 'slug', ignoreRecord: true),
                        TextInput::make('slug')
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->unique(Product::class, 'slug', ignoreRecord: true),
                        MarkdownEditor::make('description')->columnSpan(2)
                    ])->columns(2),
                    Section::make('Pricing & Inventory')
                    ->schema([
                        TextInput::make('sku')
                            ->label('SKU (Stock Keeping Unit)')
                            ->unique(Product::class, 'sku', ignoreRecord: true)
                            ->required(),
                        TextInput::make('price')
                            ->numeric()
                            ->required(),
                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                        Select::make('type')
                        ->options([
                            'downloadable' => ProductTypeEnum::DOWNLOADABLE->value,
                            'deliverable' => ProductTypeEnum::DELIVERABLE->value,
                        ])->required()
                    ])->columns(2)
                ]),
                Group::make()
                ->schema([
                    Section::make('Status')
                    ->schema([
                        Toggle::make('is_visible')
                            ->label('Visibility')
                            ->helperText('Enable or disable product visibility')
                            ->default(true),
                        Toggle::make('is_featured')
                            ->label('Featured')
                            ->helperText('Enable or disable product featured status'),
                        DatePicker::make('published_at')
                            ->label('Published Date')
                            ->default(now()),
                    ]),
                    Section::make('Image')
                    ->schema([
                        FileUpload::make('image')
                            ->directory('form-attachment')
                            ->preserveFilenames()
                            ->image()
                            ->imageEditor()
                            ->required()
                    ])->collapsed(),
                    Section::make('Associations')
                    ->schema([
                        Select::make('brand_id')
                            ->relationship('brand', 'name')
                    ])
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('brand.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_visible')->boolean()
                    ->sortable()
                    ->toggleable()
                    ->label('Visibility')
                    ->boolean(),
                TextColumn::make('price')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('quantity')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('type'),
                TextColumn::make('published_at')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_visible')
                    ->label('Visibility')
                    ->boolean()
                    ->trueLabel('Only visibility products')
                    ->falseLabel('Only hidden products')
                    ->native(false),
                SelectFilter::make('brand')
                    ->relationship('brand', 'name')
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit')
        ];
    }
}
