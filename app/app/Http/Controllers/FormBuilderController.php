<?php

namespace App\Http\Controllers;

use App\Models\FormTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use DB;
use App\Models\FormSubmission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class FormBuilderController extends Controller
{
    public function index()
    {
        if (!Auth::user()) {
            return redirect()->route('login');
        }
        if(!in_array(Auth::user()->role, [1,3,10]))
        {
            return redirect('login');
        }
        

        // where('user_id', 1)->
        $forms = FormTemplate::where('active', 1)->latest()->get();

        return view('form-builder.index', ["title"=>"Form Builder", "forms"=>$forms]);
    }

    public function getStateDetailsByType(Request $request)
    {
        if($request->type == "city")
{
            $query = DB::table('state_wise_cities')->select('city as name', 'id');
            if ($request->state_id) {
                $query->where('state_id', $request->state_id);
            }
            $data = $query->orderBy('city', 'asc')->get();
        }
        else
        {
            $data = DB::table('state')->select('state as name', 'id')->orderBy('state', 'asc')->get();
        }
        return $data;
    }

    public function getTradeDetailsByType(Request $request)
    {
        try {            
            if($request->type == "nsti")
            {
                $data = DB::table('trade')->select('trade as name', 'id')->orderBy('trade', 'asc')->get();
            }
            else
            {
                $data = DB::table('trade')->select('trade as name', 'id')->orderBy('trade', 'asc')->get();
            }
            return $data;
            
        } catch (\Throwable $th) {
            return [];
        }
    }

    public function getCollegeDetailsByType(Request $request)
    {
        // if($request->type == "SDP")
        // {

        if($request->phase != null && $request->phase != "undefined")
        {
            $phaseid = $request->phase;
        }
        else
        {
            $phase = DB::table('phase')->where('active', 1)->first();
            $phaseid = $phase->phaseid;
        }
            $colleges = DB::table('college')->select('college_name as name', 'id')
            ->where('status', 1)
            ->where('phase', $phaseid)
            ->orderBy('college_name', 'asc')->get();

            return $colleges;
        // }
    }

    
    // public function getCollegesByState(Request $request)
    // {
    //     $stateName = DB::table('state')->where('id', $request->state_id)->value('state');

    //     $colleges = DB::table('college')
    //         ->leftJoin('location_detail', 'location_detail.id', '=', 'college.location')
    //         ->where('college.status', 1)
    //         ->when($request->phase && $request->phase != "undefined", function ($q) use ($request) {
    //             $q->where('college.phase', $request->phase);
    //         })
    //         ->when($request->state_id, function ($q) use ($stateName) {
    //             $q->where(function ($sub) use ($stateName) {
    //                 if ($stateName) {
    //                     // FK rows: college.location -> location_detail.state matches
    //                     $sub->whereRaw('LOWER(TRIM(location_detail.id)) COLLATE utf8mb4_general_ci = ?', [strtolower(trim($stateName))])
    //                         // Fallback: some rows store the state name directly in college.location
    //                         ->orWhereRaw('LOWER(TRIM(college.location)) COLLATE utf8mb4_general_ci = ?', [strtolower(trim($stateName))]);
    //                 } else {
    //                     $sub->whereRaw('1 = 0');
    //                 }
    //             });
    //         })
    //         ->select('college.college_name as name', 'college.id')
    //         ->orderBy('college.college_name', 'asc')
    //         ->distinct()
    //         ->get();

    //     return $colleges;
    // }
    public function getCollegesByState(Request $request)
{
    $stateName = DB::table('state')
        ->where('id', $request->state_id)
        ->value('state');
        // dd($stateName);

    if (!$stateName) {
        return [];
    }

    $activePhase = DB::table('phase')
        ->where('active', 1)
        ->value('phaseid');

    $locationIds = DB::table('location_detail')
        ->whereRaw('LOWER(TRIM(state)) = ?', [strtolower(trim($stateName))])
        ->pluck('id');

    $colleges = DB::table('college')
        ->where('status', 1)
        ->where('phase', $activePhase)
        ->where(function ($q) use ($locationIds, $stateName) {
            $q->whereIn('location', $locationIds)
              ->orWhereRaw('LOWER(TRIM(location)) = ?', [strtolower(trim($stateName))]);
        })
        ->select('id', 'college_name as name')
        ->orderBy('college_name')
        ->distinct()
        ->get();

    return $colleges;
}

    // college.location stores the state as either a numeric id or the state name
    public function getCollegeState(Request $request)
    {
        $college = DB::table('college')->where('id', $request->college_id)->first();

        if (!$college || $college->location === null || trim($college->location) === '') {
            return response()->json(['state_id' => '', 'state_name' => '', 'city' => $college->city ?? '']);
        }

        $location = trim($college->location);

        if (is_numeric($location)) {
            $state = DB::table('state')->where('id', $location)->first();
        } else {
            $state = DB::table('state')->whereRaw('LOWER(TRIM(state)) = ?', [strtolower($location)])->first();
        }

        return response()->json([
            'state_id'   => $state->id ?? '',
            'state_name' => $state->state ?? $location,
            'city'       => $college->city ?? '',
        ]);
    }

    public function create()
    {
        if (!Auth::user()) {
            return redirect()->route('login');
        }
        if(!in_array(Auth::user()->role, [1,3,10]))
        {
            return redirect('login');
        }
        
        $all_phase = DB::table('phase')->get();
        // return view('form-builder.create', ["title"=>"Form Builder", "all_phase"=>$all_phase]);
        return view('form-builder.create-v3', ["title"=>"Form Builder", "all_phase"=>$all_phase]);
    }

    public function createV3()
    {
        if (!Auth::user()) {
            return redirect()->route('login');
        }
        if (!in_array(Auth::user()->role, [1, 3, 10])) {
            return redirect('login');
        }

        $all_phase = DB::table('phase')->get();
        return view('form-builder.create', ["title" => "Form Builder", "all_phase" => $all_phase]);
    }

    public function editV3($id)
    {
        if (!Auth::user()) {
            return redirect()->route('login');
        }
        if (!in_array(Auth::user()->role, [1, 3, 10, 21])) {
            return redirect('login');
        }

        $formTemplate = FormTemplate::findOrFail($id);

        if ($formTemplate->isEverPublished == 1 && Auth::user()->role != 21) {
            return redirect()->route('form-builder.index')
                ->with('error', 'The form is already published. You cannot edit it now.');
        }
        if (Auth::user()->role != 1 && Auth::user()->role != 21 && $formTemplate->user_id != 1) {
            return redirect()->route('form-builder.index')
                ->with('error', 'You do not have permission to edit this form.');
        }

        $all_phase = DB::table('phase')->get();
        return view('form-builder.edit-v3', ["title" => "Form Builder", "all_phase" => $all_phase, "formTemplate" => $formTemplate]);
    }

    /* .
    public function storeV3(Request $request)
    {
        if (!Auth::user() || !in_array(Auth::user()->role, [1, 3, 10])) {
            return response()->json(['status' => 0, 'message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'title'          => ['required', 'string', 'max:200', 'regex:/^[A-Za-z0-9 \-_.,&()\']+$/'],
            'form_structure' => ['required', 'json'],
            'type'           => 'nullable|string',
            'student_type_name' => 'nullable|string',
        ]);

        $structure = json_decode($data['form_structure'], true);
        if (!is_array($structure) || count($structure) === 0) {
            return response()->json(['status' => 0, 'message' => 'Add at least one field to create a form.'], 422);
        }

        $slug = \Illuminate\Support\Str::slug($data['title']) . '-' . \Illuminate\Support\Str::random(5);
        while (DB::table('form_templates')->where('slug', $slug)->exists()) {
            $slug = \Illuminate\Support\Str::slug($data['title']) . '-' . \Illuminate\Support\Str::random(5);
        }

        $isReg = ($request->input('type') === 'registration') ? 1 : 0;
        $s = (array) $request->input('settings', []);
        $yn = function ($k, $def = 0) use ($s) { return isset($s[$k]) ? (int) $s[$k] : $def; };

        $redirectMethod = $request->input('redirect_method', 'same_page') ?: 'same_page';

        $val = [
            'title'                => $data['title'],
            'slug'                 => $slug,
            'form_structure'       => $data['form_structure'],
            'is_registration_form' => $isReg,
            'is_public'            => 1,
            'user_id'              => Auth::id(),
            'unique_string'        => $this->generateUniqueStringForFormBuilder(Auth::id()),
            'form_type'            => $request->input('type', ''),
            'student_type'         => $isReg ? 20 : null,
            'student_type_name'    => $isReg ? $request->input('student_type_name') : null,
            'isAnonymous'          => $yn('anonymous'),
            'accessible_using_url' => $yn('url'),
            'multi_submission'     => $yn('multi'),
            'login_required'       => $yn('login', 1),
            'edit_response'        => $yn('edit'),
            'scoring'              => $yn('scoring'),
            'review'               => $yn('review'),
            'allowed_old_phase'    => (int) $request->input('oldPhase', 0),
            'is_dynamic_url'       => (int) $request->input('isDynamicUrl', 0),
            'redirect_method'      => $redirectMethod,
            'redirect_url'         => $request->input('redirect_url'),
            'success_message'      => $request->input('success_message') ?: 'Form Submitted Successfully!',
            'submit_btn_txt'       => $request->input('submit_btn_txt') ?: 'Submit',
            'parameters'           => json_encode([]),
        ];

        $form = FormTemplate::create($val);

        try {
            $this->syncFormBuilderCampaigns($form->id, $structure);
        } catch (\Throwable $e) {
            \Log::error('syncFormBuilderCampaigns failed on storeV3', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'status' => 1,
            'id'     => $form->id,
            'slug'   => $form->slug,
            'url'    => url('/submit/' . $form->slug),
        ]);
    }
    */
    public function createv2()
    
    {
        
        try{
        if (!Auth::user()) {
            return redirect()->route('login');
        }
        if(!in_array(Auth::user()->role, [1,3,10]))
        {
            return redirect('login');
        }
        } catch (\Exception $e) {

    Log::error('Error fetching', ['error' => $e->getMessage()]);

    dd($e->getMessage());

}
 
        return view('form-builder.createv2', ["title"=>"Form Builder"]);
    }

    public function createReg()
    {
        return view('form-builder.reg-create', ["title"=>"Reg. Form Builder"]);
    }

    public static function generateUniqueStringForFormBuilder($userId)
    {
        do {
            // $timestamp = now()->timestamp;
            // $random = Str::random(5);
            // $uniqueString = hash('sha256', $userId . $timestamp . $random);
            $uniqueString = 'slug-' . substr(md5($userId . microtime(true) . Str::random(8)), 0, 6); // e.g., slug-a1f2c9
        } while (
            DB::table('form_templates')->where('unique_string', $uniqueString)->exists()
        );

        return $uniqueString;
    }

    public function checkFormBuilderSlug(Request $request)
    {
        $slug = $request->slug;
        $originalSlug = $slug;
        $counter = 1;
        $status = 0;
        if(DB::table('form_templates')->where('slug', $slug)->when($request->id != null, function($query) use ($request){$query->whereNotIn('id', [$request->id]);})->exists())
        {
            $status = 1;
        }

        while (DB::table('form_templates')->where('slug', $slug)->when($request->id != null, function($query) use ($request){$query->whereNotIn('id', [$request->id]);})->exists()) {
            $slug = $originalSlug . $counter;
            $counter++;
        }

        return response()->json(["slug" => $slug, "status" => $status]);
    }

    public function isRegistrationForm(Request $request)
    {
        DB::table('form_templates')
            ->where('slug', $request->slug)
            ->update([
                'is_registration_form' => (int) $request->is_registration_form
            ]);
        $activePhase = DB::table('phase')
        ->where('active', 1)
        ->value('phaseid');


        return response()->json(['success' => true, 'data' => [
            'phase' => $activePhase
        ]]);
    }


    public function uploadSampleFile(Request $request)
    {
        $request->validate(['sample_file' => 'required|file|max:10240']);

        $file = $request->file('sample_file');

        // Validate by the file's actual extension (reliable for Office/zip-based
        // formats like xlsx/docx/pptx, unlike mime-sniffing).
        $allowed = array_filter(array_map(fn($e) => strtolower(trim($e)), explode(',', (string) $request->extension_allowed)));
        if (!empty($allowed)) {
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, $allowed, true)) {
                return response()->json([
                    'status' => 0,
                    'errors' => [
                        'sample_file' => ['The sample file must be of type: ' . implode(', ', $allowed) . '.'],
                    ],
                ], 422);
            }
        }

        $origFileName = $file->getClientOriginalName();
        $ext = $file->getClientOriginalExtension();
        $md5Name = substr(md5($origFileName), 20);
        $newname = $md5Name . rand(2, 50) . date("his") . '.' . $ext;
        $destinationPath = 'form_builder/sample_files';
        $file->storeAs($destinationPath, $newname, 'azure');

        $url = Storage::disk('azure')->url("$destinationPath/$newname" . "?" . env('AZURE_STORAGE_SAS_TOKEN'));

        return response()->json([
            'status' => 1,
            'url'    => $url,
            'name'   => $origFileName,
            'path'   => "$destinationPath/$newname",
        ]);
    }

    public function deleteSampleFile(Request $request)
    {
        if ($request->filled('path')) {

            Storage::disk('azure')->delete($request->path);

        }

        return response()->json([
            'status' => 1
        ]);
    }

    public function store(Request $request)
    {
        if(DB::table('form_templates')->where('slug', $request->slug)->exists())
        {
            if ($request->expectsJson()) return response()->json(['status' => 0, 'message' => 'This form url is already in use.'], 422);
            return redirect()->back()->with('err', 'This form url is already in use');
        }

        $sampleFile = null;
        if ($request->hasFile('sample_file')) {

            $file = $request->file('sample_file');

            $origFileName = $file->getClientOriginalName();
            $ext = $file->getClientOriginalExtension();

            $md5Name = substr(md5($origFileName), 20);
            $newname = $md5Name . rand(2, 50) . date("his") . '.' . $ext;

            $destinationPath = 'form_builder/sample_files';

            $file->storeAs($destinationPath, $newname, 'azure');

            $sampleFile = [
                'name' => $origFileName,
                'url'  => Storage::disk('azure')->url(
                    $destinationPath.'/'.$newname.'?'.env('AZURE_STORAGE_SAS_TOKEN')
                ),
            ];
        }
        // dd($request->all());
        $validated = $request->validate([
            'title' => ['required','string','max:200','regex:/^[A-Za-z0-9 \-_.,&()\']+$/'],
            'description' => 'nullable|string',
            'form_structure' => ['required','json', function($attribute, $value, $fail) {
                $elements = json_decode($value, true);
                if (! is_array($elements)) {
                    return $fail('Invalid form structure.');
                }

                foreach ($elements as $idx => $el) {
                    if (empty($el['label']) || trim($el['label']) === '') {
                        return $fail("Every form element must have a label (missing on element #".($idx+1).").");
                    }
                    if (($el['type'] ?? null) === 'download_file' && empty($el['sampleFile']['url'])) {
                        return $fail("Please upload a file for the \"".($el['label'] ?? 'Sample File')."\" field.");
                    }
                }
            }],
            'is_public' => 'boolean',
            'slug' => 'required',
            'multi_submission' => 'nullable',
            'login_required' => 'nullable',
            'edit_response' => 'nullable',
            'isAnonymous' => 'nullable',
            'accessible_using_url' => 'nullable',
            'scoring' => 'nullable',
            'parameters' => 'nullable',
            'show_in_sdp_report' => 'nullable',
            'review' => 'nullable',
            'is_registration_form' => 'nullable',
            'student_type_builder' => (int) $request->is_registration_form === 1 
            ? 'required' 
            : 'nullable',

        ]);

        // dd($request->post());
        // dd($request->student_type_builder_display, $request->all());

        if(sizeof(json_decode($request->form_structure)) == 0)
        {
            if ($request->expectsJson()) return response()->json(['status' => 0, 'message' => 'Add at least one field to create a form.'], 422);
            return redirect()->back()->with('err', 'Add at least one field to create a form!');
        }

        $validated['user_id'] = 1;
        // $validated['slug'] = Str::slug($validated['title']) . '-' . Str::random(5);
        $validated['slug'] = $request->slug;

        $validated['student_type'] = ((int) $request->is_registration_form === 1) ? $request->student_type_builder : null;
        $validated['student_type_name'] = ((int) $request->is_registration_form === 1) ? $request->student_type_builder_display : null;
        // $validated['is_registration_form'] = $request->is_registration_form;
        $validated['isAnonymous'] = $request->isAnonymous;
        
        $validated['is_dynamic_url'] = $request->isDynamicUrl;
        $validated['accessible_using_url'] = $request->accessible_using_url;
        $unique_string = $this->generateUniqueStringForFormBuilder(Auth::id());
        $validated['unique_string'] = $unique_string;
        $validated['form_type'] = $request->form_type ?? '';
        $validated['allowed_old_phase'] = $request->allowed_old_phase;

        $parameters = $request->parameter_name ?? [];
        $weightages = $request->weightage;

        $data = [];

        foreach ($parameters as $index => $param) {
            $data[] = [
                'parameter' => $param,
                'weightage' => $weightages[$index] ?? 1,
            ];
        }

        $validated['scoring'] = $request->scoring;
        $validated['login_required'] = $request->login_required??0;
        $validated['review'] = $request->review ?? 0;
        $validated['parameters'] = json_encode($data, true);

        $redirect_url = $request->redirect_url;
        $redirect_method = $request->redirect_method;
        if($redirect_method == null)
        {
            $redirect_method = 'same_page';
        }
        
        $success_message = $request->success_message;
        if($success_message == null)
        {
            $success_message = 'Form Submitted Successfully!';
        }

        $submit_btn_txt = $request->submit_btn_txt;

        if($submit_btn_txt == null)
        {
            $submit_btn_txt = 'Submit';
        }
        
        $validated['redirect_method'] = $redirect_method;
        $validated['success_message'] = $success_message;
        $validated['submit_btn_txt'] = $submit_btn_txt;
        $validated['redirect_url'] = $redirect_url;
        // dd($validated);

        $form = FormTemplate::create($validated);

        // Sync form-builder email campaigns
        try {
            $structure = json_decode($request->form_structure, true);
            if (is_array($structure)) {
                $this->syncFormBuilderCampaigns($form->id, $structure);
            }
        } catch (\Throwable $e) {
            \Log::error('syncFormBuilderCampaigns failed on store', ['error' => $e->getMessage()]);
        }

        if ((int) $validated['is_registration_form'] === 1) {

            $body = [
                'subject'      => 'Registration Form Created',
                'form_id'      => $form->id,
                'form_name'    => $form->title,
                'form_url'     => url('/submit/' . $form->slug),
                'created_at'   => $form->created_at->format('d M Y'),
                'added_by'     => auth()->user()->name,
            ];


        // $mail_users = ["asingh@edunetfoundation.org", "rakesh@edunetfoundation.org","mpakhtar@edunetfoundation.org"];
        // // $mail_users = ["mpakhtar@edunetfoundation.org"];


        //    \Mail::to($mail_users)
        //   ->send(new \App\Mail\RegistrationFormBuilder($body));
        }


        if ($request->expectsJson()) {
            return response()->json([
                'status' => 1,
                'id'     => $form->id,
                'slug'   => $form->slug,
                'url'    => url('/submit/' . $form->slug),
            ]);
        }

        return redirect('forms')
        // ->route('form-builder.edit', $form->id)
            ->with('success', 'Form created successfully.');
    }
    public function storev2(Request $request)
    
    {
        // dd($request->all());
        if(DB::table('form_templates')->where('slug', $request->slug)->exists())
        {
            return redirect()->back()->with('err', 'This form url is already in use. Please contact to development team to fix this issue!');
        }

        $validated = $request->validate([
            'title' => ['required','string','max:200','regex:/^[A-Za-z0-9 \-_.,&()\']+$/'],
            'description' => 'nullable|string',
            'form_structure' => ['required','json', function($attribute, $value, $fail) {
                $elements = json_decode($value, true);
                if (! is_array($elements)) {
                    return $fail('Invalid form structure.');
                }

                foreach ($elements as $idx => $el) {
                    if (empty($el['label']) || trim($el['label']) === '') {
                        return $fail("Every form element must have a label (missing on element #".($idx+1).").");
                    }
                    if (($el['type'] ?? null) === 'download_file' && empty($el['sampleFile']['url'])) {
                        return $fail("Please upload a file for the \"".($el['label'] ?? 'Sample File')."\" field.");
                    }
                }
            }],
            'is_public' => 'boolean',
            'slug' => 'required',
            'multi_submission' => 'nullable',
            'login_required' => 'nullable',
            'approval_required' => 'nullable',
            'edit_response' => 'nullable',
            'isAnonymous' => 'nullable',
            'accessible_using_url' => 'nullable',
            'scoring' => 'nullable',
            'parameters' => 'nullable',
            'show_in_sdp_report' => 'nullable',
            'review' => 'nullable',
        ]);

        // dd($request->post());

        if(sizeof(json_decode($request->form_structure)) == 0)
        {
            return redirect()->back()->with('err', 'Add at least one field to create a form!');
        }

        $validated['user_id'] = 1;
        // $validated['slug'] = Str::slug($validated['title']) . '-' . Str::random(5);
        $validated['slug'] = $request->slug;
        $validated['isAnonymous'] = $request->isAnonymous;
        $validated['accessible_using_url'] = $request->accessible_using_url;
        $validated['approval_required'] = $request->approval_required;
        $unique_string = $this->generateUniqueStringForFormBuilder(Auth::id());
        $validated['unique_string'] = $unique_string;

        $parameters = $request->parameter_name ?? [];
        $weightages = $request->weightage;

        $data = [];

        foreach ($parameters as $index => $param) {
            $data[] = [
                'parameter' => $param,
                'weightage' => $weightages[$index] ?? 1,
            ];
        }

        $validated['scoring'] = $request->scoring;
        $validated['review'] = $request->review ?? 0;
        $validated['parameters'] = json_encode($data, true);

        $redirect_url = $request->redirect_url;
        $redirect_method = $request->redirect_method;
        if($redirect_method == null)
        {
            $redirect_method = 'same_page';
        }
        
        $success_message = $request->success_message;
        if($success_message == null)
        {
            $success_message = 'Form Submitted Successfully!';
        }

        $submit_btn_txt = $request->submit_btn_txt;

        if($submit_btn_txt == null)
        {
            $submit_btn_txt = 'Submit';
        }
        
        $validated['redirect_method'] = $redirect_method;
        $validated['success_message'] = $success_message;
        $validated['submit_btn_txt'] = $submit_btn_txt;
        $validated['redirect_url'] = $redirect_url;
        // dd($validated);

        $form = FormTemplate::create($validated);

        // Sync form-builder email campaigns
        try {
            $structure = json_decode($request->form_structure, true);
            if (is_array($structure)) {
                $this->syncFormBuilderCampaigns($form->id, $structure);
            }
        } catch (\Throwable $e) {
            \Log::error('syncFormBuilderCampaigns failed on store', ['error' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 1,
                'id'     => $form->id,
                'slug'   => $form->slug,
                'url'    => url('/submit/' . $form->slug),
            ]);
        }

        return redirect('forms')
        // ->route('form-builder.edit', $form->id)
            ->with('success', 'Form created successfully.');
    }


    public function edit($id)
    {
        $formTemplate = FormTemplate::findOrFail($id);
        
        if(Auth::user()->role != 1 && Auth::user()->role != 21)
        {
            if ($formTemplate->user_id != 1) {
                return redirect()->route('form-builder.index')
                    ->with('error', 'You do not have permission to edit this form.');
            }
        }

        $all_phase = DB::table('phase')->get();

        // dd($formTemplate);
        // Old builder UI (kept for reference)
        // return view('form-builder.edit', ["title"=>"Form Builder", "all_phase"=>$all_phase, "formTemplate"=>$formTemplate]);
        return view('form-builder.edit-v3', ["title"=>"Form Builder", "all_phase"=>$all_phase, "formTemplate"=>$formTemplate]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $formTemplate = FormTemplate::findOrFail($id);

        if ($formTemplate->isEverPublished == 1 && Auth::user()->role != 21) {
            if ($request->expectsJson()) return response()->json(['status' => 0, 'message' => 'The form is already published. You cannot edit it now.'], 422);
            return redirect()->route('form-builder.index')
                ->with('error', 'The form is already published. You cannot edit it now.');
        }

        // Make sure the user can only update their own forms
        if (Auth::user()->role != 1 && Auth::user()->role != 21 && $formTemplate->user_id != 1) {
            if ($request->expectsJson()) return response()->json(['status' => 0, 'message' => 'You do not have permission to update this form.'], 403);
            return redirect()->route('form-builder.index')
                ->with('error', 'You do not have permission to update this form.');
        }
        
        // Normalize CRLF -> LF so the server counts characters the same way the
        // textarea maxlength / counter does (browsers submit line breaks as \r\n).
        $request->merge([
            'success_message' => str_replace("\r\n", "\n", (string) $request->input('success_message')),
        ]);

        // Validate the incoming request
        $validated = $request->validate([
            'title' => ['required','string','max:255','regex:/^[A-Za-z0-9 \-_.,&()\']+$/'],
            // 'description' => 'nullable|string',
            'is_public' => 'boolean',
            'form_structure' => 'required|json',
            'multi_submission' => 'nullable',
            'login_required' => 'nullable',
            'edit_response' => 'nullable',
            'approval_required' => 'nullable',
            'isAnonymous' => 'nullable',
            'isDynamicUrl' => 'nullable',
            'accessible_using_url' => 'nullable',
            'redirect_method'     => 'required|in:same_page,custom',
            'redirect_url'        => 'nullable|url|required_if:redirect_method,custom',
            'success_message'     => 'nullable|string|max:100',
            'submit_btn_txt'      => 'nullable|string|max:50',
            'scoring' => 'nullable',
            'parameters' => 'nullable',
            'show_in_sdp_report' => 'nullable',
            'allowed_old_phase' => 'nullable',
            'is_registration_form' => 'nullable',
            'review' => 'nullable',
            'student_type_builder' => (int) $request->is_registration_form === 1 
    ? 'required' 
    : 'nullable',
        ]);

        // dd($validated['form_structure']);
        
        // Update the form template
        $formTemplate->title = $validated['title'];
        // $formTemplate->slug = Str::slug($validated['title']); // Update the slug based on the title
        // $formTemplate->description = $validated['description'];
        $formTemplate->slug = $request->slug;
        $formTemplate->is_public = $request->has('is_public') ? 1 : 0;
        $formTemplate->form_structure = $validated['form_structure'];
        $formTemplate->multi_submission = $validated['multi_submission'] ?? $formTemplate->multi_submission;
        $formTemplate->login_required = $validated['login_required']??0;
        $formTemplate->approval_required = $validated['approval_required'] ?? null;
        $formTemplate->edit_response = $validated['edit_response']??0;
        $formTemplate->isAnonymous = $validated['isAnonymous'];
        $formTemplate->is_dynamic_url = $request->isDynamicUrl ?? 0;
        $formTemplate->allowed_old_phase = $validated['allowed_old_phase']??null;
        $formTemplate->accessible_using_url = $validated['accessible_using_url'];
        $formTemplate->redirect_method  = $validated['redirect_method'];
        $formTemplate->redirect_url     = ($validated['redirect_method'] === 'custom')
                                            ? ($validated['redirect_url'] ?? null)
                                            : null;
        $formTemplate->success_message  = $validated['success_message'] ?? 'Form Submitted Successfully!';
        $formTemplate->submit_btn_txt   = $validated['submit_btn_txt']  ?? 'Submit';

        $parameters = $request->parameter_name ?? [];
        $weightages = $request->weightage;

        $data = [];

        foreach ($parameters as $index => $param) {
            $data[] = [
                'parameter' => $param,
                'weightage' => $weightages[$index] ?? 1,
            ];
        }

        $formTemplate->scoring = $request->scoring;
        $formTemplate->review = $request->review ?? 0;
        $formTemplate->parameters = json_encode($data, true);
        $formTemplate->student_type = ($formTemplate->is_registration_form == 1) ? ($request->student_type_builder ?? $formTemplate->student_type) : null;
        $formTemplate->student_type_name = ($formTemplate->is_registration_form == 1) ? ($request->student_type_builder_display ?? $formTemplate->student_type_name) : null;
        $formTemplate->form_type = $request->form_type ?? $formTemplate->form_type;
        
        $formTemplate->save();

        // Sync form-builder email campaigns (delete + recreate)
        try {
            $structure = json_decode($request->form_structure, true);
            if (is_array($structure)) {
                $this->syncFormBuilderCampaigns($formTemplate->id, $structure);
            }
        } catch (\Throwable $e) {
            \Log::error('syncFormBuilderCampaigns failed on update', ['error' => $e->getMessage()]);
        }
        
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 1,
                'id'     => $formTemplate->id,
                'slug'   => $formTemplate->slug,
                'url'    => url('/submit/' . $formTemplate->slug),
            ]);
        }

        return redirect()->route('form-builder.index')
            ->with('status', 'Form template updated successfully!');
    }

    /**
     * Sync form-builder-created email campaigns for a form.
     * Deletes all existing form_builder campaigns for this form,
     * then recreates from the current form_structure.
     */
    private function syncFormBuilderCampaigns(int $formId, array $formStructure): void
    {
        // 1. Delete old variable maps for form_builder campaigns of this form
        $oldCampaignIds = DB::table('email_campaigns')
            ->where('source_id', $formId)
            ->where('created_via', 'form_builder')
            ->pluck('id');

        if ($oldCampaignIds->isNotEmpty()) {
            DB::table('email_campaign_variable_map')
                ->whereIn('campaign_id', $oldCampaignIds)
                ->delete();
            DB::table('email_campaigns')
                ->whereIn('id', $oldCampaignIds)
                ->delete();
        }

        // 2. Recreate from form_structure
        foreach ($formStructure as $field) {
            if (empty($field['sendEmail']) || empty($field['sendEmailRules'])) {
                continue;
            }

            foreach ($field['sendEmailRules'] as $rule) {
                if (empty($rule['template_id']) || !isset($rule['value'])) {
                    continue;
                }

                // Insert campaign
                $campaignId = DB::table('email_campaigns')->insertGetId([
                    'template_id'     => (int) $rule['template_id'],
                    'source_type'     => 'form',
                    'source_id'       => $formId,
                    'send_mode'       => 'on_submit',
                    'on_submit'       => 1,
                    'bulk_upload_id'  => null,
                    'total_recipients'=> 0,
                    'sent_count'      => 0,
                    'skipped_count'   => 0,
                    'status'          => 0,
                    'created_by'      => Auth::id(),
                    'created_via'     => 'form_builder',
                    'field_condition' => json_encode([
                        'field' => $field['name'],
                        'value' => $rule['value'],
                    ]),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                // Insert variable mappings
                if (!empty($rule['mapping']) && is_array($rule['mapping'])) {
                    foreach ($rule['mapping'] as $templateVar => $sourceField) {
                        if (empty($templateVar) || empty($sourceField)) continue;
                        DB::table('email_campaign_variable_map')->insert([
                            'campaign_id'        => $campaignId,
                            'template_variable'  => $templateVar,
                            'source_field'       => $sourceField,
                            'created_at'         => now(),
                            'updated_at'         => now(),
                        ]);
                    }
                }
            }
        }
    }

    public function deleteForm($id)
    {
        DB::table('form_templates')->where('id',$id)->update([
            'active' => 0,
        ]);
            // $this->authorize('delete', $formTemplate);
            // $formTemplate->delete();
        
            return redirect()->route('form-builder.index')
                ->with('status', 'Form archived successfully.');


    }

    public function unarchiveForm($id)
    {
        DB::table('form_templates')->where('id', $id)->update([
            'active' => 1,
        ]);

        return response()->json(['status' => 'success']);
    }

     public function viewDisabledForm($id)
    {
        $formTemplate = FormTemplate::findOrFail($id);
        
        if(Auth::user()->role != 1)
        {
            if ($formTemplate->user_id != 1) {
                return redirect()->route('form-builder.index')
                    ->with('error', 'You do not have permission to edit this form.');
            }
        }
        $all_phase = DB::table('phase')->get();
        // Old builder UI (kept for reference)
        // return view('form-builder.view_disabled_form', ["title"=>"Form Builder", "formTemplate"=>$formTemplate??[], "all_phase" => $all_phase]);
        return view('form-builder.view-v3', ["title"=>"Form Builder", "formTemplate"=>$formTemplate, "all_phase" => $all_phase]);
    }

    public function viewV3($id)
    {
        $formTemplate = FormTemplate::findOrFail($id);

        if (Auth::user()->role != 1) {
            if ($formTemplate->user_id != 1) {
                return redirect()->route('form-builder.index')
                    ->with('error', 'You do not have permission to view this form.');
            }
        }
        $all_phase = DB::table('phase')->get();
        return view('form-builder.view-v3', ["title" => "Form Builder", "formTemplate" => $formTemplate, "all_phase" => $all_phase]);
    }

    public function getForms(Request $request)
    {
        if ($request->ajax()) {
        
            $forms = FormTemplate::where('active', 1)
                        ->latest()
                        ->get();

    if(Auth::user()->role == 3)
    {
        $trainerid = Auth::id();
        $responses = DB::table('form_submissions')
                    ->select('form_template_id', DB::raw('COUNT(*) as total_responses','form_submissions.created_at'))
                    ->leftJoin('users','users.id','=','form_submissions.userid')
                    // ->leftJoin('batch_user_mapping as bum','bum.userid','=','form_submissions.userid')
                    // ->leftJoin('batch_detail as bd','bd.id','=','bum.batchid')
                    // ->whereRaw("FIND_IN_SET($trainerid,bd.assigned_to)")
                    // ->where('users.role',2)
                    ->groupBy('form_template_id')
                    ->pluck('total_responses', 'form_template_id');
    }
    else
    {        
        $responses = DB::table('form_submissions')
                    ->select('form_template_id', DB::raw('COUNT(*) as total_responses','form_submissions.created_at'))
                    ->leftJoin('users','users.id','=','form_submissions.userid')
                    // ->where('users.role',2)
                    ->groupBy('form_template_id')
                    ->pluck('total_responses', 'form_template_id');
    }

        $latestResponses = DB::table('form_submissions')
                    ->select('form_template_id', DB::raw('MAX(created_at) as latest_response'))
                    ->groupBy('form_template_id')
                    ->pluck('latest_response', 'form_template_id');
       
            
//  <a href="' . route('form-report', $form->id) . '" class="text-primary" title="View Form"><i class="fas fa-eye"></i></a>
        $data = [];
        foreach ($forms as $form) {
            // $count = $responses->get($form->id, 0);
            $actionBtns = '
                
                <a href="' . route('form-submission.show', $form->slug) . '" target="_blank" class="text-primary" title="View Form"><i class="fas fa-eye"></i></a>';

                if((Auth::user()->role == 1 || Auth::user()->role == 10))
                {
                    if ($form->isEverPublished === 0) {
                        $actionBtns .= '<a href="' . route('form-builder.edit', $form->id) . '" class="text-success px-1" title="Edit"><i class="fas fa-edit"></i></a>';
                    } else {
                        // $actionBtns .= '<span class="text-muted px-1" title="Editing disabled">'
                        // . '<i class="fas fa-edit"></i>'
                        // . '</span>';

                        $actionBtns .= '<a href="' . route('form-builder.view_disabled_form', $form->id) . '" class="text-success px-1" title="View Form Details"><i class="fas fa-edit"></i></a>';
                    }

                    
                    $actionBtns .= '<a href="javascript:void(0);" class="text-warning px-1 duplicate-form" data-id="' . $form->id . '" title="Duplicate">
                                <i class="fas fa-clone"></i>
                            </a>';

                    if ($form->is_registration_form == 0) {
                        $actionBtns .= '
                        <form id="delete-form-' . $form->id . '" action="' 
                            . route('form-builder.destroy', $form->id) 
                            . '" method="POST" style="display:none;">'
                            . csrf_field() 
                            . '</form>
                        <a href="javascript:void(0);" class="text-danger me-1" title="Delete"
                        onclick="if(confirm(\'Are you sure you want to delete this form?\')) 
                            document.getElementById(\'delete-form-' . $form->id . '\').submit();">
                        <i class="fas fa-trash-alt"></i>
                        </a>';
                    }

                    

                }                
                
                $actionBtns .= '<a href="' . route('form-report', $form->id) . '" class="text-success px-1" title="View form resources" ><i style="color: black" class="fa-solid fa-table"></i></a>
 
                <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary me-1"
                   onclick="copyToClipboard(\'' . route('form-submission.show', $form->slug) . '\', this)" title="Copy Form Link">
                   <i class="fas fa-copy"></i> Form Link
                </a>';

                if ($form->unique_string) {
                $actionBtns .= '<a href="javascript:void(0);" class="btn btn-sm btn-outline-primary me-1"
                                onclick="copyToClipboard(\'' . route('form-submission.short_link_show', $form->unique_string) . '\', this)" title="Copy Short Link">
                                <i class="fas fa-copy"></i> Short Form Link
                                </a>';
                } else {
                    $actionBtns .= '<a href="javascript:void(0);" class="btn btn-sm btn-outline-primary me-1"
                                    onclick="copyToClipboard(\'' . route('form-submission.show', $form->slug) . '\', this)" title="Copy Short Link">
                                    <i class="fas fa-copy"></i> Short Form Link
                                    </a>';
                }
 
            $data[] = [
               '<label class="switch">
                    <input type="checkbox" class="form-status-toggle" data-id="' . $form->id . '" ' . ($form->is_published ? 'checked' : '') . '>
                    <span class="slider"></span>
                </label>',
                '<span class="text-primary font-weight-bold">' . $form->title . '</span>',
                '<div class="d-flex align-items-center">
                    <div class="mr-2" style="min-width: 40px;">'. ($responses[$form->id] ?? 0) .'</div>
                </div>',
                \Carbon\Carbon::parse($form->created_at)->isoFormat('DD MMM YYYY HH:mm:ss'),
                \Carbon\Carbon::parse($latestResponses[$form->id] ?? $form->created_at)->isoFormat('DD MMM YYYY HH:mm:ss'),

                $actionBtns,
            ];
        }
 
        return response()->json(['data' => $data]);
    }


}


// public function duplicateForm($id)
// {
//     $form = FormTemplate::findOrFail($id);

//     // Duplicate main form
//     $newForm = $form->replicate();
//     $newForm->title = $form->title . ' (Copy)';

//     // Unique slug generation
//     $originalSlug = $form->slug;
//     $newSlug = $originalSlug;
//     $i = 1;
//     while (FormTemplate::where('slug', $newSlug)->exists()) {
//         $newSlug = $originalSlug . $i;
//         $i++;
//     }

//     $newForm->slug = $newSlug;
//     $newForm->isEverPublished = 0;
//     $newForm->is_published = 0;
//     $newForm->save();

//     $fields = DB::table('form_templates')->where('id', $form->id)->get();

//     foreach ($fields as $field) {
//         $newField = (array) $field;
//         unset($newField['id']);
//         $newField['id'] = $newForm->id;
//         DB::table('form_templates')->insert($newField);
//     }

//     // 🔁 Repeat for other related tables if needed

//     return response()->json(['status' => 'success', 'message' => 'Form duplicated successfully']);
// }
public function duplicateForm($id)
{
    $form = FormTemplate::findOrFail($id);

    $newForm = $form->replicate();
    $newForm->title = $form->title . ' (Copy)';

    // Unique slug generation
    $originalSlug = $form->slug;
    $newSlug = $originalSlug;
    $i = 1;
    while (FormTemplate::where('slug', $newSlug)->exists()) {
        $newSlug = $newSlug . $i;
        $i++;
    }
    $unique_string = $this->generateUniqueStringForFormBuilder(Auth::id());
    $newForm->unique_string = $unique_string;
    $newForm->slug = $newSlug;
    $newForm->isEverPublished = 0;
    $newForm->is_published = 0;
    $newForm->active = 1;
    $newForm->delete_requested = 0;
    $newForm->delete_requested_by = null;
    $newForm->delete_approved = 0;
    $newForm->delete_approved_by = null;
    $newForm->delete_reason = null;
    $newForm->save();

    return response()->json([
        'status' => 'success',
        'message' => 'Form duplicated successfully',
        'form_id' => $newForm->id
    ]);
}


public function approveDeleteRequest($id)
{
    DB::beginTransaction();

    try {

        $form = FormTemplate::findOrFail($id);

        DB::table('form_delete_logs')->insert([
            'form_id'            => $form->id,
            'deleted_by'         => $form->delete_requested_by,
            'delete_action'      => 'deleted',
            'delete_approved_by' => Auth::id(),
           'form_data' => json_encode([
                'form'   => $form->toArray(),
                'reason' => $form->delete_reason
            ], JSON_UNESCAPED_UNICODE),
            'created_at'         => now(),
            'updated_at'         => now()
        ]);

        $form->delete();

        DB::commit();

        return response()->json([
            'status'  => 'success',
            'message' => 'Form approved and deleted successfully.'
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'status'  => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}

public function rejectDeleteRequest($id)
{
    $form = FormTemplate::findOrFail($id);

    $form->delete_requested = 0;
    $form->delete_requested_by = null;
    $form->delete_approved = 0;
    $form->delete_approved_by = Auth::id();
    $form->save();

    return response()->json([
        'status'  => 'success',
        'message' => 'Delete request rejected successfully.'
    ]);
}

public function deleteRequests()
{
    $title = "Delete Requests";

    if (request()->ajax()) {
        $forms = DB::table('form_templates as ft')
            ->leftJoin('users as u', 'u.id', '=', 'ft.delete_requested_by')
            ->where('ft.delete_requested', 1)
            ->where('ft.delete_approved', 0)
            ->select(
                'ft.id',
                'ft.title',
                'ft.delete_reason',
                'u.name as requested_by',
                'ft.created_at'
            )
            ->latest('ft.id')
            ->get();

        $data = [];
        foreach ($forms as $form) {
            $data[] = [
                // $form->id,
                $form->title,
                $form->delete_reason ?? '-',
                $form->requested_by ?? '-',
                \Carbon\Carbon::parse($form->created_at)->isoFormat('DD MMM YYYY HH:mm'),
                '<button class="btn btn-success btn-sm approve-delete" data-id="' . $form->id . '">Approve</button>
                 <button class="btn btn-danger btn-sm reject-delete" data-id="' . $form->id . '">Reject</button>'
            ];
        }

        return response()->json(['data' => $data]);
    }

    return view('form-builder.delete-requests', compact('title'));
}

public function deleteAction(Request $request, $id)
{
    DB::beginTransaction();

    try {
        $form = FormTemplate::findOrFail($id);

        if ($form->delete_requested == 1 && $form->delete_approved == 0) {
            return response()->json([
                'status'  => 'request_pending',
                'message' => 'Delete request is already pending approval.'
            ]);
        }

        $form->delete_requested = 1;
        $form->delete_requested_by = Auth::id();
        $form->delete_reason = $request->input('reason');  
        $form->save();

        DB::commit();

        return response()->json([
            'status'  => 'request_sent',
            'message' => 'Delete approval request sent successfully.'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

public function formStatus(Request $request)
{
    $previousEver = FormTemplate::where('id', $request->form_id)
        ->where('user_id', 1)
        ->value('isEverPublished');
    $newEver = $request->active == 1 ? 1 : $previousEver;

    FormTemplate::where('id', $request->form_id)
        // ->where('user_id', 1)
        ->update(['is_published' => $request->active, 'isEverPublished' => $newEver]);

    return response()->json(['status' => 'success']);
}

 public function indexV2()
    {
        if (!Auth::user()) {
            return redirect()->route('login');
        }
        if(!in_array(Auth::user()->role, [1,3,21,10]))
        {
            return redirect('login');
        }
        
// where('user_id', 1)->
        $forms = FormTemplate::where('active', 1)->latest()->get();

        return view('form-builder.index_v2', ["title"=>"Form Builder", "forms"=>$forms]);
    }

    // public function getFormsV3Template(Request $request)
    // {
    //     if ($request->ajax()) {
        
    //         $forms = FormTemplate::where('active', -1)
    //                     // ->whereIn('id',[361])
    //                     ->latest()
    //                     ->get();           
    //         $data = [];
    //         foreach ($forms as $form) {
    //             $actionBtns = '
                    
    //                 <a href="' . route('form-submission.show', $form->slug) . '" target="_blank" class="text-primary" title="View Form"><i class="fas fa-eye"></i></a>';
                        
    //                     $actionBtns .= '<a href="javascript:void(0);" class="text-warning px-1 duplicate-form" data-id="' . $form->id . '" title="Duplicate">
    //                                 <i class="fas fa-clone"></i>
    //                             </a>';             
                    
    //             $data[] = [
    //                 '<span class="text-primary font-weight-bold">' . $form->title . '</span>',
    //                 $form->is_registration_form==1?'Registration Form':'Survey Form',
    //                 $actionBtns,
    //             ];
    //         }
    
    //         return response()->json(['data' => $data]);
    //     }


    // }

    public function getFormsV3Template(Request $request)
{
    if ($request->ajax()) {

        $forms = FormTemplate::where('active', -1)
                    ->latest()
                    ->get();

        $data = [];
        foreach ($forms as $form) {
            $data[] = [
                'id'       => $form->id,
                'name'     => $form->title,
                'category' => $form->is_registration_form == 1 ? 'Registration Form' : 'Survey Form',
                'view_url' => route('form-submission.show', $form->slug),
            ];
        }

        return response()->json(['data' => $data]);
    }
}

    public function getFormsV3(Request $request)
    {
        if ($request->ajax()) {
        
            // $forms = FormTemplate::where('active', 1)
            //             ->latest()
            //             ->get();
            $filter = $request->get('filter');

            $query = FormTemplate::query();

            if ($filter === 'archived') {
                $query->where('active', 0);
            } elseif ($filter === 'unpublished') {
                $query->where('active', 1)->where('is_published', 0);
            } elseif ($filter === 'published') {
                $query->where('active', 1)->where('is_published', 1);
            } else {
                $query->where('active', 1); 
            }

            $forms = $query->latest()->get();

    if(Auth::user()->role == 3)
    {
        $trainerid = Auth::id();
        $responses = DB::table('form_submissions')
                    ->select('form_template_id', DB::raw('COUNT(*) as total_responses','form_submissions.created_at'))
                    ->leftJoin('users','users.id','=','form_submissions.userid')
                    // ->leftJoin('batch_user_mapping as bum','bum.userid','=','form_submissions.userid')
                    // ->leftJoin('batch_detail as bd','bd.id','=','bum.batchid')
                    // ->whereRaw("FIND_IN_SET($trainerid,bd.assigned_to)")
                    // ->where('users.role',2)
                    ->groupBy('form_template_id')
                    ->pluck('total_responses', 'form_template_id');
    }
    else
    {        
        $responses = DB::table('form_submissions')
                    ->select('form_template_id', DB::raw('COUNT(*) as total_responses','form_submissions.created_at'))
                    ->leftJoin('users','users.id','=','form_submissions.userid')
                    // ->where('users.role',2)
                    ->groupBy('form_template_id')
                    ->pluck('total_responses', 'form_template_id');
    }

        $latestResponses = DB::table('form_submissions')
                    ->select('form_template_id', DB::raw('MAX(created_at) as latest_response'))
                    ->groupBy('form_template_id')
                    ->pluck('latest_response', 'form_template_id');
       
            
//  <a href="' . route('form-report', $form->id) . '" class="text-primary" title="View Form"><i class="fas fa-eye"></i></a>
        $data = [];
        foreach ($forms as $form) {
            // $count = $responses->get($form->id, 0);
            if ($form->active == 0) {
                $statusLabel = 'Archived';
            } elseif ($form->is_published == 1) {
                $statusLabel = 'Published';
            } else {
                $statusLabel = 'Unpublished';
            }
            $actionBtns = '
                
                <a href="' . route('form-submission.show', $form->slug) . '" target="_blank" class="text-primary" title="View Form"><i class="fas fa-eye"></i></a>';

                if((Auth::user()->role == 1 || Auth::user()->role == 10 || Auth::user()->role == 21))
                {
                    if ($form->isEverPublished === 0 || Auth::user()->role == 21) {
                        $actionBtns .= '<a href="' . route('form-builder.edit', $form->id) . '" class="text-success px-1" title="Edit"><i class="fas fa-edit"></i></a>';
                    } else {
                        // $actionBtns .= '<span class="text-muted px-1" title="Editing disabled">'
                        // . '<i class="fas fa-edit"></i>'
                        // . '</span>';

                        $actionBtns .= '<a href="' . route('form-builder.view_disabled_form', $form->id) . '" class="text-success px-1" title="View Form Details"><i class="fas fa-edit"></i></a>';
                    }

                    
                    $actionBtns .= '<a href="javascript:void(0);" class="text-warning px-1 duplicate-form" data-id="' . $form->id . '" title="Duplicate">
                                <i class="fas fa-clone"></i>
                            </a>
                             <a href="javascript:void(0);" class="text-info px-1 " onClick="openBulkOnboardForm('.$form->id.', `'.$form->title.'`)" title="Bulk Upload">
                        <i class="fas fa-upload"></i>
                    </a>';
                    

                    if ($form->is_registration_form == 0 || Auth::user()->role == 21) {
                        $actionBtns .= '
                        <form id="delete-form-' . $form->id . '" action="' 
                            . route('form-builder.destroy', $form->id) 
                            . '" method="POST" style="display:none;">'
                            . csrf_field() 
                            . '</form>
                        <a href="javascript:void(0);" class="text-warning me-1" title="Archive"
                        onclick="if(confirm(\'Are you sure you want to archive this form?\')) 
                            document.getElementById(\'delete-form-' . $form->id . '\').submit();">
                      
                         <i class="fa-solid fa-archive"></i>
                        </a>';
                    }
                
                        if($form->delete_requested == 1 && $form->delete_approved == 0)
                        {
                            $actionBtns .= '
                            <span class="text-warning px-1" title="Delete Request Pending">
                                <i class="fas fa-hourglass-half"></i>
                            </span>';
                        }
                        else
                        {
                            $actionBtns .= '
                            <a href="javascript:void(0);"
                            class="text-danger px-1 permanent-delete-form"
                            data-id="'.$form->id.'"
                            title="'.($form->delete_approved == 1 ? 'Delete' : 'Request Delete').'">
                                <i class="fas fa-trash-alt"></i>
                            </a>';
                        }
                    

                    

                }                
                
                $actionBtns .= '<a href="' . route('form-report', $form->id) . '" class="text-success px-1" title="View form resources" ><i style="color: black" class="fa-solid fa-table"></i></a>
 
                <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary me-1"
                   onclick="copyToClipboard(\'' . route('form-submission.show', $form->slug) . '\', this)" title="Copy Form Link">
                   <i class="fas fa-copy"></i> Form Link
                </a>';

                if ($form->unique_string) {
                $actionBtns .= '<a href="javascript:void(0);" class="btn btn-sm btn-outline-primary me-1"
                                onclick="copyToClipboard(\'' . route('form-submission.short_link_show', $form->unique_string) . '\', this)" title="Copy Short Link">
                                <i class="fas fa-copy"></i> Short Form Link
                                </a>';
                } else {
                    $actionBtns .= '<a href="javascript:void(0);" class="btn btn-sm btn-outline-primary me-1"
                                    onclick="copyToClipboard(\'' . route('form-submission.show', $form->slug) . '\', this)" title="Copy Short Link">
                                    <i class="fas fa-copy"></i> Short Form Link
                                    </a>';
                }
 
            $data[] = [
                $statusLabel,
               '<label class="switch">
                    <input type="checkbox" class="form-status-toggle" data-id="' . $form->id . '" ' . ($form->is_published ? 'checked' : '') . '>
                    <span class="slider"></span>
                </label>',
                '<span class="text-primary font-weight-bold">' . $form->title . '</span>',
                '<div class="d-flex align-items-center">
                    <div class="mr-2" style="min-width: 40px;">'. ($responses[$form->id] ?? 0) .'</div>
                </div>',
                \Carbon\Carbon::parse($form->created_at)->isoFormat('DD MMM YYYY HH:mm:ss'),
                \Carbon\Carbon::parse($latestResponses[$form->id] ?? $form->created_at)->isoFormat('DD MMM YYYY HH:mm:ss'),

                $actionBtns,
            ];
        }
 
        return response()->json(['data' => $data]);
    }


}
    public function getFormsNew(Request $request)
    {
        $title = "Forms";

        if ($request->ajax()) {

            $filter = $request->get('filter');

            $query = FormTemplate::query();

            if ($filter === 'archived') {
                $query->where('active', 0);
            } elseif ($filter === 'unpublished') {
                $query->where('active', 1)->where('is_published', 0);
            } elseif ($filter === 'published') {
                $query->where('active', 1)->where('is_published', 1);
            } else {
                $query->where('active', 1);
            }

            $forms = $query->latest()->get();

            
                $responses = DB::table('form_submissions')
                    ->select('form_template_id', DB::raw('COUNT(*) as total_responses', 'form_submissions.created_at'))
                    ->groupBy('form_template_id')
                    ->pluck('total_responses', 'form_template_id');
            

            $latestResponses = DB::table('form_submissions')
                ->select('form_template_id', DB::raw('MAX(created_at) as latest_response'))
                ->groupBy('form_template_id')
                ->pluck('latest_response', 'form_template_id');

            $data = [];
            $role = 1;
            $canManage = in_array($role, [1, 10, 21]);

            $data = [];
            foreach ($forms as $form) {

                if ($form->active == 0) {
                    $status = 'archived';
                } elseif ($form->is_published == 1) {
                    $status = 'published';
                } else {
                    $status = 'unpublished';
                }


                $data[] = [
                    'id' => $form->id,
                    'name' => $form->title,
                    'status' => $status,
                    'is_published' => (int) $form->is_published,
                    'responses' => ($responses[$form->id] ?? 0),
                    'created' => \Carbon\Carbon::parse($form->created_at)->isoFormat('DD MMM YYYY'),
                    'last' => \Carbon\Carbon::parse(($latestResponses[$form->id] ?? null) ?? $form->created_at)->isoFormat('DD MMM YYYY'),
                    'view_url' => $form->is_dynamic_url == 1 ? route('form-submission.showDynamicUrl', ['slug'=>$form->slug, 'is_dynamic_url'=>'batch_code']) : route('form-submission.show', $form->slug),
                    'edit_url' => ($form->isEverPublished === 0 || $role == 21)
                        ? url("form-builder.edit/$form->id")
                        : url("form-builder.edit/$form->id"),
                    'report_url' => url("form-report/$form->id"),
                    'form_link' => $form->is_dynamic_url == 1 ? route('form-submission.showDynamicUrl', ['slug'=>$form->slug, 'is_dynamic_url'=>'batch_code']) : route('form-submission.show', $form->slug),
                    'short_link' => $form->is_dynamic_url == 1 ? route('form-submission.showDynamicUrlShort', ['slug'=>$form->slug, 'is_dynamic_url'=>'batch_code']) : ($form->unique_string
                        ? route('form-submission.short_link_show', $form->unique_string)
                        : route('form-submission.show', $form->slug)),
                    'archive_url' => route('form-builder.destroy', $form->id),
                    'unarchive_url' => route('form-builder.unarchive', $form->id),
                    'is_registration' => (int) $form->is_registration_form,
                    'form_type' => $form->form_type ?: ($form->is_registration_form ? 'registration' : 'survey'),
                    'delete_requested' => (int) $form->delete_requested,
                    'delete_approved' => (int) $form->delete_approved,
                    'can_manage' => $canManage,
                    'can_edit' => ($form->isEverPublished === 0 || $role == 21),
                ];
            }

            $counts = [
                'all'         => FormTemplate::where('active', 1)->count(),
                'published'   => FormTemplate::where('active', 1)->where('is_published', 1)->count(),
                'unpublished' => FormTemplate::where('active', 1)->where('is_published', 0)->count(),
                'archived'    => FormTemplate::where('active', 0)->count(),
            ];

            return response()->json(['data' => $data, 'counts' => $counts]);
        }

        return view('index_new', ['title' => $title, 'phase' => $phase ?? [],]);



    }


 public function getFormsV2(Request $request)
    {
        if ($request->ajax()) {
            
            $forms = FormTemplate::where('user_id', 1)
                        ->where('active', 1)
                        ->latest()
                        ->get();
            $responses = DB::table('form_submissions')
                        ->select('form_template_id', DB::raw('COUNT(*) as total_responses','form_submissions.created_at'))
                        ->groupBy('form_template_id')
                        ->pluck('total_responses', 'form_template_id');
            $latestResponses = DB::table('form_submissions')
                        ->select('form_template_id', DB::raw('MAX(created_at) as latest_response'))
                        ->groupBy('form_template_id')
                        ->pluck('latest_response', 'form_template_id');
        
            $data = [];
            foreach ($forms as $form) {
                $count = $responses->get($form->id, 0);
                $actionBtns = '                    
                    <a href="' . route('form-submission.show', $form->slug) . '" target="_blank" class="text-primary" title="View Form"><i class="fas fa-eye"></i></a>';

                    if ($form->isEverPublished === 0) {
                        $actionBtns .= '<a href="' . route('form-builder.edit', $form->id) . '" class="text-success px-1" title="Edit"><i class="fas fa-edit"></i></a>';
                    } else {
                        $actionBtns .= '<a href="' . route('form-builder.view_disabled_form', $form->id) . '" class="text-success px-1" title="View Form Details"><i class="fas fa-edit"></i></a>';
                    }

                    $actionBtns .= '<a href="javascript:void(0);" class="text-warning px-1 duplicate-form" data-id="' . $form->id . '" title="Duplicate">
                        <i class="fas fa-clone"></i>
                    </a>
                    <a href="javascript:void(0);" class="text-info px-1 " onClick="openBulkOnboardForm('.$form->id.', `'.$form->title.'`)" title="Bulk Upload">
                        <i class="fas fa-cloud"></i>
                    </a>
                    ';

                    $actionBtns .= '
                        <form id="delete-form-' . $form->id . '" action="' 
                            . route('form-builder.destroy', $form->id) 
                            . '" method="POST" style="display:none;">'
                            . csrf_field() 
                            . '</form>
                        <a href="javascript:void(0);" class="text-danger me-1" title="Delete"
                        onclick="if(confirm(\'Are you sure you want to delete this form?\')) 
                            document.getElementById(\'delete-form-' . $form->id . '\').submit();">
                        <i class="fas fa-trash-alt"></i>
                        </a>';
                    
                    $actionBtns .= '<a href="' . route('form-report', $form->id) . '" class="text-success px-1" title="View form resources" ><i style="color: black" class="fa-solid fa-table"></i></a>
    
                    <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary me-1"
                    onclick="copyToClipboard(\'' . route('form-submission.show', $form->slug) . '\', this)" title="Copy Form Link">
                    <i class="fas fa-copy"></i> Form Link
                    </a>';

                    if ($form->unique_string) {
                    $actionBtns .= '<a href="javascript:void(0);" class="btn btn-sm btn-outline-primary me-1"
                                    onclick="copyToClipboard(\'' . route('form-submission.short_link_show', $form->unique_string) . '\', this)" title="Copy Short Link">
                                    <i class="fas fa-copy"></i> Short Form Link
                                    </a>';
                    } else {
                    $actionBtns .= '<a href="javascript:void(0);" class="btn btn-sm btn-outline-primary me-1"
                        onclick="copyToClipboard(\'' . route('form-submission.show', $form->slug) . '\', this)" title="Copy Short Link">
                        <i class="fas fa-copy"></i> Short Form Link
                        </a>';
                    }
    
                $data[] = [
                '<label class="switch">
                        <input type="checkbox" class="form-status-toggle" data-id="' . $form->id . '" ' . ($form->is_published ? 'checked' : '') . '>
                        <span class="slider"></span>
                    </label>',
                    '<span class="text-primary font-weight-bold">' . $form->title . '</span>',
                    '<div class="d-flex align-items-center">
                        <div class="mr-2" style="min-width: 40px;">'. ($responses[$form->id] ?? 0) .'</div>
                    </div>',
                    \Carbon\Carbon::parse($form->created_at)->isoFormat('DD MMM YYYY HH:mm:ss'),
                    \Carbon\Carbon::parse($latestResponses[$form->id] ?? $form->created_at)->isoFormat('DD MMM YYYY HH:mm:ss'),

                    $actionBtns,
                ];
            }
    
            return response()->json(['data' => $data]);
        }


    }

    public function fetchFormFileFields($id)
    {
        $formRecord = FormTemplate::findOrFail($id);
        $formStructure = json_decode($formRecord->form_structure, true);
        $excelColumns = [];
        $columnDetails = [];

        if ($formRecord->login_required == 1 && !$formRecord->is_registration_form) {
            $columnDetails[] = [
                'column_name'     => 'Email As Per LMS',
                'requirement'     => 'Required',
                'expected_values' => 'Must be an existing email registered on the LMS.'
            ];
        }

        // if (is_array($formStructure)) {
        //     foreach ($formStructure as $field) {
        //         if (isset($field['type']) && $field['type'] == "hidden_field") continue;
                
        //         if (in_array($field['type'], ['file'])) {
        //             $cleanLabel = html_entity_decode($field['label']);
        //             $cleanLabel = strip_tags($cleanLabel);
        //             $cleanLabel = preg_replace('/\s+/u', ' ', $cleanLabel);
        //             $cleanLabel = trim($cleanLabel);
                    
        //             $excelColumns[] = $cleanLabel; 
        //         }
        //     }
        // }

        if (is_array($formStructure)) {
            foreach ($formStructure as $field) {
                // Skip structural/hidden fields
                if (isset($field['type']) && $field['type'] == "hidden_field") continue;
                // if (in_array($field['type'], ['page_break', 'description', 'new_line', 'title', 'file'])) continue;
                if (in_array($field['type'], ['page_break', 'description', 'new_line', 'title', 'file', 'file_upload'])) continue;
                
                // Clean the label for the Column Name
                $cleanLabel = html_entity_decode($field['label'] ?? '');
                $cleanLabel = strip_tags($cleanLabel);
                $cleanLabel = preg_replace('/\s+/u', ' ', $cleanLabel);
                $cleanLabel = trim($cleanLabel);
                
                if (empty($cleanLabel)) continue;

                // 1. Determine Requirement
                $isRequired = (!empty($field['required']) && $field['required'] === true) ? 'Required' : 'Optional';

                // 2. Determine Expected Values based on field type
                $expectedValues = '';

                if (in_array($field['type'], ['file', 'file_upload'])) {
                    $expectedValues = 'Cannot be bulk uploaded. Leave blank in Excel and upload manually on the LMS.';
                    $isRequired = 'Optional (in Excel)';
                    
                } elseif (in_array($field['type'], ['sdp_college', 'selectSDPCollege'])) {
                    $expectedValues = 'Must exactly match a College Name that is already onboarded on the LMS.';

                } elseif ($field['type'] === 'selectState') {
                    $expectedValues = 'Must exactly match a State Name from the list (e.g. Maharashtra, Gujarat).';

                } elseif ($field['type'] === 'selectCity') {
                    $expectedValues = 'Must exactly match a City Name from the list (e.g. Mumbai, Pune).';

                } elseif ($field['type'] === 'selectStateCity') {
                    if (!empty($field['target_elemnt'])) {
                        $expectedValues = 'Must exactly match a State Name from the list (e.g. Maharashtra, Gujarat).';
                    } else {
                        $expectedValues = 'Must exactly match a City Name from the list (e.g. Mumbai, Pune).';
                    }

                } elseif (in_array($field['type'], ['select', 'dropdown', 'radio', 'checkbox', 'multiple_select'])) {
                    // Extract available options
                    $options = [];
                    $optionsArray = $field['options'] ?? $field['choices'] ?? [];
                    foreach ($optionsArray as $opt) {
                        if (is_array($opt)) {
                            $options[] = trim((string)($opt['value'] ?? $opt['text'] ?? $opt['label'] ?? ''));
                        } else {
                            $options[] = trim((string)$opt);
                        }
                    }
                    $expectedValues = 'Must be one of the following exact options: <strong>[' . implode(', ', $options) . ']</strong>';
                    
                } elseif ($field['type'] === 'date') {
                    $expectedValues = 'Date format (e.g., DD/MM/YYYY).';
                    if (!empty($field['start_date'])) $expectedValues .= ' Must be after ' . \Carbon\Carbon::parse($field['start_date'])->format('d/m/Y') . '.';
                    if (!empty($field['end_date'])) $expectedValues .= ' Must be before ' . \Carbon\Carbon::parse($field['end_date'])->format('d/m/Y') . '.';
                
                // --- NEW: Number Limits (Min/Max Value) ---
                } elseif ($field['type'] === 'number') {
                    $expectedValues = 'Must be a valid number.';
                    $constraints = [];
                    if (isset($field['minValue']) && $field['minValue'] !== '') $constraints[] = 'Min: ' . $field['minValue'];
                    if (isset($field['maxValue']) && $field['maxValue'] !== '') $constraints[] = 'Max: ' . $field['maxValue'];
                    if (!empty($constraints)) {
                        $expectedValues .= ' <strong>(' . implode(', ', $constraints) . ')</strong>';
                    }

                // --- NEW: Text/Textarea Limits (Min/Max Length) ---
                } else {
                    $expectedValues = 'Standard text input.';
                    $constraints = [];
                    if (isset($field['minLength']) && $field['minLength'] !== '') $constraints[] = 'Min Length: ' . $field['minLength'] . ' chars';
                    if (isset($field['maxLength']) && $field['maxLength'] !== '') $constraints[] = 'Max Length: ' . $field['maxLength'] . ' chars';
                    if (!empty($constraints)) {
                        $expectedValues .= ' <strong>(' . implode(', ', $constraints) . ')</strong>';
                    }
                }

                // Check for hidden email validations
                if (!empty($field['pattern']) && str_contains($field['pattern'], '@')) {
                    $expectedValues = 'Must be a valid Email Address format.';
                }

                // Build the final array for this column
                $columnDetails[] = [
                    'column_name'     => $cleanLabel,
                    'requirement'     => $isRequired,
                    'expected_values' => $expectedValues
                ];
            }
        }

        return response()->json(['excelColumns'=>$excelColumns, 'columnDetails'=>$columnDetails]);
    }
    public function fetchFormFileFields_old($id)
    {
        $formRecord = FormTemplate::findOrFail($id);
        $formStructure = json_decode($formRecord->form_structure, true);
        $excelColumns = [];
        $columnDetails = [];

        if ($formRecord->login_required == 1 && !$formRecord->is_registration_form) {
            $columnDetails[] = [
                'column_name'     => 'Email As Per LMS',
                'requirement'     => 'Required',
                'expected_values' => 'Must be an existing email registered on the LMS.'
            ];
        }

        if (is_array($formStructure)) {
            foreach ($formStructure as $field) {
                if (isset($field['type']) && $field['type'] == "hidden_field") continue;
                
                if (in_array($field['type'], ['file'])) {
                    
                    $cleanLabel = html_entity_decode($field['label']);
                    $cleanLabel = strip_tags($cleanLabel);
                    $cleanLabel = preg_replace('/\s+/u', ' ', $cleanLabel);
                    $cleanLabel = trim($cleanLabel);
                    
                    $excelColumns[] = $cleanLabel; 
                }
            }
        }

        if (is_array($formStructure)) {
            foreach ($formStructure as $field) {
                // Skip structural/hidden fields
                if (isset($field['type']) && $field['type'] == "hidden_field") continue;
                if (in_array($field['type'], ['page_break', 'description', 'new_line', 'title', 'file'])) continue;
                
                // Clean the label for the Column Name
                $cleanLabel = html_entity_decode($field['label'] ?? '');
                $cleanLabel = strip_tags($cleanLabel);
                $cleanLabel = preg_replace('/\s+/u', ' ', $cleanLabel);
                $cleanLabel = trim($cleanLabel);
                
                if (empty($cleanLabel)) continue;

                // 1. Determine Requirement
                $isRequired = (!empty($field['required']) && $field['required'] === true) ? 'Required' : 'Optional';

                // 2. Determine Expected Values based on field type
                $expectedValues = 'Standard text/number input.'; // Default message

                if (in_array($field['type'], ['file', 'file_upload'])) {
                    $expectedValues = 'Cannot be bulk uploaded. Leave blank in Excel and upload manually on the LMS.';
                    $isRequired = 'Optional (in Excel)';
                    
                } elseif (in_array($field['type'], ['sdp_college', 'selectSDPCollege'])) {
                    $expectedValues = 'Must exactly match a College Name that is already onboarded on the LMS.';
                    
                } elseif (in_array($field['type'], ['select', 'dropdown', 'radio', 'checkbox', 'multiple_select'])) {
                    // Extract available options
                    $options = [];
                    $optionsArray = $field['options'] ?? $field['choices'] ?? [];
                    foreach ($optionsArray as $opt) {
                        if (is_array($opt)) {
                            $options[] = trim((string)($opt['value'] ?? $opt['text'] ?? $opt['label'] ?? ''));
                        } else {
                            $options[] = trim((string)$opt);
                        }
                    }
                    $expectedValues = 'Must be one of the following exact options: <strong>[' . implode(', ', $options) . ']</strong>';
                    
                } elseif ($field['type'] === 'date') {
                    $expectedValues = 'Date format (e.g., DD/MM/YYYY).';
                    if (!empty($field['start_date'])) $expectedValues .= ' Must be after ' . \Carbon\Carbon::parse($field['start_date'])->format('d/m/Y') . '.';
                    if (!empty($field['end_date'])) $expectedValues .= ' Must be before ' . \Carbon\Carbon::parse($field['end_date'])->format('d/m/Y') . '.';
                }

                // Check for hidden email validations
                if (!empty($field['pattern']) && str_contains($field['pattern'], '@')) {
                    $expectedValues = 'Must be a valid Email Address format.';
                }

                // Build the final array for this column
                $columnDetails[] = [
                    'column_name'     => $cleanLabel,
                    'requirement'     => $isRequired,
                    'expected_values' => $expectedValues
                ];
            }
        }

        return response()->json(['excelColumns'=>$excelColumns, 'columnDetails'=>$columnDetails]);
    }

    public function exportTemplate($id)
    {
        $formRecord = FormTemplate::findOrFail($id);
        $formStructure = json_decode($formRecord->form_structure, true);

        $excelColumns = [];

        // --- NEW LOGIC: Add LMS Email Column for Feedback/Login Forms ---
        if ($formRecord->login_required == 1 && !$formRecord->is_registration_form) {
            $excelColumns[] = "Email As Per LMS";
        }

        if (is_array($formStructure)) {
            foreach ($formStructure as $field) {
                if (isset($field['type']) && $field['type'] == "hidden_field") continue;
                
                if (!empty($field['name']) && !in_array($field['type'], ['page_break', 'description', 'new_line', 'title', 'file'])) {
                    
                    $cleanLabel = html_entity_decode($field['label']);
                    $cleanLabel = strip_tags($cleanLabel);
                    $cleanLabel = preg_replace('/\s+/u', ' ', $cleanLabel);
                    $cleanLabel = trim($cleanLabel);
                    
                    $excelColumns[] = $cleanLabel; 
                }
            }
        }

        $fileName = 'form_columns_template_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"$fileName\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($excelColumns) {
            $file = fopen('php://output', 'w');
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $excelColumns);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    public function exportTemplate_old($id)
{
    $formRecord = FormTemplate::findOrFail($id);
    $formStructure = json_decode($formRecord->form_structure, true);

    $excelColumns = [];

    if (is_array($formStructure)) {
        foreach ($formStructure as $field) {
            if ($field['type'] == "hidden_field") continue;
            
            if (!empty($field['name']) && !in_array($field['type'], ['page_break', 'description', 'new_line', 'title', 'file'])) {
                
                // 1. Decode HTML entities (like &amp; or &nbsp;)
                $cleanLabel = html_entity_decode($field['label']);
                
                // 2. Strip any accidental HTML tags (like <b> or <span>)
                $cleanLabel = strip_tags($cleanLabel);
                
                // 3. Convert all double/triple spaces or tabs into a single normal space
                $cleanLabel = preg_replace('/\s+/u', ' ', $cleanLabel);
                
                // 4. Trim leading and trailing spaces from the ends of the string
                $cleanLabel = trim($cleanLabel);
                
                $excelColumns[] = $cleanLabel; 
            }
        }
    }

    $fileName = 'form_columns_template_' . date('Y-m-d_H-i-s') . '.csv';
    
    $headers = [
        "Content-type"        => "text/csv; charset=UTF-8",
        "Content-Disposition" => "attachment; filename=\"$fileName\"",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $callback = function () use ($excelColumns) {
        $file = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
        
        fputcsv($file, $excelColumns);
        
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

    public function initBulkUpload(Request $request, $id)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls']);

        // 1. Store the file in Azure Blob Storage
        $filename = 'bulk_form_' . time() . '.csv';
        $path = $request->file('file')->storeAs('temp_uploads', $filename, 'azure');

        // 2. Open a stream directly from Azure to count the rows
        $stream = Storage::disk('azure')->readStream($path);
        $totalRows = 0;
        
        if ($stream !== false) {
            while (fgetcsv($stream) !== false) {
                $totalRows++;
            }
            fclose($stream);
        }

        // Subtract 1 for the header row
        $totalRows = $totalRows > 0 ? $totalRows - 1 : 0;

        return response()->json([
            'success' => true,
            'file_path' => $path, // This is just 'temp_uploads/filename.csv'
            'total_rows' => $totalRows
        ]);
    }

    public function processBulkUploadChunk(Request $request, $id)
{
    $form = \App\Models\FormTemplate::findOrFail($id);
    
    $path = $request->file_path;
    $offset = (int) $request->offset;
    $limit = (int) $request->limit;
    $totalRows = (int) $request->total_rows;

    $stream = \Illuminate\Support\Facades\Storage::disk('azure')->readStream($path);
    
    if ($stream === false) {
        return response()->json(['success' => false, 'errors' => [['row' => 'System Error', 'messages' => ['Could not read file from Azure.']]]]);
    }

    $normalizeStr = function($str) {
        $str = strip_tags(html_entity_decode((string)$str)); 
        $str = preg_replace('/[^a-zA-Z0-9]/', '', $str); 
        return strtolower($str); 
    };

    // 1. Read headers and normalize them
    $rawHeaders = fgetcsv($stream);
    if (!$rawHeaders) {
        return response()->json(['success' => false, 'errors' => [['row' => 'File Error', 'messages' => ['CSV is empty or invalid.']]]]);
    }
    
    $normalizedHeaders = [];
    foreach ($rawHeaders as $index => $header) {
        $normalizedHeaders[$index] = $normalizeStr($header);
    }

    // 2. Map form structure from Database
    $formStructure = json_decode($form->form_structure, true);
    $fieldMap = [];
    
    if (is_array($formStructure)) {
        foreach ($formStructure as $field) {
            if (isset($field['type']) && $field['type'] == "hidden_field") continue;
            if (!empty($field['name']) && isset($field['label']) && !in_array($field['type'], ['page_break', 'description', 'new_line', 'title', 'file', 'file_upload'])) {
                $normalizedLabel = $normalizeStr($field['label']);
                $fieldMap[$normalizedLabel] = $field; 
            }
        }
    }

    // ==========================================
    // NEW FIX: Strict Template Matching (Missing & Extra Columns)
    // ==========================================
    $extraColumns = [];
    $missingColumns = [];
    $foundMappedFields = [];
    $foundLmsEmail = false;
    
    $toUtf8 = function($str) {
            if (!mb_check_encoding($str, 'UTF-8')) {
                $str = mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
            }
            return $str;
        };

    foreach ($normalizedHeaders as $idx => $normHeader) {
        if ($normHeader === '') continue;

        $isSpecialAuthColumn = ($form->login_required == 1 && !$form->is_registration_form && $normHeader === 'emailasperlms');
        
        if ($isSpecialAuthColumn) {
            $foundLmsEmail = true;
            continue;
        }

        if (!isset($fieldMap[$normHeader])) {
            // $extraColumns[] = $rawHeaders[$idx]; 
            $extraColumns[] = $toUtf8($rawHeaders[$idx]);
        } else {
            $foundMappedFields[] = $normHeader;
        }
    }

    if ($form->login_required == 1 && !$form->is_registration_form && !$foundLmsEmail) {
        $missingColumns[] = 'Email As Per LMS';
    }

    foreach ($fieldMap as $normLabel => $fieldInfo) {
        if (!in_array($normLabel, $foundMappedFields)) {
            // $missingColumns[] = strip_tags(html_entity_decode($fieldInfo['label']));
            $missingColumns[] = $toUtf8(strip_tags(html_entity_decode($fieldInfo['label'])));
        }
    }

    if (!empty($extraColumns) || !empty($missingColumns)) {
        $errorMsgs = [];
        if (!empty($extraColumns)) $errorMsgs[] = 'Extra/Unrecognized columns found: <strong>' . implode(', ', $extraColumns) . '</strong>.';
        if (!empty($missingColumns)) $errorMsgs[] = 'Missing expected columns: <strong>' . implode(', ', $missingColumns) . '</strong>.';
        $errorMsgs[] = 'Please use the exact downloaded template without adding or removing columns.';

        // return response()->json([
        //     'success' => false,
        //     'errors' => [['row' => 'Template Match Error', 'messages' => $errorMsgs]]
        // ]);

        return response()->json([
            'success' => false,
            'errors' => [['row' => 'Template Match Error', 'messages' => $errorMsgs]]
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }
    // ==========================================

    // Build combined state-city pairs map for cross-field validation (once per request)
    // cityFieldName => stateFieldName — only for selectStateCity combined fields
    $stateCityPairs = [];
    foreach ($fieldMap as $fieldData) {
        if ($fieldData['type'] === 'selectStateCity' && !empty($fieldData['target_elemnt'])) {
            $targetElemId = $fieldData['target_elemnt'];
            foreach ($fieldMap as $fieldData2) {
                if ($fieldData2['type'] === 'selectStateCity' &&
                    empty($fieldData2['target_elemnt']) &&
                    ($fieldData2['id'] ?? '') === $targetElemId) {
                    $stateCityPairs[$fieldData2['name']] = $fieldData['name'];
                    break;
                }
            }
        }
    }

    $processed = 0;
    $errors = [];
    $currentRow = 0;

    while ($currentRow < $offset && fgetcsv($stream) !== false) {
        $currentRow++;
    }

    // 4. Process the chunk
    while (($row = fgetcsv($stream)) !== false && $processed < $limit) {
        
        $trimmedRow = array_map('trim', $row);
        if (empty(array_filter($trimmedRow, fn($value) => $value !== ''))) continue; 

        $rowNum = $offset + $processed + 2; 
        
        $submissionData = [];
        $rowToValidate = [];
        $rowRules = [];
        $rowMessages = [];
        $customAttributes = []; 

        $email = null;
        $mobile = null;
        $studentName = null;
        $emailAsPerLms = null; 
        $firstFieldValue = null;

        // Loop through HEADERS, not the row, to catch missing trailing cells
        foreach ($normalizedHeaders as $index => $normalizedLabel) {
            if (!$normalizedLabel) continue; 
            
            $value = $trimmedRow[$index] ?? ''; // Force empty string if cell is missing

            if ($form->login_required == 1 && !$form->is_registration_form && $normalizedLabel === 'emailasperlms') {
                $emailAsPerLms = $value;
                continue; 
            }

            if (!isset($fieldMap[$normalizedLabel])) continue; 

            if (str_contains($normalizedLabel, 'email')) $email = $value;
            if (str_contains($normalizedLabel, 'mobile') || str_contains($normalizedLabel, 'phone')) $mobile = $value;
            if ($firstFieldValue === null && isset($fieldMap[$normalizedLabel])) $firstFieldValue = $value;
            if ($normalizedLabel === 'studentname') $studentName = $value;

            $fieldInfo = $fieldMap[$normalizedLabel];
            $fieldName = $fieldInfo['name']; 
            $friendlyName = trim(strip_tags(html_entity_decode($fieldInfo['label']))); 
            
            $submissionData[$fieldName] = $value;
            $rowToValidate[$fieldName]  = $value;
            $rules = [];

            // College lookup
            if ($fieldInfo['type'] === 'selectSDPCollege' && !empty($value)) {
                $collegeId = \Illuminate\Support\Facades\DB::table('college')->where('college_name', $value)->value('id');
                if ($collegeId) {
                    $submissionData[$fieldName] = $collegeId; 
                    $rowToValidate[$fieldName]  = $collegeId;
                } else {
                    $rules[] = function ($attribute, $val, $fail) use ($value) {
                        $fail("The College '{$value}' was not found in our database records.");
                    };
                }
            }

            // State lookup (selectState or selectStateCity state-half)
            $isStateField = ($fieldInfo['type'] === 'selectState') ||
                ($fieldInfo['type'] === 'selectStateCity' && !empty($fieldInfo['target_elemnt']));

            if ($isStateField && !empty($value)) {
                $stateId = \Illuminate\Support\Facades\DB::table('state')
                    ->where('state', $value)->value('id');
                if ($stateId) {
                    $submissionData[$fieldName] = $stateId;
                    $rowToValidate[$fieldName]  = $stateId;
                } else {
                    $rules[] = function ($attribute, $val, $fail) use ($value, $friendlyName) {
                        $fail("The State '{$value}' was not found. Please enter the exact state name as it appears in the list.");
                    };
                }
            }

            // City lookup (selectCity or selectStateCity city-half)
            $isCityField = ($fieldInfo['type'] === 'selectCity') ||
                ($fieldInfo['type'] === 'selectStateCity' && empty($fieldInfo['target_elemnt']));

            if ($isCityField && !empty($value)) {
                $cityId = \Illuminate\Support\Facades\DB::table('state_wise_cities')
                    ->where('city', $value)->value('id');
                if ($cityId) {
                    $submissionData[$fieldName] = $cityId;
                    $rowToValidate[$fieldName]  = $cityId;
                } else {
                    $rules[] = function ($attribute, $val, $fail) use ($value, $friendlyName) {
                        $fail("The City '{$value}' was not found. Please enter the exact city name as it appears in the list.");
                    };
                }
            }

            // Dropdown exact matching
            $selectTypes = ['select', 'dropdown', 'radio', 'checkbox', 'multiple_select'];
            if (in_array($fieldInfo['type'], $selectTypes) && !empty($value)) {
                $validOptions = [];
                $optionsArray = $fieldInfo['options'] ?? $fieldInfo['choices'] ?? [];
                foreach ($optionsArray as $opt) {
                    if (is_array($opt)) {
                        $validOptions[] = trim((string)($opt['value'] ?? $opt['text'] ?? $opt['label'] ?? ''));
                    } else {
                        $validOptions[] = trim((string)$opt); 
                    }
                }
                if (!empty($validOptions) && !in_array($value, $validOptions)) {
                    $optionsStr = implode(', ', $validOptions);
                    $rules[] = function ($attribute, $val, $fail) use ($value, $optionsStr, $friendlyName) {
                        $fail("The value '{$value}' is invalid for {$friendlyName}. Allowed options are: [{$optionsStr}].");
                    };
                }
            }

            // File Uploads
            if ($fieldInfo['type'] === 'file' || $fieldInfo['type'] === 'file_upload') {
                $rules[] = 'nullable';
            } else {
                $rules[] = (!empty($fieldInfo['required']) && $fieldInfo['required'] === true) ? 'required' : 'nullable';
            }

            // Number Validation (Age / Mins / Maxes)
            if ($fieldInfo['type'] === 'number') {
                $rules[] = 'numeric'; 
                if (isset($fieldInfo['minValue']) && $fieldInfo['minValue'] !== '') $rules[] = 'min:' . $fieldInfo['minValue'];
                if (isset($fieldInfo['maxValue']) && $fieldInfo['maxValue'] !== '') $rules[] = 'max:' . $fieldInfo['maxValue'];
            }

            // Date Validation (Math Based + "t" blocker)
            if ($fieldInfo['type'] === 'date' && !empty($value)) {
                $parsedDate = null;
                
                if (preg_match('/\d/', $value)) {
                    $formats = ['d/m/Y', 'j/n/Y', 'Y-m-d', 'm/d/Y', 'n/j/Y', 'd-m-Y', 'Y/m/d'];
                    foreach($formats as $format) {
                        try {
                            $parsedDate = \Carbon\Carbon::createFromFormat($format, $value);
                            if ($parsedDate) break;
                        } catch (\Exception $e) {}
                    }
                    if (!$parsedDate) {
                        try { $parsedDate = \Carbon\Carbon::parse($value); } catch (\Exception $e) {}
                    }
                }

                if ($parsedDate) {
                    $standardDate = $parsedDate->format('Y-m-d');
                    $submissionData[$fieldName] = $standardDate;
                    $rowToValidate[$fieldName]  = $standardDate;

                    $rules[] = 'date_format:Y-m-d';

                    if (!empty($fieldInfo['start_date']) && $standardDate < $fieldInfo['start_date']) {
                        $rules[] = function ($attribute, $val, $fail) use ($fieldInfo, $friendlyName) {
                            $min = \Carbon\Carbon::parse($fieldInfo['start_date'])->format('d/m/Y');
                            $fail("The {$friendlyName} must be on or after {$min}.");
                        };
                    }
                    if (!empty($fieldInfo['end_date']) && $standardDate > $fieldInfo['end_date']) {
                        $rules[] = function ($attribute, $val, $fail) use ($fieldInfo, $friendlyName) {
                            $max = \Carbon\Carbon::parse($fieldInfo['end_date'])->format('d/m/Y');
                            $fail("The {$friendlyName} must be on or before {$max}.");
                        };
                    }
                } else {
                    $rules[] = function ($attribute, $val, $fail) use ($friendlyName) {
                        $fail("The {$friendlyName} contains an invalid date format. Please use DD/MM/YYYY.");
                    };
                }
            }

            // Standard Text lengths (Only apply min/max if not a number)
            if ($fieldInfo['type'] !== 'number') {
                if (!empty($fieldInfo['minLength'])) $rules[] = 'min:' . $fieldInfo['minLength'];
                if (!empty($fieldInfo['maxLength'])) $rules[] = 'max:' . $fieldInfo['maxLength'];
            }

            if (!empty($fieldInfo['pattern'])) {
                $pattern = str_replace('/', '\/', $fieldInfo['pattern']); 
                $rules[] = 'regex:/' . $pattern . '/';
            }

            if (!empty($rules)) {
                $rowRules[$fieldName] = $rules; 
                $customAttributes[$fieldName] = $friendlyName;
                $rowMessages["{$fieldName}.required"] = "{$friendlyName} is required.";
                $rowMessages["{$fieldName}.numeric"]  = "{$friendlyName} must be a valid number.";
                $rowMessages["{$fieldName}.regex"]    = "{$friendlyName} format is invalid.";
            }
        }

        // 5. Validator
        if (!empty($rowRules)) {
            $validator = \Illuminate\Support\Facades\Validator::make($rowToValidate, $rowRules, $rowMessages, $customAttributes);
            if ($validator->fails()) {
                $errors[] = ['row' => "Row {$rowNum}: Validation Failed", 'messages' => $validator->errors()->all()];
                $processed++;
                continue; 
            }
        }

        // 5b. Cross-validate combined state-city pairs
        $crossValidationFailed = false;
        foreach ($stateCityPairs as $cityFieldName => $stateFieldName) {
            // Skip if either field is absent or didn't resolve to a numeric ID
            // (individual validation already handles the missing/invalid cases)
            if (!isset($submissionData[$cityFieldName]) || !isset($submissionData[$stateFieldName])) continue;
            $resolvedStateId = $submissionData[$stateFieldName];
            $resolvedCityId  = $submissionData[$cityFieldName];
            if (!is_numeric($resolvedStateId) || !is_numeric($resolvedCityId)) continue;

            $cityBelongsToState = \Illuminate\Support\Facades\DB::table('state_wise_cities')
                ->where('id', $resolvedCityId)
                ->where('state_id', $resolvedStateId)
                ->exists();

            if (!$cityBelongsToState) {
                $cityName  = \Illuminate\Support\Facades\DB::table('state_wise_cities')
                    ->where('id', $resolvedCityId)->value('city') ?? $resolvedCityId;
                $stateName = \Illuminate\Support\Facades\DB::table('state')
                    ->where('id', $resolvedStateId)->value('state') ?? $resolvedStateId;
                $errors[] = [
                    'row'      => "Row {$rowNum}: Validation Failed",
                    'messages' => ["The City '{$cityName}' does not belong to the selected State '{$stateName}'. Please enter a city that is within the selected state."]
                ];
                $crossValidationFailed = true;
            }
        }
        if ($crossValidationFailed) {
            $processed++;
            continue;
        }
        if ($studentName === null) $studentName = $firstFieldValue;
        // 6. User Handling
        $userid = \Illuminate\Support\Facades\Auth::id(); 

        if ($form->is_registration_form) {
            if (!$email) {
                $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => ["Missing email address."]];
                $processed++;
                continue;
            }

                $existingUser = DB::table('users')
                    ->where('email', $email)
                    ->whereIn('role', [1,3,4,5,7,8,10,9,11,21,23,100])
                    ->first();

                if ($existingUser) {                
                    $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => ['Email already exist on lms']];
                    $processed++;
                    continue;
                }
                
                $existingUserMobile = DB::table('users')
                    ->where('mobile', $mobile)
                    ->whereIn('role', [1,3,4,5,7,8,10,9,11,21,23,100])
                    ->first();

                if ($existingUserMobile) {       
                    $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => ['Mobile already exist on lms']];
                    $processed++;
                    continue; 
                }

            $allowed_old_phase = $form->allowed_old_phase;

            if($allowed_old_phase == 0)
            {
                $validated['phase'] = DB::table('phase')
                    ->where('active', 1)
                    ->value('phaseid');

                $existingUser = \Illuminate\Support\Facades\DB::table('users')->where('email', $email)->orWhere('mobile', $mobile)->first();

                if ($existingUser) {
                    $conflictMsg = "User already exists on lms.";
                    if (strtolower($existingUser->email) === strtolower($email)) {
                        $conflictMsg = "Email : {$email} already exist on lms.";
                    } elseif ($existingUser->mobile === $mobile) {
                        $conflictMsg = "Mobile : {$mobile} already exist on lms.";
                    }
                    $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => [$conflictMsg]];
                    $processed++;
                    continue; 
                }

                $userid = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
                    "name"           => $studentName ?? 'Student',
                    "email"          => $email,
                    "mobile"         => $mobile,
                    "role"           => 20,
                    "password"       => \Illuminate\Support\Facades\Hash::make("Test@12345"),
                    "active"         => 1,
                    "authentication" => 1,
                ]);

                \Illuminate\Support\Facades\DB::table('student_type')->insert([
                    "userid"           => $userid,
                    "type"             => $form->student_type ?? 0,
                    "password"         => 0,
                    "profile"          => 1,
                    "profile_complete" => 1
                ]);
            }
            else
            {
                $emailUser  = DB::table('users')->where('email', $email)->first();
                $mobileUser = DB::table('users')->where('mobile', $mobile)->first();

                $emailExists  = !is_null($emailUser);
                $mobileExists = !is_null($mobileUser);

                if (!$emailExists && !$mobileExists)
                {
                    $userid = DB::table('users')->insertGetId([
                        "name"=>$studentName,
                        "email"=>$email,
                        "mobile"=>$mobile,
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
                elseif ($emailExists && $mobileExists && $emailUser->id !== $mobileUser->id)
                {
                    // return redirect()->back()
                    // ->withInput()
                    // ->withErrors(['err' => 'Your email and mobile are registered with 2 different accounts.']);
                    $conflictMsg = 'email and mobile are registered with 2 different accounts';
                    $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => [$conflictMsg]];
                    $processed++;
                    continue;
                }
                else
                {
                    $user = $emailUser ?? $mobileUser;

                    // Tell the caller which field(s) matched
                    $matchedFields = [];
                    if ($emailExists  && $emailUser->id  === $user->id) $matchedFields[] = 'email';
                    if ($mobileExists && $mobileUser->id === $user->id) $matchedFields[] = 'mobile';

                    $updateData = [];

                    if (in_array('mobile', $matchedFields) && !in_array('email', $matchedFields))
                    {
                        $updateData['email'] = $email;
                    }
                    else
                    {
                        $updateData['mobile'] = $mobile;
                    }

                    $check_already_reg = DB::table('form_submissions')->where('form_template_id', $form->id)->where('userid', $user->id)->exists();

                    if($check_already_reg)
                    {
                        // return redirect()->back()
                        // ->withInput()
                        // ->withErrors(['err' => 'Your email and mobile are already registered.']);
                        $conflictMsg = 'Your email and mobile are already registered';

                        $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => [$conflictMsg]];
                        $processed++;
                        continue;
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

            $submissionData['phase'] = \Illuminate\Support\Facades\DB::table('phase')->where('active', 1)->value('phaseid');
            $submissionData['profile_updated'] = \Carbon\Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s');
            $submissionData['password_updated'] = null;

        } elseif ($form->login_required == 1) {
            if (empty($emailAsPerLms)) {
                $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => ["'Email As Per LMS' column is missing or empty. This is required for this form."]];
                $processed++;
                continue;
            }

            $lmsUser = \Illuminate\Support\Facades\DB::table('users')->where('email', $emailAsPerLms)->first();

            if (!$lmsUser) {
                $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => ["Email : {$emailAsPerLms} does not exist on lms."]];
                $processed++;
                continue;
            }
            $userid = $lmsUser->id; 
        }

        // Multi-Submission Validation
        if ($form->multi_submission == 0 && $form->login_required == 1) {
            $alreadySubmitted = \App\Models\FormSubmission::where('form_template_id', $form->id)
                ->where('userid', $userid)
                ->exists();

            if ($alreadySubmitted) {
                $errors[] = [
                    'row' => "Row {$rowNum}: Skipped", 
                    'messages' => ["Multiple submissions are disabled. User ({$emailAsPerLms}) already has an entry for this form."]
                ];
                $processed++;
                continue;
            }
        }

        // 7. Save Form Submission
        $submission = \App\Models\FormSubmission::create([
            'form_template_id' => $form->id,
            'submission_data'  => $submissionData,
            'userid'           => $userid,
        ]);

        // try {
        //     app(\App\Http\Controllers\EmailTemplateController::class)->triggerOnSubmit($form->id, $submission->id, $userid);
        // } catch (\Throwable $e) { }

        // if ($userid) {
        //     $check_assigned = \Illuminate\Support\Facades\DB::table('user_topic_completion')->where('userid', $userid)->where('topicid', $form->id)->exists();
        //     if ($check_assigned) {
        //         \Illuminate\Support\Facades\DB::table('user_topic_completion')->where('topicid', $form->id)->where('userid', $userid)->where('completion', 0)->update(['completion' => 1]);
        //     }
        // }

        $processed++;
    }

    if (is_resource($stream)) fclose($stream);

    if (($offset + $processed) >= $totalRows) {
        \Illuminate\Support\Facades\Storage::disk('azure')->delete($path);
    }

    return response()->json([
        'success'   => true,
        'processed' => $processed,
        'errors'    => $errors 
    ]);
}
    public function processBulkUploadChunk_old_V6(Request $request, $id)
{
    $form = \App\Models\FormTemplate::findOrFail($id);
    
    $path = $request->file_path;
    $offset = (int) $request->offset;
    $limit = (int) $request->limit;
    $totalRows = (int) $request->total_rows;

    $stream = \Illuminate\Support\Facades\Storage::disk('azure')->readStream($path);
    
    if ($stream === false) {
        return response()->json(['success' => false, 'errors' => [['row' => 'System Error', 'messages' => ['Could not read file from Azure.']]]]);
    }

    $normalizeStr = function($str) {
        $str = strip_tags(html_entity_decode((string)$str)); 
        $str = preg_replace('/[^a-zA-Z0-9]/', '', $str); 
        return strtolower($str); 
    };

    // 1. Read headers and normalize them
    $rawHeaders = fgetcsv($stream);
    if (!$rawHeaders) {
        return response()->json(['success' => false, 'errors' => [['row' => 'File Error', 'messages' => ['CSV is empty or invalid.']]]]);
    }
    
    $normalizedHeaders = [];
    foreach ($rawHeaders as $index => $header) {
        $normalizedHeaders[$index] = $normalizeStr($header);
    }

    // 2. Map form structure from Database
    $formStructure = json_decode($form->form_structure, true);
    $fieldMap = [];
    
    if (is_array($formStructure)) {
        foreach ($formStructure as $field) {
            if (isset($field['type']) && $field['type'] == "hidden_field") continue;
            if (!empty($field['name']) && isset($field['label']) && !in_array($field['type'], ['page_break', 'description', 'new_line', 'title', 'file', 'file_upload'])) {
                $normalizedLabel = $normalizeStr($field['label']);
                $fieldMap[$normalizedLabel] = $field; 
            }
        }
    }

    // 3. Strict Column Matching
    $extraColumns = [];
    foreach ($normalizedHeaders as $idx => $normHeader) {
        $isSpecialAuthColumn = ($form->login_required == 1 && !$form->is_registration_form && $normHeader === 'emailasperlms');
        if ($normHeader !== '' && !$isSpecialAuthColumn && !isset($fieldMap[$normHeader])) {
            $extraColumns[] = $rawHeaders[$idx]; 
        }
    }

    if (!empty($extraColumns)) {
        return response()->json([
            'success' => false,
            'errors' => [[
                'row' => 'Template Match Error', 
                'messages' => ['Extra or unrecognized columns found: <strong>' . implode(', ', $extraColumns) . '</strong>.', 'Please remove extra columns and only use the exact downloaded template.']
            ]]
        ]);
    }

    $processed = 0;
    $errors = [];
    $currentRow = 0;

    while ($currentRow < $offset && fgetcsv($stream) !== false) {
        $currentRow++;
    }

    // 4. Process the chunk
    while (($row = fgetcsv($stream)) !== false && $processed < $limit) {
        
        $trimmedRow = array_map('trim', $row);
        if (empty(array_filter($trimmedRow, fn($value) => $value !== ''))) continue; 

        $rowNum = $offset + $processed + 2; 
        
        $submissionData = [];
        $rowToValidate = [];
        $rowRules = [];
        $rowMessages = [];
        $customAttributes = []; 

        $email = null;
        $mobile = null;
        $studentName = null;
        $emailAsPerLms = null; 

        foreach ($trimmedRow as $index => $value) {
            $normalizedLabel = $normalizedHeaders[$index] ?? null;
            if (!$normalizedLabel) continue; 

            if ($form->login_required == 1 && !$form->is_registration_form && $normalizedLabel === 'emailasperlms') {
                $emailAsPerLms = $value;
                continue; 
            }

            if (!isset($fieldMap[$normalizedLabel])) continue; 

            if (str_contains($normalizedLabel, 'email')) $email = $value;
            if (str_contains($normalizedLabel, 'mobile') || str_contains($normalizedLabel, 'phone')) $mobile = $value;
            if (str_contains($normalizedLabel, 'name')) $studentName = $value;

            $fieldInfo = $fieldMap[$normalizedLabel];
            $fieldName = $fieldInfo['name']; 
            $friendlyName = trim(strip_tags(html_entity_decode($fieldInfo['label']))); 
            
            $submissionData[$fieldName] = $value;
            $rowToValidate[$fieldName]  = $value;
            $rules = [];

            // College lookup
            if ($fieldInfo['type'] === 'selectSDPCollege' && !empty($value)) {
                $collegeId = \Illuminate\Support\Facades\DB::table('college')->where('college_name', $value)->value('id');
                if ($collegeId) {
                    $submissionData[$fieldName] = $collegeId; 
                    $rowToValidate[$fieldName]  = $collegeId;
                } else {
                    $rules[] = function ($attribute, $val, $fail) use ($value) {
                        $fail("The College '{$value}' was not found in our database records.");
                    };
                }
            }

            // State lookup (selectState or selectStateCity state-half)
            $isStateField = ($fieldInfo['type'] === 'selectState') ||
                ($fieldInfo['type'] === 'selectStateCity' && !empty($fieldInfo['target_elemnt']));

            if ($isStateField && !empty($value)) {
                $stateId = \Illuminate\Support\Facades\DB::table('state')
                    ->where('state', $value)->value('id');
                if ($stateId) {
                    $submissionData[$fieldName] = $stateId;
                    $rowToValidate[$fieldName]  = $stateId;
                } else {
                    $rules[] = function ($attribute, $val, $fail) use ($value, $friendlyName) {
                        $fail("The State '{$value}' was not found. Please enter the exact state name as it appears in the list.");
                    };
                }
            }

            // City lookup (selectCity or selectStateCity city-half)
            $isCityField = ($fieldInfo['type'] === 'selectCity') ||
                ($fieldInfo['type'] === 'selectStateCity' && empty($fieldInfo['target_elemnt']));

            if ($isCityField && !empty($value)) {
                $cityId = \Illuminate\Support\Facades\DB::table('state_wise_cities')
                    ->where('city', $value)->value('id');
                if ($cityId) {
                    $submissionData[$fieldName] = $cityId;
                    $rowToValidate[$fieldName]  = $cityId;
                } else {
                    $rules[] = function ($attribute, $val, $fail) use ($value, $friendlyName) {
                        $fail("The City '{$value}' was not found. Please enter the exact city name as it appears in the list.");
                    };
                }
            }

            // Dropdown exact matching
            $selectTypes = ['select', 'dropdown', 'radio', 'checkbox', 'multiple_select'];
            if (in_array($fieldInfo['type'], $selectTypes) && !empty($value)) {
                $validOptions = [];
                $optionsArray = $fieldInfo['options'] ?? $fieldInfo['choices'] ?? [];
                foreach ($optionsArray as $opt) {
                    if (is_array($opt)) {
                        $validOptions[] = trim((string)($opt['value'] ?? $opt['text'] ?? $opt['label'] ?? ''));
                    } else {
                        $validOptions[] = trim((string)$opt); 
                    }
                }
                if (!empty($validOptions) && !in_array($value, $validOptions)) {
                    $optionsStr = implode(', ', $validOptions);
                    $rules[] = function ($attribute, $val, $fail) use ($value, $optionsStr, $friendlyName) {
                        $fail("The value '{$value}' is invalid for {$friendlyName}. Allowed options are: [{$optionsStr}].");
                    };
                }
            }

            // File Uploads
            if ($fieldInfo['type'] === 'file' || $fieldInfo['type'] === 'file_upload') {
                $rules[] = 'nullable';
            } else {
                $rules[] = (!empty($fieldInfo['required']) && $fieldInfo['required'] === true) ? 'required' : 'nullable';
            }

            // Date Validation
            if ($fieldInfo['type'] === 'date' && !empty($value)) {
                $parsedDate = null;
                $formats = ['d/m/Y', 'j/n/Y', 'Y-m-d', 'm/d/Y', 'n/j/Y', 'd-m-Y'];
                foreach($formats as $format) {
                    try {
                        $parsedDate = \Carbon\Carbon::createFromFormat($format, $value);
                        if ($parsedDate) break;
                    } catch (\Exception $e) {}
                }
                if (!$parsedDate) {
                    try { $parsedDate = \Carbon\Carbon::parse($value); } catch (\Exception $e) {}
                }

                if ($parsedDate) {
                    $standardDate = $parsedDate->format('Y-m-d');
                    $submissionData[$fieldName] = $standardDate;
                    $rowToValidate[$fieldName]  = $standardDate;

                    $rules[] = 'date_format:Y-m-d';

                    if (!empty($fieldInfo['start_date']) && $standardDate < $fieldInfo['start_date']) {
                        $rules[] = function ($attribute, $val, $fail) use ($fieldInfo, $friendlyName) {
                            $min = \Carbon\Carbon::parse($fieldInfo['start_date'])->format('d/m/Y');
                            $fail("The {$friendlyName} must be on or after {$min}.");
                        };
                    }
                    if (!empty($fieldInfo['end_date']) && $standardDate > $fieldInfo['end_date']) {
                        $rules[] = function ($attribute, $val, $fail) use ($fieldInfo, $friendlyName) {
                            $max = \Carbon\Carbon::parse($fieldInfo['end_date'])->format('d/m/Y');
                            $fail("The {$friendlyName} must be on or before {$max}.");
                        };
                    }
                } else {
                    $rules[] = function ($attribute, $val, $fail) use ($friendlyName) {
                        $fail("The {$friendlyName} contains an invalid date format. Please use DD/MM/YYYY.");
                    };
                }
            }

            if (!empty($fieldInfo['minLength'])) $rules[] = 'min:' . $fieldInfo['minLength'];
            if (!empty($fieldInfo['maxLength'])) $rules[] = 'max:' . $fieldInfo['maxLength'];
            if (!empty($fieldInfo['pattern'])) {
                $pattern = str_replace('/', '\/', $fieldInfo['pattern']); 
                $rules[] = 'regex:/' . $pattern . '/';
            }

            if (!empty($rules)) {
                $rowRules[$fieldName] = $rules; 
                $customAttributes[$fieldName] = $friendlyName;
                $rowMessages["{$fieldName}.required"] = "{$friendlyName} is required.";
                $rowMessages["{$fieldName}.regex"]    = "{$friendlyName} format is invalid.";
            }
        }

        // 5. Validator
        if (!empty($rowRules)) {
            $validator = \Illuminate\Support\Facades\Validator::make($rowToValidate, $rowRules, $rowMessages, $customAttributes);
            if ($validator->fails()) {
                $errors[] = ['row' => "Row {$rowNum}: Validation Failed", 'messages' => $validator->errors()->all()];
                $processed++;
                continue; 
            }
        }

        

        // 6. User Handling
        $userid = \Illuminate\Support\Facades\Auth::id(); 

        if ($form->is_registration_form) {
            if (!$email) {
                $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => ["Missing email address."]];
                $processed++;
                continue;
            }

            $existingUser = \Illuminate\Support\Facades\DB::table('users')->where('email', $email)->orWhere('mobile', $mobile)->first();

            if ($existingUser) {
                $conflictMsg = "User already exists on lms.";
                if (strtolower($existingUser->email) === strtolower($email)) {
                    $conflictMsg = "Email : {$email} already exist on lms.";
                } elseif ($existingUser->mobile === $mobile) {
                    $conflictMsg = "Mobile : {$mobile} already exist on lms.";
                }
                $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => [$conflictMsg]];
                $processed++;
                continue; 
            }

            $userid = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
                "name"           => $studentName ?? 'Student',
                "email"          => $email,
                "mobile"         => $mobile,
                "role"           => 20,
                "password"       => \Illuminate\Support\Facades\Hash::make("Test@12345"),
                "active"         => 1,
                "authentication" => 1,
            ]);

            \Illuminate\Support\Facades\DB::table('student_type')->insert([
                "userid"           => $userid,
                "type"             => $form->student_type ?? 0,
                "password"         => 0,
                "profile"          => 1,
                "profile_complete" => 1
            ]);

            $submissionData['phase'] = \Illuminate\Support\Facades\DB::table('phase')->where('active', 1)->value('phaseid');
            $submissionData['profile_updated'] = \Carbon\Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s');
            $submissionData['password_updated'] = null;

        } elseif ($form->login_required == 1) {
            if (empty($emailAsPerLms)) {
                $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => ["'Email As Per LMS' column is missing or empty. This is required for this form."]];
                $processed++;
                continue;
            }

            $lmsUser = \Illuminate\Support\Facades\DB::table('users')->where('email', $emailAsPerLms)->first();

            if (!$lmsUser) {
                $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => ["Email : {$emailAsPerLms} does not exist on lms."]];
                $processed++;
                continue;
            }
            $userid = $lmsUser->id; 
        }

        // ==========================================
        // NEW FIX: Multi-Submission Validation
        // ==========================================
        // We strictly apply this rule to Login Required forms.
        // (Registration forms already block duplicates via the 'existingUser' check above)
        if ($form->multi_submission == 0 && $form->login_required == 1) {
            
            $alreadySubmitted = \App\Models\FormSubmission::where('form_template_id', $form->id)
                ->where('userid', $userid)
                ->exists();

            if ($alreadySubmitted) {
                $errors[] = [
                    'row' => "Row {$rowNum}: Skipped", 
                    'messages' => ["Multiple submissions are disabled. User ({$emailAsPerLms}) already has an entry for this form."]
                ];
                $processed++;
                continue;
            }
        }
        // ==========================================

        // 7. Save Form Submission
        $submission = \App\Models\FormSubmission::create([
            'form_template_id' => $form->id,
            'submission_data'  => $submissionData,
            'userid'           => $userid,
        ]);

        try {
            app(\App\Http\Controllers\EmailTemplateController::class)->triggerOnSubmit($form->id, $submission->id, $userid);
        } catch (\Throwable $e) { }

        if ($userid) {
            $check_assigned = \Illuminate\Support\Facades\DB::table('user_topic_completion')->where('userid', $userid)->where('topicid', $form->id)->exists();
            if ($check_assigned) {
                \Illuminate\Support\Facades\DB::table('user_topic_completion')->where('topicid', $form->id)->where('userid', $userid)->where('completion', 0)->update(['completion' => 1]);
            }
        }

        $processed++;
    }

    if (is_resource($stream)) fclose($stream);

    if (($offset + $processed) >= $totalRows) {
        \Illuminate\Support\Facades\Storage::disk('azure')->delete($path);
    }

    return response()->json([
        'success'   => true,
        'processed' => $processed,
        'errors'    => $errors 
    ]);
}

    public function processBulkUploadChunk_old_V5(Request $request, $id)
{
    $form = \App\Models\FormTemplate::findOrFail($id);
    
    $path = $request->file_path;
    $offset = (int) $request->offset;
    $limit = (int) $request->limit;
    $totalRows = (int) $request->total_rows;

    $stream = \Illuminate\Support\Facades\Storage::disk('azure')->readStream($path);
    
    if ($stream === false) {
        return response()->json(['success' => false, 'errors' => [['row' => 'System Error', 'messages' => ['Could not read file from Azure.']]]]);
    }

    $normalizeStr = function($str) {
        $str = strip_tags(html_entity_decode((string)$str)); 
        $str = preg_replace('/[^a-zA-Z0-9]/', '', $str); 
        return strtolower($str); 
    };

    // 1. Read headers and normalize them
    $rawHeaders = fgetcsv($stream);
    if (!$rawHeaders) {
        return response()->json(['success' => false, 'errors' => [['row' => 'File Error', 'messages' => ['CSV is empty or invalid.']]]]);
    }
    
    $normalizedHeaders = [];
    foreach ($rawHeaders as $index => $header) {
        $normalizedHeaders[$index] = $normalizeStr($header);
    }

    // 2. Map form structure from Database
    $formStructure = json_decode($form->form_structure, true);
    $fieldMap = [];
    
    if (is_array($formStructure)) {
        foreach ($formStructure as $field) {
            if (isset($field['type']) && $field['type'] == "hidden_field") continue;
            if (!empty($field['name']) && isset($field['label']) && !in_array($field['type'], ['page_break', 'description', 'new_line', 'title', 'file', 'file_upload'])) {
                $normalizedLabel = $normalizeStr($field['label']);
                $fieldMap[$normalizedLabel] = $field; 
            }
        }
    }

    // 3. Strict Column Matching
    $extraColumns = [];
    foreach ($normalizedHeaders as $idx => $normHeader) {
        $isSpecialAuthColumn = ($form->login_required == 1 && !$form->is_registration_form && $normHeader === 'emailasperlms');
        if ($normHeader !== '' && !$isSpecialAuthColumn && !isset($fieldMap[$normHeader])) {
            $extraColumns[] = $rawHeaders[$idx]; 
        }
    }

    if (!empty($extraColumns)) {
        return response()->json([
            'success' => false,
            'errors' => [[
                'row' => 'Template Match Error', 
                'messages' => ['Extra or unrecognized columns found: <strong>' . implode(', ', $extraColumns) . '</strong>.', 'Please remove extra columns and only use the exact downloaded template.']
            ]]
        ]);
    }

    $processed = 0;
    $errors = [];
    $currentRow = 0;

    while ($currentRow < $offset && fgetcsv($stream) !== false) {
        $currentRow++;
    }

    // 4. Process the chunk
    while (($row = fgetcsv($stream)) !== false && $processed < $limit) {
        
        $trimmedRow = array_map('trim', $row);
        if (empty(array_filter($trimmedRow, fn($value) => $value !== ''))) continue; 

        $rowNum = $offset + $processed + 2; 
        
        $submissionData = [];
        $rowToValidate = [];
        $rowRules = [];
        $rowMessages = [];
        $customAttributes = []; 

        $email = null;
        $mobile = null;
        $studentName = null;
        $emailAsPerLms = null; 

        foreach ($trimmedRow as $index => $value) {
            $normalizedLabel = $normalizedHeaders[$index] ?? null;
            if (!$normalizedLabel) continue; 

            if ($form->login_required == 1 && !$form->is_registration_form && $normalizedLabel === 'emailasperlms') {
                $emailAsPerLms = $value;
                continue; 
            }

            if (!isset($fieldMap[$normalizedLabel])) continue; 

            if (str_contains($normalizedLabel, 'email')) $email = $value;
            if (str_contains($normalizedLabel, 'mobile') || str_contains($normalizedLabel, 'phone')) $mobile = $value;
            if (str_contains($normalizedLabel, 'name')) $studentName = $value;

            $fieldInfo = $fieldMap[$normalizedLabel];
            $fieldName = $fieldInfo['name']; 
            $friendlyName = trim(strip_tags(html_entity_decode($fieldInfo['label']))); 
            
            $submissionData[$fieldName] = $value;
            $rowToValidate[$fieldName]  = $value;
            $rules = [];

            // College lookup
            if ($fieldInfo['type'] === 'sdp_college' && !empty($value)) {
                $collegeId = \Illuminate\Support\Facades\DB::table('college')->where('college_name', $value)->value('id');
                if ($collegeId) {
                    $submissionData[$fieldName] = $collegeId; 
                    $rowToValidate[$fieldName]  = $collegeId;
                } else {
                    $rules[] = function ($attribute, $val, $fail) use ($value) {
                        $fail("The College '{$value}' was not found in our database records.");
                    };
                }
            }

            // Dropdown exact matching
            $selectTypes = ['select', 'dropdown', 'radio', 'checkbox', 'multiple_select'];
            if (in_array($fieldInfo['type'], $selectTypes) && !empty($value)) {
                $validOptions = [];
                $optionsArray = $fieldInfo['options'] ?? $fieldInfo['choices'] ?? [];
                foreach ($optionsArray as $opt) {
                    if (is_array($opt)) {
                        $validOptions[] = trim((string)($opt['value'] ?? $opt['text'] ?? $opt['label'] ?? ''));
                    } else {
                        $validOptions[] = trim((string)$opt); 
                    }
                }
                if (!empty($validOptions) && !in_array($value, $validOptions)) {
                    $optionsStr = implode(', ', $validOptions);
                    $rules[] = function ($attribute, $val, $fail) use ($value, $optionsStr, $friendlyName) {
                        $fail("The value '{$value}' is invalid for {$friendlyName}. Allowed options are: [{$optionsStr}].");
                    };
                }
            }

            // File Uploads
            if ($fieldInfo['type'] === 'file' || $fieldInfo['type'] === 'file_upload') {
                $rules[] = 'nullable';
            } else {
                $rules[] = (!empty($fieldInfo['required']) && $fieldInfo['required'] === true) ? 'required' : 'nullable';
            }

            // ==========================================
            // DATE VALIDATION (100% Fail-Proof Math)
            // ==========================================
            if ($fieldInfo['type'] === 'date' && !empty($value)) {
                $parsedDate = null;
                
                // j/n/Y handles single digits (like 1/1/2001) seamlessly
                $formats = ['d/m/Y', 'j/n/Y', 'Y-m-d', 'm/d/Y', 'n/j/Y', 'd-m-Y'];
                foreach($formats as $format) {
                    try {
                        $parsedDate = \Carbon\Carbon::createFromFormat($format, $value);
                        if ($parsedDate) break;
                    } catch (\Exception $e) {}
                }
                if (!$parsedDate) {
                    try { $parsedDate = \Carbon\Carbon::parse($value); } catch (\Exception $e) {}
                }

                if ($parsedDate) {
                    $standardDate = $parsedDate->format('Y-m-d');
                    $submissionData[$fieldName] = $standardDate;
                    $rowToValidate[$fieldName]  = $standardDate;

                    $rules[] = 'date_format:Y-m-d';

                    // Direct string comparison math before validator runs
                    if (!empty($fieldInfo['start_date']) && $standardDate < $fieldInfo['start_date']) {
                        $rules[] = function ($attribute, $val, $fail) use ($fieldInfo, $friendlyName) {
                            $min = \Carbon\Carbon::parse($fieldInfo['start_date'])->format('d/m/Y');
                            $fail("The {$friendlyName} must be on or after {$min}.");
                        };
                    }
                    if (!empty($fieldInfo['end_date']) && $standardDate > $fieldInfo['end_date']) {
                        $rules[] = function ($attribute, $val, $fail) use ($fieldInfo, $friendlyName) {
                            $max = \Carbon\Carbon::parse($fieldInfo['end_date'])->format('d/m/Y');
                            $fail("The {$friendlyName} must be on or before {$max}.");
                        };
                    }
                } else {
                    $rules[] = function ($attribute, $val, $fail) use ($friendlyName) {
                        $fail("The {$friendlyName} contains an invalid date format. Please use DD/MM/YYYY.");
                    };
                }
            }
            // ==========================================

            if (!empty($fieldInfo['minLength'])) $rules[] = 'min:' . $fieldInfo['minLength'];
            if (!empty($fieldInfo['maxLength'])) $rules[] = 'max:' . $fieldInfo['maxLength'];
            if (!empty($fieldInfo['pattern'])) {
                $pattern = str_replace('/', '\/', $fieldInfo['pattern']); 
                $rules[] = 'regex:/' . $pattern . '/';
            }

            if (!empty($rules)) {
                $rowRules[$fieldName] = $rules; 
                $customAttributes[$fieldName] = $friendlyName;
                $rowMessages["{$fieldName}.required"] = "{$friendlyName} is required.";
                $rowMessages["{$fieldName}.regex"]    = "{$friendlyName} format is invalid.";
            }
        }

        // 5. Validator
        if (!empty($rowRules)) {
            $validator = \Illuminate\Support\Facades\Validator::make($rowToValidate, $rowRules, $rowMessages, $customAttributes);
            if ($validator->fails()) {
                $errors[] = ['row' => "Row {$rowNum}: Validation Failed", 'messages' => $validator->errors()->all()];
                $processed++;
                continue; 
            }
        }

        

        // 6. User Handling
        $userid = \Illuminate\Support\Facades\Auth::id(); 

        if ($form->is_registration_form) {
            if (!$email) {
                $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => ["Missing email address."]];
                $processed++;
                continue;
            }

            $existingUser = \Illuminate\Support\Facades\DB::table('users')->where('email', $email)->orWhere('mobile', $mobile)->first();

            if ($existingUser) {
                $conflictMsg = "User already exists on lms.";
                if (strtolower($existingUser->email) === strtolower($email)) {
                    $conflictMsg = "Email : {$email} already exist on lms.";
                } elseif ($existingUser->mobile === $mobile) {
                    $conflictMsg = "Mobile : {$mobile} already exist on lms.";
                }
                $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => [$conflictMsg]];
                $processed++;
                continue; 
            }

            $userid = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
                "name"           => $studentName ?? 'Student',
                "email"          => $email,
                "mobile"         => $mobile,
                "role"           => 20,
                "password"       => \Illuminate\Support\Facades\Hash::make("Test@12345"),
                "active"         => 1,
                "authentication" => 1,
            ]);

            \Illuminate\Support\Facades\DB::table('student_type')->insert([
                "userid"           => $userid,
                "type"             => $form->student_type ?? 0,
                "password"         => 0,
                "profile"          => 1,
                "profile_complete" => 1
            ]);

            $submissionData['phase'] = \Illuminate\Support\Facades\DB::table('phase')->where('active', 1)->value('phaseid');
            $submissionData['profile_updated'] = \Carbon\Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s');
            $submissionData['password_updated'] = null;

        } elseif ($form->login_required == 1) {
            if (empty($emailAsPerLms)) {
                $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => ["'Email As Per LMS' column is missing or empty. This is required for this form."]];
                $processed++;
                continue;
            }

            $lmsUser = \Illuminate\Support\Facades\DB::table('users')->where('email', $emailAsPerLms)->first();

            if (!$lmsUser) {
                $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => ["Email : {$emailAsPerLms} does not exist on lms."]];
                $processed++;
                continue;
            }
            $userid = $lmsUser->id; 
        }

        // 7. Save Form Submission
        $submission = \App\Models\FormSubmission::create([
            'form_template_id' => $form->id,
            'submission_data'  => $submissionData,
            'userid'           => $userid,
        ]);

        try {
            app(\App\Http\Controllers\EmailTemplateController::class)->triggerOnSubmit($form->id, $submission->id, $userid);
        } catch (\Throwable $e) { }

        if ($userid) {
            $check_assigned = \Illuminate\Support\Facades\DB::table('user_topic_completion')->where('userid', $userid)->where('topicid', $form->id)->exists();
            if ($check_assigned) {
                \Illuminate\Support\Facades\DB::table('user_topic_completion')->where('topicid', $form->id)->where('userid', $userid)->where('completion', 0)->update(['completion' => 1]);
            }
        }

        $processed++;
    }

    if (is_resource($stream)) fclose($stream);

    if (($offset + $processed) >= $totalRows) {
        \Illuminate\Support\Facades\Storage::disk('azure')->delete($path);
    }

    return response()->json([
        'success'   => true,
        'processed' => $processed,
        'errors'    => $errors 
    ]);
}

    public function processBulkUploadChunk_old_V4(Request $request, $id)
    {
        $form = FormTemplate::findOrFail($id);
        
        $path = $request->file_path;
        $offset = (int) $request->offset;
        $limit = (int) $request->limit;
        $totalRows = (int) $request->total_rows;

        $stream = \Illuminate\Support\Facades\Storage::disk('azure')->readStream($path);
        
        if ($stream === false) {
            return response()->json(['success' => false, 'errors' => [['row' => 'System Error', 'messages' => ['Could not read file from Azure.']]]]);
        }

        $normalizeStr = function($str) {
            $str = strip_tags(html_entity_decode((string)$str)); 
            $str = preg_replace('/[^a-zA-Z0-9]/', '', $str); 
            return strtolower($str); 
        };

        // 1. Read headers and normalize them
        $rawHeaders = fgetcsv($stream);
        if (!$rawHeaders) {
            return response()->json(['success' => false, 'errors' => [['row' => 'File Error', 'messages' => ['CSV is empty or invalid.']]]]);
        }
        
        $normalizedHeaders = [];
        foreach ($rawHeaders as $index => $header) {
            $normalizedHeaders[$index] = $normalizeStr($header);
        }

        // 2. Map form structure from Database
        $formStructure = json_decode($form->form_structure, true);
        $fieldMap = [];
        
        if (is_array($formStructure)) {
            foreach ($formStructure as $field) {
                if (isset($field['type']) && $field['type'] == "hidden_field") continue;
                
                if (!empty($field['name']) && isset($field['label']) && !in_array($field['type'], ['page_break', 'description', 'new_line', 'title', 'file', 'file_upload'])) {
                    $normalizedLabel = $normalizeStr($field['label']);
                    $fieldMap[$normalizedLabel] = $field; 
                }
            }
        }

        // 3. Strict Column Matching
        $extraColumns = [];
        foreach ($normalizedHeaders as $idx => $normHeader) {
            // Exclude the special "Email As Per LMS" column from throwing an extra column error
            $isSpecialAuthColumn = ($form->login_required == 1 && !$form->is_registration_form && $normHeader === 'emailasperlms');

            if ($normHeader !== '' && !$isSpecialAuthColumn && !isset($fieldMap[$normHeader])) {
                $extraColumns[] = $rawHeaders[$idx]; 
            }
        }

        if (!empty($extraColumns)) {
            return response()->json([
                'success' => false,
                'errors' => [[
                    'row' => 'Template Match Error', 
                    'messages' => ['Extra or unrecognized columns found: <strong>' . implode(', ', $extraColumns) . '</strong>.', 'Please remove extra columns and only use the exact downloaded template.']
                ]]
            ]);
        }

        $processed = 0;
        $errors = [];
        $currentRow = 0;

        while ($currentRow < $offset && fgetcsv($stream) !== false) {
            $currentRow++;
        }

        // 4. Process the chunk
        while (($row = fgetcsv($stream)) !== false && $processed < $limit) {
            
            $trimmedRow = array_map('trim', $row);
            if (empty(array_filter($trimmedRow, fn($value) => $value !== ''))) continue; 

            $rowNum = $offset + $processed + 2; 
            
            $submissionData = [];
            $rowToValidate = [];
            $rowRules = [];
            $rowMessages = [];
            $customAttributes = []; 

            $email = null;
            $mobile = null;
            $studentName = null;
            $emailAsPerLms = null; // New Variable

            foreach ($trimmedRow as $index => $value) {
                $normalizedLabel = $normalizedHeaders[$index] ?? null;
                if (!$normalizedLabel) continue; 

                // Detect the special LMS email column
                if ($form->login_required == 1 && !$form->is_registration_form && $normalizedLabel === 'emailasperlms') {
                    $emailAsPerLms = $value;
                    continue; // Skip the fieldMap check below for this system column
                }

                if (!isset($fieldMap[$normalizedLabel])) continue; 

                if (str_contains($normalizedLabel, 'email')) $email = $value;
                if (str_contains($normalizedLabel, 'mobile') || str_contains($normalizedLabel, 'phone')) $mobile = $value;
                if (str_contains($normalizedLabel, 'name')) $studentName = $value;

                $fieldInfo = $fieldMap[$normalizedLabel];
                $fieldName = $fieldInfo['name']; 
                $friendlyName = trim(strip_tags(html_entity_decode($fieldInfo['label']))); 
                
                $submissionData[$fieldName] = $value;
                $rowToValidate[$fieldName]  = $value;
                $rules = [];

                // sdp_college lookup
                if ($fieldInfo['type'] === 'selectSDPCollege' && !empty($value)) {
                    $collegeId = DB::table('college')->where('college_name', $value)->value('id');
                    if ($collegeId) {
                        $submissionData[$fieldName] = $collegeId; 
                        $rowToValidate[$fieldName]  = $collegeId;
                    } else {
                        $rules[] = function ($attribute, $val, $fail) use ($value) {
                            $fail("The College '{$value}' was not found in our database records.");
                        };
                    }
                }

                // Dropdown matching
                $selectTypes = ['select', 'dropdown', 'radio', 'checkbox', 'multiple_select'];
                if (in_array($fieldInfo['type'], $selectTypes) && !empty($value)) {
                    $validOptions = [];
                    $optionsArray = $fieldInfo['options'] ?? $fieldInfo['choices'] ?? [];
                    foreach ($optionsArray as $opt) {
                        if (is_array($opt)) {
                            $validOptions[] = trim((string)($opt['value'] ?? $opt['text'] ?? $opt['label'] ?? ''));
                        } else {
                            $validOptions[] = trim((string)$opt); 
                        }
                    }

                    if (!empty($validOptions) && !in_array($value, $validOptions)) {
                        $optionsStr = implode(', ', $validOptions);
                        $rules[] = function ($attribute, $val, $fail) use ($value, $optionsStr, $friendlyName) {
                            $fail("The value '{$value}' is invalid for {$friendlyName}. Allowed options are: [{$optionsStr}].");
                        };
                    }
                }

                // Optional File Fields
                if ($fieldInfo['type'] === 'file' || $fieldInfo['type'] === 'file_upload') {
                    $rules[] = 'nullable';
                } else {
                    if (!empty($fieldInfo['required']) && $fieldInfo['required'] === true) {
                        $rules[] = 'required';
                    } else {
                        $rules[] = 'nullable';
                    }
                }

                if (!empty($fieldInfo['minLength'])) $rules[] = 'min:' . $fieldInfo['minLength'];
                if (!empty($fieldInfo['maxLength'])) $rules[] = 'max:' . $fieldInfo['maxLength'];
                if (!empty($fieldInfo['pattern'])) {
                    $pattern = str_replace('/', '\/', $fieldInfo['pattern']); 
                    $rules[] = 'regex:/' . $pattern . '/';
                }

                if (!empty($rules)) {
                    $rowRules[$fieldName] = $rules; 
                    $customAttributes[$fieldName] = $friendlyName;
                    $rowMessages["{$fieldName}.required"] = "{$friendlyName} is required.";
                    $rowMessages["{$fieldName}.regex"]    = "{$friendlyName} format is invalid.";
                }
            }

            if (!empty($rowRules)) {
                $validator = \Illuminate\Support\Facades\Validator::make($rowToValidate, $rowRules, $rowMessages, $customAttributes);
                if ($validator->fails()) {
                    $errors[] = ['row' => "Row {$rowNum}: Validation Failed", 'messages' => $validator->errors()->all()];
                    $processed++;
                    continue; 
                }
            }

            // 5. User Handling Logic (Registration vs Feedback)
            $userid = \Illuminate\Support\Facades\Auth::id(); 

            if ($form->is_registration_form) {
                if (!$email) {
                    $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => ["Missing email address."]];
                    $processed++;
                    continue;
                }

                $existingUser = \Illuminate\Support\Facades\DB::table('users')->where('email', $email)->orWhere('mobile', $mobile)->first();

                if ($existingUser) {
                    // --- NEW LOGIC: Detailed Existing User Errors ---
                    $conflictMsg = "User already exists on lms.";
                    if (strtolower($existingUser->email) === strtolower($email)) {
                        $conflictMsg = "Email : {$email} already exist on lms.";
                    } elseif ($existingUser->mobile === $mobile) {
                        $conflictMsg = "Mobile : {$mobile} already exist on lms.";
                    }

                    $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => [$conflictMsg]];
                    $processed++;
                    continue; 
                }

                $userid = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
                    "name"           => $studentName ?? 'Student',
                    "email"          => $email,
                    "mobile"         => $mobile,
                    "role"           => 20,
                    "password"       => \Illuminate\Support\Facades\Hash::make("Test@12345"),
                    "active"         => 1,
                    "authentication" => 1,
                ]);

                \Illuminate\Support\Facades\DB::table('student_type')->insert([
                    "userid"           => $userid,
                    "type"             => $form->student_type ?? 0,
                    "password"         => 0,
                    "profile"          => 1,
                    "profile_complete" => 1
                ]);

                $submissionData['phase'] = \Illuminate\Support\Facades\DB::table('phase')->where('active', 1)->value('phaseid');
                $submissionData['profile_updated'] = \Carbon\Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s');
                $submissionData['password_updated'] = null;

            } elseif ($form->login_required == 1) {
                
                // --- NEW LOGIC: Email_As_Per_LMS mapping for Login Required Forms ---
                if (empty($emailAsPerLms)) {
                    $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => ["'Email As Per LMS' column is missing or empty. This is required for this form."]];
                    $processed++;
                    continue;
                }

                $lmsUser = \Illuminate\Support\Facades\DB::table('users')->where('email', $emailAsPerLms)->first();

                if (!$lmsUser) {
                    $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => ["Email : {$emailAsPerLms} does not exist on lms."]];
                    $processed++;
                    continue;
                }

                $userid = $lmsUser->id; // Dynamically tie the submission to this user
            }

            // 6. Save Form Submission
            $submission = \App\Models\FormSubmission::create([
                'form_template_id' => $form->id,
                'submission_data'  => $submissionData,
                'userid'           => $userid,
            ]);

            try {
                app(\App\Http\Controllers\EmailTemplateController::class)->triggerOnSubmit($form->id, $submission->id, $userid);
            } catch (\Throwable $e) { }

            if ($userid) {
                $check_assigned = \Illuminate\Support\Facades\DB::table('user_topic_completion')->where('userid', $userid)->where('topicid', $form->id)->exists();
                if ($check_assigned) {
                    \Illuminate\Support\Facades\DB::table('user_topic_completion')->where('topicid', $form->id)->where('userid', $userid)->where('completion', 0)->update(['completion' => 1]);
                }
            }

            $processed++;
        }

        if (is_resource($stream)) fclose($stream);

        if (($offset + $processed) >= $totalRows) {
            \Illuminate\Support\Facades\Storage::disk('azure')->delete($path);
        }

        return response()->json([
            'success'   => true,
            'processed' => $processed,
            'errors'    => $errors 
        ]);
    }
    public function processBulkUploadChunk_old_V3(Request $request, $id)
{
    $form = FormTemplate::findOrFail($id);
    
    $path = $request->file_path;
    $offset = (int) $request->offset;
    $limit = (int) $request->limit;
    $totalRows = (int) $request->total_rows;

    $stream = Storage::disk('azure')->readStream($path);
    
    if ($stream === false) {
        return response()->json(['success' => false, 'errors' => [['row' => 'System Error', 'messages' => ['Could not read file from Azure.']]]]);
    }

    $normalizeStr = function($str) {
        $str = strip_tags(html_entity_decode((string)$str)); 
        $str = preg_replace('/[^a-zA-Z0-9]/', '', $str); 
        return strtolower($str); 
    };

    // 1. Read headers and normalize them
    $rawHeaders = fgetcsv($stream);
    if (!$rawHeaders) {
        return response()->json(['success' => false, 'errors' => [['row' => 'File Error', 'messages' => ['CSV is empty or invalid.']]]]);
    }
    
    $normalizedHeaders = [];
    foreach ($rawHeaders as $index => $header) {
        $normalizedHeaders[$index] = $normalizeStr($header);
    }

    // 2. Map form structure from Database
    $formStructure = json_decode($form->form_structure, true);
    $fieldMap = [];
    
    if (is_array($formStructure)) {
        foreach ($formStructure as $field) {
            if (isset($field['type']) && $field['type'] == "hidden_field") continue;
            
            if (!empty($field['name']) && isset($field['label']) && !in_array($field['type'], ['page_break', 'description', 'new_line', 'title', 'file', 'file_upload'])) {
                $normalizedLabel = $normalizeStr($field['label']);
                $fieldMap[$normalizedLabel] = $field; 
            }
        }
    }

    // ==========================================
    // BUG FIX 1: Strict Column Matching
    // Reject if the Excel contains extra columns
    // ==========================================
    $extraColumns = [];
    foreach ($normalizedHeaders as $idx => $normHeader) {
        if ($normHeader !== '' && !isset($fieldMap[$normHeader])) {
            $extraColumns[] = $rawHeaders[$idx]; // Keep original name for the error message
        }
    }

    if (!empty($extraColumns)) {
        return response()->json([
            'success' => false,
            'errors' => [[
                'row' => 'Template Match Error', 
                'messages' => [
                    'Extra or unrecognized columns found: <strong>' . implode(', ', $extraColumns) . '</strong>.', 
                    'Please remove extra columns and only use the exact downloaded template.'
                ]
            ]]
        ]);
    }
    // ==========================================

    // Build combined state-city pairs map for cross-field validation (once per request)
    // cityFieldName => stateFieldName — only for selectStateCity combined fields
    $stateCityPairs = [];
    foreach ($fieldMap as $fieldData) {
        if ($fieldData['type'] === 'selectStateCity' && !empty($fieldData['target_elemnt'])) {
            $targetElemId = $fieldData['target_elemnt'];
            foreach ($fieldMap as $fieldData2) {
                if ($fieldData2['type'] === 'selectStateCity' &&
                    empty($fieldData2['target_elemnt']) &&
                    ($fieldData2['id'] ?? '') === $targetElemId) {
                    $stateCityPairs[$fieldData2['name']] = $fieldData['name'];
                    break;
                }
            }
        }
    }

    $processed = 0;
    $errors = [];
    $currentRow = 0;

    // Fast-forward to the offset
    while ($currentRow < $offset && fgetcsv($stream) !== false) {
        $currentRow++;
    }

    // 3. Process the chunk
    while (($row = fgetcsv($stream)) !== false && $processed < $limit) {
        
        $trimmedRow = array_map('trim', $row);
        if (empty(array_filter($trimmedRow, fn($value) => $value !== ''))) continue; 

        $rowNum = $offset + $processed + 2; 
        
        $submissionData = [];
        $rowToValidate = [];
        $rowRules = [];
        $rowMessages = [];
        $customAttributes = []; 

        $email = null;
        $mobile = null;
        $studentName = null;

        foreach ($trimmedRow as $index => $value) {
            $normalizedLabel = $normalizedHeaders[$index] ?? null;
            if (!$normalizedLabel || !isset($fieldMap[$normalizedLabel])) continue; 

            // Core user data detection
            if (str_contains($normalizedLabel, 'email')) $email = $value;
            if (str_contains($normalizedLabel, 'mobile') || str_contains($normalizedLabel, 'phone')) $mobile = $value;
            if (str_contains($normalizedLabel, 'name')) $studentName = $value;

            $fieldInfo = $fieldMap[$normalizedLabel];
            $fieldName = $fieldInfo['name']; 
            $friendlyName = trim(strip_tags(html_entity_decode($fieldInfo['label']))); 
            
            $submissionData[$fieldName] = $value;
            $rowToValidate[$fieldName]  = $value;
            $rules = [];

            // ==========================================
            // BUG FIX 3: sdp_college DB Lookup
            // ==========================================
            if ($fieldInfo['type'] === 'sdp_college' && !empty($value)) {
                $collegeId = DB::table('college')->where('college_name', $value)->value('id');
                
                if ($collegeId) {
                    $submissionData[$fieldName] = $collegeId; // Save ID instead of Text
                    $rowToValidate[$fieldName]  = $collegeId;
                } else {
                    $rules[] = function ($attribute, $val, $fail) use ($value, $friendlyName) {
                        $fail("The College '{$value}' was not found in our database records.");
                    };
                }
            }

           // ==========================================
            // BUG FIX 4: Dropdown/Select Exact Matching
            // ==========================================
            $selectTypes = ['select', 'dropdown', 'radio', 'checkbox', 'multiple_select'];
            if (in_array($fieldInfo['type'], $selectTypes) && !empty($value)) {
                $validOptions = [];
                
                // Extract valid options dynamically from form structure JSON
                $optionsArray = $fieldInfo['options'] ?? $fieldInfo['choices'] ?? [];
                foreach ($optionsArray as $opt) {
                    // Handle standard form builder array structures and TRIM spaces
                    if (is_array($opt)) {
                        $extractedOpt = (string)($opt['value'] ?? $opt['text'] ?? $opt['label'] ?? '');
                        $validOptions[] = trim($extractedOpt); // <--- Added trim() here
                    } else {
                        $validOptions[] = trim((string)$opt);  // <--- Added trim() here
                    }
                }

                if (!empty($validOptions) && !in_array($value, $validOptions)) {
                    $optionsStr = implode(', ', $validOptions);
                    $rules[] = function ($attribute, $val, $fail) use ($value, $optionsStr, $friendlyName) {
                        $fail("The value '{$value}' is invalid for {$friendlyName}. Allowed options are: [{$optionsStr}].");
                    };
                }
            }

            // ==========================================
            // BUG FIX 2: File Upload Optional By Default
            // ==========================================
            if ($fieldInfo['type'] === 'file' || $fieldInfo['type'] === 'file_upload') {
                $rules[] = 'nullable'; // Force file fields to be optional via CSV
            } else {
                // Standard Required/Nullable logic for everything else
                if (!empty($fieldInfo['required']) && $fieldInfo['required'] === true) {
                    $rules[] = 'required';
                } else {
                    $rules[] = 'nullable';
                }
            }

            // ==========================================
            // NEW FIX: Bulletproof Date Min/Max Validation
            // ==========================================
            if ($fieldInfo['type'] === 'date' && !empty($value)) {
                $parsedDate = null;
                
                // 1. Safely parse Excel's various date formats (DD/MM/YYYY, etc.)
                $formats = ['d/m/Y', 'm/d/Y', 'Y-m-d', 'd-m-Y', 'Y/m/d'];
                foreach($formats as $format) {
                    try {
                        $parsedDate = \Carbon\Carbon::createFromFormat($format, $value);
                        if ($parsedDate) break;
                    } catch (\Exception $e) {}
                }

                // Fallback to standard parse if the above formats don't match
                if (!$parsedDate) {
                    try { $parsedDate = \Carbon\Carbon::parse($value); } catch (\Exception $e) {}
                }

                if ($parsedDate) {
                    // Standardize to strictly YYYY-MM-DD
                    $standardDate = $parsedDate->format('Y-m-d');
                    
                    // Overwrite data for strict validation and clean DB saving
                    $value = $standardDate;
                    $submissionData[$fieldName] = $standardDate;
                    $rowToValidate[$fieldName]  = $standardDate;

                    $rules[] = 'date_format:Y-m-d';
                    
                    // 2. Direct Carbon Mathematical Validation (Bulletproof)
                    $startDateStr = $fieldInfo['start_date'] ?? null;
                    $endDateStr   = $fieldInfo['end_date'] ?? null;

                    $rules[] = function ($attribute, $val, $fail) use ($parsedDate, $startDateStr, $endDateStr, $friendlyName) {
                        
                        // Check Minimum Date
                        if (!empty($startDateStr)) {
                            $minDate = \Carbon\Carbon::parse($startDateStr)->startOfDay();
                            // If 1999 is strictly less than (<) 2000, FAIL IT!
                            if ($parsedDate->copy()->startOfDay()->lt($minDate)) {
                                $fail("The {$friendlyName} must be on or after " . $minDate->format('d/m/Y') . ".");
                            }
                        }
                        
                        // Check Maximum Date
                        if (!empty($endDateStr)) {
                            $maxDate = \Carbon\Carbon::parse($endDateStr)->endOfDay();
                            if ($parsedDate->copy()->startOfDay()->gt($maxDate)) {
                                $fail("The {$friendlyName} must be on or before " . $maxDate->format('d/m/Y') . ".");
                            }
                        }
                    };

                } else {
                    // If the user typed random text instead of a date
                    $rules[] = function ($attribute, $val, $fail) use ($friendlyName) {
                        $fail("The {$friendlyName} contains an invalid date format. Please use DD/MM/YYYY.");
                    };
                }
            }
            // ==========================================

            // Apply standard rules (Length, Regex)
                if (!empty($fieldInfo['minLength'])) $rules[] = 'min:' . $fieldInfo['minLength'];
                if (!empty($fieldInfo['maxLength'])) $rules[] = 'max:' . $fieldInfo['maxLength'];
                if (!empty($fieldInfo['pattern'])) {
                    $pattern = str_replace('/', '\/', $fieldInfo['pattern']); 
                    $rules[] = 'regex:/' . $pattern . '/';
                }

            if (!empty($rules)) {
                $rowRules[$fieldName] = $rules; 
                $customAttributes[$fieldName] = $friendlyName;
                
                $rowMessages["{$fieldName}.required"] = "{$friendlyName} is required.";
                $rowMessages["{$fieldName}.regex"]    = "{$friendlyName} format is invalid.";
            }
        }

        // 4. Run Dynamic Validation
        if (!empty($rowRules)) {
            $validator = Validator::make($rowToValidate, $rowRules, $rowMessages, $customAttributes);
            
            if ($validator->fails()) {
                $errors[] = [
                    'row' => "Row {$rowNum}: Validation Failed", 
                    'messages' => $validator->errors()->all()
                ];
                $processed++;
                continue; 
            }
        }

        // 5. Registration Logic
        $userid = Auth::id();

        if ($form->is_registration_form) {
            if (!$email) {
                $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => ["Missing email address."]];
                $processed++;
                continue;
            }

            $existingUser = DB::table('users')->where('email', $email)->orWhere('mobile', $mobile)->first();

            if ($existingUser) {
                $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => ["User already exists with this Email or Mobile."]];
                $processed++;
                continue; 
            }

            $userid = DB::table('users')->insertGetId([
                "name"           => $studentName ?? 'Student',
                "email"          => $email,
                "mobile"         => $mobile,
                "role"           => 20,
                "password"       => Hash::make("Test@12345"),
                "active"         => 1,
                "authentication" => 1,
            ]);

            DB::table('student_type')->insert([
                "userid"           => $userid,
                "type"             => $form->student_type ?? 0,
                "password"         => 0,
                "profile"          => 1,
                "profile_complete" => 1
            ]);

            $submissionData['phase'] = DB::table('phase')->where('active', 1)->value('phaseid');
            $submissionData['profile_updated'] = \Carbon\Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s');
            $submissionData['password_updated'] = null;
        }

        // 6. Save Form Submission
        $submission = FormSubmission::create([
            'form_template_id' => $form->id,
            'submission_data'  => $submissionData,
            'userid'           => $userid,
        ]);

        // 7. Trigger Events
        try {
            app(\App\Http\Controllers\EmailTemplateController::class)->triggerOnSubmit($form->id, $submission->id, $userid);
        } catch (\Throwable $e) {
            Log::error("Bulk Upload Email Trigger Failed for user {$userid}: " . $e->getMessage());
        }

        if ($userid) {
            $check_assigned = DB::table('user_topic_completion')->where('userid', $userid)->where('topicid', $form->id)->exists();
            if ($check_assigned) {
                DB::table('user_topic_completion')
                    ->where('topicid', $form->id)
                    ->where('userid', $userid)
                    ->where('completion', 0)
                    ->update(['completion' => 1]);
            }
        }

        $processed++;
    }

    if (is_resource($stream)) fclose($stream);

    if (($offset + $processed) >= $totalRows) {
        Storage::disk('azure')->delete($path);
    }

    return response()->json([
        'success'   => true,
        'processed' => $processed,
        'errors'    => $errors 
    ]);
}
    public function processBulkUploadChunk_old_V2(Request $request, $id)
{
    $form = FormTemplate::findOrFail($id);
    
    $path = $request->file_path;
    $offset = (int) $request->offset;
    $limit = (int) $request->limit;
    $totalRows = (int) $request->total_rows;

    // 1. Open the stream from Azure
    $stream = Storage::disk('azure')->readStream($path);
    
    if ($stream === false) {
        return response()->json(['success' => false, 'errors' => [['row' => 'System Error', 'messages' => ['Could not read file from Azure.']]]]);
    }

    // --- HELPER: Nuclear String Normalization ---
    // Strips ALL spaces, punctuation, HTML, and special/invisible characters.
    $normalizeStr = function($str) {
        $str = strip_tags(html_entity_decode((string)$str)); 
        $str = preg_replace('/[^a-zA-Z0-9]/', '', $str); // Leave ONLY letters and numbers
        return strtolower($str); 
    };

    // 2. Read headers and normalize them by exact column index
    $rawHeaders = fgetcsv($stream);
    if (!$rawHeaders) {
        return response()->json(['success' => false, 'errors' => [['row' => 'File Error', 'messages' => ['CSV is empty or invalid.']]]]);
    }
    
    $normalizedHeaders = [];
    foreach ($rawHeaders as $index => $header) {
        $normalizedHeaders[$index] = $normalizeStr($header);
    }

    // 3. Map form structure from Database
    $formStructure = json_decode($form->form_structure, true);
    $fieldMap = [];
    
    if (is_array($formStructure)) {
        foreach ($formStructure as $field) {
            if (isset($field['type']) && $field['type'] == "hidden_field") continue;
            
            if (!empty($field['name']) && isset($field['label']) && !in_array($field['type'], ['page_break', 'description', 'new_line', 'title', 'file', 'file_upload'])) {
                $normalizedLabel = $normalizeStr($field['label']);
                $fieldMap[$normalizedLabel] = $field; 
            }
        }
    }

    $processed = 0;
    $errors = [];
    $currentRow = 0;

    // 4. Fast-forward the stream to the correct offset for this chunk
    while ($currentRow < $offset && fgetcsv($stream) !== false) {
        $currentRow++;
    }

    // 5. Process the chunk
    while (($row = fgetcsv($stream)) !== false && $processed < $limit) {
        
        $trimmedRow = array_map('trim', $row);
        
        // Skip entirely blank rows
        if (empty(array_filter($trimmedRow, fn($value) => $value !== ''))) {
            continue;
        }

        $rowNum = $offset + $processed + 2; // +2 offsets for 0-index and Header row
        
        $submissionData = [];
        $rowToValidate = [];
        $rowRules = [];
        $rowMessages = [];
        $customAttributes = []; // For clean validation names

        $email = null;
        $mobile = null;
        $studentName = null;

        // Map data using column indices (prevents Excel column-count mismatch crashes)
        foreach ($trimmedRow as $index => $value) {
            $normalizedLabel = $normalizedHeaders[$index] ?? null;
            
            if (!$normalizedLabel) continue; 

            // Core user data detection
            if (str_contains($normalizedLabel, 'email')) $email = $value;
            if (str_contains($normalizedLabel, 'mobile') || str_contains($normalizedLabel, 'phone')) $mobile = $value;
            if (str_contains($normalizedLabel, 'name')) $studentName = $value;

            // Match against our normalized field map
            if (isset($fieldMap[$normalizedLabel])) {
                $fieldInfo = $fieldMap[$normalizedLabel];
                $fieldName = $fieldInfo['name']; 
                
                $submissionData[$fieldName] = $value;
                $rowToValidate[$fieldName]  = $value;

                // Build Validation Rules
                $rules = [];
                if (!empty($fieldInfo['required']) && $fieldInfo['required'] === true) {
                    $rules[] = 'required';
                } else {
                    $rules[] = 'nullable';
                }
                
                if (!empty($fieldInfo['minLength'])) $rules[] = 'min:' . $fieldInfo['minLength'];
                if (!empty($fieldInfo['maxLength'])) $rules[] = 'max:' . $fieldInfo['maxLength'];
                if (!empty($fieldInfo['pattern'])) {
                    $pattern = str_replace('/', '\/', $fieldInfo['pattern']); 
                    $rules[] = 'regex:/' . $pattern . '/';
                }

                if (!empty($rules)) {
                    $rowRules[$fieldName] = $rules; 
                    
                    // Set friendly name for Validator (e.g., "Please State your Gender" instead of "radio1")
                    $friendlyName = trim(strip_tags(html_entity_decode($fieldInfo['label']))); 
                    $customAttributes[$fieldName] = '<u>'.$friendlyName.'</u>';
                    
                    $rowMessages["{$fieldName}.required"] = "{$friendlyName} is required.";
                    $rowMessages["{$fieldName}.regex"]    = "{$friendlyName} format is invalid.";
                }
            }
        }

        // Safety check if headers were totally mismatched
        if (empty($submissionData)) {
            $errors[] = [
                'row' => "Row {$rowNum}: Header Mismatch", 
                'messages' => ["Headers didn't match the form. Please download the template."]
            ];
            $processed++;
            continue;
        }

        // 6. Run Dynamic Validation
        if (!empty($rowRules)) {
            // Passing $customAttributes as the 4th argument
            $validator = Validator::make($rowToValidate, $rowRules, $rowMessages, $customAttributes);
            
            if ($validator->fails()) {
                // Send an array of messages to build the UI Accordion
                $errors[] = [
                    'row' => "Row {$rowNum}: Validation Failed", 
                    'messages' => $validator->errors()->all()
                ];
                $processed++;
                continue; 
            }
        }

        // 7. Registration Logic
        $userid = Auth::id();

        if ($form->is_registration_form) {
            if (!$email) {
                $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => ["Missing email address."]];
                $processed++;
                continue;
            }

            $existingUser = DB::table('users')
                ->where('email', $email)
                ->orWhere('mobile', $mobile)
                ->first();

            if ($existingUser) {
                $errors[] = ['row' => "Row {$rowNum}: Skipped", 'messages' => ["User already exists with this Email or Mobile."]];
                $processed++;
                continue; 
            }

            $userid = DB::table('users')->insertGetId([
                "name"           => $studentName ?? 'Student',
                "email"          => $email,
                "mobile"         => $mobile,
                "role"           => 20,
                "password"       => Hash::make("Test@12345"),
                "active"         => 1,
                "authentication" => 1,
            ]);

            DB::table('student_type')->insert([
                "userid"           => $userid,
                "type"             => $form->student_type ?? 0,
                "password"         => 0,
                "profile"          => 1,
                "profile_complete" => 1
            ]);

            $submissionData['phase'] = DB::table('phase')->where('active', 1)->value('phaseid');
            $submissionData['profile_updated'] = \Carbon\Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s');
            $submissionData['password_updated'] = null;
        }

        // 8. Save Form Submission
        $submission = FormSubmission::create([
            'form_template_id' => $form->id,
            'submission_data'  => $submissionData,
            'userid'           => $userid,
        ]);

        // 9. Trigger Events
        try {
            app(\App\Http\Controllers\EmailTemplateController::class)->triggerOnSubmit($form->id, $submission->id, $userid);
        } catch (\Throwable $e) {
            Log::error("Bulk Upload Email Trigger Failed for user {$userid}: " . $e->getMessage());
        }

        if ($userid) {
            $check_assigned = DB::table('user_topic_completion')
                ->where('userid', $userid)
                ->where('topicid', $form->id)
                ->exists();
                
            if ($check_assigned) {
                DB::table('user_topic_completion')
                    ->where('topicid', $form->id)
                    ->where('userid', $userid)
                    ->where('completion', 0)
                    ->update(['completion' => 1]);
            }
        }

        $processed++;
    }

    if (is_resource($stream)) {
        fclose($stream);
    }

    // 10. Cleanup Azure Storage if this was the last chunk
    if (($offset + $processed) >= $totalRows) {
        Storage::disk('azure')->delete($path);
    }

    return response()->json([
        'success'   => true,
        'processed' => $processed,
        'errors'    => $errors // Now returns structured data for your accordion
    ]);
}

    public function processBulkUploadChunk_old(Request $request, $id)
{
    $form = \App\Models\FormTemplate::findOrFail($id);
    
    $path = $request->file_path;
    $offset = (int) $request->offset;
    $limit = (int) $request->limit;
    $totalRows = (int) $request->total_rows;

    $stream = \Illuminate\Support\Facades\Storage::disk('azure')->readStream($path);
    
    if ($stream === false) {
        return response()->json(['success' => false, 'errors' => ['Could not read file from Azure.']]);
    }

    // --- HELPER: Nuclear String Normalization ---
    // This strips ALL spaces, punctuation, HTML, and special/invisible characters.
    // It leaves ONLY lowercase letters and numbers.
    $normalizeStr = function($str) {
        $str = strip_tags(html_entity_decode((string)$str)); 
        $str = preg_replace('/[^a-zA-Z0-9]/', '', $str); // Remove everything except A-Z and 0-9
        return strtolower($str); 
    };

    // 1. Read headers and normalize them by exact column index
    $rawHeaders = fgetcsv($stream);
    if (!$rawHeaders) {
        return response()->json(['success' => false, 'errors' => ['CSV is empty or invalid.']]);
    }
    
    $normalizedHeaders = [];
    foreach ($rawHeaders as $index => $header) {
        $normalizedHeaders[$index] = $normalizeStr($header);
    }

    // 2. Map form structure
    $formStructure = json_decode($form->form_structure, true);
    $fieldMap = [];
    
    if (is_array($formStructure)) {
        foreach ($formStructure as $field) {
            if (isset($field['type']) && $field['type'] == "hidden_field") continue;
            
            if (!empty($field['name']) && isset($field['label']) && !in_array($field['type'], ['page_break', 'description', 'new_line', 'title', 'file', 'file_upload'])) {
                $normalizedLabel = $normalizeStr($field['label']);
                $fieldMap[$normalizedLabel] = $field; 
            }
        }
    }

    $processed = 0;
    $errors = [];
    $currentRow = 0;

    // Fast-forward
    while ($currentRow < $offset && fgetcsv($stream) !== false) {
        $currentRow++;
    }

    // 3. Process the chunk
    while (($row = fgetcsv($stream)) !== false && $processed < $limit) {
        
        $trimmedRow = array_map('trim', $row);
        
        // Skip entirely blank rows
        if (empty(array_filter($trimmedRow, fn($value) => $value !== ''))) {
            continue; 
        }

        $rowNum = $offset + $processed + 2; 
        
        $submissionData = [];
        $rowToValidate = [];
        $rowRules = [];
        $rowMessages = [];
        $customAttributes = [];

        $email = null;
        $mobile = null;
        $studentName = null;

        // Map data using column indices (prevents Excel column-count mismatch crashes)
        foreach ($trimmedRow as $index => $value) {
            // Get the normalized header for this specific column
            $normalizedLabel = $normalizedHeaders[$index] ?? null;
            
            // If there's no header for this column, skip it
            if (!$normalizedLabel) continue; 

            // Core user data detection
            if (str_contains($normalizedLabel, 'email')) $email = $value;
            if (str_contains($normalizedLabel, 'mobile') || str_contains($normalizedLabel, 'phone')) $mobile = $value;
            if (str_contains($normalizedLabel, 'name')) $studentName = $value;

            // Match against our nuclear-normalized field map
            if (isset($fieldMap[$normalizedLabel])) {
                $fieldInfo = $fieldMap[$normalizedLabel];
                $fieldName = $fieldInfo['name']; 
                
                $submissionData[$fieldName] = $value;
                $rowToValidate[$fieldName]  = $value;

                $rules = [];
                if (!empty($fieldInfo['required']) && $fieldInfo['required'] === true) {
                    $rules[] = 'required';
                } else {
                    $rules[] = 'nullable';
                }
                
                if (!empty($fieldInfo['minLength'])) $rules[] = 'min:' . $fieldInfo['minLength'];
                if (!empty($fieldInfo['maxLength'])) $rules[] = 'max:' . $fieldInfo['maxLength'];
                if (!empty($fieldInfo['pattern'])) {
                    $pattern = str_replace('/', '\/', $fieldInfo['pattern']); 
                    $rules[] = 'regex:/' . $pattern . '/';
                }

                // if (!empty($rules)) {
                //     $rowRules[$fieldName] = $rules; 
                //     $friendlyName = strip_tags(html_entity_decode($fieldInfo['label'])); 
                //     $rowMessages["{$fieldName}.required"] = "Column '{$friendlyName}' is required.";
                //     $rowMessages["{$fieldName}.regex"]    = "Column '{$friendlyName}' format is invalid.";
                // }
                if (!empty($rules)) {
                    $rowRules[$fieldName] = $rules; 
                    
                    // Clean up the label so it looks nice in the error message
                    $friendlyName = trim(strip_tags(html_entity_decode($fieldInfo['label']))); 
                    
                    // Tell Laravel to use this friendly name instead of "radio1" or "text0"
                    $customAttributes[$fieldName] = '<u>'.$friendlyName.'</u>';

                    // Keep custom messages for specific rules if you want
                    $rowMessages["{$fieldName}.required"] = "Column '{$friendlyName}' is required.";
                    $rowMessages["{$fieldName}.regex"]    = "Column '{$friendlyName}' format is invalid.";
                }
            }
        }

        // Diagnostic Check: If it still fails, it logs exactly what headers it saw
        // if (empty($submissionData)) {
        //     $parsedHeaders = implode(', ', array_filter($normalizedHeaders));
        //     \Illuminate\Support\Facades\Log::warning("Row {$rowNum} failed. Headers parsed: {$parsedHeaders}");
            
        //     $errors[] = "Row {$rowNum}: Skipped. Headers didn't match the form. (See Laravel logs for details).";
        //     $processed++;
        //     continue;
        // }

        if (empty($submissionData)) {
            $errors[] = [
                'row' => "Row {$rowNum}: Headers Mismatch", 
                'messages' => ["Headers didn't match the form. Please download the template."]
            ];
            $processed++;
            continue;
        }

        if (!empty($rowRules)) {
            $validator = \Illuminate\Support\Facades\Validator::make($rowToValidate, $rowRules, $rowMessages, $customAttributes);
            if ($validator->fails()) {
                // Send the raw array of errors so JS can loop through them!
                $errors[] = [
                    'row' => "Row {$rowNum}: Validation Failed", 
                    'messages' => $validator->errors()->all() 
                ];
                $processed++;
                continue; 
            }
        }

        // 4. Validate
        // if (!empty($rowRules)) {
        //     $validator = \Illuminate\Support\Facades\Validator::make($rowToValidate, $rowRules, $rowMessages);
        //     if ($validator->fails()) {
        //         $errorStr = implode(' ', $validator->errors()->all());
        //         $errors[] = "Row {$rowNum}: Validation failed - " . $errorStr;
        //         $processed++;
        //         continue; 
        //     }
        // }
        // 4. Validate
        if (!empty($rowRules)) {
            // Pass $customAttributes as the 4th argument here!
            $validator = \Illuminate\Support\Facades\Validator::make($rowToValidate, $rowRules, $rowMessages, $customAttributes);
            
            if ($validator->fails()) {
                $errorStr = implode(' ', $validator->errors()->all());
                $errors[] = "Row {$rowNum}: Validation failed - " . $errorStr;
                $processed++;
                continue; 
            }
        }

        // 5. Registration Logic
        $userid = \Illuminate\Support\Facades\Auth::id();

        if ($form->is_registration_form) {
            if (!$email) {
                $errors[] = "Row {$rowNum}: Missing email address. Skipped.";
                $processed++;
                continue;
            }

            $existingUser = \Illuminate\Support\Facades\DB::table('users')
                ->where('email', $email)
                ->orWhere('mobile', $mobile)
                ->first();

            if ($existingUser) {
                $errors[] = "Row {$rowNum}: User already exists ($email). Skipped.";
                $processed++;
                continue; 
            }

            $userid = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
                "name"           => $studentName ?? 'Student',
                "email"          => $email,
                "mobile"         => $mobile,
                "role"           => 20,
                "password"       => \Illuminate\Support\Facades\Hash::make("Test@12345"),
                "active"         => 1,
                "authentication" => 1,
            ]);

            \Illuminate\Support\Facades\DB::table('student_type')->insert([
                "userid"           => $userid,
                "type"             => $form->student_type ?? 0,
                "password"         => 0,
                "profile"          => 1,
                "profile_complete" => 1
            ]);

            $submissionData['phase'] = \Illuminate\Support\Facades\DB::table('phase')->where('active', 1)->value('phaseid');
            $submissionData['profile_updated'] = \Carbon\Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s');
            $submissionData['password_updated'] = null;
        }

        // 6. Save Form Submission
        $submission = \App\Models\FormSubmission::create([
            'form_template_id' => $form->id,
            'submission_data'  => $submissionData,
            'userid'           => $userid,
        ]);

        // 7. Trigger Events
        try {
            app(\App\Http\Controllers\EmailTemplateController::class)->triggerOnSubmit($form->id, $submission->id, $userid);
        } catch (\Throwable $e) {}

        if ($userid) {
            $check_assigned = \Illuminate\Support\Facades\DB::table('user_topic_completion')->where('userid', $userid)->where('topicid', $form->id)->exists();
            if ($check_assigned) {
                \Illuminate\Support\Facades\DB::table('user_topic_completion')->where('topicid', $form->id)->where('userid', $userid)->where('completion', 0)->update(['completion' => 1]);
            }
        }

        $processed++;
    }

    if (is_resource($stream)) {
        fclose($stream);
    }

    if (($offset + $processed) >= $totalRows) {
        \Illuminate\Support\Facades\Storage::disk('azure')->delete($path);
    }

    return response()->json([
        'success'   => true,
        'processed' => $processed,
        'errors'    => $errors
    ]);
}


}