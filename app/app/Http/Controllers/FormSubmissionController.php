<?php

namespace App\Http\Controllers;

use App\Models\FormTemplate;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

 
use DB;

class FormSubmissionController extends Controller
{
    
    public function short_link_show(string $unique_string)
    {
        $form = FormTemplate::where('unique_string', $unique_string)
        ->where('is_dynamic_url', 0)
        ->firstOrFail();
        
        
        if ($form->active != 1) {
        abort(403); 
        }
        if (in_array($role, [2, 20, 6]) && $form->accessible_using_url == 0
            && !$this->isFormAssignedToUserBatch($form->id, 1)) {
            abort(403, 'This form is not accessible or not published.');
        }
       
        

        $submit_btn_txt = $form->submit_btn_txt;
        $submission = FormSubmission::where('userid', 1)->where('form_template_id', $form->id)->first();

        return view('show', ["title"=>"Form ", "form"=>$form, "submission"=>$submission, "submit_btn_txt"=>$submit_btn_txt]);
    }

     public function showDynamicUrl(string $slug, $is_dynamic_url)
    {
        
        if($is_dynamic_url == null || $is_dynamic_url == '' || empty($is_dynamic_url))
        {
            abort(404);
        }

        $form = FormTemplate::where('slug', $slug)
        ->where('is_dynamic_url', 1)
        ->firstOrFail();

        if($form->login_required == 1){
            if (!Auth::check()) {
               return redirect('login');
            }
        }else{
            if(!Auth::check()){
                if ($form->accessible_using_url == 0) {
                    abort(403, 'This form is not accessible or not published.');
                }
                if ( $form->is_published == 1) {
                    return view('show-witout-login', ["title"=>"Form ", "form"=>$form, "submit_btn_txt"=>$form->submit_btn_txt, 'is_dynamic_url' => $is_dynamic_url]);
                } else {
                    return abort(403);;
                }
            }
        }

        if (!1) {
            return redirect()->route('login');
        }
        $userRole = DB::table('users')->select('role')->where('id', 1)->first();
        $role = $userRole->role;
        if ($form->active != 1 && $form->active != -1) {
            abort(403); 
        }
        if (in_array($role, [2, 20, 6]) && $form->accessible_using_url == 0
            && !$this->isFormAssignedToUserBatch($form->id, 1)) {
            abort(403, 'This form is not accessible or not published.');
        }
       
        

        $submission = FormSubmission::where('userid', 1)->where('form_template_id', $form->id)->first();

        $submit_btn_txt = $form->submit_btn_txt;

        if($submit_btn_txt == null)
        {
            $submit_btn_txt = "Submit";
        }

        $submittedData = null;
        if ($form->review == 1 && $submission && $submission->review_status === 'rejected') {
            $submittedData = $submission->submission_data;
        }

        return view('show', [
            "title"=>"Form ", "form"=>$form, "submission"=>$submission,
            "submit_btn_txt"=>$submit_btn_txt, "submittedData"=>$submittedData,
            "workflowSlug"=>$workflowSlug??'', "remainingSeconds"=>$remainingSeconds??'',
            "proctorResultId"=>$proctorResultId??'', "enableCamera"=>$enableCamera??'', "enableScreen"=>$enableScreen??'', 
            "requireSeb"=>$requireSeb??'', 'is_dynamic_url' => $is_dynamic_url
        ]);
    }

    public function show(string $slug)
    {
        $form = FormTemplate::where('slug', $slug)
        ->where('is_dynamic_url', 0)
        ->firstOrFail();
        
        if ($form->active != 1 && $form->active != -1) {
            abort(403); 
        }
        if ($form->accessible_using_url == 0) {
            abort(403, 'This form is not accessible or not published.');
        }
       
        

        $submission = FormSubmission::where('userid', 1)->where('form_template_id', $form->id)->first();

        $submit_btn_txt = $form->submit_btn_txt;

        if($submit_btn_txt == null)
        {
            $submit_btn_txt = "Submit";
        }

        $submittedData = null;
        if ($form->review == 1 && $submission && $submission->review_status === 'rejected') {
            $submittedData = $submission->submission_data;
        }

        return view('show', [
            "title"=>"Form ", "form"=>$form, "submission"=>$submission,
            "submit_btn_txt"=>$submit_btn_txt, "submittedData"=>$submittedData,
            "workflowSlug"=>$workflowSlug??'', "remainingSeconds"=>$remainingSeconds??'',
            "proctorResultId"=>$proctorResultId??'', "enableCamera"=>$enableCamera??'', "enableScreen"=>$enableScreen??'', 
            "requireSeb"=>$requireSeb??'',
        ]);
    }

    public function thankYouFormSubmission()
    {
        if (!session()->has('success'))
        {
             $title = "Edutnet";
        }
       else
        {
             $title = "Thanks You for form submission";
        }
        return view('admin.registration_thank_you', compact('title'));
    }

