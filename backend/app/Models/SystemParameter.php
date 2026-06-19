<?php
namespace App\Models;
class SystemParameter extends AdminModel {
    protected function casts(): array { return ['is_public' => 'boolean']; }
}

