<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormSubmission extends Model
{
    protected $fillable = ['form_template_id', 'submission_data','userid','reject_reason','approval_status','review_status'];

    protected $casts = [
        'submission_data' => 'array',
    ];

    public function formTemplate()
    {
        return $this->belongsTo(FormTemplate::class);
    }
}