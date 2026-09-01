@extends('layouts.admin')
<link rel="stylesheet" href="{{ asset('css/form-builder.css') }}">
@section('content')

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link
        href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css" rel="stylesheet">


    <style>
        .fb-page {
            --primary: #6964f7;
            --primary-blue: #00a4ef;
            --primary-hover: #605af7;
            --primary-soft: rgba(105, 100, 247, .18);
            --text: #5c5776;
            --head: #18113c;
            --muted: #a8a3b0;
            --border: #ededf5;
            --page-bg: #f5f4f9;
            --card-bg: #fff;
            --success: #01d277;
            --grey: #868e96;
            --radius: 7px;
            --edu-border: #e4eef5;
            --edu-tint: #f2fafe;
            --edu-primary-deep: #008fd2;
            /* font-family: 'Nunito', sans-serif; */
        }

        .fb-wrap {
            padding: 8px 0;
        }

        .fb-page .card {
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: none;
        }

        /* .fb-page .btn-primary {
                        background: var(--primary);
                        border-color: var(--primary);
                    } */

        .fb-page .btn-primary:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .fb-panel-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .fb-panel-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 18px;
            color: var(--head);
            margin: 0;
        }

        .fb-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .fb-toolbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
            /* margin-left: auto; */
        }

        .view-switch {
            display: inline-flex;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .vs-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            background: #fff;
            color: var(--muted);
            width: 35px;
            height: 35px;
            cursor: pointer;
        }

        .vs-btn+.vs-btn {
            border-left: 1px solid var(--border);
        }

        .vs-btn[aria-pressed="true"] {
            background: var(--primary-blue);
            color: #fff;
        }

        .vs-btn svg {
            width: 18px;
            height: 18px;
        }

        .vs-btn i {
            font-size: 16px;
            line-height: 1;
        }

        .filter-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .ft {
            border: 1px solid var(--border);
            background: #fff;
            color: #000;
            border-radius: 7px;
            padding: 8px 16px;
            font: 500 13px/1 'Roboto', sans-serif;
            cursor: pointer;
        }

        .ft[aria-pressed="true"] {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
            color: #fff;
        }

        .ft.form-filter:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        .ft-count {
            margin-left: 5px;
            font-weight: 700;
        }

        .fb-search {
            position: relative;
            flex: 1 1 220px;
            min-width: 0;
        }

        .fb-search svg,
        .fb-search i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            font-size: 16px;
            color: var(--muted);
            pointer-events: none;
        }

        .fb-search .form-control {
            padding-left: 38px;
            width: 100%;
            height: 35px;
            font-size: 0.8rem;
        }

        .form-card {
            position: relative;
            background: #fff;
            border: 1px solid var(--edu-border);
            border-radius: 7px;
            box-shadow: 0 3px 12px rgba(0, 143, 210, 0.06);
            height: 100%;
            z-index: 1;
            overflow: visible;
            transition:
                transform 0.18s ease,
                box-shadow 0.18s ease,
                border-color 0.18s ease;
        }

        .form-card.form-card.form-card--template {
            border-top: 1px solid var(--primary-blue);
            box-shadow: inset 0 3px 0 var(--primary-blue);
        }

        .form-card:has(.dropdown-menu.show) {
            z-index: 1055;
        }

        .form-card:hover {
            transform: translateY(-4px);
            box-shadow: inset 0 3px 0 var(--primary-blue), 0 10px 24px rgba(0, 143, 210, .14);
            border-top: 1px solid var(--primary-blue);
        }

        .ribbon-top-right::before,
        .ribbon-top-right::after {
            display: none !important;
        }

        .ribbon-top-right span {
            left: unset;
            top: unset;
            transform: unset;
        }

        .form-card__body {
            padding: 1.1rem 1.15rem 0.9rem;
            /* display: flex;
                    flex-direction: column;
                    gap: 16px;
                    flex: 1; */
        }

        .form-card__name {
            display: -webkit-box;
            font-size: 0.98rem;
            font-weight: 600;
            color: #18113c;
            text-decoration: none;
            padding-right: 5.5rem;
            margin-bottom: 0.35rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: wrap;
            transition: color 0.15s ease;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.4;
            min-height: 2.8em;
            white-space: normal;
            word-break: break-word;
        }

        .form-card__name:hover {
            /* text-decoration: underline; */
            color: var(--primary-blue);
        }

        .form-card__type {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.68rem;
            font-weight: 700;
            /* color: #008fd2;
                     background: #f2fafe;
                    border: 1px solid #cfe9f7; */
            border-radius: 7px;
            padding: 0.18rem 0.6rem;
        }

        .table th,
        .table td {
            padding: 0.5rem;
        }

        .form-card__type i {
            font-size: 0.72rem;
        }

        .form-card__stats {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 12px;
            gap: 1rem;
            margin-top: 0.95rem;
        }

        .form-card__num {
            font-size: 1.7rem;
            font-weight: 700;
            line-height: 1;
            color: var(--head);
        }

        .form-card__num-label {
            font-size: 0.68rem;
            font-weight: 600;
            color: #8a93a5;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 0.2rem;
        }

        .form-card__meta {
            text-align: right;
        }

        .form-card__meta-label {
            display: block;
            font-weight: 600;
            font-size: 0.66rem;
            color: #8a93a5;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-card__meta-val {
            color: #18113c;
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.15rem;
        }

        .form-card.form-card--template .form-card__meta-val {
            color: var(--primary-blue);
        }

        .form-card__divider {
            /* background: var(--border); */
            margin: 0.85rem 0 0.7rem;
            border-color: #e4eef5;
            opacity: 1;
            height: unset !important;
        }

        .form-card__actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .form-card__actions .left {
            display: flex;
            gap: 0.45rem;
        }

        .ribbon {
            /* width: 140px; */
            /* height: 140px; */
            /* overflow: hidden; */
            position: absolute;
            top: 0.85rem !important;
            right: 0.85rem !important;
            z-index: 2;
            width: auto;
            overflow: unset;
            height: auto;
        }

        .ribbon span {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.62rem;
            font-weight: 600;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            padding: 0.24rem 0.65rem;
            border-radius: 7px;
            position: unset;
            box-shadow: none;
            width: auto;
            text-shadow: none;
        }

        /* .ribbon-top-right {
                        top: -10px;
                        right: -10px;
                        } */

        .ribbon-published span {
            /* background: var(--success); */
            background: #e8f8f3;
            color: #00a389;
            border: 1px solid #c4ece2;
        }

        .ribbon span::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .ribbon-unpublished span {
            background: var(--grey);
            background: #fff7e6;
            color: #c98a12;
            border: 1px solid #f3e2ba;
        }

        .ribbon-archived span {
            /* background: #f84242; */
            background: #f1f3f7;
            color: #7a8499;
            border: 1px solid #dde2ec;
        }

        .fb-iconbtn {
            height: 36px;
            width: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e4eef5;
            background: #fff;
            border-radius: var(--radius);
            padding: 9px 10px;
            /* font: 600 13px/1 'Nunito', sans-serif; */
            color: #5a6270;
            font-size: 0.95rem;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .fb-iconbtn:hover {
            background: var(--edu-tint);
            border-color: var(--primary-blue);
            color: var(--edu-primary-deep);
        }

        .fb-iconbtn svg {
            width: 18px;
            height: 18px;
        }

        /* .fb-iconbtn i {
                     font-size: 16px;
                      line-height: 1;
                 } */

        /* .fb-iconbtn--icon {
                       width: 40px;
                      height: 40px;
                      padding: 0;
                    } */

        .fb-iconbtn.dropdown-toggle::after {
            display: none;
        }

        .fb-dropdown .dropdown-menu {
            border: 1px solid var(--edu-border);
            border-radius: 10px;
            box-shadow: 0 10px 24px rgba(24, 17, 60, 0.12);
            padding: 0.35rem;
            min-width: 175px;
            font-size: 0.8rem;
        }

        .fb-dropdown .dropdown-item {
            border-radius: 7px;
            padding: 0.45rem 0.65rem;
            display: flex;
            align-items: center;
            gap: 0.55rem;
            color: #3a4258;
            font-weight: 500;
        }

        .fb-dropdown .dropdown-item i {
            color: #8a93a5;
            font-size: 0.85rem;
            width: 16px;
            text-align: center;
        }

        .fb-dropdown .dropdown-item:hover {
            background: var(--edu-tint);
            color: var(--edu-primary-deep);
        }

        .fb-dropdown .dropdown-item:hover i {
            color: var(--edu-primary-deep);
        }

        .fb-dropdown .dropdown-item.text-danger i {
            color: #d63b56;
        }

        .fb-dropdown .dropdown-item.text-danger:hover {
            background: #fdecef;
            color: #d63b56;
        }

        .fb-dropdown .dropdown-divider {
            border-color: var(--edu-border);
            margin: 0.3rem 0;
        }

        .fb-more {
            min-width: 230px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 6px;
            box-shadow: 0 10px 40px rgba(62, 57, 107, .12);
            background: #fff;
            z-index: 1100;
        }

        .fb-more .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 5px;
            font-size: 14px;
            padding: 8px 10px;
        }

        .fb-more .dropdown-item svg {
            width: 16px;
            height: 16px;
            color: var(--muted);
        }

        .fb-more .dropdown-item i {
            width: 16px;
            font-size: 15px;
            text-align: center;
            color: var(--muted);
        }

        .fb-switch-row {
            padding: 8px 10px;
        }

        .fb-switch-row .form-check-input:checked {
            background-color: var(--success);
            border-color: var(--success);
        }

        .fb-created {
            font-size: 12px;
            color: var(--muted);
            padding: 4px 10px;
        }

        .fb-table thead th {
            font-size: 12px;
            text-transform: capitalize;
            letter-spacing: .04em;
            font-weight: 700 !important;
            color: #333;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        .fb-table td {
            border-top: 1px solid var(--border);
            color: var(--text);
            font-size: 14px;
            vertical-align: middle;
        }

        .fb-link {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }

        .fb-link:hover {
            text-decoration: underline;
        }

        .fb-rowactions {
            display: flex;
            gap: 6px;
            align-items: center;
            justify-content: start;
        }

        .fb-empty {
            padding: 48px 16px;
            text-align: center;
            color: var(--muted);
            font-size: 15px;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 30px;
            height: 16px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 16px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 12px;
            width: 12px;
            left: 2px;
            bottom: 2px;
            background-color: #fff;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #2196F3;
        }

        input:checked+.slider:before {
            transform: translateX(14px);
        }

        .offcanvas-end {
            width: 600px;
        }

        /* pagination */
        .fb-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        .fb-count-label {
            font-size: 13px;
            color: var(--muted);
        }

        span.template-badge {
            display: inline-flex;
            align-items: center;
            background: #eef9ff;
            color: var(--edu-primary-deep);
            border: 1px solid #cfe9f7;
            font-size: .68rem;
            font-weight: 600;
            padding: .25rem .65rem;
            border-radius: 50px;
            margin-bottom: .8rem;
        }

        @media (min-width: 992px) {
            .form-card__name {
                max-width: 250px;
            }
        }

        @media (max-width: 575.98px) {
            .form-card__num {
                font-size: 1.5rem;
            }

            .form-card__name {
                padding-right: 5rem;
                min-height: auto;
            }
            .fb-pagination{
                justify-content: center
            }

            .fb-pagination .pagination {
                flex-wrap: nowrap;
                justify-content: center;
            }

            .fb-pagination .page-item {
                display: none;
            }

            .fb-pagination .page-item:first-child,
            .fb-pagination .page-item:last-child {
                display: block;
            }

            .fb-pagination .page-item.active {
                display: block;
            }

            .fb-pagination .page-item.active+.page-item {
                display: block;
            }

            .fb-pagination .page-item.disabled {
                display: block;
            }

            .fb-pagination .page-link {
                padding: .45rem .7rem;
                font-size: 14px;
            }
        }

        /*
            .fb-page .pagination .page-link {
                color: var(--text);
                border-color: var(--border);
                border-radius: 6px;
                 margin: 0 3px;
                font-weight: 600;
                font-size: 13px;
                }

            .fb-page .pagination .page-item.active .page-link {
                background: var(--primary);
                  border-color: var(--primary);
                color: #fff;
             } */
    </style>

    <div class="app-content my-3 my-md-5 fb-page">
        <div class="side-app fb-wrap">

            <div class="page-header d-flex">
                <h4 class="page-title">{{ $title }}</h4>
                @if (in_array(1, [1, 3, 10, 21]))
                    <div class="d-flex align-items-center gap-2">
                        @if (1 == 21)
                            @php
                                $deleteRequestCount = DB::table('form_templates')
                                    ->where('delete_requested', 1)
                                    ->where('delete_approved', 0)
                                    ->count();
                            @endphp
                            <a href="{{ route('form-builder.delete-requests') }}" class="btn btn-danger">
                                Delete Requests
                                @if ($deleteRequestCount > 0)
                                    <span class="badge bg-white text-danger">{{ $deleteRequestCount }}</span>
                                @endif
                            </a>
                        @endif
                        <div class="dropdown">
                            <a data-bs-toggle="dropdown" href="javascript:void(0)" class="btn btn-primary"
                                aria-expanded="false">
                                Create New Form <i class="fa-solid fa-angle-down"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/">Survey Form</a></li>
                                
                            </ul>
                        </div>
                    </div>
                @endif
            </div>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="card">
                <div class="card-body p-4" style="background: #fbfdff">

                    <div class="fb-panel-head">
                        <h2 class="fb-panel-title">My Forms</h2>

                    </div>

                    <div class="fb-toolbar">
                        <div class="filter-tabs" role="group" aria-label="Filter forms by status">
                            <button type="button" class="ft form-filter" data-filter="all" aria-pressed="true">All <span
                                    class="ft-count" data-count="all"></span></button>
                            <button type="button" class="ft form-filter" data-filter="published"
                                aria-pressed="false">Published <span class="ft-count"
                                    data-count="published"></span></button>
                            <button type="button" class="ft form-filter" data-filter="unpublished"
                                aria-pressed="false">Draft <span class="ft-count" data-count="unpublished"></span></button>
                            <button type="button" class="ft form-filter" data-filter="archived"
                                aria-pressed="false">Archived <span class="ft-count" data-count="archived"></span></button>
                        </div>
                        <div class="fb-toolbar-right">
                            <div class="fb-search">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input id="fbSearch" type="search" class="form-control" placeholder="Search forms…"
                                    autocomplete="off">
                            </div>
                            <div class="view-switch" role="group" aria-label="List view">
                                <button type="button" class="vs-btn" data-view="cards" aria-pressed="true"
                                    title="Card view">
                                    <i class="fa-solid fa-table-cells-large"></i>
                                </button>
                                <button type="button" class="vs-btn" data-view="table" aria-pressed="false"
                                    title="Table view">
                                    <i class="fa-solid fa-list"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="fbLoader" class="fb-empty d-none">
                        <div class="spinner-border text-primary" role="status"><span
                                class="visually-hidden">Loading…</span></div>
                        <div class="mt-2">Loading forms…</div>
                    </div>

                    <div id="cardView" class="row g-3"></div>

                    <div id="tableView" class="table-responsive d-none">
                        <table class="table fb-table mb-0">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Form name</th>
                                    <th>Type</th>
                                    <th>Responses</th>
                                    <th>Created</th>
                                    <th>Last response</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody"></tbody>
                        </table>
                    </div>

                    <div id="emptyState" class="fb-empty d-none">No forms found for this filter.</div>
                    <div class="fb-pagination">
                        <span class="fb-count-label" id="countLabel"></span>
                        <nav aria-label="Forms pagination">
                            <ul class="pagination mb-0" id="fbPagination"></ul>
                        </nav>
                    </div>

                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <div class="card-title">Ready-to-Use Form Templates</div>
                </div>
                <div class="card-body">
                    <p>Browse ready-made templates. Preview a form or duplicate it to quickly create your own.</p>
                    <div id="templateCards" class="row g-3"></div>
                    <div id="templateEmpty" class="fb-empty d-none">No templates available.</div>
                </div>
            </div>

        </div>
    </div>

    {{-- ===== Bulk Upload Offcanvas (unchanged) ===== --}}
    <div class="offcanvas offcanvas-end custom-offcanvas" tabindex="-1" id="globalOffcanvas">
        <div class="offcanvas-header py-3">
            <h3 class="card-title fw-bold text-primary mb-4" id="form_title">Bulk Onboard Students</h3>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0" style="background-color:#fcfcfc;">
            <div class="row container">
                <div class="col-md-6">
                    <div class="form-group"><input type="file" id="bulk-csv" accept=".csv,.xlsx,.xls"></div>
                </div>
                <div class="col-md-6">
                    <div class="form-group"><label class="form-label">Download Sample File</label>
                        <div class="same_file_section"></div>
                    </div>
                </div>
                <div class="col-md- start_upload_btn_section"></div>
                <div class="file_field_lists"></div>
                <div class="col-md-12 mt-4 progress_section">
                    <div id="upload-progress-container" class="progress progress-md mb-2"
                        style="height:14px; display:none;">
                        <div id="upload-progress-bar"
                            class="progress-bar progress-bar-striped progress-bar-animated bg-green" style="width:0%;">0%
                        </div>
                    </div>
                    <p id="upload-status" class="mt-2"></p>
                    <div id="upload-summary" style="display:none;" class="alert alert-info mt-3"></div>
                    <div class="panel-group1" id="upload-errors"></div>
                </div>
                <hr>
                <div class="responsive-table">
                    <h3>Mandatory Columns</h3>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Requirement</th>
                                <th>Expected Value</th>
                            </tr>
                        </thead>
                        <tbody id="instructions-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>


    <script>
        const ROLE = {{ 1 }};
        const CSRF = '{{ csrf_token() }}';
        const FORMS_URL = "{{ route('form-builder.index') }}";

        const ICONS = {
            eye: '<i class="fa-regular fa-eye"></i>',
            edit: '<i class="fa-solid fa-pen-to-square"></i>',
            link: '<i class="fa-solid fa-link"></i>',
            dots: '<i class="fa-solid fa-ellipsis-vertical"></i>',
            chart: '<i class="fa-solid fa-chart-column"></i>',
            copy: '<i class="fa-regular fa-copy"></i>',
            upload: '<i class="fa-solid fa-upload"></i>',
            archive: '<i class="fa-solid fa-box-archive"></i>',
            trash: '<i class="fa-solid fa-trash-can"></i>',
            hourglass: '<i class="fa-solid fa-hourglass-half"></i>'
        };
        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        } [c]));

        let currentPage = 1;
        const PAGE_SIZE = 12;
        let ALL_FORMS = [];
        let currentFilter = 'all';
        let currentView = 'cards';

        function copyDropdown(f) {
            return `<div class="dropdown">
    <button class="fb-iconbtn fb-iconbtn--icon dropdown-toggle" type="button" data-bs-toggle="dropdown" title="Copy link">${ICONS.link}</button>
    <ul class="dropdown-menu">
      <li><button class="dropdown-item fb-copy" type="button" data-link="${esc(f.form_link)}">Form link</button></li>
      <li><button class="dropdown-item fb-copy" type="button" data-link="${esc(f.short_link)}">Short form link</button></li>
    </ul></div>`;
        }

        function moreDropdown(f, v) {
            if (!f.can_manage) {
                return `<a class="fb-iconbtn fb-iconbtn--icon" href="${esc(f.report_url)}" title="View resources">${ICONS.chart}</a>`;
            }
            let deleteItem;
            if (f.delete_requested == 1 && f.delete_approved == 0) {
                deleteItem =
                    `<li><span class="dropdown-item text-warning" style="cursor:default">${ICONS.hourglass}Delete request pending</span></li>`;
            } else {
                const label = f.delete_approved == 1 ? 'Delete' : 'Request delete';
                deleteItem =
                    `<li><button class="dropdown-item text-danger fb-delete" type="button" data-id="${f.id}" data-approved="${f.delete_approved}" data-name="${esc(f.name)}">${ICONS.trash}${label}</button></li>`;
            }
            const archiveItem = f.status === 'archived' ?
                `<li><button class="dropdown-item fb-unarchive" type="button" data-id="${f.id}" data-url="${esc(f.unarchive_url)}" data-name="${esc(f.name)}">${ICONS.archive}Unarchive</button></li>` :
                `<li><button class="dropdown-item fb-archive" type="button" data-id="${f.id}" data-url="${esc(f.archive_url)}" data-name="${esc(f.name)}">${ICONS.archive}Archive</button></li>`;

            return `<div class="dropdown" data-bs-auto-close="outside">
    <button class="fb-iconbtn fb-iconbtn--icon dropdown-toggle" type="button" data-bs-toggle="dropdown" title="More">${ICONS.dots}</button>
    <ul class="dropdown-menu dropdown-menu-end fb-more">
   <li class="fb-switch-row">
    <label class="d-flex align-items-center gap-2 m-0" style="cursor:pointer">
        <span class="switch"><input type="checkbox" class="fb-pub" data-id="${f.id}" ${f.is_published ? 'checked' : ''}><span class="slider"></span></span>
        <span>Published</span>
    </label>
    </li>
      <li><hr class="dropdown-divider"></li>
      ${f.is_published
        ? `<li><a class="dropdown-item" href="${esc(f.edit_url)}">${ICONS.edit}Edit</a></li>`
        : `<li><a class="dropdown-item" href="${esc(f.report_url)}">${ICONS.chart}View responses</a></li>`}
      <li><button class="dropdown-item fb-duplicate" type="button" data-id="${f.id}">${ICONS.copy}Duplicate</button></li>
      <li><button class="dropdown-item fb-bulk" type="button" data-id="${f.id}" data-name="${esc(f.name)}">${ICONS.upload}Bulk upload</button></li>
      <li><span class="fb-created d-block">Created ${esc(f.created)}</span></li>
      <li><hr class="dropdown-divider"></li>
      ${archiveItem}
      ${deleteItem}
    </ul></div>`;
        }

        const FORM_TYPES = {
            registration: {
                label: 'Registration Form',
                color: '#01d277'
            },
            assessment_form: {
                label: 'Assessment Form',
                color: '#f8a000'
            },
            assigment: {
                label: 'Assignment Form',
                color: '#7d5fff'
            },
            survey: {
                label: 'Survey Form',
                color: '#3b9dff'
            }
        };
        const formType = f => FORM_TYPES[f.form_type] || FORM_TYPES.survey;
        const typeBadge = t => `<span class="form-card__type" style="color:${t.color};background:${t.color}20">
            <span style="width:8px;height:8px;border-radius:50%;background:${t.color}"></span>${t.label}</span>`;

        function primaryActionBtn(f) {
            if (!f.can_manage) return '';
            return f.is_published ?
                `<a class="fb-iconbtn fb-iconbtn--icon" href="${esc(f.report_url)}" title="View responses">${ICONS.chart}</a>` :
                `<a class="fb-iconbtn fb-iconbtn--icon" href="${esc(f.edit_url)}" title="Edit">${ICONS.edit}</a>`;
        }

        function cardMarkup(f) {
            const ribbonLabel = f.status === 'published' ? 'Published' : (f.status === 'archived' ? 'Archived' : 'Draft');
            const t = formType(f);
            const editBtn = primaryActionBtn(f);
            return `<div class="col-12 col-sm-6 col-xl-4 fb-item" data-name="${esc(f.name.toLowerCase())}">
        <article class="form-card">
        <div class="ribbon ribbon-top-right ribbon-${f.status}"><span>${ribbonLabel}</span></div>
            <div class="form-card__body">
                <a href="${esc(f.view_url)}" target="_blank" class="form-card__name">${esc(f.name)}</a>
                ${typeBadge(t)}
        <div class="form-card__stats">
          <div><div class="form-card__num">${Number(f.responses).toLocaleString()}</div><div class="form-card__num-label">Responses</div></div>
          <div class="form-card__meta"><span class="form-card__meta-label">Last response</span><span class="form-card__meta-val">${esc(f.last)}</span></div>
        </div>
        <hr class="form-card__divider">
        <div class="form-card__actions">
          <div class="left">
            <a class="fb-iconbtn fb-iconbtn--icon" href="${esc(f.view_url)}" target="_blank" title="View">${ICONS.eye}</a>
            ${editBtn}
            ${copyDropdown(f)}
          </div>
          ${moreDropdown(f,'c')}
        </div>
      </div>
    </article></div>`;
        }

        function rowMarkup(f) {
            const editBtn = primaryActionBtn(f);
            return `<tr class="fb-item" data-name="${esc(f.name.toLowerCase())}">
    <td><label class="switch"><input type="checkbox" class="fb-pub" data-id="${f.id}" ${f.is_published ? 'checked' : ''}><span class="slider"></span></label></td>
    <td><a href="${esc(f.view_url)}" target="_blank" class="fb-link">${esc(f.name)}</a></td>
    <td>${typeBadge(formType(f))}</td>
    <td>${Number(f.responses).toLocaleString()}</td>
    <td>${esc(f.created)}</td>
    <td>${esc(f.last)}</td>
    <td><div class="fb-rowactions">
      <a class="fb-iconbtn fb-iconbtn--icon" href="${esc(f.view_url)}" target="_blank" title="View">${ICONS.eye}</a>
      ${editBtn}${copyDropdown(f)}${moreDropdown(f,'t')}
    </div></td></tr>`;
        }

        function render() {
            const q = ($('#fbSearch').val() || '').trim().toLowerCase();
            const list = q ? ALL_FORMS.filter(f => (f.name + ' ' + formType(f).label).toLowerCase().includes(q)) :
                ALL_FORMS;

            const totalPages = Math.max(1, Math.ceil(list.length / PAGE_SIZE));
            if (currentPage > totalPages) currentPage = totalPages;
            const start = (currentPage - 1) * PAGE_SIZE;
            const pageItems = list.slice(start, start + PAGE_SIZE);

            $('#cardView').html(pageItems.map(cardMarkup).join(''));
            $('#tableBody').html(pageItems.map(rowMarkup).join(''));

            $('#emptyState').toggleClass('d-none', list.length > 0);
            $('#countLabel').text(list.length ? `Showing ${start + 1}–${start + pageItems.length} of ${list.length}` : '');

            let pg =
                `<li class="page-item ${currentPage===1?'disabled':''}"><a class="page-link" href="javascript:void(0)" data-page="${currentPage-1}">Previous</a></li>`;

            const windowSize = 2; // pages shown on each side of current
            let startPage = Math.max(1, currentPage - windowSize);
            let endPage = Math.min(totalPages, currentPage + windowSize);

            if (startPage > 1) {
                pg += `<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page="1">1</a></li>`;
                if (startPage > 2) pg += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
            }
            for (let p = startPage; p <= endPage; p++) {
                pg +=
                    `<li class="page-item ${p===currentPage?'active':''}"><a class="page-link" href="javascript:void(0)" data-page="${p}">${p}</a></li>`;
            }
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) pg += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
                pg +=
                    `<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page="${totalPages}">${totalPages}</a></li>`;
            }

            pg +=
                `<li class="page-item ${currentPage===totalPages?'disabled':''}"><a class="page-link" href="javascript:void(0)" data-page="${currentPage+1}">Next</a></li>`;
            $('#fbPagination').html(pg);

            document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(el => {
                bootstrap.Dropdown.getOrCreateInstance(el, {
                    autoClose: el.closest('[data-bs-auto-close]') ? 'outside' : true,
                    popperConfig: (cfg) => ({
                        ...cfg,
                        strategy: 'absolute'
                    })
                });
            });
        }

        function loadForms() {
            $('#fbLoader').removeClass('d-none');
            $('#cardView, #tableView, #fbPagination').addClass('d-none');

            $.ajax({
                url: FORMS_URL,
                method: 'GET',
                data: {
                    filter: currentFilter
                },
                success: function(res) {
                    ALL_FORMS = res.data || [];
                    if (res.counts) {
                        $('.ft-count').each(function() {
                            $(this).text(res.counts[$(this).data('count')] ?? '');
                        });
                    }
                    render();
                },
                error: function() {
                    console.error('Failed to load forms.');
                },
                complete: function() {
                    $('#fbLoader').addClass('d-none');
                    // restore the active view
                    $('#cardView').toggleClass('d-none', currentView !== 'cards');
                    $('#tableView').toggleClass('d-none', currentView !== 'table');
                    $('#fbPagination').removeClass('d-none');
                }
            });
        }

        $(document).ready(function() {
            loadForms();

            // Filter pills
            $(document).on('click', '.form-filter', function() {
                currentFilter = $(this).data('filter');
                currentPage = 1;
                $('.form-filter').attr('aria-pressed', 'false');
                $(this).attr('aria-pressed', 'true');
                loadForms();
            });

            // Search
            $('#fbSearch').on('input', function() {
                currentPage = 1;
                render();
            });

            // View switch
            $('.vs-btn').on('click', function() {
                currentView = $(this).data('view');
                $('.vs-btn').attr('aria-pressed', 'false');
                $(this).attr('aria-pressed', 'true');
                $('#cardView').toggleClass('d-none', currentView !== 'cards');
                $('#tableView').toggleClass('d-none', currentView !== 'table');
            });

            // Publish toggle
            $(document).on('change', '.fb-pub', function() {
                const formId = $(this).data('id');
                const isActive = $(this).is(':checked') ? 1 : 0;
                $.ajax({
                    url: "{{ route('formStatus') }}",
                    method: 'POST',
                    data: {
                        _token: CSRF,
                        form_id: formId,
                        active: isActive
                    },
                    success: function() {
                        loadForms();
                    },
                    error: function() {
                        console.error('Failed to update status.');
                    }
                });
            });

            // Duplicate
            $(document).on('click', '.fb-duplicate', function() {
                const formId = $(this).data('id');
                swal({
                    title: "Duplicate this form?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#6964f7",
                    confirmButtonText: "Duplicate",
                    cancelButtonText: "Cancel"
                }, function(ok) {
                    if (!ok) return;
                    $.ajax({
                        url: '/form-duplicate/' + formId,
                        type: 'POST',
                        data: {
                            _token: CSRF
                        },
                        success: function(res) {
                            if (res.status === 'success') {
                                window.location.href = `/forms/${res.form_id}/edit`;
                            } else {
                                swal("Failed", res.message || 'Unknown error', "error");
                            }
                        },
                        error: function() {
                            swal("Error", "Error duplicating form.", "error");
                        }
                    });
                });
            });

            // Archive
            $(document).on('click', '.fb-archive', function() {
                const url = $(this).data('url');
                swal({
                    title: "Archive this form?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#f8a000",
                    confirmButtonText: "Archive",
                    cancelButtonText: "Cancel"
                }, function(ok) {
                    if (!ok) return;
                    const form = $(
                        `<form method="POST" action="${url}" style="display:none">@csrf</form>`);
                    $('body').append(form);
                    form.submit();
                });
            });

            // Unarchive
            $(document).on('click', '.fb-unarchive', function() {
                const url = $(this).data('url');
                swal({
                    title: "Unarchive this form?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#6964f7",
                    confirmButtonText: "Unarchive",
                    cancelButtonText: "Cancel"
                }, function(ok) {
                    if (!ok) return;
                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: {
                            _token: CSRF
                        },
                        success: function() {
                            loadForms();
                        },
                        error: function() {
                            swal("Error", "Could not unarchive the form.", "error");
                        }
                    });
                });
            });

            // Bulk upload
            $(document).on('click', '.fb-bulk', function() {
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    const toggle = menu.parentElement.querySelector('[data-bs-toggle="dropdown"]');
                    if (toggle) bootstrap.Dropdown.getInstance(toggle)?.hide();
                });
                openBulkOnboardForm($(this).data('id'), $(this).data('name'));
            });

            // Delete / request delete
            $(document).on('click', '.fb-delete', function() {
                const formId = $(this).data('id');
                const isApproved = $(this).data('approved') == 1;

                swal({
                    title: isApproved ? "Delete this form?" : "Send delete request?",
                    text: isApproved ? "This cannot be undone." : "It will be sent for approval.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#f84242",
                    confirmButtonText: "Continue",
                    cancelButtonText: "Cancel",
                    closeOnConfirm: false
                }, function(ok) {
                    if (!ok) return;

                    swal({
                        title: "Reason for deletion",
                        text: "Add a short reason for the audit log (required).",
                        type: "input",
                        inputPlaceholder: "e.g. Duplicate form, created by mistake…",
                        showCancelButton: true,
                        confirmButtonColor: "#f84242",
                        confirmButtonText: isApproved ? "Delete" : "Send request",
                        cancelButtonText: "Cancel",
                        closeOnConfirm: false
                    }, function(reason) {
                        if (reason === false) return false; // cancelled
                        if (!reason || !reason.trim()) {
                            swal.showInputError("Please enter a reason.");
                            return false;
                        }
                        $.ajax({
                            url: "{{ url('forms-delete-action') }}/" + formId,
                            type: 'POST',
                            data: {
                                _token: CSRF,
                                reason: reason.trim()
                            },
                            success: function(res) {
                                swal("Done", res.message, "success");
                                loadForms();
                            },
                            error: function() {
                                swal("Error", "Something went wrong.", "error");
                            }
                        });
                    });
                });
            });

            // Copy link
            // $(document).on('click', '.fb-copy', function() {
            //     const url = $(this).data('link');
            //     const el = this;
            //     navigator.clipboard.writeText(url).then(() => {
            //         const orig = el.innerHTML;
            //         el.innerHTML = 'Copied!';
            //         setTimeout(() => el.innerHTML = orig, 2000);
            //     });
            // });
            $(document).on('click', '.fb-copy', function() {
                const url = $(this).data('link');
                navigator.clipboard.writeText(url).then(() => {
                    swal({
                        title: "Copied!",
                        text: "Link copied to clipboard.",
                        type: "success",
                        timer: 1500,
                        showConfirmButton: false
                    });
                }).catch(() => {
                    swal("Error", "Could not copy link.", "error");
                });
            });

            // Templates table (unchanged)
            function templateCardMarkup(t) {
                return `<div class="col-12 col-sm-6 col-xl-4">
      <article class="form-card form-card--template">
        <div class="form-card__body">
            <span class="template-badge"><i class="fa-regular fa-file-lines me-1"></i>Template</span>
          <a href="${esc(t.view_url)}" target="_blank" class="form-card__name">${esc(t.name)}</a>
          <div style="text-align:left">
            <span class="form-card__meta-label">Category</span>
            <span class="form-card__meta-val d-block">${esc(t.category)}</span>
          </div>
          <hr class="form-card__divider">
          <div class="form-card__actions">
            <div class="left">
              <a class="fb-iconbtn fb-iconbtn--icon" href="${esc(t.view_url)}" target="_blank" title="Preview">${ICONS.eye}</a>
              <button class="fb-iconbtn fb-iconbtn--icon fb-tpl-duplicate" type="button" data-id="${t.id}" title="Duplicate">${ICONS.copy}</button>
            </div>
          </div>
        </div>
      </article>
    </div>`;
            }

            

            $(document).on('click', '.fb-tpl-duplicate', function() {
                const formId = $(this).data('id');
                swal({
                    title: "Duplicate this template?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#6964f7",
                    confirmButtonText: "Duplicate",
                    cancelButtonText: "Cancel"
                }, function(ok) {
                    if (!ok) return;
                    $.ajax({
                        url: '/form-duplicate/' + formId,
                        type: 'POST',
                        data: {
                            _token: CSRF
                        },
                        success: function(res) {
                            if (res.status === 'success') {
                                window.location.href = `/forms/${res.form_id}/edit`;
                            } else {
                                swal("Failed", res.message || 'Unknown error', "error");
                            }
                        },
                        error: function() {
                            swal("Error", "Error duplicating template.", "error");
                        }
                    });
                });
            });
        });

        {{-- ===== Bulk upload functions (unchanged from your original) ===== --}}
        async function startBulkUpload(formId) {
            const fileInput = document.getElementById('bulk-csv');
            if (!fileInput.files.length) return alert('Please select a file.');
            const file = fileInput.files[0];
            const formData = new FormData();
            formData.append('file', file);
            document.getElementById('bulk-csv').value = '';
            const statusText = document.getElementById('upload-status');
            const progressContainer = document.getElementById('upload-progress-container');
            const progressBar = document.getElementById('upload-progress-bar');
            const errorList = document.getElementById('upload-errors');
            const summaryBox = document.getElementById('upload-summary');
            progressContainer.style.display = 'flex';
            progressBar.style.width = '0%';
            progressBar.innerText = '0%';
            summaryBox.style.display = 'none';
            statusText.innerText = 'Initializing upload...';
            errorList.innerHTML = '';
            const initRes = await fetch(`/forms/${formId}/bulk-upload-init`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json'
                },
                body: formData
            });
            if (initRes.status === 422) {
                const e = await initRes.json();
                return alert('Validation Failed: ' + Object.values(e.errors)[0][0]);
            }
            if (!initRes.ok) return alert('Server error: ' + initRes.statusText);
            const initData = await initRes.json();
            if (!initData.success) return alert('Initialization failed.');
            const totalRows = initData.total_rows,
                filePath = initData.file_path,
                chunkSize = 100;
            let offset = 0,
                totalErrorsCount = 0;
            while (offset < totalRows) {
                statusText.innerText = `Processing ${offset} of ${totalRows} rows...`;
                const processRes = await fetch(`/forms/${formId}/bulk-upload-process`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        file_path: filePath,
                        offset: offset,
                        limit: chunkSize,
                        total_rows: totalRows
                    })
                }).then(r => r.json());
                offset += chunkSize;
                let pct = Math.min((offset / totalRows) * 100, 100);
                progressBar.style.width = `${pct}%`;
                progressBar.innerText = `${Math.round(pct)}%`;
                if (processRes.errors && processRes.errors.length > 0) {
                    totalErrorsCount += processRes.errors.length;
                    processRes.errors.forEach((errObj, index) => {
                        let uniqueId = `collapseErr_${offset}_${index}`;
                        let panelDiv = document.createElement('div');
                        panelDiv.className = 'panel panel-default border mb-3';
                        let headingDiv = document.createElement('div');
                        headingDiv.className = 'panel-heading1';
                        let h4 = document.createElement('h4');
                        h4.className = 'panel-title1';
                        let aTag = document.createElement('a');
                        aTag.className = 'accordion-toggle collapsed text-danger';
                        aTag.setAttribute('data-bs-toggle', 'collapse');
                        aTag.setAttribute('data-parent', '#upload-errors');
                        aTag.setAttribute('href', `#${uniqueId}`);
                        aTag.innerHTML = `<strong>${errObj.row}</strong>`;
                        h4.appendChild(aTag);
                        headingDiv.appendChild(h4);
                        let collapseDiv = document.createElement('div');
                        collapseDiv.id = uniqueId;
                        collapseDiv.className = 'panel-collapse collapse';
                        let bodyDiv = document.createElement('div');
                        bodyDiv.className = 'panel-body';
                        let ulList = document.createElement('ul');
                        ulList.className = 'list-group mb-0';
                        errObj.messages.forEach(msg => {
                            let li = document.createElement('li');
                            li.className = 'listunorder1 text-danger';
                            li.innerHTML = msg;
                            ulList.appendChild(li);
                        });
                        bodyDiv.appendChild(ulList);
                        collapseDiv.appendChild(bodyDiv);
                        panelDiv.appendChild(headingDiv);
                        panelDiv.appendChild(collapseDiv);
                        errorList.appendChild(panelDiv);
                    });
                }
            }
            statusText.innerText = '';
            progressBar.style.width = '100%';
            progressBar.innerText = '100%';
            let totalSuccessCount = Math.max(0, totalRows - totalErrorsCount);
            let alertClass = totalErrorsCount === 0 ? 'alert-success1' : (totalSuccessCount === 0 ? 'alert-danger1' :
                'alert-warning1');
            summaryBox.className = `alert1 ${alertClass} mt-3`;
            summaryBox.innerHTML =
                `<h4 class="mb-2">Upload Complete!</h4><span>Total Records Processed:</span> ${totalRows}<br><span class="text-success">Successfully Inserted:</span> ${totalSuccessCount}<br><span class="text-danger">Failed Records:</span> ${totalErrorsCount}`;
            summaryBox.style.display = 'block';
        }

        function openBulkOnboardForm(form_id, form_title) {
            $('.same_file_section').html(
                `<a href='/forms/${form_id}/export-template'><i class="fa-solid fa-download"></i> Download</a>`);
            $('.start_upload_btn_section').html(
                `<button class="btn btn-primary" onclick="startBulkUpload(${form_id})">Start Upload</button>`);
            $('#form_title').html(form_title);
            document.getElementById('bulk-csv').value = '';
            $('.file_field_lists').html('');
            $.ajax({
                url: `/fetch-form-file-fields/${form_id}`,
                beforeSend: function() {
                    $('.file_field_lists').html('');
                },
                success: function(response) {
                    let cols = response.excelColumns;
                    if (cols.length > 0) {
                        let html =
                            '<div class="alert alert-warning my-3"><strong>Please Note:</strong> The following columns are file upload fields and cannot be processed via bulk upload: <ul class="list-style2">';
                        cols.forEach(e => html += `<li>${e}</li>`);
                        html += '</ul></div>';
                        $('.file_field_lists').html(html);
                    }
                    const offcanvasEl = document.getElementById('globalOffcanvas');
                    (bootstrap.Offcanvas.getInstance(offcanvasEl) || new bootstrap.Offcanvas(offcanvasEl)).show
                        ();
                    const tbody = document.getElementById('instructions-tbody');
                    tbody.innerHTML = '';
                    response.columnDetails.forEach(col => {
                        let tr = document.createElement('tr');
                        tr.innerHTML =
                            `<td>${col.column_name}</td><td>${col.requirement}</td><td>${col.expected_values}</td>`;
                        tbody.appendChild(tr);
                    });
                }
            });
        }
        $(document).on('click', '#fbPagination .page-link', function() {
            const p = parseInt($(this).data('page'));
            if (!isNaN(p)) {
                currentPage = p;
                render();
            }
        });
    </script>

@endsection
