<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements IsTenant
{
    protected $fillable = [
        'id',
        'name',
    ];

    protected static function booted(): void
    {
        static::creating(fn(Tenant $model) => $model->createDatabase());
    }

    public function createDatabase(): void
    {
        if(is_null($this->database)) {
            $this->database = 'tenant_' . $this->name;
        }

        DB::connection('tenant')->statement(
            "CREATE DATABASE IF NOT EXISTS `$this->database`"
        );
    }
}
