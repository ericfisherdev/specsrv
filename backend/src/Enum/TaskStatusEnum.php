<?php

namespace App\Enum;

enum TaskStatusEnum: string
{
    case BACKLOG = 'backlog';
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case REVIEW = 'review';
    case COMPLETED = 'completed';
    case OBSOLETE = 'obsolete';

    public static function getActiveStatuses(): array
    {
        return [self::BACKLOG, self::TODO, self::IN_PROGRESS, self::REVIEW, self::COMPLETED];
    }

    public function getLabel(): string
    {
        return match($this) {
            self::BACKLOG => 'Backlog',
            self::TODO => 'Todo',
            self::IN_PROGRESS => 'In Progress',
            self::REVIEW => 'Review',
            self::COMPLETED => 'Completed',
            self::OBSOLETE => 'Obsolete',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::BACKLOG => 'gray',
            self::TODO => 'blue',
            self::IN_PROGRESS => 'yellow',
            self::REVIEW => 'purple',
            self::COMPLETED => 'green',
            self::OBSOLETE => 'red',
        };
    }
}
