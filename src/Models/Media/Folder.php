<?php

namespace Larasell\Admin\Models\Media;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 */
class Folder extends Model
{
    protected $table = 'larasell_admin_media_folders';

    protected $fillable = [
        'name',
    ];
}