    public function store(Request $request, string $slug)
    {
       
        $form = FormTemplate::where('slug', $slug)->firstOrFail();
        
        $structure = json_decode($form->form_structure);
        $validationRules = $this->generateValidationRules($structure, $request);


        $validated = $request->validate($validationRules, [], $this->generateValidationAttributes($structure));
        
        foreach ($validated as $key => $value) {
            if ($value instanceof \Illuminate\Http\UploadedFile)
            {
                $origFileName = $value->getClientOriginalName();
                $ext = $value->getClientOriginalExtension();
                $md5Name = substr(md5($origFileName), 20);
                $newname = $md5Name . rand(2, 50) . date("his") . '.' . $ext;
                $destinationPath = 'form_builder/uploads';
                $value->storeAs($destinationPath, $newname, 'azure');
                $validated[$key] = $newname;
            }
        }

        if($form->is_dynamic_url == 1)
        {
            $validated['reg_batch_code'] = $request->reg_batch_code;
        }

        if ($form->is_registration_form) {

            $allowed_old_phase = $form->allowed_old_phase;

            $validated['phase'] = DB::table('phase')
                ->where('active', 1)
                ->value('phaseid');

            $validated['profile_updated'] = \Carbon\Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s');

            $validated['password_updated'] = null;

                $existingUser = DB::table('users')
                    ->where('email', $request->email)
                    ->whereIn('role', [1,3,4,5,7,8,10,9,11,21,23,100])
                    ->first();

                if ($existingUser) {                
                    return redirect()->back()
                    ->withInput()
                    ->withErrors(['err' => 'This email is already registered.']);
                }
                
                $existingUserMobile = DB::table('users')
                    ->where('mobile', $request->mobile)
                    ->whereIn('role', [1,3,4,5,7,8,10,9,11,21,23,100])
                    ->first();

                if ($existingUserMobile) {                
                    return redirect()->back()
                    ->withInput()
                    ->withErrors(['err' => 'This mobile is already registered.']);
                }


            if($allowed_old_phase == 0)
            {
                $existingUser = DB::table('users')
                    ->where('email', $request->email)
                    ->first();

                if ($existingUser) {                
                    return redirect()->back()
                    ->withInput()
                    ->withErrors(['err' => 'This email is already registered.']);
                }
                
                $existingUserMobile = DB::table('users')
                    ->where('mobile', $request->mobile)
                    ->first();

                if ($existingUserMobile) {                
                    return redirect()->back()
                    ->withInput()
                    ->withErrors(['err' => 'This mobile is already registered.']);
                }

                $userid = DB::table('users')->insertGetId([
                    "name"=>$request->student_name,
                    "email"=>$request->email,
                    "mobile"=>$request->mobile,
                    "role"=>20,
                    "password"=>Hash::make("Test@12345"),
                    "active"=>1,
                    "authentication"=>1,
                ]);
                DB::table('student_type')->insert([
                    "userid"=>$userid,
                    "type"=>$form->student_type,
                    "password" =>0,
                    "profile" => 1,
                    "profile_complete" => 1
                ]);
            }
            else
            {
                $emailUser  = DB::table('users')->where('email', $request->email)->first();
                $mobileUser = DB::table('users')->where('mobile', $request->mobile)->first();

                $emailExists  = !is_null($emailUser);
                $mobileExists = !is_null($mobileUser);

                if (!$emailExists && !$mobileExists)
                {
                    $userid = DB::table('users')->insertGetId([
                        "name"=>$request->student_name,
                        "email"=>$request->email,
                        "mobile"=>$request->mobile,
                        "role"=>20,
                        "password"=>Hash::make("Test@12345"),
                        "active"=>1,
                        "authentication"=>1,
                    ]);
                    DB::table('student_type')->insert([
                        "userid"=>$userid,
                        "type"=>$form->student_type,
                        "password" =>0,
                        "profile" => 1,
                        "profile_complete" => 1
                    ]);
                }

                // ─── CASE C: Both exist but on DIFFERENT accounts ────────────────────────────
                elseif ($emailExists && $mobileExists && $emailUser->id !== $mobileUser->id)
                {
                    return redirect()->back()
                    ->withInput()
                    ->withErrors(['err' => 'Your email and mobile are registered with 2 different accounts.']);
                }

                else
                {
                    $user = $emailUser ?? $mobileUser;

                    $matchedFields = [];
                    if ($emailExists  && $emailUser->id  === $user->id) $matchedFields[] = 'email';
                    if ($mobileExists && $mobileUser->id === $user->id) $matchedFields[] = 'mobile';

                    $updateData = [];

                    if (in_array('mobile', $matchedFields) && !in_array('email', $matchedFields))
                    {
                        $updateData['email'] = $request->email;
                    }
                    else
                    {
                        $updateData['mobile'] = $request->mobile;
                    }

                    $check_already_reg = DB::table('form_submissions')->where('form_template_id', $form->id)->where('userid', $user->id)->exists();

                    if($check_already_reg)
                    {
                        return redirect()->back()
                        ->withInput()
                        ->withErrors(['err' => 'Your email and mobile are already registered.']);
                    }

                    if ($user->role != 20) {
                        $updateData['role'] = 20;
                    }

                    if (!empty($updateData)) {
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update($updateData);
                        $user = DB::table('users')->where('id', $user->id)->first();
                    }

                    $userid = $user->id;
                }
            }
        }
        else
        {
            $userid = 1;
        }
        
        // Review mode submission guard
        if ($form->review == 1 && !$form->is_registration_form) {
            $existingSubmission = FormSubmission::where('userid', $userid)
                ->where('form_template_id', $form->id)
                ->first();

            if ($existingSubmission) {
                if ($existingSubmission->review_status === 'approved') {
                    return redirect()->back()->with('error', 'Your submission has already been approved and cannot be changed.');
                }
                if ($existingSubmission->review_status === 'pending') {
                    return redirect()->back()->with('error', 'Your submission is currently under review. Please wait for admin feedback.');
                }
                // rejected — update existing row and reset to pending
                $existingSubmission->submission_data = $validated;
                $existingSubmission->review_status = 'pending';
                $existingSubmission->save();
                $submission = $existingSubmission;
            } else {
                $submission = FormSubmission::create([
                    'form_template_id' => $form->id,
                    'submission_data' => $validated,
                    'userid' => $userid,
                    'review_status' => 'pending',
                ]);
            }
        } else {
            $submission = FormSubmission::create([
                'form_template_id' => $form->id,
                'submission_data' => $validated,
                'userid' => $userid,
            ]);
        }

        try {
            app(\App\Http\Controllers\EmailTemplateController::class)
                ->triggerOnSubmit($form->id, $submission->id, $userid);
        } catch (\Throwable $e) {
            \Log::error('OnSubmit Email Trigger Failed', [
                'error' => $e->getMessage()
            ]);
        }

        $message = $form->success_message;
        if($message == null)
        {
            $message = 'Form submitted successfully.';
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        if($form->redirect_method === "same_page")
        {
            if ($form->is_registration_form)
            {
                return redirect()->route('thankYouFormSubmission')->with('success', $message);
            }
            return redirect()->back()->with('success', $message);
        }
        return redirect()->back()->with([
            'success'     => $message,
            'redirect_to' => $form->redirect_url,
        ]);
    }

   
    public function addressColumnLabels($field): array
    {
        $name = $field['name'] ?? '';
        $cfg  = $field['address'] ?? [];
        $base = $field['label'] ?? 'Address';

        if ($name === '') {
            return [];
        }

        $parts = ['state' => 'State', 'city' => 'City', 'pin' => 'Pin Code'];
        $sections = [
            'curr' => ['cfg' => $cfg['current'] ?? [], 'title' => 'Current'],
            'perm' => ['cfg' => $cfg['permanent'] ?? [], 'title' => 'Permanent'],
        ];

        if (empty($cfg['permanentEnabled'])) {
            unset($sections['perm']);
        }

        $labels = [];
        foreach ($sections as $prefix => $section) {
            foreach ($parts as $key => $partLabel) {
                if (!empty($section['cfg'][$key])) {
                    $labels["{$name}_{$prefix}_{$key}"] = "{$base} - {$section['title']} {$partLabel}";
                }
            }
        }

        return $labels;
    }

    private function generateValidationAttributes($formStructure)
    {
        $attributes = [];
        foreach ($formStructure as $field) {
            $name = $field->name ?? null;
            if (!$name) continue;
            $label = $field->label ?? $name;
            $attributes[$name] = $label;
            $attributes[$name . '.*'] = $label;
        }
        return $attributes;
    }

    private function generateValidationRules($formStructure, Request $request)
{
    $rules = [];

    foreach ($formStructure as $field) {
        $name = $field->name ?? null;
        if (!$name) continue;

        $type        = $field->type ?? null;

        // Display-only elements carry no submitted value
        if (in_array($type, ['title', 'description', 'new_line', 'page_break', 'download_file'], true)) {
            continue;
        }

        // Only required if the field is marked required AND its condition (if any) is met
        $isVisible  = $this->areConditionsMetServer($field, $request);
        $isRequired = (!empty($field->required) && $isVisible);

        // Address block: validate only the enabled sub-fields (state/city/pin per section)
        if ($type === 'address' && isset($field->address)) {
            $this->addAddressRules($rules, $name, $field->address, $isRequired);
            continue;
        }

        // Checkbox group
        if ($type === 'checkbox') {
            $rules[$name] = ($isRequired ? 'required' : 'nullable') . '|array';
            if ($isRequired) {
                $rules[$name] .= '|min:1';
            }
            $rules[$name . '.*'] = 'string';
            continue;
        }

        // File uploads
        if ($type === 'file') {
            $rule = $isRequired ? 'required|file' : 'nullable|file';
            if (!empty($field->extensionRequired)) {
                $exts = array_map('trim', explode(',', $field->extensionRequired));
                $exts = array_filter($exts);
                if ($exts) {
                    $rule .= '|mimes:' . implode(',', $exts);
                }
            }
            $rules[$name] = $rule;
            continue;
        }

        // Radio / Select (scalar)
        if (in_array($type, ['radio', 'select'], true)) {
            $rules[$name] = $isRequired ? 'required' : 'nullable';
            continue;
        }

        // Email
        if ($type === 'email') {
            $rules[$name] = ($isRequired ? 'required' : 'nullable') . '|email:rfc,filter';
            continue;
        }

        // Text / Tel / Number / Date / Textarea / etc.
        $rules[$name] = $isRequired ? 'required' : 'nullable';
    }

    return $rules;
}

private function addAddressRules(array &$rules, $name, $address, $isRequired)
{
    $add = function ($prefix, $comp) use (&$rules, $name, $isRequired) {
        $key = $name . '_' . $prefix . '_' . $comp;
        if ($comp === 'pin') {
            $rules[$key] = ($isRequired ? 'required' : 'nullable') . '|digits:6';
        } else {
            $rules[$key] = $isRequired ? 'required' : 'nullable';
        }
    };
    $cur = $address->current ?? null;
    if ($cur) {
        if (!empty($cur->state)) $add('curr', 'state');
        if (!empty($cur->city))  $add('curr', 'city');
        if (!empty($cur->pin))   $add('curr', 'pin');
    }
    if (!empty($address->permanentEnabled) && isset($address->permanent)) {
        $p = $address->permanent;
        if (!empty($p->state)) $add('perm', 'state');
        if (!empty($p->city))  $add('perm', 'city');
        if (!empty($p->pin))   $add('perm', 'pin');
    }
}


private function areConditionsMetServer($field, Request $request): bool
{
    $conditions = [];
    if (!empty($field->conditions) && is_array($field->conditions)) {
        $conditions = $field->conditions;
    } elseif (!empty($field->conditional)) {
        $conditions = [$field->conditional];
    }

    if (empty($conditions)) {
        return true; // no condition => visible
    }

    $logic = strtolower($field->conditionLogic ?? 'all');

    if ($logic === 'any') {
        foreach ($conditions as $c) {
            if ($this->isConditionMetServer($c, $request)) return true;
        }
        return false;
    }

    foreach ($conditions as $c) {
        if (!$this->isConditionMetServer($c, $request)) return false;
    }
    return true;
}

private function isConditionMetServer($conditional, Request $request): bool
{
    if (!$conditional || empty($conditional->field) || empty($conditional->operator)) {
        return true; // no condition => visible
    }

    $field  = $conditional->field;
    $op     = $conditional->operator;
    $target = (string) ($conditional->value ?? '');

    if ($target === '') {
        return true; // half-configured condition => don't hide/enforce
    }

    $value = $request->input($field);          // may be scalar or array (for checkbox groups)
    $isArr = is_array($value);

    $asString = static function($v) {
        return is_scalar($v) ? (string) $v : '';
    };

    $inList = static function() use ($target) {
        return array_values(array_filter(array_map('trim', explode(',', $target)), static function ($v) {
            return $v !== '';
        }));
    };

    switch ($op) {
        case 'equals':
            return $isArr
                ? in_array($target, array_map('strval', $value), true)
                : $asString($value) === $target;

        case 'not_equals':
            return $isArr
                ? !in_array($target, array_map('strval', $value), true)
                : $asString($value) !== $target;

        case 'contains':
            // arrays → membership; scalars → substring
            return $isArr
                ? in_array($target, array_map('strval', $value), true)
                : (string) str_contains($asString($value), $target);

        case 'not_contains':
            return $isArr
                ? !in_array($target, array_map('strval', $value), true)
                : !(string) str_contains($asString($value), $target);

        case 'greater_than':
            return floatval($value) > floatval($target);

        case 'less_than':
            return floatval($value) < floatval($target);

        case 'in': {
            $list = $inList();
            return $isArr
                ? count(array_intersect(array_map('strval', $value), $list)) > 0
                : in_array($asString($value), $list, true);
        }

        case 'not_in': {
            $list = $inList();
            return $isArr
                ? count(array_intersect(array_map('strval', $value), $list)) === 0
                : !in_array($asString($value), $list, true);
        }

        default:
            return true;
    }
}



    public function index(FormTemplate $formTemplate)
    {
        $this->authorize('viewSubmissions', $formTemplate);
        
        $submissions = $formTemplate->submissions()->latest()->paginate(15);
        return view('index', compact('formTemplate', 'submissions'));
    }
    
    public function view(FormSubmission $formSubmission)
    {
        $formTemplate = $formSubmission->formTemplate;
        $this->authorize('viewSubmissions', $formTemplate);
        
        return view('view', compact('formSubmission', 'formTemplate'));
    }
    
    public function destroy(FormSubmission $formSubmission)
    {
        $formTemplate = $formSubmission->formTemplate;
        $this->authorize('viewSubmissions', $formTemplate);
        
        $formSubmission->delete();
        
        return redirect()->route('index', $formTemplate->id)
            ->with('success', 'Submission deleted successfully.');
    }
    
    public function export(FormTemplate $formTemplate)
    {
        $this->authorize('viewSubmissions', $formTemplate);
        
        $submissions = $formTemplate->submissions;
        $csvData = $this->generateCsvData($formTemplate, $submissions);
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $formTemplate->slug . '-submissions.csv"',
        ];
        
        return Response::make($csvData, 200, $headers);
    }
    
