<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Role extends AdminModel {
    protected function casts(): array { return ['enabled' => 'boolean', 'is_system' => 'boolean']; }
    public function users(): BelongsToMany { return $this->belongsToMany(User::class); }
    public function permissions(): BelongsToMany { return $this->belongsToMany(Permission::class); }
}

