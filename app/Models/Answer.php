<?php

namespace App\Models;

use App\Models\Concerns\HasVotes;
use Database\Factories\AnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    /** @use HasFactory<AnswerFactory> */
    use HasFactory, HasVotes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'body',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'hidden_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
