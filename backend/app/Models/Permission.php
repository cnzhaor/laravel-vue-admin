<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Permission extends AdminModel {
    public function roles(): BelongsToMany { return $this->belongsToMany(Role::class); }
}