    private function generateCsvData(FormTemplate $formTemplate, $submissions)
    {
        if ($submissions->isEmpty()) {
            return "No submissions found";
        }
        
        // Extract all possible fields from submissions
        $allFields = [];
        foreach ($submissions as $submission) {
            foreach ($submission->submission_data as $field => $value) {
                if (!in_array($field, $allFields)) {
                    $allFields[] = $field;
                }
            }
        }
        
        // Create CSV header
        $csvData = fopen('php://temp', 'r+');
        $header = array_merge(['Submission ID', 'Submitted At'], $allFields);
        fputcsv($csvData, $header);
        
        // Add rows
        foreach ($submissions as $submission) {
            $row = [
                $submission->id,
                $submission->created_at->format('Y-m-d H:i:s')
            ];
            
            foreach ($allFields as $field) {
                $value = $submission->submission_data[$field] ?? '';
                
                // Handle array values (like from checkboxes)
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                
                $row[] = $value;
            }
            
            fputcsv($csvData, $row);
        }
        
        rewind($csvData);
        $csvContent = stream_get_contents($csvData);
        fclose($csvData);
        
        return $csvContent;
    }
    public function formReport($id)
{
    $submissions = DB::table('form_submissions')->where('form_template_id', $id)->get();
    $edit = DB::table('form_templates')->where('id', $id)->first();

    if(empty($edit))
    {
        abort(404);
    }

    $formStructure = json_decode($edit->form_structure, true);
    if (is_string($formStructure)) {
        $formStructure = json_decode($formStructure, true);
    }
    $fieldLabels = [];
    // dd($formStructure);
    if (is_array($formStructure)) {
        foreach ($formStructure as $field) {
            if ($field['type'] == "hidden_field") continue;
            if (!empty($field['name']) && $field['type'] != 'page_break' && $field['type'] != 'description' && $field['type'] != 'new_line' && $field['type'] != 'title')
            {
                if ($field['type'] === 'address') {
                    $fieldLabels += $this->addressColumnLabels($field);
                    continue;
                }

                $fieldLabels[$field['name']] = $field['label'] ?? ucfirst($field['name']);
            }
        }
    }
    // dd($fieldLabels);
    
    $allKeys = collect();
    foreach ($submissions as $submission) {
        $data = json_decode($submission->submission_data, true);
        $allKeys = $allKeys->merge(array_keys($data));
    }
    $uniqueKeys = $allKeys->unique()->values();
   
    $title = 'View Response';

// dd($submissions);
    return view('admin.form-responce-view', [
        "title" => $title,
        'submissions' => $submissions,
        'columns' => $uniqueKeys,
        'edit' => $edit,
        'columnLabels' => $fieldLabels
    ]);
}

