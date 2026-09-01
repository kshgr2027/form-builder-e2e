@extends('layouts.admin')

@section('content')
    @verbatim
        <style>
            .stepper {
                padding: 1rem 0.5rem;
                border-bottom: 1px solid #e2e8f0;
                margin-bottom: 2.5rem !important;
            }

            .stepper .step-num {
                width: 32px;
                height: 32px;
                font-weight: 600;
                font-size: 0.875rem;
                transition: all 0.25s ease;
            }

            .stepper .step-num.is-todo {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                color: #e2e8f0;
            }

            .stepper .step-num.is-active {
                background: #00a4ef;
                border: 1px solid #00a4ef;
                color: #fff;
                box-shadow: 0 0 0 4px rgba(0, 164, 239, 0.15);
            }

            .stepper .step-num.is-done {
                background: #fff;
                border: 2px solid #00a4ef;
                color: #00a4ef
            }

            .stepper .step-line {
                height: 2px;
                background: #e2e8f0;
                transition: background-color 0.25s ease;
            }

            .stepper .step-label {
                color: #a8a3b0;
                font-weight: 600;
                font-size: 0.925rem;
            }

            .stepper .step.active .step-label,
            .stepper .step.done .step-label {
                color: #1e293b;
                font-weight: 600;
            }

            .dropzone {
                min-height: 380px;
                border: 2px dashed #cbd5e1;
                background-color: #f8fafc;
                background-image: radial-gradient(#cbd5e1 1.2px, transparent 1.2px);
                background-size: 16px 16px;
                border-radius: 7px;
                padding: 1rem !important;
                transition: all 0.25s ease;
                position: relative;
            }

            .dropzone:hover {
                border-color: #00a4ef;
                background-color: rgba(0, 164, 239, 0.01);
            }

            .dropzone::after {
                color: #64748b !important;
                font-weight: 400;
                font-size: 0.95rem;
            }

            .fields-pallete {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.04), 0 2px 6px -1px rgba(15, 23, 42, 0.02);
                padding: 1.5rem !important;
            }

            #palette .col,
            .field-card {
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            }

            #palette div {
                background: #ffffff;
                border: 1px solid #e2e8f0 !important;
                border-radius: 10px !important;
                padding: 0.75rem 0.5rem !important;
                font-size: 0.85rem !important;
                font-weight: 500 !important;
                color: #0f172a !important;
                cursor: grab;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
                margin: 5px;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            }

            .field-card {
                cursor: pointer;
                margin-bottom: 1rem;
            }

            #palette div:hover,
            .field-card:hover {
                transform: translateY(-2px);
                border-color: #00a4ef !important;
                box-shadow: 0 10px 20px -4px rgba(15, 23, 42, 0.08), 0 4px 8px -2px rgba(15, 23, 42, 0.04);
                /* color: #00a4ef !important; */
                background-color: #ffffff;
            }

            .col-lg-4 .btn-group {
                background-color: #f1f5f9;
                padding: 4px;
                border-radius: 7px;
                border: none;
                gap: 7px;
                justify-content: center;
            }

            .col-lg-4 .btn-group .btn-outline-primary {
                border: none !important;
                color: #64748b;
                font-weight: 500;
                font-size: 0.9rem;
                border-radius: 7px !important;
                padding: 0.5rem 1rem;
                background: transparent;
                transition: all 0.2s ease;
                margin-bottom: 0;
            }

            .col-lg-4 .btn-group .btn-outline-primary:hover {
                color: #0f172a;
                background: rgba(15, 23, 42, 0.04);
            }

            .col-lg-4 .btn-group .btn-check:checked+.btn-outline-primary {
                background-color: #ffffff !important;
                color: #0f172a !important;
                font-weight: 600;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08), 0 1px 2px rgba(15, 23, 42, 0.04) !important;
            }

            .btn-light {
                background-color: #f1f5f9;
                border-color: #e2e8f0;
                color: #0f172a;
                font-weight: 500;
                border-radius: 8px;
                padding: 0.6rem 1.2rem;
            }

            .btn-light:hover {
                background-color: #e2e8f0;
            }

            .btn-primary {
                background-color: #00a4ef;
                border-color: #00a4ef;
                font-weight: 500;
                border-radius: 8px;
                /* padding: 0.6rem 1.2rem; */
                box-shadow: 0 2px 4px rgba(0, 164, 239, 0.15);
            }

            .btn-primary:hover {
                background-color: #008cd1;
                border-color: #008cd1;
            }

            .fields-pallete {
                background: #ffffff;
                border: 1px solid #cbd5e1;
                border-radius: 14px;
                box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.04);
                padding: 1.25rem !important;
                overflow: hidden;
                width: 100% !important;
            }

            #palette {
                display: flex !important;
                flex-wrap: wrap !important;
                width: auto !important;
                margin-left: -4px !important;
                margin-right: -4px !important;
            }

            #palette>div {
                padding: 4px !important;
                box-sizing: border-box !important;
            }

            #palette>div>div,
            #palette>div.btn {
                background: #ffffff !important;
                border: 1px solid #e2e8f0 !important;
                border-radius: 10px !important;
                padding: 0.85rem 0.4rem !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                text-align: center !important;
                gap: 6px !important;
                width: 100% !important;
                height: 82px !important;
                min-height: 82px !important;
                max-height: 82px !important;
                box-sizing: border-box !important;
                cursor: grab;
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }

            #palette span,
            #palette p,
            #palette .field-label {
                font-size: 0.76rem !important;
                line-height: 1.2 !important;
                font-weight: 500 !important;
                color: #475569 !important;
                margin: 0 !important;
                padding: 0 2px !important;
                width: 100% !important;
                white-space: normal !important;
                display: -webkit-box !important;
                -webkit-line-clamp: 2 !important;
                -webkit-box-orient: vertical !important;
                overflow: hidden !important;
            }

            #palette i,
            #palette svg {
                font-size: 1.2rem !important;
                color: #94a3b8 !important;
                margin: 0 !important;
                transition: all 0.2s ease !important;
            }

            #palette .field-card:hover,
            #palette>div>div:hover,
            #palette>div.btn:hover {
                transform: translateY(-2px) !important;
                border-color: #00a4ef !important;
                background-color: rgba(0, 164, 239, 0.04) !important;
                box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.04) !important;
            }

            #palette .field-card:hover span,
            #palette>div>div:hover span {
                color: #00a4ef !important;
                font-weight: 600 !important;
            }

            #palette .field-card:hover i,
            #palette>div>div:hover i {
                color: #00a4ef !important;
                transform: scale(1.06) !important;
            }

            #canvas .field-card .card-body {
                padding: .75rem 1rem
            }

            #canvas .field-card .form-control,
            #canvas .field-card .form-select {
                margin-bottom: 0
            }

            @media (min-width: 992px) {
                .builder-side {
                    position: sticky;
                    top: 5rem;
                    align-self: flex-start;
                    max-height: calc(100vh - 6rem);
                    overflow-y: auto
                }

                .builder-side .btn-group {
                    position: sticky;
                    top: 0;
                    z-index: 3;
                    background: #fff;
                    margin-bottom: 0 !important;
                    padding-bottom: .75rem;
                    border-bottom: 1px solid #f0f0f5
                }
            }

            @media (max-width: 991.98px) {
                .add-field-bar {
                    position: sticky;
                    bottom: 4.5rem;
                    z-index: 1020;
                    margin-top: .5rem;
                    padding: .6rem 0;
                    background: #fff;
                    box-shadow: 0 -2px 8px rgba(0, 0, 0, .06)
                }
            }

            #wizFoot {
                position: sticky;
                bottom: 0;
                z-index: 1030;
                margin-top: 1rem !important;
                padding: .75rem 0;
                background: #fff;
                border-top: 1px solid #ededf5
            }

            #wizFoot:empty {
                display: none
            }

            .field-card.selected {
                border-color: #00A4EF !important;
                box-shadow: 0 0 0 2px rgba(105, 100, 247, .15)
            }

            .field-tools {
                opacity: 0;
                transition: opacity .15s
            }

            .field-card:hover .field-tools,
            .field-card.selected .field-tools {
                opacity: 1
            }

            .req {
                color: #f84242;
                font-weight: 700;
                margin-left: 2px
            }

            .offcanvas-bottom {
                height: auto;
                max-height: 85vh
            }

            @media(max-width:991.98px) {
                .field-tools {
                    opacity: 1
                }

                /* .dropzone {
                            min-height: 280px;
                            padding: 1.25rem !important;
                        } */
            }

            @media(max-width:767.98px) {
                .stepper-desktop {
                    display: none !important
                }

                #canvas.field-card span {
                    font-size: 13px;
                }

                .input-group.buttonDiv {
                    flex-direction: column
                }

                .input-group.buttonDiv input {
                    width: 100%;
                    border-radius: 0;
                    font-size: 10px;
                    padding: 10px;
                }
                .input-group.buttonDiv .btn{
                    border-radius: 0px
                }
            }

            @media(min-width:768px) {
                .stepper-mobile {
                    display: none !important
                }
            }

            #sideOptionsBody,
            #optsCanvasBody {
                font-size: .95rem
            }

            #sideOptionsBody {
                background: #ffffff;
                border: 1px solid #cbd5e1;
                border-radius: 14px;
                box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.04);
                padding: 1.25rem !important;
                overflow: hidden;
                width: 100% !important;
            }

            #sideOptionsBody .form-select-sm,
            #optsCanvasBody .form-select-sm,
            #sideOptionsBody .form-control-sm,
            #optsCanvasBody .form-control-sm,
            #sideOptionsBody .input-group-sm>*,
            #optsCanvasBody .input-group-sm>* {
                font-size: .92rem;
                padding: .45rem .75rem;
                height: auto;
                min-height: auto
            }

            #sideOptionsBody .form-check-label,
            #optsCanvasBody .form-check-label {
                font-size: .95rem
            }

            .side-app .form-switch,
            #optsCanvasBody .form-switch {
                padding-left: 2.85em;
                min-height: 1.5em;
                display: flex;
                align-items: center
            }

            .side-app .form-switch .form-check-input,
            #optsCanvasBody .form-switch .form-check-input {
                width: 2.5em;
                height: 1.3em;
                margin-top: 0;
                margin-left: -2.85em;
                flex-shrink: 0
            }

            .side-app .form-switch .form-check-label,
            #optsCanvasBody .form-switch .form-check-label {
                margin-bottom: 0
            }

            #sideOptionsBody .o-cond,
            #optsCanvasBody .o-cond {
                background: #fafafb
            }

            #sideOptionsBody .o-cdel,
            #optsCanvasBody .o-cdel {
                font-size: .9rem
            }

            .btn-check:checked+.btn-outline-primary,
            .btn-check:active+.btn-outline-primary,
            .btn-outline-primary:active,
            .btn-outline-primary.active,
            .btn-outline-primary.dropdown-toggle.show {
                background-color: #00A4EF !important;
            }

            .fields-pallete {
                box-shadow: 0 10px 40px 0 rgba(62, 57, 107, 0.1), 0 2px 9px 0 rgba(62, 57, 107, 0.1);
                padding: 20px;
            }

            .border.rounded.p-4 {
                border: 1px solid #e2e8f0 !important;
                background-color: #ffffff;
                border-radius: 7px !important;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05);
            }

            .form-control,
            .form-select {
                border: 1px solid #e2e8f0;
                padding: 0.625rem 0.875rem;
                border-radius: 8px;
                font-size: 0.95rem;
                color: #1e293b;
                box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.02);
                transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            }

            #redirect.form-select {
                height: auto;
            }

            #settingsList label.btn-sm {
                padding: 6px 12px !important;
            }
        </style>
    @endverbatim

    <div class="app-content my-3 my-md-5">
        <div class="side-app">
            <div class="p-md-4">
                <h1 class="fw-bold mb-4" id="pageTitle">Form Builder</h1>

                <div class="card">
                    <div class="card-body p-4">

                        <ol class="stepper stepper-desktop list-unstyled d-flex align-items-center mb-4">
                            <li class="step d-flex align-items-center" data-step="1"><span
                                    class="step-num rounded-circle d-inline-flex align-items-center justify-content-center is-active">1</span><span
                                    class="ms-2 step-label">Details</span></li>
                            <span class="step-line flex-grow-1 mx-3"></span>
                            <li class="step d-flex align-items-center" data-step="2"><span
                                    class="step-num rounded-circle d-inline-flex align-items-center justify-content-center is-todo">2</span><span
                                    class="ms-2 step-label">Builder</span></li>
                            <span class="step-line flex-grow-1 mx-3"></span>
                            <li class="step d-flex align-items-center" data-step="3"><span
                                    class="step-num rounded-circle d-inline-flex align-items-center justify-content-center is-todo">3</span><span
                                    class="ms-2 step-label">Settings</span></li>
                            <span class="step-line flex-grow-1 mx-3"></span>
                            <li class="step d-flex align-items-center" data-step="4"><span
                                    class="step-num rounded-circle d-inline-flex align-items-center justify-content-center is-todo">4</span><span
                                    class="ms-2 step-label">Finish</span></li>
                        </ol>
                        <div class="stepper-mobile mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2"><span class="fw-semibold"
                                    id="smLabel">Step 1 of 4 · Details</span><span class="badge bg-primary"
                                    id="smType"></span></div>
                            <div class="progress" style="height:6px">
                                <div class="progress-bar" id="smBar" style="width:25%"></div>
                            </div>
                        </div>

                        <!-- STEP 1 DETAILS -->
                        <section class="wiz-step" data-step="1">
                            <div class="row g-4">
                                <div class="col-lg-8">
                                    <div class="border rounded p-2 p-md-4">
                                        <div
                                            class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="mb-1">Form basics</h5>
                                                <p class="text-muted mb-4">Enter the primary details for your new
                                                    data-collection form.</p>
                                            </div>
                                            <span class="badge bg-primary p-2 fw-normal" id="typeBadge"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold mb-1" for="formTitle">Form title <span
                                                    class="text-danger">*</span></label>
                                            <input id="formTitle" type="text" class="form-control" maxlength="200"
                                                placeholder="e.g., Fall 2024 Registration">
                                            <small class="text-muted"><span id="titleCount">0</span>/200</small>
                                            <div class="invalid-feedback">Please enter a form title.</div>
                                        </div>
                                        <div class="mb-3 d-none" id="studentTypeWrap">
                                            <label class="form-label fw-semibold" for="studentType">Target student type
                                                <span class="text-danger">*</span></label>
                                            <input id="studentType" type="text" class="form-control"
                                                placeholder="Enter target student type…">
                                            <div class="invalid-feedback">Please enter a target student type.</div>
                                        </div>
                                        <p class="text-muted small mb-0">Public URL: <span
                                                id="publicUrl">https://{{ request()->getHost() }}/submit/</span></p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- STEP 2 BUILDER -->
                        <section class="wiz-step d-none" data-step="2">
                            <div class="row g-3">
                                <div class="col-lg-8">
                                    <div class="border rounded p-2 p-md-4">
                                        <div class="d-flex align-items-center gap-2 flex-wrap mb-3" id="pageTabs"></div>
                                        {{-- Page label input hidden for now
                                        <input id="pageLabel" type="text" class="form-control mb-3"
                                            placeholder="Page label (optional)">
                                        --}}
                                        <input id="pageLabel" type="hidden">
                                        <div id="canvas" class="dropzone d-flex flex-column p-3"></div>
                                        <div class="d-lg-none add-field-bar"><button class="btn btn-primary w-100"
                                                type="button" data-bs-toggle="offcanvas"
                                                data-bs-target="#paletteCanvas">+ Add field</button></div>
                                    </div>
                                </div>
                                <div class="col-lg-4 d-none d-lg-block builder-side">
                                    <div class="btn-group w-100 mb-3" role="group">
                                        <input type="radio" class="btn-check" name="ptab" id="ptab-fields"
                                            autocomplete="off" checked>
                                        <label class="btn btn-outline-primary" for="ptab-fields">Add fields</label>
                                        <input type="radio" class="btn-check" name="ptab" id="ptab-options"
                                            autocomplete="off">
                                        <label class="btn btn-outline-primary" for="ptab-options">Field options</label>
                                    </div>
                                    <div data-pbody="fields" class="fields-pallete">
                                        <p class="text-uppercase text-muted small fw-bold mb-2">Standard fields</p>
                                        <div class="row row-cols-2 g-2" id="palette"></div>
                                    </div>
                                    <div data-pbody="options" class="d-none" id="sideOptionsBody">
                                        <p class="text-muted small mb-0">Select a field on the canvas to edit its label,
                                            validation and options.</p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- STEP 3 SETTINGS -->
                        <section class="wiz-step d-none" data-step="3">
                            <div class="border rounded p-4">
                                <h5 class="mb-1">Form settings</h5>
                                <p class="text-muted mb-4">Control visibility, submission behaviour and notifications for
                                    this form.</p>
                                <div id="regBox" class="border rounded p-3 mb-4 d-none">
                                    <div class="row align-items-center g-2">
                                        <div class="col">
                                            <span class="fw-semibold d-block">Old-phase students allowed</span>
                                            <span class="text-muted small">Let students from previous phases register
                                                through this form.</span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="form-check form-switch m-0"><input class="form-check-input"
                                                    type="checkbox" role="switch" id="oldPhase"></div>
                                        </div>
                                    </div>
                                    <div class="row align-items-center g-2 mt-2 pt-2 border-top">
                                        <div class="col">
                                            <span class="fw-semibold d-block">Dynamic URL</span>
                                            <span class="text-muted small">Append a unique <code>{dynamic_value}</code>
                                                to the form URL for each user or response.</span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="form-check form-switch m-0"><input class="form-check-input"
                                                    type="checkbox" role="switch" id="dynamicUrl"></div>
                                        </div>
                                    </div>
                                </div>
                                <div id="settingsList"></div>
                                <div id="scoringParamsBox" class="border rounded p-3 mt-3 mb-2 d-none">
                                    <span class="fw-semibold d-block">Parameters for scoring</span>
                                    <span class="text-muted small d-block mb-3">Add each parameter responses are scored
                                        on, and its weightage.</span>
                                    <div id="scoringParams"></div>
                                    <button type="button" class="btn btn-sm btn-light" id="addScoringRow">+ Add
                                        row</button>
                                </div>
                                <div class="row g-3 mt-2">
                                    <div class="col-12"><label class="form-label fw-semibold" for="redirect">Redirect
                                            to</label><select id="redirect" class="form-select">
                                            <option>Same page</option>
                                            <option>Custom URL</option>
                                        </select></div>
                                    <div class="col-12"><label class="form-label fw-semibold" for="msg">Message to
                                            show <span class="text-muted fw-normal">(after submission)</span></label>
                                        <textarea id="msg" class="form-control" rows="2">Form submitted successfully!</textarea>
                                    </div>
                                    <div class="col-12"><label class="form-label fw-semibold" for="submitText">Submit
                                            button text</label><input id="submitText" type="text" class="form-control"
                                            value="Submit"></div>
                                </div>
                            </div>
                        </section>

                        <!-- STEP 4 FINISH -->
                        <section class="wiz-step d-none" data-step="4">
                            <div class="text-center mb-4">
                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 text-success mb-3"
                                    style="width:64px;height:64px"><svg viewBox="0 0 24 24" width="32"
                                        height="32" fill="none" stroke="white" stroke-width="2.5"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M5 12l5 5l9 -9" />
                                    </svg></div>
                                <h4 class="mb-1">Your form is ready!</h4>
                                <p class="text-muted"><span id="finishName">"Untitled"</span> has been created.
                                    <span class="badge bg-warning text-dark ms-1" id="publishBadge">Draft</span>
                                </p>
                            </div>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="mb-1">Share &amp; preview</h6>
                                    <p class="text-muted small mb-3">Use this link to let people open and submit the form.
                                    </p>
                                    <div class="input-group buttonDiv">
                                        <input type="text" class="form-control bg-light" id="shareLink" readonly
                                            value="">
                                        <button class="btn btn-outline-secondary" type="button" id="copyShare">Copy
                                            link</button>
                                        <a class="btn btn-outline-primary" id="previewLink" target="_blank"
                                            href="#">Preview</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h6 class="mb-1">Publish options</h6>
                                    <p class="text-muted small mb-3">Make the form live now, or set it up later from the
                                        forms dashboard.</p>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="border rounded p-3 h-100 d-flex flex-column">
                                                <div class="mb-2 text-primary"><i class="fa-solid fa-rocket"></i></div>
                                                <h6 class="mb-1">Publish now</h6>
                                                <p class="text-muted small flex-grow-1">Make the form immediately
                                                    accessible to your audience.</p>
                                                <button class="btn btn-primary w-100" type="button"
                                                    id="publishNow">Publish now</button>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="border rounded p-3 h-100 d-flex flex-column">
                                                <div class="mb-2 text-primary"><i class="fa-solid fa-user-plus"></i>
                                                </div>
                                                <h6 class="mb-1">Publish &amp; assign</h6>
                                                <p class="text-muted small flex-grow-1">Publish and assign this form to a
                                                    batch of students.</p>
                                                <button class="btn btn-light w-100" type="button"
                                                    disabled>Assign</button>
                                                <span class="text-muted small fst-italic mt-1">Coming soon</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="border rounded p-3 h-100 d-flex flex-column">
                                                <div class="mb-2 text-primary"><i class="fa-solid fa-clock"></i></div>
                                                <h6 class="mb-1">Schedule for later</h6>
                                                <p class="text-muted small flex-grow-1">Pick a date and time for the form
                                                    to go live automatically.</p>
                                                <button class="btn btn-light w-100" type="button"
                                                    disabled>Schedule</button>
                                                <span class="text-muted small fst-italic mt-1">Coming soon</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center mt-4 pt-3 border-top">
                                        <a href="{{ route('form-builder.index') }}"
                                            class="btn btn-outline-secondary mb-3 mb-lg-0 me-2">Go to forms dashboard</a>
                                        <button class="btn btn-outline-primary" type="button" id="createAnother">Create
                                            another form</button>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="d-flex justify-content-center  align-items-center gap-2 flex-wrap mt-4 justify-content-md-between"
                            id="wizFoot"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-bottom" id="paletteCanvas" tabindex="-1">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Add a field</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <div class="row row-cols-2 g-2" id="paletteMobile"></div>
        </div>
    </div>
    <div class="offcanvas offcanvas-bottom" id="optsCanvas" tabindex="-1">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Field options</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body" id="optsCanvasBody"></div>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.3.0/tinymce.min.js"
    integrity="sha512-RUZ2d69UiTI+LdjfDCxqJh5HfjmOcouct56utQNVRjr90Ea8uHQa+gCxvxDTC9fFvIGP+t4TDDJWNTRV48tBpQ=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        // Settings for the shared form builder script (public/js/form-builder-v3.js)
        window.FB = {
            mode: 'create',
            publicBase: "https://{{ request()->getHost() }}/submit/",
            dashboardUrl: "{{ route('form-builder.index') }}",
            storeUrl: "{{ route('form-builder.store') }}",
            csrf: "{{ csrf_token() }}",
            sampleUploadUrl: "{{ route('form-builder.uploadSampleFile') }}",
            sampleDeleteUrl: "{{ route('form-builder.deleteSampleFile') }}",
            formStatusUrl: "{{ route('formStatus') }}",
            phases: {!! json_encode($all_phase->pluck('phaseid')->values()) !!},
            activePhase: {!! json_encode(optional($all_phase->firstWhere('active', 1))->phaseid ?? '') !!}
        };
    </script>
    <script src="{{ asset('js/form-builder-v3.js') }}"></script>
@endsection