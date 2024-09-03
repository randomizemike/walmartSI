<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingInstructions extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'ShippingInstructions';
    protected $fillable = ['PONumber','Containers','si_status'];
    protected $casts = [
        'Containers' => 'json',
    ];
}
