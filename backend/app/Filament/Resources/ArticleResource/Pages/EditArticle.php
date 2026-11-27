<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();
        $record = $this->getRecord();

        if ($user?->hasRole('reporter')) {
            $allowed = ['draft', 'in_review'];

            if (in_array($record->status, ['draft', 'in_review', 'revision_needed'], true)) {
                $allowed[] = $record->status;
            }

            $status = $data['status'] ?? $record->status;

            if (! in_array($status, array_unique($allowed), true)) {
                throw ValidationException::withMessages([
                    'data.status' => 'Reporters can only set Draft or In Review status.',
                ]);
            }

            $data['status'] = $status;
            $data['author_id'] = $record->author_id;

            foreach ([
                'is_headline',
                'is_breaking',
                'is_recommended',
                'is_featured',
                'is_sponsored',
                'sponsor_name',
                'editor_note',
                'editor_id',
                'published_at',
            ] as $field) {
                unset($data[$field]);
            }

            return $data;
        }

        if (in_array($data['status'] ?? null, ['revision_needed', 'scheduled', 'published'], true)) {
            $data['editor_id'] = $user?->id;
        }

        if (($data['status'] ?? null) === 'published' && blank($data['published_at'] ?? null)) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
