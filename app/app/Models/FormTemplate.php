<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormTemplate extends Model
{
    protected $fillable = ['user_id', 'title', 'slug', 'unique_string', 'description', 'form_structure', 'is_public','multi_submission','login_required','edit_response', 
     'delete_requested',
    'delete_requested_by',
    'delete_approved',
    'delete_approved_by',
    'delete_reason',
    'allowed_old_phase', 'is_dynamic_url',
                            'success_message', 'redirect_method', 'redirect_url', 'submit_btn_txt', 'isEverPublished','isAnonymous','is_published', 'accessible_using_url', 'show_in_sdp_report', 'scoring', 'parameters','approval_required','is_registration_form', 'student_type', 'student_type_name', 'review','form_type'];

    protected $casts = [
        'form_structure' => 'array',
        'is_public' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function submissions()
    {
        return $this->hasMany(FormSubmission::class);
    }
}