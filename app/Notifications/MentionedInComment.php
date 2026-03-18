<?php

namespace App\Notifications;

use App\Models\Entry;
use App\Models\EntryComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MentionedInComment extends Notification
{
    use Queueable;

    public function __construct(
        public readonly EntryComment $comment,
        public readonly Entry $entry,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $commenter = $this->comment->user;
        $book      = $this->entry->book;
        $business  = $book->business;

        return [
            'type'               => 'mention',
            'commenter_name'     => $commenter?->name ?? 'Someone',
            'commenter_id'       => $commenter?->id,
            'entry_id'           => $this->entry->id,
            'entry_description'  => $this->entry->description,
            'entry_amount'       => $this->entry->amount,
            'entry_type'         => $this->entry->type,
            'book_id'            => $book->id,
            'book_name'          => $book->name,
            'business_id'        => $business->id,
            'business_name'      => $business->name,
            'comment_id'         => $this->comment->id,
            'comment_excerpt'    => \Str::limit(strip_tags($this->comment->body), 80),
        ];
    }
}
