        (function() {
            const CFG = window.FB || {};
            const MODE = CFG.mode || 'create';
            const IS_EDIT = MODE === 'edit' || MODE === 'view';
            const READONLY = MODE === 'view';

            const PUBLIC_BASE = CFG.publicBase || '';
            const DASHBOARD_URL = CFG.dashboardUrl || '';
            const STORE_URL = CFG.storeUrl || '';
            const CSRF = CFG.csrf || '';
            const SAMPLE_UPLOAD_URL = CFG.sampleUploadUrl || '';
            const SAMPLE_DELETE_URL = CFG.sampleDeleteUrl || '';
            const FORM_STATUS_URL = CFG.formStatusUrl || '';
            const PHASES = CFG.phases || [];
            const ACTIVE_PHASE = CFG.activePhase || '';
            const FORM = CFG.form || null;

            let savedUrl = '',
                savedId = FORM ? FORM.id : null;

            const $ = s => document.querySelector(s);
            const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [c]));
            const slug = s => (s || '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') ||
                'untitled';
            const mq = window.matchMedia('(max-width:991.98px)');

            const ICONS = {
                arrowR: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 6l6 6l-6 6"/></svg>',
                arrowL: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12h-14"/><path d="M11 6l-6 6l6 6"/></svg>',
                move: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 9l-3 3l3 3"/><path d="M9 5l3 -3l3 3"/><path d="M15 19l-3 3l-3 -3"/><path d="M19 9l3 3l-3 3"/><path d="M2 12h20"/><path d="M12 2v20"/></svg>',
                up: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6 -6l6 6"/></svg>',
                down: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6l6 -6"/></svg>',
                dup: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0 -2 -2H6a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h2"/></svg>',
                edit: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/><path d="M20.4 6.6a2.1 2.1 0 0 0 -3 -3l-8.4 8.4v3h3z"/><path d="M16 5l3 3"/></svg>',
                trash: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3h6v3"/></svg>',
                field: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16"/><path d="M4 12h10"/><path d="M4 18h7"/></svg>'
            };
            const FIELDS = ['Text input', 'Text area', 'Number input', 'Email input', 'Phone input', 'Dropdown',
                'Radio buttons', 'Checkboxes', 'Date picker', 'Title', 'Description', 'File upload',
                'Download file', 'New line', 'Hidden field', 'Page break', 'State', 'City', 'State & city combined',
                'College', 'College & State', 'Address'
            ];
            const PLACEHOLDER_T = ['Text input', 'Text area', 'Number input', 'Email input', 'Phone input'];
            const OPTION_T = ['Dropdown', 'Radio buttons', 'Checkboxes'];
            const CHARS_T = ['Text input', 'Text area', 'Phone input'];
            const REQUIRE_T = [...PLACEHOLDER_T, ...OPTION_T, 'Date picker', 'File upload', 'State', 'City',
                'State & city combined', 'College', 'College & State', 'Address'
            ];
            const has = (a, t) => a.indexOf(t) >= 0;
            const SETTINGS = [
                ['anonymous', 'Anonymous', 'no'],
                ['url', 'Accessible using URL', 'no'],
                ['multi', 'Multiple submission', 'no'],
                ['login', 'Login required', 'yes'],
                ['edit', 'Edit response', 'no'],
                ['review', 'Review', 'no'],
                ['scoring', 'Scoring', 'no']
            ];

            const TYPE_MAP = {
                'Text input': 'text',
                'Text area': 'textarea',
                'Number input': 'number',
                'Email input': 'email',
                'Phone input': 'tel',
                'Dropdown': 'select',
                'Radio buttons': 'radio',
                'Checkboxes': 'checkbox',
                'Date picker': 'date',
                'Title': 'title',
                'Description': 'description',
                'File upload': 'file',
                'Download file': 'download_file',
                'New line': 'new_line',
                'Hidden field': 'hidden_field',
                'Page break': 'page_break',
                'State': 'selectState',
                'City': 'selectCity',
                'State & City': 'selectStateCity',
                'College': 'selectSDPCollege',
                'College & State': 'selectStateCollege',
                'Address': 'address'
            };

            function buildTextPattern(f) {
                let cls = 'A-Za-z';
                if (f.allowNumber) cls += '0-9';
                if (f.allowSpecial) cls += "@#$%&*()_+=.,:;!?/'\\-";
                if (f.allowSpace) return '^[' + cls + ' ]+$';
                return '^[' + cls + ']+(?: [' + cls + ']+)*$';
            }

            const params = new URLSearchParams(location.search);
            let formType = (FORM && FORM.form_type) || params.get('type') || 'survey';
            const EXCLUDE_BY_TYPE = {
                assessment_form: ['File upload', 'Download file', 'State', 'City', 'State & city combined',
                    'College', 'College & State'
                ]
            };
            const paletteFields = FIELDS.filter(name => !(EXCLUDE_BY_TYPE[formType] || []).includes(name));
            const paletteHTML = paletteFields.map(name =>
                `<div class="col"><button type="button" class="btn btn-light w-100 h-100 d-flex flex-column align-items-center gap-1 py-2 pal-item" data-field="${esc(name)}">${ICONS.field}<span class="small">${esc(name)}</span></button></div>`
            ).join('');
            $('#palette').innerHTML = paletteHTML;
            $('#paletteMobile').innerHTML = paletteHTML;

            $('#settingsList').innerHTML = SETTINGS.map(([k, l, d]) => `
  <div class="row align-items-center g-2 py-3 border-bottom">
    <div class="col"><span class="fw-semibold">${l}</span></div>
    <div class="col-auto"><div class="btn-group" role="group">
      <input type="radio" class="btn-check" name="set-${k}" id="${k}-yes" autocomplete="off" ${d==='yes'?'checked':''}><label class="btn btn-sm btn-outline-primary" for="${k}-yes">Yes</label>
      <input type="radio" class="btn-check" name="set-${k}" id="${k}-no" autocomplete="off" ${d==='no'?'checked':''}><label class="btn btn-sm btn-outline-primary" for="${k}-no">No</label>
    </div></div>
  </div>`).join('');

            const TYPE_LABELS = {
                registration: 'Registration form',
                survey: 'Survey form',
                assessment_form: 'Assessment form',
                assigment: 'Assignment form'
            };
            const typeLabel = t => TYPE_LABELS[t] || (String(t || 'Form').replace(/_/g, ' ').replace(/\b\w/g, c => c
                .toUpperCase()));

            const IS_REG = () => formType === 'registration' || !!(FORM && FORM.is_registration_form == 1);
            function applyType() {
                const isReg = IS_REG(),
                    label = typeLabel(formType);
                $('#studentTypeWrap').classList.toggle('d-none', !isReg);
                $('#regBox').classList.toggle('d-none', !isReg);
                $('#typeBadge').textContent = label;
                $('#smType').textContent = label;

                // Defaults for a NEW registration form only.
                // When editing, prefillFromForm() runs after this and puts back the saved values.
                if (isReg && !IS_EDIT) {
                    const loginNo = document.getElementById('login-no');
                    if (loginNo) loginNo.checked = true;

                    const urlYes = document.getElementById('url-yes');
                    if (urlYes) urlYes.checked = true;
                }
            }

            let pages = [],
                activePageId = null,
                selectedId = null,
                pageSeq = 0,
                fieldSeq = 0;
            const addPageObj = () => {
                const p = {
                    id: ++pageSeq,
                    label: '',
                    fields: []
                };
                pages.push(p);
                return p;
            };
            const activePage = () => pages.find(p => p.id === activePageId);
            const getField = id => activePage().fields.find(f => f.id === id);

            function newField(type) {
                const f = {
                    id: ++fieldSeq,
                    type,
                    label: type,
                    placeholder: '',
                    min: '',
                    max: '',
                    minVal: '',
                    maxVal: '',
                    required: false,
                    cssClass: '',
                    sendEmail: false,
                    allowSpecial: false,
                    allowNumber: false,
                    allowSpace: false,
                    allowDecimal: false,
                    limitByChar: false,
                    startDate: '',
                    endDate: '',
                    extension: '',
                    titleText: '',
                    descText: '',
                    hiddenValue: '',
                    conditions: [],
                    conditionLogic: 'all'
                };
                if (type === 'College & State') f.label = 'Select State';
                if (type === 'College') {
                    f.label = 'Select College';
                    f.phase = ACTIVE_PHASE || ((PHASES && PHASES.length) ? String(PHASES[0]) : '');
                }
                if (has(OPTION_T, type)) f.options = ['Option 1', 'Option 2', 'Option 3'];
                if (type === 'Address') f.address = {
                    current: {
                        state: true,
                        city: true,
                        pin: true
                    },
                    permanentEnabled: false,
                    permanent: {
                        state: false,
                        city: false,
                        pin: false
                    },
                    sameAsCurrent: true
                };
                return f;
            }

            function getPriorFields(currentId) {
                const list = [];
                let stop = false;
                pages.forEach(pg => pg.fields.forEach(f => {
                    if (f.id === currentId) {
                        stop = true;
                        return;
                    }
                    if (stop) return;
                    if (['Title', 'Description', 'New line', 'Page break', 'Hidden field', 'File upload',
                            'Address'
                        ].includes(f.type)) return;
                    list.push(f);
                }));
                return list;
            }

            function condFieldOptions(fieldId) {
                let opts = null;
                pages.forEach(pg => pg.fields.forEach(f => {
                    if (String(f.id) === String(fieldId) && OPTION_T.includes(f.type)) opts = (f.options ||
                        []).filter(o => String(o).trim() !== '');
                }));
                return opts;
            }

            function condValueControl(cn) {
                const opts = condFieldOptions(cn.fieldId);
                if (opts && opts.length && (cn.operator === 'in' || cn.operator === 'not_in')) {
                    const sel = String(cn.value || '').split(',').map(s => s.trim()).filter(Boolean);
                    return `<div class="o-cvmulti border rounded p-2">` + opts.map(o =>
                        `<div class="form-check"><input class="form-check-input o-cvchk" type="checkbox" value="${esc(o)}" ${sel.includes(o)?'checked':''}><label class="form-check-label">${esc(o)}</label></div>`
                    ).join('') + `</div>`;
                }
                if (opts && opts.length && (cn.operator === 'equals' || cn.operator === 'not_equals')) {
                    return `<select class="form-select form-select-sm o-cv"><option value="">Select value</option>` +
                        opts.map(o =>
                            `<option value="${esc(o)}"${String(cn.value)===String(o)?' selected':''}>${esc(o)}</option>`
                        ).join('') + `</select>`;
                }
                return `<input class="form-control form-control-sm o-cv" placeholder="Value" value="${esc(cn.value)}">`;
            }

            // These types have no label on the real form, so don't show one on the card either.
            function hideLabel(f) {
                return ['Title', 'Description', 'New line', 'Page break'].includes(f.type);
            }

            function fieldBody(f) {
                const ph = esc(f.placeholder || '');
                if (['Text input', 'Email input', 'Number input', 'Phone input', 'State', 'City'].includes(f.type))
                    return `<input class="form-control" disabled placeholder="${ph}">`;
                if (f.type === 'Text area')
                    return `<textarea class="form-control" rows="2" disabled placeholder="${ph}"></textarea>`;
                if (f.type === 'Dropdown')
                    return `<select class="form-select" disabled><option>${esc((f.options&&f.options[0])||'Select…')}</option></select>`;
                if (f.type === 'Radio buttons') return (f.options || []).map(o =>
                    `<div class="form-check"><input class="form-check-input" type="radio" disabled><label class="form-check-label">${esc(o)}</label></div>`
                ).join('');
                if (f.type === 'Checkboxes') return (f.options || []).map(o =>
                    `<div class="form-check"><input class="form-check-input" type="checkbox" disabled><label class="form-check-label">${esc(o)}</label></div>`
                ).join('');
                if (f.type === 'Date picker') return `<input type="date" class="form-control" disabled>`;
                if (f.type === 'File upload') return `<input type="file" class="form-control" disabled>`;
                if (f.type === 'State & city combined')
                    return `<div class="row g-2"><div class="col-6"><select class="form-select" disabled><option>State</option></select></div><div class="col-6"><select class="form-select" disabled><option>City</option></select></div></div>`;
                if (f.type === 'College & State')
                    return `<div class="row g-2"><div class="col-6"><select class="form-select" disabled><option>State</option></select></div><div class="col-6"><select class="form-select" disabled><option>College</option></select></div>${f.includeCollegeCity?`<div class="col-6"><select class="form-select" disabled><option>City</option></select></div>`:''}</div>`;
                if (f.type === 'College')
                    return `<select class="form-select" disabled><option>Select College${f.phase?` (Phase ${esc(f.phase)})`:''}</option></select>`;
                if (f.type === 'Download file') return f.sampleFile && f.sampleFile.url ?
                    `<a href="${esc(f.sampleFile.url)}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="fa fa-file-text-o me-1"></i> Download ${esc(f.sampleFile.name||'File')}</a>` :
                    `<span class="text-muted small"><i class="fa fa-upload me-1"></i> Upload a file in Field Options</span>`;
                if (f.type === 'Address') {
                    const a = f.address || {
                        current: {}
                    };
                    const p = c => ['state', 'city', 'pin'].filter(k => c && c[k]).join(', ') || '—';
                    return `<div class="small text-muted">Current: ${p(a.current)}${a.permanentEnabled?` · Permanent: ${p(a.permanent)}`:''}</div>`;
                }
                if (f.type === 'Title') return `<h5 class="mb-0">${esc(f.titleText||'Title')}</h5>`;
                if (f.type === 'Description')
                    return `<div class="text-muted mb-0">${f.descText||'Description text shown to the user.'}</div>`;
                if (f.type === 'New line') return `<hr class="my-0">`;
                if (f.type === 'Page break') return `<div class="text-center text-muted small">— Page break —</div>`;
                if (f.type === 'Hidden field') return `<span class="badge bg-light text-dark">Hidden field</span>`;
                return '';
            }

            function renderCanvas() {
                const p = activePage(),
                    c = $('#canvas'),
                    visible = p.fields.filter(f => !f.internal);
                if (!visible.length) {
                    c.innerHTML = READONLY ?
                        '<div class="text-center text-muted py-5 w-100">This page has no fields.</div>' :
                        '<div class="text-center text-muted py-5 w-100">This page is empty. Tap “Add field” (or use the panel) to add elements.</div>';
                    return;
                }
                c.innerHTML = visible.map(f => `
    <div class="card field-card ${f.id===selectedId?'selected':''} ${esc(f.cssClass)}" data-fid="${f.id}"><div class="card-body p-3">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-2">
        <span class="fw-semibold">${hideLabel(f)?'':`${esc(f.label)}${f.required?'<span class="req">(Required)</span>':''}${f.system?'<span class="badge bg-light text-muted mt-2 mt-md-0 ms-md-2 fw-normal">Default</span>':''}`}</span>
        ${READONLY?'':`<div class="field-tools d-flex gap-1">
          ${f.system?'':`<button class="btn btn-sm btn-link text-muted p-1 fld-move" title="Drag" data-act="move">${ICONS.move}</button>`}
          <button class="btn btn-sm btn-link text-muted p-1" title="Up" data-act="up">${ICONS.up}</button>
          <button class="btn btn-sm btn-link text-muted p-1" title="Down" data-act="down">${ICONS.down}</button>
          ${f.system?'':`<button class="btn btn-sm btn-link text-muted p-1" title="Duplicate" data-act="dup">${ICONS.dup}</button>`}
          <button class="btn btn-sm btn-link text-muted p-1" title="Edit" data-act="edit">${ICONS.edit}</button>
          ${f.system?'':`<button class="btn btn-sm btn-link text-danger p-1" title="Delete" data-act="del">${ICONS.trash}</button>`}
        </div>`}
      </div>
      ${fieldBody(f)}
    </div></div>`).join('');
            }

            function renderPageTabs() {
                const el = $('#pageTabs');
                if (el) el.innerHTML = '';
            }
            // Add page option disabled for now
            // function renderPageTabs(){
            //   $('#pageTabs').innerHTML = pages.map((p,i)=>`
        //     <button type="button" class="btn btn-sm ${p.id===activePageId?'btn-primary':'btn-outline-secondary'}" data-page="${p.id}">
        //       ${esc(p.label||('Page '+(i+1)))}${pages.length>1?` <span class="ms-1 pg-x" data-delpage="${p.id}">×</span>`:''}
        //     </button>`).join('') + `<button type="button" class="btn btn-sm btn-outline-primary ms-auto" id="addPage">+ Add page</button>`;
            // }
            function switchPage(id) {
                activePageId = id;
                selectedId = null;
                $('#pageLabel').value = activePage().label;
                renderPageTabs();
                renderCanvas();
            }

            function optionsHTML(f) {
                const ph = has(PLACEHOLDER_T, f.type),
                    ch = has(OPTION_T, f.type),
                    rq = has(REQUIRE_T, f.type),
                    chars = has(CHARS_T, f.type) || (f.type === 'Number input' && f.limitByChar),
                    val = f.type === 'Number input' && !f.limitByChar;
                // Title and Description have their own text box, so they don't need a Label.
                const noLabel = ['Title', 'Description'].includes(f.type);

                let h = `<p class="text-uppercase text-muted small fw-bold mb-3">Element settings</p>`;
                if (!noLabel) {
                    h += `<div class="mb-3"><label class="form-label fw-semibold">Label</label><input class="form-control o-label" value="${esc(f.label)}"></div>`;
                }
                if (f.type === 'Title') h +=
                    `<div class="mb-3"><label class="form-label fw-semibold">Title text</label><input class="form-control o-title" value="${esc(f.titleText)}"></div>`;
                if (f.type === 'Description') h +=
                    `<div class="mb-3"><label class="form-label fw-semibold">Description text</label><textarea class="form-control o-desc" rows="2">${esc(f.descText)}</textarea></div>`;
                if (f.type === 'Hidden field') h +=
                    `<div class="mb-3"><label class="form-label fw-semibold">Value</label><input class="form-control o-hidden" value="${esc(f.hiddenValue)}"></div>`;
                if (ph) h +=
                    `<div class="mb-3"><label class="form-label fw-semibold">Placeholder</label><input class="form-control o-ph" value="${esc(f.placeholder)}"></div>`;
                if (f.type === 'Number input') h +=
                    `<div class="form-check form-switch mb-3"><input class="form-check-input o-limitchar" type="checkbox" id="olc-${f.id}" ${f.limitByChar?'checked':''}><label class="form-check-label" for="olc-${f.id}">Validate by character length</label></div>`;
                if (chars) h +=
                    `<div class="row g-2 mb-3"><div class="col-6"><label class="form-label fw-semibold">Min characters</label><input type="number" min="0" class="form-control o-min" value="${esc(f.min)}"></div><div class="col-6"><label class="form-label fw-semibold">Max characters</label><input type="number" min="0" class="form-control o-max" value="${esc(f.max)}"></div></div>`;
                if (val) h +=
                    `<div class="row g-2 mb-3"><div class="col-6"><label class="form-label fw-semibold">Min value</label><input type="number" class="form-control o-minval" value="${esc(f.minVal)}"></div><div class="col-6"><label class="form-label fw-semibold">Max value</label><input type="number" class="form-control o-maxval" value="${esc(f.maxVal)}"></div></div>
    <div class="form-check mb-3"><input class="form-check-input o-decimal" type="checkbox" id="odec-${f.id}" ${f.allowDecimal?'checked':''}><label class="form-check-label" for="odec-${f.id}">Allow decimal values</label></div>`;
                if (f.type === 'Text input') h +=
                    `<div class="mb-3"><label class="form-label fw-semibold d-block mb-2">Allowed input</label>
    <div class="form-check form-check-inline"><input class="form-check-input o-sp" type="checkbox" id="osp-${f.id}" ${f.allowSpecial?'checked':''}><label class="form-check-label" for="osp-${f.id}">Special char</label></div>
    <div class="form-check form-check-inline"><input class="form-check-input o-num" type="checkbox" id="onum-${f.id}" ${f.allowNumber?'checked':''}><label class="form-check-label" for="onum-${f.id}">Numbers</label></div>
    <div class="form-check form-check-inline"><input class="form-check-input o-space" type="checkbox" id="ospc-${f.id}" ${f.allowSpace?'checked':''}><label class="form-check-label" for="ospc-${f.id}">Extra space</label></div></div>`;
                if (f.type === 'Date picker') h +=
                    `<div class="row g-2 mb-3"><div class="col-6"><label class="form-label fw-semibold">Start date</label><input type="date" class="form-control o-start" value="${esc(f.startDate)}"></div><div class="col-6"><label class="form-label fw-semibold">End date</label><input type="date" class="form-control o-end" value="${esc(f.endDate)}"></div></div>`;
                if (f.type === 'File upload') h +=
                    `<div class="mb-3"><label class="form-label fw-semibold">Extension allowed</label>
    <input class="form-control o-ext" placeholder="e.g. pdf, jpg, png, xlsx, docx, pptx" value="${esc(f.extension)}">
    <small class="form-text text-muted">Enter file extensions only (e.g. <b>xlsx</b>, not "excel"), separated by commas. The sample file must match one of these.</small></div>`;
                if (f.type === 'College') h +=
                    `<div class="mb-3"><label class="form-label fw-semibold">College Phase</label><select class="form-select o-phase"><option value="">Select</option>${(PHASES||[]).map(p=>`<option value="${esc(p)}"${String(f.phase)===String(p)?' selected':''}>${esc(p)}</option>`).join('')}</select></div>`;
                if (f.type === 'College & State') h +=
                    `<div class="form-check mb-3"><input class="form-check-input o-collegecity" type="checkbox" id="occ-${f.id}" ${f.includeCollegeCity?'checked':''}><label class="form-check-label" for="occ-${f.id}">Include College City</label></div>`;
                if (f.type === 'File upload' || f.type === 'Download file') h +=
                    `<div class="mb-3"><label class="form-label fw-semibold">Sample File ${f.type==='Download file'?'<span class="text-danger">*</span>':''} <span class="text-muted fw-normal">(shown as a download link on the form)</span></label>
    <input type="file" class="form-control o-samplefile${f.type==='Download file'&&!(f.sampleFile&&f.sampleFile.url)?' is-invalid':''}">
    ${f.type==='Download file'&&!(f.sampleFile&&f.sampleFile.url)?'<div class="invalid-feedback d-block">Please upload a file for this element.</div>':''}
    <small class="form-text text-muted d-block o-samplestatus">Max size: 10 MB.</small>
    ${f.sampleFile&&f.sampleFile.url?`<div class="mt-2"><div class="d-flex align-items-center border rounded px-2 py-1 bg-light"><i class="fa fa-file-text-o text-primary me-2"></i><a href="${esc(f.sampleFile.url)}" target="_blank" rel="noopener" class="text-truncate me-auto" style="max-width:75%">${esc(f.sampleFile.name||'Download sample')}</a><a href="javascript:void(0)" class="text-danger small ms-2 text-nowrap o-samplefile-remove"><i class="fa fa-times"></i> Remove</a></div></div>`:''}</div>`;
                if (ch) h +=
                    `<div class="mb-3"><label class="form-label fw-semibold">Options</label><div class="o-options">${(f.options||[]).map((o,i)=>`<div class="input-group input-group-sm mb-2"><input class="form-control o-opt" data-i="${i}" value="${esc(o)}"><button type="button" class="btn btn-outline-secondary o-optdel" data-i="${i}">${ICONS.trash}</button></div>`).join('')}</div><button type="button" class="btn btn-sm btn-light o-optadd">+ Add option</button></div>`;
                if (f.type === 'Address') {
                    const a = f.address;
                    h +=
                        `<div class="mb-2"><label class="form-label fw-semibold d-block">Current address</label>
    <div class="form-check form-check-inline"><input class="form-check-input o-acs" type="checkbox" ${a.current.state?'checked':''}><label class="form-check-label">State</label></div>
    <div class="form-check form-check-inline"><input class="form-check-input o-acc" type="checkbox" ${a.current.city?'checked':''}><label class="form-check-label">City</label></div>
    <div class="form-check form-check-inline"><input class="form-check-input o-acp" type="checkbox" ${a.current.pin?'checked':''}><label class="form-check-label">Pin</label></div></div>
    <div class="form-check mb-2"><input class="form-check-input o-aperm" type="checkbox" ${a.permanentEnabled?'checked':''}><label class="form-check-label">Include permanent address</label></div>
    ${a.permanentEnabled?`<div class="mb-2 border rounded p-2"><label class="form-label fw-semibold d-block">Permanent address</label>
                                                                                                            <div class="form-check form-check-inline"><input class="form-check-input o-aps" type="checkbox" ${a.permanent.state?'checked':''}><label class="form-check-label">State</label></div>
                                                                                                            <div class="form-check form-check-inline"><input class="form-check-input o-apc" type="checkbox" ${a.permanent.city?'checked':''}><label class="form-check-label">City</label></div>
                                                                                                            <div class="form-check form-check-inline"><input class="form-check-input o-app" type="checkbox" ${a.permanent.pin?'checked':''}><label class="form-check-label">Pin</label></div>
                                                                                                            <div class="form-check mt-1"><input class="form-check-input o-asame" type="checkbox" ${a.sameAsCurrent?'checked':''}><label class="form-check-label">Show "Same as current"</label></div></div>`:''}`;
                }
                if (rq) h +=
                    `<div class="form-check mb-3"><input class="form-check-input o-req" type="checkbox" id="oreq-${f.id}" ${f.required?'checked':''} ${f.system?'disabled':''}><label class="form-check-label" for="oreq-${f.id}">Required</label></div>`;
                const hasCond = f.conditions && f.conditions.length,
                    showCond = f.type !== 'Download file';
                if (showCond) h +=
                    `<div class="form-check form-switch border-top pt-3 mb-2"><input class="form-check-input o-condon me-1" type="checkbox" id="oc-${f.id}" ${hasCond?'checked':''}><label class="form-check-label fw-semibold" for="oc-${f.id}">Conditional logic</label></div>`;
                if (showCond && hasCond) {
                    const prior = getPriorFields(f.id);
                    const fOpts = sel => '<option value="">Select field</option>' + prior.map(pf =>
                        `<option value="${pf.id}"${String(pf.id)===String(sel)?' selected':''}>${esc(pf.label)}</option>`
                    ).join('');
                    const ops = [
                        ['equals', 'Equals'],
                        ['not_equals', 'Not equals'],
                        ['contains', 'Contains'],
                        ['not_contains', 'Not contains'],
                        ['greater_than', 'Greater than'],
                        ['less_than', 'Less than'],
                        ['in', 'Is any of'],
                        ['not_in', 'Is none of']
                    ];
                    const oOpts = sel => ops.map(([v, l]) =>
                        `<option value="${v}"${v===sel?' selected':''}>${l}</option>`).join('');
                    h += `<div class="mb-2"><select class="form-select form-select-sm o-condlogic"><option value="all"${f.conditionLogic!=='any'?' selected':''}>Match ALL</option><option value="any"${f.conditionLogic==='any'?' selected':''}>Match ANY</option></select></div>
    <div class="o-condrules">${f.conditions.map((cn,i)=>`<div class="border rounded p-2 mb-2 o-cond" data-i="${i}">
                                                                                                              <label class="form-label small text-muted mb-1">When field</label>
                                                                                                              <select class="form-select form-select-sm mb-2 o-cf">${fOpts(cn.fieldId)}</select>
                                                                                                              <label class="form-label small text-muted mb-1">Condition</label>
                                                                                                              <select class="form-select form-select-sm mb-2 o-co">${oOpts(cn.operator)}</select>
                                                                                                              <label class="form-label small text-muted mb-1">Value</label>
                                                                                                              <div class="mb-2">${condValueControl(cn)}</div>
                                                                                                              <button type="button" class="btn btn-sm btn-link text-danger p-0 o-cdel">Remove</button></div>`).join('')}</div>
    <button type="button" class="btn btn-sm btn-light o-condadd">+ Add condition</button>`;
                }
                h +=
                    `<div class="mb-3 mt-2"><label class="form-label fw-semibold">CSS class</label><input class="form-control o-css" value="${esc(f.cssClass)}"></div>
    ${f.type==='Download file'?'':`<div class="form-check mb-3"><input class="form-check-input o-mail" type="checkbox" id="omail-${f.id}" ${f.sendEmail?'checked':''}><label class="form-check-label" for="omail-${f.id}">Send email</label></div>`}
    ${f.system?'':`<div class="d-grid gap-2"><button type="button" class="btn btn-outline-primary o-dup">Duplicate element</button><button type="button" class="btn btn-outline-danger o-del">Remove element</button></div>`}`;
                return h;
            }

            function bindOptions(c, f) {
                const on = (s, e, fn) => {
                    const el = c.querySelector(s);
                    if (el) el.addEventListener(e, fn);
                };
                on('.o-label', 'input', e => {
                    f.label = e.target.value;
                    renderCanvas();
                });
                on('.o-title', 'input', e => {
                    f.titleText = e.target.value;
                    renderCanvas();
                });
                on('.o-desc', 'input', e => {
                    f.descText = e.target.value;
                    renderCanvas();
                });
                on('.o-hidden', 'input', e => {
                    f.hiddenValue = e.target.value;
                });
                on('.o-ph', 'input', e => {
                    f.placeholder = e.target.value;
                    renderCanvas();
                });
                on('.o-min', 'input', e => {
                    f.min = e.target.value;
                });
                on('.o-max', 'input', e => {
                    f.max = e.target.value;
                });
                on('.o-minval', 'input', e => {
                    f.minVal = e.target.value;
                });
                on('.o-maxval', 'input', e => {
                    f.maxVal = e.target.value;
                });
                on('.o-decimal', 'change', e => {
                    f.allowDecimal = e.target.checked;
                });
                on('.o-limitchar', 'change', e => {
                    f.limitByChar = e.target.checked;
                    renderOptionsInto(c, f);
                });
                on('.o-sp', 'change', e => {
                    f.allowSpecial = e.target.checked;
                });
                on('.o-num', 'change', e => {
                    f.allowNumber = e.target.checked;
                });
                on('.o-space', 'change', e => {
                    f.allowSpace = e.target.checked;
                });
                on('.o-start', 'change', e => {
                    f.startDate = e.target.value;
                });
                on('.o-end', 'change', e => {
                    f.endDate = e.target.value;
                });
                on('.o-ext', 'input', e => {
                    f.extension = e.target.value;
                });
                on('.o-collegecity', 'change', e => {
                    f.includeCollegeCity = e.target.checked;
                    renderCanvas();
                });
                on('.o-phase', 'change', e => {
                    f.phase = e.target.value;
                    renderCanvas();
                });
                on('.o-samplefile', 'change', async e => {
                    const file = e.target.files && e.target.files[0];
                    if (!file) return;
                    const statusEl = c.querySelector('.o-samplestatus');
                    const allowed = (f.extension || '').split(',').map(s => s.trim().toLowerCase()).filter(
                        Boolean);
                    const ext = (file.name.split('.').pop() || '').toLowerCase();
                    if (allowed.length && !allowed.includes(ext)) {
                        if (statusEl) statusEl.textContent = 'File must be of type: ' + allowed.join(', ');
                        e.target.value = '';
                        return;
                    }
                    if (file.size > 10 * 1024 * 1024) {
                        if (statusEl) statusEl.textContent = 'File is too large. Max size: 10 MB.';
                        e.target.value = '';
                        return;
                    }
                    if (statusEl) statusEl.textContent = 'Uploading…';
                    try {
                        const fd = new FormData();
                        fd.append('sample_file', file);
                        fd.append('extension_allowed', allowed.join(','));
                        const res = await fetch(SAMPLE_UPLOAD_URL, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': CSRF,
                                'Accept': 'application/json'
                            },
                            body: fd
                        });
                        const data = await res.json();
                        if (res.ok && data.status === 1) {
                            f.sampleFile = {
                                name: data.name,
                                path: data.path,
                                url: data.url
                            };
                            renderOptionsInto(c, f);
                        } else {
                            if (statusEl) statusEl.textContent = (data.errors && data.errors.sample_file &&
                                data.errors.sample_file[0]) || 'Upload failed';
                            e.target.value = '';
                        }
                    } catch (err) {
                        if (statusEl) statusEl.textContent = 'Upload failed';
                        e.target.value = '';
                    }
                });
                on('.o-samplefile-remove', 'click', async () => {
                    if (f.sampleFile && f.sampleFile.path) {
                        try {
                            const fd = new FormData();
                            fd.append('path', f.sampleFile.path);
                            fd.append('_token', CSRF);
                            await fetch(SAMPLE_DELETE_URL, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': CSRF
                                },
                                body: fd
                            });
                        } catch (err) {}
                    }
                    f.sampleFile = null;
                    renderOptionsInto(c, f);
                });
                on('.o-req', 'change', e => {
                    f.required = e.target.checked;
                    renderCanvas();
                });
                on('.o-css', 'input', e => {
                    f.cssClass = e.target.value;
                    renderCanvas();
                });
                on('.o-mail', 'change', e => {
                    f.sendEmail = e.target.checked;
                });
                if (f.address) {
                    on('.o-acs', 'change', e => {
                        f.address.current.state = e.target.checked;
                        renderCanvas();
                    });
                    on('.o-acc', 'change', e => {
                        f.address.current.city = e.target.checked;
                        renderCanvas();
                    });
                    on('.o-acp', 'change', e => {
                        f.address.current.pin = e.target.checked;
                        renderCanvas();
                    });
                    on('.o-aperm', 'change', e => {
                        f.address.permanentEnabled = e.target.checked;
                        const p = f.address.permanent;
                        // a permanent address with no fields is meaningless, so
                        // start it off matching the current address
                        if (e.target.checked && !p.state && !p.city && !p.pin) {
                            p.state = f.address.current.state;
                            p.city = f.address.current.city;
                            p.pin = f.address.current.pin;
                            if (!p.state && !p.city && !p.pin) p.state = p.city = p.pin = true;
                        }
                        renderCanvas();
                        renderOptionsInto(c, f);
                    });
                    // at least one permanent field must stay ticked
                    const keepOnePermanent = (key, el) => {
                        const p = f.address.permanent;
                        const next = { ...p, [key]: el.checked };
                        if (!next.state && !next.city && !next.pin) {
                            el.checked = true;
                            alert('Select at least one permanent address field, or turn off "Include permanent address".');
                            return;
                        }
                        p[key] = el.checked;
                        renderCanvas();
                    };
                    on('.o-aps', 'change', e => keepOnePermanent('state', e.target));
                    on('.o-apc', 'change', e => keepOnePermanent('city', e.target));
                    on('.o-app', 'change', e => keepOnePermanent('pin', e.target));
                    on('.o-asame', 'change', e => {
                        f.address.sameAsCurrent = e.target.checked;
                    });
                }
                on('.o-condon', 'change', e => {
                    if (e.target.checked) {
                        if (!f.conditions || !f.conditions.length) {
                            f.conditions = [{
                                fieldId: '',
                                operator: 'equals',
                                value: ''
                            }];
                            f.conditionLogic = 'all';
                        }
                    } else {
                        f.conditions = [];
                    }
                    renderOptionsInto(c, f);
                });
                on('.o-condlogic', 'change', e => {
                    f.conditionLogic = e.target.value;
                });
                on('.o-condadd', 'click', () => {
                    f.conditions.push({
                        fieldId: '',
                        operator: 'equals',
                        value: ''
                    });
                    renderOptionsInto(c, f);
                });
                c.querySelectorAll('.o-cond').forEach(row => {
                    const i = +row.dataset.i;
                    row.querySelector('.o-cf').addEventListener('change', e => {
                        f.conditions[i].fieldId = e.target.value;
                        f.conditions[i].value = '';
                        renderOptionsInto(c, f);
                    });
                    row.querySelector('.o-co').addEventListener('change', e => {
                        f.conditions[i].operator = e.target.value;
                        f.conditions[i].value = '';
                        renderOptionsInto(c, f);
                    });
                    const chks = row.querySelectorAll('.o-cvchk');
                    if (chks.length) {
                        chks.forEach(chk => chk.addEventListener('change', () => {
                            f.conditions[i].value = Array.from(chks).filter(x => x.checked).map(x =>
                                x.value).join(',');
                        }));
                    } else {
                        const cv = row.querySelector('.o-cv');
                        if (cv) {
                            const ev = cv.tagName === 'SELECT' ? 'change' : 'input';
                            cv.addEventListener(ev, e => {
                                f.conditions[i].value = e.target.value;
                            });
                        }
                    }
                    row.querySelector('.o-cdel').addEventListener('click', () => {
                        f.conditions.splice(i, 1);
                        renderOptionsInto(c, f);
                    });
                });
                on('.o-dup', 'click', () => duplicateField(f.id));
                on('.o-del', 'click', () => deleteField(f.id));
                c.querySelectorAll('.o-opt').forEach(i => i.addEventListener('input', e => {
                    f.options[+e.target.dataset.i] = e.target.value;
                    renderCanvas();
                }));
                c.querySelectorAll('.o-optdel').forEach(b => b.addEventListener('click', e => {
                    f.options.splice(+e.currentTarget.dataset.i, 1);
                    renderCanvas();
                    renderOptionsInto(c, f);
                }));
                on('.o-optadd', 'click', () => {
                    f.options.push('Option ' + (f.options.length + 1));
                    renderCanvas();
                    renderOptionsInto(c, f);
                });
            }

            function killEditors(c) {
                if (!window.tinymce) return;
                const list = (typeof tinymce.get === 'function' ? tinymce.get() : null) || tinymce.editors || [];
                Array.from(list).forEach(ed => {
                    const el = ed && ed.getElement && ed.getElement();
                    if (el && c.contains(el)) ed.remove();
                });
            }
            function initDescEditor(c, f) {
                if (f.type !== 'Description' || !window.tinymce) return;
                const ta = c.querySelector('.o-desc');
                if (!ta) return;
                tinymce.init({
                    target: ta,
                    menubar: false,
                    branding: false,
                    height: 400,
                    ui_mode: 'split',
                    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
                    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
                    setup: ed => ed.on('Change KeyUp SetContent', () => {
                        f.descText = ed.getContent();
                        renderCanvas();
                    })
                });
            }
            // Draws the "Field options" panel for one field.
            // In view mode we only show the values, so everything is disabled.
            function renderOptionsInto(c, f) {
                killEditors(c);
                c.innerHTML = optionsHTML(f);

                if (READONLY) {
                    // Disable every control so nothing can be changed.
                    c.querySelectorAll('input, select, button').forEach(el => el.disabled = true);
                    c.querySelectorAll('textarea:not(.o-desc)').forEach(el => el.disabled = true);

                    // Remove the buttons that add or delete things.
                    const removeThese = '.o-dup, .o-del, .o-optadd, .o-optdel, .o-condadd, .o-cdel, .o-samplefile, .o-samplefile-remove';
                    c.querySelectorAll(removeThese).forEach(el => el.remove());

                    initDescEditor(c, f);
                    return;
                }

                bindOptions(c, f);
                initDescEditor(c, f);
            }

            function openOptions(id) {
                selectedId = id;
                renderCanvas();
                const f = getField(id);
                if (mq.matches) {
                    renderOptionsInto($('#optsCanvasBody'), f);
                    bootstrap.Offcanvas.getOrCreateInstance($('#optsCanvas')).show();
                } else {
                    renderOptionsInto($('#sideOptionsBody'), f);
                    switchPaletteTab('options');
                }
            }

            function addField(type) {
                const f = newField(type);
                const p = activePage();
                const si = selectedId != null ? p.fields.findIndex(x => x.id === selectedId) : -1;
                if (si >= 0) p.fields.splice(si + 1, 0, f);
                else p.fields.push(f);
                if (mq.matches) {
                    selectedId = f.id;
                    renderCanvas();
                    bootstrap.Offcanvas.getOrCreateInstance($('#paletteCanvas')).hide();
                } else openOptions(f.id);
                requestAnimationFrame(() => {
                    const el = $('#canvas').querySelector(`.field-card[data-fid="${f.id}"]`);
                    if (el) el.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                });
            }

            function duplicateField(id) {
                const p = activePage(),
                    i = p.fields.findIndex(f => f.id === id);
                if (i < 0 || p.fields[i].system) return;
                const copy = JSON.parse(JSON.stringify(p.fields[i]));
                copy.id = ++fieldSeq;
                delete copy.savedName;
                delete copy.savedName2;
                delete copy.savedName3;
                p.fields.splice(i + 1, 0, copy);
                openOptions(copy.id);
            }

            function deleteField(id) {
                const p = activePage(),
                    t = p.fields.find(f => f.id === id);
                if (t && t.system) return;
                p.fields = p.fields.filter(f => f.id !== id);
                if (selectedId === id) selectedId = null;
                renderCanvas();
                if (mq.matches) {
                    const oc = bootstrap.Offcanvas.getInstance($('#optsCanvas'));
                    if (oc) oc.hide();
                } else {
                    $('#sideOptionsBody').innerHTML =
                        '<p class="text-muted small mb-0">Select a field on the canvas to edit its options.</p>';
                    switchPaletteTab('fields');
                }
            }

            function moveField(id, dir) {
                const p = activePage(),
                    i = p.fields.findIndex(f => f.id === id),
                    j = i + dir;
                if (j < 0 || j >= p.fields.length) return;
                [p.fields[i], p.fields[j]] = [p.fields[j], p.fields[i]];
                renderCanvas();
            }

            $('#canvas').addEventListener('click', e => {
                const block = e.target.closest('.field-card');
                if (!block) return;
                const fid = +block.dataset.fid,
                    btn = e.target.closest('[data-act]');

                // View mode: clicking a field just shows its options.
                if (READONLY) {
                    openOptions(fid);
                    return;
                }

                if (btn) {
                    e.stopPropagation();
                    const a = btn.dataset.act;
                    if (a === 'up') moveField(fid, -1);
                    else if (a === 'down') moveField(fid, 1);
                    else if (a === 'dup') duplicateField(fid);
                    else if (a === 'del') deleteField(fid);
                    else if (a === 'edit') openOptions(fid);
                } else openOptions(fid);
            });
            document.addEventListener('click', e => {
                if (READONLY) return; // no adding fields in view mode
                const it = e.target.closest('.pal-item');
                if (it) addField(it.dataset.field);
            });
            $('#pageTabs').addEventListener('click', e => {
                const del = e.target.closest('[data-delpage]');
                if (del) {
                    e.stopPropagation();
                    const id = +del.dataset.delpage,
                        p = pages.find(x => x.id === id);
                    if (p.fields.length && !confirm('Delete this page and its ' + p.fields.length +
                            ' field(s)?')) return;
                    pages = pages.filter(x => x.id !== id);
                    if (activePageId === id) activePageId = pages[0].id;
                    switchPage(activePageId);
                    return;
                }
                if (e.target.id === 'addPage') {
                    const np = addPageObj();
                    activePageId = np.id;
                    selectedId = null;
                    $('#pageLabel').value = '';
                    renderPageTabs();
                    renderCanvas();
                    $('#pageLabel').focus();
                    return;
                }
                const tab = e.target.closest('[data-page]');
                if (tab) switchPage(+tab.dataset.page);
            });
            $('#pageLabel').addEventListener('input', e => {
                activePage().label = e.target.value;
                renderPageTabs();
            });

            function switchPaletteTab(name) {
                if (name !== 'options') {
                    const ob = $('#sideOptionsBody');
                    if (ob) killEditors(ob);
                }
                document.querySelectorAll('[data-pbody]').forEach(p => p.classList.toggle('d-none', p.dataset.pbody !==
                    name));
                const r = document.getElementById(name === 'options' ? 'ptab-options' : 'ptab-fields');
                if (r) r.checked = true;
                if (name === 'options' && selectedId != null) {
                    const f = getField(selectedId),
                        ob = $('#sideOptionsBody');
                    if (f && ob) initDescEditor(ob, f);
                }
            }
            document.querySelectorAll('input[name="ptab"]').forEach(r => r.addEventListener('change', () =>
                switchPaletteTab(r.id === 'ptab-options' ? 'options' : 'fields')));
            if (window.Sortable && !READONLY) {
                Sortable.create($('#canvas'), {
                    handle: '.fld-move',
                    animation: 150,
                    onEnd: () => {
                        const ids = [...$('#canvas').querySelectorAll('.field-card')].map(n => +n.dataset
                            .fid);
                        activePage().fields.sort((a, b) => ids.indexOf(a.id) - ids.indexOf(b.id));
                    }
                });
            }

            /* serialize to form_structure */
            function buildFormStructure() {
                const out = [];
                const counts = {};
                let uid = 0;
                const idName = {};
                const usedNames = new Set();
                pages.forEach(pg => pg.fields.forEach(f => {
                    [f.savedName, f.savedName2, f.sysName].forEach(n => {
                        if (n) usedNames.add(n);
                    });
                }));
                const nm = (code) => {
                    counts[code] = (counts[code] || 0);
                    let n = code + counts[code];
                    while (usedNames.has(n)) {
                        counts[code]++;
                        n = code + counts[code];
                    }
                    counts[code]++;
                    usedNames.add(n);
                    return n;
                };
                const uniqueId = () => 'element_' + Date.now() + '_' + (uid++);
                const applyCond = (el, f) => {
                    if (f.conditions && f.conditions.length) {
                        const cs = f.conditions.map(cn => ({
                            field: idName[cn.fieldId],
                            operator: cn.operator,
                            value: cn.value
                        })).filter(c => c.field && c.value !== '');
                        if (cs.length) {
                            el.conditions = cs;
                            el.conditionLogic = f.conditionLogic || 'all';
                        }
                    }
                };
                pages.forEach((pg, pi) => {
                    if (pi > 0) out.push({
                        id: uniqueId(),
                        type: 'page_break',
                        label: 'Field',
                        name: nm('page_break'),
                        required: false,
                        cssClass: ''
                    });
                    pg.fields.forEach(f => {
                        const type = TYPE_MAP[f.type] || 'text';
                        if (f.type === 'State & city combined') {
                            const cityId = uniqueId();
                            const bn = f.savedName || nm('selectStateCity');
                            idName[f.id] = bn;
                            const stEl = {
                                id: uniqueId(),
                                type: 'selectStateCity',
                                label: f.label || 'Select State',
                                name: bn,
                                fieldType: 'state',
                                target_elemnt: cityId,
                                required: !!f.required,
                                cssClass: f.cssClass || ''
                            };
                            applyCond(stEl, f);
                            out.push(stEl);
                            out.push({
                                id: cityId,
                                type: 'selectStateCity',
                                label: 'Select City',
                                name: f.savedName2 || (bn + '_city'),
                                fieldType: 'city',
                                target_elemnt: 0,
                                required: !!f.required,
                                cssClass: ''
                            });
                            return;
                        }
                        if (f.type === 'College & State') {
                            const clgId = uniqueId();
                            const cityId = uniqueId();
                            const bn = f.savedName || nm('selectStateCollege');
                            idName[f.id] = bn;
                            const stEl = {
                                id: uniqueId(),
                                type: 'selectStateCollege',
                                label: f.label || 'Select State',
                                name: bn,
                                fieldType: 'state',
                                target_elemnt: clgId,
                                required: !!f.required,
                                cssClass: f.cssClass || ''
                            };
                            applyCond(stEl, f);
                            out.push(stEl);
                            out.push({
                                id: clgId,
                                type: 'selectStateCollege',
                                label: 'Select College',
                                name: f.savedName2 || (bn + '_college'),
                                fieldType: 'college',
                                // when city is included, the college drives the city dropdown
                                target_elemnt: f.includeCollegeCity ? cityId : 0,
                                required: !!f.required,
                                cssClass: ''
                            });
                            if (f.includeCollegeCity) {
                                out.push({
                                    id: cityId,
                                    type: 'selectStateCollege',
                                    label: 'Select City',
                                    name: f.savedName3 || (bn + '_city'),
                                    fieldType: 'city',
                                    target_elemnt: 0,
                                    required: !!f.required,
                                    cssClass: ''
                                });
                            }
                            return;
                        }
                        if (f.type === 'Address') {
                            const el = {
                                id: uniqueId(),
                                type: 'address',
                                label: f.label || 'Address',
                                name: f.savedName || nm('address'),
                                required: !!f.required,
                                cssClass: f.cssClass || '',
                                sendEmail: !!f.sendEmail,
                                address: f.address
                            };
                            idName[f.id] = el.name;
                            applyCond(el, f);
                            out.push(el);
                            return;
                        }
                        const el = {
                            id: uniqueId(),
                            type,
                            label: f.label || f.type,
                            name: f.savedName || nm(type),
                            required: !!f.required,
                            cssClass: f.cssClass || '',
                            sendEmail: !!f.sendEmail
                        };
                        if (['text', 'textarea', 'email', 'tel', 'number'].includes(type)) el
                            .placeholder = f.placeholder || '';
                        if (['text', 'textarea', 'tel'].includes(type)) {
                            el.minLength = f.min || '';
                            el.maxLength = f.max || '';
                        }
                        if (type === 'text') {
                            el.allowSpecial = !!f.allowSpecial;
                            el.allowNumber = !!f.allowNumber;
                            el.allowSpace = !!f.allowSpace;
                            el.pattern = buildTextPattern(f);
                        }
                        if (type === 'number') {
                            el.limitByChar = !!f.limitByChar;
                            el.allowDecimal = !!f.allowDecimal;
                            if (f.limitByChar) {
                                el.minLength = f.min || '';
                                el.maxLength = f.max || '';
                            } else {
                                el.minValue = f.minVal || '';
                                el.maxValue = f.maxVal || '';
                            }
                        }
                        if (['select', 'radio', 'checkbox'].includes(type)) el.options = (f.options ||
                        []).filter(o => String(o).trim() !== '');
                        if (type === 'date') {
                            el.start_date = f.startDate || '';
                            el.end_date = f.endDate || '';
                        }
                        if (type === 'file') {
                            el.extensionRequired = f.extension || '';
                            if (f.sampleFile && f.sampleFile.url) el.sampleFile = f.sampleFile;
                        }
                        if (type === 'download_file') {
                            if (f.sampleFile && f.sampleFile.url) el.sampleFile = f.sampleFile;
                        }
                        if (type === 'title') el.title = f.titleText || '';
                        if (type === 'description') el.description = f.descText || '';
                        if (type === 'hidden_field') el.hiddenValue = f.hiddenValue || '';
                        if (type === 'selectState') {
                            el.fieldType = 'state';
                            el.target_elemnt = 0;
                        }
                        if (type === 'selectCity') {
                            el.fieldType = 'city';
                            el.target_elemnt = 0;
                        }
                        if (type === 'selectSDPCollege') {
                            el.name = 'sdp_college';
                            el.phase = f.phase || '';
                            el.options = ['Option 1', 'Option 2'];
                        }
                        if (f.system) {
                            el.name = f.sysName;
                            el.system = true;
                            if (f.sysPattern) el.pattern = f.sysPattern;
                            if (f.internal) el.internal = true;
                        }
                        idName[f.id] = el.name;
                        applyCond(el, f);
                        out.push(el);
                    });
                });
                return out;
            }
            const setYN = k => {
                const el = document.getElementById(k + '-yes');
                return el && el.checked ? 1 : 0;
            };

            let scoringRows = [];
            function renderScoringParams() {
                const box = $('#scoringParams');
                if (!box) return;
                box.innerHTML = scoringRows.map((r, i) => `
      <div class="row g-2 mb-2 align-items-end sc-row" data-i="${i}">
        <div class="col-12 col-sm-5"><label class="form-label small text-muted mb-1">Parameter</label>
          <input type="text" class="form-control form-control-sm sc-name" placeholder="Enter score parameter" value="${esc(r.parameter)}"></div>
        <div class="col-8 col-sm-4"><label class="form-label small text-muted mb-1">Weightage</label>
          <input type="number" min="0" step="0.5" class="form-control form-control-sm sc-weight" placeholder="Enter weightage" value="${esc(r.weightage)}"></div>
        <div class="col-4 col-sm-3"><button type="button" class="btn btn-sm btn-outline-danger w-100 sc-del">Remove</button></div>
      </div>`).join('');
                box.querySelectorAll('.sc-row').forEach(row => {
                    const i = +row.dataset.i;
                    row.querySelector('.sc-name').addEventListener('input', e => {
                        scoringRows[i].parameter = e.target.value;
                        e.target.classList.remove('is-invalid');
                    });
                    row.querySelector('.sc-weight').addEventListener('input', e => {
                        scoringRows[i].weightage = e.target.value;
                        e.target.classList.remove('is-invalid');
                    });
                    row.querySelector('.sc-del').addEventListener('click', () => {
                        scoringRows.splice(i, 1);
                        if (!scoringRows.length) {
                            const n = document.getElementById('scoring-no');
                            if (n) n.checked = true;
                        }
                        syncScoringBox();
                    });
                });
            }
            function syncScoringBox() {
                const on = setYN('scoring') === 1;
                if (on && !scoringRows.length) scoringRows = [{ parameter: '', weightage: '' }];
                if (!on) scoringRows = [];
                const box = $('#scoringParamsBox');
                if (box) box.classList.toggle('d-none', !on);
                renderScoringParams();
            }
            // a Download file element is pointless without its file
            function validateSampleFiles() {
                let missing = null;
                pages.forEach(pg => pg.fields.forEach(f => {
                    if (!missing && f.type === 'Download file' && !(f.sampleFile && f.sampleFile.url)) {
                        missing = f;
                    }
                }));
                if (!missing) return true;

                alert(`Please upload a file for the "${missing.label || 'Download file'}" field, or remove it.`);
                goStep(2);
                openOptions(missing.id);
                return false;
            }

            function validateScoring() {
                if (setYN('scoring') !== 1) return true;
                let ok = true;
                $('#scoringParams').querySelectorAll('.sc-row').forEach(row => {
                    const n = row.querySelector('.sc-name'), w = row.querySelector('.sc-weight');
                    if (!n.value.trim()) { n.classList.add('is-invalid'); ok = false; }
                    if (w.value === '' || +w.value < 0) { w.classList.add('is-invalid'); ok = false; }
                });
                return ok;
            }
            document.querySelectorAll('input[name="set-scoring"]').forEach(r => r.addEventListener('change',
                syncScoringBox));
            if ($('#addScoringRow')) $('#addScoringRow').addEventListener('click', () => {
                scoringRows.push({ parameter: '', weightage: '' });
                renderScoringParams();
            });
            const mapRedirect = () => (($('#redirect') && $('#redirect').value) || '').toLowerCase().includes(
                'custom') ? 'custom' : 'same_page';
            const rand5 = () => Math.random().toString(36).slice(2, 7);
            async function publish() {
                const isReg = IS_REG();
                const rmethod = mapRedirect();
                const payload = {
                    title: $('#formTitle').value.trim(),
                    slug: FORM ? FORM.slug : (slug($('#formTitle').value) + '-' + rand5()),
                    form_structure: JSON.stringify(buildFormStructure()),
                    is_public: 1,
                    form_type: formType,
                    is_registration_form: isReg ? 1 : 0,
                    student_type_builder: isReg ? 20 : '',
                    student_type_builder_display: isReg ? (($('#studentType') && $('#studentType').value.trim()) || '') : '',
                    isAnonymous: setYN('anonymous'),
                    accessible_using_url: setYN('url'),
                    multi_submission: setYN('multi'),
                    login_required: setYN('login'),
                    edit_response: setYN('edit'),
                    scoring: setYN('scoring'),
                    review: setYN('review'),
                    isDynamicUrl: $('#dynamicUrl') && $('#dynamicUrl').checked ? 1 : 0,
                    allowed_old_phase: $('#oldPhase') && $('#oldPhase').checked ? 1 : 0,
                    redirect_method: rmethod,
                    success_message: $('#msg').value,
                    submit_btn_txt: $('#submitText').value,
                    redirect_url: rmethod === 'custom' ? ((FORM && FORM.redirect_url) || '') : '',
                    parameter_name: scoringRows.map(r => r.parameter),
                    weightage: scoringRows.map(r => r.weightage)
                };
                const res = await fetch(STORE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    },
                    body: JSON.stringify(payload)
                });
                return res.json();
            }

            /* wizard nav */
            let step = 1;
            const STEP_LABELS = ['Details', 'Builder', 'Settings', 'Finish'];

            function renderFooter() {
                const f = $('#wizFoot');
                if (step === 4) {
                    f.innerHTML = '';
                    return;
                }
                const back = step > 1 ?
                    `<button class="btn btn-light" type="button" data-nav="back">${ICONS.arrowL} Back</button>` : '';

                // View mode: only Back / Next, no save button.
                if (READONLY) {
                    const nextBtn = step < 3 ?
                        `<button class="btn btn-primary" type="button" data-nav="next">Next: ${STEP_LABELS[step]} ${ICONS.arrowR}</button>` : '';
                    f.innerHTML =
                        `<button class="btn btn-outline-secondary" type="button" data-nav="cancel">Back to dashboard</button>
    <div class="d-flex gap-2 flex-wrap ms-auto">${back}${nextBtn}</div>`;
                    return;
                }

                // Create mode says "Create form", edit mode says "Update form".
                const saveText = IS_EDIT ? 'Update form' : 'Create form';
                const next = step === 3 ? saveText : 'Next: ' + STEP_LABELS[step];
                f.innerHTML =
                    `<button class="btn btn-outline-danger" type="button" data-nav="cancel">Cancel</button>
    <div class="d-flex gap-2 flex-wrap ms-auto">${back}<button class="btn btn-primary" type="button" data-nav="next">${next} ${ICONS.arrowR}</button></div>`;
            }

            function goStep(n) {
                step = n;
                const ob = $('#sideOptionsBody');
                if (ob && n !== 2) killEditors(ob);
                if (ob && n === 2 && selectedId != null && !ob.classList.contains('d-none')) {
                    const f = getField(selectedId);
                    if (f) initDescEditor(ob, f);
                }
                document.querySelectorAll('.wiz-step').forEach(s => s.classList.toggle('d-none', +s.dataset.step !==
                    n));
                document.querySelectorAll('.stepper-desktop .step').forEach(s => {
                    const i = +s.dataset.step,
                        num = s.querySelector('.step-num');
                    num.className =
                        'step-num rounded-circle d-inline-flex align-items-center justify-content-center ' + (
                            i < n ? 'is-done' : i === n ? 'is-active' : 'is-todo');
                    num.textContent = i < n ? '✓' : i;
                    s.classList.toggle('active', i === n);
                    s.classList.toggle('done', i < n);
                });
                $('#smLabel').textContent = `Step ${n} of 4 · ${STEP_LABELS[n-1]}`;
                $('#smBar').style.width = (n / 4 * 100) + '%';
                // Heading text depends on which mode we are in.
                let heading = 'Form Builder';
                if (READONLY) heading = 'View Form';
                else if (IS_EDIT) heading = (n === 4) ? 'Form updated' : 'Edit Form';
                else if (n === 4) heading = 'Form created';
                $('#pageTitle').textContent = heading;
                if (n === 4) {
                    const t = $('#formTitle').value.trim() || 'Untitled';
                    $('#finishName').textContent = '"' + t + '"';
                    $('#shareLink').value = savedUrl || '';
                    $('#previewLink').href = savedUrl || '#';
                }
                renderFooter();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }

            function validateStep1() {
                let ok = true;
                const title = $('#formTitle');
                if (!title.value.trim()) {
                    title.classList.add('is-invalid');
                    ok = false;
                } else title.classList.remove('is-invalid');
                if (formType === 'registration') {
                    const st = $('#studentType');
                    if (!st.value.trim()) {
                        st.classList.add('is-invalid');
                        ok = false;
                    } else st.classList.remove('is-invalid');
                }
                return ok;
            }
            $('#wizFoot').addEventListener('click', e => {
                const nav = e.target.closest('[data-nav]');
                if (!nav) return;
                if (nav.dataset.nav === 'cancel') {
                    location.href = DASHBOARD_URL;
                    return;
                }
                if (nav.dataset.nav === 'back') {
                    goStep(step - 1);
                    return;
                }
                if (nav.dataset.nav === 'next') {
                    if (step === 1 && !validateStep1()) return;
                    if (step === 2 && !validateSampleFiles()) return;
                    if (step === 3) {
                        const total = pages.reduce((s, p) => s + p.fields.length, 0);
                        if (!total) {
                            alert('Add at least one field before creating the form.');
                            return;
                        }
                        if (!validateScoring()) return;
                        if (!validateSampleFiles()) return;
                        const orig = nav.innerHTML;
                        nav.disabled = true;
                        nav.textContent = 'Saving…';
                        publish().then(r => {
                            nav.disabled = false;
                            nav.innerHTML = orig;
                            if (r && r.status === 1) {
                                savedUrl = r.url || '';
                                savedId = r.id || null;
                                goStep(4);
                            } else alert((r && r.message) || 'Could not save the form.');
                        }).catch(() => {
                            nav.disabled = false;
                            nav.innerHTML = orig;
                            alert('Could not save the form.');
                        });
                        return;
                    }
                    goStep(step + 1);
                }
            });

            $('#formTitle').addEventListener('input', e => {
                $('#titleCount').textContent = e.target.value.length;
                $('#publicUrl').textContent = PUBLIC_BASE + (e.target.value.trim() ? slug(e.target.value) : '');
                if (e.target.value.trim()) e.target.classList.remove('is-invalid');
            });
            const stEl = $('#studentType');
            if (stEl) stEl.addEventListener('input', e => {
                if (e.target.value.trim()) e.target.classList.remove('is-invalid');
            });
            $('#copyShare').addEventListener('click', e => {
                const v = $('#shareLink').value;
                if (navigator.clipboard) navigator.clipboard.writeText(v).then(() => {
                    const o = e.target.textContent;
                    e.target.textContent = 'Copied!';
                    setTimeout(() => e.target.textContent = o, 1500);
                });
            });
            $('#createAnother').addEventListener('click', () => location.reload());
            $('#publishNow').addEventListener('click', async e => {
                if (!savedId) {
                    alert('Form not saved yet.');
                    return;
                }
                const btn = e.currentTarget,
                    orig = btn.innerHTML;
                btn.disabled = true;
                btn.textContent = 'Publishing…';
                try {
                    const fd = new FormData();
                    fd.append('_token', CSRF);
                    fd.append('form_id', savedId);
                    fd.append('active', 1);
                    const res = await fetch(FORM_STATUS_URL, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json'
                        },
                        body: fd
                    });
                    const data = await res.json();
                    if (res.ok && data && data.status === 'success') {
                        const badge = $('#publishBadge');
                        if (badge) {
                            badge.textContent = 'Published';
                            badge.className = 'badge bg-success ms-1';
                        }
                        btn.className = 'btn btn-success w-100';
                        btn.innerHTML = 'Published';
                    } else {
                        btn.disabled = false;
                        btn.innerHTML = orig;
                        alert((data && data.status) || 'Could not publish the form.');
                    }
                } catch (err) {
                    btn.disabled = false;
                    btn.innerHTML = orig;
                    alert('Could not publish the form.');
                }
            });

            function sysField(over) {
                const f = newField(over.type);
                Object.assign(f, over);
                return f;
            }

            function seedRegistrationFields() {
                return [
                    sysField({
                        type: 'Text input',
                        label: 'Student Name',
                        required: true,
                        system: true,
                        sysName: 'student_name',
                        sysPattern: '^[A-Za-z]+(?: [A-Za-z]+)*$'
                    }),
                    sysField({
                        type: 'Email input',
                        label: 'Email',
                        required: true,
                        system: true,
                        sysName: 'email',
                        sysPattern: '^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$'
                    }),
                    sysField({
                        type: 'Phone input',
                        label: 'Mobile',
                        required: true,
                        system: true,
                        sysName: 'mobile',
                        sysPattern: '^[0-9]{10}$'
                    }),
                    sysField({
                        type: 'Radio buttons',
                        label: 'Gender',
                        required: true,
                        system: true,
                        sysName: 'gender',
                        options: ['Male', 'Female', 'Other']
                    }),
                    sysField({
                        type: 'Hidden field',
                        label: 'Phase',
                        system: true,
                        internal: true,
                        sysName: 'phase'
                    }),
                    sysField({
                        type: 'Hidden field',
                        label: 'Profile Updated',
                        system: true,
                        internal: true,
                        sysName: 'profile_updated'
                    }),
                    sysField({
                        type: 'Hidden field',
                        label: 'Password Updated',
                        system: true,
                        internal: true,
                        sysName: 'password_updated'
                    })
                ];
            }
            const REVERSE_TYPE = {
                text: 'Text input', textarea: 'Text area', number: 'Number input', email: 'Email input',
                tel: 'Phone input', select: 'Dropdown', radio: 'Radio buttons', checkbox: 'Checkboxes',
                date: 'Date picker', title: 'Title', description: 'Description', file: 'File upload',
                download_file: 'Download file', new_line: 'New line', hidden_field: 'Hidden field',
                page_break: 'Page break', selectState: 'State', selectCity: 'City',
                selectStateCity: 'State & city combined', selectSDPCollege: 'College',
                selectStateCollege: 'College & State', address: 'Address'
            };
            function deserialize(structure) {
                const nameToId = {};
                let pg = addPageObj();
                activePageId = pg.id;
                const applyCondBack = (f, el) => {
                    if (Array.isArray(el.conditions) && el.conditions.length) {
                        f.conditions = el.conditions.map(c => ({
                            fieldId: nameToId[c.field] || '',
                            operator: c.operator || 'equals',
                            value: c.value != null ? String(c.value) : ''
                        })).filter(c => c.fieldId);
                        f.conditionLogic = el.conditionLogic || 'all';
                    }
                };
                for (let i = 0; i < structure.length; i++) {
                    const el = structure[i] || {};
                    // keep the page break as a normal card, otherwise the fields
                    // after it would land on a page the builder cannot switch to
                    if (el.type === 'page_break') {
                        const pb = newField('Page break');
                        pb.label = el.label || 'Page break';
                        pb.savedName = el.name || '';
                        pg.fields.push(pb);
                        continue;
                    }
                    if (el.type === 'selectStateCity' && el.fieldType === 'state') {
                        const f = newField('State & city combined');
                        f.label = el.label || 'Select State'; f.required = !!el.required; f.cssClass = el.cssClass || '';
                        f.savedName = el.name || '';
                        if (structure[i + 1] && structure[i + 1].type === 'selectStateCity') f.savedName2 = structure[i + 1].name || '';
                        nameToId[el.name] = f.id; applyCondBack(f, el); pg.fields.push(f);
                        if (structure[i + 1] && structure[i + 1].type === 'selectStateCity') i++;
                        continue;
                    }
                    if (el.type === 'selectStateCollege' && el.fieldType === 'state') {
                        const f = newField('College & State');
                        f.label = el.label || 'Select State'; f.required = !!el.required; f.cssClass = el.cssClass || '';
                        f.savedName = el.name || '';
                        const college = structure[i + 1];
                        const city = structure[i + 2];
                        if (college && college.type === 'selectStateCollege' && college.fieldType === 'college') {
                            f.savedName2 = college.name || '';
                            i++;
                            if (city && city.type === 'selectStateCollege' && city.fieldType === 'city') {
                                f.includeCollegeCity = true;
                                f.savedName3 = city.name || '';
                                i++;
                            }
                        }
                        nameToId[el.name] = f.id; applyCondBack(f, el); pg.fields.push(f);
                        continue;
                    }
                    const wtype = REVERSE_TYPE[el.type] || 'Text input';
                    const f = newField(wtype);
                    f.label = el.label || wtype;
                    f.required = !!el.required;
                    f.cssClass = el.cssClass || '';
                    f.sendEmail = !!el.sendEmail;
                    f.savedName = el.name || '';
                    if (el.system) { f.system = true; f.sysName = el.name; if (el.pattern) f.sysPattern = el.pattern; if (el.internal) f.internal = true; }
                    if (['Text input', 'Text area', 'Number input', 'Email input', 'Phone input'].includes(wtype)) f.placeholder = el.placeholder || '';
                    if (['Text input', 'Text area', 'Phone input'].includes(wtype)) { f.min = el.minLength || ''; f.max = el.maxLength || ''; }
                    if (wtype === 'Text input') { f.allowSpecial = !!el.allowSpecial; f.allowNumber = !!el.allowNumber; f.allowSpace = !!el.allowSpace; }
                    if (wtype === 'Number input') { f.limitByChar = !!el.limitByChar; f.allowDecimal = !!el.allowDecimal; if (el.limitByChar) { f.min = el.minLength || ''; f.max = el.maxLength || ''; } else { f.minVal = el.minValue || ''; f.maxVal = el.maxValue || ''; } }
                    if (['Dropdown', 'Radio buttons', 'Checkboxes'].includes(wtype)) f.options = Array.isArray(el.options) ? el.options.slice() : [];
                    if (wtype === 'Date picker') { f.startDate = el.start_date || ''; f.endDate = el.end_date || ''; }
                    if (wtype === 'File upload') { f.extension = el.extensionRequired || ''; if (el.sampleFile) f.sampleFile = el.sampleFile; }
                    if (wtype === 'Download file') { if (el.sampleFile) f.sampleFile = el.sampleFile; }
                    if (wtype === 'Title') f.titleText = el.title || '';
                    if (wtype === 'Description') f.descText = el.description || '';
                    if (wtype === 'Hidden field') f.hiddenValue = el.hiddenValue || '';
                    if (wtype === 'College') f.phase = el.phase || '';
                    if (wtype === 'Address' && el.address) f.address = el.address;
                    nameToId[el.name] = f.id;
                    applyCondBack(f, el);
                    pg.fields.push(f);
                }
                if (!pages.length) addPageObj();
                activePageId = pages[0].id;
            }
            function prefillFromForm() {
                $('#formTitle').value = FORM.title || '';
                $('#titleCount').textContent = (FORM.title || '').length;
                $('#publicUrl').textContent = PUBLIC_BASE + (FORM.slug || '');
                if ($('#studentType')) $('#studentType').value = FORM.student_type_name || '';
                const setSw = (id, val) => { const el = $('#' + id); if (el) el.checked = !!val; };
                setSw('oldPhase', FORM.allowed_old_phase);
                setSw('dynamicUrl', FORM.is_dynamic_url);
                const setYNval = (k, val) => { const y = $('#' + k + '-yes'), n = $('#' + k + '-no'); if (y && n) { y.checked = !!val; n.checked = !val; } };
                setYNval('anonymous', FORM.isAnonymous);
                setYNval('url', FORM.accessible_using_url);
                setYNval('multi', FORM.multi_submission);
                setYNval('login', FORM.login_required);
                setYNval('edit', FORM.edit_response);
                setYNval('scoring', FORM.scoring);
                setYNval('review', FORM.review);
                scoringRows = (FORM.parameters || []).map(p => ({
                    parameter: p.parameter ?? '',
                    weightage: p.weightage ?? ''
                }));
                syncScoringBox();
                const rd = $('#redirect'); if (rd) rd.value = FORM.redirect_method === 'custom' ? 'Custom URL' : 'Same page';
                const msg = $('#msg'); if (msg) msg.value = FORM.success_message || '';
                const sb = $('#submitText'); if (sb) sb.value = FORM.submit_btn_txt || '';
            }
            function lockReadonly() {
                document.querySelectorAll(
                        '.wiz-step[data-step="1"] input, .wiz-step[data-step="1"] textarea, .wiz-step[data-step="1"] select, .wiz-step[data-step="3"] input, .wiz-step[data-step="3"] textarea, .wiz-step[data-step="3"] select')
                    .forEach(el => {
                        el.disabled = true;
                    });
            }
            applyType();
            if (IS_EDIT && FORM) {
                prefillFromForm();
                deserialize(FORM.structure || []);
            } else {
                const first = addPageObj();
                activePageId = first.id;
                if (formType === 'registration') first.fields.push(...seedRegistrationFields());
            }
            renderPageTabs();
            $('#pageLabel').value = '';
            renderCanvas();
            if (READONLY) lockReadonly();
            goStep(1);
        })();
