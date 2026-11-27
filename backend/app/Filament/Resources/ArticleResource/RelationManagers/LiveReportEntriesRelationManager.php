<?php

namespace App\Filament\Resources\ArticleResource\RelationManagers;

use App\Models\LiveReportEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LiveReportEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'liveReportEntries';

    protected static ?string $title = 'Entri Live Report';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->type === 'live_report';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\RichEditor::make('content')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('image_path')
                    ->image()
                    ->nullable(),
                Forms\Components\DateTimePicker::make('published_at')
                    ->default(now('Asia/Jakarta'))
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('published_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('author.name')
                    ->label('Penulis'),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => [
                        ...$data,
                        'author_id' => auth()->id(),
                    ]),
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

    protected function canCreate(): bool
    {
        return (bool) auth()->user()?->can('create', [LiveReportEntry::class, $this->getOwnerRecord()]);
    }

    protected function canEdit(Model $record): bool
    {
        return (bool) auth()->user()?->can('update', $record);
    }

    protected function canDelete(Model $record): bool
    {
        return (bool) auth()->user()?->can('delete', $record);
    }
}
