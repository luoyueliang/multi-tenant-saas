<?php

namespace MultiTenantSaas\Tests;

use Illuminate\Database\Eloquent\Model;
use MultiTenantSaas\Concerns\BelongsToTenant;

class Customer extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'email'];
}
