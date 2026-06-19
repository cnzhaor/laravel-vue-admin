<?php
namespace App\Models;
class Menu extends AdminModel {
    protected function casts(): array { return ['enabled' => 'boolean', 'visible' => 'boolean']; }
}

