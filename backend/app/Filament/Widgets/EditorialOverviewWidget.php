<?php

namespace App\Filament\Widgets;

use App\Models\Article;
use App\Models\Comment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EditorialOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Artikel In Review', Article::where('status', 'in_review')->count())
                ->icon('heroicon-o-document-text'),
            Stat::make('Live Report Aktif', Article::where('type', 'live_report')->where('live_status', 'live')->count())
                ->icon('heroicon-o-signal'),
            Stat::make('Komentar Pending', Comment::where('status', 'pending')->count())
                ->icon('heroicon-o-chat-bubble-left-right'),
        ];
    }
}