 public function formReportv2($id)
{
    $submissions = DB::table('form_submissions')->where('form_template_id', $id)->get();
    $edit = DB::table('form_templates')->where('id', $id)->first();
    $formStructure = json_decode($edit->form_structure, true);
    if (is_string($formStructure)) {
        $formStructure = json_decode($formStructure, true);
    }
    $fieldLabels = [];
    // dd($formStructure);
    if (is_array($formStructure)) {
        foreach ($formStructure as $field) {
            if ($field['type'] == "hidden_field") continue;
            if (!empty($field['name']) && $field['type'] != 'page_break' && $field['type'] != 'description' && $field['type'] != 'new_line' && $field['type'] != 'title')
            {
                if ($field['type'] === 'address') {
                    $fieldLabels += $this->addressColumnLabels($field);
                    continue;
                }

                $fieldLabels[$field['name']] = $field['label'] ?? ucfirst($field['name']);
            }
        }
    }
    // dd($fieldLabels);
    
    $allKeys = collect();
    foreach ($submissions as $submission) {
        $data = json_decode($submission->submission_data, true);
        $allKeys = $allKeys->merge(array_keys($data));
    }
    $uniqueKeys = $allKeys->unique()->values();
   
    $title = 'View Response';

// dd($submissions);
    return view('admin.form-response-view-v2', [
        "title" => $title,
        'submissions' => $submissions,
        'columns' => $uniqueKeys,
        'edit' => $edit,
        'columnLabels' => $fieldLabels
    ]);
}
public function ajaxResponceView(Request $request)
{
    // reg_batch_code
    $form_id = $request->id;

    if(Auth::user()->role == 3|| Auth::user()->role == 11)
    {
        $trainerid = 1;
        $submissions = DB::table('form_submissions')
                    ->select('form_submissions.*','users.name','users.email','users.mobile as user_mobile')
                    ->leftJoin('users','users.id','=','form_submissions.userid')
                    // ->leftJoin('batch_user_mapping as bum','bum.userid','=','form_submissions.userid')
                    // ->leftJoin('batch_detail as bd','bd.id','=','bum.batchid')
                    ->where('form_template_id', $form_id)
                    // ->where('users.role',2)
                    // ->whereRaw("FIND_IN_SET($trainerid,bd.assigned_to)")
                    // ->groupBy('users.id', 'form_submissions.id')
                    ->get();
    }
    else
    {
        $submissions = DB::table('form_submissions')
                    ->select('form_submissions.*','users.name','users.email','users.mobile as user_mobile')
                    ->leftJoin('users','users.id','=','form_submissions.userid')
                    ->where('form_template_id', $form_id)
                    // ->where('users.role',2)
                    ->get();
    }  


    $edit = DB::table('form_templates')->where('id', $form_id)->first();
    
    $formStructure = json_decode($edit->form_structure, true);
    if (is_string($formStructure)) {
        $formStructure = json_decode($formStructure, true);
    }
    $uniqueKeys = [];
    $fieldTypeMap = [];
    if (is_array($formStructure)) {
        foreach ($formStructure as $field) {
            if (!empty($field['name']) && $field['type'] != 'page_break' && $field['type'] != 'description' && $field['type'] != 'new_line' && $field['type'] != 'title')
            {
                // an address is stored as name_curr_state, name_perm_pin, ... not as one key
                if ($field['type'] === 'address') {
                    foreach (array_keys($this->addressColumnLabels($field)) as $addrKey) {
                        $uniqueKeys[] = $addrKey;
                        $fieldTypeMap[$addrKey] = [
                            'type'      => 'address',
                            'fieldType' => substr($addrKey, strrpos($addrKey, '_') + 1),
                        ];
                    }
                    continue;
                }

                $uniqueKeys[] = $field['name'];
                $fieldTypeMap[$field['name']] = [
                    'type'      => $field['type'],
                    'fieldType' => $field['fieldType'] ?? null,
                ];
            }
        }
    }
    // dd($submissions); reg_batch_code

    $is_dynamic_url = 0;

    if($edit->is_dynamic_url == 1)
    {
        $is_dynamic_url = 1;
    }

    $responseData = [];
    foreach ($submissions as $submission) {
        $data = json_decode($submission->submission_data, true);



        if($edit->isAnonymous == 1)
        {
            $row = [
                // 'name' => $submission->name,
                // 'email' => $submission->email,
                // 'phone' => $submission->user_mobile,
                'submission_id' => $submission->id
            ];
        }
        else
        {
            $row = [
                'name' => $submission->name,
                'email' => $submission->email,
                'phone' => $submission->user_mobile,
                'submission_id' => $submission->id
            ];
        }

        if($is_dynamic_url == 1)
        {
            $row['Batch Code'] = $data['reg_batch_code'] ?? '';
        }

        foreach ($uniqueKeys as $key) {
                $isFile = false;
                $file_url = '';

                try {                    
                    if (is_string($data[$key]))
                    {
                        $isFile = preg_match('/\.(pdf|jpe?g|png|csv|gif|bmp|xlsx?|xlsm|xlsb|docx?|pptx?|mp4)$/i', $data[$key]);
                    }

                    if($isFile)
                    {

                        $file_url = Storage::disk("azure")->url("form_builder/uploads/$data[$key]" . "?" . env('AZURE_STORAGE_SAS_TOKEN'));

                        $decodedUrl = urldecode(urldecode($file_url));
                        $parts = parse_url($decodedUrl);

                        $finalUrl = $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?' . env('AZURE_STORAGE_SAS_TOKEN');


                        $row[$key] =  "<a target='_blank' href='$finalUrl'>$data[$key]</a>";
                    }
                    else
                    {
                        // $row[$key] =  $data[$key];
                        $ftype  = $fieldTypeMap[$key]['type'] ?? null;
                        $ffield = $fieldTypeMap[$key]['fieldType'] ?? null;

                        if ($key == 'sdp_college' || ($ftype === 'selectStateCollege' && $ffield === 'college')) {

                            $college = DB::table('college')
                                        ->where('id', $data[$key])
                                        ->value('college_name');

                            $row[$key] = $college ?? $data[$key];

                        } elseif (in_array($ftype, ['selectState', 'selectCity', 'selectStateCity', 'selectStateCollege', 'address'])) {
                            if ($ffield === 'state') {
                                $row[$key] = DB::table('state')->where('id', $data[$key])->value('state') ?? $data[$key];
                            } elseif ($ffield === 'city') {
                                $row[$key] = DB::table('state_wise_cities')->where('id', $data[$key])->value('city') ?? $data[$key];
                            } else {
                                $row[$key] = $data[$key];
                            }
                        } else {
                            $row[$key] = $data[$key];
                        }
                    }
                } catch (\Throwable $th) {
                    $row[$key] = '';
                }
        }
        // die;
        $row['submission'] = \Carbon\Carbon::parse($submission->created_at)->isoFormat('D MMM YYYY HH:mm:ss');

        if($edit->scoring == 1)
        {
            // dd($edit->parameters);
            foreach(json_decode($edit->parameters) as $key=>$value)
            {
                $student_score_value = '';
                
                if($submission->scoring)
                {
                    $scoring_data = json_decode($submission->scoring);
                    // dd($scoring_data);
                    $value1 = $value->parameter;
                    $student_score_value = $scoring_data[0]->$value1??'';

                    $row[$value->parameter] = '<input type="number" data-max_weightage="'.$value->weightage.'" min="0" max="'.$value->weightage.'" step="0.1" onchange="updateScore(this)" data-form_id="'.$edit->id.'" data-submission_id="'.$submission->id.'" value="'.$student_score_value.'" data-name="'.$value->parameter.'" placeholder="'.$value->parameter.'">';

                }
                else
                {
                    $row[$value->parameter] = '<input type="number" data-max_weightage="'.$value->weightage.'" min="0" max="'.$value->weightage.'" step="0.1" onchange="updateScore(this)" data-form_id="'.$edit->id.'" data-submission_id="'.$submission->id.'" value="'.$student_score_value.'" data-name="'.$value->parameter.'" placeholder="'.$value->parameter.'">';

                }
            }
        }


        $actions = '';
        $rejectReason = '';

        if ($edit->approval_required == 1) {
            if ($submission->approval_status == 1) {
                $actions = '<span class="badge bg-success">Approved</span>';
                $rejectReason = ''; 
            } elseif ($submission->approval_status == -1) {
                $actions = '<span class="badge bg-danger">Rejected</span>';
                $rejectReason = $submission->reject_reason ?? '';
            } else {
                $actions = '
                    <button class="btn btn-sm btn-success me-1 approveBtn" data-id="' . $submission->id . '">Approve</button>
                    <button class="btn btn-sm btn-danger rejectBtn" data-id="' . $submission->id . '">Reject</button>
                ';
                $rejectReason = ''; 
            }
        } else {
            $actions = '';
            $rejectReason = '';
        }
        $row['actions'] = $actions;
        $row['rejectReason'] = $rejectReason;

        // Review action column
        if ($edit->review == 1) {
            if ($submission->review_status === 'approved') {
                $row['review_action'] = '<span class="badge bg-success">Approved</span>';
            } elseif ($submission->review_status === 'rejected') {
                $row['review_action'] = '<span class="badge bg-warning text-dark">Rejected / Awaiting resubmission</span>';
            } else {
                $row['review_action'] = '
                    <button class="btn btn-sm btn-success me-1 reviewApproveBtn" data-id="' . $submission->id . '">Approve</button>
                    <button class="btn btn-sm btn-danger reviewRejectBtn" data-id="' . $submission->id . '">Reject</button>
                ';
            }
        } else {
            $row['review_action'] = '';
        }

        // dd($row);

        $row['action'] = '<a href="' . route('form.edit', ['id' => $submission->id]) . '" class="btn btn-sm btn-primary">Edit</a>';

        $responseData[] = $row;
    }

    return response()->json(['data' => $responseData]);
}

public function assessmentFormReport($id)
{
    $submissions = DB::table('assessment_form_submission')->where('form_template_id', $id)->get();
    $edit = DB::table('form_templates')->where('id', $id)->first();

    if(empty($edit))
    {
        abort(404);
    }

    $formStructure = json_decode($edit->form_structure, true);
    if (is_string($formStructure)) {
        $formStructure = json_decode($formStructure, true);
    }
    $fieldLabels = [];
    if (is_array($formStructure)) {
        foreach ($formStructure as $field) {
            if ($field['type'] == "hidden_field") continue;
            if (!empty($field['name']) && $field['type'] != 'page_break' && $field['type'] != 'description' && $field['type'] != 'new_line' && $field['type'] != 'title')
            {
                if ($field['type'] === 'address') {
                    $fieldLabels += $this->addressColumnLabels($field);
                    continue;
                }

                $fieldLabels[$field['name']] = $field['label'] ?? ucfirst($field['name']);
            }
        }
    }

    $allKeys = collect();
    foreach ($submissions as $submission) {
        $data = json_decode($submission->submission_data, true);
        $allKeys = $allKeys->merge(array_keys($data));
    }
    $uniqueKeys = $allKeys->unique()->values();

    $title = 'View Response';

    return view('admin.assessment-form-report', [
        "title" => $title,
        'submissions' => $submissions,
        'columns' => $uniqueKeys,
        'edit' => $edit,
        'columnLabels' => $fieldLabels
    ]);
}

public function assessmentAjaxResponceView(Request $request)
{
    // reg_batch_code
    $form_id = $request->id;

    if(Auth::user()->role == 3|| Auth::user()->role == 11)
    {
        $trainerid = 1;
        $submissions = DB::table('assessment_form_submission')
                    ->select('assessment_form_submission.*','users.name','users.email','users.mobile as user_mobile')
                    ->leftJoin('users','users.id','=','assessment_form_submission.userid')
                    // ->leftJoin('batch_user_mapping as bum','bum.userid','=','assessment_form_submission.userid')
                    // ->leftJoin('batch_detail as bd','bd.id','=','bum.batchid')
                    ->where('form_template_id', $form_id)
                    // ->where('users.role',2)
                    // ->whereRaw("FIND_IN_SET($trainerid,bd.assigned_to)")
                    // ->groupBy('users.id', 'form_submissions.id')
                    ->get();
    }
    else
    {
        $submissions = DB::table('assessment_form_submission')
                    ->select('assessment_form_submission.*','users.name','users.email','users.mobile as user_mobile')
                    ->leftJoin('users','users.id','=','assessment_form_submission.userid')
                    ->where('form_template_id', $form_id)
                    // ->where('users.role',2)
                    ->get();
    }  


    $edit = DB::table('form_templates')->where('id', $form_id)->first();
    
    $formStructure = json_decode($edit->form_structure, true);
    if (is_string($formStructure)) {
        $formStructure = json_decode($formStructure, true);
    }
    $uniqueKeys = [];
    $fieldTypeMap = [];
    if (is_array($formStructure)) {
        foreach ($formStructure as $field) {
            if (!empty($field['name']) && $field['type'] != 'page_break' && $field['type'] != 'description' && $field['type'] != 'new_line' && $field['type'] != 'title')
            {
                // an address is stored as name_curr_state, name_perm_pin, ... not as one key
                if ($field['type'] === 'address') {
                    foreach (array_keys($this->addressColumnLabels($field)) as $addrKey) {
                        $uniqueKeys[] = $addrKey;
                        $fieldTypeMap[$addrKey] = [
                            'type'      => 'address',
                            'fieldType' => substr($addrKey, strrpos($addrKey, '_') + 1),
                        ];
                    }
                    continue;
                }

                $uniqueKeys[] = $field['name'];
                $fieldTypeMap[$field['name']] = [
                    'type'      => $field['type'],
                    'fieldType' => $field['fieldType'] ?? null,
                ];
            }
        }
    }
    // dd($submissions); reg_batch_code

    $is_dynamic_url = 0;

    if($edit->is_dynamic_url == 1)
    {
        $is_dynamic_url = 1;
    }

    $responseData = [];
    foreach ($submissions as $submission) {
        $data = json_decode($submission->submission_data, true);



        if($edit->isAnonymous == 1)
        {
            $row = [
                // 'name' => $submission->name,
                // 'email' => $submission->email,
                // 'phone' => $submission->user_mobile,
                'submission_id' => $submission->id
            ];
        }
        else
        {
            $row = [
                'name' => $submission->name,
                'email' => $submission->email,
                'phone' => $submission->user_mobile,
                'submission_id' => $submission->id
            ];
        }

        if($is_dynamic_url == 1)
        {
            $row['Batch Code'] = $data['reg_batch_code'] ?? '';
        }

        foreach ($uniqueKeys as $key) {
                $isFile = false;
                $file_url = '';

                try {                    
                    if (is_string($data[$key]))
                    {
                        $isFile = preg_match('/\.(pdf|jpe?g|png|csv|gif|bmp|xlsx?|xlsm|xlsb|docx?|pptx?|mp4)$/i', $data[$key]);
                    }

                    if($isFile)
                    {

                        $file_url = Storage::disk("azure")->url("form_builder/uploads/$data[$key]" . "?" . env('AZURE_STORAGE_SAS_TOKEN'));

                        $decodedUrl = urldecode(urldecode($file_url));
                        $parts = parse_url($decodedUrl);

                        $finalUrl = $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?' . env('AZURE_STORAGE_SAS_TOKEN');


                        $row[$key] =  "<a target='_blank' href='$finalUrl'>$data[$key]</a>";
                    }
                    else
                    {
                        // $row[$key] =  $data[$key];
                        $ftype  = $fieldTypeMap[$key]['type'] ?? null;
                        $ffield = $fieldTypeMap[$key]['fieldType'] ?? null;

                        if ($key == 'sdp_college' || ($ftype === 'selectStateCollege' && $ffield === 'college')) {

                            $college = DB::table('college')
                                        ->where('id', $data[$key])
                                        ->value('college_name');

                            $row[$key] = $college ?? $data[$key];

                        } elseif (in_array($ftype, ['selectState', 'selectCity', 'selectStateCity', 'selectStateCollege', 'address'])) {
                            if ($ffield === 'state') {
                                $row[$key] = DB::table('state')->where('id', $data[$key])->value('state') ?? $data[$key];
                            } elseif ($ffield === 'city') {
                                $row[$key] = DB::table('state_wise_cities')->where('id', $data[$key])->value('city') ?? $data[$key];
                            } else {
                                $row[$key] = $data[$key];
                            }
                        } else {
                            $row[$key] = $data[$key];
                        }
                    }
                } catch (\Throwable $th) {
                    $row[$key] = '';
                }
        }
        // die;
        $row['submission'] = \Carbon\Carbon::parse($submission->created_at)->isoFormat('D MMM YYYY HH:mm:ss');

        if($edit->scoring == 1)
        {
            // dd($edit->parameters);
            foreach(json_decode($edit->parameters) as $key=>$value)
            {
                $student_score_value = '';
                
                if($submission->scoring)
                {
                    $scoring_data = json_decode($submission->scoring);
                    // dd($scoring_data);
                    $value1 = $value->parameter;
                    $student_score_value = $scoring_data[0]->$value1??'';

                    $row[$value->parameter] = '<input type="number" data-max_weightage="'.$value->weightage.'" min="0" max="'.$value->weightage.'" step="0.1" onchange="updateScore(this)" data-form_id="'.$edit->id.'" data-submission_id="'.$submission->id.'" value="'.$student_score_value.'" data-name="'.$value->parameter.'" placeholder="'.$value->parameter.'">';

                }
                else
                {
                    $row[$value->parameter] = '<input type="number" data-max_weightage="'.$value->weightage.'" min="0" max="'.$value->weightage.'" step="0.1" onchange="updateScore(this)" data-form_id="'.$edit->id.'" data-submission_id="'.$submission->id.'" value="'.$student_score_value.'" data-name="'.$value->parameter.'" placeholder="'.$value->parameter.'">';

                }
            }
        }


        $actions = '';
        $rejectReason = '';

        if ($edit->approval_required == 1) {
            if ($submission->approval_status == 1) {
                $actions = '<span class="badge bg-success">Approved</span>';
                $rejectReason = ''; 
            } elseif ($submission->approval_status == -1) {
                $actions = '<span class="badge bg-danger">Rejected</span>';
                $rejectReason = $submission->reject_reason ?? '';
            } else {
                $actions = '
                    <button class="btn btn-sm btn-success me-1 approveBtn" data-id="' . $submission->id . '">Approve</button>
                    <button class="btn btn-sm btn-danger rejectBtn" data-id="' . $submission->id . '">Reject</button>
                ';
                $rejectReason = ''; 
            }
        } else {
            $actions = '';
            $rejectReason = '';
        }
        $row['actions'] = $actions;
        $row['rejectReason'] = $rejectReason;

        // Review action column
        if ($edit->review == 1) {
            if ($submission->review_status === 'approved') {
                $row['review_action'] = '<span class="badge bg-success">Approved</span>';
            } elseif ($submission->review_status === 'rejected') {
                $row['review_action'] = '<span class="badge bg-warning text-dark">Rejected / Awaiting resubmission</span>';
            } else {
                $row['review_action'] = '
                    <button class="btn btn-sm btn-success me-1 reviewApproveBtn" data-id="' . $submission->id . '">Approve</button>
                    <button class="btn btn-sm btn-danger reviewRejectBtn" data-id="' . $submission->id . '">Reject</button>
                ';
            }
        } else {
            $row['review_action'] = '';
        }

        // dd($row);

        $row['action'] = '<a href="' . route('form.edit', ['id' => $submission->id]) . '" class="btn btn-sm btn-primary">Edit</a>';

        $responseData[] = $row;
    }

    return response()->json(['data' => $responseData]);
}


public function approveSubmission($id)
{
    DB::table('form_submissions')->where('id', $id)->update(['approval_status' => 1]);
    return response()->json(['success' => true]);
}

public function reviewApprove($id)
{
    if (Auth::user()->role != 1 && Auth::user()->role != 10) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
    }
    DB::table('form_submissions')->where('id', $id)->update(['review_status' => 'approved']);
    return response()->json(['success' => true]);
}

