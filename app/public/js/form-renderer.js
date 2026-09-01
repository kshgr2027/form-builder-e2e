    // Copies one current-address field into the matching permanent one.
    function copyAddressField(name, k) {
        const src = document.getElementById(`${name}_curr_${k}`);
        const dst = document.getElementById(`${name}_perm_${k}`);
        if (!src || !dst) return;

        // A dropdown can only hold a value that exists in its list, so add it if missing.
        if (dst.tagName === 'SELECT' && src.value && !Array.from(dst.options).some(o => o.value === src.value)) {
            const opt = document.createElement('option');
            opt.value = src.value;
            opt.textContent = src.options[src.selectedIndex] ? src.options[src.selectedIndex].text : src.value;
            dst.appendChild(opt);
        }

        let v = src.value;

        // Respect the field's own max length (e.g. Pin Code is 6 digits).
        const maxLen = parseInt(dst.getAttribute('data-maxlen') || '0', 10);
        if (maxLen && v.length > maxLen) v = v.slice(0, maxLen);

        dst.value = v;

        // the value was set by script, so clear any stale "required / invalid" state
        dst.setCustomValidity('');
        setFieldMessage(dst, true, '');
    }

    // Copies the whole permanent address. Order matters: the state must finish
    // reloading its city list before the city value is set, or it gets wiped.
    async function syncPermanentAddress(name) {
        copyAddressField(name, 'state');

        const permState = document.getElementById(`${name}_perm_state`);
        const permCity = document.getElementById(`${name}_perm_city`);

        if (permState && permCity && typeof window.updateCity === 'function') {
            try {
                await window.updateCity(permState, `${name}_perm_city`);
            } catch (err) { /* leave the list as-is if the lookup fails */ }
        }

        copyAddressField(name, 'city');
        copyAddressField(name, 'pin');
    }

    window.toggleSameAsCurrent = async function (name, checked) {
        if (checked) await syncPermanentAddress(name);

        ['state', 'city', 'pin'].forEach(function (k) {
            const dst = document.getElementById(`${name}_perm_${k}`);
            if (!dst) return;

            if (checked) {
                // readOnly stops typing; selects ignore it, so also block pointer events
                dst.readOnly = true;
                dst.style.pointerEvents = 'none';
                dst.style.opacity = '0.6';
                dst.tabIndex = -1;
            } else {
                // unchecked: editable again, and clear the copied value
                dst.readOnly = false;
                dst.style.pointerEvents = '';
                dst.style.opacity = '';
                dst.tabIndex = 0;
                dst.value = '';
                dst.setCustomValidity('');
                setFieldMessage(dst, true, '');
            }
        });
    };

    // keep syncing while ticked, so ticking before typing also works
    document.addEventListener('change', function (e) {
        const el = e.target;
        if (!el || !el.id) return;

        const m = el.id.match(/^(.*)_curr_(state|city|pin)$/);
        if (!m) return;

        const box = document.getElementById(`${m[1]}_same_as_current`);
        if (box && box.checked) syncPermanentAddress(m[1]);
    });

    document.addEventListener('input', function (e) {
        const el = e.target;
        if (!el || !el.id) return;

        const m = el.id.match(/^(.*)_curr_(pin)$/);
        if (!m) return;

        const box = document.getElementById(`${m[1]}_same_as_current`);
        if (box && box.checked) copyAddressField(m[1], 'pin');
    });

    document.addEventListener('input', function (e) {
        const el = e.target;
        if (!el || !el.classList || !el.classList.contains('js-decimal-2')) return;

        const value = el.value;
        if (value === '' || /^-?\d*(\.\d{0,2})?$/.test(value)) {
            el.setCustomValidity('');
        } else {
            el.setCustomValidity('Please enter a number with up to 2 decimal places (e.g. 42.44).');
        }
    });

    function setFieldMessage(el, valid, msg) {
        const errorDiv = document.getElementById(`error_${el.name.replace('[]', '')}`);
        if (!errorDiv) return;
        el.classList.toggle('is-invalid', !valid);
        errorDiv.textContent = valid ? '' : msg;
        errorDiv.style.display = valid ? '' : 'block';
    }

    function fieldHasValue(input) {
        if (input.type === 'checkbox' || input.type === 'radio') {
            return document.querySelectorAll(`[name="${input.name}"]:checked`).length > 0;
        }
        if (input.type === 'file') {
            return input.files && input.files.length > 0;
        }
        return input.value.trim() !== '';
    }

    function checkRequired(el) {
        if (el.disabled) return;

        if (el.type === 'checkbox' || el.type === 'radio') {
            const container = document.getElementById(`container_${el.name.replace('[]', '')}`);
            if (!container || container.getAttribute('data-required') !== '1') return;
            setFieldMessage(el, fieldHasValue(el), 'This field is required');
            return;
        }

        if (el.hasAttribute('required') && !fieldHasValue(el)) {
            setFieldMessage(el, false, 'This field is required');
            return;
        }

        // keep showing the field's own error (wrong length, bad email, ...)
        // instead of clearing it just because something was typed
        if (el.value !== '' && el.checkValidity && !el.checkValidity()) {
            setFieldMessage(el, false, el.validationMessage || el.getAttribute('data-allowmsg') || 'Please enter a valid value.');
            return;
        }

        if (!el.hasAttribute('required')) return;
        setFieldMessage(el, true, '');
    }

    document.addEventListener('input', function (e) {
        const el = e.target;
        if (!el || !el.getAttribute) return;

        const allowed = el.getAttribute('data-allowchars');
        if (allowed) {
            const cleaned = el.value.replace(new RegExp('[^' + allowed + ']', 'g'), '');
            const msg = el.getAttribute('data-allowmsg') || 'Some characters are not allowed in this field.';
            if (cleaned !== el.value) {
                const pos = el.selectionStart - (el.value.length - cleaned.length);
                el.value = cleaned;
                try { el.setSelectionRange(pos, pos); } catch (err) {}
                el.setCustomValidity(msg);
                setFieldMessage(el, false, msg);
            } else {
                const exactLen = el.getAttribute('data-exactlen');
                const valid = !exactLen || el.value === '' || el.value.length === parseInt(exactLen);
                // setCustomValidity is what actually blocks submit
                el.setCustomValidity(valid ? '' : msg);
                setFieldMessage(el, valid, msg);
            }
            return;
        }

      if (el.getAttribute('data-emailcheck')) {
            const valid = el.value === '' || /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(el.value);
            setFieldMessage(el, valid, el.getAttribute('data-allowmsg'));
            return;
        }

        if (el.getAttribute('data-numbercheck')) {
            let valid = true;
            if (el.value !== '') {
                const num = parseFloat(el.value);
                if (el.min !== '' && num < parseFloat(el.min)) valid = false;
                if (el.max !== '' && num > parseFloat(el.max)) valid = false;
            }
            setFieldMessage(el, valid, el.getAttribute('data-allowmsg'));
            return;
        }

        if (el.getAttribute('data-numlencheck')) {
            const maxLen = parseInt(el.getAttribute('data-maxlen') || '0', 10);
            const minLen = parseInt(el.getAttribute('data-minlen') || '0', 10);
            if (maxLen && el.value.length > maxLen) {
                el.value = el.value.slice(0, maxLen);
            }
            let valid = true;
            if (el.value !== '') {
                if (minLen && el.value.length < minLen) valid = false;
                if (maxLen && el.value.length > maxLen) valid = false;
            }
            const msg = el.getAttribute('data-allowmsg') || 'Invalid length.';
            el.setCustomValidity(valid ? '' : msg);
            setFieldMessage(el, valid, msg);
            return;
        }
    });

    document.addEventListener('change', function (e) {
        const el = e.target;
        if (!el || !el.getAttribute) return;

        if (el.getAttribute('data-datecheck')) {
            let valid = true;
            if (el.value) {
                const start = el.getAttribute('data-start_date');
                const end = el.getAttribute('data-end_date');
                if (start && el.value < start) valid = false;
                if (end && el.value > end) valid = false;
            }
            setFieldMessage(el, valid, el.getAttribute('data-allowmsg'));
        }

        if (el.classList && el.classList.contains('file-check')) {
            const allowed = el.getAttribute('data-extensionrequired');
            if (allowed && el.files && el.files.length) {
                const fileExt = el.files[0].name.split('.').pop().toLowerCase();
                const allowedList = allowed.split(',').map(ext => ext.trim().toLowerCase());
                if (!allowedList.includes(fileExt)) {
                    setFieldMessage(el, false, `Invalid file type: .${fileExt}. Allowed: ${allowedList.join(', ')}`);
                    el.value = '';
                    return;
                }
            }
            setFieldMessage(el, true, '');
        }

        if (el.matches && el.matches('input, select, textarea')) {
            if (el.type === 'email' || el.type === 'tel') {
        el.setAttribute('data-validate', 'true'); 
    }
            checkRequired(el);
        }
    });

    document.addEventListener('blur', function (e) {
        const el = e.target;
       if (el.matches && el.matches('input, select, textarea')) {
    if (el.type === 'checkbox') {
        // Find all checkboxes in the form sharing the same base array name
        const groupName = el.name;
        const checkboxGroup = document.querySelectorAll(`input[name="${groupName}"]`);
        
        // Pass the first checkbox of the group to trigger validation for the group container
        if (checkboxGroup.length > 0) {
            checkRequired(checkboxGroup[0]);
        }
    } else {
        checkRequired(el);
    }
}
    }, true);

    window.renderForm = async function(formStructure) {
        window.divElementID = 'formRenderer';
        const formRenderer = document.getElementById('formRenderer');
        if (!formRenderer) return;
        
        formRenderer.innerHTML = '';

        for (const element of formStructure) {
            const fieldHtml = await renderFormField(element);
            // formRenderer.innerHTML += fieldHtml;
        }

        // moveSubmitToEnd();
        setupConditionalLogic(formStructure);
    };

    // page breaks add extra cards, so keep the submit button below the last one
    function moveSubmitToEnd() {
        const btn = document.getElementById('submitBtn');
        if (!btn) return;

        const wrapper = btn.parentElement;
        const cards = document.querySelectorAll('#formSubmission .card');
        if (!cards.length) return;

        const lastBody = cards[cards.length - 1].querySelector('.card-body');
        if (lastBody && !lastBody.contains(btn)) lastBody.appendChild(wrapper);
    }

    async function renderFormField(element) {
        // const isInitiallyVisible = !element.conditional || isConditionMet(element.conditional, formStructure);
        // const containerStyle = isInitiallyVisible ? '' : ' style="display:none"';
        // const maybeDisabled  = isInitiallyVisible ? '' : ' disabled';

        let formRenderer = document.getElementById(`${divElementID}`);
        const requiredStar = element.required ? '<span class="text-danger">*</span>' : '';
        const requiredAttr = element.required ? ' required' : '';
        // this is appended inside an existing class="...", so it must be a bare class name
        const classAttr = element.cssClass ? ` ${element.cssClass}` : '';
        const fieldId = `${element.id}`;
        // const minLengthAttr = element.minLength ? `minLength="${element.minLength}"` : '';
        // const maxLengthAttr = element.maxLength ? `maxLength="${element.maxLength}"` : '';
        const minLengthAttr = element.minLength ? `minlength="${element.minLength}"` : '';
        const maxLengthAttr = element.type === 'email' ? '' :
            element.type === 'tel' ? `maxlength="${element.maxLength || 10}"` :
            element.maxLength ? `maxlength="${element.maxLength}"` : '';
        let patternAttr = '';
        if (element.type === 'email') {
            patternAttr = ` pattern="[a-zA-Z0-9._%+\\-]+@[a-zA-Z0-9.\\-]+\\.[a-zA-Z]{2,}" title="Please enter a valid email address (e.g. name@example.com)"`;
        } else if (element.pattern) {
            patternAttr = ` pattern="${element.pattern}" title="${element.patternTitle || 'Please match the requested format'}"`;
        }

        let allowCharsAttr = '';
        if (element.type === 'text') {
            let cls = 'A-Za-z ';
            if (element.allowNumber)  cls += '0-9';
            if (element.allowSpecial) cls += "@#$%&*()_+=.,:;!?/'\\-";

            let msgParts = ['letters'];
            if (element.allowNumber)  msgParts.push('numbers');
            if (element.allowSpecial) msgParts.push('special characters');
            const allowMsg = 'Only ' + msgParts.join(', ').replace(/, ([^,]*)$/, ' and $1') + ' are allowed in this field.';

            allowCharsAttr = ` data-allowchars="${cls}" data-allowmsg="${allowMsg}"`;
        } else if (element.type === 'tel') {
            allowCharsAttr = ` data-allowchars="0-9" data-exactlen="10" data-allowmsg="Mobile number must be exactly 10 digits."`;
        } else if (element.type === 'email') {
            allowCharsAttr = ` data-emailcheck="1" data-allowmsg="Please enter a valid email address (e.g. name@example.com)."`;
        } else if (element.type === 'number') {
            if (element.limitByChar) {
                const minL = element.minLength, maxL = element.maxLength;
                const lenMsg = (minL && maxL)
                    ? (String(minL) === String(maxL) ? `Must be exactly ${minL} digits.` : `Must be between ${minL} and ${maxL} digits.`)
                    : minL ? `Must be at least ${minL} digits.`
                    : maxL ? `Must be at most ${maxL} digits.`
                    : 'Please enter a valid number.';
                allowCharsAttr = ` data-numlencheck="1"${minL ? ` data-minlen="${minL}"` : ''}${maxL ? ` data-maxlen="${maxL}"` : ''} data-allowmsg="${lenMsg}"`;
            } else {
                const rangeMsg = element.minValue && element.maxValue ? `Value must be between ${element.minValue} and ${element.maxValue}.` :
                    element.minValue ? `Value must be at least ${element.minValue}.` :
                    element.maxValue ? `Value must be at most ${element.maxValue}.` :
                    'Please enter a valid number.';
                allowCharsAttr = ` data-numbercheck="1" data-allowmsg="${rangeMsg}"`;
            }
        }
        const targetElementId = element.target_elemnt ? `${element.target_elemnt}` : 0;

        
        // const minValueAttr = element.minValue ? `min="${element.minValue}" step="0.01"` : '';
        const minValueAttr = element.minValue ? `min="${element.minValue}"` : '';
        const maxValueAttr = element.maxValue ? `max="${element.maxValue}"` : '';
       
        const allowDecimal = element.type === 'number' && element.allowDecimal;
        const stepAttr = allowDecimal ? `step="any"` : '';
        const decimalClass = allowDecimal ? ' js-decimal-2' : '';

        let html = ``;
console.log("Current Field Type from Server:", element.type);
        switch (element.type) {
            case 'text':
    html += `<div class="col-12 col-md-6 mb-3" id="container_${element.name}">`;
    html += `<label class="form-label">${element.label} ${requiredStar}</label>`;
    html += `<input type="text" class="form-control${classAttr}" id="${fieldId}" name="${element.name}" placeholder="${element.placeholder || ''}" value="${element.value || ''}"${requiredAttr}>`;
    html += `<div class="invalid-feedback" id="error_${element.name}"></div>`;
    html += '</div>';
    formRenderer.innerHTML += html;
    break;
    case 'email':
    // Directly inject structural validation bypassing missing element props
    var forcedEmailStar = '<span class="text-danger">*</span>';
    html += `<div class="col-12 col-md-6 mb-3" id="container_${element.name}">`;
    html += `<label class="form-label">${element.label} ${forcedEmailStar}</label>`;
    html += `<input type="email" class="form-control check-required${classAttr}" id="${fieldId}" name="${element.name}" placeholder="${element.placeholder || ''}" value="${element.value || ''}" required data-required="1" data-emailcheck="1" data-allowchars="A-Za-z0-9._%+\\-@" data-allowmsg="Please enter a valid email address (e.g. name@example.com).">`;
    html += `<div class="invalid-feedback" id="error_${element.name}"></div>`;
    html += '</div>';
    formRenderer.innerHTML += html;
    break;

case 'tel':
    // Directly inject structural validation bypassing missing element props
    var forcedTelStar = '<span class="text-danger">*</span>';
    html += `<div class="col-12 col-md-6 mb-3" id="container_${element.name}">`;
    html += `<label class="form-label">${element.label} ${forcedTelStar}</label>`;
    html += `<input type="tel" class="form-control check-required${classAttr}" id="${fieldId}" name="${element.name}" placeholder="${element.placeholder || ''}" value="${element.value || ''}" required data-required="1" data-allowchars="0-9" data-exactlen="10" data-allowmsg="Mobile number must be exactly 10 digits.">`;
    html += `<div class="invalid-feedback" id="error_${element.name}"></div>`;
    html += '</div>';
    formRenderer.innerHTML += html;
    break;
            case 'number':
    html += `<div class="col-12 col-md-6 mb-3">
                <div class="field-card" id="container_${element.name}">`;

    let className = element.type == 'tel' ? "mobileNumber" : "";

    html += `
        <label for="${fieldId}" class="form-label">
            ${element.label} ${requiredStar}
        </label>

        <input
            type="${element.type}"
            class="form-control ${className} ${classAttr} ${decimalClass}"
            id="${fieldId}"
            name="${element.name}"
            placeholder="${element.placeholder || ''}"
            ${requiredAttr}
            ${minLengthAttr}
            ${maxLengthAttr}
            ${patternAttr}
            ${allowCharsAttr}
            ${minValueAttr}
            ${maxValueAttr}
            ${stepAttr}
        >

        <div class="invalid-feedback" id="error_${element.name}"></div>
    `;

    html += `
                </div>
            </div>`;

    formRenderer.innerHTML += html;
    break;

           case 'title':
    html += `
        <div class="col-12 mb-3${classAttr}">
            <div class="field-card" id="container_${element.name}">
                <h3 class="form-title">${element.title}</h3>
            </div>
        </div>
    `;

    formRenderer.innerHTML += html;
    break;

            case 'description':
    html += `
        <div class="col-12 mb-3${classAttr}">
            <div class="field-card" id="container_${element.name}">
                <div class="form-subtitle rich-text">${element.description}</div>
            </div>
        </div>`;
    html += `<style>.rich-text ul{list-style:disc;padding-left:1.5rem}.rich-text ol{list-style:decimal;padding-left:1.5rem}.rich-text li{display:list-item}</style>`;
    html += `
    `;

    formRenderer.innerHTML += html;
    break;

            case 'new_line':
                // full width so it breaks the row, with a visible line
                html += `<div class="col-12"><hr class="my-3"></div>`;
                formRenderer.innerHTML += html;
                break;

            case 'page_break': {
                formRenderer.innerHTML += html;

                const newDivId = `formRenderer${Math.floor(Math.random() * 10000)}`;
                // keep the same markup as the first card so both look identical
                const cardHtml = `<div class="card"><div class="card-body"><div id="${newDivId}" class="row"></div></div></div>`;

                // put the new card right after the current one, not at the end of the page
                const currentCard = formRenderer.closest('.card') || formRenderer.parentElement;
                currentCard.insertAdjacentHTML('afterend', cardHtml);

                window.divElementID = newDivId;
                formRenderer = document.getElementById(newDivId);
                break;
            }

            case 'date':
                html += `<div class="col-12 col-md-6 mb-3" id="container_${element.name}">`;
                html += `
                    <label for="${fieldId}" class="form-label">${element.label} ${requiredStar}</label>
                    <input type="${element.type}" class="form-control form-control${classAttr}" id="${fieldId}" name="${element.name}" 
                        placeholder="${element.placeholder || ''}"${requiredAttr} ${minLengthAttr}
                        ${maxLengthAttr} ${patternAttr} data-datecheck="1" data-start_date="${element.start_date || ''}" data-end_date="${element.end_date || ''}" data-allowmsg="Please select a date within the allowed range.">
                    <div class="invalid-feedback" id="error_${element.name}"></div>
                `;
                html += '</div>';
                formRenderer.innerHTML += html;
                break;

            case 'textarea':
                html += `<div class="col-12 mb-3" id="container_${element.name}">`;
                html += `
                    <label for="${fieldId}" class="form-label">${element.label} ${requiredStar}</label>
                    <textarea class="form-control form-control${classAttr}" id="${fieldId}" name="${element.name}" 
                            rows="${element.rows || 4}" placeholder="${element.placeholder || ''}"${requiredAttr} ${minLengthAttr} ${maxLengthAttr}></textarea>
                    <div class="invalid-feedback" id="error_${element.name}"></div>
                `;
                html += '</div>';
                formRenderer.innerHTML += html;
                break;
case 'select':
    html += `<div class="col-12 col-md-6 mb-3">
                <div class="field-card" id="container_${element.name}">`;

    let options = '';
    if (element.options) {
        element.options.forEach(option => {
            options += `<option value="${option.replace(/"/g, '&quot;')}">${option}</option>`;
        });
    }

    html += `
        <label for="${fieldId}" class="form-label">${element.label} ${requiredStar}</label>

        <select class="form-select${classAttr}" id="${fieldId}" name="${element.name}" ${requiredAttr}>
            <option value="">Select an option</option>
            ${options}
        </select>

        <div class="invalid-feedback" id="error_${element.name}"></div>
    `;

    html += `</div></div>`;

    formRenderer.innerHTML += html;
    break;

          case 'selectSDPCollege':

    let optionArrClg = [];

    html += `<div class="col-12 mb-3">
                <div class="field-card" id="container_${element.name}">`;

    let optionsselectCollege = '';

    const response = await fetch(`/get-college-details-by-type?phase=${element.phase}`);
    const data = await response.json();

    data.forEach(value => {
        optionArrClg[value.id] = value.name;
    });

    element.options = optionArrClg;

    element.options.forEach((key, value) => {
        optionsselectCollege += `<option value="${value}">${key}</option>`;
    });

    html += `
        <label for="${fieldId}" class="form-label">${element.label} ${requiredStar}</label>

        <select class="form-select${classAttr}" id="${fieldId}" name="${element.name}" ${requiredAttr}>
            <option value="">Select</option>
            ${optionsselectCollege}
        </select>

        <div class="invalid-feedback" id="error_${element.name}"></div>
    `;

    html += `</div></div>`;

    formRenderer.innerHTML += html;

    break;
            case 'nsti_trade':
            case 'iti_trade':
                html += `<div class="col-12 col-md-6 mb-3" id="container_${element.name}">`;
                let optionsselectTrade = '';
                let dropdown_options = [];

                if (element.type == "nsti_trade") {
                    const response = await fetch(`/get-trade-details-by-type?type=nsti`);
                    const data = await response.json();
                    data.forEach(value => {
                        dropdown_options[value.id] = value.name;
                    });
                    element.options = dropdown_options;
                } else if (element.type == "iti_trade") {
                    const response = await fetch(`/get-trade-details-by-type?type=iti`);
                    const data = await response.json();
                    data.forEach(value => {
                        dropdown_options[value.id] = value.name;
                    });
                    element.options = dropdown_options;
                }

                if (element.options) {
                    element.options.forEach((key, value) => {
                        optionsselectTrade += `<option value="${value}">${key}</option>`;
                    });
                }

                html += `
                    <label for="${fieldId}" class="form-label">${element.label} ${requiredStar}</label>
                    <select class="form-select${classAttr}" id="${fieldId}" name="${element.name}"${requiredAttr}>
                        <option value="">Select</option>
                        ${optionsselectTrade}
                    </select>
                    <div class="invalid-feedback" id="error_${element.name}"></div>
                `;
                html += '</div>';
                formRenderer.innerHTML += html;
                break;

            case 'selectState': {
                html += `<div class="col-12 col-md-6 mb-3" id="container_${element.name}">`;
                const stateResp = await fetch(`/get-state-details-by-type`);
                const stateData = await stateResp.json();
                let stateOptions = '';
                stateData.forEach(value => {
                    stateOptions += `<option value="${value.id}">${value.name}</option>`;
                });
                html += `
                    <label for="${fieldId}" class="form-label">${element.label} ${requiredStar}</label>
                    <select class="form-control" id="${fieldId}" name="${element.name}"${requiredAttr}>
                        <option value="">Select</option>
                        ${stateOptions}
                    </select>
                    <div class="invalid-feedback" id="error_${element.name}"></div>
                `;
                html += '</div>';
                formRenderer.innerHTML += html;
                break;
            }

            case 'selectCity': {
                html += `<div class="col-12 col-md-6 mb-3" id="container_${element.name}">`;
                const cityResp = await fetch(`/get-state-details-by-type?type=city`);
                const cityData = await cityResp.json();
                let cityOptions = '';
                cityData.forEach(value => {
                    cityOptions += `<option value="${value.id}">${value.name}</option>`;
                });
                html += `
                    <label for="${fieldId}" class="form-label">${element.label} ${requiredStar}</label>
                    <select class="form-control" id="${fieldId}" name="${element.name}"${requiredAttr}>
                        <option value="">Select</option>
                        ${cityOptions}
                    </select>
                    <div class="invalid-feedback" id="error_${element.name}"></div>
                `;
                html += '</div>';
                formRenderer.innerHTML += html;
                break;
            }

            case 'selectStateCity':
                html += `<div class="col-12 col-md-6 mb-3" id="container_${element.name}">`;
                let optionsselectStateCity = '';
                let updatefnCode = '';

                if (targetElementId != 0) {
                    updatefnCode = `onchange="updateCity(this,'${targetElementId}')"`;                
                    if (element.fieldType == "state") {
                        const response = await fetch(`/get-state-details-by-type`);
                        const data = await response.json();
                        let dropdown_options = [];
                        data.forEach(value => {
                            dropdown_options[value.id] = value.name;
                        });
                        element.options = dropdown_options;
                    }
                }

                if (element.options) {
                    element.options.forEach((key, value) => {
                        optionsselectStateCity += `<option value="${value}">${key}</option>`;
                    });
                }

                html += `
                    <label for="${fieldId}" class="form-label">${element.label} ${requiredStar}</label>
                    <select class="form-select${classAttr}" id="${fieldId}" ${updatefnCode} name="${element.name}"${requiredAttr}>
                        <option value="">Select</option>
                        ${optionsselectStateCity}
                    </select>
                    <div class="invalid-feedback" id="error_${element.name}"></div>
                `;
                html += '</div>';
                formRenderer.innerHTML += html;
                break;

            case 'selectStateCollege':
                html += `<div class="col-12 col-md-6 mb-3" id="container_${element.name}">`;
                if (element.fieldType === 'state') {
                    let updateCollegeFn = '';
                    if (targetElementId != 0) {
                        updateCollegeFn = `onchange="updateCollegeByState(this,'${targetElementId}')"`;
                    }
                    const stateResp = await fetch(`/get-state-details-by-type`);
                    const stateData = await stateResp.json();
                    let stateOptions = '';
                    stateData.forEach(value => {
                        stateOptions += `<option value="${value.id}">${value.name}</option>`;
                    });
                    html += `
                        <label for="${fieldId}" class="form-label">${element.label} ${requiredStar}</label>
                        <select class="form-select${classAttr}" id="${fieldId}" ${updateCollegeFn} name="${element.name}"${requiredAttr}>
                            <option value="">Select</option>
                            ${stateOptions}
                        </select>
                        <div class="invalid-feedback" id="error_${element.name}"></div>
                    `;
                } else if (element.fieldType === 'city') {
                    // college.city is plain text, so this is auto-filled and read-only
                    html += `
                        <label for="${fieldId}" class="form-label">${element.label} ${requiredStar}</label>
                        <input type="text" class="form-control${classAttr}" id="${fieldId}" name="${element.name}"${requiredAttr} readonly placeholder="Select college first">
                        <div class="invalid-feedback" id="error_${element.name}"></div>
                    `;
                } else {
                    // college: if it feeds a city field, fill it on change
                    const cityFn = targetElementId != 0 ? `onchange="updateCityByCollege(this,'${targetElementId}')"` : '';
                    html += `
                        <label for="${fieldId}" class="form-label">${element.label} ${requiredStar}</label>
                        <select class="form-control form-control${classAttr}" id="${fieldId}" ${cityFn} name="${element.name}"${requiredAttr}>
                            <option value="">Select state first</option>
                        </select>
                        <div class="invalid-feedback" id="error_${element.name}"></div>
                    `;
                }
                html += '</div>';
                formRenderer.innerHTML += html;
                break;

            case 'radio':
                html += `<div class="col-12 col-md-6 mb-3 checkbox-group" id="container_${element.name}" data-group="${element.name}" data-required="${element.required ? '1' : '0'}">`;
                html += `<label class="form-label">${element.label} ${requiredStar}</label>`;
                html += `<div class="custom-selection-group${classAttr}">`;
                if (element.options) {
    element.options.forEach((option, i) => {
        const optionId = `${fieldId}_${i}`;
        html += `
            <div class="form-check">
                <label class="form-check-label" for="${optionId}">
                    <input class="form-check-input" type="${element.type}" name="${element.name}${element.type === 'checkbox' ? '[]' : ''}" 
                        id="${optionId}" value="${option.replace(/"/g, '&quot;')}"${i === 0 && element.required && element.type === 'radio' ? ' required' : ''}>
                    ${option}
                    <span class="custom-indicator"></span>
                </label>
            </div>
        `;
    });
}
                html += `</div><div class="invalid-feedback" id="error_${element.name}"></div>`;
                html += '</div>';
                formRenderer.innerHTML += html;
                break;

                case 'checkbox':
            html += `<div class="col-12 col-md-6 mb-3 checkbox-group" id="container_${element.name}" data-group="${element.name}" data-required="${element.required ? '1' : '0'}">`;
            html += `<label class="form-label">${element.label} ${requiredStar}</label>`;
            html += `<div class="custom-selection-group${classAttr}">`;
            if (element.options) {
                element.options.forEach((option, i) => {
                    const optionId = `${fieldId}_${i}`;
                    html += `
                        <div class="form-check">
                            <label class="form-check-label" for="${optionId}">
                                <input class="form-check-input" type="checkbox" name="${element.name}[]" id="${optionId}" value="${option.replace(/"/g, '&quot;')}"${i === 0 && element.required ? ' required' : ''}>
                                ${option}
                                <span class="custom-indicator"></span>
                            </label>
                        </div>
                    `;
                });
            }
            html += `</div><div class="invalid-feedback" id="error_${element.name}"></div>`;
            html += '</div>';
            formRenderer.innerHTML += html;
            break;


            case 'file':
                html += `<div class="mb-3 form-group col-md-6" id="container_${element.name}">`;
                let extensions = element.extensionRequired || "";
                let extn1 = extensions ? `(e.g. ${extensions})` : "";
                html += `
    <label for="${fieldId}" class="form-label">${element.label} ${requiredStar} ${extn1} </label>
    <div class="file-upload-wrapper" onclick="document.getElementById('${fieldId}').click()">
        <i class="fa-solid fa-cloud-arrow-up file-upload-icon"></i>
        <div class="fw-semibold text-dark" style="font-size: 0.9rem;">Click to upload document</div>
        <div class="text-muted" style="font-size: 0.8rem;">Allowed types: ${extensions || 'All files'}</div>
        <input type="file" class="d-none file-check${classAttr}" id="${fieldId}" name="${element.name}"
            data-extensionRequired="${extensions}" accept="${element.accept || ''}"${requiredAttr} onchange="this.previousElementSibling.previousElementSibling.textContent = this.files[0]?.name || 'Click to upload document'">
    </div>
    <div class="invalid-feedback" id="error_${element.name}"></div>
`;
                if (element.sampleFile && element.sampleFile.url) {
                    html += `
                        <a href="${element.sampleFile.url}" target="_blank" rel="noopener"
                            class="btn btn-sm btn-outline-primary mt-2">
                            <i class="fa fa-file-text-o me-1"></i> Download ${element.sampleFile.name || 'Sample File'}
                        </a>
                    `;
                }
                html += '</div>';
                formRenderer.innerHTML += html;
                break;

            case 'download_file':
                html += `<div class="mb-3 form-group col-md-6" id="container_${element.name}">`;
                html += `<label class="form-label">${element.label}</label>`;
                if (element.sampleFile && element.sampleFile.url) {
                    html += `
                        <div class="d-flex align-items-center justify-content-center p-3 border rounded-3" style="background-color: #f8fafc; height: 58px;">
        <div class="text-primary me-3"><i class="fa-regular fa-file-lines fa-lg"></i></div>
        <div>
            <a href="${element.sampleFile.url}" target="_blank" rel="noopener" class="text-primary fw-semibold" style="font-size: 0.9rem; text-decoration: none;">
                <i class="fa-solid fa-download me-1"></i> Download ${element.sampleFile.name || 'File'}
            </a>
        </div>
    </div>
                    `;
                }
                html += '</div>';
                formRenderer.innerHTML += html;
                break;

            case 'hidden_field':
                html += `<input type="hidden" id="${fieldId}" name="${element.name}" value="${element.defaultValue || ''}" >`;
                formRenderer.innerHTML += html;
                break;

            case 'address': {
                const a = element.address || { current: {}, permanent: {}, permanentEnabled: false, sameAsCurrent: false };
                const nm = element.name;

                const usesState = a.current.state || (a.permanentEnabled && a.permanent.state);
                let stateOptions = '';
                if (usesState) {
                    const r = await fetch(`/get-state-details-by-type`);
                    const d = await r.json();
                    d.forEach(v => stateOptions += `<option value="${v.id}">${v.name}</option>`);
                }
                const allCityOptions = async () => {
                    const r = await fetch(`/get-state-details-by-type?type=city`);
                    const d = await r.json();
                    return d.map(v => `<option value="${v.id}">${v.name}</option>`).join('');
                };

                const section = (prefix, cfg, cityOpts) => {
                    let s = '';
                    if (cfg.state) {
                        const casc = cfg.city ? ` onchange="updateCity(this,'${nm}_${prefix}_city')"` : '';
                        s += `<div class="col-12 col-md-6 mb-3" id="container_${nm}_${prefix}_state">
                            <label class="form-label" for="${nm}_${prefix}_state">State ${requiredStar}</label>
                            <select class="form-control" id="${nm}_${prefix}_state" name="${nm}_${prefix}_state"${requiredAttr}${casc}>
                                <option value="">Select</option>${stateOptions}
                            </select>
                            <div class="invalid-feedback" id="error_${nm}_${prefix}_state"></div>
                        </div>`;
                    }
                    if (cfg.city) {
                        s += `<div class="col-12 col-md-6 mb-3" id="container_${nm}_${prefix}_city">
                            <label class="form-label" for="${nm}_${prefix}_city">City ${requiredStar}</label>
                            <select class="form-control" id="${nm}_${prefix}_city" name="${nm}_${prefix}_city"${requiredAttr}>
                                <option value="">Select</option>${cityOpts}
                            </select>
                            <div class="invalid-feedback" id="error_${nm}_${prefix}_city"></div>
                        </div>`;
                    }
                    if (cfg.pin) {
                        s += `<div class="col-12 col-md-6 mb-3" id="container_${nm}_${prefix}_pin">
                            <label class="form-label" for="${nm}_${prefix}_pin">Pin Code ${requiredStar}</label>
                            <input type="number" class="form-control" id="${nm}_${prefix}_pin" name="${nm}_${prefix}_pin"${requiredAttr} data-numlencheck="1" data-minlen="6" data-maxlen="6" data-allowmsg="Pin Code must be exactly 6 digits.">
                            <div class="invalid-feedback" id="error_${nm}_${prefix}_pin"></div>
                        </div>`;
                    }
                    return s;
                };

                const currCityOpts = (a.current.city && !a.current.state) ? await allCityOptions() : '';
                let inner = `<div class="col-12"><h5 class="mb-2">Current Address</h5></div>` + section('curr', a.current, currCityOpts);

                // only show permanent block if at least one field is enabled
                const hasPermFields = !!(a.permanent && (a.permanent.state || a.permanent.city || a.permanent.pin));

                if (a.permanentEnabled && hasPermFields) {
                    if (a.sameAsCurrent) {
                        inner += `<div class="col-12 mb-2"><div class="form-check">
                            <input class="form-check-input" type="checkbox" id="${nm}_same_as_current" onchange="toggleSameAsCurrent('${nm}', this.checked)">
                            <label class="form-check-label" for="${nm}_same_as_current">Permanent address same as current</label>
                        </div></div>`;
                    }
                    const permCityOpts = (a.permanent.city && !a.permanent.state) ? await allCityOptions() : '';
                    inner += `<div class="col-12"><h5 class="mb-2 mt-2">Permanent Address</h5></div>` + section('perm', a.permanent, permCityOpts);
                }

                html += `<div class="col-12" id="container_${nm}">
                    <label class="form-label fw-bold d-block">${element.label}</label>
                    <div class="row g-3">${inner}</div>
                </div>`;
                formRenderer.innerHTML += html;
                break;
            }

            default:
                html += `<div class="mb-3 form-group col-md-6" id="container_${element.name}">`;
                html += `<div class="alert alert-warning">Unknown element type: ${element.type}</div>`;
                html += '</div>';
                formRenderer.innerHTML += html;
        }

        return html;
    }


   
    // Function to check if a condition is met
    function isConditionMet(conditional, formStructure) {
        // If there's no condition, it's always met
        // console.log(conditional);
        if (!conditional || !conditional.field || !conditional.operator) {
            return true;
        }
        if (conditional.value === '' || conditional.value == null) {
            return true;
        }
        // Find the field that controls this condition
        // console.log(`[name="${conditional.field}"]`);
        const nodes = document.querySelectorAll(
            `[name="${conditional.field}"], [name="${conditional.field}[]"]`
        );
        if (!nodes.length) return false;

        const first = nodes[0];
        const tag  = first.tagName.toLowerCase();
        const type = (first.getAttribute('type') || '').toLowerCase();

        let value = '';
        let values = [];
        const targetValue = conditional.value;

        if (type === 'radio') {
        const checked = Array.from(nodes).find(n => n.checked);
        value = checked ? checked.value : '';
        }
        else if (type === 'checkbox') {
        values = Array.from(nodes).filter(n => n.checked).map(n => n.value);
        }
        // select multiple → selected options
        else if (tag === 'select' && first.multiple) {
        values = Array.from(first.selectedOptions).map(o => o.value);
        }
        // everything else → simple scalar
        else {
        value = first.value ?? '';
        }

        
        switch (conditional.operator) {
            case 'equals':
                return values.length
                    ? values.map(String).includes(String(targetValue))
                    : String(value) === String(targetValue);

            case 'not_equals':
                return values.length
                    ? !values.map(String).includes(String(targetValue))
                    : String(value) !== String(targetValue);

            case 'contains':
                // if we have an array (checkbox/multi-select), look for target in array
                return values.length
                ? values.map(String).includes(String(targetValue))
                : String(value).includes(String(targetValue));

            case 'not_contains':
                return values.length
                ? !values.map(String).includes(String(targetValue))
                : !String(value).includes(String(targetValue));

            case 'greater_than':
                return parseFloat(value) > parseFloat(targetValue);

            case 'less_than':
                return parseFloat(value) < parseFloat(targetValue);

            case 'in': {
                const list = Array.isArray(targetValue)
                    ? targetValue.map(String)
                    : String(targetValue ?? '').split(',').map(s => s.trim()).filter(Boolean);
                return values.length
                    ? values.map(String).some(v => list.includes(v))
                    : list.includes(String(value));
            }

            case 'not_in': {
                const list = Array.isArray(targetValue)
                    ? targetValue.map(String)
                    : String(targetValue ?? '').split(',').map(s => s.trim()).filter(Boolean);
                return values.length
                    ? !values.map(String).some(v => list.includes(v))
                    : !list.includes(String(value));
            }

            default:
                return true;
            }

    }
    
    function getElementConditionList(element) {
        if (Array.isArray(element.conditions) && element.conditions.length) return element.conditions;
        if (element.conditional && element.conditional.field) return [element.conditional];
        return [];
    }

    function areConditionsMet(element) {
        const list = getElementConditionList(element);
        if (!list.length) return true;
        const logic = (element.conditionLogic || 'all').toLowerCase();
        if (logic === 'any') return list.some(c => isConditionMet(c, null));
        return list.every(c => isConditionMet(c, null));
    }

    // Function to set up conditional logic
    function setupConditionalLogic(formStructure) {
        const conditionalElements = formStructure.filter(element => getElementConditionList(element).length > 0);
        if (conditionalElements.length === 0) return;

        function applyElementVisibility(element) {
            const containerElement = document.getElementById(`container_${element.name}`);
            if (!containerElement) return;
            const isMet = areConditionsMet(element);
            containerElement.style.display = isMet ? 'block' : 'none';
            containerElement.querySelectorAll('input, select, textarea').forEach(input => {
                if (input.dataset.wasRequired === undefined) {
                    input.dataset.wasRequired = input.hasAttribute('required') ? '1' : '0';
                }
                if (!isMet) {
                    input.removeAttribute('required');
                    input.classList.remove('is-invalid');
                } else if (input.dataset.wasRequired === '1') {
                    input.setAttribute('required', 'required');
                }
                input.disabled = !isMet;
            });
        }

        conditionalElements.forEach(element => {
            const triggerFields = [...new Set(getElementConditionList(element).map(c => c.field))];
            triggerFields.forEach(fieldName => {
                const controlFields = document.querySelectorAll(`[name="${fieldName}"], [name="${fieldName}[]"]`);
                controlFields.forEach(field => {
                    field.addEventListener('change', function() {
                        applyElementVisibility(element);
                    });
                });
            });
            applyElementVisibility(element);
        });
    }
    
    function isFieldVisible(input) {
        // Skip fields hidden by conditional logic (their container is display:none),
        // and native hidden inputs. offsetParent is null when an ancestor is display:none.
        if (input.type === 'hidden') return false;
        if (input.offsetParent !== null) return true;
        const container = input.closest('.form-group, [id^="container_"]');
        return container ? container.offsetParent !== null : false;
    }

    if (document.getElementById('formSubmission')) {
        document.getElementById('formSubmission').addEventListener('submit', function(e) {
            const form = e.target;

            // make sure every ticked "same as current" has actually copied its values
            form.querySelectorAll('[id$="_same_as_current"]').forEach(box => {
                if (!box.checked) return;
                const name = box.id.replace(/_same_as_current$/, '');
                ['state', 'city', 'pin'].forEach(k => copyAddressField(name, k));
            });

            const inputs = form.querySelectorAll('input, select, textarea');
            let isValid = true;
            let firstInvalid = null;

            inputs.forEach(input => {
                if (input.disabled) return;
                if (!isFieldVisible(input)) return;

                if (input.hasAttribute('required') && !fieldHasValue(input)) {
                    checkRequired(input);
                    isValid = false;
                    if (!firstInvalid) firstInvalid = input;
                }

                // re-check exact length here too, in case the field was pre-filled and never typed in
                const exactLen = input.getAttribute && input.getAttribute('data-exactlen');
                if (exactLen && input.value !== '' && input.value.length !== parseInt(exactLen)) {
                    input.setCustomValidity(input.getAttribute('data-allowmsg') || 'Invalid length.');
                }

                if (input.type !== 'checkbox' && input.type !== 'radio' && !input.checkValidity()) {
                    setFieldMessage(input, false, input.validationMessage || input.getAttribute('data-allowmsg') || input.title || 'Please enter a valid value.');
                    isValid = false;
                    if (!firstInvalid) firstInvalid = input;
                }
            });

            if (!isValid) {
                e.preventDefault();
                if (firstInvalid) {
                    const target = firstInvalid.closest('.form-group, [id^="container_"]') || firstInvalid;
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    try { firstInvalid.focus({ preventScroll: true }); } catch (err) {}
                }
                if (typeof toastr !== 'undefined') {
                    toastr.error('Please fill all required fields correctly before submitting.');
                }
            }
        });
    }
// });