<?php

namespace MeuCandidato\Identity\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use MeuCandidato\Candidate\Models\Politician;

class Follow extends Model
{
    protected $fillable = [
        'user_id',
        'politician_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function politician()
    {
        return $this->belongsTo(Politician::class);
    }
}
