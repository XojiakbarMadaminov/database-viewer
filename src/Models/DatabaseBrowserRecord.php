<?php

namespace Mdmnv\FilamentDatabaseViewer\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DatabaseBrowserRecord extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public static function queryForTable(string $table, ?string $connection = null): Builder
    {
        $instance = new static();

        $instance->setTable($table);

        if ($connection) {
            $instance->setConnection($connection);
        }

        return $instance->newQuery();
    }
}
