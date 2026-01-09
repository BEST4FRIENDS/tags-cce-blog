<?php

namespace App\Filament\Creator\Resources;

use App\Filament\Creator\Resources\PostResource\Pages;
use Modules\Content\Models\Post;
use Modules\Content\Models\Category;
use App\Services\GeminiService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use FilamentTiptapEditor\TiptapEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Resources\Concerns\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;

class PostResource extends Resource
{
    use Translatable;

    protected static ?string $model = Post::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-pencil';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationLabel(): string
    {
        return __('blog.menu_label');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', Auth::id());
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->title;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('blog.status') => match ($record->status) {
                'draft' => __('blog.status_draft'),
                'review' => __('blog.status_review'),
                'published' => __('blog.status_published'),
                default => $record->status,
            },
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(Auth::id()),
                Forms\Components\Hidden::make('author_id')
                    ->default(Auth::id()),

                // --- LINKER BEREICH ---
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label(__('blog.title'))
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $set('slug', Str::slug($state));
                                    })
                                    // --- MAGIC TITLE ---
                                    ->suffixAction(
                                        Action::make('magicTitle')
                                            ->icon('heroicon-m-sparkles')
                                            ->color('warning')
                                            ->tooltip('KI: Titel optimieren')
                                            ->action(function (Get $get, Set $set, $livewire) {
                                                $rawContent = $get('content');
                                                $content = self::extractTextFromTiptap($rawContent);
                                                $langCode = $livewire->activeLocale; 

                                                if (strlen($content) < 10) {
                                                    Notification::make()->title('Zu wenig Inhalt!')->warning()->send();
                                                    return;
                                                }

                                                $service = app(GeminiService::class);
                                                Notification::make()->title('Gemini generiert Titel (' . strtoupper($langCode) . ')...')->info()->send();
                                                
                                                $prompt = "Generiere einen einzigen, extrem attraktiven und klickstarken Blog-Titel in der Sprache '{$langCode}' (max 60 Zeichen) basierend auf folgendem Text. Antworte NUR mit dem Titel, ohne Anführungszeichen: \n\n" . substr($content, 0, 1000);
                                                
                                                $newTitle = $service->generateText($prompt);

                                                if ($newTitle) {
                                                    $set('title', trim($newTitle));
                                                    $set('slug', Str::slug($newTitle));
                                                    Notification::make()->title('Titel generiert!')->success()->send();
                                                }
                                            })
                                    ),

                                Forms\Components\TextInput::make('slug')
                                    ->label(__('blog.slug'))
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->suffixAction(
                                        Action::make('regenerateSlug')
                                            ->icon('heroicon-m-arrow-path')
                                            ->tooltip('Aus Titel generieren')
                                            ->action(function (Get $get, Set $set) {
                                                $title = $get('title');
                                                if ($title) $set('slug', Str::slug($title));
                                            })
                                    ),

                                TiptapEditor::make('content')
                                    ->label(__('blog.content'))
                                    ->columnSpanFull()
                                    ->required()
                                    ->live(debounce: 500)
                                    ->extraInputAttributes(['style' => 'min-height: 400px;'])
                                    // --- SMART TRANSLATOR (Jede Richtung!) ---
                                    ->hintAction(
                                        Action::make('translateContent')
                                            ->label(__('blog.translate_action_label')) // "Inhalt übersetzen"
                                            ->icon('heroicon-m-language')
                                            ->color('info')
                                            // Modal: Frage nach der Quelle
                                            ->form(function ($record, $livewire) {
                                                if (!$record) return [];

                                                // Wir suchen alle Sprachen, die schon Inhalt haben
                                                $existingTranslations = $record->getTranslations('content');
                                                $currentLang = $livewire->activeLocale;
                                                
                                                // Baue Optionen (z.B. "de" => "Deutsch")
                                                $options = [];
                                                foreach($existingTranslations as $locale => $content) {
                                                    // Biete Sprache nur an, wenn sie Inhalt hat UND nicht die aktuelle ist
                                                    if (!empty($content) && $locale !== $currentLang) {
                                                        $options[$locale] = strtoupper($locale); 
                                                    }
                                                }

                                                return [
                                                    Forms\Components\Select::make('source_lang')
                                                        ->label(__('blog.translate_source_label')) // "Quellsprache"
                                                        ->options($options)
                                                        ->required()
                                                        ->native(false),
                                                ];
                                            })
                                            ->modalHeading(__('blog.translate_modal_title'))
                                            ->modalSubmitActionLabel('Start Gemini 🚀')
                                            ->action(function ($record, Set $set, $livewire, array $data) {
                                                if (!$record) {
                                                    Notification::make()->title(__('blog.translate_not_saved'))->warning()->send();
                                                    return;
                                                }
                                                
                                                // 1. Quellsprache und Zielsprache bestimmen
                                                $sourceLang = $data['source_lang']; // Wurde im Modal gewählt
                                                $targetLang = $livewire->activeLocale; // Wo wir gerade sind

                                                // 2. Inhalt holen
                                                $sourceContentRaw = $record->getTranslation('content', $sourceLang);
                                                $sourceText = self::extractTextFromTiptap($sourceContentRaw);

                                                if (strlen($sourceText) < 5) {
                                                    Notification::make()->title(__('blog.translate_error_no_source'))->warning()->send();
                                                    return;
                                                }

                                                // 3. KI beauftragen
                                                $service = app(GeminiService::class);
                                                Notification::make()->title("Gemini: {$sourceLang} -> {$targetLang} ...")->info()->send();

                                                $prompt = "You are a professional translator. Translate the following blog post from language code '{$sourceLang}' to language code '{$targetLang}'. Return the result as clean HTML (using <p>, <h2>, <ul> tags). Do not add markdown code blocks.\n\nText:\n" . $sourceText;
                                                
                                                $translatedHtml = $service->generateText($prompt);
                                                $translatedHtml = str_replace(['```html', '```'], '', $translatedHtml);

                                                if ($translatedHtml) {
                                                    $set('content', $translatedHtml);
                                                    Notification::make()->title(__('blog.translate_success'))->success()->send();
                                                }
                                            })
                                    ),
                            ]),

                        // SEO Box
                        Forms\Components\Section::make('SEO & Sichtbarkeit')
                            ->schema([
                                Forms\Components\TextInput::make('meta_title')
                                    ->label('Meta Titel (Google)')
                                    ->maxLength(60),

                                Forms\Components\Textarea::make('meta_description')
                                    ->label('Meta Beschreibung')
                                    ->rows(3)
                                    ->maxLength(160)
                                    ->hintAction(
                                        Action::make('generateSEO')
                                            ->label('KI: Automatisch generieren')
                                            ->icon('heroicon-m-sparkles')
                                            ->color('primary')
                                            ->action(function (Get $get, Set $set, $livewire) {
                                                $rawContent = $get('content');
                                                $content = self::extractTextFromTiptap($rawContent);
                                                $title = $get('title');
                                                $langCode = $livewire->activeLocale;
                                                
                                                if(is_array($title)) $title = reset($title);

                                                if (strlen($content) < 10) {
                                                    Notification::make()->title('Zu wenig Inhalt für SEO!')->warning()->send();
                                                    return;
                                                }

                                                $service = app(GeminiService::class);
                                                Notification::make()->title('Optimiere für Google (' . strtoupper($langCode) . ')...')->info()->send();

                                                $prompt = "Du bist ein SEO Experte. Erstelle basierend auf dem Text unten IN DER SPRACHE '{$langCode}':\n1. Einen SEO Meta-Title (max 60 Zeichen)\n2. Eine Meta-Description (max 155 Zeichen, animierend zum Klicken).\n\nTitel: $title\nInhalt: " . substr($content, 0, 800) . "\n\nAntworte strikt als JSON Objekt: {\"meta_title\": \"...\", \"meta_description\": \"...\"}";

                                                $jsonResponse = $service->generateText($prompt);
                                                $jsonResponse = str_replace(['```json', '```'], '', $jsonResponse);
                                                $data = json_decode($jsonResponse, true);

                                                if ($data) {
                                                    $set('meta_title', $data['meta_title'] ?? null);
                                                    $set('meta_description', $data['meta_description'] ?? null);
                                                    Notification::make()->title('SEO Daten generiert!')->success()->send();
                                                }
                                            })
                                    ),
                            ]),

                    ])->columnSpan(['lg' => 2]),

                // --- RECHTER BEREICH ---
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make(__('blog.status'))
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label(__('blog.status'))
                                    ->options([
                                        'draft' => __('blog.status_draft'),
                                        'review' => __('blog.status_review'),
                                    ])
                                    ->default('draft')
                                    ->selectablePlaceholder(false)
                                    ->native(false)
                                    ->required(),
                                Forms\Components\CheckboxList::make('platforms')
                                    ->label(__('blog.platforms'))
                                    ->options([
                                        'tags.world' => 'TAGS.WORLD',
                                        'tags.index' => 'TAGS.INDEX',
                                        'tags.menu' => 'TAGS.MENU',
                                        'tags.events' => 'TAGS.EVENTS',
                                        'tags.jobs' => 'TAGS.JOBS',
                                        'tags.shopping' => 'TAGS.SHOPPING',
                                        'best4friends' => 'BEST4FRIENDS',
                                    ])
                                    ->columns(1),
                            ]),

                        Forms\Components\Section::make('Organisieren')
                            ->schema([
                                Forms\Components\Select::make('category_id')
                                    ->label('Kategorie')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->required(),
                                        Forms\Components\TextInput::make('slug')->required(),
                                    ]),
                                SpatieTagsInput::make('tags')
                                    ->label('Tags (#Hashtags)')
                                    ->type('hashtags'),
                            ]),

                        Forms\Components\Section::make(__('blog.image'))
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('image')
                                    ->label(__('blog.image'))
                                    ->collection('posts')
                                    ->image()
                                    ->imageEditor(),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('image')
                    ->collection('posts')->circular(),
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category.name')->badge()->color('info'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d.m.Y')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
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

    protected static function extractTextFromTiptap($node): string
    {
        if (empty($node)) return '';
        if (is_string($node)) return strip_tags($node);
        
        $text = '';
        if (is_array($node)) {
            if (isset($node['text']) && is_string($node['text'])) {
                $text .= $node['text'] . ' ';
            }
            if (isset($node['content']) && is_array($node['content'])) {
                foreach ($node['content'] as $child) {
                    $text .= self::extractTextFromTiptap($child);
                }
            }
            if (!isset($node['type']) && !isset($node['text'])) {
                foreach ($node as $val) {
                    $text .= self::extractTextFromTiptap($val);
                    if(strlen($text) > 50000) break;
                }
            }
        }
        return trim($text);
    }
}