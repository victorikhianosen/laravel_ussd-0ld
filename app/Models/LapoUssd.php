<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LapoUssd extends Model
{
    use HasFactory;

    public function printOut(){
        return 'printing out test';
    }
}
