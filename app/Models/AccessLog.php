<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
 protected $fillable = ['pin', 'status', 'ip_address', 'owner_name'];
    protected $casts = ['created_at' => 'datetime'];
}  

