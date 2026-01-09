<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    // Dynamische Beschriftung über Sprachdateien
    public static function getNavigationLabel(): string
    {
        return __('blog.users');
    }

    // Gruppierung im Menü
    public static function getNavigationGroup(): ?string
    {
        return __('blog.menu_group_users');
    }

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', false)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Benutzerdaten')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('email')
                            ->label('E-Mail Adresse')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('password')
                            ->label('Passwort')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),
                    ])->columns(2),

                Forms\Components\Section::make('Zugriffssteuerung & Rollen')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->label('Rolle')
                            ->options(function () {
                                $roles = [
                                    User::ROLE_CREATOR => 'Creator (Blogger)',
                                ];

                                if (Auth::user()->isSuperAdmin()) {
                                    $roles[User::ROLE_ADMIN] = 'Admin (Manager)';
                                    $roles[User::ROLE_SUPER_ADMIN] = 'Super Admin (IT)';
                                }

                                return $roles;
                            })
                            ->disabled(fn ($record) => !Auth::user()->isSuperAdmin() && $record?->isSuperAdmin())
                            ->required()
                            ->default(User::ROLE_CREATOR),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Benutzerkonto aktivieren')
                            ->helperText('Deaktivierte Nutzer können sich nicht einloggen.')
                            ->default(false)
                            ->onColor('success')
                            ->offColor('danger'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->icon('heroicon-m-envelope'),

                Tables\Columns\TextColumn::make('role')
                    ->label('Rolle')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        User::ROLE_SUPER_ADMIN => 'danger',
                        User::ROLE_ADMIN       => 'warning',
                        User::ROLE_CREATOR     => 'success',
                        default                => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        User::ROLE_SUPER_ADMIN => 'Super Admin',
                        User::ROLE_ADMIN       => 'Admin',
                        User::ROLE_CREATOR     => 'Creator',
                        default                => $state,
                    })
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Freigabe')
                    ->onColor('success')
                    ->offColor('danger')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registriert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('wartet_auf_freigabe')
                    ->label('Nur wartende Benutzer')
                    ->query(fn ($query) => $query->where('is_active', false)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}