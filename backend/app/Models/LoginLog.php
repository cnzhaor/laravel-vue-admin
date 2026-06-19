<?php
namespace App\Models;
class LoginLog extends AdminModel {
    protected function casts(): array { return ['success' => 'boolean']; }
}

