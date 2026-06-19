<?php
namespace App\Models;
class OperationLog extends AdminModel {
    protected function casts(): array { return ['payload' => 'array']; }
}

