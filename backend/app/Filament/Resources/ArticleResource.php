<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Filament\Resources\ArticleResource\RelationManagers;
use App\Models\Article;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = 'Konten';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        $isEditor = fn (): bool => (bool) auth()->user()?->hasAnyRole(['admin', 'editor']);

        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Section::make()
                            ->columnSpan(2)
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, Set $set, ?string $state): void {
                                        if ($operation === 'create' && filled($state)) {
                                            $set('slug', Str::slug($state));
                                        }
                                    }),
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                Forms\Components\Textarea::make('excerpt')
                                    ->required()
                                    ->rows(3)
                                    ->columnSpanFull(),
                                Forms\Components\RichEditor::make('content')
                                    ->required()
                                    ->columnSpanFull(),
                                Forms\Components\SpatieMediaLibraryFileUpload::make('featured_image')
                                    ->collection('featured_image')
                                    ->image()
                                    ->nullable()
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('image_caption')
                                    ->maxLength(255)
                                    ->nullable(),
                                Forms\Components\TextInput::make('image_credit')
                                    ->maxLength(255)
                                    ->nullable(),
                                Forms\Components\TextInput::make('video_url')
                                    ->url()
                                    ->maxLength(255)
                                    ->nullable()
                                    ->columnSpanFull(),
                            ]),
                        Forms\Components\Section::make()
                            ->columnSpan(1)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->options(fn (): array => $isEditor()
                                        ? [
                                            'draft' => 'Draft',
                                            'in_review' => 'In Review',
                                            'revision_needed' => 'Revision Needed',
                                            'scheduled' => 'Scheduled',
                                            'published' => 'Published',
                                            'archived' => 'Archived',
                                        ]
                                        : [
                                            'draft' => 'Draft',
                                            'in_review' => 'In Review',
                                        ])
                                    ->required()
                                    ->in(fn (): array => $isEditor()
                                        ? ['draft', 'in_review', 'revision_needed', 'scheduled', 'published', 'archived']
                                        : ['draft', 'in_review'])
                                    ->default('draft')
                                    ->live(),
                                Forms\Components\Textarea::make('editor_note')
                                    ->rows(3)
                                    ->nullable()
                                    ->disabled(fn (): bool => ! $isEditor())
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\Select::make('tags')
                                    ->relationship('tags', 'name')
                                    ->multiple()
                                    ->preload(),
                                Forms\Components\Select::make('region_scope')
                                    ->options([
                                        'bandar_lampung' => 'Bandar Lampung',
                                        'lampung' => 'Lampung',
                                        'nasional' => 'Nasional',
                                    ])
                                    ->required()
                                    ->default('bandar_lampung'),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'standard' => 'Standard',
                                        'live_report' => 'Live Report',
                                    ])
                                    ->required()
                                    ->default('standard')
                                    ->live(),
                                Forms\Components\Select::make('live_status')
                                    ->options([
                                        'live' => 'Live',
                                        'ended' => 'Ended',
                                    ])
                                    ->visible(fn (Get $get): bool => $get('type') === 'live_report')
                                    ->required(fn (Get $get): bool => $get('type') === 'live_report'),
                                Forms\Components\Select::make('liveReportAssignees')
                                    ->relationship(
                                        'liveReportAssignees',
                                        'name',
                                        fn (Builder $query) => $query->role('reporter'),
                                    )
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->visible(fn (Get $get): bool => $get('type') === 'live_report' && $isEditor()),
                                Forms\Components\DateTimePicker::make('published_at')
                                    ->visible($isEditor)
                                    ->required(fn (Get $get): bool => $isEditor() && $get('status') === 'scheduled'),
                                Forms\Components\Section::make('Kurasi')
                                    ->visible($isEditor)
                                    ->schema([
                                        Forms\Components\Toggle::make('is_headline'),
                                        Forms\Components\Toggle::make('is_breaking'),
                                        Forms\Components\Toggle::make('is_recommended'),
                                        Forms\Components\Toggle::make('is_featured'),
                                        Forms\Components\Toggle::make('is_sponsored')->live(),
                                        Forms\Components\TextInput::make('sponsor_name')
                                            ->maxLength(255)
                                            ->nullable()
                                            ->visible(fn (Get $get): bool => (bool) $get('is_sponsored')),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable(),
                Tables\Columns\TextColumn::make('author.name')
                    ->label('Penulis')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'in_review' => 'warning',
                        'revision_needed' => 'danger',
                        'scheduled' => 'info',
                        'published' => 'success',
                        'archived' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Str::headline($state)),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'live_report' ? 'danger' : 'gray')
                    ->formatStateUsing(fn (string $state): string => Str::headline($state)),
                Tables\Columns\TextColumn::make('live_status')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'live' ? 'danger' : 'gray')
                    ->formatStateUsing(fn (?string $state): string => $state === 'live' ? 'LIVE' : ($state ? Str::headline($state) : '—'))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('views_count')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'in_review' => 'In Review',
                        'revision_needed' => 'Revision Needed',
                        'scheduled' => 'Scheduled',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'standard' => 'Standard',
                        'live_report' => 'Live Report',
                    ]),
                Tables\Filters\SelectFilter::make('region_scope')
                    ->options([
                        'bandar_lampung' => 'Bandar Lampung',
                        'lampung' => 'Lampung',
                        'nasional' => 'Nasional',
                    ]),
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            RelationManagers\LiveReportEntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
