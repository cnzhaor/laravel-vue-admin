<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Dictionary extends AdminModel {
    public function items(): HasMany { return $this->hasMany(DictionaryItem::class); }
}

