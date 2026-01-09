<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use FilamentTiptapEditor\TiptapEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Resources\Concerns\Translatable;
use Modules\Content\Models\Post;

class PostResource extends Resource
{
    use Translatable;

    protected static ?string $model = Post::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getNavigationLabel(): string
    {
        return __('blog.menu_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Autor')
                    ->relationship('author', 'name')
                    ->default(auth()->id())
                    ->required(),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label(__('blog.title'))
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                                Forms\Components\TextInput::make('slug')
                                    ->label(__('blog.slug'))
                                    ->required()
                                    ->unique(ignoreRecord: true),

                                TiptapEditor::make('content')
                                    ->label(__('blog.content'))
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make(__('blog.status'))
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label(__('blog.status'))
                                    ->options([
                                        'draft' => __('blog.status_draft'),
                                        'review' => __('blog.status_review'),
                                        'published' => __('blog.status_published'),
                                    ])
                                    ->default('draft')
                                    ->required(),

                                Forms\Components\DateTimePicker::make('published_at')
                                    ->label(__('blog.published_at')),

                                // JETZT MIT ALLEN PLATTFORMEN
                                Forms\Components\CheckboxList::make('platforms')
                                    ->label(__('blog.platforms'))
                                    ->options([
                                        'tags.world' => 'TAGS.WORLD',   // <--- NEU
                                        'tags.index' => 'TAGS.INDEX',   // <--- NEU
                                        'tags.menu' => 'TAGS.MENU',
                                        'tags.events' => 'TAGS.EVENTS',
                                        'tags.jobs' => 'TAGS.JOBS',
                                        'tags.shopping' => 'TAGS.SHOPPING',
                                        'best4friends' => 'BEST4FRIENDS',
                                    ])->columns(1),
                            ]),

                        Forms\Components\Section::make(__('blog.image'))
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('image')
                                    ->label(__('blog.image'))
                                    ->collection('posts')
                                    ->image(),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label(__('blog.title'))->searchable(),
                Tables\Columns\TextColumn::make('author.name')->label('Autor')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('blog.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'review' => 'warning',
                        'published' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('published_at')->label(__('blog.published_at'))->dateTime(),
            ])
            ->actions([ Tables\Actions\EditAction::make() ])
            ->bulkActions([ Tables\Actions\DeleteBulkAction::make() ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}