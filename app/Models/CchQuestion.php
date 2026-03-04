<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CchQuestion extends Model
{
    protected $table = 't_cch_questions';
    protected $primaryKey = 'question_id';
    public $timestamps = false;

    protected $fillable = ['cch_id', 'asked_by', 'question_text', 'is_resolved', 'asked_at'];
    protected $casts = ['is_resolved' => 'boolean', 'asked_at' => 'datetime'];

    public function cch(): BelongsTo { return $this->belongsTo(Cch::class, 'cch_id', 'cch_id'); }
    public function askedBy(): BelongsTo { return $this->belongsTo(CchUser::class, 'asked_by', 'id'); }
    public function responses(): HasMany { return $this->hasMany(CchQuestionResponse::class, 'question_id', 'question_id'); }
}

class CchQuestionResponse extends Model
{
    protected $table = 't_cch_question_responses';
    protected $primaryKey = 'response_id';
    public $timestamps = false;

    protected $fillable = ['question_id', 'responded_by', 'response_text', 'responded_at'];
    protected $casts = ['responded_at' => 'datetime'];

    public function question(): BelongsTo { return $this->belongsTo(CchQuestion::class, 'question_id', 'question_id'); }
    public function respondedBy(): BelongsTo { return $this->belongsTo(CchUser::class, 'responded_by', 'id'); }
}
