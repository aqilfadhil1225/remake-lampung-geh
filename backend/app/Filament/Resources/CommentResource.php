<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentResource\Pages;
use App\Models\Comment;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Konten';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        $statusColor = fn (string $state): string => match ($state) {
            'pending' => 'warning',
            'approved' => 'success',
            'spam' => 'danger',
            'trash' => 'gray',
            default => 'gray',
        };

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('article.title')
                    ->label('Artikel')
                    ->limit(40)
                    ->searchable(),
                Tables\Columns\TextColumn::make('guest_name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('content')
                    ->limit(60),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color($statusColor)
                    ->formatStateUsing(fn (string $state): string => Str::headline($state)),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'spam' => 'Spam',
                        'trash' => 'Trash',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Comment $record): bool => $record->status !== 'approved')
                    ->authorize(fn (Comment $record): bool => (bool) auth()->user()?->can('update', $record))
                    ->action(fn (Comment $record) => $record->update(['status' => 'approved'])),
                Tables\Actions\Action::make('markAsSpam')
                    ->label('Mark as Spam')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn (Comment $record): bool => $record->status !== 'spam')
                    ->authorize(fn (Comment $record): bool => (bool) auth()->user()?->can('update', $record))
                    ->action(fn (Comment $record) => $record->update(['status' => 'spam'])),
                Tables\Actions\Action::make('trash')
                    ->label('Trash')
                    ->icon('heroicon-o-trash')
                    ->color('gray')
                    ->visible(fn (Comment $record): bool => $record->status !== 'trash')
                    ->authorize(fn (Comment $record): bool => (bool) auth()->user()?->can('update', $record))
                    ->action(fn (Comment $record) => $record->update(['status' => 'trash'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                    ->authorize(fn (?Collection $records = null): bool => $records === null
                        ? (bool) auth()->user()?->can('viewAny', Comment::class)
                        : $records->every(fn (Comment $record): bool => (bool) auth()->user()?->can('update', $record)))
                        ->action(fn (Collection $records) => $records->each(fn (Comment $record) => $record->update(['status' => 'approved']))),
                    Tables\Actions\BulkAction::make('markAsSpam')
                        ->label('Mark as Spam')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('danger')
                        ->authorize(fn (?Collection $records = null): bool => $records === null
                            ? (bool) auth()->user()?->can('viewAny', Comment::class)
                            : $records->every(fn (Comment $record): bool => (bool) auth()->user()?->can('update', $record)))
                        ->action(fn (Collection $records) => $records->each(fn (Comment $record) => $record->update(['status' => 'spam']))),
                    Tables\Actions\BulkAction::make('trash')
                        ->label('Trash')
                        ->icon('heroicon-o-trash')
                        ->color('gray')
                        ->authorize(fn (?Collection $records = null): bool => $records === null
                            ? (bool) auth()->user()?->can('viewAny', Comment::class)
                            : $records->every(fn (Comment $record): bool => (bool) auth()->user()?->can('update', $record)))
                        ->action(fn (Collection $records) => $records->each(fn (Comment $record) => $record->update(['status' => 'trash']))),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComments::route('/'),
        ];
    }
}
