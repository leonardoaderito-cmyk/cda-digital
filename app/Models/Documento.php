<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['ficheiro', 'titulo', 'tipo', 'entidade', 'ano', 'categoria', 'status'])]
class Documento extends Model {}
