@extends('layouts.admin')
<link rel="stylesheet" href="{{ asset('css/form-builder.css') }}">
@section('content')


    <div class="app-content  my-3 my-md-5">
        <div class="side-app">
            <div class="page-header">
                <h4 class="page-title">{{ $form->title }}</h4>
            </div>

            <div>

                @if ($form->description)
                    <p class="lead">{{ $form->description }}</p>
                @endif

                @if (session()->has('message'))
                    <div class="autoDismissAlert alert alert-success alert-dismissible">
                        <a href="javascript:void(0)" class="close" data-bs-dismiss="alert" aria-label="close">&times;</a>
                        <strong> {{ session()->get('message') }}</strong>
                    </div>
                @endif
                {{--  @if (session()->has('err'))
                <div class="autoDismissAlert alert alert-danger alert-dismissible">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                    <strong> {{ session()->get('err') }}</strong>
                </div>
                 @endif --}}
                @if ($errors->has('err'))
                    <div class="autoDismissAlert alert alert-danger alert-dismissible">
                        <a href="javascript:void(0)" class="close" data-bs-dismiss="alert" aria-label="close">&times;</a>
                        {{ $errors->first('err') }}
                    </div>
                @endif
                @php $fieldErrors = collect($errors->getMessages())->except('err'); @endphp
                @if ($fieldErrors->isNotEmpty())
                    <div class="autoDismissAlert alert alert-danger alert-dismissible">
                        <a href="javascript:void(0)" class="close" data-bs-dismiss="alert" aria-label="close">&times;</a>
                        @foreach ($fieldErrors as $messages)
                            @foreach ($messages as $error)
                                <strong>{{ $error }}</strong><br>
                            @endforeach
                        @endforeach
                    </div>
                @endif


                @if (session('success'))
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            // show the toast right away
                            toastr.success(@json(session('success')), '', {
                                closeButton: false
                            });

                            @if (session('redirect_to'))
                                // after 2 seconds, go to the custom URL
                                setTimeout(function() {
                                    window.location.href = @json(session('redirect_to'));
                                }, 2000);
                            @endif
                        });
                    </script>
                @endif

                @if ($form->review == 1)
                    @if ($submission != null && $submission->review_status === 'approved')
                        <div class="alert alert-success text-center">
                            <h4><strong>Your submission has been approved!</strong></h4>
                            <p>No further changes are allowed.</p>
                        </div>
                    @elseif($submission != null && $submission->review_status === 'pending')
                        <div class="alert alert-info text-center">
                            <h4><strong>Your submission is under review.</strong></h4>
                            <p>Please wait for admin feedback.</p>
                        </div>
                    @else
                        @if (!empty($workflowSlug))
                            <div id="formTimerBar"
                                style="position:sticky;top:0;z-index:1000;text-align:center;padding:10px;background:#ecfdf5;border-bottom:1px solid #10b981;font-weight:700;color:#1f2937;">
                                Time Remaining: <span id="formTimer">--:--</span>
                            </div>
                            <script>
                                (function() {
                                    const SYNC_URL = "{{ route('syncFormTimer') }}";
                                    const CSRF = "{{ csrf_token() }}";
                                    const FORM_ID = {{ $form->id }};
                                    const WORKFLOW_SLUG = "{{ $workflowSlug }}";
                                    let remaining = {{ $remainingSeconds ?? 0 }};
                                    let interval = null;

                                    function fmt(s) {
                                        if (s < 0) s = 0;
                                        const h = Math.floor(s / 3600),
                                            m = Math.floor((s % 3600) / 60),
                                            sec = s % 60;
                                        const pad = n => (n < 10 ? '0' + n : n);
                                        return (h > 0 ? pad(h) + ':' : '') + pad(m) + ':' + pad(sec);
                                    }

                                    function sync() {
                                        fetch(SYNC_URL, {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': CSRF
                                            },
                                            body: JSON.stringify({
                                                form_template_id: FORM_ID,
                                                remaining_seconds: remaining
                                            })
                                        }).catch(() => {});
                                    }

                                    function expire() {
                                        clearInterval(interval);
                                        sync();
                                        window.location.href = WORKFLOW_SLUG ? "/attempt-workflow/" + WORKFLOW_SLUG : "/thankyou";
                                    }

                                    function tick() {
                                        if (document.hidden) return; // pause when not visible
                                        remaining--;
                                        document.getElementById('formTimer').textContent = fmt(remaining);
                                        if (remaining <= 0) expire();
                                    }

                                    document.addEventListener('DOMContentLoaded', function() {
                                        if (remaining <= 0) {
                                            expire();
                                            return;
                                        }
                                        document.getElementById('formTimer').textContent = fmt(remaining);
                                        interval = setInterval(tick, 1000);
                                        setInterval(sync, 15000);
                                        document.addEventListener('visibilitychange', function() {
                                            if (document.hidden) sync();
                                        });
                                        window.addEventListener('beforeunload', sync);
                                    });
                                })();
                            </script>
                        @endif
                        <form id="formSubmission" action="{{ route('form-submission.store', $form->slug) }}" method="POST"
                            enctype="multipart/form-data" novalidate>
                            @csrf
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div id="formRenderer" class="row"></div>

                                        </div>

                                    </div>
                                    <div class="d-flex justify-content-end mt-2">
                                        <button type="submit" id="submitBtn"
                                            class="btn btn-primary">{{ $submit_btn_txt ?? 'Submit' }}</button>
                                    </div>
                                </div>
                            </div>



                        </form>
                    @endif
                @else
                    @if ($submission != null && $form->multi_submission == 0)
                        @if (!session()->has('success'))
                            <h2>
                                <center>Form already submitted!</center>
                            </h2>
                        @elseif (session()->has('success'))
                            <h2>
                                <center>Form submitted!</center>
                            </h2>
                        @endif
                    @else
                        <form id="formSubmission" action="{{ route('form-submission.store', $form->slug) }}" method="POST"
                            enctype="multipart/form-data" novalidate>
                            @csrf
                            @isset($is_dynamic_url)
                                <input type="hidden" name="reg_batch_code" value="{{ $is_dynamic_url }}">
                        @endif
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card">
                                    <div class="card-body">
                                        <div id="formRenderer" class="row"></div>

                                    </div>

                                </div>
                                <div class="d-flex justify-content-end mt-2">
                                    <button type="submit" id="submitBtn"
                                        class="btn btn-primary">{{ $submit_btn_txt ?? 'Submit' }}</button>
                                </div>
                            </div>
                        </div>



                        </form>
                    @endif
                    @endif

                </div>
            </div>
        </div>

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css"
            integrity="sha512-3pIirOrwegjM6erE5gPSwkUzO+3cTjpnV9lexlNZqvupR64iZBnOOTiiLPb9M36zpMScbmUNIcHUqKD47M719g=="
            crossorigin="anonymous" referrerpolicy="no-referrer" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"
            integrity="sha512-VEd+nq25CkR676O+pLBnDW09R7VQX9Mdiij052gVCp5yVH3jGtH70Ho/UUv4mJDsEdTvqRCFZg0NKGiojGnUCw=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <style>
            .flatpickr-input[readonly] {
                background-color: #fff !important;
                cursor: pointer;
            }

            #submitBtn {
                white-space: normal;
                word-break: break-word;
                max-width: 100%;
            }
        </style>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script src="{{ asset('js/socket.io.min.js') }}"></script>
        @if (!empty($workflowSlug))
            <script>
                window.PROCTOR_CONFIG = {
                    resultId: {{ $proctorResultId ?? 0 }},
                    studentId: {{ auth()->id() }},
                    studentName: @json(auth()->user()->name ?? ''),
                    enableCamera: {{ $enableCamera ?? 0 }},
                    enableScreen: {{ $enableScreen ?? 0 }},
                    onViolation: function(reason) {},
                    onTerminate: function(reason) {
                        // Forced end on the form -> go to hub (form section will be treated done).
                        window.location.href = '/attempt-workflow/{{ $workflowSlug }}';
                    }
                };
            </script>

            @include('partials.workflow-proctoring', [
                'proctorResultId' => $proctorResultId ?? 0,
                'enableCamera' => $enableCamera ?? 0,
                'enableScreen' => $enableScreen ?? 0,
            ])
        @endif

        <script>
            toastr.options.closeButton = true;

            function initDatepickers() {
                if (typeof flatpickr === 'undefined') return;
                const cleanDate = v => (v && v !== 'undefined' && v !== 'null') ? v : null;
                document.querySelectorAll("input[type='date'], input.flatpickr-input").forEach(function(input) {
                    if (input._flatpickr) return;
                    const min = cleanDate(input.getAttribute("data-start_date"));
                    const max = cleanDate(input.getAttribute("data-end_date"));
                    input.type = 'text';

                    flatpickr(input, {
                        dateFormat: "Y-m-d",
                        allowInput: false,
                        clickOpens: true,
                        minDate: min,
                        maxDate: max
                    });
                });
            }
        </script>
    <script type="module" src="{{ asset('js/form-renderer.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const formStructure = JSON.parse(@json($form->form_structure));
            await renderForm(formStructure);
            initDatepickers();

            @if (!empty($submittedData))
                const submittedData = @json($submittedData);
                if (submittedData) {
                    setTimeout(function() {
                        Object.keys(submittedData).forEach(function(key) {
                            const value = submittedData[key];
                            // text, textarea, number, email, url, date inputs
                            const input = document.querySelector(
                                `input[name="${key}"], textarea[name="${key}"], select[name="${key}"]`
                            );
                            if (input) {
                                if (input.type === 'radio' || input.type === 'checkbox') return;
                                input.value = value;
                            }
                            // radio
                            const radio = document.querySelector(
                                `input[type="radio"][name="${key}"][value="${value}"]`);
                            if (radio) radio.checked = true;
                            // checkboxes (array values)
                            if (Array.isArray(value)) {
                                value.forEach(function(v) {
                                    const cb = document.querySelector(
                                        `input[type="checkbox"][name="${key}[]"][value="${v}"]`
                                    );
                                    if (cb) cb.checked = true;
                                });
                            }
                            // select
                            const select = document.querySelector(`select[name="${key}"]`);
                            if (select) select.value = value;
                        });
                    }, 600);
                }
            @endif
        });

        async function updateCity(element, elementID) {
            let state_id = element.value;
            const elementv2 = document.getElementById(elementID);

            elementv2.innerHTML = '<option value="">Select City</option>';

            if (!state_id) return;

            const responseselectStateCity = await fetch(`/get-state-details-by-type?type=city&state_id=${state_id}`);
            const dataselectStateCity = await responseselectStateCity.json();
            dataselectStateCity.forEach(value => {
                elementv2.innerHTML += `<option value="${value.id}">${value.name}</option>`
            });
        }

        async function updateCityByCollege(collegeEl, cityElementId) {
            const cityEl = document.getElementById(cityElementId);
            if (!cityEl) return;
            cityEl.value = '';
            if (!collegeEl.value) return;
            const resp = await fetch(`/get-college-state?college_id=${collegeEl.value}`);
            const data = await resp.json();
            cityEl.value = data.city || '';
        }

        async function updateCollegeByState(element, elementID) {
            const stateId = element.value;
            const collegeSelect = document.getElementById(elementID);

            collegeSelect.innerHTML = '<option value="">Select College</option>';

            if (!stateId) return;

            const res = await fetch(`/get-colleges-by-state-common?state_id=${stateId}`);
            const data = await res.json();
            if (data.length === 0) {
                collegeSelect.innerHTML = '<option value="">No colleges found for this state</option>';
                return;
            }
            data.forEach(value => {
                collegeSelect.innerHTML += `<option value="${value.id}">${value.name}</option>`;
            });
        }


        document.querySelector('#formSubmission').addEventListener('submit', function(e) {
            const form = this;
            let valid = true;

            // clear old errors
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');

            // validate each required checkbox / radio group (skip groups hidden by conditional logic)
            const groups = form.querySelectorAll('.checkbox-group[data-required="1"]');
            groups.forEach(group => {
                if (group.offsetParent === null) return;
                const name = group.getAttribute('data-group');
                const anyChecked = form.querySelectorAll(
                    `input[name="${name}[]"]:checked, input[name="${name}"]:checked`).length > 0;
                if (!anyChecked) {
                    valid = false;
                    const err = document.getElementById(`error_${name}`);
                    if (err) err.textContent = 'Please select at least one option.';
                    group.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach(cb => cb
                        .classList.add('is-invalid'));
                }
            });

            if (!valid) e.preventDefault();
        });

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.autoDismissAlert .close');
            if (!btn) return;
            e.preventDefault();
            const alertBox = btn.closest('.autoDismissAlert');
            if (alertBox) alertBox.remove();
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const form = document.getElementById('formSubmission');
            const submitBtn = document.getElementById('submitBtn');

            if (!form || !submitBtn) return;

            form.addEventListener('submit', function(e) {

                if (submitBtn.disabled) {
                    e.preventDefault();
                    return false;
                }

                if (e.defaultPrevented) return;

                submitBtn.disabled = true;
                submitBtn.innerText = 'Submitting...';
            });

        });
    </script>

    <script type="module" src="{{ asset('js/form-renderer.js') }}"></script>
@endsection
