<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = ['expense_date' => 'date'];

    public const CATEGORIES = ['rent' => 'Rent', 'salary' => 'Salaries', 'utilities' => 'Utilities', 'marketing' => 'Marketing', 'office' => 'Office', 'other' => 'Other'];

}
