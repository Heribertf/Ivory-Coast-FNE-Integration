<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $connection = 'sage_db_connection';
    protected $table = '_etblUnits';
    protected $primaryKey = 'idUnits';
    public $timestamps = false;
}
