<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use Modules\Content\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Concerns\Translatable;
use Illuminate\Support\Str;
use Filament\Forms\Set;

class CategoryResource extends Resource
{
    use Translatable; 

    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    
    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('blog.categories'); // <--- Key statt fester Text
    }

    public static function getNavigationGroup(): ?string
    {
        return __('blog.menu_group_content'); // <--- Key für "Content Management"
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('blog.category_basics')) // "Basisdaten"
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('blog.category_name')) // "Name"
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, Set $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->label(__('blog.slug'))
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\ColorPicker::make('color')
                            ->label(__('blog.category_color')) // "Farbe"
                            ->helperText(__('blog.category_color_desc')), // "Wird für Badges genutzt"
                    ])->columns(2),

                Forms\Components\Section::make(__('blog.settings')) // "Einstellungen"
                    ->schema([
                        Forms\Components\TextInput::make('icon')
                            ->label(__('blog.category_icon'))
                            ->placeholder('heroicon-o-star')
                            ->helperText(__('blog.category_icon_desc')),

                        Forms\Components\TextInput::make('sort_order')
                            ->label(__('blog.category_sort')) // "Sortierung"
                            ->numeric()
                            ->default(0),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('blog.category_active')) // "Aktiv?"
                            ->default(true),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color')
                    ->label(__('blog.category_color')),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('blog.category_name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label(__('blog.slug'))
                    ->color('gray')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('blog.category_sort'))
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('blog.category_active'))
                    ->boolean(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}