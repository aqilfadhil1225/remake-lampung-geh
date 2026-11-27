<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        $data['author_id'] = $user?->id;

        if ($user?->hasRole('reporter')) {
            if (! in_array($data['status'] ?? null, ['draft', 'in_review'], true)) {
                throw ValidationException::withMessages([
                    'data.status' => 'Reporters can only create articles with Draft or In Review status.',
                ]);
            }

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
        }

        return $data;
    }
}