public function reviewReject($id)
{
    if (Auth::user()->role != 1 && Auth::user()->role != 10) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
    }

    $submission = DB::table('form_submissions')->where('id', $id)->first();

    DB::table('form_submissions')->where('id', $id)->update(['review_status' => 'rejected']);

    // Reset topic completion so form reappears in student navigation
    if ($submission) {
        DB::table('user_topic_completion')
            ->where('userid', $submission->userid)
            ->where('topicid', $submission->form_template_id)
            ->update(['completion' => 0]);
    }

    return response()->json(['success' => true]);
}

public function rejectSubmission(Request $request,$id)
{
    DB::table('form_submissions')->where('id', $id)->update(['approval_status' => -1,'reject_reason' => $request->reject_reason,]);
    return response()->json(['success' => true]);
}


public function edit($id){
    // $form = DB::table('form_submissions')
    //         ->select('form_submissions.*','form_templates.title','form_templates.description','form_templates.form_structure','form_templates.slug')
    //         ->leftJoin('form_templates' , 'form_templates.id','=','form_submissions.form_template_id')    
    //         ->where('form_submissions.id',$id)
    //         ->first();
    //         dd(json_decode($form->form_structure));
            $role=Auth::user()->role;
            $user = auth()->user();
           
          
            if($role==1 || $role==3 || $role==10)
                $form = FormTemplate::leftJoin('form_submissions' , 'form_templates.id','=','form_submissions.form_template_id')
                ->where('form_submissions.id',$id)
                ->select('form_submissions.*','form_templates.title','form_templates.description','form_templates.form_structure','form_templates.slug')
                ->firstOrFail();
            else{
                $form = FormTemplate::leftJoin('form_submissions' , 'form_templates.id','=','form_submissions.form_template_id')
                ->where('form_submissions.id', $id) 
                ->where('form_submissions.userid', $user->id)            
                ->select('form_submissions.*','form_templates.title','form_templates.description','form_templates.form_structure','form_templates.slug')
                ->firstOrFail();
            }
            $submittedData = json_decode($form->submission_data, true); 
           

            // dd($submittedData);

return view('edit-responce', ["title"=>"Form Builder", "form"=>$form,"submittedData"=>$submittedData, "submit_btn_txt"=>$form->submit_btn_txt]);
}
public function update(Request $request, string $slug, int $id)
{
    $form = FormTemplate::where('slug', $slug)->firstOrFail();
    $submission = FormSubmission::findOrFail($id);

    // Build validation rules (base)
    $structure = json_decode($form->form_structure);
    $validationRules = $this->generateValidationRules($structure, $request);

    // For FILE fields on EDIT: allow keeping existing file (nullable),
    // and optionally restrict by extension.
    $existingRaw  = $submission->submission_data;
    $existingData = is_array($existingRaw) ? $existingRaw : (json_decode($existingRaw, true) ?: []);

    foreach ($structure as $field) {
        if (!isset($field->name, $field->type)) continue;

        if ($field->type === 'file') {
            // default rule for edit
            $rule = 'nullable|file';

            // add mimes if you configured extensionRequired in builder
            if (!empty($field->extensionRequired)) {
                $exts = array_map('trim', explode(',', $field->extensionRequired));
                $rule .= '|mimes:' . implode(',', $exts);
            }

            // if this file was originally required AND no existing file is present,
            // make it required on edit as well
            $hasExisting = !empty($existingData[$field->name]);
            if (!empty($field->required) && !$hasExisting) {
                $rule = 'required|file' . (empty($exts) ? '' : ('|mimes:' . implode(',', $exts)));
            }

            $validationRules[$field->name] = $rule;
        }
    }

    // Validate
    $validated = $request->validate($validationRules);

    // Handle new uploads (store to Azure like in store())
    foreach ($request->allFiles() as $key => $file) {
        if ($file instanceof \Illuminate\Http\UploadedFile) {
            $origFileName = $file->getClientOriginalName();
            $ext         = $file->getClientOriginalExtension();
            $md5Name     = substr(md5($origFileName), 20);
            $newname     = $md5Name . rand(2, 50) . date("his") . '.' . $ext;
            $destinationPath = 'form_builder/uploads';
            $file->storeAs($destinationPath, $newname, 'azure');
            $validated[$key] = $newname;
        }
    }

    // Preserve existing file if user didn't upload a new one
    foreach ($structure as $field) {
        if (!isset($field->name, $field->type)) continue;
        if ($field->type === 'file') {
            if (!array_key_exists($field->name, $validated)) {
                // prefer hidden *_existing value (sent by the edit renderer), else old DB value
                $validated[$field->name] = $request->input($field->name . '_existing', $existingData[$field->name] ?? null);
            }
        }
    }

    // Save

    if(Auth::user()->role==2 || Auth::user()->role==11|| Auth::user()->role==20 || Auth::user()->role==21){      
    
            FormSubmission::where('id', $id)->update([
                'form_template_id' => $form->id,
                'submission_data'  => $validated,
                'userid'           => Auth::user()->id,
            ]);

        return redirect()->back()->with('success', 'Form updated successfully.');
    }else{
        
     FormSubmission::where('id', $id)->update([
                'form_template_id' => $form->id,
                'submission_data'  => $validated,
                'userid'           => $submission->userid,
            ]);



        return redirect()->route('form-report', $form->id)->with('success', 'Form updated successfully.');
    }

}


    public function FDPRegistrationFormReport($id)
    {
        $form = DB::table('form_templates')->where('id', $id)->first();

            $columns = [];
            if ($form && $form->form_structure) {
                $rawStructure = json_decode($form->form_structure, true);
        
                if (is_string($rawStructure)) {
                    $structure = json_decode($rawStructure, true);
                } else {
                    $structure = $rawStructure;
                }
        
                if (is_array($structure)) {
                    foreach ($structure as $field) {
                        $columns[] = [
                            'label' => $field['label'] ?? ucfirst($field['name']),
                            'name' => $field['name']
                        ];
                    }
                }
            }
        $title = ' FDP registration View ';

    
    
        return view('admin.FDP-form-responce-view', [
            "title" => $title,
            'columns' => $columns,
        ]);
    }
    
    public function ajaxFDPResponceView(Request $request)
    {
        $submissions = DB::table('student_profile')
    ->select(
        'student_profile.*',
        'users.name',
        'users.email',
        'users.mobile as user_mobile',
        'state_home.state as home_state_name',
        'city_home.city as home_city_name',
        'state_college.state as college_state_name',
        'city_college.city as college_city_name'
    )
    ->leftJoin('users', 'users.id', '=', 'student_profile.userid')
    ->leftJoin('state as state_home', 'state_home.id', '=', 'student_profile.state')
    ->leftJoin('state_wise_cities as city_home', 'city_home.id', '=', 'student_profile.city')
    ->leftJoin('state as state_college', 'state_college.id', '=', 'student_profile.college_state')
    ->leftJoin('state_wise_cities as city_college', 'city_college.id', '=', 'student_profile.college_city')
    ->where('users.role', 6)
    ->get();

        
             foreach($submissions as $value){
                if($value->unique_id_proof != null)
				{
                    $picurl = Storage::disk('azure')->url("unique_id_proof/$value->unique_id_proof?" . env('AZURE_STORAGE_SAS_TOKEN'));
                    $Download_img = "<a class='btn-sm btn-primary' href='$picurl' download>Download</a>";

				}
				else
				{
					$Download_img = '';
				}

                $data[] = array(
                    $value->registration_date,
                    $value->name,
                    $value->email,
                    $value->user_mobile,
                    $value->dob,
                    $value->gender,
                    $Download_img,
                    $value->unique_id_proof_number,
                    $value->address,
                    $value->home_state_name,
                    $value->home_city_name,
                    $value->pin,
                    $value->institute_name,
                    $value->college_state_name,
                    $value->college_city_name,
                    $value->college_pin,
                );
             }           
                       
    
        return response()->json(['data' => $data]);
    }

   

    public function updateFormBuilderScore(Request $request)
    {
        $prev_data =  DB::table('form_submissions')->where('id', $request->submission_id)
        ->where('form_template_id', $request->form_id)->first();

        if($prev_data->scoring)
        {
            $data = json_decode($prev_data->scoring, true);
        }
        else
        {
            $data = [];
        }

        $newKey   = $request->input_name;
        $newValue = $request->input_value;

        $found = false;

        foreach ($data as &$item) {
            if (array_key_exists($newKey, $item)) {
                $item[$newKey] = $newValue;
                $found = true;
                break;
            }
        }
        if (!$found) {
            if (!empty($data)) {
                $data[0][$newKey] = $newValue;
            } else {
                $data[] = [$newKey => $newValue];
            }
        }

        $updated_json = json_encode($data);

        DB::table('form_submissions')
            ->where('id', $request->submission_id)
            ->where('form_template_id', $request->form_id)
            ->update(['scoring' => $updated_json]);

        return response()->json(['status' => 'success']);
    }

    // Workflow: persist remaining form-section time
    public function syncFormTimer(Request $request)
    {
        $request->validate([
            'form_template_id'  => 'required|integer',
            'remaining_seconds' => 'required|integer|min:0',
        ]);

        $formId    = (int) $request->form_template_id;
        $remaining = (int) $request->remaining_seconds;

        // Overall-time mode: persist the shared countdown, not the per-section column.
        $workflow = DB::table('assessment_workflow')
            ->whereRaw('FIND_IN_SET(?, form_ids)', [$formId])
            ->first();
        if ($workflow && ($workflow->timing ?? 'sequence') === 'overall') {
            DB::table('workflow_proctor_session')
                ->where('user_id', 1)
                ->where('workflow_id', $workflow->id)
                ->update(['overall_seconds' => max(0, $remaining), 'updated_at' => now()]);
            return response()->json(['success' => true]);
        }

        DB::table('form_attempt_timer')->updateOrInsert(
            ['user_id' => 1, 'form_template_id' => $formId],
            ['remaining_seconds' => $remaining, 'attempt_number' => $this->formAttemptNumber($formId, (int) 1), 'updated_at' => now()]
        );
        return response()->json(['success' => true]);
    }

    /** Current workflow attempt number for this form + user. */
    private function formAttemptNumber(int $formId, int $userId): int
    {
        $wf = DB::table('assessment_workflow')
            ->whereRaw('FIND_IN_SET(?, form_ids)', [$formId])
            ->first();
        if (!$wf) return 1;
        $n = DB::table('workflow_proctor_session')
            ->where('user_id', $userId)->where('workflow_id', $wf->id)
            ->orderBy('id','desc')
            ->value('attempt_number');
        return $n ? (int) $n : 1;
    }

    public function workflowStore(Request $request, string $slug, int $workflow_id)
    {
        $form = FormTemplate::where('slug', $slug)->firstOrFail();

        $validationRules = $this->generateValidationRules(json_decode($form->form_structure), $request);
        $validated = $request->validate($validationRules);

        foreach ($validated as $key => $value) {
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                $origFileName = $value->getClientOriginalName();
                $ext = $value->getClientOriginalExtension();
                $md5Name = substr(md5($origFileName), 20);
                $newname = $md5Name . rand(2, 50) . date("his") . '.' . $ext;
                $destinationPath = 'form_builder/uploads';
                $value->storeAs($destinationPath, $newname, 'azure');
                $validated[$key] = $newname;
            }
        }

        $userid  = 1;
        $attempt = $this->formAttemptNumber((int) $form->id, (int) $userid);

        // dd($attempt);

        // Store into assessment_form_submission instead of form_submissions
        if ($form->review == 1) {
            $existingSubmission = DB::table('assessment_form_submission')
                ->where('userid', $userid)
                ->where('form_template_id', $form->id)
                ->where('attempt_number', $attempt)
                ->first();

            if ($existingSubmission) {
                if ($existingSubmission->review_status === 'approved') {
                    return response()->json(['success' => false, 'message' => 'Your submission has already been approved and cannot be changed.'], 422);
                }
                if ($existingSubmission->review_status === 'pending') {
                    return response()->json(['success' => false, 'message' => 'Your submission is currently under review. Please wait for admin feedback.'], 422);
                }
                // rejected — update and reset to pending
                DB::table('assessment_form_submission')
                    ->where('id', $existingSubmission->id)
                    ->update([
                        'submission_data' => json_encode($validated),
                        'review_status'   => 'pending',
                        'updated_at'      => now(),
                    ]);
            } else {
                DB::table('assessment_form_submission')->insert([
                    'form_template_id' => $form->id,
                    'submission_data'  => json_encode($validated),
                    'assessment_workflow_id' => $workflow_id,
                    'userid'           => $userid,
                    'review_status'    => 'pending',
                    'attempt_number'   => $attempt,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        } else {
            DB::table('assessment_form_submission')->insert([
                'form_template_id' => $form->id,
                'submission_data'  => json_encode($validated),
                'assessment_workflow_id' => $workflow_id,
                'userid'           => $userid,
                'attempt_number'   => $attempt,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        // try {
        //     app(\App\Http\Controllers\EmailTemplateController::class)
        //         ->triggerOnSubmit($form->id, null, $userid);
        // } catch (\Throwable $e) {
        //     \Log::error('OnSubmit Email Trigger Failed (workflow)', ['error' => $e->getMessage()]);
        // }

        $message = $form->success_message ?? 'Form submitted successfully.';

        return response()->json(['success' => true, 'message' => $message]);
    }


    public function workflowFormReport($id)
    {
        $submissions = DB::table('assessment_form_submission')->where('form_template_id', $id)->get();
        $edit = DB::table('form_templates')->where('id', $id)->first();
        $formStructure = json_decode($edit->form_structure, true);
        if (is_string($formStructure)) {
            $formStructure = json_decode($formStructure, true);
        }
        $fieldLabels = [];
        // dd($formStructure);
        if (is_array($formStructure)) {
            foreach ($formStructure as $field) {
                if ($field['type'] == "hidden_field")
                    continue;
                if (!empty($field['name']) && $field['type'] != 'page_break' && $field['type'] != 'description' && $field['type'] != 'new_line' && $field['type'] != 'title') {
                    $fieldLabels[$field['name']] = $field['label'] ?? ucfirst($field['name']);
                }
            }
        }
        // dd($fieldLabels);

        $allKeys = collect();
        foreach ($submissions as $submission) {
            $data = json_decode($submission->submission_data, true);
            $allKeys = $allKeys->merge(array_keys($data));
        }
        $uniqueKeys = $allKeys->unique()->values();

        $title = 'View Response';

        // dd($submissions);
        return view('admin.workflow-form-report', [
            "title" => $title,
            'submissions' => $submissions,
            'columns' => $uniqueKeys,
            'edit' => $edit,
            'columnLabels' => $fieldLabels
        ]);
    }

    public function workflowAjaxResponceView(Request $request)
{
    
    $form_id = $request->id;

    if(Auth::user()->role == 3|| Auth::user()->role == 11)
    {
        $trainerid = 1;
        $submissions = DB::table('assessment_form_submission')
                    ->select('assessment_form_submission.*','users.name','users.email','users.mobile as user_mobile')
                    ->leftJoin('users','users.id','=','assessment_form_submission.userid')
                   
                    ->where('form_template_id', $form_id)
                  
                    ->get();
    }
    else
    {
        $submissions = DB::table('assessment_form_submission')
                    ->select('assessment_form_submission.*','users.name','users.email','users.mobile as user_mobile')
                    ->leftJoin('users','users.id','=','assessment_form_submission.userid')
                    ->where('form_template_id', $form_id)
                    // ->where('users.role',2)
                    ->get();
    }  


    $edit = DB::table('form_templates')->where('id', $form_id)->first();
    
    $formStructure = json_decode($edit->form_structure, true);
    if (is_string($formStructure)) {
        $formStructure = json_decode($formStructure, true);
    }
    $uniqueKeys = [];
    $fieldTypeMap = [];
    if (is_array($formStructure)) {
        foreach ($formStructure as $field) {
            if (!empty($field['name']) && $field['type'] != 'page_break' && $field['type'] != 'description' && $field['type'] != 'new_line' && $field['type'] != 'title')
            {
                // an address is stored as name_curr_state, name_perm_pin, ... not as one key
                if ($field['type'] === 'address') {
                    foreach (array_keys($this->addressColumnLabels($field)) as $addrKey) {
                        $uniqueKeys[] = $addrKey;
                        $fieldTypeMap[$addrKey] = [
                            'type'      => 'address',
                            'fieldType' => substr($addrKey, strrpos($addrKey, '_') + 1),
                        ];
                    }
                    continue;
                }

                $uniqueKeys[] = $field['name'];
                $fieldTypeMap[$field['name']] = [
                    'type'      => $field['type'],
                    'fieldType' => $field['fieldType'] ?? null,
                ];
            }
        }
    }
    // dd($submissions);

    $is_dynamic_url = 0;

    if($edit->is_dynamic_url == 1)
    {
        $is_dynamic_url = 1;
    }

    $responseData = [];
    foreach ($submissions as $submission) {
        $data = json_decode($submission->submission_data, true);



        if($edit->isAnonymous == 1)
        {
            $row = [
                // 'name' => $submission->name,
                // 'email' => $submission->email,
                // 'phone' => $submission->user_mobile,
                'submission_id' => $submission->id
            ];
        }
        else
        {
            $row = [
                'name' => $submission->name,
                'email' => $submission->email,
                'phone' => $submission->user_mobile,
                'submission_id' => $submission->id
            ];
        }

        if($is_dynamic_url == 1)
        {
            $row['Batch Code'] = $data['reg_batch_code'];
        }

        foreach ($uniqueKeys as $key) {
                $isFile = false;
                $file_url = '';

                try {                    
                    if (is_string($data[$key]))
                    {
                        $isFile = preg_match('/\.(pdf|jpe?g|png|csv|gif|bmp|xlsx?|xlsm|xlsb|docx?|pptx?|mp4)$/i', $data[$key]);
                    }

                    if($isFile)
                    {

                        $file_url = Storage::disk("azure")->url("form_builder/uploads/$data[$key]" . "?" . env('AZURE_STORAGE_SAS_TOKEN'));

                        $decodedUrl = urldecode(urldecode($file_url));
                        $parts = parse_url($decodedUrl);

                        $finalUrl = $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?' . env('AZURE_STORAGE_SAS_TOKEN');


                        $row[$key] =  "<a target='_blank' href='$finalUrl'>$data[$key]</a>";
                    }
                    else
                    {
                        // $row[$key] =  $data[$key];
                        $ftype  = $fieldTypeMap[$key]['type'] ?? null;
                        $ffield = $fieldTypeMap[$key]['fieldType'] ?? null;

                        if ($key == 'sdp_college' || ($ftype === 'selectStateCollege' && $ffield === 'college')) {

                            $college = DB::table('college')
                                        ->where('id', $data[$key])
                                        ->value('college_name');

                            $row[$key] = $college ?? $data[$key];

                        } elseif (in_array($ftype, ['selectState', 'selectCity', 'selectStateCity', 'selectStateCollege', 'address'])) {
                            if ($ffield === 'state') {
                                $row[$key] = DB::table('state')->where('id', $data[$key])->value('state') ?? $data[$key];
                            } elseif ($ffield === 'city') {
                                $row[$key] = DB::table('state_wise_cities')->where('id', $data[$key])->value('city') ?? $data[$key];
                            } else {
                                $row[$key] = $data[$key];
                            }
                        } else {
                            $row[$key] = $data[$key];
                        }
                    }
                } catch (\Throwable $th) {
                    $row[$key] = '';
                }
        }
        // die;
        $row['submission'] = \Carbon\Carbon::parse($submission->created_at)->isoFormat('D MMM YYYY HH:mm:ss');

        if($edit->scoring == 1)
        {
            // dd($edit->parameters);
            foreach(json_decode($edit->parameters) as $key=>$value)
            {
                $student_score_value = '';
                
                if($submission->scoring)
                {
                    $scoring_data = json_decode($submission->scoring);
                    // dd($scoring_data);
                    $value1 = $value->parameter;
                    $student_score_value = $scoring_data[0]->$value1??'';

                    $row[$value->parameter] = '<input type="number" data-max_weightage="'.$value->weightage.'" min="0" max="'.$value->weightage.'" step="0.1" onchange="updateScore(this)" data-form_id="'.$edit->id.'" data-submission_id="'.$submission->id.'" value="'.$student_score_value.'" data-name="'.$value->parameter.'" placeholder="'.$value->parameter.'">';

                }
                else
                {
                    $row[$value->parameter] = '<input type="number" data-max_weightage="'.$value->weightage.'" min="0" max="'.$value->weightage.'" step="0.1" onchange="updateScore(this)" data-form_id="'.$edit->id.'" data-submission_id="'.$submission->id.'" value="'.$student_score_value.'" data-name="'.$value->parameter.'" placeholder="'.$value->parameter.'">';

                }
            }
        }


        $actions = '';
        $rejectReason = '';

        if ($edit->approval_required == 1) {
            if ($submission->approval_status == 1) {
                $actions = '<span class="badge bg-success">Approved</span>';
                $rejectReason = ''; 
            } elseif ($submission->approval_status == -1) {
                $actions = '<span class="badge bg-danger">Rejected</span>';
                $rejectReason = $submission->reject_reason ?? '';
            } else {
                $actions = '
                    <button class="btn btn-sm btn-success me-1 approveBtn" data-id="' . $submission->id . '">Approve</button>
                    <button class="btn btn-sm btn-danger rejectBtn" data-id="' . $submission->id . '">Reject</button>
                ';
                $rejectReason = ''; 
            }
        } else {
            $actions = '';
            $rejectReason = '';
        }
        $row['actions'] = $actions;
        $row['rejectReason'] = $rejectReason;

        // Review action column
        if ($edit->review == 1) {
            if ($submission->review_status === 'approved') {
                $row['review_action'] = '<span class="badge bg-success">Approved</span>';
            } elseif ($submission->review_status === 'rejected') {
                $row['review_action'] = '<span class="badge bg-warning text-dark">Rejected / Awaiting resubmission</span>';
            } else {
                $row['review_action'] = '
                    <button class="btn btn-sm btn-success me-1 reviewApproveBtn" data-id="' . $submission->id . '">Approve</button>
                    <button class="btn btn-sm btn-danger reviewRejectBtn" data-id="' . $submission->id . '">Reject</button>
                ';
            }
        } else {
            $row['review_action'] = '';
        }

        // dd($row);

        $row['action'] = '<a href="' . route('form.edit', ['id' => $submission->id]) . '" class="btn btn-sm btn-primary">Edit</a>';

        $responseData[] = $row;
    }

    return response()->json(['data' => $responseData]);
}

}
